# GitSync for WordPress

A WordPress plugin that keeps your WordPress content (pages, posts, and WooCommerce products) in sync with a remote Git repository containing Markdown files.

## Features

- **Automatic Content Sync**: Pull content from a Git repository and automatically create/update WordPress posts, pages, and WooCommerce products
- **Markdown Support**: Write your content in Markdown format with YAML frontmatter for metadata
- **Git Integration**: Direct integration with Git repositories (GitHub, GitLab, Bitbucket, etc.)
- **Multiple Content Types**: Support for WordPress Posts, Pages, and WooCommerce Products
- **Scheduled Sync**: Optional automatic hourly syncing
- **Manual Sync**: Trigger syncs manually from the admin panel
- **Conflict Resolution**: Smart detection of existing content to prevent duplicates

## Installation

1. Upload the `git-sync` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your Git repository settings in the GitSync admin menu

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Git installed on the server
- Server write access to the WordPress uploads directory

## Configuration

### Repository Settings

1. Navigate to **GitSync** in the WordPress admin menu
2. Enter your Git repository URL (HTTPS format)
3. Specify the branch to sync (default: `main`)
4. For private repositories, provide your username and access token
5. Click **Save Settings**

### Sync Settings

- **Auto Sync**: Enable to automatically sync content every hour
- **Manual Sync**: Click "Sync Now" to trigger an immediate sync

## Markdown File Format

Your Markdown files can include YAML frontmatter for metadata:

```markdown
---
title: My Post Title
type: post
status: publish
categories: Technology, WordPress
tags: git, sync, automation
author: username
date: 2025-01-15
---

# My Post Title

Your content here in **Markdown** format.

## Subheading

- List item 1
- List item 2

[Link text](https://example.com)
```

### Supported Metadata Fields

#### Common Fields (All Content Types)

- `title`: Post/Page/Product title
- `type`: Content type (`post`, `page`, or `product`)
- `status`: Publication status (`publish`, `draft`, `pending`, `private`)
- `slug`: URL slug (auto-generated from filename if not provided)
- `author`: WordPress username
- `date`: Publication date (YYYY-MM-DD format)

#### Posts and Pages

- `categories`: Comma-separated list of category names
- `tags`: Comma-separated list of tag names

#### WooCommerce Products

- `price`: Regular price
- `sale_price`: Sale price
- `sku`: Product SKU
- `stock`: Stock quantity

## Directory Structure

Organize your Markdown files in your Git repository:

```
repository/
├── posts/
│   ├── my-first-post.md
│   └── another-post.md
├── pages/
│   ├── about.md
│   └── contact.md
└── products/
    ├── product-1.md
    └── product-2.md
```

The plugin will automatically determine content type based on the directory structure or the `type` field in frontmatter.

## How It Works

1. **Repository Sync**: The plugin clones or pulls the latest content from your Git repository
2. **File Discovery**: Scans for Markdown files (`.md` or `.markdown` extensions)
3. **Content Parsing**: Parses YAML frontmatter and converts Markdown to HTML
4. **Content Sync**: Creates new or updates existing WordPress content based on the slug
5. **Metadata Updates**: Applies categories, tags, and other metadata

## Security Considerations

- Access tokens are stored in the WordPress database
- Use personal access tokens instead of passwords
- Consider using environment variables for credentials in production
- The plugin requires Git to be installed on the server

## Troubleshooting

### Git Not Found
- Ensure Git is installed on your server
- Check that PHP can execute shell commands
- Contact your hosting provider if Git is not available

### Permission Denied
- Verify server has write access to the uploads directory
- Check file permissions on the `wp-content/uploads/gitsync-repo` directory

### Authentication Failed
- Verify your access token is valid and has repository access
- For GitHub, create a Personal Access Token with `repo` scope
- For GitLab, create a Personal Access Token with `read_repository` scope

### No Content Synced
- Check that your repository contains Markdown files
- Verify the branch name is correct
- Review WordPress debug logs for errors

## Development

### File Structure

```
git-sync/
├── git-sync.php              # Main plugin file
├── includes/
│   ├── class-git-operations.php    # Git operations handler
│   ├── class-markdown-parser.php   # Markdown parser
│   ├── class-content-sync.php      # Content synchronization
│   ├── class-admin-settings.php    # Admin settings page
│   └── class-sync-scheduler.php    # Cron scheduler
├── assets/
│   ├── css/
│   │   └── admin.css         # Admin styles
│   └── js/
│       └── admin.js          # Admin JavaScript
└── README.md
```

## License

GPL-2.0+

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/wilsonify/git-sync-for-wordpress).

## Changelog

### 1.0.0
- Initial release
- Support for Posts, Pages, and WooCommerce Products
- Git repository integration
- Markdown to HTML conversion
- Manual and scheduled sync
- Admin settings interface
