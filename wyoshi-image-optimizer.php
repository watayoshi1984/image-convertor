<?php
/**
 * Plugin Name: Wyoshi Image Optimizer Pro
 * Plugin URI: https://example.com/wyoshi-image-optimizer
 * Description: High-performance WordPress image optimization plugin with WebP and AVIF support. Automatically converts and optimizes images for better performance and SEO.
 * Version: 1.0.0
 * Author: Plugin Developer
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wyoshi-image-optimizer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package WyoshiImageOptimizer
 * @version 1.0.0
 * @author Plugin Developer
 * @copyright 2024 Plugin Developer
 * @license GPL-2.0-or-later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WYOSHI_IMG_OPT_VERSION', '1.0.0');
define('WYOSHI_IMG_OPT_PLUGIN_FILE', __FILE__);
define('WYOSHI_IMG_OPT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WYOSHI_IMG_OPT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WYOSHI_IMG_OPT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WYOSHI_IMG_OPT_TEXT_DOMAIN', 'wyoshi-image-optimizer');
define('WYOSHI_IMG_OPT_MIN_PHP_VERSION', '7.4');
define('WYOSHI_IMG_OPT_MIN_WP_VERSION', '5.8');

// Define plugin paths
define('WYOSHI_IMG_OPT_INCLUDES_DIR', WYOSHI_IMG_OPT_PLUGIN_DIR . 'includes/');
define('WYOSHI_IMG_OPT_ASSETS_DIR', WYOSHI_IMG_OPT_PLUGIN_DIR . 'assets/');
define('WYOSHI_IMG_OPT_BIN_DIR', WYOSHI_IMG_OPT_PLUGIN_DIR . 'bin/');
define('WYOSHI_IMG_OPT_LANGUAGES_DIR', WYOSHI_IMG_OPT_PLUGIN_DIR . 'languages/');

// Define plugin URLs
define('WYOSHI_IMG_OPT_ASSETS_URL', WYOSHI_IMG_OPT_PLUGIN_URL . 'assets/');
define('WYOSHI_IMG_OPT_JS_URL', WYOSHI_IMG_OPT_ASSETS_URL . 'js/');
define('WYOSHI_IMG_OPT_CSS_URL', WYOSHI_IMG_OPT_ASSETS_URL . 'css/');
define('WYOSHI_IMG_OPT_IMAGES_URL', WYOSHI_IMG_OPT_ASSETS_URL . 'images/');

/**
 * Check system requirements before plugin activation
 */
function wyoshi_img_opt_check_requirements() {
    $errors = [];
    
    // Check PHP version
    if (version_compare(PHP_VERSION, WYOSHI_IMG_OPT_MIN_PHP_VERSION, '<')) {
        $errors[] = sprintf(
            __('Wyoshi Image Optimizer Pro requires PHP %s or higher. You are running PHP %s.', 'wyoshi-image-optimizer'),
            WYOSHI_IMG_OPT_MIN_PHP_VERSION,
            PHP_VERSION
        );
    }
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, WYOSHI_IMG_OPT_MIN_WP_VERSION, '<')) {
        $errors[] = sprintf(
            __('Wyoshi Image Optimizer Pro requires WordPress %s or higher. You are running WordPress %s.', 'wyoshi-image-optimizer'),
            WYOSHI_IMG_OPT_MIN_WP_VERSION,
            $wp_version
        );
    }
    
    // Check required PHP extensions
    $required_extensions = ['gd', 'exif', 'fileinfo'];
    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            $errors[] = sprintf(
                __('Wyoshi Image Optimizer Pro requires the PHP %s extension to be installed and enabled.', 'wyoshi-image-optimizer'),
                $extension
            );
        }
    }
    
    return $errors;
}

/**
 * Plugin activation hook
 */
function wyoshi_img_opt_activate() {
    // Check system requirements
    $errors = wyoshi_img_opt_check_requirements();
    if (!empty($errors)) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>' . __('Plugin Activation Error', 'wyoshi-image-optimizer') . '</h1>' .
            '<p>' . implode('</p><p>', $errors) . '</p>',
            __('Plugin Activation Error', 'wyoshi-image-optimizer'),
            ['back_link' => true]
        );
    }
    
    // Set default options
    $default_options = [
        'version' => WYOSHI_IMG_OPT_VERSION,
        'webp_enabled' => true,
        'avif_enabled' => false, // Pro feature
        'quality_jpeg' => 85,
        'quality_png' => 90,
        'quality_webp' => 80,
        'quality_avif' => 75,
        'auto_optimize' => true,
        'backup_originals' => true,
        'max_width' => 2048,
        'max_height' => 2048,
        'progressive_jpeg' => true,
        'strip_metadata' => true,
        'delivery_method' => 'picture_tag',
        'lazy_loading' => true,
        'cdn_enabled' => false,
        'license_key' => '',
        'license_status' => 'inactive'
    ];
    
    add_option('wyoshi_img_opt_options', $default_options);
    
    // Create upload directories
    $upload_dir = wp_upload_dir();
    $optimizer_dir = $upload_dir['basedir'] . '/wyoshi-image-optimizer';
    $backup_dir = $optimizer_dir . '/backups';
    $cache_dir = $optimizer_dir . '/cache';
    
    wp_mkdir_p($optimizer_dir);
    wp_mkdir_p($backup_dir);
    wp_mkdir_p($cache_dir);
    
    // Create .htaccess for security
    $htaccess_content = "# Wyoshi Image Optimizer Security\nOptions -Indexes\n<Files *.php>\nOrder allow,deny\nDeny from all\n</Files>";
    file_put_contents($optimizer_dir . '/.htaccess', $htaccess_content);
    
    // Schedule cleanup task
    if (!wp_next_scheduled('wyoshi_img_opt_cleanup')) {
        wp_schedule_event(time(), 'daily', 'wyoshi_img_opt_cleanup');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook
 */
function wyoshi_img_opt_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('wyoshi_img_opt_cleanup');
    wp_clear_scheduled_hook('wyoshi_img_opt_batch_process');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin uninstall hook
 */
function wyoshi_img_opt_uninstall() {
    // Remove options
    delete_option('wyoshi_img_opt_options');
    delete_option('wyoshi_img_opt_stats');
    delete_option('wyoshi_img_opt_queue');
    
    // Remove transients
    delete_transient('wyoshi_img_opt_system_info');
    delete_transient('wyoshi_img_opt_license_check');
    
    // Clear scheduled events
    wp_clear_scheduled_hook('wyoshi_img_opt_cleanup');
    wp_clear_scheduled_hook('wyoshi_img_opt_batch_process');
    
    // Remove upload directories (optional - user choice)
    $remove_data = get_option('wyoshi_img_opt_remove_data_on_uninstall', false);
    if ($remove_data) {
        $upload_dir = wp_upload_dir();
        $optimizer_dir = $upload_dir['basedir'] . '/wyoshi-image-optimizer';
        
        if (is_dir($optimizer_dir)) {
            // Recursively remove directory
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($optimizer_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }
            
            rmdir($optimizer_dir);
        }
    }
}

// Register hooks
register_activation_hook(__FILE__, 'wyoshi_img_opt_activate');
register_deactivation_hook(__FILE__, 'wyoshi_img_opt_deactivate');
register_uninstall_hook(__FILE__, 'wyoshi_img_opt_uninstall');

// Load autoloader
require_once WYOSHI_IMG_OPT_PLUGIN_DIR . 'autoload.php';

// Load Composer autoloader if available
if (file_exists(WYOSHI_IMG_OPT_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once WYOSHI_IMG_OPT_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Initialize the plugin
 */
function wyoshi_img_opt_init() {
    // Prevent multiple initializations
    static $initialized = false;
    if ($initialized) {
        return;
    }
    
    // Check requirements on every load
    $errors = wyoshi_img_opt_check_requirements();
    if (!empty($errors)) {
        add_action('admin_notices', function() use ($errors) {
            echo '<div class="notice notice-error"><p>' . implode('</p><p>', $errors) . '</p></div>';
        });
        return;
    }
    
    // Load text domain
    load_plugin_textdomain(
        'wyoshi-image-optimizer',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
    
    // Initialize main plugin class
    if (class_exists('WyoshiImageOptimizer\Plugin')) {
        $plugin = WyoshiImageOptimizer\Plugin::get_instance();
        $plugin->init();
        $initialized = true;
    }
}

// Initialize plugin after WordPress is fully loaded
add_action('plugins_loaded', 'wyoshi_img_opt_init');

/**
 * Add plugin action links
 */
function wyoshi_img_opt_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wyoshi-img-opt-settings') . '">' . __('Settings', 'wyoshi-image-optimizer') . '</a>';
    $pro_link = '<a href="https://example.com/wyoshi-image-optimizer-pro" target="_blank" style="color: #d54e21; font-weight: bold;">' . __('Go Pro', 'wyoshi-image-optimizer') . '</a>';
    
    array_unshift($links, $settings_link, $pro_link);
    
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wyoshi_img_opt_plugin_action_links');

/**
 * Add plugin meta links
 */
function wyoshi_img_opt_plugin_row_meta($links, $file) {
    if (plugin_basename(__FILE__) === $file) {
        $meta_links = [
            '<a href="https://example.com/wyoshi-image-optimizer/docs" target="_blank">' . __('Documentation', 'wyoshi-image-optimizer') . '</a>',
            '<a href="https://example.com/support" target="_blank">' . __('Support', 'wyoshi-image-optimizer') . '</a>',
            '<a href="https://example.com/wyoshi-image-optimizer/changelog" target="_blank">' . __('Changelog', 'wyoshi-image-optimizer') . '</a>'
        ];
        
        $links = array_merge($links, $meta_links);
    }
    
    return $links;
}
add_filter('plugin_row_meta', 'wyoshi_img_opt_plugin_row_meta', 10, 2);

/**
 * Emergency deactivation function for debugging
 */
if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['wyoshi_img_opt_emergency_deactivate'])) {
    deactivate_plugins(plugin_basename(__FILE__));
    wp_redirect(admin_url('plugins.php?deactivate=true'));
    exit;
}