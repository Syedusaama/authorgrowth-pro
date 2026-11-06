# AuthorGrowth Pro

Premium author marketing plugin, under active development. **Version 0.4.0-rc.2 includes the admin navigation hotfix. Not production-certified.** GPL-2.0-or-later.

## Install

Requires WordPress 6.5+, PHP 8.1+. Back up your site and test on staging first.
Run `./scripts/package.sh`, then upload `dist/authorgrowth-pro-0.4.0-rc.2.zip` using Plugins → Add New → Upload Plugin and activate. No Composer, Node, API keys, or paid dependencies are required.

## Phase 1 workflow

Open AuthorGrowth → Dashboard. Choose Add a book. Add a title, description, excerpt, author name, optional ISBN, release date, and absolute HTTP/HTTPS retailer URL. Use WordPress Featured image for the cover (requires theme featured-image support). Publish or schedule using the native editor. Books are available at `/books/`; individual book pages append a cover, author, and Buy Now button. Save Settings → Permalinks if a theme/server still returns 404 after activation. Theme templates control the archive and final presentation.

Gutenberg uses the native block editor and registered REST metadata. Elementor can enable the Books post type in its settings; no Elementor-specific widgets ship in Phase 1. Browser/editor integration must be tested on the target site before release.

Books follow native post permissions: contributors can draft, authors publish their own, and editors/admins manage others. This release uses the shared WordPress post capabilities rather than a separate books role. Media uploads require WordPress upload permissions. Drafts and private books retain WordPress visibility protections. Meta saves require a nonce and edit permission; REST writes use WordPress authentication and per-book permission checks. URLs permit HTTP/HTTPS and reject unsafe destinations; no remote URL is fetched by this plugin.

Phase 1 has no tracking or analytics. Phase 2 lead handling and privacy are described below. Retailer links navigate readers to third-party sites. Deactivation retains books and metadata; deletion also retains data intentionally. Remove books through WordPress if permanent deletion is desired.

## Development and verification

Use the existing checkout; no worktree is required. Run `find authorgrowth-pro tests -name '*.php' -exec php -l {} \;` and `php tests/books-test.php`. The lightweight test suite checks validation and save guards with WordPress API doubles; it does not replace real WordPress testing. Build the installable ZIP with `./scripts/package.sh`.

Before a production release, verify activation/deactivation, dashboard permissions/counts, book CRUD, cover upload, publishing/scheduling, nonce rejection, REST authorization, front-end escaping, Gutenberg and Elementor editing on a real WordPress site. See REVIEW.md for actual results and limitations.

## Roadmap

1. Foundation, dashboard, Book Manager (this release).
2. Reader leads and free chapter PDF downloads with consent/privacy controls (implemented in 0.2.0).
3. Promotion widgets/shortcodes and retailer click analytics (implemented in 0.3.0).
4. Launch countdown/waitlist, moderated reviews, branding/settings and release validation (implemented in 0.4.0-rc.1; live integration tests still required).

Future phases are not implemented and no synthetic lead or analytics metrics appear in the dashboard.

## Phase 2 — reader leads and private chapters (0.2.0)

Phase 2 adds `[agp_chapter book="123"]` forms, emailed single-use chapter links, admin-only Reader Leads with pagination/deletion, subscriber CSV export, and dashboard counts. Use the shortcode in Gutenberg's Shortcode block or Elementor's Shortcode widget. Phase 1 book editing and front-end details remain intact. Phase 4 additions are described in the release-candidate section below.

### Private storage setup (required for downloads)

Create an existing directory readable/writable by the PHP process **outside all web-served directories**, with restrictive permissions (for example `/srv/private/authorgrowth`). In `wp-config.php`, before WordPress loads, add:

```php
define( 'AGP_PRIVATE_DIR', '/srv/private/authorgrowth' );
```

Do not use the Media Library or public uploads for private chapters. The plugin rejects storage under WordPress or the reported document root, rejects escaping symlinks, and never exposes a file path or public file URL. Your host must also ensure this directory is not exposed by aliases, another virtual host, a CDN, or backups; the plugin cannot discover that server configuration. Keep private storage in your backup plan. No default directory is created because a safe location depends on hosting.

An administrator opens AuthorGrowth → Reader Leads, enters a saved book ID, and uploads a PDF (10 MB maximum). PHP fileinfo is required; MIME type and PDF signature are checked. This is file-type validation, not a malware scanner. Upload only trusted PDFs. Replacing a PDF deletes its prior private file; existing unexpired links deliver the current chapter. Publishing a book without a password enables its form. Unpublishing/password-protecting it disables requests and outstanding downloads.

### Delivery, consent, and anti-spam

Configure working WordPress mail delivery first. A successful `wp_mail` return means the transport accepted the message, not that the inbox received it. Links expire in 30 minutes and are consumed atomically once. Only token hashes are stored. Using a link confirms the email; it does not grant marketing consent unless the separate, unchecked marketing box was selected. Email security scanners may consume links; request a fresh link if needed. A repeated request replaces the pending token and consent record for that email/book, requiring fresh verification. No marketing messages are sent by the plugin.

Forms use WordPress nonces and a honeypot. Atomic hourly rate limits permit up to 10 attempts per connection IP and 3 per email. IPs are only stored as keyed hashes in short-lived counters; forwarded headers are not trusted. Shared proxies may share the IP limit. This reduces abuse; it is not a CAPTCHA or a complete distributed-abuse defense. Exclude chapter-form pages and `admin-post.php` from caching; stale cached nonces can reject legitimate forms. Use HTTPS site-wide so email links and captured addresses travel securely.

### Privacy and lead operations

Only administrators (`manage_options`) can view/delete/export leads or upload private chapters. Book authors do not automatically receive access to reader emails. CSV export includes **verified, consenting rows only**, with consent wording and UTC request/verification timestamps. Formula-like cells are escaped. The same email may appear once per requested book. Dashboard totals are current stored request records, verified readers per book, and verified consenting records, not unique people or analytics event counts.

Publish a privacy policy explaining chapter delivery, optional marketing, retention, and a contact address for withdrawal. The form links the configured WordPress privacy page. WordPress Tools → Export/Erase Personal Data includes plugin leads. Deleting/erasing a lead invalidates its token and removes its consent; administrators must also update any external mailing lists or CSV copies. Pending unverified records are removed after seven days by daily WP-Cron (traffic/cron execution required); verified records remain until deleted/erased. Review these regularly under your retention policy. No automatic marketing service integration or unsubscribe email service is included. Deactivation clears the cleanup schedule but retains leads and PDFs; plugin deletion retains them too. Protect database backups and exported CSV files as personal data.

### Phase 2 checks

Run `php tests/books-test.php`, `php tests/leads-test.php`, and `python3 tests/download-test.py` (set `PHP_BIN` if necessary). These tests use doubles. Real mail delivery, SQL schema/migration/concurrency, HTTP headers, WP-Cron/privacy tools, upload handling, and browser integrations still need a real WordPress staging site. Build the updated ZIP with `./scripts/package.sh` → `dist/authorgrowth-pro-0.2.0.zip`.

## Phase 3 — promotion cards and retailer click analytics (0.3.0)

### Install / upgrade

Requires WordPress 6.5+ and PHP 8.1+. Back up the database, private chapter directory and plugin files. On staging, upload `dist/authorgrowth-pro-0.3.0.zip` through Plugins → Add New → Upload Plugin; choose to replace the existing plugin, then activate if inactive. The versioned schema check creates the aggregate clicks table on the next WordPress initialization, including upgrades without reactivation. Existing books, metadata, leads, private PDFs and `AGP_PRIVATE_DIR` are retained. No paid dependencies or external libraries are required. Confirm the Phase 1/2 workflows and table creation before updating production. Restoring an earlier plugin version leaves aggregate data in the database; restore backups for a full rollback.

### Promotion shortcodes

Place these in Gutenberg's Shortcode block, Elementor's Shortcode widget, or a shortcode-enabled theme area:

```text
[agp_book id="123"]
[agp_books layout="grid" columns="3"]
[agp_books ids="123,456" layout="list" limit="10"]
[agp_floating_cta book="123"]
[agp_book id="123" color="#F97316" background="#ffffff" text="#17233b" button="Shop the book"]
```

Cards display the cover (if configured), title, plain-text description/excerpt and retailer button. Grid columns are clamped to 1–4, with responsive tablet/mobile layouts. Collections default to the 12 newest published books, allow 1–50 books, and optionally accept an ordered comma-separated `ids` selection. Draft/private/password-protected books are omitted. A book without a valid retailer URL displays without a Buy Now button. No raw HTML or CSS is accepted in attributes. Choose accessible contrasting colors; theme CSS can further customize the scoped `.agp-promotions` classes.

The floating CTA is a **sticky in-flow bar**, not a viewport-covering popup. Place it in the content area where it should stick; sticky behavior is bounded by the theme's containing element and can be affected by ancestor overflow. Only one bar renders per page. The bar uses the book title and button; full cards include covers and descriptions. Frontend CSS is a small local stylesheet, loaded site-wide to support builder/template shortcodes. No JavaScript is required.

### Click reports and safety

Open AuthorGrowth → Click Analytics as an administrator. Choose 7/30/90 days (inclusive of today), daily/ISO-week/month grouping, and optionally an individual Book ID. Tables show total clicks, periods, retailer hostnames and individual books. Periods at the beginning/end of the selected date range are partial; missing periods have zero clicks and are omitted. All reporting dates are UTC. Book Manager's existing **Buy Now URL** remains the single retailer destination per book; Amazon and other retailers are distinguished by hostname. Updating the saved URL changes future destinations, while historical counts retain the hostname used at click time. There is no multiple-retailer picker in this release.

All plugin Buy Now buttons, including individual book pages, use a nonce-protected POST handler followed by a 303 redirect. The destination is read exclusively from the published book's saved, validated HTTP/HTTPS URL; visitor URL/host parameters are ignored. Embedded credentials, unsafe URL schemes/private destinations (via WordPress URL validation), and unusual ports are rejected. Trusted editors still control retailer URLs and should review them: this is not a retailer-domain allowlist or a safety guarantee about third-party websites. The plugin does not fetch retailer pages or interpret clicks as purchases. External links elsewhere in a theme, raw content, or third-party widgets are not tracked. Exclude shortcode pages from full-page caching, or use a cache lifetime shorter than the WordPress nonce window; stale buttons require reloading a fresh page.

These are **button-click counts, not sales, unique visitors, or confirmed retailer visits**. Known bots, empty bot user agents, prefetch-purpose headers and excessive requests are excluded where detectable; automation can still spoof headers. At most 60 eligible click requests per connection IP per UTC hour count across books. Excluded/limited requests still redirect safely. Counters use keyed IP hashes with the Phase 2 cleanup system; raw IPs, user agents, emails, cookies and per-visitor click histories are not saved in analytics. DNT and Global Privacy Control requests redirect without counting. Aggregated day/book/retailer totals are retained until removed by the site owner/database administrator and survive deactivation/deletion. Document this in your privacy policy. Web server logs and retailer privacy practices are outside this plugin's control.

### Validation

Run the existing suites plus `php tests/promotions-test.php`. See REVIEW.md for executed results and remaining live WordPress/editor/database/browser checks. Build with `./scripts/package.sh` for `dist/authorgrowth-pro-0.3.0.zip`.

## Phase 4 release candidate — 0.4.0-rc.1

This is a review build with all four phases represented. **It is not certified for production.** Historical phase sections above describe earlier defaults; current Settings retention and module controls override them.

### Launch manager

Edit a book → Book Launch (UTC). Set the launch date/time and an absolute HTTP/HTTPS preorder URL. Publish the book now as a coming-soon page, rather than scheduling the WordPress post: unpublished books remain private and are never promoted. Add:

```text
[agp_launch book="123"]
```

The responsive launch card includes a cover, title, date, countdown, preorder button and reader waitlist. The countdown updates with a small local script; without JavaScript it shows the launch date and approximate days remaining from page render time. At launch, a refreshed page switches to the normal Book Manager Buy Now URL. Open/cached pages ask the reader to reload; outdated preorder requests are rejected. UTC is explicit in the editor to avoid server/site timezone ambiguity. Launch date metadata is separate from the Phase 1 release date.

Waitlist signup requires a separate, purpose-specific checkbox authorizing updates about that book. A single-use confirmation email link expires after 24 hours. Unverified entries must not be used for campaigns. Launch Waitlist provides pagination, deletion/withdrawal and CSV export of verified entries only. Repeated requests replace the token and require fresh verification. Waitlist data is separate from chapter leads, so launch consent never silently authorizes general marketing. No launch broadcast scheduler or automated announcement emails are included; verified CSV lists can be used with your chosen compliant mailing service. Your site privacy policy must provide a withdrawal contact, and external list/CSV copies must be updated after withdrawal. Scanner-consumed links require a fresh request. Verification of issued waitlist links continues if the module is disabled.

### Reader reviews and testimonials

```text
[agp_review_form book="123"]
[agp_reviews book="123" layout="cards" limit="12"]
[agp_reviews book="123" layout="slider" stars="yes" limit="20"]
```

Only real reader submissions are stored. The plugin never seeds or generates endorsements. The form asks for a public display name, private email, review, optional 1–5 rating, and explicit permission to publish the name/text/rating. Email is for moderation, not mailing-list enrollment. Submissions start pending regardless of the logged-in author's role. Approve, unapprove, mark spam, trash or permanently delete in Reader Reviews. Display includes approved reviews only, and only for published, unprotected books. There is no purchaser-verification badge or authenticity guarantee. Native WordPress comment moderation/plugins may also act on reviews.

Reviews use native comments (`agp_review` type). The plugin integrates with WordPress personal-data export/erase, including review/rating/consent metadata. Its eraser permanently deletes matching plugin reviews (including public text) before native comment anonymization. Review deletion also removes their comment metadata. Plugin review metadata includes optional rating and publication consent/time. Do not put contact details in public review text. Anti-spam includes nonces, a honeypot, 5 submissions per connection IP/hour and 3 per email/hour, plus mandatory moderation. It cannot prevent all targeted/distributed abuse. Raw IP/user-agent fields are left empty by this submission handler; WordPress/server/third-party hooks may separately log requests.

The slider scrolls manually with Previous/Next buttons, touch or keyboard. There is no autoplay, so reading is not interrupted. Without JavaScript, the horizontally scrollable review list remains available and inactive buttons stay hidden. Reduced-motion preferences disable smooth movement. Star ratings have screen-reader text. Empty review lists produce no invented placeholders. Frontend controls are in English in this candidate and need a full localization pass before international release.

### Settings, branding, modules, and setup

AuthorGrowth → Settings configures the dashboard brand name, global promotion-card colors, module controls, plugin email sender, retention and non-secret diagnostics. Per-shortcode card colors override global defaults. Select colors with adequate contrast; the default uses navy/white/orange. Some theme/button styles may override plugin styling, so test on your target theme. Book editing always remains enabled. Disabling promotions, launches, reviews or leads hides their public shortcodes and rejects new submissions. Existing private chapter links and waitlist confirmation links remain valid until consumed/expired. Disabling analytics stops counting but keeps safe Buy Now redirects and historical reports. Module data/admin privacy operations are retained.

The three-step Setup Wizard guides brand/mail configuration, private storage/privacy checks and completion. It does not create private storage, configure SMTP, certify inbox delivery or change your privacy policy automatically. Finishing dismisses the welcome notice. Diagnostics show versions, table presence, HTTPS, privacy-page, fileinfo, cleanup schedule and private storage status without exposing credentials or filesystem paths. A present table is not proof of valid schema/concurrency or production readiness.

Sender settings apply only to plugin chapter/waitlist messages, not site-wide WordPress mail. Leave blank to retain normal WordPress/SMTP defaults. Use an address your SMTP provider authorizes and configure SPF/DKIM/DMARC as appropriate. `wp_mail()` acceptance is not a delivery guarantee. No SMTP password is stored in this plugin.

Daily cleanup defaults: unverified chapter/waitlist entries after 7 days; pending/spam reviews after 30 days (up to 100 per daily run); verified leads/waitlist entries and aggregate click totals retained indefinitely (0). Settings permit 1–3650 days for pending data and 0–3650 for verified leads/clicks. Positive retention deletes verified entries by verification time and aggregate clicks by UTC day. Approved reviews and private PDFs are retained until deliberately removed. WP-Cron must run; low-traffic sites should configure a real cron trigger. Cleanup/erasure cannot remove external CSV copies, email messages or backup copies.

### Install, upgrade, rollback and validation

Build with `./scripts/package.sh` and upload `dist/authorgrowth-pro-0.4.0-rc.1.zip` through Plugins → Add New → Upload Plugin. For upgrades from 0.1/0.2/0.3, back up the database and private PDFs, upload and replace the old plugin, then activate if inactive. Versioned initialization creates missing lead/click/waitlist tables without requiring reactivation; existing metadata/data is retained. Reviews use core comments tables. Missing-table failures are retried rather than marked installed. Check diagnostics and server logs after upgrade. Do not delete existing plugin data or expose PDFs to make an upgrade pass. Restore database/files from backup for full rollback; replacing plugin PHP alone does not undo schema/settings changes or retention deletions. Deactivation clears the daily cleanup event and retains data; subsequent use schedules it again. Uninstall retains data intentionally.

Run all automated suites with `./scripts/test.sh` (PHP 8.1+, Python 3 and Node required). Set `PHP_BIN` if PHP is not on PATH. These tests use WordPress/database doubles; simulated upgrade contracts do not prove a real `dbDelta` migration. `tests/render-fixture.php` generates a **static synthetic testing fixture only**, never live reviews, for browser layout checks. See RELEASE-CHECKLIST.md for WordPress/Gutenberg/Elementor, upgrades, mail, PDF, responsive/accessibility and security review. REVIEW.md records actual results and unverified tests. Only publish a production release after the real-site gates pass.


## Admin navigation hotfix — 0.4.0-rc.2

This patch fixes all six protected submenu routes without changing slugs, capabilities, data, schemas or features. The top-level menu now registers at `admin_menu` priority 5, before module submenus at priority 10. See ADMIN-FIX.md for the reproduced root cause and tests.

Back up the database and private chapter files, then upload `dist/authorgrowth-pro-0.4.0-rc.2.zip` through Plugins → Add New → Upload Plugin and replace the installed AuthorGrowth Pro plugin. Confirm version 0.4.0-rc.2 in Plugins, then reload the admin page while signed in as a user with `manage_options`. Reactivation, data deletion, role changes and permalink flushing are unnecessary. Existing books/leads/reviews/waitlists/clicks/settings/private PDFs remain unchanged. Purge PHP OPcache through your host only if it continues executing the old version after replacement; do not disable capability checks.

Verify Dashboard and Books, then Reader Leads, Click Analytics, Launch Waitlist, Reader Reviews, Settings and Setup Wizard. Each custom sidebar link must use `admin.php?page=...`. Users without `manage_options` should still be denied the protected pages. If a problem persists, record the full URL, installed version and user's actual capabilities; on a backed-up staging clone test menu/security plugins individually and inspect host redirects/server logs. This patch reproduces/fixes an intrinsic registration-order defect but cannot rule out additional site-specific plugins or rewriting.

For the core-routing regression tests, set `WP_CORE_DIR` to a trusted WordPress source tree containing `wp-includes/plugin.php`, `wp-includes/class-wp-hook.php`, `wp-admin/includes/plugin.php` and `wp-admin/menu-header.php`, then run `WP_CORE_DIR=/path/to/wordpress ./scripts/test.sh`. The test uses unmodified WordPress menu/hook/sidebar code with a simulated capability resolver and no database. It checks the old failure and fixed behavior for every submenu. Without that source tree the core-dependent tests explicitly report SKIP; other suites still run. Full installed-site testing remains required.
