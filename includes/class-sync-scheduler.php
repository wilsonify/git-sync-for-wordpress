<?php
/**
 * Sync Scheduler Class
 * Handles scheduled sync operations
 */

namespace GitSync;

use function __;
use function add_filter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GitSyncSyncScheduler {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_filter( 'cron_schedules', array( $this, 'addCronSchedules' ) );
    }
    
    /**
     * Add custom cron schedules
     */
    public function addCronSchedules( $schedules ) {
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
