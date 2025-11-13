---
layout: default
title: Testing
---

# Testing

Quick instructions to run the unit tests for this plugin.

## Prerequisites
- PHP >= 7.4
- Composer

## Install dev dependencies

```bash
composer install
```

## Run the test suite

```bash
vendor/bin/phpunit -v
```

Notes
- Tests are lightweight unit tests that stub minimal WordPress functions in `tests/bootstrap.php`.


TESTING
=======

Quick instructions to run the unit tests for this plugin.

Prerequisites
- PHP >= 7.4
- Composer

Install dev dependencies

```bash
composer install
```

Run the test suite

```bash
vendor/bin/phpunit -v
```

Notes
- Tests are lightweight unit tests that stub minimal WordPress functions in `tests/bootstrap.php`.
- Additional integration tests requiring a full WP environment can be added later (e.g., using the WordPress Test Suite or WP Testbench).
