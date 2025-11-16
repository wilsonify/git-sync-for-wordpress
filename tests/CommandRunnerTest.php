<?php

use GitSync\GitSyncCommandRunner;
use PHPUnit\Framework\TestCase;

class CommandRunnerTest extends TestCase {
    public function test_run_successful_command_returns_output_lines() {
        $result = GitSyncCommandRunner::run(
            array( PHP_BINARY, '-r', 'echo "Hello";' )
        );

        $this->assertTrue( $result['success'] );
        $this->assertSame( 0, $result['exit_code'] );
        $this->assertSame( array( 'Hello' ), $result['output'] );
    }

    public function test_run_failure_captures_exit_code_and_stderr() {
        $result = GitSyncCommandRunner::run(
            array( PHP_BINARY, '-r', 'fwrite(STDERR, "Boom" . PHP_EOL); exit(2);' )
        );

        $this->assertFalse( $result['success'] );
        $this->assertSame( 2, $result['exit_code'] );
        $this->assertNotEmpty( $result['output'] );
        $this->assertSame( 'Boom', $result['output'][0] );
    }

    public function test_run_handles_multi_line_output() {
        $result = GitSyncCommandRunner::run(
            array( PHP_BINARY, '-r', 'echo "line1" . PHP_EOL . "line2";' )
        );

        $this->assertTrue( $result['success'] );
        $this->assertSame( array( 'line1', 'line2' ), $result['output'] );
    }
}
