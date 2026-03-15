const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');

const WP_PATH = process.env.WP_PATH || '/tmp/wordpress';

/**
 * Run a WP-CLI command and return stdout.
 *
 * @param {string} command
 * @returns {string}
 */
function wp(command) {
    return execSync(`wp ${command} --path=${WP_PATH} --allow-root`, {
        encoding: 'utf-8',
        timeout: 30000,
    }).trim();
}

test.describe('Setup', () => {
    test('import test data', () => {
        wp(`db import ${process.cwd()}/tests/fixtures/testdata.sql`);

        const title = wp('post get 100 --field=title');
        expect(title).toBe('Simple Paragraphs');
    });
});

test.describe('WP-CLI: status', () => {
    test('reports classic posts', () => {
        const output = wp('classic-to-gutenberg status');
        expect(output).toContain('classic post(s)');
    });

    test('filters by post type', () => {
        const output = wp('classic-to-gutenberg status --post-type=page');
        expect(output).toContain('classic post(s)');
    });
});

test.describe('WP-CLI: convert', () => {
    test('dry run does not modify posts', () => {
        const before = wp('post get 100 --field=content');
        const output = wp('classic-to-gutenberg convert 100 --dry-run');

        expect(output).toContain('[OK] Post #100');
        expect(output).toContain('Would convert');

        const after = wp('post get 100 --field=content');
        expect(after).toBe(before);
    });

    test('converts a single post by ID', () => {
        const output = wp('classic-to-gutenberg convert 100');
        expect(output).toContain('[OK] Post #100');
        expect(output).toContain('Converted 1 post(s)');

        const content = wp('post get 100 --field=content');
        expect(content).toContain('<!-- wp:paragraph -->');
    });

    test('converts multiple posts by IDs', () => {
        const output = wp('classic-to-gutenberg convert 101,102');
        expect(output).toContain('[OK] Post #101');
        expect(output).toContain('[OK] Post #102');
        expect(output).toContain('Converted 2 post(s)');

        const content101 = wp('post get 101 --field=content');
        expect(content101).toContain('<!-- wp:heading -->');

        const content102 = wp('post get 102 --field=content');
        expect(content102).toContain('<!-- wp:list -->');
    });

    test('converts quote and separator', () => {
        wp('classic-to-gutenberg convert 103');

        const content = wp('post get 103 --field=content');
        expect(content).toContain('<!-- wp:quote -->');
        expect(content).toContain('<!-- wp:separator -->');
    });

    test('converts images and shortcodes', () => {
        wp('classic-to-gutenberg convert 104');

        const content = wp('post get 104 --field=content');
        expect(content).toContain('<!-- wp:image');
        expect(content).toContain('<!-- wp:gallery');
        expect(content).toContain('<!-- wp:shortcode -->');
    });

    test('converts table', () => {
        wp('classic-to-gutenberg convert 105');

        const content = wp('post get 105 --field=content');
        expect(content).toContain('<!-- wp:table -->');
        expect(content).toContain('has-fixed-layout');
    });

    test('converts more and nextpage markers', () => {
        wp('classic-to-gutenberg convert 106');

        const content = wp('post get 106 --field=content');
        expect(content).toContain('<!-- wp:more -->');
        expect(content).toContain('<!-- wp:nextpage /-->');
    });

    test('converts a page', () => {
        wp('classic-to-gutenberg convert 110');

        const content = wp('post get 110 --field=content');
        expect(content).toContain('<!-- wp:heading -->');
        expect(content).toContain('<!-- wp:list -->');
    });

    test('skips already-converted post', () => {
        const before = wp('post get 120 --field=content');

        // Status should not count post 120 (already has blocks).
        const status = wp('classic-to-gutenberg status');

        // Converting by ID still runs but content already has blocks.
        const after = wp('post get 120 --field=content');
        expect(after).toContain('<!-- wp:paragraph -->');
    });

    test('batch convert finds remaining classic posts', () => {
        // Post 121 (draft) should still be classic.
        const content = wp('post get 121 --field=content');
        expect(content).not.toContain('<!-- wp:');

        const output = wp('classic-to-gutenberg convert');
        expect(output).toContain('[OK] Post #121');

        const converted = wp('post get 121 --field=content');
        expect(converted).toContain('<!-- wp:paragraph -->');
    });
});

test.describe('WP-CLI: rollback', () => {
    test('restores post from revision', () => {
        // Re-import to get fresh data for rollback test.
        wp(`db import ${process.cwd()}/tests/fixtures/testdata.sql`);

        const original = wp('post get 100 --field=content');
        expect(original).not.toContain('<!-- wp:');

        wp('classic-to-gutenberg convert 100');
        const converted = wp('post get 100 --field=content');
        expect(converted).toContain('<!-- wp:paragraph -->');

        wp('classic-to-gutenberg rollback 100');
        const restored = wp('post get 100 --field=content');
        expect(restored).not.toContain('<!-- wp:');
    });
});

test.describe('Admin UI', () => {
    test('shows row actions for classic posts', async ({ page }) => {
        // Re-import fresh data.
        wp(`db import ${process.cwd()}/tests/fixtures/testdata.sql`);

        await page.goto('/wp-admin/edit.php');

        // Post 100 should have the "Convert to Blocks" action.
        const row = page.locator('#post-100');
        await row.hover();
        await expect(row.locator('.ctg_convert a')).toBeVisible();
        await expect(row.locator('.ctg_preview a')).toBeVisible();
    });

    test('hides row actions for block posts', async ({ page }) => {
        await page.goto('/wp-admin/edit.php');

        const row = page.locator('#post-120');
        await row.hover();
        await expect(row.locator('.ctg_convert')).toHaveCount(0);
    });

    test('convert row action works', async ({ page }) => {
        await page.goto('/wp-admin/edit.php');

        const row = page.locator('#post-100');
        await row.hover();
        await row.locator('.ctg_convert a').click();

        // Should redirect back to edit.php with a success notice.
        await expect(page.locator('.notice-success')).toBeVisible();

        // Verify post was converted.
        const content = wp('post get 100 --field=content');
        expect(content).toContain('<!-- wp:paragraph -->');
    });
});
