<?php
/**
 * Plugin Name: When I Work API Integration
 * Description: Connects WordPress to the When I Work API for employee scheduling.
 * Version: 1.0
 * Author: Web Integrated Solutions
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define constants
define('WIW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WIW_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once WIW_PLUGIN_DIR . 'includes/api-handler.php';
require_once WIW_PLUGIN_DIR . 'includes/shortcode-handler.php';
require_once WIW_PLUGIN_DIR . 'includes/settings-page.php';

// Activate the plugin
function wiw_activate() {
    add_option('wiw_api_key', '');
}
register_activation_hook(__FILE__, 'wiw_activate');

// Deactivate the plugin
function wiw_deactivate() {
    delete_option('wiw_api_key');
}
register_deactivation_hook(__FILE__, 'wiw_deactivate');
