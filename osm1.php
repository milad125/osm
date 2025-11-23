<?php
/**
 * Plugin Name: سیستم پیشرفته حمل و نقل ووکامرس
 * Plugin URI: https://example.com
 * Description: سیستم پیشرفته حمل و نقل بومی ووکامرس با اتصال به Tapin، Alopeyk، SnappBox و Tipax
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: osm1
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('OSM1_VERSION', '1.0.0');
define('OSM1_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OSM1_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OSM1_PLUGIN_FILE', __FILE__);
define('OSM1_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>';
        echo __('سیستم حمل و نقل پیشرفته نیاز به افزونه WooCommerce دارد.', 'osm1');
        echo '</p></div>';
    });
    return;
}

// Autoloader
require_once OSM1_PLUGIN_DIR . 'includes/class-autoloader.php';

// Initialize the plugin
OSM1_Autoloader::init();

// Activation hook
register_activation_hook(__FILE__, array('OSM1_Activator', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('OSM1_Deactivator', 'deactivate'));

// Initialize main plugin class
add_action('plugins_loaded', array('OSM1_Main', 'get_instance'), 10);

