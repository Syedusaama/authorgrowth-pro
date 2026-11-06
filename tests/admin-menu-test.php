<?php
/** Routing regression using unmodified WordPress core menu/hook functions.
 * WP_CORE_DIR must contain wp-includes/{plugin.php,class-wp-hook.php},
 * wp-admin/includes/plugin.php and wp-admin/menu-header.php.
 * Capabilities, URLs and presentation helpers are doubles; no database/live site.
 */
$core = getenv( 'WP_CORE_DIR' );
if ( ! $core || ! is_file( $core . '/wp-admin/menu-header.php' ) ) { fwrite( STDERR, "Set WP_CORE_DIR to a WordPress source tree.\n" ); exit( 1 ); }
define( 'ABSPATH', rtrim( $core, '/' ) . '/' );
define( 'WP_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'WPMU_PLUGIN_DIR', '/nonexistent/agp-test-mu-plugins' );
$wp_plugin_paths = array();
function wp_normalize_path( $path ) { return str_replace( '\\', '/', $path ); }
function sanitize_title( $s ) { return strtolower( str_replace( ' ', '-', $s ) ); }
function current_user_can( $cap, ...$args ) { return ! empty( $GLOBALS['caps'][ $cap ] ); }
function __( $s, $domain = '' ) { return $s; }
function _e( $s ) { echo $s; }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_attr__( $s ) { return esc_attr( $s ); }
function esc_attr_e( $s ) { echo esc_attr( $s ); }
function esc_url( $s ) { return esc_attr( $s ); }
function set_url_scheme( $s ) { return $s; }
function wptexturize( $s ) { return $s; }
function sanitize_html_class( $s ) { return $s; }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function admin_url( $path = '' ) { return 'https://example.org/wp-admin/' . $path; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function add_shortcode( $tag, $cb ) {}
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-admin/includes/plugin.php';
require dirname( __DIR__ ) . '/authorgrowth-pro/authorgrowth-pro.php';
// WordPress runs these relative file checks from wp-admin, not the plugin checkout.
chdir( ABSPATH . 'wp-admin' );
$legacy = in_array( '--legacy', $argv, true );
if ( $legacy ) {
 // Reproduce rc.1 without editing production files: move parent back to priority 10,
 // where bootstrap inserts it after every module's submenu callback.
 remove_action( 'admin_menu', array( 'AGP_Dashboard', 'menu' ), 5 );
 add_action( 'admin_menu', array( 'AGP_Dashboard', 'menu' ), 10 );
}
$pages = array(
 'authorgrowth-pro' => array( 'edit_posts', array( 'AGP_Dashboard', 'render' ) ),
 'agp-leads' => array( 'manage_options', array( 'AGP_Leads', 'admin' ) ),
 'agp-analytics' => array( 'manage_options', array( 'AGP_Clicks', 'admin' ) ),
 'agp-waitlist' => array( 'manage_options', array( 'AGP_Launches', 'admin' ) ),
 'agp-reviews' => array( 'manage_options', array( 'AGP_Reviews', 'admin' ) ),
 'agp-settings' => array( 'manage_options', array( 'AGP_Settings', 'admin' ) ),
 'agp-setup' => array( 'manage_options', array( 'AGP_Settings', 'wizard' ) ),
);
$n = 0;
function check_menu( $ok, $message ) { global $n; ++$n; if ( ! $ok ) { throw new Exception( 'FAIL: ' . $message ); } }
$roles = $legacy ? array( 'administrator' ) : array( 'administrator', 'editor', 'author', 'subscriber', 'disabled-modules-admin' );
foreach ( $roles as $role ) {
 $caps = array( 'read' => true, 'edit_posts' => in_array( $role, array( 'administrator', 'editor', 'author', 'disabled-modules-admin' ), true ), 'manage_options' => in_array( $role, array( 'administrator', 'disabled-modules-admin' ), true ) );
 $options = 'disabled-modules-admin' === $role ? array( 'agp_settings' => array_fill_keys( AGP_Settings::MODULES, 0 ) ) : array();
 // Isolate WordPress's per-request menu and callback registries between users.
 foreach ( $pages as $slug => $data ) { foreach ( array( 'admin', 'authorgrowth', 'toplevel' ) as $prefix ) { remove_all_actions( $prefix . '_page_' . $slug ); } }
 $menu = $submenu = $admin_page_hooks = $_registered_pages = $_parent_pages = $_wp_real_parent_file = $_wp_menu_nopriv = $_wp_submenu_nopriv = array();
 $pagenow = 'admin.php'; $typenow = ''; $parent_file = ''; $submenu_file = null; $plugin_page = null; $self = 'admin.php'; $_SERVER['PHP_SELF'] = '/wp-admin/admin.php';
 // Core adds native post-type submenus separately. Their edit.php links need no callback.
 if ( current_user_can( 'edit_posts' ) ) { add_submenu_page( 'authorgrowth-pro', 'Books', 'Books', 'edit_posts', 'edit.php?post_type=agp_book' ); }
 do_action( 'admin_menu' );
 ob_start();
 if ( ! function_exists( '_wp_menu_output' ) ) { require ABSPATH . 'wp-admin/menu-header.php'; } else { _wp_menu_output( $menu, $submenu ); }
 $html = ob_get_clean();
 foreach ( $pages as $slug => list( $cap, $callback ) ) {
  $plugin_page = $slug; $parent_file = ''; $submenu_file = null;
  $hook = get_plugin_page_hookname( $slug, 'authorgrowth-pro' );
  $allowed = current_user_can( $cap );
  if ( $legacy && $slug !== 'authorgrowth-pro' ) {
   check_menu( isset( $_registered_pages[ 'admin_page_' . $slug ] ), 'legacy registers old hook: ' . $slug );
   check_menu( ! user_can_access_admin_page(), 'legacy reproduces direct URL denial: ' . $slug );
   check_menu( str_contains( $html, "href='$slug'" ), 'legacy reproduces bare submenu URL: ' . $slug );
   continue;
  }
  check_menu( user_can_access_admin_page() === $allowed, "$role access: $slug" );
  if ( $allowed ) {
   check_menu( get_plugin_page_hook( $slug, 'authorgrowth-pro' ) === $hook, "$role resolves callback: $slug" );
   check_menu( false !== has_action( $hook, $callback ) && is_callable( $callback ), "$role expected callable: $slug" );
   check_menu( isset( $_registered_pages[ $hook ] ), "$role registered page: $slug" );
   check_menu( str_contains( $html, "href='admin.php?page=$slug'" ), "$role canonical sidebar URL: $slug" );
   check_menu( ! str_contains( $html, "href='$slug'" ), "$role no bare URL: $slug" );
   foreach ( $submenu['authorgrowth-pro'] as $item ) { if ( $item[2] === $slug ) { check_menu( $item[1] === $cap, "$role capability unchanged: $slug" ); } }
  } else {
   check_menu( false === has_action( $hook, $callback ), "$role no protected callback: $slug" );
   check_menu( ! str_contains( $html, "href='admin.php?page=$slug'" ), "$role protected menu hidden: $slug" );
  }
 }
 if ( current_user_can( 'edit_posts' ) ) { check_menu( str_contains( $html, "href='edit.php?post_type=agp_book'" ), "$role native Books URL preserved" ); }
}
echo 'PASS: ' . $n . ( $legacy ? ' legacy reproduction' : ' WordPress-core admin menu' ) . " assertions\n";
