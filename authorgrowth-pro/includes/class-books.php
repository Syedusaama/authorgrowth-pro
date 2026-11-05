<?php
/** Book catalog, metadata, and presentation. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class AGP_Books {
 const TYPE = 'agp_book';
 const FIELDS = array( 'author', 'isbn', 'buy_url', 'release_date' );
 public static function hooks() {
  add_action( 'add_meta_boxes', array( __CLASS__, 'boxes' ) );
  add_action( 'save_post_agp_book', array( __CLASS__, 'save' ), 10, 2 );
  add_filter( 'the_content', array( __CLASS__, 'content' ) );
  add_filter( 'manage_agp_book_posts_columns', array( __CLASS__, 'columns' ) );
  add_action( 'manage_agp_book_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
 }
 public static function register() {
  register_post_type( self::TYPE, array(
   'labels' => array( 'name' => __( 'Books', 'authorgrowth-pro' ), 'singular_name' => __( 'Book', 'authorgrowth-pro' ), 'add_new_item' => __( 'Add New Book', 'authorgrowth-pro' ), 'edit_item' => __( 'Edit Book', 'authorgrowth-pro' ) ),
   'public' => true, 'show_in_rest' => true, 'has_archive' => true,
   'rewrite' => array( 'slug' => 'books' ), 'show_in_menu' => 'authorgrowth-pro',
   'menu_icon' => 'dashicons-book', 'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
   'map_meta_cap' => true,
  ) );
  foreach ( self::FIELDS as $field ) {
   register_post_meta( self::TYPE, '_agp_' . $field, array(
    'type' => 'string', 'single' => true, 'show_in_rest' => true,
    'sanitize_callback' => function ( $value ) use ( $field ) { return self::sanitize( $field, $value ); },
    'auth_callback' => function ( $allowed, $key, $post_id ) { return current_user_can( 'edit_post', $post_id ); },
   ) );
  }
 }
 public static function sanitize( $field, $value ) {
  if ( ! is_string( $value ) ) { return ''; }
  $value = trim( $value );
  if ( 'buy_url' === $field ) {
   $url = esc_url_raw( $value, array( 'https', 'http' ) );
   return wp_http_validate_url( $url ) ? $url : '';
  }
  if ( 'release_date' === $field ) {
   $date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
   return $date && $date->format( 'Y-m-d' ) === $value ? $value : '';
  }
  return sanitize_text_field( $value );
 }
 public static function boxes() {
  add_meta_box( 'agp-details', __( 'Book Details', 'authorgrowth-pro' ), array( __CLASS__, 'box' ), self::TYPE, 'normal', 'high' );
 }
 public static function box( $post ) {
  wp_nonce_field( 'agp_save_book', 'agp_book_nonce' );
  $labels = array( 'author' => __( 'Author name', 'authorgrowth-pro' ), 'isbn' => __( 'ISBN', 'authorgrowth-pro' ), 'buy_url' => __( 'Buy Now URL', 'authorgrowth-pro' ), 'release_date' => __( 'Release date', 'authorgrowth-pro' ) );
  foreach ( $labels as $field => $label ) {
   $type = 'buy_url' === $field ? 'url' : ( 'release_date' === $field ? 'date' : 'text' );
   echo '<p><label for="agp-' . esc_attr( $field ) . '"><strong>' . esc_html( $label ) . '</strong></label><br><input class="widefat" id="agp-' . esc_attr( $field ) . '" name="agp[' . esc_attr( $field ) . ']" type="' . esc_attr( $type ) . '" value="' . esc_attr( get_post_meta( $post->ID, '_agp_' . $field, true ) ) . '"></p>';
  }
  echo '<p>' . esc_html__( 'Use Featured image for the book cover. Enter an absolute HTTP or HTTPS retailer URL. Invalid URLs and dates are cleared when saved.', 'authorgrowth-pro' ) . '</p>';
 }
 public static function save( $post_id, $post ) {
  if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || self::TYPE !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) { return; }
  if ( ! isset( $_POST['agp_book_nonce'] ) || ! is_string( $_POST['agp_book_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['agp_book_nonce'] ) ), 'agp_save_book' ) ) { return; }
  $values = isset( $_POST['agp'] ) && is_array( $_POST['agp'] ) ? wp_unslash( $_POST['agp'] ) : array();
  foreach ( self::FIELDS as $field ) {
   if ( ! array_key_exists( $field, $values ) ) { continue; }
   update_post_meta( $post_id, '_agp_' . $field, self::sanitize( $field, $values[ $field ] ) );
  }
 }
 public static function content( $content ) {
  if ( ! is_singular( self::TYPE ) || ! in_the_loop() || ! is_main_query() || post_password_required() ) { return $content; }
  $id = get_the_ID();
  $author = get_post_meta( $id, '_agp_author', true );
  $url = self::sanitize( 'buy_url', get_post_meta( $id, '_agp_buy_url', true ) );
  $details = '<div class="agp-book-details">';
  if ( has_post_thumbnail( $id ) ) { $details .= get_the_post_thumbnail( $id, 'medium', array( 'class' => 'agp-book-cover' ) ); }
  if ( $author ) { $details .= '<p>' . esc_html( $author ) . '</p>'; }
  if ( $url ) { $details .= '<p><a class="wp-element-button" rel="sponsored noopener" href="' . esc_url( $url ) . '">' . esc_html__( 'Buy Now', 'authorgrowth-pro' ) . '</a></p>'; }
  return $content . $details . '</div>';
 }
 public static function columns( $columns ) {
  $columns['agp_author'] = __( 'Author', 'authorgrowth-pro' );
  $columns['agp_retailer'] = __( 'Retailer link', 'authorgrowth-pro' );
  return $columns;
 }
 public static function column( $column, $post_id ) {
  if ( 'agp_author' === $column ) { echo esc_html( get_post_meta( $post_id, '_agp_author', true ) ); }
  if ( 'agp_retailer' === $column ) { echo esc_html( get_post_meta( $post_id, '_agp_buy_url', true ) ? __( 'Configured', 'authorgrowth-pro' ) : __( 'Missing', 'authorgrowth-pro' ) ); }
 }
}
