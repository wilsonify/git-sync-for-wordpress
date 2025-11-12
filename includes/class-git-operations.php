<?php
/**
 * Git Operations Class
 * Handles all Git operations for the plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GitSync_Git_Operations {
    
    /**
     * Local repository path
     */
    private $repo_path;
    
    /**
     * Constructor
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->repo_path = $upload_dir['basedir'] . '/gitsync-repo';
    }
    
    /**
     * Get repository path
     */
    public function get_repo_path() {
        return $this->repo_path;
    }
    
    /**
     * Check if git is available
     */
    public function is_git_available() {
        $output = array();
        $return_var = 0;
        exec( 'git --version 2>&1', $output, $return_var );
        return $return_var === 0;
    }
    
    /**
     * Clone or update repository
     */
    public function sync_repository() {
        $repo_url = get_option( 'gitsync_repo_url', '' );
        $branch = get_option( 'gitsync_branch', 'main' );
        
        if ( empty( $repo_url ) ) {
            return new WP_Error( 'no_repo_url', __( 'Repository URL is not configured.', 'gitsync' ) );
        }
        
        if ( ! $this->is_git_available() ) {
            return new WP_Error( 'git_not_available', __( 'Git is not available on this server.', 'gitsync' ) );
        }
        
        // Check if repository already exists
        if ( $this->is_repo_initialized() ) {
            return $this->pull_changes( $branch );
        } else {
            return $this->clone_repository( $repo_url, $branch );
        }
    }
    
    /**
     * Check if repository is initialized
     */
    private function is_repo_initialized() {
        return file_exists( $this->repo_path . '/.git' );
    }
    
    /**
     * Clone repository
     */
    private function clone_repository( $repo_url, $branch ) {
        // Ensure directory exists and is empty
        if ( ! file_exists( $this->repo_path ) ) {
            wp_mkdir_p( $this->repo_path );
        }
        
        $username = get_option( 'gitsync_username', '' );
        $token = get_option( 'gitsync_token', '' );
        
        // Build authenticated URL if credentials provided
        if ( ! empty( $username ) && ! empty( $token ) ) {
            $repo_url = $this->add_credentials_to_url( $repo_url, $username, $token );
        }
        
        $command = sprintf(
            'cd %s && git clone -b %s %s . 2>&1',
            escapeshellarg( $this->repo_path ),
            escapeshellarg( $branch ),
            escapeshellarg( $repo_url )
        );
        
        $output = array();
        $return_var = 0;
        exec( $command, $output, $return_var );
        
        if ( $return_var !== 0 ) {
            $this->log_error( 'Clone failed: ' . implode( "\n", $output ) );
            return new WP_Error( 'clone_failed', __( 'Failed to clone repository.', 'gitsync' ), $output );
        }
        
        $this->log_info( 'Repository cloned successfully.' );
        return true;
    }
    
    /**
     * Pull changes from repository
     */
    private function pull_changes( $branch ) {
        $username = get_option( 'gitsync_username', '' );
        $token = get_option( 'gitsync_token', '' );
        
        // Configure credentials if provided
        if ( ! empty( $username ) && ! empty( $token ) ) {
            $this->configure_credentials( $username, $token );
        }
        
        $command = sprintf(
            'cd %s && git fetch origin && git reset --hard origin/%s 2>&1',
            escapeshellarg( $this->repo_path ),
            escapeshellarg( $branch )
        );
        
        $output = array();
        $return_var = 0;
        exec( $command, $output, $return_var );
        
        if ( $return_var !== 0 ) {
            $this->log_error( 'Pull failed: ' . implode( "\n", $output ) );
            return new WP_Error( 'pull_failed', __( 'Failed to pull changes.', 'gitsync' ), $output );
        }
        
        $this->log_info( 'Changes pulled successfully.' );
        return true;
    }
    
    /**
     * Add credentials to URL
     */
    private function add_credentials_to_url( $url, $username, $token ) {
        $parsed = parse_url( $url );
        if ( ! $parsed ) {
            return $url;
        }
        
        $scheme = isset( $parsed['scheme'] ) ? $parsed['scheme'] : 'https';
        $host = isset( $parsed['host'] ) ? $parsed['host'] : '';
        $path = isset( $parsed['path'] ) ? $parsed['path'] : '';
        
        return sprintf( '%s://%s:%s@%s%s', $scheme, $username, $token, $host, $path );
    }
    
    /**
     * Configure credentials for git
     */
    private function configure_credentials( $username, $token ) {
        $repo_url = get_option( 'gitsync_repo_url', '' );
        $auth_url = $this->add_credentials_to_url( $repo_url, $username, $token );
        
        exec( sprintf(
            'cd %s && git config credential.helper store 2>&1',
            escapeshellarg( $this->repo_path )
        ) );
    }
    
    /**
     * Get list of markdown files in repository
     */
    public function get_markdown_files() {
        if ( ! $this->is_repo_initialized() ) {
            return array();
        }
        
        $files = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $this->repo_path, RecursiveDirectoryIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ( $iterator as $file ) {
            if ( $file->isFile() && in_array( strtolower( $file->getExtension() ), array( 'md', 'markdown' ) ) ) {
                // Skip .git directory
                if ( strpos( $file->getPathname(), '/.git/' ) !== false ) {
                    continue;
                }
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Log error message
     */
    private function log_error( $message ) {
        error_log( '[GitSync Error] ' . $message );
    }
    
    /**
     * Log info message
     */
    private function log_info( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[GitSync Info] ' . $message );
        }
    }
}
