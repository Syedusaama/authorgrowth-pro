# AuthorGrowth Pro 0.4.0-rc.1 — review candidate

Branch: `development/phase-4`, based on Phase 3 commit `6cf1349`. This is not a production-readiness certification.

## Delivered

- Phase 1 Book Manager/dashboard/native editor, Phase 2 private chapter/lead flows, and Phase 3 promotion/aggregate analytics preserved.
- Book launch UTC date/time, separate validated preorder destination/nonce purpose, coming-soon card, progressive countdown, post-launch Buy Now switch, purpose-specific verified waitlist with admin list/delete/CSV and privacy integration.
- Reader review form, optional rating and publication consent; pending-by-default native comment storage; admin approval/spam/trash/delete; approved-only cards and manual accessible slider. No live seed or generated reviews.
- Global branding/colors, public module controls, plugin-only email sender configuration, configurable retention, diagnostics and three-step setup guidance.
- Review metadata privacy export and review erasure before native comment anonymization; configurable daily cleanup; safe typed preorder redirects and sender-header validation.
- Audit fix: chapter mail-failure invalidation only revokes the token issued by that failed request, avoiding invalidation of a newer concurrent token. Missing-table failures do not set successful schema markers.
- Installable RC ZIP, bundled readme/license, automated test/package scripts, installation/upgrade documentation, SECURITY.md and RELEASE-CHECKLIST.md.

## Executed validation

All available automated suites were run on PHP 8.4.24 with local WordPress/database/mail doubles:

| Check | Result |
| --- | --- |
| PHP syntax checks | All 17 plugin/test PHP files passed |
| Phase 1 book suite | 13 assertions passed |
| Phase 2 lead suite | 30 assertions passed |
| Phase 3 promotion/analytics suite | 34 assertions passed |
| Phase 4 settings/launch/review/admin/privacy suite | 71 assertions passed |
| Bootstrap/schema upgrade contracts (fresh and Phases 1–3) | 31 assertions passed |
| Download streaming/rejection subprocess scenarios | 6 passed |
| Private/public/missing/symlink storage boundary scenarios | 5 passed |
| Countdown/slider JavaScript doubles | 7 assertions passed |
| JavaScript syntax | Passed |
| ZIP creation and integrity | Passed |
| ZIP source/header/version/content inspection and SHA-256 | Passed |
| git diff --check | Passed |

The Phase 4 suite exercises new/public forms and display callbacks, dashboard/metaboxes/admin screens and all wizard steps, capability and nonce denials, default modules, module disabling, invalid dates/ratings/consent, escaping, waitlist capture/mail failure/hash/confirmation, preorder destination gates, pending reviews/moderation, mail sender header safety, privacy and retention query contracts. Earlier suites cover the existing shortcode layouts, book saves, chapter capture and PDF streaming. Bootstrap contracts simulate old version markers and table existence; they do not execute real database migrations.

The reproducible test command is `PHP_BIN=/path/to/php ./scripts/test.sh`. This instance used `/tmp/agp-php/root/usr/bin/php8.4` and its extracted runtime libraries. No system dependencies, environment draft or publication were changed.

## Unverified / blocked tests

No live WordPress/database/SMTP installation was available. Real `dbDelta` upgrades and data preservation from 0.1/0.2/0.3, SQL concurrency, WordPress permissions/REST/nonces/privacy-hook ordering, real PDF upload/MIME/file permissions/HTTP exposure, mail inbox delivery/provider sender behavior, HTTP response headers/caching, WP-Cron, and Gutenberg/Elementor/theme integration are unverified. PHP 8.1 minimum and WordPress 6.5 minimum compatibility need real matrix testing.

A static Chromium layout check was attempted. Browser startup failed in the command sandbox; a supported elevated local retry stalled and exited on its 20-second timeout without DOM results. The assertions on missing DOM output did not pass. This was a browser execution failure, not evidence of a layout defect or success. Responsive visual, screen-reader, keyboard, contrast and sticky-container checks therefore remain unverified. The JS tests validate only behavior through browser API doubles. The static fixture is retained for future local browser review and contains clearly labeled synthetic test text only.

## Known limitations and acceptance gates

- No automated launch broadcasts, mailing-service integration, purchase verification, email deliverability guarantee, CAPTCHA, malware scanning or distributed-abuse guarantee.
- Preorders use one separate URL per book; normal promotions use one saved Buy Now URL. Analytics reports aggregate approximate clicks, never sales or unique visitors. Counts do not distinguish preorder from normal clicks to the same host.
- Default verified-lead/waitlist/click retention is indefinite; pending cleanup depends on WP-Cron. At most 100 pending/spam reviews are purged per daily run. Approved reviews and private PDFs require deliberate removal. Deactivation/uninstall preserves data. External CSV/mailing lists/messages/backups require separate handling.
- Disabling a module retains stored data and admin/privacy tools; issued chapter/waitlist links may still complete. Nonce-cached forms and pages open across a launch deadline may require refresh. Mail scanners can consume bearer links.
- Review submissions are moderated but do not verify email ownership or book purchases; identity claims require human moderation. Native comment tools/plugins are trusted and can also alter reviews. The plugin eraser deletes matching plugin reviews; test ordering with other privacy plugins before relying on it.
- Brand name applies to the workspace title; colors apply to frontend promotions/reviews/launches. Theme styles, custom color contrast and container overflow affect the final appearance. Sticky bars are in-flow and container-bounded. Slider is manual, not autoplay. Some new UI strings are English-only; full localization is outstanding.
- Diagnostics report table presence and configuration signals, not schema correctness, external-file privacy, delivery or production health. Wizard completion is guidance only.

Complete every real-site gate in RELEASE-CHECKLIST.md on staging, using owned test addresses and backups, before promoting this RC to a production release. README.md includes current installation, upgrade, rollback and all shortcode usage instructions.
