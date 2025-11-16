<?php
/**
 * Content Sync Class
 * Handles syncing content between Git and WordPress
 */

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
        
        // Get title from metadata or content
        $title = isset( $data['metadata']['title'] ) ? $data['metadata']['title'] : '';
        if ( empty( $title ) && preg_match( '/<h1>(.+?)<\/h1>/', $data['html_content'], $match ) ) {
            $title = wp_strip_all_tags( $match[1] );
        }
        if ( empty( $title ) ) {
            $title = ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );
        }
        
        // Check if content already exists
    $existing = self::findExistingContent( $slug, $content_type );
        
        // Prepare post data
        $post_data = array(
            'post_title' => $title,
            'post_content' => $data['html_content'],
            'post_name' => $slug,
            'post_status' => isset( $data['metadata']['status'] ) ? $data['metadata']['status'] : 'publish',
            'post_type' => $content_type === 'product' ? 'product' : $content_type,
        );
        
        // Add date if provided
        if ( isset( $data['metadata']['date'] ) ) {
            $post_data['post_date'] = $data['metadata']['date'];
        }
        
        // Add author if provided
        if ( isset( $data['metadata']['author'] ) ) {
            $author = get_user_by( 'login', $data['metadata']['author'] );
            if ( $author ) {
                $post_data['post_author'] = $author->ID;
            }
        }
        
        if ( $existing ) {
            // Update existing content
            $post_data['ID'] = $existing->ID;
            
            // Only update if content has changed
            if ( $existing->post_content !== $post_data['post_content'] ||
                 $existing->post_title !== $post_data['post_title'] ) {
                $post_id = wp_update_post( $post_data, true );
                if ( is_wp_error( $post_id ) ) {
                    return $post_id;
                }
                self::updatePostMeta( $post_id, $data, $content_type );
                return 'updated';
            }
            return 'skipped';
        } else {
            // Create new content
            $post_id = wp_insert_post( $post_data, true );
            if ( is_wp_error( $post_id ) ) {
                return $post_id;
            }
            self::updatePostMeta( $post_id, $data, $content_type );
            return 'created';
        }
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
        // Store original file path
        update_post_meta( $post_id, '_gitsync_file_path', $data['file_path'] );
        update_post_meta( $post_id, '_gitsync_synced', current_time( 'mysql' ) );
        
        // Handle categories
        if ( isset( $data['metadata']['categories'] ) ) {
            $categories = is_array( $data['metadata']['categories'] ) ? 
                $data['metadata']['categories'] : 
                array_map( 'trim', explode( ',', $data['metadata']['categories'] ) );
            
            if ( $content_type === 'post' ) {
                wp_set_post_categories( $post_id, $categories, false );
            }
        }
        
        // Handle tags
        if ( isset( $data['metadata']['tags'] ) ) {
            $tags = is_array( $data['metadata']['tags'] ) ? 
                $data['metadata']['tags'] : 
                array_map( 'trim', explode( ',', $data['metadata']['tags'] ) );
            
            if ( $content_type === 'post' ) {
                wp_set_post_tags( $post_id, $tags, false );
            }
        }
        
        // Handle WooCommerce product specific metadata
        if ( $content_type === 'product' && class_exists( 'WC_Product' ) ) {
            $product = wc_get_product( $post_id );
            if ( $product ) {
                // Set price
                if ( isset( $data['metadata']['price'] ) ) {
                    $product->set_regular_price( $data['metadata']['price'] );
                }
                if ( isset( $data['metadata']['sale_price'] ) ) {
                    $product->set_sale_price( $data['metadata']['sale_price'] );
                }
                
                // Set SKU
                if ( isset( $data['metadata']['sku'] ) ) {
                    $product->set_sku( $data['metadata']['sku'] );
                }
                
                // Set stock
                if ( isset( $data['metadata']['stock'] ) ) {
                    $product->set_stock_quantity( (int) $data['metadata']['stock'] );
                    $product->set_manage_stock( true );
                }
                
                $product->save();
            }
        }
        
        // Handle custom fields
        if ( isset( $data['metadata']['custom_fields'] ) && is_array( $data['metadata']['custom_fields'] ) ) {
            foreach ( $data['metadata']['custom_fields'] as $key => $value ) {
                update_post_meta( $post_id, $key, $value );
            }
        }
    }
    
    /**
     * Log error message
     */
    private static function logError( $message ) {
        error_log( '[GitSync Error] ' . $message );
    }
}
