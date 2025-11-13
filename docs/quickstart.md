---
layout: default
title: Quick Start
---

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

See repository examples for full format details. Use YAML frontmatter to set `title`, `type`, `status`, etc.

## Useful Commands

```bash
# Validate plugin structure
php validate.php

# Check PHP syntax
php -l git-sync.php

# Run tests locally
composer install
vendor/bin/phpunit -v
```
