<?php
// Basic bootstrap for tests — provide minimal WordPress stubs required by classes

// Define ABSPATH to satisfy plugin files that check it
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/class-command-runner.php';

// Minimal WP functions used by the plugin classes (sufficient for unit tests)
function wp_upload_dir() {
    return array( 'basedir' => sys_get_temp_dir() );
}

function sanitize_title( $title ) {
    $title = strtolower( $title );
    $title = preg_replace( '/[^a-z0-9\-]/', '-', $title );
    $title = preg_replace( '/-+/', '-', $title );
    $title = trim( $title, '-' );
    return $title;
}

function sanitize_text_field( $value ) { return trim( (string) $value ); }
function esc_url_raw( $url ) { return $url; }
function __ ( $text, $domain = null ) { return $text; }

// Minimal WP_Error implementation for tests
class WP_Error {
    private $code;
    private $message;
    private $data;

    public function __construct( $code = '', $message = '', $data = null ) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    public function get_error_message() {
        return $this->message;
    }

    public function get_error_data() {
        return $this->data;
    }
}

// Require plugin source files we will test
require_once __DIR__ . '/../includes/class-markdown-parser.php';
require_once __DIR__ . '/../includes/class-git-operations.php';
