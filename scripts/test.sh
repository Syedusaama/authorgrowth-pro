#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
PHP_BIN=${PHP_BIN:-php}
find authorgrowth-pro tests -name '*.php' -print | while IFS= read -r file; do "$PHP_BIN" -n -l "$file"; done
for suite in books leads promotions release bootstrap; do "$PHP_BIN" -n "tests/$suite-test.php"; done
export PHP_BIN
python3 tests/download-test.py
python3 tests/private-storage-test.py
node --check authorgrowth-pro/assets/release.js
node tests/release-js-test.cjs

if [ -n "${WP_CORE_DIR:-}" ]; then
 "$PHP_BIN" -n tests/admin-menu-test.php --legacy
 "$PHP_BIN" -n tests/admin-menu-test.php
else
 printf '%s\n' 'SKIP: WordPress-core admin menu regression (set WP_CORE_DIR to a WordPress source tree; see ADMIN-FIX.md).'
fi
