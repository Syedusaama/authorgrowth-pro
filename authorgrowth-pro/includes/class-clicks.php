<?php
/** Aggregate outbound click counts. No visitor event log or sales attribution. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class AGP_Clicks {
 public static function table() { global $wpdb; return $wpdb->prefix . 'agp_clicks'; }
 public static function install() {
  global $wpdb; require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta( 'CREATE TABLE ' . self::table() . " (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
   day date NOT NULL,
   book_id bigint(20) unsigned NOT NULL,
   retailer varchar(190) NOT NULL,
   clicks bigint(20) unsigned NOT NULL DEFAULT 0,
   PRIMARY KEY  (id),
   UNIQUE KEY day_book_retailer (day,book_id,retailer)
  ) " . $wpdb->get_charset_collate() . ';' );
  update_option( 'agp_clicks_schema', '1', false );
 }
 public static function hooks() {
  add_action( 'init', function () { if ( '1' !== get_option( 'agp_clicks_schema' ) ) { self::install(); } } );
  add_action( 'admin_post_agp_click', array( __CLASS__, 'redirect' ) );
  add_action( 'admin_post_nopriv_agp_click', array( __CLASS__, 'redirect' ) );
  add_action( 'admin_menu', function () { add_submenu_page( 'authorgrowth-pro', 'Click Analytics', 'Click Analytics', 'manage_options', 'agp-analytics', array( __CLASS__, 'admin' ) ); } );
 }
 public static function destination( $id ) {
  if ( ! AGP_Leads::public_book( $id ) ) { return ''; }
  $url = AGP_Books::sanitize( 'buy_url', get_post_meta( $id, '_agp_buy_url', true ) );
  $parts = wp_parse_url( $url );
  if ( ! $url || ! is_array( $parts ) || empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || ( isset( $parts['port'] ) && ! in_array( $parts['port'], array( 80, 443 ), true ) ) ) { return ''; }
  return $url;
 }
 public static function button( $id, $label = 'Buy Now' ) {
  $url = self::destination( $id ); if ( ! $url ) { return ''; }
  // POST reduces prefetch/link-scanner inflation. Destination is never accepted from a visitor.
  return '<form class="agp-buy-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="agp_click"><input type="hidden" name="book" value="' . esc_attr( $id ) . '">' . wp_nonce_field( 'agp_click_' . $id, 'agp_nonce', false, false ) . '<button class="agp-buy-button wp-element-button" type="submit">' . esc_html( $label ) . '</button></form>';
 }
 public static function eligible( $server ) {
  $ua = is_string( $server['HTTP_USER_AGENT'] ?? null ) ? $server['HTTP_USER_AGENT'] : '';
  if ( ! $ua || preg_match( '/bot|crawler|spider|headless|preview|slurp|curl|wget|python/i', $ua ) ) { return false; }
  foreach ( array( 'HTTP_SEC_PURPOSE', 'HTTP_PURPOSE' ) as $header ) { if ( ! empty( $server[ $header ] ) ) { return false; } }
  return empty( $server['HTTP_DNT'] ) && '1' !== ( $server['HTTP_SEC_GPC'] ?? '' );
 }
 public static function redirect() {
  if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( 'Use the book button to continue.', '', array( 'response' => 405 ) ); }
  $id = absint( AGP_Leads::input( 'book' ) ); $url = self::destination( $id );
  if ( ! $url || ! wp_verify_nonce( AGP_Leads::input( 'agp_nonce' ), 'agp_click_' . $id ) ) { wp_die( 'Book link unavailable. Reload the book page and try again.', '', array( 'response' => 403 ) ); }
  if ( self::eligible( $_SERVER ) && AGP_Leads::rate( 'click:' . (string) ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ), 60 ) ) {
   global $wpdb;
   $host = strtolower( wp_parse_url( $url, PHP_URL_HOST ) );
   $wpdb->query( $wpdb->prepare( 'INSERT INTO ' . self::table() . ' (day,book_id,retailer,clicks) VALUES (%s,%d,%s,1) ON DUPLICATE KEY UPDATE clicks=clicks+1', gmdate( 'Y-m-d' ), $id, $host ) );
  }
  nocache_headers(); header( 'Referrer-Policy: no-referrer' );
  // External redirect is intentional: only the validated, editor-saved retailer URL is used.
  wp_redirect( $url, 303, 'AuthorGrowth Pro' ); exit;
 }
 public static function period( $day, $group ) {
  $date = new DateTimeImmutable( $day, new DateTimeZone( 'UTC' ) );
  return 'monthly' === $group ? $date->format( 'Y-m' ) : ( 'weekly' === $group ? $date->format( 'o-\WW' ) : $date->format( 'Y-m-d' ) );
 }
 public static function admin() {
  if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden.', '', array( 'response' => 403 ) ); }
  global $wpdb;
  $days = isset( $_GET['days'] ) && is_scalar( $_GET['days'] ) ? (int) $_GET['days'] : 30; if ( ! in_array( $days, array( 7, 30, 90 ), true ) ) { $days = 30; }
  $book = isset( $_GET['book'] ) && is_scalar( $_GET['book'] ) ? absint( $_GET['book'] ) : 0;
  $group = isset( $_GET['group'] ) && is_string( $_GET['group'] ) && in_array( $_GET['group'], array( 'daily', 'weekly', 'monthly' ), true ) ? $_GET['group'] : 'daily';
  $start = gmdate( 'Y-m-d', time() - ( $days - 1 ) * DAY_IN_SECONDS ); $end = gmdate( 'Y-m-d' );
  $where = $wpdb->prepare( ' WHERE day BETWEEN %s AND %s', $start, $end ); if ( $book ) { $where .= $wpdb->prepare( ' AND book_id=%d', $book ); }
  $periods = array(); $retailers = array(); $books = array(); $total = 0;
  $rows = $wpdb->get_results( 'SELECT day,book_id,retailer,clicks FROM ' . self::table() . $where . ' ORDER BY day DESC' );
  foreach ( $rows as $r ) { $n = (int) $r->clicks; $p = self::period( $r->day, $group ); $periods[ $p ] = ( $periods[ $p ] ?? 0 ) + $n; $retailers[ $r->retailer ] = ( $retailers[ $r->retailer ] ?? 0 ) + $n; $books[ $r->book_id ] = ( $books[ $r->book_id ] ?? 0 ) + $n; $total += $n; }
  echo '<div class="wrap agp"><header class="agp-hero"><div><p class="agp-eyebrow">READER INTEREST</p><h1>Click Analytics</h1><p>Retailer button clicks, not sales or unique readers. All dates are UTC.</p></div></header><form method="get"><input type="hidden" name="page" value="agp-analytics"><label>Range <select name="days">';
  foreach ( array( 7, 30, 90 ) as $d ) { echo '<option value="' . esc_attr( $d ) . '" ' . selected( $days, $d, false ) . '>' . esc_html( $d . ' days' ) . '</option>'; }
  echo '</select></label> <label>Group <select name="group">'; foreach ( array( 'daily', 'weekly', 'monthly' ) as $g ) { echo '<option value="' . esc_attr( $g ) . '" ' . selected( $group, $g, false ) . '>' . esc_html( ucfirst( $g ) ) . '</option>'; }
  echo '</select></label> <label>Book ID <input type="number" min="0" name="book" value="' . esc_attr( $book ) . '"></label> '; submit_button( 'Apply filters', 'primary', 'submit', false ); echo '</form><section class="agp-card"><h2>Recorded clicks</h2><p class="agp-number">' . esc_html( number_format_i18n( $total ) ) . '</p><p>' . esc_html( $start . ' to ' . $end ) . '</p></section><div class="agp-grid">';
  self::report( 'Clicks by period', $periods ); self::report( 'Clicks by retailer', $retailers );
  $labels = array(); foreach ( $books as $id => $n ) { $labels[ $id . ' — ' . ( get_the_title( $id ) ?: 'Deleted book' ) ] = $n; } self::report( 'Clicks by book', $labels );
  echo '</div><p>Known bots, prefetch requests, DNT/GPC requests, and excessive requests are excluded where detectable. Counts may still include automation or repeat clicks. Older cached forms may need a page reload.</p></div>';
 }
 public static function report( $heading, $rows ) {
  echo '<section class="agp-card"><h2>' . esc_html( $heading ) . '</h2><table class="widefat striped"><thead><tr><th scope="col">Group</th><th scope="col">Clicks</th></tr></thead><tbody>';
  foreach ( $rows as $label => $n ) { echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( number_format_i18n( $n ) ) . '</td></tr>'; }
  if ( ! $rows ) { echo '<tr><td colspan="2">No recorded clicks in this range.</td></tr>'; } echo '</tbody></table></section>';
 }
}
