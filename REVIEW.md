# Phase 3 review

Branch: `development/phase-3`, based on Phase 2 commit `92b355c`.

## Delivered

- Required `[agp_book]`, `[agp_books]`, and `[agp_floating_cta]` shortcodes, responsive grid/list cards, covers/titles/descriptions, selected-book collections, constrained color/button options and one in-flow sticky CTA per page.
- Existing Book Manager retailer URL powers nonce-protected POST buttons in cards, sticky bars and native single-book content. Server-side destination validation ignores visitor-supplied URLs; only published unprotected books qualify.
- Aggregate UTC day/book/retailer-host counters, atomic SQL increment, and admin-only reports with 7/30/90-day filters, daily/ISO-week/monthly grouping and individual-book filter. Reports distinguish clicks from sales and unique readers.
- Known-bot/prefetch exclusions, hashed-IP hourly rate limits, DNT/GPC opt-out and no visitor event log/cookies.
- Navy/white/orange interface and local lightweight CSS without external libraries or JavaScript dependencies.
- Existing Phase 1/2 modules retained, with tests rerun. Version 0.3.0 ZIP and installation/upgrade/privacy/shortcode documentation.

## Executed validation

- PHP 8.4.24 syntax checks: all 10 plugin/test PHP files passed.
- Phase 1 suite: 13 assertions passed.
- Phase 2 suite: 30 assertions passed; download subprocess suite: 6 scenarios passed.
- Phase 3 suite: 34 assertions passed for safe destinations, ignoring arbitrary redirect parameters, invalid nonce/method handling, bot/prefetch/DNT/GPC filtering, rate-limited counting without blocking redirects, aggregate-only write parameters, period grouping, public-book visibility, escaping, bounded options/queries, list rendering, sticky deduplication, admin permissions and malformed report filters.
- Updated ZIP build and `unzip -t`: passed.
- `git diff --check`: passed.

These are PHP CLI tests with WordPress/database doubles. PHP uses the previously extracted local Debian CLI under `/tmp/agp-php`. No environment configuration was published or changed.

## Unverified integration tests / release gates

No live WordPress/database/mail setup was available. Activation and upgrade schema creation, actual MySQL/MariaDB atomic increments and race handling, HTTP redirect headers, full-page cache/nonce behavior, real theme layouts, browser/mobile/keyboard visuals, Gutenberg/Elementor integration, and minimum PHP 8.1/WordPress 6.5 remain unverified. Phase 1/2 mail delivery, uploads, WordPress permissions/REST behavior, privacy tools and scheduled retention also remain unverified in a live site. CLI double tests do not establish those behaviors.

Before production, back up and upgrade on staging per README. Confirm all three shortcodes with draft, private, password-protected, missing-cover and missing-link books. Test 1–4 columns, tablet/mobile views, keyboard focus, long titles and custom contrast. Verify theme containers permit the intended sticky behavior without covering other controls. Test Gutenberg and Elementor Shortcode blocks/widgets.

Click saved Amazon/other retailer URLs; inspect 303 responses and aggregate rows; forge URL parameters, invalid nonces, drafts and unsafe retailer destinations. Confirm bot/prefetch/DNT/GPC requests redirect but do not increment; GET requests must not count. Exercise concurrent clicks on one daily row, date boundaries/partial weeks/months, individual books and admin permission checks. Recheck book CRUD, covers, chapter capture/mail/download and lead privacy/export workflows after upgrade.

## Intentional limits

One saved retailer destination per book, grouped by hostname (not a retailer-link picker). Clicks are approximate button interactions, never sales or unique readers. Header-based bot detection can be spoofed. Shared-proxy IPs share limits. Aggregate history persists until removed by a database administrator; no analytics retention UI yet. Cached forms expire with WordPress nonces. Sticky bars are bounded by their theme container. Frontend CSS loads site-wide for builder compatibility. Theme/builder compatibility and final visual polish require live review. Phase 4 remains pending.
