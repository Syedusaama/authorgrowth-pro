# Release candidate staging gates

All unchecked gates below remain required before a production-readiness claim. Record versions, commands/results, screenshots where useful, errors and fixes. Use a disposable staging site and test addresses owned by the reviewer. Never import synthetic endorsements into a real author site.

## Environment matrix and installation

- [ ] Fresh install on WordPress 6.5/PHP 8.1 and a current supported WordPress/PHP combination; use both MySQL and MariaDB when supported.
- [ ] Activate ZIP; no warnings/notices with WP_DEBUG. Verify all three plugin tables, indexes, character sets and all native book post/meta registration.
- [ ] Upgrade copies of 0.1.0, 0.2.0 and 0.3.0 with existing books, covers, chapter files, leads, consent and click totals. Compare before/after records; settings defaults must preserve workflows. Repeat initialization and deactivate/reactivate to confirm idempotence.
- [ ] Simulate denied schema permissions; diagnostics must reveal missing tables, no silent successful-install marker. Restore access and retry.
- [ ] Test database/filesystem backup restoration and rollback. Verify no data deletion on disable/deactivate/uninstall.

## Every shortcode and screen

- [ ] Admin Dashboard, native Books list/edit, Reader Leads, Click Analytics, Launch Waitlist, Reader Reviews, Settings/Diagnostics and all three wizard steps. Verify subscriber/anonymous denial and author/editor/admin behavior, including native comment moderation access.
- [ ] `[agp_book id="123"]`, `[agp_books layout="grid" columns="3"]`, list/ids/limit options, `[agp_floating_cta book="123"]`, `[agp_chapter book="123"]`, `[agp_launch book="123"]`, `[agp_review_form book="123"]`, and `[agp_reviews book="123"]` cards/slider/stars on/off.
- [ ] Missing/invalid IDs, empty collections, drafts/private/password-protected books, missing covers/URLs, long Unicode text and malicious HTML/attributes; nothing private or executable should render.
- [ ] Disable each module: public forms/widgets stop, submissions reject, existing chapter/confirmation links still work, analytics redirects work without counts, admin data/privacy remain accessible.
- [ ] Gutenberg Shortcode blocks and native book editing; Elementor Shortcode widgets and opt-in Books post type; theme templates, excerpts, single pages and archives. No native custom Gutenberg block or Elementor widget is claimed.

## Functional, security and privacy

- [ ] Book CRUD/permissions, cover upload, scheduling, normal Buy Now links and archive rewrites retained.
- [ ] Chapter upload: trusted valid PDF, oversize, fake extension, wrong MIME, invalid signature, malformed upload, unavailable fileinfo, unwritable/missing/public/escaping-symlink storage. Verify private files cannot be retrieved through HTTP, server aliases, CDN or backups.
- [ ] Actual SMTP chapter/waitlist delivery, sender headers, mail failure and simultaneous requests. Verify header injection attempts fail; no email addresses leak in responses/redirects/logs created by plugin.
- [ ] Token expiry, tampering, replay and concurrent requests (only one download/confirmation succeeds), unpublished/password-protected book and file replacement. Confirm exact PDF bytes, Content-Length, attachment/nosniff/no-cache/referrer headers.
- [ ] Launch time before/at/after deadline, UTC vs site timezone, no-JS fallback, cached page behavior, preorder destination validation and nonce-purpose separation, waitlist verification/CSV/delete/withdrawal. No email broadcasts occur automatically.
- [ ] Reviews: optional ratings, invalid data, publication consent, pending-by-default even for administrators, manual approval/spam/trash/delete, approved-only display, no public emails, no seeded testimonials.
- [ ] Invalid/missing nonces, forged capabilities, arrays instead of scalar fields, unrelated-comment moderation, SQL injection and arbitrary redirect attempts. Inspect server-side SQL prepared arguments.
- [ ] Bot/honeypot/rate thresholds, shared-IP behavior, GET vs POST analytics, DNT/GPC, visitor URL parameters, 303 destinations and concurrent increments. Metrics must remain clicks, not sales/unique readers.
- [ ] 7/30/90-day reports, UTC and ISO-week/year/month boundaries, partial periods, book/retailer historical counts, deleted-book labels and pagination/CSV batches >500 rows.
- [ ] Personal-data export/erase for leads, waitlists and reviews; verify reviews and metadata are deleted before native comment anonymization. Withdrawal must also remove external mailing-list/CSV copies as required.
- [ ] Invoke cleanup with WP-Cron on/off: configured pending/verified/click retention, <=100 old pending/spam reviews per run, retained approved reviews/PDFs and rate-counter cleanup. Test retained-data defaults and explicit deletion after lowering retention.

## Responsive/accessibility and release

- [ ] 320/390/768/1280px, zoom 200–400%, long text and 1–4 columns; no horizontal page overflow. Sticky bar behavior within theme containers and collision with cookie notices.
- [ ] Keyboard navigation, visible focus, form labels/errors, review slider controls/touch/keyboard, screen-reader stars/timer, reduced motion and no-JS fallbacks. Validate contrast after branding changes.
- [ ] Run scripts/test.sh and scripts/package.sh; inspect ZIP contents; compare source version to artifact and SHA-256. No secrets, test fixtures, personal data or development caches in ZIP.
- [ ] Review SECURITY.md, README, known limitations and licensing/localization. Obtain real-site acceptance before promoting the candidate to a final release.
