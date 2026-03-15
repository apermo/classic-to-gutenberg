# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WordPress plugin for batch migration of classic editor content to Gutenberg blocks. Converts HTML patterns (paragraphs, headings, images, lists, etc.) into their corresponding block editor equivalents using an extendable factory pattern.

**PHP 8.1+ minimum.** Strict types everywhere (`declare(strict_types=1)`).

## Architecture

- `plugin.php` — Main plugin entry point
- `src/Plugin.php` — Plugin bootstrap
- `src/Converter/` — Block conversion system
  - `BlockConverterFactory.php` — Factory that manages converter registration and lookup
  - `BlockConverterInterface.php` — Interface all converters implement
  - `AbstractBlockConverter.php` — Base class with shared serialization helpers
  - `ParagraphConverter.php`, `HeadingConverter.php`, etc. — Individual converters
  - `HtmlBlockConverter.php` — Fallback for unrecognized HTML
- `src/Migration/` — Batch migration orchestration (planned)
- `uninstall.php` — Cleanup on plugin deletion

### Key conventions

- PSR-4 autoloading under `src/` (namespace: `Apermo\ClassicToGutenberg`)
- Coding standards: `apermo/apermo-coding-standards` (PHPCS)
- Static analysis: `apermo/phpstan-wordpress-rules` + `szepeviktor/phpstan-wordpress`
- Testing: PHPUnit + Brain Monkey + Yoast PHPUnit Polyfills
- Test suites: `tests/Unit/` and `tests/Integration/`

### Block Conversion Factory Pattern

The factory (`BlockConverterFactory`) maintains a registry of `BlockConverterInterface` implementations. Each converter declares which HTML tag(s) it handles. The factory iterates top-level DOM nodes and delegates to the matching converter. Unmatched nodes fall through to `HtmlBlockConverter` (wraps in `core/html` block). Third-party plugins can register custom converters via WordPress filter hooks.

## Commands

```bash
composer cs              # Run PHPCS
composer cs:fix          # Fix PHPCS violations
composer analyse         # Run PHPStan
composer test            # Run all tests
composer test:unit       # Run unit tests only
composer test:integration # Run integration tests only
npm run test:e2e         # Run Playwright E2E tests
npm run test:e2e:ui      # Run E2E tests with UI
```

## Local Development (DDEV)

```bash
ddev start && ddev orchestrate   # Full WordPress environment
```

- Uses `apermo/ddev-orchestrate` addon
- Project type is `php` (not `wordpress`), so WP-CLI uses a custom `ddev wp` command wrapper
- Bind-mounts repo into `wp-content/plugins/`

## Git Hooks

Pre-commit hook runs PHPCS and PHPStan on staged files. Enable with:

```bash
git config core.hooksPath .githooks
```

## CI (GitHub Actions)

- `ci.yml` — PHPCS + PHPStan + PHPUnit across PHP 8.2, 8.3, 8.4
- `integration.yml` — WP integration tests (real WP + MySQL, multisite matrix)
- `e2e.yml` — Playwright E2E tests against running WordPress
- `wp-beta.yml` — Nightly WP beta/RC compatibility check
- `release.yml` — CHANGELOG-driven releases
- `pr-validation.yml` — conventional commit and changelog checks

## Template Sync

```bash
git remote add template https://github.com/apermo/template-wordpress.git
git fetch template
git checkout -b chore/sync-template
git merge template/main --allow-unrelated-histories
```
