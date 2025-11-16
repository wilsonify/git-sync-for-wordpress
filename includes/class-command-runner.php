<?php
/**
 * Lightweight command runner that delegates to Symfony's Process component.
 */

use Symfony\Component\Process\Process;

if ( ! defined( 'ABSPATH' ) && ! defined( 'GITSYNC_CLI' ) ) {
    exit;
}

class GitSync_Command_Runner {

    /**
     * Execute a command safely without invoking the system shell.
     *
     * @param array       $command Array of command parts, e.g. ['git','status'].
     * @param string|null $working_directory Optional working directory.
     * @param int         $timeout Seconds before the process is aborted.
     *
     * @return array{
     *     success: bool,
     *     exit_code: int|null,
     *     output: string[],
     * }
     */
    public static function run( array $command, $working_directory = null, $timeout = 120 ) {
        $process = new Process( $command, $working_directory ?: null, null, null, $timeout );
        $process->run();

        return array(
            'success'   => $process->isSuccessful(),
            'exit_code' => $process->getExitCode(),
            'output'    => self::normalize_output( $process->getOutput(), $process->getErrorOutput() ),
        );
    }

    /**
     * Convert stdout/stderr into a trimmed array of lines.
     */
    private static function normalize_output( $stdout, $stderr ) {
        $combined = trim( (string) $stdout . ( $stderr ? "\n" . $stderr : '' ) );

        if ( '' === $combined ) {
            return array();
        }

        return preg_split( '/\r\n|\n|\r/', $combined );
    }
}
