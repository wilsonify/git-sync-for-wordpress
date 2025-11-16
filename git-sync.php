<?php
/**
 * Plugin Name: GitSync
 * Plugin URI: https://github.com/wilsonify/git-sync-for-wordpress
 * Description: Keeps WordPress content (pages, posts, WooCommerce products) in sync with a remote Git repository containing Markdown files.
 * Version: 1.0.0
 * Author: GitSync Contributors
 * Author URI: https://github.com/wilsonify/git-sync-for-wordpress
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: gitsync
 * Domain Path: /languages
 */

use GitSync\GitSyncAdminSettings;
use GitSync\GitSyncContentSync;
use GitSync\GitSyncSyncScheduler;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'GITSYNC_VERSION', '1.0.0' );
define( 'GITSYNC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GITSYNC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GITSYNC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once GITSYNC_PLUGIN_DIR . 'vendor/autoload.php';
require_once GITSYNC_PLUGIN_DIR . 'includes/autoload.php';

/**
 * Main GitSync Class
 */
class GitSync {
    
    /**
     * Instance of this class
     */
    private static $instance = null;

    /** @var GitSyncSyncScheduler */
    private $scheduler;
    
    /**
     * Get instance of the class
     */
    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->scheduler = new GitSyncSyncScheduler();
        $this->initHooks();
    }
    
    /**
     * Initialize hooks
     */
    private function initHooks() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        
        add_action( 'plugins_loaded', array( $this, 'loadTextdomain' ) );
        add_action( 'admin_menu', array( GitSyncAdminSettings::class, 'addAdminMenu' ) );
        add_action( 'admin_init', array( GitSyncAdminSettings::class, 'registerSettings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminAssets' ) );
        
        // AJAX hooks for sync actions
    add_action( 'wp_ajax_gitsync_manual_sync', array( GitSyncContentSync::class, 'manualSync' ) );
        
        // Scheduled sync
        add_action( 'gitsync_scheduled_sync', array( GitSyncContentSync::class, 'scheduledSync' ) );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create directory for local git repository
        $repo_dir = wp_upload_dir()['basedir'] . '/gitsync-repo';
        if ( ! file_exists( $repo_dir ) ) {
            wp_mkdir_p( $repo_dir );
        }
        
        // Schedule sync event
        if ( ! wp_next_scheduled( 'gitsync_scheduled_sync' ) ) {
            wp_schedule_event( time(), 'hourly', 'gitsync_scheduled_sync' );
        }
        
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook( 'gitsync_scheduled_sync' );
        flush_rewrite_rules();
    }
    
    /**
     * Load plugin textdomain
     */
    public function loadTextdomain() {
        load_plugin_textdomain( 'gitsync', false, dirname( GITSYNC_PLUGIN_BASENAME ) . '/languages' );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets( $hook ) {
        if ( 'toplevel_page_gitsync-settings' !== $hook ) {
            return;
        }
        
        wp_enqueue_style( 'gitsync-admin', GITSYNC_PLUGIN_URL . 'assets/css/admin.css', array(), GITSYNC_VERSION );
        wp_enqueue_script( 'gitsync-admin', GITSYNC_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), GITSYNC_VERSION, true );
        
        wp_localize_script( 'gitsync-admin', 'gitsyncAdmin', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'gitsync_nonce' ),
            'strings' => array(
                'syncing' => __( 'Syncing...', 'gitsync' ),
                'sync_complete' => __( 'Sync completed successfully!', 'gitsync' ),
                'sync_error' => __( 'Sync failed. Please check the logs.', 'gitsync' ),
            ),
        ) );
    }
}

/**
 * Initialize the plugin
 */
function gitsyncInit() {
    return GitSync::getInstance();
}

// Start the plugin
gitsyncInit();
