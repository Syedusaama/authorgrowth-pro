<?php
/** Consent-aware chapter delivery. Private PDFs must live outside the web root. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class AGP_Leads {
 const CONSENT = 'Send me author news and book offers by email. I can unsubscribe by contacting the site owner.';
 public static function table() { global $wpdb; return $wpdb->prefix . 'agp_leads'; }
 public static function install() {
  global $wpdb;
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta( 'CREATE TABLE ' . self::table() . " (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
   email varchar(254) NOT NULL,
   book_id bigint(20) unsigned NOT NULL,
   consent tinyint(1) NOT NULL DEFAULT 0,
   consent_text text NOT NULL,
   created_at datetime NOT NULL,
   verified_at datetime DEFAULT NULL,
   token_hash varchar(64) DEFAULT NULL,
   expires bigint(20) unsigned NOT NULL DEFAULT 0,
   PRIMARY KEY  (id),
   UNIQUE KEY email_book (email,book_id)
  ) " . $wpdb->get_charset_collate() . ';' );
  update_option( 'agp_leads_schema', '1', false );
 }
 public static function hooks() {
  add_action( 'init', function () { if ( '1' !== get_option( 'agp_leads_schema' ) ) { self::install(); } } );
  add_action( 'agp_leads_cleanup', array( __CLASS__, 'cleanup' ) );
  add_action( 'init', function () { if ( ! wp_next_scheduled( 'agp_leads_cleanup' ) ) { wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'agp_leads_cleanup' ); } } );
  add_shortcode( 'agp_chapter', array( __CLASS__, 'form' ) );
  foreach ( array( 'agp_capture' => 'capture', 'agp_download' => 'download' ) as $action => $method ) {
   add_action( 'admin_post_' . $action, array( __CLASS__, $method ) );
   add_action( 'admin_post_nopriv_' . $action, array( __CLASS__, $method ) );
  }
  add_action( 'admin_menu', function () { add_submenu_page( 'authorgrowth-pro', 'Reader Leads', 'Reader Leads', 'manage_options', 'agp-leads', array( __CLASS__, 'admin' ) ); } );
  add_action( 'admin_post_agp_export', array( __CLASS__, 'export' ) );
  add_action( 'admin_post_agp_delete_lead', array( __CLASS__, 'delete' ) );
  add_action( 'admin_post_agp_pdf', array( __CLASS__, 'upload' ) );
  add_action( 'add_meta_boxes', function () { add_meta_box( 'agp-chapter', 'Free Chapter PDF', array( __CLASS__, 'box' ), 'agp_book', 'side' ); } );
  add_filter( 'wp_privacy_personal_data_exporters', function ( $items ) { $items['agp'] = array( 'exporter_friendly_name' => 'AuthorGrowth leads', 'callback' => array( __CLASS__, 'privacy_export' ) ); return $items; } );
  add_filter( 'wp_privacy_personal_data_erasers', function ( $items ) { $items['agp'] = array( 'eraser_friendly_name' => 'AuthorGrowth leads', 'callback' => array( __CLASS__, 'privacy_erase' ) ); return $items; } );
 }
 public static function private_dir() {
  if ( ! defined( 'AGP_PRIVATE_DIR' ) ) { return false; }
  $dir = realpath( AGP_PRIVATE_DIR );
  if ( ! $dir || ! is_writable( $dir ) ) { return false; }
  // A separate, existing directory outside every known public root is mandatory.
  foreach ( array_filter( array( ABSPATH, $_SERVER['DOCUMENT_ROOT'] ?? '' ) ) as $root ) {
   $root = realpath( $root );
   if ( ! $root || $dir === $root || str_starts_with( $dir . DIRECTORY_SEPARATOR, rtrim( $root, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR ) ) { return false; }
  }
  return $dir;
 }
 public static function pdf( $book ) {
  $dir = self::private_dir(); $name = get_post_meta( $book, '_agp_private_pdf', true );
  if ( ! $dir || ! is_string( $name ) || ! preg_match( '/^[a-f0-9]{32}\.pdf$/D', $name ) ) { return false; }
  $path = realpath( $dir . '/' . $name );
  return $path && dirname( $path ) === $dir && is_readable( $path ) ? $path : false;
 }
 public static function public_book( $id ) { return 'agp_book' === get_post_type( $id ) && 'publish' === get_post_status( $id ) && '' === (string) get_post_field( 'post_password', $id ); }
 public static function input( $key ) { return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; }
 public static function form( $attrs ) {
  $attrs = shortcode_atts( array( 'book' => get_the_ID() ), $attrs ); $id = absint( $attrs['book'] );
  if ( ! self::public_book( $id ) || ! self::pdf( $id ) ) { return ''; }
  $uid = wp_unique_id( 'agp-chapter-' );
  ob_start(); ?>
  <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="agp-chapter">
   <h3><?php esc_html_e( 'Read a free chapter', 'authorgrowth-pro' ); ?></h3>
   <input type="hidden" name="action" value="agp_capture"><input type="hidden" name="book" value="<?php echo esc_attr( $id ); ?>">
   <?php wp_nonce_field( 'agp_capture_' . $id, 'agp_nonce' ); ?>
   <p><label for="<?php echo esc_attr( $uid ); ?>">Email</label><br><input id="<?php echo esc_attr( $uid ); ?>" name="email" type="email" maxlength="254" autocomplete="email" required></p>
   <p hidden aria-hidden="true"><label>Leave empty<input name="website" tabindex="-1" autocomplete="off"></label></p>
   <p><label><input type="checkbox" name="consent" value="1"> <?php echo esc_html( self::CONSENT ); ?></label></p>
   <p><?php esc_html_e( 'We store your email to deliver this chapter. Marketing consent is optional. Your download link expires in 30 minutes and works once.', 'authorgrowth-pro' ); ?> <?php the_privacy_policy_link(); ?></p>
   <button type="submit"><?php esc_html_e( 'Email my chapter link', 'authorgrowth-pro' ); ?></button>
  </form>
  <?php return ob_get_clean();
 }
 public static function capture() {
  global $wpdb;
  $id = absint( self::input( 'book' ) );
  if ( ! wp_verify_nonce( self::input( 'agp_nonce' ), 'agp_capture_' . $id ) || ! self::public_book( $id ) || ! self::pdf( $id ) ) { wp_die( 'Chapter unavailable. Reload the form and try again.', '', array( 'response' => 400 ) ); }
  $email = strtolower( trim( self::input( 'email' ) ) );
  if ( ! is_email( $email ) || strlen( $email ) > 254 || self::input( 'website' ) !== '' ) { wp_die( 'Invalid submission.', '', array( 'response' => 400 ) ); }
  // Atomic per-hour counters. Store only keyed hashes, never raw IP addresses.
  $ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
  if ( ! self::rate( 'ip:' . $ip, 10 ) || ! self::rate( 'email:' . $email, 3 ) ) { wp_die( 'Too many attempts. Try again next hour.', '', array( 'response' => 429 ) ); }
  $token = bin2hex( random_bytes( 32 ) ); $consent = '1' === self::input( 'consent' ) ? 1 : 0;
  $data = array( 'email' => $email, 'book_id' => $id, 'consent' => $consent, 'consent_text' => $consent ? self::CONSENT : '', 'created_at' => current_time( 'mysql', true ), 'verified_at' => null, 'token_hash' => hash( 'sha256', $token ), 'expires' => time() + 1800 );
  $existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE email=%s AND book_id=%d', $email, $id ) );
  $ok = $existing ? $wpdb->update( self::table(), $data, array( 'id' => $existing ) ) : $wpdb->insert( self::table(), $data );
  if ( false === $ok ) { wp_die( 'Unable to save request. Try again later.', '', array( 'response' => 503 ) ); }
  $url = add_query_arg( array( 'action' => 'agp_download', 'token' => $token ), admin_url( 'admin-post.php' ) );
  if ( ! wp_mail( $email, 'Your free chapter', "Download your requested chapter (valid once for 30 minutes):\n" . $url . "\nIf you did not request this, ignore this email." ) ) {
   $wpdb->update( self::table(), array( 'token_hash' => null ), array( 'email' => $email, 'book_id' => $id ) );
   wp_die( 'Email delivery could not be started. Please try again later.', '', array( 'response' => 503 ) );
  }
  wp_die( 'Check your inbox for your chapter link. This does not subscribe you unless you checked the marketing box and confirm your email by using the link.', 'Chapter requested', array( 'response' => 200 ) );
 }
 public static function rate( $identity, $limit ) {
  global $wpdb;
  $key = '_agp_rate_' . gmdate( 'YmdH' ) . '_' . hash_hmac( 'sha256', $identity, wp_salt( 'nonce' ) );
  $written = $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,'1','off') ON DUPLICATE KEY UPDATE option_value=CAST(option_value AS UNSIGNED)+1", $key ) );
  $count = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name=%s", $key ) );
  // Delete past hour buckets; current and previous hour survive concurrent boundary requests.
  $cutoff = '_agp_rate_' . gmdate( 'YmdH', time() - 7200 );
  $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name < %s", $wpdb->esc_like( '_agp_rate_' ) . '%', $cutoff ) );
  return false !== $written && null !== $count && (int) $count > 0 && (int) $count <= $limit;
 }
 public static function download() {
  global $wpdb;
  $token = isset( $_GET['token'] ) && is_string( $_GET['token'] ) ? wp_unslash( $_GET['token'] ) : '';
  if ( ! preg_match( '/^[a-f0-9]{64}$/D', $token ) ) { wp_die( 'Invalid link.', '', array( 'response' => 403 ) ); }
  $hash = hash( 'sha256', $token );
  $lead = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE token_hash=%s AND expires>=%d', $hash, time() ) );
  $path = $lead && self::public_book( $lead->book_id ) ? self::pdf( $lead->book_id ) : false;
  if ( ! $path ) { wp_die( 'Link expired or chapter unavailable.', '', array( 'response' => 403 ) ); }
  $file = fopen( $path, 'rb' );
  if ( ! $file ) { wp_die( 'Chapter unavailable.', '', array( 'response' => 503 ) ); }
  $used = $wpdb->query( $wpdb->prepare( 'UPDATE ' . self::table() . ' SET token_hash=NULL,verified_at=%s WHERE id=%d AND token_hash=%s AND expires>=%d', current_time( 'mysql', true ), $lead->id, $hash, time() ) );
  if ( 1 !== $used ) { fclose( $file ); wp_die( 'Link already used.', '', array( 'response' => 403 ) ); }
  while ( ob_get_level() ) { if ( ! ob_end_clean() ) { break; } }
  nocache_headers(); header( 'Referrer-Policy: no-referrer' ); header( 'X-Content-Type-Options: nosniff' );
  header( 'Content-Type: application/pdf' ); header( 'Content-Disposition: attachment; filename="free-chapter.pdf"' );
  header( 'Content-Length: ' . filesize( $path ) ); fpassthru( $file ); fclose( $file ); exit;
 }
 public static function box( $post ) {
  echo '<p>Use <code>[agp_chapter book="' . esc_html( $post->ID ) . '"]</code> in a Shortcode block or Elementor Shortcode widget.</p><p>Upload a PDF from Reader Leads after saving this book.</p>';
  echo '<p>' . esc_html( self::pdf( $post->ID ) ? 'Private chapter configured.' : 'No private chapter available.' ) . '</p>';
 }
 public static function guard( $action ) { if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden.', '', array( 'response' => 403 ) ); } check_admin_referer( $action ); }
 public static function upload() {
  self::guard( 'agp_pdf' ); $book = absint( self::input( 'book' ) ); $dir = self::private_dir();
  if ( ! $dir || 'agp_book' !== get_post_type( $book ) || ! current_user_can( 'edit_post', $book ) ) { wp_die( 'Configure private storage and choose a valid book.' ); }
  $file = $_FILES['chapter'] ?? array();
  if ( ! isset( $file['tmp_name'], $file['size'], $file['error'] ) || ! is_string( $file['tmp_name'] ) || UPLOAD_ERR_OK !== $file['error'] || $file['size'] > 10 * MB_IN_BYTES || ! is_uploaded_file( $file['tmp_name'] ) ) { wp_die( 'Upload a PDF up to 10 MB.' ); }
  $handle = fopen( $file['tmp_name'], 'rb' ); $magic = $handle ? fread( $handle, 5 ) : ''; if ( $handle ) { fclose( $handle ); }
  if ( ! class_exists( 'finfo' ) ) { wp_die( 'The PHP fileinfo extension is required for PDF uploads.' ); }
  $mime = ( new finfo( FILEINFO_MIME_TYPE ) )->file( $file['tmp_name'] );
  if ( '%PDF-' !== $magic || 'application/pdf' !== $mime ) { wp_die( 'Invalid PDF.' ); }
  $name = bin2hex( random_bytes( 16 ) ) . '.pdf'; $old = self::pdf( $book );
  if ( ! move_uploaded_file( $file['tmp_name'], $dir . '/' . $name ) ) { wp_die( 'Unable to store PDF.' ); }
  chmod( $dir . '/' . $name, 0600 );
  if ( false === update_post_meta( $book, '_agp_private_pdf', $name ) ) { unlink( $dir . '/' . $name ); wp_die( 'Unable to save PDF.' ); }
  if ( $old ) { unlink( $old ); }
  wp_safe_redirect( admin_url( 'admin.php?page=agp-leads' ) ); exit;
 }
 public static function stats() {
  global $wpdb;
  return $wpdb->get_row( 'SELECT COUNT(*) total, COALESCE(SUM(verified_at IS NOT NULL),0) verified, COALESCE(SUM(consent=1 AND verified_at IS NOT NULL),0) subscribers FROM ' . self::table() );
 }
 public static function admin() {
  if ( ! current_user_can( 'manage_options' ) ) { return; }
  global $wpdb; $page = max( 1, absint( $_GET['paged'] ?? 1 ) );
  $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT 50 OFFSET %d', ( $page - 1 ) * 50 ) );
  echo '<div class="wrap"><h1>Reader Leads</h1><p>Only verified, consenting readers may receive marketing. Delete a lead to withdraw consent and invalidate links.</p>';
  echo '<p>Private PDF storage: ' . esc_html( self::private_dir() ? 'Available' : 'Not configured — see README' ) . '</p>';
  echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><h2>Upload private chapter</h2><input type="hidden" name="action" value="agp_pdf">'; wp_nonce_field( 'agp_pdf' );
  echo '<label>Book ID <input type="number" name="book" min="1" required></label> <label>PDF (10 MB max) <input type="file" name="chapter" accept="application/pdf" required></label> '; submit_button( 'Upload PDF', 'secondary', 'submit', false ); echo '</form>';
  echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="agp_export">'; wp_nonce_field( 'agp_export' ); submit_button( 'Export verified marketing subscribers (CSV)', 'secondary' ); echo '</form>';
  echo '<table class="widefat striped"><thead><tr><th>Email</th><th>Book ID</th><th>Requested (UTC)</th><th>Consent</th><th>Verified</th><th>Action</th></tr></thead><tbody>';
  foreach ( $rows as $row ) {
   echo '<tr><td>' . esc_html( $row->email ) . '</td><td>' . esc_html( $row->book_id ) . '</td><td>' . esc_html( $row->created_at ) . '</td><td>' . esc_html( $row->consent ? 'Yes' : 'No' ) . '</td><td>' . esc_html( $row->verified_at ?: 'Pending' ) . '</td><td><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="agp_delete_lead"><input type="hidden" name="id" value="' . esc_attr( $row->id ) . '">'; wp_nonce_field( 'agp_delete_lead' ); echo '<button class="button">Delete</button></form></td></tr>';
  }
  if ( ! $rows ) { echo '<tr><td colspan="6">No leads found.</td></tr>'; } echo '</tbody></table><p>';
  if ( $page > 1 ) { echo '<a href="' . esc_url( add_query_arg( 'paged', $page - 1 ) ) . '">Previous</a> '; }
  if ( count( $rows ) === 50 ) { echo '<a href="' . esc_url( add_query_arg( 'paged', $page + 1 ) ) . '">Next</a>'; } echo '</p></div>';
 }
 public static function csv_cell( $value ) { return preg_match( '/^[\s]*[=+@\-]/u', (string) $value ) ? "'" . $value : $value; }
 public static function export() {
  self::guard( 'agp_export' ); global $wpdb; nocache_headers(); header( 'Content-Type: text/csv; charset=utf-8' ); header( 'Content-Disposition: attachment; filename="authorgrowth-subscribers.csv"' );
  $out = fopen( 'php://output', 'w' ); fputcsv( $out, array( 'Email', 'Book ID', 'Consent text', 'Requested UTC', 'Verified UTC' ), ',', '"', '' );
  $last = 0;
  do {
   $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id>%d AND consent=1 AND verified_at IS NOT NULL ORDER BY id LIMIT 500', $last ) );
   foreach ( $rows as $r ) { fputcsv( $out, array_map( array( __CLASS__, 'csv_cell' ), array( $r->email, $r->book_id, $r->consent_text, $r->created_at, $r->verified_at ) ), ',', '"', '' ); $last = $r->id; }
  } while ( count( $rows ) === 500 ); fclose( $out ); exit;
 }
 public static function delete() { self::guard( 'agp_delete_lead' ); global $wpdb; $wpdb->delete( self::table(), array( 'id' => absint( self::input( 'id' ) ) ) ); wp_safe_redirect( admin_url( 'admin.php?page=agp-leads' ) ); exit; }
 public static function cleanup() {
  global $wpdb;
  $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name < %s", $wpdb->esc_like( '_agp_rate_' ) . '%', '_agp_rate_' . gmdate( 'YmdH', time() - 7200 ) ) );
  $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE verified_at IS NULL AND created_at < %s', gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS ) ) );
 }
 public static function privacy_export( $email, $page = 1 ) {
  global $wpdb; $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE email=%s ORDER BY id LIMIT 100 OFFSET %d', strtolower( $email ), ( max( 1, (int) $page ) - 1 ) * 100 ) ); $data = array();
  foreach ( $rows as $r ) { $fields = array(); foreach ( array( 'email', 'book_id', 'consent', 'consent_text', 'created_at', 'verified_at' ) as $key ) { $fields[] = array( 'name' => $key, 'value' => (string) $r->$key ); } $data[] = array( 'group_id' => 'agp-leads', 'group_label' => 'Chapter requests', 'item_id' => 'agp-' . $r->id, 'data' => $fields ); }
  return array( 'data' => $data, 'done' => count( $rows ) < 100 );
 }
 public static function privacy_erase( $email, $page = 1 ) { global $wpdb; $result = $wpdb->delete( self::table(), array( 'email' => strtolower( $email ) ) ); return array( 'items_removed' => $result > 0, 'items_retained' => false === $result, 'messages' => false === $result ? array( 'Lead deletion failed; retry.' ) : array(), 'done' => false !== $result ); }
}
