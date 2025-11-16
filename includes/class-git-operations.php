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
        $result = GitSync_Command_Runner::run( array( 'git', '--version' ) );
        return true === $result['success'];
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

        // Validate repo URL and branch to reduce injection attack surface
        if ( ! $this->validate_repo_url( $repo_url ) ) {
            $this->log_error( 'Invalid repository URL provided.' );
            return new WP_Error( 'invalid_repo_url', __( 'Repository URL is invalid.', 'gitsync' ) );
        }

        if ( ! $this->validate_branch( $branch ) ) {
            $this->log_error( 'Invalid branch name provided: ' . $branch );
            return new WP_Error( 'invalid_branch', __( 'Branch name is invalid.', 'gitsync' ) );
        }
        
        // Build authenticated URL if credentials provided
        if ( ! empty( $username ) && ! empty( $token ) ) {
            $repo_url = $this->add_credentials_to_url( $repo_url, $username, $token );
        }
        
        $result = GitSync_Command_Runner::run(
            array( 'git', 'clone', '-b', $branch, $repo_url, '.' ),
            $this->repo_path
        );

        if ( true !== $result['success'] ) {
            $msg = implode( "\n", $result['output'] );
            // Mask token if present before logging
            $this->log_error( 'Clone failed: ' . $this->mask_token_in_message( $msg, $token ) );
            return new WP_Error( 'clone_failed', __( 'Failed to clone repository.', 'gitsync' ), $result['output'] );
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
        
        // Validate branch
        if ( ! $this->validate_branch( $branch ) ) {
            $this->log_error( 'Invalid branch name provided: ' . $branch );
            return new WP_Error( 'invalid_branch', __( 'Branch name is invalid.', 'gitsync' ) );
        }

        // Configure credentials if provided
        if ( ! empty( $username ) && ! empty( $token ) ) {
            $this->configure_credentials( $username, $token );
        }

        $fetch = GitSync_Command_Runner::run( array( 'git', 'fetch', 'origin' ), $this->repo_path );
        if ( true !== $fetch['success'] ) {
            $msg = implode( "\n", $fetch['output'] );
            $this->log_error( 'Pull failed (fetch): ' . $this->mask_token_in_message( $msg, $token ) );
            return new WP_Error( 'pull_failed', __( 'Failed to pull changes.', 'gitsync' ), $fetch['output'] );
        }

        $reset = GitSync_Command_Runner::run(
            array( 'git', 'reset', '--hard', sprintf( 'origin/%s', $branch ) ),
            $this->repo_path
        );

        if ( true !== $reset['success'] ) {
            $msg = implode( "\n", $reset['output'] );
            $this->log_error( 'Pull failed (reset): ' . $this->mask_token_in_message( $msg, $token ) );
            return new WP_Error( 'pull_failed', __( 'Failed to pull changes.', 'gitsync' ), $reset['output'] );
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

        // URL-encode credentials to prevent special chars from breaking the URL/shell
        $u = rawurlencode( $username );
        $t = rawurlencode( $token );

        return sprintf( '%s://%s:%s@%s%s', $scheme, $u, $t, $host, $path );
    }

    /**
     * Validate a branch name to avoid shell injection via branch input.
     * Allows letters, numbers, dot, underscore, hyphen and slash (for names like feature/x).
     */
    private function validate_branch( $branch ) {
        if ( ! is_string( $branch ) ) {
            return false;
        }
        return (bool) preg_match( '/^[A-Za-z0-9._\-\/]+$/', $branch );
    }

    /**
     * Basic repo URL validation - only allow common schemes and require a host/path
     */
    private function validate_repo_url( $url ) {
        $parsed = parse_url( $url );
        if ( ! $parsed || empty( $parsed['host'] ) ) {
            return false;
        }

        $scheme = isset( $parsed['scheme'] ) ? $parsed['scheme'] : '';
        $allowed = array( 'https', 'http', 'git', 'ssh' );
        // Also allow scp-like git@host:repo.git forms (no scheme) - keep basic check
        if ( in_array( $scheme, $allowed, true ) ) {
            return true;
        }

        // If no scheme but contains @ and ':' it's likely an scp-style URL (git@github.com:owner/repo.git)
        if ( strpos( $url, '@' ) !== false && strpos( $url, ':' ) !== false ) {
            return true;
        }

        return false;
    }

    /**
     * Mask token values in a message to avoid leaking secrets in logs
     */
    private function mask_token_in_message( $message, $token ) {
        if ( empty( $token ) ) {
            return $message;
        }
        return str_replace( $token, '****', $message );
    }
    
    /**
     * Configure credentials for git
     */
    private function configure_credentials( $username, $token ) {
        $repo_url = get_option( 'gitsync_repo_url', '' );
        $auth_url = $this->add_credentials_to_url( $repo_url, $username, $token );
        
        GitSync_Command_Runner::run(
            array( 'git', 'config', 'credential.helper', 'store' ),
            $this->repo_path
        );
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
