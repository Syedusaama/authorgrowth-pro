<?php
/** Reader-authored testimonials stored in native WordPress comments. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class AGP_Reviews {
 const TYPE = 'agp_review';
 public static function hooks() {
  add_shortcode( 'agp_review_form', array( __CLASS__, 'form' ) );
  add_shortcode( 'agp_reviews', array( __CLASS__, 'display' ) );
  add_action( 'admin_post_agp_review', array( __CLASS__, 'submit' ) ); add_action( 'admin_post_nopriv_agp_review', array( __CLASS__, 'submit' ) );
  add_action( 'admin_post_agp_moderate', array( __CLASS__, 'moderate' ) );
  add_action( 'admin_menu', function () { add_submenu_page( 'authorgrowth-pro', 'Reader Reviews', 'Reader Reviews', 'manage_options', 'agp-reviews', array( __CLASS__, 'admin' ) ); } );
  add_filter( 'wp_privacy_personal_data_exporters', function ( $items ) { return array( 'agp-reviews' => array( 'exporter_friendly_name' => 'Reader reviews', 'callback' => array( __CLASS__, 'privacy_export' ) ) ) + $items; } );
  // Run before native comment anonymization so matching email addresses are still available.
  add_filter( 'wp_privacy_personal_data_erasers', function ( $items ) { return array( 'agp-reviews' => array( 'eraser_friendly_name' => 'Reader reviews', 'callback' => array( __CLASS__, 'privacy_erase' ) ) ) + $items; } );
  add_action( 'agp_leads_cleanup', array( __CLASS__, 'cleanup' ) );
 }
 public static function validate( $data ) {
  $out = array();
  foreach ( array( 'name' => 100, 'email' => 254, 'review' => 2000 ) as $key => $limit ) {
   if ( ! isset( $data[ $key ] ) || ! is_string( $data[ $key ] ) || strlen( trim( $data[ $key ] ) ) > $limit ) { return false; }
   $out[ $key ] = 'review' === $key ? sanitize_textarea_field( $data[ $key ] ) : sanitize_text_field( $data[ $key ] );
   if ( '' === trim( $out[ $key ] ) ) { return false; }
  }
  if ( ! is_email( $out['email'] ) || ! isset( $data['consent'] ) || '1' !== $data['consent'] || ! empty( $data['website'] ) ) { return false; }
  $rating = $data['rating'] ?? '';
  if ( ! is_string( $rating ) || ! in_array( $rating, array( '', '1', '2', '3', '4', '5' ), true ) ) { return false; }
  $out['rating'] = (int) $rating; return $out;
 }
 public static function form( $attrs ) {
  if ( ! AGP_Settings::enabled( 'reviews' ) ) { return ''; }
  $a = shortcode_atts( array( 'book' => 0 ), $attrs ); $id = absint( $a['book'] ); if ( ! AGP_Leads::public_book( $id ) ) { return ''; } $uid = wp_unique_id( 'agp-review-' );
  $html = '<form class="agp-review-form agp-promotions" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><h3>Share your review</h3><input type="hidden" name="action" value="agp_review"><input type="hidden" name="book" value="' . esc_attr( $id ) . '">' . wp_nonce_field( 'agp_review_' . $id, 'agp_nonce', false, false );
  foreach ( array( 'name' => 'Display name (public)', 'email' => 'Email (not published)' ) as $key => $label ) { $html .= '<p><label for="' . esc_attr( $uid . $key ) . '">' . esc_html( $label ) . '</label><br><input id="' . esc_attr( $uid . $key ) . '" name="' . esc_attr( $key ) . '" type="' . ( 'email' === $key ? 'email' : 'text' ) . '" maxlength="' . ( 'email' === $key ? '254' : '100' ) . '" required></p>'; }
  $html .= '<p><label for="' . esc_attr( $uid . 'rating' ) . '">Rating (optional)</label><br><select id="' . esc_attr( $uid . 'rating' ) . '" name="rating"><option value="">No rating</option>';
  for ( $n = 1; $n <= 5; $n++ ) { $html .= '<option value="' . $n . '">' . $n . ' / 5</option>'; }
  $html .= '</select></p><p><label for="' . esc_attr( $uid . 'body' ) . '">Your review</label><br><textarea id="' . esc_attr( $uid . 'body' ) . '" name="review" maxlength="2000" rows="5" required></textarea></p><p hidden aria-hidden="true"><input name="website" tabindex="-1" aria-label="Leave empty" autocomplete="off"></p><p><label><input type="checkbox" name="consent" value="1" required> I allow my display name, review and optional rating to be published after moderation. Contact the site owner to request removal.</label></p><p>Your email is stored for moderation, not marketing. ' . get_the_privacy_policy_link() . '</p><button type="submit">Submit for approval</button></form>';
  return $html;
 }
 public static function submit() {
  $id = absint( AGP_Leads::input( 'book' ) ); $input = array(); foreach ( array( 'name', 'email', 'review', 'rating', 'consent', 'website' ) as $key ) { $input[ $key ] = AGP_Leads::input( $key ); }
  $data = self::validate( $input );
  if ( ! AGP_Settings::enabled( 'reviews' ) || ! AGP_Leads::public_book( $id ) || ! wp_verify_nonce( AGP_Leads::input( 'agp_nonce' ), 'agp_review_' . $id ) || ! $data ) { wp_die( 'Invalid review. Reload the form and try again.', '', array( 'response' => 400 ) ); }
  if ( ! AGP_Leads::rate( 'review-ip:' . ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ), 5 ) || ! AGP_Leads::rate( 'review-email:' . strtolower( $data['email'] ), 3 ) ) { wp_die( 'Too many submissions.', '', array( 'response' => 429 ) ); }
  $comment = wp_insert_comment( array( 'comment_post_ID' => $id, 'comment_author' => $data['name'], 'comment_author_email' => $data['email'], 'comment_author_url' => '', 'comment_content' => $data['review'], 'comment_type' => self::TYPE, 'comment_approved' => 0, 'comment_author_IP' => '', 'comment_agent' => '', 'user_id' => 0, 'comment_meta' => array( 'agp_rating' => $data['rating'], 'agp_publication_consent' => 'I allow my display name, review and optional rating to be published after moderation.', 'agp_consent_at' => current_time( 'mysql', true ) ) ) );
  if ( ! $comment ) { wp_die( 'Unable to save review.', '', array( 'response' => 503 ) ); }
  wp_die( 'Thank you. Your review is pending approval and is not yet public.', 'Review submitted', array( 'response' => 200 ) );
 }
 public static function display( $attrs ) {
  if ( ! AGP_Settings::enabled( 'reviews' ) ) { return ''; }
  $a = shortcode_atts( array( 'book' => 0, 'layout' => 'cards', 'limit' => 12, 'stars' => 'yes' ), $attrs ); $id = absint( $a['book'] );
  if ( ! AGP_Leads::public_book( $id ) ) { return ''; }
  $rows = get_comments( array( 'post_id' => $id, 'type' => self::TYPE, 'status' => 'approve', 'number' => max( 1, min( 50, (int) $a['limit'] ) ), 'orderby' => 'comment_date_gmt', 'order' => 'DESC' ) );
  if ( ! $rows ) { return ''; }
  $slider = 'slider' === $a['layout']; $uid = wp_unique_id( 'agp-testimonials-' );
  $html = '<section class="agp-promotions agp-reviews" aria-label="Reader reviews">';
  if ( $slider ) { $html .= '<div class="agp-slider-controls" data-agp-controls="' . esc_attr( $uid ) . '" hidden><button type="button" data-agp-prev aria-label="Previous reviews">Previous</button> <button type="button" data-agp-next aria-label="Next reviews">Next</button></div>'; }
  $html .= '<div id="' . esc_attr( $uid ) . '" class="' . ( $slider ? 'agp-review-slider' : 'agp-review-grid' ) . '"' . ( $slider ? ' tabindex="0" aria-label="Scrollable reader reviews"' : '' ) . '>';
  foreach ( $rows as $r ) {
   $rating = (int) get_comment_meta( $r->comment_ID, 'agp_rating', true );
   $html .= '<figure class="agp-review-card"><blockquote><p>' . nl2br( esc_html( $r->comment_content ) ) . '</p></blockquote><figcaption>' . esc_html( $r->comment_author ) . '</figcaption>';
   if ( 'no' !== $a['stars'] && $rating >= 1 && $rating <= 5 ) { $html .= '<p aria-label="' . esc_attr( $rating . ' out of 5 stars' ) . '"><span aria-hidden="true">' . str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) . '</span></p>'; }
   $html .= '</figure>';
  }
  return $html . '</div></section>';
 }
 public static function admin() {
  if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden.', '', array( 'response' => 403 ) ); }
  $page = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
  $status = isset( $_GET['status'] ) && is_string( $_GET['status'] ) && in_array( $_GET['status'], array( 'hold', 'approve', 'spam', 'trash' ), true ) ? $_GET['status'] : 'hold';
  $rows = get_comments( array( 'type' => self::TYPE, 'status' => $status, 'number' => 50, 'offset' => ( $page - 1 ) * 50, 'orderby' => 'comment_date_gmt', 'order' => 'DESC' ) );
  echo '<div class="wrap agp"><h1>Reader Reviews</h1><p>Only real reader submissions belong here. Publication requires approval. No review or purchase verification is implied.</p><nav aria-label="Moderation status">';
  foreach ( array( 'hold' => 'Pending', 'approve' => 'Approved', 'spam' => 'Spam', 'trash' => 'Trash' ) as $key => $label ) { echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=agp-reviews&status=' . $key ) ) . '">' . esc_html( $label ) . '</a> '; } echo '</nav>';
  foreach ( $rows as $r ) { echo '<section class="agp-card"><h2>' . esc_html( $r->comment_author ) . ' — Book ' . esc_html( $r->comment_post_ID ) . '</h2><p>' . esc_html( $r->comment_content ) . '</p><p>Rating: ' . esc_html( (int) get_comment_meta( $r->comment_ID, 'agp_rating', true ) ?: 'None' ) . '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="agp_moderate"><input type="hidden" name="id" value="' . esc_attr( $r->comment_ID ) . '">'; wp_nonce_field( 'agp_moderate_' . $r->comment_ID );
   foreach ( array( 'approve' => 'Approve', 'hold' => 'Unapprove', 'spam' => 'Spam', 'trash' => 'Trash', 'delete' => 'Delete permanently' ) as $value => $label ) { echo '<button class="button" name="decision" value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</button> '; } echo '</form></section>'; }
  if ( ! $rows ) { echo '<p>No reviews in this status.</p>'; } echo '<p>'; if ( $page > 1 ) { echo '<a href="' . esc_url( add_query_arg( 'paged', $page - 1 ) ) . '">Previous</a> '; } if ( count( $rows ) === 50 ) { echo '<a href="' . esc_url( add_query_arg( 'paged', $page + 1 ) ) . '">Next</a>'; } echo '</p></div>';
 }
 public static function moderate() {
  $id = absint( AGP_Leads::input( 'id' ) ); AGP_Leads::guard( 'agp_moderate_' . $id ); $review = get_comment( $id ); $decision = AGP_Leads::input( 'decision' );
  if ( ! $review || self::TYPE !== $review->comment_type || ! current_user_can( 'edit_post', $review->comment_post_ID ) || ! in_array( $decision, array( 'approve', 'hold', 'spam', 'trash', 'delete' ), true ) ) { wp_die( 'Invalid moderation action.', '', array( 'response' => 403 ) ); }
  $ok = 'delete' === $decision ? wp_delete_comment( $id, true ) : wp_set_comment_status( $id, $decision, true );
  if ( ! $ok || is_wp_error( $ok ) ) { wp_die( 'Unable to moderate review.', '', array( 'response' => 503 ) ); }
  wp_safe_redirect( admin_url( 'admin.php?page=agp-reviews' ) ); exit;
 }
 public static function privacy_export( $email, $page = 1 ) {
  $rows = get_comments( array( 'type' => self::TYPE, 'author_email' => $email, 'status' => array( 'hold', 'approve', 'spam', 'trash' ), 'number' => 100, 'offset' => ( max( 1, (int) $page ) - 1 ) * 100, 'orderby' => 'comment_ID', 'order' => 'ASC' ) ); $data = array();
  foreach ( $rows as $r ) { $data[] = array( 'group_id' => 'agp-reviews', 'group_label' => 'Reader reviews', 'item_id' => 'agp-review-' . $r->comment_ID, 'data' => array( array( 'name' => 'Name', 'value' => $r->comment_author ), array( 'name' => 'Email', 'value' => $r->comment_author_email ), array( 'name' => 'Review', 'value' => $r->comment_content ), array( 'name' => 'Book ID', 'value' => (string) $r->comment_post_ID ), array( 'name' => 'Rating', 'value' => (string) get_comment_meta( $r->comment_ID, 'agp_rating', true ) ), array( 'name' => 'Publication consent', 'value' => (string) get_comment_meta( $r->comment_ID, 'agp_publication_consent', true ) ), array( 'name' => 'Consent UTC', 'value' => (string) get_comment_meta( $r->comment_ID, 'agp_consent_at', true ) ) ) ); }
  return array( 'data' => $data, 'done' => count( $rows ) < 100 );
 }
 public static function privacy_erase( $email, $page = 1 ) {
  $ids = get_comments( array( 'type' => self::TYPE, 'author_email' => $email, 'status' => array( 'hold', 'approve', 'spam', 'trash' ), 'number' => 100, 'fields' => 'ids', 'orderby' => 'comment_ID', 'order' => 'ASC' ) ); $removed = false; $failed = false;
  foreach ( $ids as $id ) { if ( wp_delete_comment( $id, true ) ) { $removed = true; } else { $failed = true; } }
  return array( 'items_removed' => $removed, 'items_retained' => $failed, 'messages' => $failed ? array( 'Some reviews could not be deleted; retry.' ) : array(), 'done' => ! $failed && count( $ids ) < 100 );
 }
 public static function cleanup() {
  $s = AGP_Settings::get(); $ids = get_comments( array( 'type' => self::TYPE, 'status' => array( 'hold', 'spam' ), 'number' => 100, 'fields' => 'ids', 'date_query' => array( array( 'column' => 'comment_date_gmt', 'before' => gmdate( 'Y-m-d H:i:s', time() - $s['review_days'] * DAY_IN_SECONDS ) ) ) ) );
  foreach ( $ids as $id ) { wp_delete_comment( $id, true ); }
 }
}
