<?php

use GitSync\GitSyncGitOperations;
use PHPUnit\Framework\TestCase;

class GitOperationsTest extends TestCase {
    private $tempDir;

    protected function setUp(): void {
        $this->tempDir = sys_get_temp_dir() . '/gitsync_test_' . uniqid();
        if ( ! file_exists( $this->tempDir ) ) {
            mkdir( $this->tempDir, 0777, true );
        }
        // create .git dir to simulate initialized repo
        mkdir( $this->tempDir . '/.git' );

        // create some markdown files
        file_put_contents( $this->tempDir . '/post1.md', '# Post 1' );
        file_put_contents( $this->tempDir . '/readme.markdown', '# Readme' );
        file_put_contents( $this->tempDir . '/ignore.txt', 'ignore' );
    }

    protected function tearDown(): void {
        // remove files
        $it = new RecursiveDirectoryIterator( $this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS );
        $files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );
        foreach ( $files as $file ) {
            if ( $file->isDir() ) {
                rmdir( $file->getRealPath() );
            } else {
                unlink( $file->getRealPath() );
            }
        }
        if ( file_exists( $this->tempDir ) ) {
            rmdir( $this->tempDir );
        }
    }

    public function test_get_markdown_files_returns_only_md_and_markdown_files() {
    $gitOps = new GitSyncGitOperations( $this->tempDir );

    $files = $gitOps->getMarkdownFiles();
        $this->assertIsArray( $files );

        // Normalize paths for comparison
        $normalized = array_map( function( $p ) { return basename( $p ); }, $files );
        $this->assertContains( 'post1.md', $normalized );
        $this->assertContains( 'readme.markdown', $normalized );
        $this->assertNotContains( 'ignore.txt', $normalized );
    }
}
