<?php
// Basic bootstrap for tests — provide minimal WordPress stubs required by classes

// Define ABSPATH to satisfy plugin files that check it
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/autoload.php';

$GLOBALS['gitsync_options'] = array();

// Minimal WP functions used by the plugin classes (sufficient for unit tests)
function wp_upload_dir() {
    return array( 'basedir' => sys_get_temp_dir() );
}

function wp_mkdir_p( $dir ) {
    if ( ! file_exists( $dir ) ) {
        mkdir( $dir, 0777, true );
    }
    return true;
}

function get_option( $name, $default = false ) {
    return array_key_exists( $name, $GLOBALS['gitsync_options'] ) ? $GLOBALS['gitsync_options'][ $name ] : $default;
}

function update_option( $name, $value ) {
    $GLOBALS['gitsync_options'][ $name ] = $value;
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
function __ ( $text, $domain = null ) {
    if ( null !== $domain ) {
        // domain parameter kept for signature parity with WordPress
    }
    return $text;
}

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

    public function get_error_code() {
        return $this->code;
    }

    public function get_error_message() {
        return $this->message;
    }

    public function get_error_data() {
        return $this->data;
    }
}
