# GitSync Quick Reference

## Installation

```bash
# 1. Upload to WordPress plugins directory
cp -r git-sync /path/to/wordpress/wp-content/plugins/

# 2. Activate in WordPress Admin
# Go to Plugins → Activate "GitSync"
```

## Basic Configuration

1. **Admin Panel**: Navigate to `GitSync` in WordPress admin menu
2. **Repository URL**: Enter Git repository URL (HTTPS)
   - Example: `https://github.com/username/content-repo.git`
3. **Branch**: Specify branch (default: `main`)
4. **Credentials** (for private repos):
   - Username: Your Git username
   - Token: Personal access token
5. **Save Settings**
6. **Test**: Click "Sync Now"

## Markdown Format

### Basic Structure

```markdown
---
title: Post Title
type: post
status: publish
---

# Post Title

Your content here...
```

### Content Types

| Type | Value | Description |
|------|-------|-------------|
| Post | `post` | WordPress blog post |
| Page | `page` | WordPress page |
| Product | `product` | WooCommerce product |

### Metadata Fields

#### All Content Types
- `title`: Content title
- `type`: Content type (post/page/product)
- `status`: publish/draft/pending/private
- `slug`: URL slug
- `author`: WordPress username
- `date`: YYYY-MM-DD

#### Posts/Pages
- `categories`: Category1, Category2
- `tags`: tag1, tag2

#### Products
- `price`: 49.99
- `sale_price`: 39.99
- `sku`: PRODUCT-SKU
- `stock`: 100

## Directory Structure

Organize content by type:

```
repository/
├── posts/          # Auto-detected as posts
│   └── post1.md
├── pages/          # Auto-detected as pages
│   └── about.md
└── products/       # Auto-detected as products
    └── product1.md
```

## Common Tasks

### Add New Post

```bash
# Create file
cat > posts/new-post.md << EOF
---
title: My New Post
type: post
status: publish
---

# My New Post
Content here...
EOF

# Commit and push
git add posts/new-post.md
git commit -m "Add new post"
git push

# Sync in WordPress
# Click "Sync Now" or wait for auto-sync
```

### Update Existing Content

```bash
# Edit file
vim posts/existing-post.md

# Commit and push
git add posts/existing-post.md
git commit -m "Update post content"
git push

# Sync in WordPress
```

### Private Repository Setup

**GitHub:**
1. Settings → Developer settings → Personal access tokens
2. Generate token with `repo` scope
3. Use GitHub username + token in plugin settings

**GitLab:**
1. User Settings → Access Tokens
2. Create token with `read_repository` scope
3. Use GitLab username + token in plugin settings

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "Git not available" | Install Git on server |
| Authentication failed | Check username/token |
| Content not syncing | Check file format, review logs |
| Permission denied | Check uploads directory permissions |

## Sync Frequency

- **Manual**: Click "Sync Now" anytime
- **Automatic**: Enable auto-sync for hourly updates
- **Instant**: Set up webhooks (advanced)

## Commands

```bash
# Validate plugin structure
php validate.php

# Check PHP syntax
php -l git-sync.php

# View logs (if WP_DEBUG enabled)
tail -f wp-content/debug.log
```

## File Locations

- **Plugin**: `wp-content/plugins/git-sync/`
- **Repository**: `wp-content/uploads/gitsync-repo/`
- **Settings**: WordPress Options table
- **Logs**: `wp-content/debug.log` (if enabled)

## Admin URLs

- Settings: `/wp-admin/admin.php?page=gitsync-settings`
- Posts: `/wp-admin/edit.php`
- Pages: `/wp-admin/edit.php?post_type=page`
- Products: `/wp-admin/edit.php?post_type=product`

## Security Checklist

- [ ] Use HTTPS repository URLs
- [ ] Use access tokens (not passwords)
- [ ] Limit token permissions
- [ ] Enable WP_DEBUG only in development
- [ ] Keep WordPress updated
- [ ] Regular token rotation

## Support

- **Documentation**: See README.md and INSTALLATION.md
- **Validation**: Run `php validate.php`
- **Issues**: GitHub repository
- **Logs**: Enable WP_DEBUG for detailed logging

## Version

Current Version: 1.0.0

## License

GPL-2.0+
