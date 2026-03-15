#!/usr/bin/env bash
#
# Import E2E test fixtures into WordPress.
# Workaround: placed in addon fragments dir until ddev-orchestrate#3 lands.
# See: https://github.com/apermo/classic-to-gutenberg/issues/10

TESTDATA_FILE="/var/www/html/tests/fixtures/testdata.sql"

if [ ! -f "$TESTDATA_FILE" ]; then
    echo "No test data file found, skipping."
    return 0
fi

echo "Importing test fixtures..."
wp db import "$TESTDATA_FILE" --path="${WP_PATH}" --quiet
echo "Test fixtures imported."
