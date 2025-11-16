<?php
/**
 * Git Operations Class
 * Handles all Git operations for the plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GitSyncGitOperations {
    
    /**
     * Local repository path
     */
    private $repoPath;
    
    /**
     * Constructor
     */
    public function __construct() {
    $upload_dir = wp_upload_dir();
    $this->repoPath = $upload_dir['basedir'] . '/gitsync-repo';
    }
    
    /**
     * Get repository path
     */
    public function getRepoPath() {
        return $this->repoPath;
    }
    
    /**
     * Check if git is available
     */
    public function isGitAvailable() {
        $output = array();
        $return_var = 0;
        exec( 'git --version 2>&1', $output, $return_var );
        return $return_var === 0;
    }
    
    /**
     * Clone or update repository
     */
    public function syncRepository() {
        $repo_url = get_option( 'gitsync_repo_url', '' );
        $branch = get_option( 'gitsync_branch', 'main' );
        
        if ( empty( $repo_url ) ) {
            return new WP_Error( 'no_repo_url', __( 'Repository URL is not configured.', 'gitsync' ) );
        }
        
        if ( ! $this->isGitAvailable() ) {
            return new WP_Error( 'git_not_available', __( 'Git is not available on this server.', 'gitsync' ) );
        }
        
        // Check if repository already exists
        if ( $this->isRepoInitialized() ) {
            return $this->pullChanges( $branch );
        } else {
            return $this->cloneRepository( $repo_url, $branch );
        }
    }
    
    /**
     * Check if repository is initialized
     */
    private function isRepoInitialized() {
    return file_exists( $this->repoPath . '/.git' );
    }
    
    /**
     * Clone repository
     */
    private function cloneRepository( $repo_url, $branch ) {
        // Ensure directory exists and is empty
        if ( ! file_exists( $this->repoPath ) ) {
            wp_mkdir_p( $this->repoPath );
        }
        
        $username = get_option( 'gitsync_username', '' );
        $token = get_option( 'gitsync_token', '' );

        $validation_error = $this->validateCloneInputs( $repo_url, $branch );
        if ( $validation_error instanceof WP_Error ) {
            return $validation_error;
        }
        
        // Build authenticated URL if credentials provided
        if ( ! empty( $username ) && ! empty( $token ) ) {
            $repo_url = $this->addCredentialsToUrl( $repo_url, $username, $token );
        }
        
        $command = sprintf(
            'cd %s && git clone -b %s %s . 2>&1',
            escapeshellarg( $this->repoPath ),
            escapeshellarg( $branch ),
            escapeshellarg( $repo_url )
        );
        
        $output = array();
        $return_var = 0;
        exec( $command, $output, $return_var );

        if ( $return_var !== 0 ) {
            $msg = implode( "\n", $output );
            // Mask token if present before logging
            $this->logError( 'Clone failed: ' . $this->maskTokenInMessage( $msg, $token ) );
            return new WP_Error( 'clone_failed', __( 'Failed to clone repository.', 'gitsync' ), $output );
        }
        
    $this->logInfo( 'Repository cloned successfully.' );
        return true;
    }
    
    /**
     * Pull changes from repository
     */
    private function pullChanges( $branch ) {
        $username = get_option( 'gitsync_username', '' );
        $token = get_option( 'gitsync_token', '' );

        $branch_validation = $this->validateBranchInput( $branch );
        if ( $branch_validation instanceof WP_Error ) {
            return $branch_validation;
        }

        // Configure credentials if provided
        if ( ! empty( $username ) && ! empty( $token ) ) {
            $this->configureCredentials( $username, $token );
        }

        $command = sprintf(
            'cd %s && git fetch origin && git reset --hard origin/%s 2>&1',
            escapeshellarg( $this->repoPath ),
            escapeshellarg( $branch )
        );
        
        $output = array();
        $return_var = 0;
        exec( $command, $output, $return_var );
        
        if ( $return_var !== 0 ) {
            $msg = implode( "\n", $output );
            $this->logError( 'Pull failed: ' . $this->maskTokenInMessage( $msg, $token ) );
            return new WP_Error( 'pull_failed', __( 'Failed to pull changes.', 'gitsync' ), $output );
        }
        
    $this->logInfo( 'Changes pulled successfully.' );
        return true;
    }
    
    /**
     * Add credentials to URL
     */
    private function addCredentialsToUrl( $url, $username, $token ) {
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
    private function validateBranch( $branch ) {
        if ( ! is_string( $branch ) ) {
            return false;
        }
        return (bool) preg_match( '/^[A-Za-z0-9._\-\/]+$/', $branch );
    }

    /**
     * Basic repo URL validation - only allow common schemes and require a host/path
     */
    private function validateRepoUrl( $url ) {
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
    private function maskTokenInMessage( $message, $token ) {
        if ( empty( $token ) ) {
            return $message;
        }
        return str_replace( $token, '****', $message );
    }
    
    /**
     * Configure credentials for git
     */
    private function configureCredentials( $username, $token ) {
    $repo_url = get_option( 'gitsync_repo_url', '' );
    $auth_url = $this->addCredentialsToUrl( $repo_url, $username, $token );
        
        exec( sprintf(
            'cd %s && git config credential.helper store 2>&1',
            escapeshellarg( $this->repoPath )
        ) );
    }

    /**
     * Validate clone inputs while keeping return count low.
     */
    private function validateCloneInputs( $repo_url, $branch ) {
        if ( ! $this->validateRepoUrl( $repo_url ) ) {
            $this->logError( 'Invalid repository URL provided.' );
            return new WP_Error( 'invalid_repo_url', __( 'Repository URL is invalid.', 'gitsync' ) );
        }

        return $this->validateBranchInput( $branch );
    }

    /**
     * Validate branch input and return WP_Error when invalid.
     */
    private function validateBranchInput( $branch ) {
        if ( ! $this->validateBranch( $branch ) ) {
            $this->logError( 'Invalid branch name provided: ' . $branch );
            return new WP_Error( 'invalid_branch', __( 'Branch name is invalid.', 'gitsync' ) );
        }

        return null;
    }
    
    /**
     * Get list of markdown files in repository
     */
    public function getMarkdownFiles() {
        if ( ! $this->isRepoInitialized() ) {
            return array();
        }
        
        $files = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $this->repoPath, RecursiveDirectoryIterator::SKIP_DOTS ),
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
    private function logError( $message ) {
        error_log( '[GitSync Error] ' . $message );
    }
    
    /**
     * Log info message
     */
    private function logInfo( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[GitSync Info] ' . $message );
        }
    }
}
