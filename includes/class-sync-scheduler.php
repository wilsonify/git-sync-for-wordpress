<?php
/**
 * Sync Scheduler Class
 * Handles scheduled sync operations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GitSync_Sync_Scheduler {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_cron_schedules( $schedules ) {
        $schedules['gitsync_hourly'] = array(
            'interval' => 3600,
            'display' => __( 'Once Hourly (GitSync)', 'gitsync' ),
        );
        
        $schedules['gitsync_daily'] = array(
            'interval' => 86400,
            'display' => __( 'Once Daily (GitSync)', 'gitsync' ),
        );
        
        return $schedules;
    }
}
