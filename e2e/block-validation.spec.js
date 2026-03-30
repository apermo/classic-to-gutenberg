const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');

const USE_DDEV = !process.env.CI;
const WP_PATH = process.env.WP_PATH || '/tmp/wordpress';
const FIXTURES_SQL = 'tests/fixtures/testdata.sql';

/**
 * Run a WP-CLI command and return stdout.
 *
 * @param {string} command
 * @returns {string}
 */
function wp(command) {
    const cmd = USE_DDEV
        ? `ddev wp ${command} --user=admin`
        : `wp ${command} --path=${WP_PATH} --allow-root --user=admin`;

    return execSync(cmd, {
        encoding: 'utf-8',
        timeout: 30000,
    }).trim();
}

/**
 * Import test fixtures (can be called multiple times to reset state).
 */
function importFixtures() {
    if (USE_DDEV) {
        wp(`db import ${FIXTURES_SQL}`);
    } else {
        wp(`db import ${process.cwd()}/${FIXTURES_SQL}`);
    }
}

/**
 * Classic posts from testdata.sql that should be migrated and validated.
 */
const CLASSIC_POSTS = [
    { id: 100, title: 'Simple Paragraphs' },
    { id: 101, title: 'Headings Mixed Content' },
    { id: 102, title: 'Lists' },
    { id: 103, title: 'Blockquote and Separator' },
    { id: 104, title: 'Images and Shortcodes' },
    { id: 105, title: 'Table Content' },
    { id: 106, title: 'More and Nextpage Markers' },
    { id: 110, title: 'Classic Page Content' },
    { id: 121, title: 'Draft Classic Post' },
];

test.describe.serial('Block validation after migration', () => {
    test('reset and convert all fixtures', () => {
        importFixtures();

        const ids = CLASSIC_POSTS.map((p) => p.id).join(',');
        const output = wp(`classic-to-gutenberg convert ${ids}`);
        expect(output).toContain('Converted');
    });

    for (const post of CLASSIC_POSTS) {
        test(`post ${post.id} (${post.title}) has no broken blocks`, async ({ page }) => {
            await page.goto(`/wp-admin/post.php?post=${post.id}&action=edit`);

            // Dismiss the welcome guide if it appears.
            const welcomeButton = page.locator(
                '.edit-post-welcome-guide .components-modal__header button, ' +
                'button:has-text("Get started")',
            );
            if (await welcomeButton.isVisible({ timeout: 3000 }).catch(() => false)) {
                await welcomeButton.first().click();
            }

            // Wait for the block editor to fully load.
            await page.waitForSelector('.block-editor-block-list__layout', {
                timeout: 15000,
            });

            // Assert no block validation warnings exist.
            const warnings = page.locator('.block-editor-warning');
            await expect(warnings).toHaveCount(0);

            // Assert no blocks are marked with the warning state.
            const warningBlocks = page.locator(
                '.block-editor-block-list__block.has-warning',
            );
            await expect(warningBlocks).toHaveCount(0);
        });
    }
});
