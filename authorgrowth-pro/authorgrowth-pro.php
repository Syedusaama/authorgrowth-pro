<?php
/**
 * Plugin Name: AuthorGrowth Pro
 * Description: A professional book catalog and author marketing workspace.
 * Version: 0.4.0-rc.2
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: AuthorGrowth Pro
 * Text Domain: authorgrowth-pro
 * License: GPL-2.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'AGP_VERSION', '0.4.0-rc.2' );
define( 'AGP_FILE', __FILE__ );
require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-launches.php';
require_once __DIR__ . '/includes/class-reviews.php';
AGP_Settings::hooks();
AGP_Launches::hooks();
AGP_Reviews::hooks();
require_once __DIR__ . '/includes/class-books.php';
require_once __DIR__ . '/includes/class-dashboard.php';
add_action( 'init', array( 'AGP_Books', 'register' ) );
require_once __DIR__ . '/includes/class-leads.php';
require_once __DIR__ . '/includes/class-clicks.php';
require_once __DIR__ . '/includes/class-promotions.php';
AGP_Clicks::hooks();
AGP_Promotions::hooks();
AGP_Leads::hooks();
AGP_Books::hooks();
AGP_Dashboard::hooks();
register_activation_hook( __FILE__, function () { AGP_Books::register(); AGP_Leads::install(); AGP_Clicks::install(); AGP_Launches::install(); flush_rewrite_rules(); } );
register_deactivation_hook( __FILE__, function () { wp_clear_scheduled_hook( 'agp_leads_cleanup' ); flush_rewrite_rules(); } );
