# WordPress Template - Code Review Style Guide

## Project Context

This is a GitHub template repository for bootstrapping WordPress plugins and themes. It ships both plugin and theme scaffolding; a `setup.sh` script lets developers choose their mode and configures the project accordingly. PHP 8.1+ minimum, strict types everywhere.

## PHP

- Follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).
- Use tabs for indentation (not spaces).
- All files must declare `declare(strict_types=1)`.
- PSR-4 autoloading under `src/` with the `Plugin_Name` namespace (placeholder replaced by `setup.sh`).
- All user-facing strings must be translatable using `__()`, `_e()`, `esc_html__()`, `esc_html_e()`, `esc_attr__()`, or `esc_attr_e()` with the `plugin-name` text domain.
- Translator comments (`/* translators: ... */`) are required before any translation function call that contains placeholders.
- All output must be properly escaped using `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`, etc.
- Use post-increment (`$i++`) over pre-increment (`++$i`).
- Coding standards are enforced via PHPCS with the custom `Apermo` ruleset.
- Static analysis via PHPStan at level 6.

## Security

- All user input must be sanitized and validated.
- All output must be escaped.
- Nonce verification is required for form submissions.

## Testing

- Unit tests use PHPUnit with Brain Monkey for mocking WordPress functions.
- Integration tests run against a real WordPress instance.
- E2E tests use Playwright.

## Commits

- This project uses Conventional Commits with a 50-char subject / 72-char body limit.
- Each commit should address a single concern.
