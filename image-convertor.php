<?php
/**
 * Plugin Name: Image Convertor
 * Plugin URI: https://github.com/watayoshi1984/image-convertor.git
 * Description: 高性能な画像最適化プラグイン。WebP/AVIF変換、一括最適化、フロントエンド配信機能を提供します。
 * Version: 1.0.0
 * Author: Watayoshi1984
 * Author URI: hhttps://github.com/watayoshi1984/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: image-convertor
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package ImageConvertor
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IMAGE_CONVERTOR_VERSION', '1.0.0');
define('IMAGE_CONVERTOR_PLUGIN_FILE', __FILE__);
define('IMAGE_CONVERTOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMAGE_CONVERTOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IMAGE_CONVERTOR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Minimum requirements check
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Image Convertor:</strong> このプラグインにはPHP 7.4以上が必要です。現在のバージョン: ' . PHP_VERSION;
        echo '</p></div>';
    });
    return;
}

if (version_compare(get_bloginfo('version'), '5.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Image Convertor:</strong> このプラグインにはWordPress 5.0以上が必要です。';
        echo '</p></div>';
    });
    return;
}

// Load Composer autoloader if available
if (file_exists(IMAGE_CONVERTOR_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once IMAGE_CONVERTOR_PLUGIN_DIR . 'vendor/autoload.php';
}

// Load plugin classes
require_once IMAGE_CONVERTOR_PLUGIN_DIR . 'includes/common/Logger.php';
require_once IMAGE_CONVERTOR_PLUGIN_DIR . 'includes/common/Utils.php';
require_once IMAGE_CONVERTOR_PLUGIN_DIR . 'includes/processing/BinaryWrapper.php';
require_once IMAGE_CONVERTOR_PLUGIN_DIR . 'includes/processing/ImageProcessor.php';
require_once IMAGE_CONVERTOR_PLUGIN_DIR . 'includes/admin/AdminManager.php';
require_once IMAGE_CONVERTOR_PLUGIN_DIR . 'includes/Media/MediaManager.php';
require_once IMAGE_CONVERTOR_PLUGIN_DIR . 'includes/delivery/DeliveryManager.php';
require_once IMAGE_CONVERTOR_PLUGIN_DIR . 'includes/Pro/LicenseManager.php';
require_once IMAGE_CONVERTOR_PLUGIN_DIR . 'includes/Pro/AvifProcessor.php';
require_once IMAGE_CONVERTOR_PLUGIN_DIR . 'includes/Pro/ProManager.php';
require_once IMAGE_CONVERTOR_PLUGIN_DIR . 'includes/core/Plugin.php';

// Initialize plugin
function image_convertor_init() {
    $plugin = ImageConvertor\Core\Plugin::get_instance();
    $plugin->init();
}

// Hook into WordPress
add_action('plugins_loaded', 'image_convertor_init');

// Set welcome transient on activation
register_activation_hook(__FILE__, function() {
    set_transient('image_convertor_show_welcome', true, 60);
});

// Load text domain for internationalization
add_action('init', function() {
    load_plugin_textdomain('image-convertor', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Add plugin upgrade notice
add_action('in_plugin_update_message-' . plugin_basename(__FILE__), function($plugin_data, $response) {
    if (isset($response->upgrade_notice)) {
        echo '<div class="update-message">' . wp_kses_post($response->upgrade_notice) . '</div>';
    }
}, 10, 2);