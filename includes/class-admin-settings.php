<?php
/**
 * Admin Settings Class
 * Handles admin settings page and configuration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GitSyncAdminSettings {
    
    /**
     * Add admin menu
     */
    public static function addAdminMenu() {
        add_menu_page(
            __( 'GitSync Settings', 'gitsync' ),
            __( 'GitSync', 'gitsync' ),
            'manage_options',
            'gitsync-settings',
            array( __CLASS__, 'render_settings_page' ),
            'dashicons-update',
            100
        );
    }
    
    /**
     * Register settings
     */
    public static function registerSettings() {
        // Repository settings
        register_setting( 'gitsync_settings', 'gitsync_repo_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
        ) );
        
        register_setting( 'gitsync_settings', 'gitsync_branch', array(
            'type' => 'string',
            'default' => 'main',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        
        register_setting( 'gitsync_settings', 'gitsync_username', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        
        register_setting( 'gitsync_settings', 'gitsync_token', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        
        register_setting( 'gitsync_settings', 'gitsync_auto_sync', array(
            'type' => 'boolean',
            'default' => false,
        ) );
        
        // Add settings sections
        add_settings_section(
            'gitsync_repo_section',
            __( 'Repository Settings', 'gitsync' ),
            array( __CLASS__, 'render_repo_section' ),
            'gitsync-settings'
        );
        
        add_settings_section(
            'gitsync_sync_section',
            __( 'Sync Settings', 'gitsync' ),
            array( __CLASS__, 'render_sync_section' ),
            'gitsync-settings'
        );
        
        // Add settings fields
        add_settings_field(
            'gitsync_repo_url',
            __( 'Repository URL', 'gitsync' ),
            array( __CLASS__, 'render_repo_url_field' ),
            'gitsync-settings',
            'gitsync_repo_section'
        );
        
        add_settings_field(
            'gitsync_branch',
            __( 'Branch', 'gitsync' ),
            array( __CLASS__, 'render_branch_field' ),
            'gitsync-settings',
            'gitsync_repo_section'
        );
        
        add_settings_field(
            'gitsync_username',
            __( 'Username', 'gitsync' ),
            array( __CLASS__, 'render_username_field' ),
            'gitsync-settings',
            'gitsync_repo_section'
        );
        
        add_settings_field(
            'gitsync_token',
            __( 'Access Token', 'gitsync' ),
            array( __CLASS__, 'render_token_field' ),
            'gitsync-settings',
            'gitsync_repo_section'
        );
        
        add_settings_field(
            'gitsync_auto_sync',
            __( 'Auto Sync', 'gitsync' ),
            array( __CLASS__, 'render_auto_sync_field' ),
            'gitsync-settings',
            'gitsync_sync_section'
        );
    }
    
    /**
     * Render settings page
     */
    public static function renderSettingsPage() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        // Handle manual sync trigger
        $sync_message = '';
        if ( isset( $_GET['sync_triggered'] ) && $_GET['sync_triggered'] === '1' ) {
            $sync_message = '<div class="notice notice-info"><p>' . 
                __( 'Sync has been triggered. Check the status below.', 'gitsync' ) . 
                '</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <?php echo $sync_message; ?>
            
            <div class="gitsync-admin-container">
                <div class="gitsync-settings-form">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields( 'gitsync_settings' );
                        do_settings_sections( 'gitsync-settings' );
                        submit_button( __( 'Save Settings', 'gitsync' ) );
                        ?>
                    </form>
                </div>
                
                <div class="gitsync-sync-controls">
                    <h2><?php _e( 'Sync Controls', 'gitsync' ); ?></h2>
                    <p><?php _e( 'Manually trigger a sync to pull the latest content from your Git repository.', 'gitsync' ); ?></p>
                    <button id="gitsync-manual-sync" class="button button-primary button-large">
                        <?php _e( 'Sync Now', 'gitsync' ); ?>
                    </button>
                    <div id="gitsync-sync-status"></div>
                </div>
                
                <div class="gitsync-info">
                    <h2><?php _e( 'How It Works', 'gitsync' ); ?></h2>
                    <p><?php _e( 'GitSync keeps your WordPress content synchronized with a Git repository containing Markdown files.', 'gitsync' ); ?></p>
                    <ul>
                        <li><?php _e( 'Configure your Git repository URL and credentials above', 'gitsync' ); ?></li>
                        <li><?php _e( 'Click "Sync Now" to pull the latest content from your repository', 'gitsync' ); ?></li>
                        <li><?php _e( 'Markdown files will be converted to WordPress posts, pages, or WooCommerce products', 'gitsync' ); ?></li>
                        <li><?php _e( 'Enable Auto Sync to automatically sync content hourly', 'gitsync' ); ?></li>
                    </ul>
                    
                    <h3><?php _e( 'Markdown File Format', 'gitsync' ); ?></h3>
                    <p><?php _e( 'Your Markdown files can include YAML frontmatter for metadata:', 'gitsync' ); ?></p>
                    <pre>---
title: My Post Title
type: post
status: publish
categories: Category1, Category2
tags: tag1, tag2
---

# My Post Title

Your content here...</pre>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render repository section
     */
    public static function renderRepoSection() {
        echo '<p>' . __( 'Configure your Git repository connection settings.', 'gitsync' ) . '</p>';
    }
    
    /**
     * Render sync section
     */
    public static function renderSyncSection() {
        echo '<p>' . __( 'Configure synchronization behavior.', 'gitsync' ) . '</p>';
    }
    
    /**
     * Render repository URL field
     */
    public static function renderRepoUrlField() {
        $value = get_option( 'gitsync_repo_url', '' );
        ?>
        <input type="url" name="gitsync_repo_url" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <p class="description">
            <?php _e( 'The HTTPS URL of your Git repository (e.g., https://github.com/username/repo.git)', 'gitsync' ); ?>
        </p>
        <?php
    }
    
    /**
     * Render branch field
     */
    public static function renderBranchField() {
        $value = get_option( 'gitsync_branch', 'main' );
        ?>
        <input type="text" name="gitsync_branch" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <p class="description">
            <?php _e( 'The branch to sync (default: main)', 'gitsync' ); ?>
        </p>
        <?php
    }
    
    /**
     * Render username field
     */
    public static function renderUsernameField() {
        $value = get_option( 'gitsync_username', '' );
        ?>
        <input type="text" name="gitsync_username" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <p class="description">
            <?php _e( 'Git username (required for private repositories)', 'gitsync' ); ?>
        </p>
        <?php
    }
    
    /**
     * Render token field
     */
    public static function renderTokenField() {
        $value = get_option( 'gitsync_token', '' );
        ?>
        <input type="password" name="gitsync_token" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <p class="description">
            <?php _e( 'Personal access token or password (required for private repositories)', 'gitsync' ); ?>
        </p>
        <?php
    }
    
    /**
     * Render auto sync field
     */
    public static function renderAutoSyncField() {
        $value = get_option( 'gitsync_auto_sync', false );
        ?>
        <label>
            <input type="checkbox" name="gitsync_auto_sync" value="1" <?php checked( $value, true ); ?> />
            <?php _e( 'Automatically sync content every hour', 'gitsync' ); ?>
        </label>
        <?php
    }
}
