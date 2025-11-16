<?php

use GitSync\GitSyncGitOperations;
use PHPUnit\Framework\TestCase;

class GitOperationsValidationTest extends TestCase {
    protected function setUp(): void {
        $GLOBALS['gitsync_options'] = array();
    }

    public function test_default_repo_path_uses_upload_dir() {
        $gitOps = $this->createGitOps();
        $expected = rtrim( sys_get_temp_dir(), '/' ) . '/gitsync-repo';
        $this->assertSame( $expected, $gitOps->getRepoPath() );
    }

    public function test_set_repo_path_trims_trailing_slashes_and_ignores_empty_input() {
        $gitOps = $this->createGitOps( sys_get_temp_dir() . '/custom///' );
        $this->assertStringEndsWith( '/custom', $gitOps->getRepoPath() );

        $gitOps->setRepoPath( '' );
        $this->assertStringEndsWith( '/custom', $gitOps->getRepoPath() );

        $gitOps->setRepoPath( sys_get_temp_dir() . '/next///' );
        $this->assertStringEndsWith( '/next', $gitOps->getRepoPath() );
    }

    public function test_prepare_sync_configuration_handles_missing_repo_url() {
        $gitOps = $this->createGitOps();
        $GLOBALS['gitsync_options']['gitsync_repo_url'] = '';
        $GLOBALS['gitsync_options']['gitsync_branch'] = 'main';

        $result = $this->invokePrivate( $gitOps, 'prepareSyncConfiguration' );
        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'no_repo_url', $result->get_error_code() );
    }

    public function test_prepare_sync_configuration_requires_git() {
        $gitOps = $this->createGitOps( null, false );
        $GLOBALS['gitsync_options']['gitsync_repo_url'] = 'https://example.com/repo.git';
        $GLOBALS['gitsync_options']['gitsync_branch'] = 'develop';

        $result = $this->invokePrivate( $gitOps, 'prepareSyncConfiguration' );
        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'git_not_available', $result->get_error_code() );
    }

    public function test_prepare_sync_configuration_returns_expected_array_when_valid() {
        $gitOps = $this->createGitOps( null, true );
        $GLOBALS['gitsync_options']['gitsync_repo_url'] = 'https://example.com/repo.git';
        $GLOBALS['gitsync_options']['gitsync_branch'] = 'feature/x';

        $result = $this->invokePrivate( $gitOps, 'prepareSyncConfiguration' );
        $this->assertIsArray( $result );
        $this->assertSame( 'https://example.com/repo.git', $result['repo_url'] );
        $this->assertSame( 'feature/x', $result['branch'] );
    }

    public function test_validate_branch_supports_expected_characters() {
        $gitOps = $this->createGitOps();
        $isValid = $this->invokePrivate( $gitOps, 'validateBranch', 'feature/new_branch-1' );
        $this->assertTrue( $isValid );

        $isInvalid = $this->invokePrivate( $gitOps, 'validateBranch', 'invalid branch name' );
        $this->assertFalse( $isInvalid );
    }

    public function test_validate_branch_input_returns_error_for_invalid_branch() {
        $gitOps = $this->createGitOps();
        $result = $this->invokePrivate( $gitOps, 'validateBranchInput', 'bad branch' );
        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'invalid_branch', $result->get_error_code() );
    }

    public function test_validate_repo_url_supports_https_git_and_scp_formats() {
        $gitOps = $this->createGitOps();
        $this->assertTrue( $this->invokePrivate( $gitOps, 'validateRepoUrl', 'https://example.com/repo.git' ) );
        $this->assertTrue( $this->invokePrivate( $gitOps, 'validateRepoUrl', 'git@github.com:org/repo.git' ) );
        $this->assertFalse( $this->invokePrivate( $gitOps, 'validateRepoUrl', 'ftp://example.com/repo.git' ) );
    }

    public function test_validate_clone_inputs_returns_error_for_invalid_url() {
        $gitOps = $this->createGitOps();
        $result = $this->invokePrivate( $gitOps, 'validateCloneInputs', 'notaurl', 'main' );
        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'invalid_repo_url', $result->get_error_code() );

        $success = $this->invokePrivate( $gitOps, 'validateCloneInputs', 'https://example.com/repo.git', 'main' );
        $this->assertNull( $success );
    }

    public function test_add_credentials_to_url_encodes_username_and_token() {
        $gitOps = $this->createGitOps();
        $result = $this->invokePrivate(
            $gitOps,
            'addCredentialsToUrl',
            'https://github.com/org/repo.git',
            'user+name',
            'tok#123'
        );

        $this->assertSame( 'https://user%2Bname:tok%23123@github.com/org/repo.git', $result );
    }

    public function test_mask_token_in_message_replaces_secret_value() {
        $gitOps = $this->createGitOps();
        $result = $this->invokePrivate( $gitOps, 'maskTokenInMessage', 'token: SECRET', 'SECRET' );
        $this->assertSame( 'token: ****', $result );
    }

    private function invokePrivate( GitSyncGitOperations $instance, $method, ...$args ) {
        $ref = new ReflectionMethod( GitSyncGitOperations::class, $method );
        $ref->setAccessible( true );
        return $ref->invokeArgs( $instance, $args );
    }

    private function createGitOps( $repoPath = null, $gitAvailable = true ) {
        return new class( $repoPath, $gitAvailable ) extends GitSyncGitOperations {
            private $gitAvailableOverride;

            public function __construct( $repoPath = null, $gitAvailable = true ) {
                $this->gitAvailableOverride = $gitAvailable;
                parent::__construct( $repoPath );
            }

            public function isGitAvailable() {
                return $this->gitAvailableOverride;
            }
        };
    }
}
