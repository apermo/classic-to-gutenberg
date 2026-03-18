# Classic to Gutenberg

[![PHP CI](https://github.com/apermo/classic-to-gutenberg/actions/workflows/ci.yml/badge.svg)](https://github.com/apermo/classic-to-gutenberg/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/apermo/classic-to-gutenberg/graph/badge.svg)](https://codecov.io/gh/apermo/classic-to-gutenberg)
[![Packagist Version](https://img.shields.io/packagist/v/apermo/classic-to-gutenberg)](https://packagist.org/packages/apermo/classic-to-gutenberg)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](LICENSE)

Batch migration of WordPress classic editor content to Gutenberg blocks.

## Requirements

- PHP 8.2+
- WordPress 6.2+
- Composer

## Installation

This plugin uses Composer for autoloading. You must run `composer install` before activating:

```bash
composer install
```

If dependencies are missing, the plugin displays an admin notice and does not load.

## Usage

### WP-CLI

```bash
# Show how many classic posts need conversion
wp classic-to-gutenberg status

# Preview conversion without saving
wp classic-to-gutenberg convert --dry-run

# Convert all classic posts
wp classic-to-gutenberg convert

# Convert specific post types
wp classic-to-gutenberg convert --post-type=post,page --batch-size=100

# Rollback a converted post
wp classic-to-gutenberg rollback 42
```

### Admin Row Actions

On the Posts/Pages list screen, classic posts (without block markup) show two additional row actions:

- **Convert to Blocks** — converts the post content and saves immediately
- **Preview Blocks** — shows a preview of the converted block markup without saving

### Supported Conversions

| Classic HTML | Gutenberg Block |
|---|---|
| `<p>` | `core/paragraph` |
| `<h1>`–`<h6>` | `core/heading` |
| `<ul>`, `<ol>` | `core/list` with `core/list-item` |
| `<blockquote>` | `core/quote` with inner `core/paragraph` |
| `<table>` | `core/table` (requires `<thead>` + `<tbody>`) |
| `<img>` | `core/image` (standalone, linked, or in `<figure>`) |
| `<pre>` | `core/preformatted` |
| `<hr>` | `core/separator` |
| `<!--more-->` | `core/more` |
| `<!--nextpage-->` | `core/nextpage` |
| `[caption]` | `core/image` with caption |
| `[gallery]` | `core/gallery` with inner `core/image` |
| Other shortcodes | `core/shortcode` |
| Unrecognized HTML | `core/html` (fallback) |

### Hooks

#### Filters

- `classic_to_gutenberg_converters` — register custom block converters
- `classic_to_gutenberg_pre_convert` — filter content before conversion
- `classic_to_gutenberg_post_convert` — filter content after conversion
- `classic_to_gutenberg_finder_args` — filter post finder query arguments
- `classic_to_gutenberg_shortcode_handlers` — extend shortcode handler registry

#### Actions

- `classic_to_gutenberg_post_converted` — fired after each post conversion
- `classic_to_gutenberg_post_rolled_back` — fired after rollback
- `classic_to_gutenberg_batch_started` — fired before batch conversion
- `classic_to_gutenberg_batch_completed` — fired after batch conversion

## Development

```bash
composer cs               # Run PHPCS
composer cs:fix           # Fix PHPCS violations
composer analyse          # Run PHPStan
composer test             # Run all tests
composer test:unit        # Run unit tests only
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

## Contributing

Contributions are welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## Acknowledgements

Development of this plugin is supported by

- [Coding Pioneers](https://coding-pioneers.com)
- [Thomann](https://www.thomann.de)

## License

[GPL-2.0-or-later](LICENSE)
