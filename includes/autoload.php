<?php
/**
 * Lightweight PSR-4-ish autoloader for GitSync classes stored in kebab-case files.
 */

spl_autoload_register( static function ( $class ) {
    $prefix = 'GitSync\\';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }

    $relative = substr( $class, strlen( $prefix ) );
    $relative = preg_replace( '/^GitSync/', '', $relative );
    $file = 'class-' . strtolower( preg_replace( '/(?<!^)[A-Z]/', '-$0', $relative ) ) . '.php';
    $path = __DIR__ . '/' . $file;

    if ( file_exists( $path ) ) {
        require_once $path;
    }
} );
