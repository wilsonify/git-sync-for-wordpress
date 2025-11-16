<?php

use GitSync\GitSyncMarkdownParser;
use PHPUnit\Framework\TestCase;


class MarkdownParserTest extends TestCase {
    /** @var GitSyncMarkdownParser */
    private $parser;

    protected function setUp(): void {
        $this->parser = new GitSyncMarkdownParser();
    }

    public function test_parse_content_without_frontmatter_extracts_title_and_converts_markdown() {
        $md = "# Hello World\n\nThis is **bold** text.";
        $result = $this->parser->parseContent( $md );

        $this->assertArrayHasKey( 'metadata', $result );
        $this->assertEquals( 'Hello World', $result['metadata']['title'] );
        $this->assertStringContainsString( '<h1>Hello World</h1>', $result['html_content'] );
        $this->assertStringContainsString( '<strong>bold</strong>', $result['html_content'] );
        $this->assertEquals( 'post', $result['content_type'] );
    }

    public function test_parse_content_with_frontmatter_parses_yaml_and_sets_type_and_slug() {
        $md = "---\ntitle: Sample Page\ntype: page\nslug: custom-slug\n---\n\n# Sample Page\n\nContent here.";
        $result = $this->parser->parseContent( $md, '/pages/sample.md' );

        $this->assertEquals( 'Sample Page', $result['metadata']['title'] );
        $this->assertEquals( 'page', $result['content_type'] );
        $this->assertEquals( 'custom-slug', $result['metadata']['slug'] );

        // Extract slug uses sanitize_title — simulate with metadata
        $slug = $this->parser->extractSlug( $result );
        $this->assertEquals( 'custom-slug', $slug );
    }

    public function test_extract_slug_from_file_path_when_no_metadata_slug() {
        $data = array( 'file_path' => '/var/tmp/my-awesome-post.md', 'metadata' => array() );
        $slug = $this->parser->extractSlug( $data );
        $this->assertEquals( 'my-awesome-post', $slug );
    }
}
