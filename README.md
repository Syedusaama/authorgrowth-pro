# AuthorGrowth Pro

Premium author marketing plugin, under active development. **Version 0.1.0 implements Phase 1 only.** GPL-2.0-or-later.

## Install

Requires WordPress 6.5+, PHP 8.1+. Back up your site and test on staging first.
Run `./scripts/package.sh`, then upload `dist/authorgrowth-pro-0.1.0.zip` using Plugins → Add New → Upload Plugin and activate. No Composer, Node, API keys, or paid dependencies are required.

## Phase 1 workflow

Open AuthorGrowth → Dashboard. Choose Add a book. Add a title, description, excerpt, author name, optional ISBN, release date, and absolute HTTP/HTTPS retailer URL. Use WordPress Featured image for the cover (requires theme featured-image support). Publish or schedule using the native editor. Books are available at `/books/`; individual book pages append a cover, author, and Buy Now button. Save Settings → Permalinks if a theme/server still returns 404 after activation. Theme templates control the archive and final presentation.

Gutenberg uses the native block editor and registered REST metadata. Elementor can enable the Books post type in its settings; no Elementor-specific widgets ship in Phase 1. Browser/editor integration must be tested on the target site before release.

Books follow native post permissions: contributors can draft, authors publish their own, and editors/admins manage others. This release uses the shared WordPress post capabilities rather than a separate books role. Media uploads require WordPress upload permissions. Drafts and private books retain WordPress visibility protections. Meta saves require a nonce and edit permission; REST writes use WordPress authentication and per-book permission checks. URLs permit HTTP/HTTPS and reject unsafe destinations; no remote URL is fetched by this plugin.

No leads, cookies, tracking, analytics, or external requests are implemented in Phase 1. Retailer links navigate readers to third-party sites. Deactivation retains books and metadata; deletion also retains data intentionally. Remove books through WordPress if permanent deletion is desired.

## Development and verification

Use the existing checkout; no worktree is required. Run `find authorgrowth-pro tests -name '*.php' -exec php -l {} \;` and `php tests/books-test.php`. The lightweight test suite checks validation and save guards with WordPress API doubles; it does not replace real WordPress testing. Build the installable ZIP with `./scripts/package.sh`.

Before a production release, verify activation/deactivation, dashboard permissions/counts, book CRUD, cover upload, publishing/scheduling, nonce rejection, REST authorization, front-end escaping, Gutenberg and Elementor editing on a real WordPress site. See REVIEW.md for actual results and limitations.

## Roadmap

1. Foundation, dashboard, Book Manager (this release).
2. Reader leads and free chapter PDF downloads with consent/privacy controls.
3. Promotion widgets/shortcodes and retailer click analytics.
4. Launch countdown/waitlist, moderated reviews, branding/settings, expanded integration testing.

Future phases are not implemented and no synthetic lead or analytics metrics appear in the dashboard.
