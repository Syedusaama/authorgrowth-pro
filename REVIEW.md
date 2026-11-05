# Phase 1 review

Branch: `development/phase-1`

## Delivered

Installable WordPress plugin foundation, responsive dashboard with real native post counts, native Book Manager with draft/publish/scheduling, cover through Featured image, author/ISBN/release date/retailer metadata, REST registration, and public book details with Buy Now link. No paid dependencies. Documentation and reproducible ZIP packaging included.

## Validation performed

- PHP 8.4.24 syntax validation: all 4 PHP files passed.
- `tests/books-test.php`: 13 isolated assertions passed for URL/date/text validation, malformed input, missing/invalid nonce, permissions, revisions, wrong post type, and metadata whitelist.
- `scripts/package.sh`: ZIP built; all entries passed `unzip -t`.
- `git diff --check`: passed.

PHP was unavailable initially. System package installation was permission denied. Signed Debian package indexes and authenticated packages were used to extract a temporary PHP CLI under `/tmp/agp-php`; no TLS/signature checks were disabled. Tests can be repeated with any installed PHP 8.1+ using README commands.

## Unverified release gates

No live WordPress/database installation was available. Unit tests use API doubles and do not verify WordPress internals. Activation, dashboard rendering/counts, editor/media operations, REST requests, front-end templates, real role behavior, Gutenberg, and Elementor compatibility remain unverified. A ZIP integrity check verifies packaging, not WordPress activation. PHP 8.1 and WordPress 6.5 minimum-version compatibility also require a runtime matrix before production release.

Do not market this Phase 1 build as the complete premium product. Phases 2–4 remain pending. No leads, downloads, widgets, shortcodes, analytics, launches, testimonials, or custom branding settings are implemented.

## Manual review on WordPress

1. Upload ZIP, activate, and verify Books archive and individual pages with permalinks.
2. Create a draft with cover and all fields; publish, edit, schedule, trash, restore.
3. Confirm invalid dates/URLs clear; HTML in text displays safely.
4. Verify contributors cannot publish, authors cannot edit others' books, and subscribers cannot access the dashboard. Reject saves with invalid nonce and unauthorized REST updates.
5. View password-protected and draft/private books while logged out; metadata must not reveal protected content.
6. Confirm Gutenberg editing and Elementor Books opt-in on the target theme.
7. Deactivate/reactivate and confirm data retention. Check responsive layout, keyboard navigation, and translations.
