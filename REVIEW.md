# Phase 2 review

Branch: `development/phase-2`, based on Phase 1 commit `357058e`.

## Delivered

- Existing dashboard, book editing, REST metadata, publishing, covers and Buy Now links retained.
- Chapter capture shortcode for published, non-password-protected books; optional unchecked marketing consent with recorded wording.
- Admin-only private PDF upload, reader list with pagination/deletion, subscriber CSV export, and dashboard request/verification/subscriber counts.
- Emailed random 256-bit links, hashed at rest, expiring in 30 minutes, with an atomic conditional update for single-use delivery.
- Mandatory private storage outside known public roots, strict generated filenames, canonical path containment, MIME/signature checks, no public attachment URL.
- Nonce/honeypot validation, per-IP/email atomic hourly counters, CSV formula protection, WordPress privacy export/erase hooks, and daily unverified-lead cleanup.
- Documentation for setup, mail, consent, privacy/retention, caching, storage, and limitations.

## Executed validation

- PHP 8.4.24 syntax checks on all plugin/test PHP files: passed.
- Existing Phase 1 suite: 13 assertions passed.
- Phase 2 suite: 30 assertions passed (capture rejection/success paths, optional/explicit consent, token hashing/expiry, mail failure, rate rejection/fail-closed behavior, CSV protection and private-path containment).
- Download handler subprocess suite: 6 scenarios passed (PDF bytes streamed; expired/replayed/racing requests, draft book, and missing PDF rejected). Database/API doubles simulate state; this does not prove real SQL concurrency.
- Updated ZIP build and `unzip -t`: passed.
- `git diff --check`: passed.

PHP was provided by the existing locally extracted Debian CLI under `/tmp/agp-php`; no system installation changes or environment draft updates were made.

## Unverified tests / release gates

No live WordPress, database server, configured private hosting directory, or SMTP transport was available. Consequently these remain unverified: activation/upgrade dbDelta and actual SQL behavior/concurrency; admin UI, uploads/fileinfo, HTTP headers and cache behavior; WP-Cron retention and WordPress privacy tools; real inbox delivery; role/nonce/REST integration; Gutenberg/Elementor and theme rendering; minimum PHP 8.1/WordPress 6.5 compatibility. The automated suites use doubles and are not WordPress integration tests.

Before production, configure private storage and SMTP on staging. Verify upload/capture/email/download end to end; replay and expire real links; issue concurrent download requests and confirm only one succeeds. Check optional consent and CSV exclusion of unverified/non-consenting rows; test unauthorized admin actions and invalid nonces. Exercise privacy export/erase, scheduled cleanup, pagination and CSV batches over 500 rows, book unpublishing and password protection, PDF replacement, and all Phase 1 editing/cover/link operations. Confirm private files are inaccessible over HTTP, including server aliases.

Email scanners can consume one-use links. Forwarded links act as bearer credentials until used/expired. IP rate limits may apply to a shared proxy; they are not a complete bot defense. PDFs are checked for type, not malware. Site owners must configure HTTPS, privacy policy, retention, and manual withdrawal contact; external mailing lists/CSV copies are not managed by the plugin. Phases 3–4 remain pending.
