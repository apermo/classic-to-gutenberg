# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.4.0] - 2026-03-23

### Added

- Converter chaining: multiple converters per tag with LIFO fallthrough (#20)
- WP-CLI `detect` command for finding unparseable content (#15)
- Integration test coverage uploaded to Codecov (#21)
- Unit tests for MigrationRunner (8 tests)
- Unit tests for ContentConverter (7 tests)
- Unit tests for ClassicPostFinder (6 tests)
- Unit tests for AdminNotice (7 tests)
- Unit tests for RowAction (5 tests)
- Fixture for styled paragraph edge case

### Fixed

- Gallery output: missing line break after `<figure>` opening tag (#16)
- Double `<p>` wrapping on styled paragraphs from wpautop (#17)

## [0.3.1] - 2026-03-18

### Added

- Acknowledgements section in README
- Contributing section in README
- Merge policy with examples in CONTRIBUTING.md
- Planned add-ons library note in CONTRIBUTING.md
- Codecov integration and badge
- Packagist version badge

### Fixed

- Author email in composer.json
- PHPStan false positives on int comparison (moved to config-level ignores)

## [0.3.0] - 2026-03-15

### Added

- Bulk action "Convert to Blocks" in post list dropdown with section header
- Side-by-side block preview: 2x2 grid comparing rendered content and raw markup
- Post locking during conversion (respects `wp_check_post_lock`, acquires/releases lock)
- `Permission::user_can_convert()` with `classic_to_gutenberg_user_can_convert` filter
- WP-CLI `convert` command validates user and logs identity before running
- Post links in success notice (single: "Open Post Title", batch: list of links)
- Unit tests for permission checks (single site, multisite, filter grant/revoke)

### Changed

- Permission model: requires `manage_options` (single site) or super admin (multisite) instead of `edit_post`
- Preview opens in new tab (`target="_blank"`) instead of navigating away
- Convert redirect preserves referring page (filters, pagination)
- Updated `.gitattributes` export-ignore list

### Fixed

- Graceful admin notice with plugin name when `vendor/autoload.php` is missing
- Bulk action registration timing (was nested in `admin_init`, never fired)

## [0.2.0] - 2026-03-15

### Changed

- Upgrade `apermo/apermo-coding-standards` to 2.6.1
  — fully qualify PHP native functions, WP functions stay unqualified
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
