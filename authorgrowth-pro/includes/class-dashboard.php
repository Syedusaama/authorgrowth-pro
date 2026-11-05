<?php
/** Author workspace dashboard. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class AGP_Dashboard {
 public static function hooks() {
  add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
  add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
 }
 public static function menu() {
  add_menu_page( __( 'AuthorGrowth Pro', 'authorgrowth-pro' ), __( 'AuthorGrowth', 'authorgrowth-pro' ), 'edit_posts', 'authorgrowth-pro', array( __CLASS__, 'render' ), 'dashicons-book-alt', 26 );
  add_submenu_page( 'authorgrowth-pro', __( 'Dashboard', 'authorgrowth-pro' ), __( 'Dashboard', 'authorgrowth-pro' ), 'edit_posts', 'authorgrowth-pro', array( __CLASS__, 'render' ) );
 }
 public static function assets( $hook ) {
  if ( 'toplevel_page_authorgrowth-pro' === $hook ) { wp_enqueue_style( 'agp-admin', plugins_url( 'assets/admin.css', AGP_FILE ), array(), AGP_VERSION ); }
 }
 public static function render() {
  if ( ! current_user_can( 'edit_posts' ) ) { wp_die( esc_html__( 'You do not have permission to view this page.', 'authorgrowth-pro' ) ); }
  $counts = wp_count_posts( AGP_Books::TYPE, 'readable' );
  ?>
  <div class="wrap agp"><header class="agp-hero"><div><p class="agp-eyebrow"><?php esc_html_e( 'YOUR AUTHOR WORKSPACE', 'authorgrowth-pro' ); ?></p><h1>AuthorGrowth Pro</h1><p><?php esc_html_e( 'Great books deserve a remarkable home.', 'authorgrowth-pro' ); ?></p></div><a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=agp_book' ) ); ?>"><?php esc_html_e( 'Add a book', 'authorgrowth-pro' ); ?></a></header>
  <div class="agp-grid"><section class="agp-card"><h2><?php esc_html_e( 'Published books', 'authorgrowth-pro' ); ?></h2><p class="agp-number"><?php echo esc_html( number_format_i18n( (int) $counts->publish ) ); ?></p></section><section class="agp-card"><h2><?php esc_html_e( 'Draft books', 'authorgrowth-pro' ); ?></h2><p class="agp-number"><?php echo esc_html( number_format_i18n( (int) $counts->draft ) ); ?></p></section><section class="agp-card"><h2><?php esc_html_e( 'Scheduled releases', 'authorgrowth-pro' ); ?></h2><p class="agp-number"><?php echo esc_html( number_format_i18n( (int) $counts->future ) ); ?></p></section></div>
  <section class="agp-card"><h2><?php esc_html_e( 'Build your book collection', 'authorgrowth-pro' ); ?></h2><p><?php esc_html_e( 'Add your title and description, choose a cover using Featured image, and connect a retailer with a Buy Now link.', 'authorgrowth-pro' ); ?></p><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=agp_book' ) ); ?>"><?php esc_html_e( 'Manage books', 'authorgrowth-pro' ); ?></a></section>
  <section class="agp-card agp-roadmap"><h2><?php esc_html_e( 'Coming in later phases', 'authorgrowth-pro' ); ?></h2><p><?php esc_html_e( 'Reader leads, chapter downloads, promotion tools, click analytics, launches, reviews, and branding settings are not included in this Phase 1 release.', 'authorgrowth-pro' ); ?></p></section></div>
  <?php
 }
}
