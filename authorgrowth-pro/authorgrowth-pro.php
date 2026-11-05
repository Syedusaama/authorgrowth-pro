<?php
/**
 * Plugin Name: AuthorGrowth Pro
 * Description: A professional book catalog and author marketing workspace.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: AuthorGrowth Pro
 * Text Domain: authorgrowth-pro
 * License: GPL-2.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'AGP_VERSION', '0.1.0' );
define( 'AGP_FILE', __FILE__ );
require_once __DIR__ . '/includes/class-books.php';
require_once __DIR__ . '/includes/class-dashboard.php';
add_action( 'init', array( 'AGP_Books', 'register' ) );
AGP_Books::hooks();
AGP_Dashboard::hooks();
register_activation_hook( __FILE__, function () { AGP_Books::register(); flush_rewrite_rules(); } );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
