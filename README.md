# Classic to Gutenberg

[![PHP CI](https://github.com/apermo/classic-to-gutenberg/actions/workflows/ci.yml/badge.svg)](https://github.com/apermo/classic-to-gutenberg/actions/workflows/ci.yml)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](LICENSE)

Batch migration of WordPress classic editor content to Gutenberg blocks.

## Requirements

- PHP 8.1+
- WordPress 6.0+
- Composer

## Installation

```bash
composer install
```

## Development

```bash
composer cs              # Run PHPCS
composer cs:fix          # Fix PHPCS violations
composer analyse         # Run PHPStan
composer test            # Run all tests
composer test:unit       # Run unit tests only
composer test:integration # Run integration tests only
```

### Local WordPress Environment

```bash
ddev start && ddev orchestrate
```

### Git Hooks

Enable the pre-commit hook (PHPCS + PHPStan on staged files):

```bash
git config core.hooksPath .githooks
```

## License

[GPL-2.0-or-later](LICENSE)
