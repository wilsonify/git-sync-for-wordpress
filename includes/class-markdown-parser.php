<?php
/**
 * Markdown Parser Class
 * Parses Markdown files and extracts content and metadata
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GitSync_Markdown_Parser {
    
    /**
     * Parse markdown file
     */
    public function parse_file( $file_path ) {
        if ( ! file_exists( $file_path ) ) {
            return new WP_Error( 'file_not_found', __( 'Markdown file not found.', 'gitsync' ) );
        }
        
        $content = file_get_contents( $file_path );
        if ( $content === false ) {
            return new WP_Error( 'read_error', __( 'Failed to read markdown file.', 'gitsync' ) );
        }
        
        return $this->parse_content( $content, $file_path );
    }
    
    /**
     * Parse markdown content
     */
    public function parse_content( $content, $file_path = '' ) {
        $data = array(
            'metadata' => array(),
            'content' => '',
            'file_path' => $file_path,
        );
        
        // Check for front matter (YAML between --- markers)
        if ( preg_match( '/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches ) ) {
            $data['metadata'] = $this->parse_yaml_frontmatter( $matches[1] );
            $data['content'] = trim( $matches[2] );
        } else {
            // No front matter, entire content is markdown
            $data['content'] = trim( $content );
            
            // Try to extract title from first heading
            if ( preg_match( '/^#\s+(.+)$/m', $content, $title_match ) ) {
                $data['metadata']['title'] = trim( $title_match[1] );
            }
        }
        
        // Convert markdown to HTML
        $data['html_content'] = $this->markdown_to_html( $data['content'] );
        
        // Determine content type from metadata or file path
        $data['content_type'] = $this->determine_content_type( $data['metadata'], $file_path );
        
        return $data;
    }
    
    /**
     * Parse YAML frontmatter
     */
    private function parse_yaml_frontmatter( $yaml ) {
        $metadata = array();
        $lines = explode( "\n", $yaml );
        
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( empty( $line ) || $line[0] === '#' ) {
                continue;
            }
            
            // Simple key: value parsing
            if ( strpos( $line, ':' ) !== false ) {
                list( $key, $value ) = explode( ':', $line, 2 );
                $key = trim( $key );
                $value = trim( $value );
                
                // Remove quotes if present
                $value = trim( $value, '\'"' );
                
                // Handle arrays (simple comma-separated values)
                if ( strpos( $value, ',' ) !== false ) {
                    $value = array_map( 'trim', explode( ',', $value ) );
                }
                
                $metadata[ $key ] = $value;
            }
        }
        
        return $metadata;
    }
    
    /**
     * Convert markdown to HTML
     */
    private function markdown_to_html( $markdown ) {
        // Basic markdown to HTML conversion
        // For production, consider using a library like Parsedown
        
        $html = $markdown;
        
        // Headers
        $html = preg_replace( '/^######\s+(.+)$/m', '<h6>$1</h6>', $html );
        $html = preg_replace( '/^#####\s+(.+)$/m', '<h5>$1</h5>', $html );
        $html = preg_replace( '/^####\s+(.+)$/m', '<h4>$1</h4>', $html );
        $html = preg_replace( '/^###\s+(.+)$/m', '<h3>$1</h3>', $html );
        $html = preg_replace( '/^##\s+(.+)$/m', '<h2>$1</h2>', $html );
        $html = preg_replace( '/^#\s+(.+)$/m', '<h1>$1</h1>', $html );
        
        // Bold
        $html = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html );
        $html = preg_replace( '/__(.+?)__/', '<strong>$1</strong>', $html );
        
        // Italic
        $html = preg_replace( '/\*(.+?)\*/', '<em>$1</em>', $html );
        $html = preg_replace( '/_(.+?)_/', '<em>$1</em>', $html );
        
        // Links
        $html = preg_replace( '/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $html );
        
        // Images
        $html = preg_replace( '/!\[(.+?)\]\((.+?)\)/', '<img src="$2" alt="$1" />', $html );
        
        // Code blocks
        $html = preg_replace( '/```(.+?)```/s', '<pre><code>$1</code></pre>', $html );
        $html = preg_replace( '/`(.+?)`/', '<code>$1</code>', $html );
        
        // Lists - Unordered
        $html = preg_replace_callback( '/^(\*|\-)\s+(.+)$/m', function( $matches ) {
            static $in_list = false;
            $item = '<li>' . $matches[2] . '</li>';
            if ( ! $in_list ) {
                $in_list = true;
                return '<ul>' . $item;
            }
            return $item;
        }, $html );
        
        // Close any open lists
        if ( strpos( $html, '<ul>' ) !== false && substr_count( $html, '<ul>' ) > substr_count( $html, '</ul>' ) ) {
            $html .= '</ul>';
        }
        
        // Paragraphs - split by double newlines
        $paragraphs = preg_split( '/\n\n+/', $html );
        $processed = array();
        foreach ( $paragraphs as $para ) {
            $para = trim( $para );
            if ( empty( $para ) ) {
                continue;
            }
            // Don't wrap if already has block-level HTML
            if ( preg_match( '/^<(h[1-6]|ul|ol|pre|blockquote|div)/', $para ) ) {
                $processed[] = $para;
            } else {
                $processed[] = '<p>' . $para . '</p>';
            }
        }
        $html = implode( "\n", $processed );
        
        return $html;
    }
    
    /**
     * Determine content type from metadata or file path
     */
    private function determine_content_type( $metadata, $file_path ) {
        // Check metadata for explicit type
        if ( isset( $metadata['type'] ) ) {
            $type = strtolower( $metadata['type'] );
            if ( in_array( $type, array( 'post', 'page', 'product' ) ) ) {
                return $type;
            }
        }
        
        // Check file path for type indicators
        $path_lower = strtolower( $file_path );
        if ( strpos( $path_lower, '/posts/' ) !== false || strpos( $path_lower, '/blog/' ) !== false ) {
            return 'post';
        } elseif ( strpos( $path_lower, '/products/' ) !== false ) {
            return 'product';
        } elseif ( strpos( $path_lower, '/pages/' ) !== false ) {
            return 'page';
        }
        
        // Default to post
        return 'post';
    }
    
    /**
     * Extract slug from file path or metadata
     */
    public function extract_slug( $data ) {
        // Check metadata first
        if ( isset( $data['metadata']['slug'] ) ) {
            return sanitize_title( $data['metadata']['slug'] );
        }
        
        // Extract from file path
        $file_path = $data['file_path'];
        $basename = basename( $file_path, '.md' );
        $basename = basename( $basename, '.markdown' );
        
        return sanitize_title( $basename );
    }
}
