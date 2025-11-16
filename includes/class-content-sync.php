<?php
/**
 * Content Sync Class
 * Handles syncing content between Git and WordPress
 */

namespace GitSync;

use function __;
use function check_ajax_referer;
use function class_exists;
use function current_time;
use function current_user_can;
use function get_posts;
use function get_user_by;
use function is_wp_error;
use function update_post_meta;
use function wc_get_product;
use function wp_insert_post;
use function wp_send_json_error;
use function wp_send_json_success;
use function wp_set_post_categories;
use function wp_set_post_tags;
use function wp_strip_all_tags;
use function wp_update_post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GitSyncContentSync {
    
    /**
     * Manual sync triggered from admin
     */
    public static function manualSync() {
        check_ajax_referer( 'gitsync_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'gitsync' ) ) );
        }
        
    $result = self::performSync();
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
                'data' => $result->get_error_data(),
            ) );
        }
        
        wp_send_json_success( array(
            'message' => __( 'Sync completed successfully!', 'gitsync' ),
            'data' => $result,
        ) );
    }
    
    /**
     * Scheduled sync
     */
    public static function scheduledSync() {
        if ( get_option( 'gitsync_auto_sync', false ) ) {
            self::performSync();
        }
    }
    
    /**
     * Perform the actual sync
     */
    private static function performSync() {
	$git_ops = new GitSyncGitOperations();
        $parser = new GitSyncMarkdownParser();
        
        // Sync repository
    $sync_result = $git_ops->syncRepository();
        if ( is_wp_error( $sync_result ) ) {
            return $sync_result;
        }
        
        // Get markdown files
    $files = $git_ops->getMarkdownFiles();
        
        $stats = array(
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        );
        
        foreach ( $files as $file ) {
            $stats['processed']++;
            
            // Parse file
            $parsed = $parser->parseFile( $file );
            if ( is_wp_error( $parsed ) ) {
                $stats['errors']++;
                self::logError( 'Failed to parse file: ' . $file . ' - ' . $parsed->get_error_message() );
                continue;
            }
            
            // Sync content based on type
            $result = self::syncContentItem( $parsed, $parser );
            if ( is_wp_error( $result ) ) {
                $stats['errors']++;
                self::logError( 'Failed to sync content: ' . $file . ' - ' . $result->get_error_message() );
            } elseif ( $result === 'created' ) {
                $stats['created']++;
            } elseif ( $result === 'updated' ) {
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Sync individual content item
     */
    private static function syncContentItem( $data, $parser ) {
    $content_type = $data['content_type'];
    $slug = $parser->extractSlug( $data );
    $title = self::resolveContentTitle( $data, $slug );
    $existing = self::findExistingContent( $slug, $content_type );
    $post_data = self::preparePostData( $data, $title, $slug, $content_type );

        if ( $existing ) {
            return self::updateExistingContentItem( $existing, $post_data, $data, $content_type );
        }

        return self::createNewContentItem( $post_data, $data, $content_type );
    }
    
    /**
     * Find existing content by slug
     */
    private static function findExistingContent( $slug, $content_type ) {
        $post_type = $content_type === 'product' ? 'product' : $content_type;
        
        $posts = get_posts( array(
            'name' => $slug,
            'post_type' => $post_type,
            'post_status' => 'any',
            'numberposts' => 1,
        ) );
        
        return ! empty( $posts ) ? $posts[0] : null;
    }
    
    /**
     * Update post meta from markdown metadata
     */
    private static function updatePostMeta( $post_id, $data, $content_type ) {
        self::storeSyncMetadata( $post_id, $data['file_path'] );
        self::applyPostTaxonomies( $post_id, $data, $content_type );
        self::applyProductMetadata( $post_id, $data, $content_type );
        self::applyCustomFields( $post_id, $data );
    }
    
    /**
     * Log error message
     */
    private static function logError( $message ) {
        error_log( '[GitSync Error] ' . $message );
    }

    private static function resolveContentTitle( $data, $slug ) {
        if ( ! empty( $data['metadata']['title'] ) ) {
            return $data['metadata']['title'];
        }

        if ( ! empty( $data['html_content'] ) && preg_match( '/<h1>(.+?)<\/h1>/', $data['html_content'], $match ) ) {
            return wp_strip_all_tags( $match[1] );
        }

        return ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );
    }

    private static function preparePostData( $data, $title, $slug, $content_type ) {
        $post_data = array(
            'post_title' => $title,
            'post_content' => $data['html_content'],
            'post_name' => $slug,
            'post_status' => isset( $data['metadata']['status'] ) ? $data['metadata']['status'] : 'publish',
            'post_type' => $content_type === 'product' ? 'product' : $content_type,
        );

        self::maybeAssignPostDate( $post_data, $data );
        self::maybeAssignAuthor( $post_data, $data );

        return $post_data;
    }

    private static function maybeAssignPostDate( &$post_data, $data ) {
        if ( isset( $data['metadata']['date'] ) ) {
            $post_data['post_date'] = $data['metadata']['date'];
        }
    }

    private static function maybeAssignAuthor( &$post_data, $data ) {
        if ( empty( $data['metadata']['author'] ) ) {
            return;
        }

        $author = get_user_by( 'login', $data['metadata']['author'] );
        if ( $author ) {
            $post_data['post_author'] = $author->ID;
        }
    }

    private static function updateExistingContentItem( $existing, $post_data, $data, $content_type ) {
        $post_data['ID'] = $existing->ID;

        if ( ! self::contentHasChanged( $existing, $post_data ) ) {
            return 'skipped';
        }

        $post_id = wp_update_post( $post_data, true );
        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        self::updatePostMeta( $post_id, $data, $content_type );
        return 'updated';
    }

    private static function createNewContentItem( $post_data, $data, $content_type ) {
        $post_id = wp_insert_post( $post_data, true );
        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        self::updatePostMeta( $post_id, $data, $content_type );
        return 'created';
    }

    private static function contentHasChanged( $existing, $post_data ) {
        return $existing->post_content !== $post_data['post_content'] ||
            $existing->post_title !== $post_data['post_title'];
    }

    private static function storeSyncMetadata( $post_id, $file_path ) {
        update_post_meta( $post_id, '_gitsync_file_path', $file_path );
        update_post_meta( $post_id, '_gitsync_synced', current_time( 'mysql' ) );
    }

    private static function applyPostTaxonomies( $post_id, $data, $content_type ) {
        if ( $content_type !== 'post' || empty( $data['metadata'] ) ) {
            return;
        }

        $metadata = $data['metadata'];
        if ( isset( $metadata['categories'] ) ) {
            $categories = self::normalizeListMetadata( $metadata['categories'] );
            wp_set_post_categories( $post_id, $categories, false );
        }

        if ( isset( $metadata['tags'] ) ) {
            $tags = self::normalizeListMetadata( $metadata['tags'] );
            wp_set_post_tags( $post_id, $tags, false );
        }
    }

    private static function applyProductMetadata( $post_id, $data, $content_type ) {
        if ( $content_type !== 'product' || ! class_exists( 'WC_Product' ) ) {
            return;
        }

        $product = wc_get_product( $post_id );
        if ( ! $product || empty( $data['metadata'] ) ) {
            return;
        }

        $metadata = $data['metadata'];

        if ( isset( $metadata['price'] ) ) {
            $product->set_regular_price( $metadata['price'] );
        }

        if ( isset( $metadata['sale_price'] ) ) {
            $product->set_sale_price( $metadata['sale_price'] );
        }

        if ( isset( $metadata['sku'] ) ) {
            $product->set_sku( $metadata['sku'] );
        }

        if ( isset( $metadata['stock'] ) ) {
            $product->set_stock_quantity( (int) $metadata['stock'] );
            $product->set_manage_stock( true );
        }

        $product->save();
    }

    private static function applyCustomFields( $post_id, $data ) {
        if ( empty( $data['metadata']['custom_fields'] ) || ! is_array( $data['metadata']['custom_fields'] ) ) {
            return;
        }

        foreach ( $data['metadata']['custom_fields'] as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }
    }

    private static function normalizeListMetadata( $value ) {
        if ( is_array( $value ) ) {
            return $value;
        }

        if ( is_string( $value ) ) {
            return array_map( 'trim', explode( ',', $value ) );
        }

        return array();
    }
}
