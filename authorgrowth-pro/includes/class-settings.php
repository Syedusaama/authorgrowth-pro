<?php
/** Validated site-wide configuration, guided setup, and safe diagnostics. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class AGP_Settings {
 const MODULES = array( 'leads', 'promotions', 'analytics', 'launches', 'reviews' );
 public static function defaults() { return array( 'brand' => 'AuthorGrowth Pro', 'accent' => '#F97316', 'background' => '#ffffff', 'text' => '#17233b', 'sender_name' => '', 'sender_email' => '', 'pending_days' => 7, 'lead_days' => 0, 'click_days' => 0, 'review_days' => 30, 'setup_done' => 0, 'leads' => 1, 'promotions' => 1, 'analytics' => 1, 'launches' => 1, 'reviews' => 1 ); }
 public static function get() { $saved = get_option( 'agp_settings', array() ); return self::sanitize( array_merge( self::defaults(), is_array( $saved ) ? $saved : array() ) ); }
 public static function enabled( $module ) { $s = self::get(); return ! empty( $s[ $module ] ); }
 public static function sanitize( $input ) {
  $input = is_array( $input ) ? $input : array(); $out = self::defaults();
  foreach ( array( 'brand', 'sender_name' ) as $key ) { $out[ $key ] = isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? substr( sanitize_text_field( $input[ $key ] ), 0, 100 ) : $out[ $key ]; }
  foreach ( array( 'accent', 'background', 'text' ) as $key ) { if ( isset( $input[ $key ] ) && is_string( $input[ $key ] ) && sanitize_hex_color( $input[ $key ] ) ) { $out[ $key ] = $input[ $key ]; } }
  $email = isset( $input['sender_email'] ) && is_string( $input['sender_email'] ) ? trim( $input['sender_email'] ) : '';
  $out['sender_email'] = ! preg_match( '/[\r\n]/', $email ) && is_email( $email ) ? $email : '';
  foreach ( self::MODULES as $key ) { $out[ $key ] = isset( $input[ $key ] ) && '1' === (string) ( is_scalar( $input[ $key ] ) ? $input[ $key ] : '' ) ? 1 : 0; }
  foreach ( array( 'pending_days', 'lead_days', 'click_days', 'review_days' ) as $key ) {
   $v = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? (int) $input[ $key ] : $out[ $key ];
   $out[ $key ] = max( in_array( $key, array( 'pending_days', 'review_days' ), true ) ? 1 : 0, min( 3650, $v ) );
  }
  $out['setup_done'] = empty( $input['setup_done'] ) ? 0 : 1;
  return $out;
 }
 public static function hooks() {
  add_action( 'admin_menu', function () {
   add_submenu_page( 'authorgrowth-pro', 'Settings & Diagnostics', 'Settings', 'manage_options', 'agp-settings', array( __CLASS__, 'admin' ) );
   add_submenu_page( 'authorgrowth-pro', 'Setup Wizard', 'Setup Wizard', 'manage_options', 'agp-setup', array( __CLASS__, 'wizard' ) );
  } );
  add_action( 'admin_post_agp_settings', array( __CLASS__, 'save' ) );
  add_action( 'admin_notices', function () { if ( current_user_can( 'manage_options' ) && ! self::get()['setup_done'] ) { echo '<div class="notice notice-info"><p>Welcome to AuthorGrowth Pro. <a href="' . esc_url( admin_url( 'admin.php?page=agp-setup' ) ) . '">Open the setup wizard</a>.</p></div>'; } } );
 }
 public static function mail( $to, $subject, $message ) {
  $s = self::get(); $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
  if ( $s['sender_email'] && is_email( $s['sender_email'] ) && ! preg_match( '/[\r\n]/', $s['sender_email'] ) ) {
   $name = str_replace( array( "\r", "\n", '<', '>', '"' ), '', $s['sender_name'] );
   $headers[] = 'From: ' . ( $name ? $name . ' ' : '' ) . '<' . $s['sender_email'] . '>';
  }
  return wp_mail( $to, $subject, $message, $headers );
 }
 public static function save() {
  AGP_Leads::guard( 'agp_settings' );
  $input = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();
  // Wizard saves only its own fields; advanced settings are preserved.
  if ( '1' === AGP_Leads::input( 'wizard' ) ) { $input = array_merge( self::get(), array_intersect_key( $input, array_flip( array( 'brand', 'accent', 'sender_name', 'sender_email', 'setup_done' ) ) ) ); }
  update_option( 'agp_settings', self::sanitize( $input ), false );
  wp_safe_redirect( admin_url( '1' === AGP_Leads::input( 'wizard' ) && empty( $input['setup_done'] ) ? 'admin.php?page=agp-setup&step=2' : 'admin.php?page=agp-settings&saved=1' ) ); exit;
 }
 public static function fields( $wizard = false ) {
  $s = self::get();
  foreach ( array( 'brand' => 'Brand name', 'accent' => 'Accent color', 'sender_name' => 'Email sender name', 'sender_email' => 'Email sender address' ) + ( $wizard ? array() : array( 'background' => 'Card background', 'text' => 'Card text color' ) ) as $key => $label ) {
   $type = in_array( $key, array( 'accent', 'background', 'text' ), true ) ? 'color' : ( 'sender_email' === $key ? 'email' : 'text' );
   echo '<p><label for="agp-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><br><input id="agp-' . esc_attr( $key ) . '" name="settings[' . esc_attr( $key ) . ']" type="' . esc_attr( $type ) . '" value="' . esc_attr( $s[ $key ] ) . '" maxlength="254"></p>';
  }
 }
 public static function form_start() { echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="agp_settings">'; wp_nonce_field( 'agp_settings' ); }
 public static function admin() {
  if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden.', '', array( 'response' => 403 ) ); }
  $s = self::get(); echo '<div class="wrap agp"><h1>Settings & Diagnostics</h1><section class="agp-card">';
  if ( isset( $_GET['saved'] ) ) { echo '<p role="status">Settings saved.</p>'; }
  self::form_start(); self::fields(); echo '<h2>Modules</h2><p>Disabling hides public features and stops new submissions/counting; retained data and privacy tools remain available. Existing chapter and waitlist verification links continue to work.</p>';
  foreach ( self::MODULES as $key ) { echo '<p><label><input type="checkbox" name="settings[' . esc_attr( $key ) . ']" value="1" ' . checked( $s[ $key ], 1, false ) . '> ' . esc_html( ucfirst( $key ) ) . '</label></p>'; }
  echo '<h2>Retention (days)</h2><p>Daily WordPress cron deletes expired records permanently. Back up first. Zero retains verified leads/click totals indefinitely. Private PDF files and approved reviews are not automatically deleted.</p>';
  foreach ( array( 'pending_days' => 'Unverified chapter/waitlist requests (minimum 1)', 'lead_days' => 'Verified leads/waitlist entries (0 = keep)', 'click_days' => 'Aggregate click records (0 = keep)', 'review_days' => 'Pending/spam reviews (minimum 1)' ) as $key => $label ) { echo '<p><label>' . esc_html( $label ) . ' <input type="number" min="' . ( in_array( $key, array( 'pending_days', 'review_days' ), true ) ? '1' : '0' ) . '" max="3650" name="settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $s[ $key ] ) . '"></label></p>'; }
  echo '<input type="hidden" name="settings[setup_done]" value="' . esc_attr( $s['setup_done'] ) . '">'; submit_button( 'Save settings' ); echo '</form></section>';
  self::diagnostics(); echo '</div>';
 }
 public static function diagnostics() {
  global $wpdb;
  $checks = array( 'Plugin version' => AGP_VERSION, 'PHP version' => PHP_VERSION, 'WordPress version' => get_bloginfo( 'version' ), 'HTTPS' => is_ssl() ? 'Yes' : 'No — configure HTTPS', 'Private PDF directory' => AGP_Leads::private_dir() ? 'Available (host must verify no public aliases)' : 'Unavailable', 'PHP fileinfo' => class_exists( 'finfo' ) ? 'Available' : 'Missing', 'Privacy policy' => get_privacy_policy_url() ? 'Configured' : 'Missing', 'Cleanup scheduled' => wp_next_scheduled( 'agp_leads_cleanup' ) ? 'Yes' : 'No', 'Mail delivery' => 'Not tested — verify on staging' );
  foreach ( array( AGP_Leads::table(), AGP_Clicks::table(), AGP_Launches::table() ) as $table ) { $checks[ str_replace( $wpdb->prefix, '', $table ) . ' table' ] = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ? 'Present' : 'Missing'; }
  echo '<section class="agp-card"><h2>Diagnostics</h2><dl>'; foreach ( $checks as $name => $value ) { echo '<dt><strong>' . esc_html( $name ) . '</strong></dt><dd>' . esc_html( $value ) . '</dd>'; } echo '</dl><p>No credentials, filesystem paths or email addresses are exposed here. Table presence does not verify schema correctness.</p></section>';
 }
 public static function wizard() {
  if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden.', '', array( 'response' => 403 ) ); }
  $step = isset( $_GET['step'] ) && is_scalar( $_GET['step'] ) ? max( 1, min( 3, (int) $_GET['step'] ) ) : 1;
  echo '<div class="wrap agp"><h1>AuthorGrowth Pro setup — ' . esc_html( $step ) . ' / 3</h1><nav aria-label="Setup steps">';
  foreach ( array( 1 => 'Brand & mail', 2 => 'Privacy & storage', 3 => 'Finish' ) as $n => $label ) { echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=agp-setup&step=' . $n ) ) . '">' . esc_html( $label ) . '</a> '; } echo '</nav><section class="agp-card">';
  if ( 1 === $step ) { self::form_start(); echo '<input type="hidden" name="wizard" value="1">'; self::fields( true ); submit_button( 'Save brand & mail' ); echo '</form><p>Use an address authorized by your SMTP provider; saving does not configure SMTP or verify delivery.</p>'; }
  if ( 2 === $step ) { echo '<h2>Prepare your site</h2><ol><li>Enable HTTPS and publish your WordPress privacy policy.</li><li>Configure AGP_PRIVATE_DIR outside all web-served roots (README).</li><li>Set up SMTP and test a real chapter request.</li><li>Choose retention settings and a withdrawal contact in your privacy policy.</li></ol>'; self::diagnostics(); }
  if ( 3 === $step ) { echo '<p>Add a book, then place chapter, launch and review shortcodes in Gutenberg or Elementor. Complete the staging release checklist before production.</p>'; self::form_start(); echo '<input type="hidden" name="wizard" value="1"><input type="hidden" name="settings[setup_done]" value="1">'; submit_button( 'Finish setup guidance' ); echo '</form><p>Finishing dismisses the welcome notice; it does not certify production readiness.</p>'; }
  echo '</section></div>';
 }
}
