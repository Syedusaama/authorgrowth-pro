# Admin navigation hotfix — 0.4.0-rc.2

Base: `development/phase-4` (`618956f`). Fix branch: `development/admin-fix`.

## Confirmed root cause

In rc.1, settings, launches, reviews, analytics and leads registered their `admin_menu` callbacks during bootstrap before `AGP_Dashboard::hooks()`. Every callback used the default priority 10. WordPress executes same-priority callbacks in insertion order, so all six module submenus were registered before `add_menu_page()` established their parent.

WordPress derives submenu hook names from the parent's `$admin_page_hooks` entry. Before the parent exists, `agp-analytics` registers as `admin_page_agp-analytics`. Once the parent is registered, routing looks for `authorgrowth_page_agp-analytics`. That hook/registered-page mismatch explains both reported symptoms:

1. WordPress's sidebar renderer cannot resolve the expected callback and falls back to the literal `agp-analytics` href, resolving to `/wp-admin/agp-analytics` rather than `admin.php?page=agp-analytics`.
2. A direct `admin.php?page=agp-analytics` request reaches `user_can_access_admin_page()`, which cannot find the expected registered hook and denies access, even for an administrator.

The same sequence affects Reader Leads, Launch Waitlist, Reader Reviews, Settings and Setup Wizard. The dashboard registers its own parent before its own dashboard submenu, so it works. The native Book Manager uses `edit.php?post_type=agp_book`, which does not depend on a custom page callback.

## Minimal change

`AGP_Dashboard::hooks()` now registers the parent/dashboard menu callback at priority **5**, before module submenus at priority **10**. The modules remain registered unconditionally during plugin bootstrap, before `admin_menu`; module enable/disable settings do not unregister their administration pages.

No page slug, parent slug, callback, capability, database schema, settings, data, public feature or UI design was changed. No rewrite/redirect workaround or capability bypass was added. The version and packaged artifact are now 0.4.0-rc.2.

| Page | Parent | Capability | Callback |
| --- | --- | --- | --- |
| authorgrowth-pro | Top level / own dashboard submenu | edit_posts | AGP_Dashboard::render |
| edit.php?post_type=agp_book | authorgrowth-pro (native post type) | Native book edit capability (edit_posts) | Native WordPress editor |
| agp-leads | authorgrowth-pro | manage_options | AGP_Leads::admin |
| agp-analytics | authorgrowth-pro | manage_options | AGP_Clicks::admin |
| agp-waitlist | authorgrowth-pro | manage_options | AGP_Launches::admin |
| agp-reviews | authorgrowth-pro | manage_options | AGP_Reviews::admin |
| agp-settings | authorgrowth-pro | manage_options | AGP_Settings::admin |
| agp-setup | authorgrowth-pro | manage_options | AGP_Settings::wizard |

## Audit and verification

Inspected every `add_menu_page`/`add_submenu_page` registration, callback, capability, bootstrap/hook order, generated `admin_url()` link, form action and admin redirect. Plugin-generated custom-page links already use canonical `admin.php?page=...`; the malformed link came from WordPress's fallback sidebar rendering. Forms still submit to `admin-post.php`, and post-action redirects use canonical page URLs. No module code removes/moves menus, rewrites admin paths or registers pages late/behind module flags. The plugin's only rewrite flushing remains the existing activation/deactivation behavior for books.

The new regression test loads **unmodified WordPress 6.5** hook, menu-registration, permission-routing and sidebar-rendering code from the official WordPress/WordPress GitHub tag. It uses capability/URL/presentation doubles and no database. It does not emulate the critical menu functions:

- `tests/admin-menu-test.php --legacy`: 26 assertions pass while deliberately restoring the rc.1 ordering; reproduces all six bare sidebar URLs and corresponding direct-URL denials.
- Fixed ordering: 173 assertions pass across administrator, editor, author, subscriber and disabled-modules administrator contexts. Checks every custom page's parent/hook, exact callback, registered route, unchanged capability, canonical sidebar URL and denial/hiding for unauthorized users. Native Books links remain intact.
- Existing suites: all 179 PHP assertions, 11 download/storage subprocess scenarios and 7 JS assertions pass.
- Syntax: all 18 plugin/test PHP files and frontend JS pass. ZIP integrity, source/version/content inspection and `git diff --check` pass.

To reproduce, set `WP_CORE_DIR` to a trusted WordPress source tree and run `WP_CORE_DIR=/path/to/wordpress ./scripts/test.sh` (PHP 8.1+, Python 3, Node). The four core files required are listed in the test header. In this environment they were downloaded with verified HTTPS from `https://raw.githubusercontent.com/WordPress/WordPress/6.5/` into `/tmp/agp-wp-core`. They are not bundled into the plugin/repository. If no source tree is provided, the test runner explicitly reports this core-dependent suite as SKIP.

## Site conflicts and unverified checks

The original failure reproduces with no other plugins and no web server/rewriting, establishing an intrinsic registration defect rather than requiring an external conflict to explain it. There is no access to the affected installed site, its other plugins, roles/capability filters, OPcache, proxy rules or server logs. Additional deployment-specific interference cannot be ruled out. The capability resolver is a test double, not a logged-in WordPress user database. Real installed-site/browser/HTTP acceptance remains unverified, as do the pre-existing RC migration/mail/editor integration gates in REVIEW.md.

## Upgrade and acceptance

1. Back up database and private chapter files. Upload `dist/authorgrowth-pro-0.4.0-rc.2.zip` via Plugins → Add New → Upload Plugin and replace the installed plugin. Confirm version **0.4.0-rc.2**.
2. Reload wp-admin as an administrator with `manage_options`. No reactivation, permalink reset, role modification, schema migration or data deletion is required for this hotfix.
3. Verify Dashboard, Books and all six protected submenus. Sidebar links must use `admin.php?page=<slug>` for custom pages; test the direct URLs too. Verify author/editor access remains limited to Dashboard/Books and subscribers remain denied.
4. If the old behavior persists, confirm deployed files/version and OPcache freshness. On a backed-up staging clone, isolate menu/security plugins and inspect redirect/server logs. Do not grant broad capabilities or bypass WordPress access checks to mask the problem.

The updated ZIP and SHA-256 file are generated locally under dist. This is a targeted review fix, not a new feature release or production certification.
