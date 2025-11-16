#!/usr/bin/env php
<?php
/**
 * Basic validation script for GitSync plugin
 * Run this to check if the plugin structure is valid
 */

echo "GitSync Plugin Validation\n";
echo "==========================\n\n";

$errors = 0;
$warnings = 0;
$checks = 0;

// Check if we're in the right directory
if ( ! file_exists( 'git-sync.php' ) ) {
    echo "❌ Error: Must be run from plugin root directory\n";
    exit( 1 );
}

// Function to check file exists
function check_file( $path, $required = true ) {
    global $errors, $warnings, $checks;
    $checks++;
    
    if ( file_exists( $path ) ) {
        echo "✅ Found: $path\n";
        return true;
    } else {
        if ( $required ) {
            echo "❌ Missing required file: $path\n";
            $errors++;
        } else {
            echo "⚠️  Missing optional file: $path\n";
            $warnings++;
        }
        return false;
    }
}

// Function to check PHP syntax
function check_php_syntax( $path ) {
    global $errors, $checks;
    $checks++;
    
    $output = array();
    $return_var = 0;
    exec( "php -l " . escapeshellarg( $path ) . " 2>&1", $output, $return_var );
    
    if ( $return_var === 0 ) {
        echo "✅ Valid PHP syntax: $path\n";
        return true;
    } else {
        echo "❌ PHP syntax error in: $path\n";
        echo "   " . implode( "\n   ", $output ) . "\n";
        $errors++;
        return false;
    }
}

// Function to check class exists in file
function check_class_exists( $path, $class_name ) {
    global $errors, $warnings, $checks;
    $checks++;
    
    if ( ! file_exists( $path ) ) {
        return false;
    }
    
    $content = file_get_contents( $path );
    if ( strpos( $content, "class $class_name" ) !== false ) {
        echo "✅ Class $class_name found in $path\n";
        return true;
    } else {
        echo "⚠️  Class $class_name not found in $path\n";
        $warnings++;
        return false;
    }
}

echo "Checking core files...\n";
echo "----------------------\n";
check_file( 'git-sync.php', true );
check_file( 'README.md', true );
check_file( 'INSTALLATION.md', false );
check_file( 'CONTRIBUTING.md', false );
check_file( '.gitignore', false );
echo "\n";

echo "Checking directory structure...\n";
echo "--------------------------------\n";
check_file( 'includes', true );
check_file( 'assets', true );
check_file( 'assets/css', true );
check_file( 'assets/js', true );
check_file( 'examples', false );
echo "\n";

echo "Checking include files...\n";
echo "-------------------------\n";
check_file( 'includes/class-git-operations.php', true );
check_file( 'includes/class-markdown-parser.php', true );
check_file( 'includes/class-content-sync.php', true );
check_file( 'includes/class-admin-settings.php', true );
check_file( 'includes/class-sync-scheduler.php', true );
echo "\n";

echo "Checking asset files...\n";
echo "-----------------------\n";
check_file( 'assets/css/admin.css', true );
check_file( 'assets/js/admin.js', true );
echo "\n";

echo "Checking PHP syntax...\n";
echo "----------------------\n";
check_php_syntax( 'git-sync.php' );
check_php_syntax( 'includes/class-git-operations.php' );
check_php_syntax( 'includes/class-markdown-parser.php' );
check_php_syntax( 'includes/class-content-sync.php' );
check_php_syntax( 'includes/class-admin-settings.php' );
check_php_syntax( 'includes/class-sync-scheduler.php' );
echo "\n";

echo "Checking classes...\n";
echo "-------------------\n";
check_class_exists( 'git-sync.php', 'GitSync' );
check_class_exists( 'includes/class-git-operations.php', 'GitSync_Git_Operations' );
check_class_exists( 'includes/class-markdown-parser.php', 'GitSync_Markdown_Parser' );
check_class_exists( 'includes/class-content-sync.php', 'GitSync_Content_Sync' );
check_class_exists( 'includes/class-admin-settings.php', 'GitSync_Admin_Settings' );
check_class_exists( 'includes/class-sync-scheduler.php', 'GitSync_Sync_Scheduler' );
echo "\n";

echo "Checking example files...\n";
echo "-------------------------\n";
check_file( 'examples/example-post.md', false );
check_file( 'examples/example-page.md', false );
check_file( 'examples/example-product.md', false );
echo "\n";

// Summary
echo "Validation Summary\n";
echo "==================\n";
echo "Total checks: $checks\n";
echo "Errors: $errors\n";
echo "Warnings: $warnings\n";
echo "\n";

if ( $errors > 0 ) {
    echo "❌ Validation FAILED with $errors error(s)\n";
    exit( 1 );
} else if ( $warnings > 0 ) {
    echo "⚠️  Validation PASSED with $warnings warning(s)\n";
    exit( 0 );
} else {
    echo "✅ Validation PASSED - All checks successful!\n";
    exit( 0 );
}
