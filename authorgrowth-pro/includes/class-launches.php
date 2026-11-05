<?php
/** Launch campaigns and separately verified, purpose-specific waitlists. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class AGP_Launches {
 public static function table() { global $wpdb; return $wpdb->prefix . 'agp_waitlist'; }
 public static function install() {
  global $wpdb; require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta( 'CREATE TABLE ' . self::table() . " (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
   email varchar(254) NOT NULL,
   book_id bigint(20) unsigned NOT NULL,
   consent_text text NOT NULL,
   created_at datetime NOT NULL,
   verified_at datetime DEFAULT NULL,
   token_hash varchar(64) DEFAULT NULL,
   expires bigint(20) unsigned NOT NULL DEFAULT 0,
   PRIMARY KEY  (id),
   UNIQUE KEY email_book (email,book_id),
   KEY token_hash (token_hash)
  ) " . $wpdb->get_charset_collate() . ';' );
  if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( self::table() ) ) ) === self::table() ) { update_option( 'agp_waitlist_schema', '1', false ); }
 }
 public static function hooks() {
  add_action( 'init', function () { if ( '1' !== get_option( 'agp_waitlist_schema' ) ) { self::install(); } } );
  add_shortcode( 'agp_launch', array( __CLASS__, 'shortcode' ) );
  add_action( 'add_meta_boxes', function () { add_meta_box( 'agp-launch', 'Book Launch (UTC)', array( __CLASS__, 'box' ), 'agp_book' ); } );
  add_action( 'save_post_agp_book', array( __CLASS__, 'save' ) );
  foreach ( array( 'agp_waitlist' => 'capture', 'agp_waitlist_confirm' => 'confirm' ) as $action => $method ) { add_action( 'admin_post_' . $action, array( __CLASS__, $method ) ); add_action( 'admin_post_nopriv_' . $action, array( __CLASS__, $method ) ); }
  add_action( 'admin_menu', function () { add_submenu_page( 'authorgrowth-pro', 'Launch Waitlist', 'Launch Waitlist', 'manage_options', 'agp-waitlist', array( __CLASS__, 'admin' ) ); } );
  add_action( 'admin_post_agp_waitlist_delete', array( __CLASS__, 'delete' ) );
  add_action( 'admin_post_agp_waitlist_export', array( __CLASS__, 'export' ) );
  add_action( 'agp_leads_cleanup', array( __CLASS__, 'cleanup' ) );
  add_filter( 'wp_privacy_personal_data_exporters', function ( $items ) { $items['agp-waitlist'] = array( 'exporter_friendly_name' => 'Launch waitlists', 'callback' => array( __CLASS__, 'privacy_export' ) ); return $items; } );
  add_filter( 'wp_privacy_personal_data_erasers', function ( $items ) { $items['agp-waitlist'] = array( 'eraser_friendly_name' => 'Launch waitlists', 'callback' => array( __CLASS__, 'privacy_erase' ) ); return $items; } );
 }
 public static function valid_date( $value ) {
  if ( ! is_string( $value ) ) { return ''; }
  $date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $value, new DateTimeZone( 'UTC' ) );
  return $date && $date->format( 'Y-m-d\TH:i' ) === $value ? $value : '';
 }
 public static function timestamp( $book ) { $s = self::valid_date( get_post_meta( $book, '_agp_launch_at', true ) ); return $s ? ( new DateTimeImmutable( $s, new DateTimeZone( 'UTC' ) ) )->getTimestamp() : 0; }
 public static function box( $post ) {
  wp_nonce_field( 'agp_launch_save', 'agp_launch_nonce' );
  echo '<p><label>Launch date/time (UTC) <input type="datetime-local" name="agp_launch_at" value="' . esc_attr( get_post_meta( $post->ID, '_agp_launch_at', true ) ) . '"></label></p><p><label>Preorder URL <input class="widefat" type="url" name="agp_preorder" value="' . esc_attr( get_post_meta( $post->ID, '_agp_preorder_url', true ) ) . '"></label></p><p>Publish the book as a coming-soon page; a scheduled/draft post is not publicly promoted. Use <code>[agp_launch book="' . esc_html( $post->ID ) . '"]</code>. After launch, the normal Buy Now URL is used. No automated launch emails are sent.</p>';
 }
 public static function save( $id ) {
  if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $id ) || 'agp_book' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) || ! wp_verify_nonce( AGP_Leads::input( 'agp_launch_nonce' ), 'agp_launch_save' ) ) { return; }
  if ( isset( $_POST['agp_launch_at'] ) ) { update_post_meta( $id, '_agp_launch_at', self::valid_date( AGP_Leads::input( 'agp_launch_at' ) ) ); }
  if ( isset( $_POST['agp_preorder'] ) ) { update_post_meta( $id, '_agp_preorder_url', AGP_Books::sanitize( 'buy_url', AGP_Leads::input( 'agp_preorder' ) ) ); }
 }
 public static function shortcode( $attrs ) {
  if ( ! AGP_Settings::enabled( 'launches' ) ) { return ''; }
  $a = shortcode_atts( array( 'book' => 0 ), $attrs ); $id = absint( $a['book'] ); $at = self::timestamp( $id );
  if ( ! AGP_Leads::public_book( $id ) || ! $at ) { return ''; }
  $future = $at > time(); $title = get_the_title( $id );
  $html = '<section class="agp-promotions agp-launch agp-promo-card"><p class="agp-eyebrow">' . esc_html( $future ? 'COMING SOON' : 'AVAILABLE NOW' ) . '</p><h3>' . esc_html( $title ) . '</h3>' . get_the_post_thumbnail( $id, 'medium', array( 'class' => 'agp-promo-cover' ) );
  $html .= '<p>Launch: <time datetime="' . esc_attr( gmdate( 'c', $at ) ) . '">' . esc_html( gmdate( 'F j, Y H:i', $at ) . ' UTC' ) . '</time></p>';
  if ( $future ) { $html .= '<p class="agp-countdown" data-agp-launch="' . esc_attr( $at ) . '" role="timer" aria-label="Time until book launch">' . esc_html( ceil( ( $at - time() ) / DAY_IN_SECONDS ) . ' days to launch' ) . '</p>' . AGP_Clicks::button( $id, 'Preorder', 'preorder' ) . self::form( $id ); }
  else { $html .= AGP_Clicks::button( $id ); }
  return $html . '</section>';
 }
 public static function form( $id ) {
  $uid = wp_unique_id( 'agp-wait-' );
  return '<form class="agp-waitlist" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><h4>Join the launch waitlist</h4><input type="hidden" name="action" value="agp_waitlist"><input type="hidden" name="book" value="' . esc_attr( $id ) . '">' . wp_nonce_field( 'agp_waitlist_' . $id, 'agp_nonce', false, false ) . '<p><label for="' . esc_attr( $uid ) . '">Email</label><br><input id="' . esc_attr( $uid ) . '" type="email" name="email" maxlength="254" autocomplete="email" required></p><p hidden aria-hidden="true"><input name="website" tabindex="-1" aria-label="Leave empty" autocomplete="off"></p><p><label><input type="checkbox" name="consent" value="1" required> Email me updates about this book launch. I can withdraw by contacting the site owner.</label></p><p>We store your email for this waitlist; confirm it using the link we send. ' . get_the_privacy_policy_link() . '</p><button type="submit">Join waitlist</button></form>';
 }
 public static function capture() {
  global $wpdb; $id = absint( AGP_Leads::input( 'book' ) ); $email = strtolower( trim( AGP_Leads::input( 'email' ) ) );
  if ( ! AGP_Settings::enabled( 'launches' ) || ! AGP_Leads::public_book( $id ) || self::timestamp( $id ) <= time() || ! wp_verify_nonce( AGP_Leads::input( 'agp_nonce' ), 'agp_waitlist_' . $id ) || ! is_email( $email ) || strlen( $email ) > 254 || '1' !== AGP_Leads::input( 'consent' ) || AGP_Leads::input( 'website' ) !== '' ) { wp_die( 'Invalid waitlist request. Reload and try again.', '', array( 'response' => 400 ) ); }
  if ( ! AGP_Leads::rate( 'wait-ip:' . ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ), 10 ) || ! AGP_Leads::rate( 'wait-email:' . $email, 3 ) ) { wp_die( 'Too many requests.', '', array( 'response' => 429 ) ); }
  $token = bin2hex( random_bytes( 32 ) );
  $data = array( 'email' => $email, 'book_id' => $id, 'consent_text' => 'Email me updates about this book launch. I can withdraw by contacting the site owner.', 'created_at' => current_time( 'mysql', true ), 'verified_at' => null, 'token_hash' => hash( 'sha256', $token ), 'expires' => time() + DAY_IN_SECONDS );
  $existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE email=%s AND book_id=%d', $email, $id ) );
  $ok = $existing ? $wpdb->update( self::table(), $data, array( 'id' => $existing ) ) : $wpdb->insert( self::table(), $data );
  if ( false === $ok ) { wp_die( 'Unable to save request.', '', array( 'response' => 503 ) ); }
  $url = add_query_arg( array( 'action' => 'agp_waitlist_confirm', 'token' => $token ), admin_url( 'admin-post.php' ) );
  if ( ! AGP_Settings::mail( $email, 'Confirm your book launch waitlist', "Confirm your request (expires in 24 hours):\n" . $url . "\nIgnore this message if you did not request it." ) ) {
   $wpdb->update( self::table(), array( 'token_hash' => null ), array( 'email' => $email, 'book_id' => $id, 'token_hash' => $data['token_hash'] ) ); wp_die( 'Email delivery could not be started.', '', array( 'response' => 503 ) );
  }
  wp_die( 'Check your email to confirm your waitlist request.', 'Waitlist requested', array( 'response' => 200 ) );
 }
 public static function confirm() {
  global $wpdb; $token = isset( $_GET['token'] ) && is_string( $_GET['token'] ) ? wp_unslash( $_GET['token'] ) : '';
  if ( ! preg_match( '/^[a-f0-9]{64}$/D', $token ) ) { wp_die( 'Invalid confirmation.', '', array( 'response' => 403 ) ); }
  // Confirmation itself does not send mail or disclose the address; it works after the launch date.
  $ok = $wpdb->query( $wpdb->prepare( 'UPDATE ' . self::table() . ' SET verified_at=%s,token_hash=NULL WHERE token_hash=%s AND expires>=%d', current_time( 'mysql', true ), hash( 'sha256', $token ), time() ) );
  nocache_headers(); header( 'Referrer-Policy: no-referrer' );
  wp_die( 1 === $ok ? 'Your waitlist signup is confirmed. Contact the site owner to withdraw.' : 'Link expired or already used.', 'Waitlist confirmation', array( 'response' => 1 === $ok ? 200 : 403 ) );
 }
 public static function admin() {
  if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden.', '', array( 'response' => 403 ) ); }
  global $wpdb; $page = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
  $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT 50 OFFSET %d', ( $page - 1 ) * 50 ) );
  echo '<div class="wrap agp"><h1>Launch Waitlist</h1><p>Purpose-specific launch consent only. No automatic campaign emails. Delete a row to withdraw consent.</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="agp_waitlist_export">'; wp_nonce_field( 'agp_waitlist_export' ); submit_button( 'Export verified waitlist CSV' ); echo '</form><table class="widefat striped"><thead><tr><th>Email</th><th>Book ID</th><th>Requested UTC</th><th>Verified UTC</th><th>Action</th></tr></thead><tbody>';
  foreach ( $rows as $r ) { echo '<tr><td>' . esc_html( $r->email ) . '</td><td>' . esc_html( $r->book_id ) . '</td><td>' . esc_html( $r->created_at ) . '</td><td>' . esc_html( $r->verified_at ?: 'Pending' ) . '</td><td><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="agp_waitlist_delete"><input type="hidden" name="id" value="' . esc_attr( $r->id ) . '">'; wp_nonce_field( 'agp_waitlist_delete' ); echo '<button class="button">Delete</button></form></td></tr>'; }
  if ( ! $rows ) { echo '<tr><td colspan="5">No waitlist entries.</td></tr>'; } echo '</tbody></table><p>';
  if ( $page > 1 ) { echo '<a href="' . esc_url( add_query_arg( 'paged', $page - 1 ) ) . '">Previous</a> '; } if ( count( $rows ) === 50 ) { echo '<a href="' . esc_url( add_query_arg( 'paged', $page + 1 ) ) . '">Next</a>'; } echo '</p></div>';
 }
 public static function delete() { AGP_Leads::guard( 'agp_waitlist_delete' ); global $wpdb; $wpdb->delete( self::table(), array( 'id' => absint( AGP_Leads::input( 'id' ) ) ) ); wp_safe_redirect( admin_url( 'admin.php?page=agp-waitlist' ) ); exit; }
 public static function export() {
  AGP_Leads::guard( 'agp_waitlist_export' ); global $wpdb; nocache_headers(); header( 'Content-Type: text/csv; charset=utf-8' ); header( 'Content-Disposition: attachment; filename="authorgrowth-waitlist.csv"' );
  $out = fopen( 'php://output', 'w' ); fputcsv( $out, array( 'Email', 'Book ID', 'Consent', 'Requested UTC', 'Verified UTC' ), ',', '"', '' ); $last = 0;
  do { $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id>%d AND verified_at IS NOT NULL ORDER BY id LIMIT 500', $last ) ); foreach ( $rows as $r ) { fputcsv( $out, array_map( array( 'AGP_Leads', 'csv_cell' ), array( $r->email, $r->book_id, $r->consent_text, $r->created_at, $r->verified_at ) ), ',', '"', '' ); $last = $r->id; } } while ( count( $rows ) === 500 ); fclose( $out ); exit;
 }
 public static function cleanup() {
  global $wpdb; $s = AGP_Settings::get();
  $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE verified_at IS NULL AND created_at<%s', gmdate( 'Y-m-d H:i:s', time() - $s['pending_days'] * DAY_IN_SECONDS ) ) );
  if ( $s['lead_days'] > 0 ) { $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE verified_at IS NOT NULL AND verified_at<%s', gmdate( 'Y-m-d H:i:s', time() - $s['lead_days'] * DAY_IN_SECONDS ) ) ); }
 }
 public static function privacy_export( $email, $page = 1 ) {
  global $wpdb; $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE email=%s ORDER BY id LIMIT 100 OFFSET %d', strtolower( $email ), ( max( 1, (int) $page ) - 1 ) * 100 ) ); $data = array();
  foreach ( $rows as $r ) { $fields = array(); foreach ( array( 'email', 'book_id', 'consent_text', 'created_at', 'verified_at' ) as $key ) { $fields[] = array( 'name' => $key, 'value' => (string) $r->$key ); } $data[] = array( 'group_id' => 'agp-waitlist', 'group_label' => 'Launch waitlist', 'item_id' => 'agp-wait-' . $r->id, 'data' => $fields ); } return array( 'data' => $data, 'done' => count( $rows ) < 100 );
 }
 public static function privacy_erase( $email, $page = 1 ) { global $wpdb; $n = $wpdb->delete( self::table(), array( 'email' => strtolower( $email ) ) ); return array( 'items_removed' => $n > 0, 'items_retained' => false === $n, 'messages' => false === $n ? array( 'Waitlist deletion failed; retry.' ) : array(), 'done' => false !== $n ); }
}
