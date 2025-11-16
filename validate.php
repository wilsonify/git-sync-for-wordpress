#!/usr/bin/env php
<?php
/**
 * Basic validation script for GitSync plugin
 * Run this to check if the plugin structure is valid
 */

// @codeCoverageIgnoreStart

echo "GitSync Plugin Validation\n";
echo "==========================\n\n";

$errors = 0;
$warnings = 0;
$checks = 0;

const GITSYNC_INCLUDE_CLASS_MAP = array(
    'git_operations' => array(
        'path' => 'includes/class-git-operations.php',
        'class' => 'GitSyncGitOperations',
    ),
    'markdown_parser' => array(
        'path' => 'includes/class-markdown-parser.php',
        'class' => 'GitSyncMarkdownParser',
    ),
    'content_sync' => array(
        'path' => 'includes/class-content-sync.php',
        'class' => 'GitSyncContentSync',
    ),
    'admin_settings' => array(
        'path' => 'includes/class-admin-settings.php',
        'class' => 'GitSyncAdminSettings',
    ),
    'sync_scheduler' => array(
        'path' => 'includes/class-sync-scheduler.php',
        'class' => 'GitSyncSyncScheduler',
    ),
);

// Check if we're in the right directory
if ( ! file_exists( 'git-sync.php' ) ) {
    echo "❌ Error: Must be run from plugin root directory\n";
    exit( 1 );
}

// Function to check file exists
function checkFile( $path, $required = true ) {
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
function checkPhpSyntax( $path ) {
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
function checkClassExists( $path, $class_name ) {
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
checkFile( 'git-sync.php', true );
checkFile( 'README.md', true );
checkFile( 'INSTALLATION.md', false );
checkFile( 'CONTRIBUTING.md', false );
checkFile( '.gitignore', false );
echo "\n";

echo "Checking directory structure...\n";
echo "--------------------------------\n";
checkFile( 'includes', true );
checkFile( 'assets', true );
checkFile( 'assets/css', true );
checkFile( 'assets/js', true );
checkFile( 'examples', false );
echo "\n";

echo "Checking include files...\n";
echo "-------------------------\n";
foreach ( GITSYNC_INCLUDE_CLASS_MAP as $include ) {
    checkFile( $include['path'], true );
}
echo "\n";

echo "Checking asset files...\n";
echo "-----------------------\n";
checkFile( 'assets/css/admin.css', true );
checkFile( 'assets/js/admin.js', true );
echo "\n";

echo "Checking PHP syntax...\n";
echo "----------------------\n";
checkPhpSyntax( 'git-sync.php' );
foreach ( GITSYNC_INCLUDE_CLASS_MAP as $include ) {
    checkPhpSyntax( $include['path'] );
}
echo "\n";

echo "Checking classes...\n";
echo "-------------------\n";
checkClassExists( 'git-sync.php', 'GitSync' );
foreach ( GITSYNC_INCLUDE_CLASS_MAP as $include ) {
    checkClassExists( $include['path'], $include['class'] );
}
echo "\n";

echo "Checking example files...\n";
echo "-------------------------\n";
checkFile( 'examples/example-post.md', false );
checkFile( 'examples/example-page.md', false );
checkFile( 'examples/example-product.md', false );
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
} elseif ( $warnings > 0 ) {
    echo "⚠️  Validation PASSED with $warnings warning(s)\n";
    exit( 0 );
} else {
    echo "✅ Validation PASSED - All checks successful!\n";
    exit( 0 );
}

// @codeCoverageIgnoreEnd
