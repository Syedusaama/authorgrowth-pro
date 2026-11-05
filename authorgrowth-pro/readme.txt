=== AuthorGrowth Pro ===
Contributors: authorgrowthpro
Tags: books, authors, marketing, reviews
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.4.0-rc.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Author marketing workspace with books, private chapter delivery, consent-aware leads, promotion cards, click reports, launches and moderated reviews.

== Description ==
RELEASE CANDIDATE: not production-certified. Minimum-version and real WordPress/editor/database integration tests remain outstanding; See repository REVIEW.md and RELEASE-CHECKLIST.md for evidence.

No paid dependencies. Native WordPress book editing and comments moderation. Gutenberg and Elementor use Shortcode blocks/widgets; no native editor-specific block/widget is bundled.

== Installation ==
1. Back up the database and private PDF files. Test on staging.
2. Upload the ZIP in Plugins > Add New > Upload Plugin; activate.
3. Use AuthorGrowth > Setup Wizard and Settings. Configure SMTP separately and test inbox delivery.
4. For chapter PDFs, define AGP_PRIVATE_DIR in wp-config.php as an existing writable directory outside ALL web-served roots. Verify host aliases/CDN do not expose it. Do not use Media Library uploads for private files.
5. Add a book, cover and retailer URL. Upload chapter PDFs from Reader Leads. Add launch UTC date/preorder URL in the Book editor.
6. Publish a privacy policy with retention and withdrawal contact. Set appropriate retention before collecting real readers.

== Shortcodes ==
[agp_book id="123"]
[agp_books layout="grid" columns="3"]
[agp_floating_cta book="123"]
[agp_chapter book="123"]
[agp_launch book="123"]
[agp_review_form book="123"]
[agp_reviews book="123" layout="slider"]

Only published, non-password-protected books are displayed. Reviews require approval. No fake reviews are supplied. Click counts are not sales. Waitlists require confirmation and do not send automated broadcasts.

== Upgrade Notice ==
Back up before replacing earlier versions. Versioned initialization creates missing tables, preserving prior books/leads/clicks. Reviews use core comments tables. Deactivation/uninstall retains data; configurable retention permanently deletes eligible records when cron runs. Restore database and private files for complete rollback.

== Privacy ==
No analytics cookies or visitor click log. Aggregate counts are stored by UTC date, book and retailer host. Short-lived rate counters use keyed hashes of connection IP/email; server/retailer logs are outside plugin control. Leads/waitlists store email, purpose/consent text, request/verification times and hashed expiring tokens. Reviews store public name/text/optional rating, private email and publication consent. WordPress privacy export/erase is integrated. External mailing lists/CSV copies/backups require separate management. Private PDFs and approved reviews are not retention-deleted automatically.

== Changelog ==
= 0.4.0-rc.1 =
Launch countdown/preorders/verified waitlists, moderated reader reviews/manual sliders, global settings/modules/sender/retention, diagnostics, setup wizard and release checks. Real-site validation required.

= 0.3.0 =
Promotion shortcodes and aggregate retailer click reports.

= 0.2.0 =
Reader leads, private chapter delivery and privacy integration.

= 0.1.0 =
Foundation, dashboard and Book Manager.

== Documentation ==
Complete install/upgrade, privacy, limitations and staging instructions:
https://github.com/Syedusaama/authorgrowth-pro/tree/development/phase-4
