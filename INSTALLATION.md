# GitSync Installation Guide

## Quick Start

1. **Download the Plugin**
   - Clone or download this repository
   - Upload to your WordPress `wp-content/plugins/` directory

2. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "GitSync" and click "Activate"

3. **Configure Settings**
   - Go to WordPress Admin → GitSync
   - Enter your Git repository URL
   - Add credentials if using a private repository
   - Click "Save Settings"

4. **Test the Sync**
   - Click "Sync Now" to test the connection
   - Check the sync status for success/error messages

## Detailed Setup

### Server Requirements

Before installing GitSync, ensure your server meets these requirements:

- **WordPress**: Version 5.0 or higher
- **PHP**: Version 7.2 or higher
- **Git**: Installed and accessible via command line
- **File Permissions**: Write access to `wp-content/uploads/`

#### Checking Git Availability

SSH into your server and run:
```bash
git --version
```

If Git is not installed, contact your hosting provider.

### Repository Setup

#### 1. Create a Content Repository

Create a new Git repository for your content:

```
my-content/
├── posts/
│   ├── welcome-post.md
│   └── getting-started.md
├── pages/
│   ├── about.md
│   └── contact.md
└── products/
    └── sample-product.md
```

#### 2. Add Markdown Files

Create Markdown files with YAML frontmatter:

```markdown
---
title: My First Post
type: post
status: publish
categories: News, Updates
tags: welcome, introduction
---

# My First Post

Welcome to my blog! This content is managed with Git.
```

#### 3. Push to Remote Repository

```bash
git init
git add .
git commit -m "Initial content"
git remote add origin https://github.com/yourusername/your-content-repo.git
git push -u origin main
```

### WordPress Configuration

#### 1. Repository URL

Enter the HTTPS URL of your Git repository:
- GitHub: `https://github.com/username/repo.git`
- GitLab: `https://gitlab.com/username/repo.git`
- Bitbucket: `https://bitbucket.org/username/repo.git`

#### 2. Branch

Specify which branch to sync (default: `main`). Common options:
- `main` or `master` for production content
- `staging` for preview content
- `develop` for draft content

#### 3. Credentials (Private Repositories)

For private repositories, you need authentication:

**GitHub:**
1. Go to GitHub Settings → Developer Settings → Personal Access Tokens
2. Generate a new token with `repo` scope
3. Use your GitHub username and the token as password

**GitLab:**
1. Go to User Settings → Access Tokens
2. Create a token with `read_repository` scope
3. Use your GitLab username and the token as password

**Bitbucket:**
1. Go to Personal Settings → App Passwords
2. Create an app password with repository read permissions
3. Use your Bitbucket username and the app password

#### 4. Auto Sync

Enable "Auto Sync" to automatically pull content every hour. Disable for manual-only sync.

## Content Management Workflow

### Basic Workflow

1. **Edit Content**: Edit Markdown files in your Git repository
2. **Commit Changes**: Commit and push to your Git repository
3. **Sync**: Trigger manual sync or wait for scheduled sync
4. **Verify**: Check WordPress for updated content

### Example: Adding a New Post

```bash
# 1. Create new Markdown file
cat > posts/new-feature.md << EOF
---
title: Announcing New Feature
type: post
status: publish
categories: News
tags: feature, announcement
date: 2025-01-15
---

# Announcing New Feature

We're excited to announce our new feature...
EOF

# 2. Commit and push
git add posts/new-feature.md
git commit -m "Add new feature announcement"
git push origin main

# 3. Sync in WordPress (or wait for auto-sync)
```

### Content Updates

When you update a Markdown file and sync, GitSync will:
- Find the existing WordPress post by slug
- Update the title if changed
- Update the content if changed
- Skip if no changes detected

### Content Organization

**Directory-Based Type Detection:**
```
repository/
├── posts/        # Automatically treated as posts
├── pages/        # Automatically treated as pages
└── products/     # Automatically treated as products
```

**Metadata-Based Type Detection:**
```markdown
---
type: page  # Explicit type override
---
```

## Troubleshooting

### Sync Fails with "Git Not Available"

**Solution:**
- Verify Git is installed: `git --version`
- Check PHP can execute commands: Test with `exec('git --version')`
- Contact hosting provider if Git is unavailable

### Authentication Error

**Solution:**
- Verify repository URL is correct (HTTPS format)
- Check username is correct
- Verify access token has repository permissions
- For GitHub, token needs `repo` scope
- For private repos, credentials are required

### Content Not Appearing

**Solution:**
- Check sync status for errors
- Verify Markdown files have proper frontmatter
- Check WordPress user permissions
- Review error logs: `wp-content/debug.log` (if WP_DEBUG enabled)

### Permission Denied

**Solution:**
- Check directory permissions: `wp-content/uploads/gitsync-repo/`
- Ensure web server can write to uploads directory
- Set permissions: `chmod 755 wp-content/uploads/gitsync-repo/`

## Advanced Configuration

### Custom Content Types

To support custom post types, modify the content type detection in `class-markdown-parser.php`:

```php
private function determine_content_type( $metadata, $file_path ) {
    if ( isset( $metadata['type'] ) ) {
        return $metadata['type']; // Supports any post type
    }
    // Add custom directory detection
    if ( strpos( $file_path, '/my-custom-type/' ) !== false ) {
        return 'my_custom_type';
    }
    return 'post';
}
```

### Custom Metadata Fields

Add custom field handling in `class-content-sync.php`:

```php
private static function update_post_meta( $post_id, $data, $content_type ) {
    // ... existing code ...
    
    // Add custom field
    if ( isset( $data['metadata']['my_custom_field'] ) ) {
        update_post_meta( $post_id, 'my_custom_field', 
            $data['metadata']['my_custom_field'] );
    }
}
```

### Webhook Integration

For instant sync on Git push, set up a webhook:

1. Add webhook endpoint to plugin
2. Configure in Git provider (GitHub, GitLab, etc.)
3. Trigger sync on push event

## Security Best Practices

1. **Use Access Tokens**: Never use passwords directly
2. **Minimum Permissions**: Grant only required repository access
3. **HTTPS Only**: Always use HTTPS repository URLs
4. **Rotate Tokens**: Periodically update access tokens
5. **Environment Variables**: Consider using environment variables for credentials

## Performance Optimization

### For Large Repositories

- Use shallow clones to reduce initial sync time
- Sync specific directories only
- Implement caching for parsed content
- Schedule syncs during low-traffic periods

### Sync Frequency

- Hourly sync for active content
- Daily sync for stable content
- Manual sync for development/testing

## Support and Contributing

- **Issues**: Report bugs on GitHub
- **Documentation**: Contribute to README
- **Code**: Submit pull requests

## License

GPL-2.0+
