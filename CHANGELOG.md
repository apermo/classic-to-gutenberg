# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2026-03-15

### Changed

- Upgrade `apermo/apermo-coding-standards` to 2.6.1 — fully qualify PHP native functions, WP functions stay unqualified
- `phpcs.xml.dist`: project-specific `text_domain`, `prefixes`, `minimum_wp_version`
- Integration test matrix auto-detects minimum WP version from plugin header
- Packagist support: keywords and support URLs in `composer.json`

### Added

- GitHub issue templates (bug report, feature request)
- GitHub pull request template
- Gemini AI code review config with project-specific styleguide
- Branch protection on `main` with required status checks

## [0.1.0] - 2026-03-15

### Added

- Content splitter (`TopLevelSplitter`) for parsing wpautop'd HTML
- Core block converters: paragraph, heading, separator, preformatted, more, nextpage, list, quote, table, image
- Shortcode handlers: `[caption]` → `core/image`, `[gallery]` → `core/gallery`, unknown → `core/shortcode`
- Content conversion pipeline (`ContentConverter`) with wpautop integration
- Migration infrastructure: `ClassicPostFinder`, `MigrationRunner`, `MigrationRollback`
- WP-CLI commands: `status`, `convert` (with post ID support), `rollback`
- Admin row actions: "Convert to Blocks" and "Preview Blocks" on post list
- Extensibility hooks for converters, shortcode handlers, and migration lifecycle
- Fixture-based integration tests for all conversion patterns
- E2E smoke tests for WP-CLI commands and admin row actions
