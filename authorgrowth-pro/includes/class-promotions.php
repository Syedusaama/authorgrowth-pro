<?php
/** Responsive shortcode presentations using the existing book catalog. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class AGP_Promotions {
 public static function hooks() {
  add_shortcode( 'agp_book', array( __CLASS__, 'single' ) );
  add_shortcode( 'agp_books', array( __CLASS__, 'collection' ) );
  add_shortcode( 'agp_floating_cta', array( __CLASS__, 'floating' ) );
  add_action( 'wp_enqueue_scripts', function () { wp_enqueue_style( 'agp-promotions', plugins_url( 'assets/promotions.css', AGP_FILE ), array(), AGP_VERSION );
   $s = AGP_Settings::get();
   wp_add_inline_style( 'agp-promotions', ':root,.agp-promotions{--agp-accent:' . $s['accent'] . ';--agp-bg:' . $s['background'] . ';--agp-text:' . $s['text'] . ';}' );
   wp_enqueue_script( 'agp-release', plugins_url( 'assets/release.js', AGP_FILE ), array(), AGP_VERSION, true ); } );
 }
 public static function options( $attrs ) {
  $s = AGP_Settings::get();
  $a = shortcode_atts( array( 'id' => 0, 'book' => 0, 'layout' => 'grid', 'columns' => 3, 'limit' => 12, 'ids' => '', 'color' => $s['accent'], 'background' => $s['background'], 'text' => $s['text'], 'button' => 'Buy Now' ), $attrs );
  $a['layout'] = 'list' === $a['layout'] ? 'list' : 'grid';
  $a['columns'] = max( 1, min( 4, (int) $a['columns'] ) ); $a['limit'] = max( 1, min( 50, (int) $a['limit'] ) );
  foreach ( array( 'color' => '#F97316', 'background' => '#ffffff', 'text' => '#17233b' ) as $key => $default ) { $a[ $key ] = is_string( $a[ $key ] ) && sanitize_hex_color( $a[ $key ] ) ? $a[ $key ] : $default; }
  $a['button'] = is_string( $a['button'] ) ? sanitize_text_field( $a['button'] ) : 'Buy Now';
  return $a;
 }
 public static function style( $a ) { return '--agp-accent:' . $a['color'] . ';--agp-bg:' . $a['background'] . ';--agp-text:' . $a['text'] . ';--agp-columns:' . $a['columns']; }
 public static function card( $id, $a ) {
  if ( ! AGP_Leads::public_book( $id ) ) { return ''; }
  $title = get_the_title( $id );
  $description = get_post_field( 'post_excerpt', $id ) ?: get_post_field( 'post_content', $id );
  $description = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $description ) ), 35 );
  $cover = get_the_post_thumbnail( $id, 'medium', array( 'class' => 'agp-promo-cover', 'loading' => 'lazy' ) );
  return '<article class="agp-promo-card">' . $cover . '<div class="agp-promo-copy"><h3><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( $title ) . '</a></h3><p>' . esc_html( $description ) . '</p>' . AGP_Clicks::button( $id, $a['button'] ) . '</div></article>';
 }
 public static function single( $attrs ) { if ( ! AGP_Settings::enabled( 'promotions' ) ) { return ''; } $a = self::options( $attrs ); $card = self::card( absint( $a['id'] ), $a ); return $card ? '<div class="agp-promotions" style="' . esc_attr( self::style( $a ) ) . '">' . $card . '</div>' : ''; }
 public static function collection( $attrs ) {
  if ( ! AGP_Settings::enabled( 'promotions' ) ) { return ''; }
  $a = self::options( $attrs ); $query = array( 'post_type' => 'agp_book', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => $a['limit'], 'no_found_rows' => true, 'orderby' => 'date', 'order' => 'DESC' );
  if ( is_string( $a['ids'] ) && $a['ids'] !== '' ) { $ids = array_filter( array_map( 'absint', explode( ',', $a['ids'] ) ) ); if ( ! $ids ) { return ''; } $query['post__in'] = array_slice( $ids, 0, 50 ); $query['orderby'] = 'post__in'; }
  $html = ''; foreach ( get_posts( $query ) as $book ) { $html .= self::card( $book->ID, $a ); }
  return $html ? '<div class="agp-promotions agp-promo-' . esc_attr( $a['layout'] ) . ' agp-cols-' . esc_attr( $a['columns'] ) . '" style="' . esc_attr( self::style( $a ) ) . '">' . $html . '</div>' : '';
 }
 public static function floating( $attrs ) {
  if ( ! AGP_Settings::enabled( 'promotions' ) ) { return ''; }
  static $rendered = false;
  $a = self::options( $attrs ); $id = absint( $a['book'] );
  if ( $rendered || ! AGP_Leads::public_book( $id ) || ! AGP_Clicks::destination( $id ) ) { return ''; }
  $rendered = true;
  // Sticky within its placement avoids covering page content or a site's cookie banner.
  return '<aside class="agp-promotions agp-floating" aria-label="' . esc_attr__( 'Featured book', 'authorgrowth-pro' ) . '" style="' . esc_attr( self::style( $a ) ) . '"><strong>' . esc_html( get_the_title( $id ) ) . '</strong>' . AGP_Clicks::button( $id, $a['button'] ) . '</aside>';
 }
}
