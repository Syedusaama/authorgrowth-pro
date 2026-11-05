# AuthorGrowth Pro

Premium author marketing plugin, under active development. **Version 0.2.0 implements Phases 1–2.** GPL-2.0-or-later.

## Install

Requires WordPress 6.5+, PHP 8.1+. Back up your site and test on staging first.
Run `./scripts/package.sh`, then upload `dist/authorgrowth-pro-0.2.0.zip` using Plugins → Add New → Upload Plugin and activate. No Composer, Node, API keys, or paid dependencies are required.

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
3. Promotion widgets/shortcodes and retailer click analytics.
4. Launch countdown/waitlist, moderated reviews, branding/settings, expanded integration testing.

Future phases are not implemented and no synthetic lead or analytics metrics appear in the dashboard.

## Phase 2 — reader leads and private chapters (0.2.0)

Phase 2 adds `[agp_chapter book="123"]` forms, emailed single-use chapter links, admin-only Reader Leads with pagination/deletion, subscriber CSV export, and dashboard counts. Use the shortcode in Gutenberg's Shortcode block or Elementor's Shortcode widget. Phase 1 book editing and front-end details remain intact. Phases 3–4 remain pending.

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
