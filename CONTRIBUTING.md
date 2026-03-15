# Contributing

Contributions are welcome! This guide explains how to add new converters and work with the codebase.

## Adding a Converter

Common converters (widely used HTML patterns) belong in `src/Converter/`. Niche or site-specific converters should be registered via the `classic_to_gutenberg_converters` filter hook.

### Steps

1. Create a class in `src/Converter/` extending `AbstractBlockConverter`
2. Implement `get_supported_tags()` — return the HTML tags your converter handles
3. Implement `convert()` — return valid Gutenberg block markup
4. Optionally override `can_convert()` — refine matching beyond tag name
5. Register in `Plugin::create_factory()`
6. Add fixture files in `tests/fixtures/` (input `.html` + expected `.expected.html`)
7. Run the test suite to verify

### Adding a Shortcode Handler

1. Create a class in `src/Converter/Shortcode/` implementing `ShortcodeHandlerInterface`
2. Register it in `Plugin::create_factory()` or via the `classic_to_gutenberg_shortcode_handlers` filter

## Code Quality

All code must pass:

```bash
composer cs        # PHPCS (Apermo coding standards)
composer analyse   # PHPStan level 6
composer test:unit # PHPUnit unit tests
```

## Conventions

- PHP 8.1+ with `declare(strict_types=1)` in every file
- PSR-4 autoloading under `src/` (namespace: `Apermo\ClassicToGutenberg`)
- Conventional commits (`feat`, `fix`, `refactor`, etc.)
- One topic per commit
