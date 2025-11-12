# Contributing to GitSync

Thank you for your interest in contributing to GitSync! This document provides guidelines for contributing to the project.

## Getting Started

1. Fork the repository
2. Clone your fork: `git clone https://github.com/yourusername/git-sync-for-wordpress.git`
3. Create a feature branch: `git checkout -b feature/your-feature-name`
4. Make your changes
5. Test your changes
6. Commit with clear messages: `git commit -m "Add feature: description"`
7. Push to your fork: `git push origin feature/your-feature-name`
8. Create a Pull Request

## Development Setup

### Requirements

- WordPress development environment (Local, MAMP, Docker, etc.)
- PHP 7.2 or higher
- Git installed locally
- Code editor (VS Code, PHPStorm, etc.)

### Local Installation

1. Clone the repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/wilsonify/git-sync-for-wordpress.git git-sync
   ```

2. Activate the plugin in WordPress admin

3. Configure with a test Git repository

## Code Standards

### PHP Code Standards

Follow WordPress PHP Coding Standards:

- Use tabs for indentation
- Opening braces on the same line for functions
- Single quotes for strings unless variables are needed
- Proper DocBlocks for all functions and classes
- Sanitize all inputs
- Escape all outputs

Example:
```php
/**
 * Function description
 *
 * @param string $param Parameter description
 * @return bool Return value description
 */
function gitsync_example_function( $param ) {
    $sanitized = sanitize_text_field( $param );
    return true;
}
```

### JavaScript Code Standards

- Use modern JavaScript (ES6+)
- Use strict mode
- Proper indentation and formatting
- Clear variable names
- Comment complex logic

### CSS Code Standards

- Use meaningful class names
- Follow BEM methodology when appropriate
- Group related properties
- Add comments for complex sections

## Testing

### Manual Testing

1. Test with different repository types (GitHub, GitLab, Bitbucket)
2. Test with public and private repositories
3. Test different content types (posts, pages, products)
4. Test sync with various Markdown formats
5. Test error handling and edge cases

### Test Checklist

- [ ] Plugin activates without errors
- [ ] Settings page loads correctly
- [ ] Repository connection works
- [ ] Markdown parsing is accurate
- [ ] Content syncs correctly
- [ ] Updates work properly
- [ ] Error messages are clear
- [ ] No PHP warnings/notices
- [ ] Works with latest WordPress version

## Pull Request Guidelines

### Before Submitting

1. Test your changes thoroughly
2. Update documentation if needed
3. Check code follows WordPress standards
4. Verify no PHP errors or warnings
5. Ensure backwards compatibility

### PR Description

Include:
- Clear description of changes
- Why the change is needed
- How to test the changes
- Screenshots (for UI changes)
- Related issues (if any)

### PR Title Format

- `Feature: Add support for custom post types`
- `Fix: Resolve authentication error with GitLab`
- `Docs: Update installation instructions`
- `Refactor: Improve markdown parser performance`

## Feature Requests

### Suggesting Features

1. Check existing issues to avoid duplicates
2. Clearly describe the feature
3. Explain the use case
4. Consider implementation complexity
5. Be open to discussion

### Feature Template

```
**Feature Description:**
Brief description of the feature

**Use Case:**
Why is this feature needed?

**Proposed Solution:**
How should it work?

**Alternatives Considered:**
Other approaches you've thought about
```

## Bug Reports

### Reporting Bugs

1. Check if bug is already reported
2. Use the bug template
3. Include reproduction steps
4. Provide error messages/logs
5. Include environment details

### Bug Template

```
**Bug Description:**
What's the problem?

**Steps to Reproduce:**
1. Step 1
2. Step 2
3. ...

**Expected Behavior:**
What should happen?

**Actual Behavior:**
What actually happens?

**Environment:**
- WordPress Version:
- PHP Version:
- GitSync Version:
- Git Version:
- Server OS:

**Error Messages:**
Include any error messages or logs
```

## Areas for Contribution

### High Priority

- Additional Markdown parser features
- Performance optimizations
- Security enhancements
- Test coverage
- Documentation improvements

### Feature Ideas

- Support for more content types
- Bi-directional sync (WordPress to Git)
- Conflict resolution UI
- Content preview before sync
- Sync history and rollback
- Multi-repository support
- Advanced webhook integration
- REST API endpoints

### Good First Issues

Look for issues labeled `good-first-issue`:
- Documentation improvements
- Simple bug fixes
- Code cleanup
- Adding examples

## Code Review Process

1. All PRs require review
2. Address review comments
3. Keep PRs focused and small
4. Be responsive to feedback
5. Be respectful and constructive

## Documentation

### Types of Documentation

- **README.md**: Overview and quick start
- **INSTALLATION.md**: Detailed setup guide
- **Code Comments**: Inline documentation
- **Examples**: Sample files and use cases

### Documentation Standards

- Clear and concise
- Include examples
- Update with code changes
- Check for accuracy
- Use proper markdown formatting

## Community

### Code of Conduct

- Be respectful and inclusive
- Welcome newcomers
- Provide constructive feedback
- Focus on the issue, not the person
- Help maintain a positive environment

### Getting Help

- GitHub Issues for bugs and features
- Discussions for questions
- Wiki for additional resources

## License

By contributing to GitSync, you agree that your contributions will be licensed under the GPL-2.0+ License.

## Questions?

If you have questions about contributing, feel free to:
- Open a discussion on GitHub
- Comment on related issues
- Reach out to maintainers

Thank you for contributing to GitSync! 🎉
