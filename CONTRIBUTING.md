# Contributing

Contributions are welcome! This guide explains how to add new converters and work with the codebase.

## What we merge

Converters and shortcode handlers for **widely used, generic HTML patterns** and
**WordPress core shortcodes** are welcome as pull requests. These benefit all users of the plugin.

**Examples of mergeable contributions:**

- A converter for `<details>/<summary>` (standard HTML)
- A shortcode handler for `[audio]` or `[video]` (WordPress core)
- Improvements to existing converters (better edge case handling, spec compliance)
- Bug fixes, performance improvements, documentation

## What we don't merge

Converters for **page builders, third-party plugins, or site-specific markup** are too niche for the core plugin.
These should live in your own code (a custom plugin or theme) and use the filter hooks to register.

**Examples of contributions that won't be merged:**

- A converter for Elementor sections (`<div class="elementor-section">`)
- A converter for WPBakery rows (`<div class="vc_row">`)
- A shortcode handler for a specific form plugin (`[ninja_form id="5"]`)
- Site-specific markup patterns (`<div class="my-company-widget">`)

### How to implement niche converters

Register them via filter in your own plugin:

```php
add_filter( 'classic_to_gutenberg_converters', function ( $factory ) {
    $factory->register( new My_Elementor_Converter() );
    return $factory;
} );
```

Or for shortcode handlers:

```php
add_filter( 'classic_to_gutenberg_shortcode_handlers', function ( $handlers ) {
    $handlers[] = new My_Custom_Shortcode_Handler();
    return $handlers;
} );
```

## Adding a Converter

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

- PHP 8.2+ with `declare(strict_types=1)` in every file
- PSR-4 autoloading under `src/` (namespace: `Apermo\ClassicToGutenberg`)
- Conventional commits (`feat`, `fix`, `refactor`, etc.)
- One topic per commit
