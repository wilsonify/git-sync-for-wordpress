# Changelog

All notable changes to GitSync will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-15

### Added
- Initial release of GitSync WordPress plugin
- Git repository integration (HTTPS)
- Support for public and private repositories
- Markdown to HTML conversion
- YAML frontmatter parsing for metadata
- WordPress Post synchronization
- WordPress Page synchronization  
- WooCommerce Product synchronization
- Manual sync via admin interface
- Scheduled hourly sync option
- Admin settings page for configuration
- AJAX-based sync with real-time status updates
- Smart conflict resolution (updates by slug)
- Category and tag support for posts
- WooCommerce product metadata (price, sale_price, SKU, stock)
- Content type detection from directory structure
- Content type override via metadata
- Error logging and debugging support
- Security features (nonce verification, capability checks)
- Repository credentials support (username/token)
- Comprehensive documentation (README, INSTALLATION, CONTRIBUTING)
- Example Markdown files for posts, pages, and products
- Quick reference guide
- Validation script for plugin structure
- Admin CSS styling
- Admin JavaScript for AJAX sync

### Security
- Input sanitization for all user inputs
- Output escaping for all outputs
- Nonce verification for AJAX requests
- Capability checks for admin actions
- Secure credential storage in WordPress options

## [Unreleased]

### Planned Features
- Bi-directional sync (WordPress to Git)
- Webhook support for instant sync
- Conflict resolution UI
- Content preview before sync
- Sync history and rollback capability
- Multi-repository support
- REST API endpoints
- Advanced markdown features (tables, footnotes)
- Custom post type support
- Media file synchronization
- Translation support
- Performance optimizations for large repositories
- Shallow clone support
- Selective directory sync
- Custom metadata field mapping

### Known Issues
- None currently reported

---

## Version History

- **1.0.0** (2025-01-15): Initial release

## Links

- [Repository](https://github.com/wilsonify/git-sync-for-wordpress)
- [Documentation](README.md)
- [Installation Guide](INSTALLATION.md)
- [Quick Start](QUICKSTART.md)
- [Contributing](CONTRIBUTING.md)
