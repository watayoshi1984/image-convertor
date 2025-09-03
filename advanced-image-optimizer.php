<?php
/**
 * Plugin Name: Advanced Image Optimizer Pro
 * Plugin URI: https://example.com/advanced-image-optimizer
 * Description: High-performance WordPress image optimization plugin with WebP and AVIF support. Automatically converts and optimizes images for better performance and SEO.
 * Version: 1.0.0
 * Author: Plugin Developer
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: advanced-image-optimizer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package AdvancedImageOptimizer
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
define('ADVANCED_IMAGE_OPTIMIZER_VERSION', '1.0.0');
define('ADVANCED_IMAGE_OPTIMIZER_PLUGIN_FILE', __FILE__);
define('ADVANCED_IMAGE_OPTIMIZER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADVANCED_IMAGE_OPTIMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ADVANCED_IMAGE_OPTIMIZER_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ADVANCED_IMAGE_OPTIMIZER_TEXT_DOMAIN', 'advanced-image-optimizer');
define('ADVANCED_IMAGE_OPTIMIZER_MIN_PHP_VERSION', '7.4');
define('ADVANCED_IMAGE_OPTIMIZER_MIN_WP_VERSION', '5.8');

// Define plugin paths
define('ADVANCED_IMAGE_OPTIMIZER_INCLUDES_DIR', ADVANCED_IMAGE_OPTIMIZER_PLUGIN_DIR . 'includes/');
define('ADVANCED_IMAGE_OPTIMIZER_ASSETS_DIR', ADVANCED_IMAGE_OPTIMIZER_PLUGIN_DIR . 'assets/');
define('ADVANCED_IMAGE_OPTIMIZER_BIN_DIR', ADVANCED_IMAGE_OPTIMIZER_PLUGIN_DIR . 'bin/');
define('ADVANCED_IMAGE_OPTIMIZER_LANGUAGES_DIR', ADVANCED_IMAGE_OPTIMIZER_PLUGIN_DIR . 'languages/');

// Define plugin URLs
define('ADVANCED_IMAGE_OPTIMIZER_ASSETS_URL', ADVANCED_IMAGE_OPTIMIZER_PLUGIN_URL . 'assets/');
define('ADVANCED_IMAGE_OPTIMIZER_JS_URL', ADVANCED_IMAGE_OPTIMIZER_ASSETS_URL . 'js/');
define('ADVANCED_IMAGE_OPTIMIZER_CSS_URL', ADVANCED_IMAGE_OPTIMIZER_ASSETS_URL . 'css/');
define('ADVANCED_IMAGE_OPTIMIZER_IMAGES_URL', ADVANCED_IMAGE_OPTIMIZER_ASSETS_URL . 'images/');

/**
 * Check system requirements before plugin activation
 */
function advanced_image_optimizer_check_requirements() {
    $errors = [];
    
    // Check PHP version
    if (version_compare(PHP_VERSION, ADVANCED_IMAGE_OPTIMIZER_MIN_PHP_VERSION, '<')) {
        $errors[] = sprintf(
            __('Advanced Image Optimizer requires PHP %s or higher. You are running PHP %s.', 'advanced-image-optimizer'),
            ADVANCED_IMAGE_OPTIMIZER_MIN_PHP_VERSION,
            PHP_VERSION
        );
    }
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, ADVANCED_IMAGE_OPTIMIZER_MIN_WP_VERSION, '<')) {
        $errors[] = sprintf(
            __('Advanced Image Optimizer requires WordPress %s or higher. You are running WordPress %s.', 'advanced-image-optimizer'),
            ADVANCED_IMAGE_OPTIMIZER_MIN_WP_VERSION,
            $wp_version
        );
    }
    
    // Check required PHP extensions
    $required_extensions = ['gd', 'exif', 'fileinfo'];
    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            $errors[] = sprintf(
                __('Advanced Image Optimizer requires the PHP %s extension to be installed and enabled.', 'advanced-image-optimizer'),
                $extension
            );
        }
    }
    
    return $errors;
}

/**
 * Plugin activation hook
 */
function advanced_image_optimizer_activate() {
    // Check system requirements
    $errors = advanced_image_optimizer_check_requirements();
    if (!empty($errors)) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<h1>' . __('Plugin Activation Error', 'advanced-image-optimizer') . '</h1>' .
            '<p>' . implode('</p><p>', $errors) . '</p>',
            __('Plugin Activation Error', 'advanced-image-optimizer'),
            ['back_link' => true]
        );
    }
    
    // Set default options
    $default_options = [
        'version' => ADVANCED_IMAGE_OPTIMIZER_VERSION,
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
    
    add_option('advanced_image_optimizer_options', $default_options);
    
    // Create upload directories
    $upload_dir = wp_upload_dir();
    $optimizer_dir = $upload_dir['basedir'] . '/advanced-image-optimizer';
    $backup_dir = $optimizer_dir . '/backups';
    $cache_dir = $optimizer_dir . '/cache';
    
    wp_mkdir_p($optimizer_dir);
    wp_mkdir_p($backup_dir);
    wp_mkdir_p($cache_dir);
    
    // Create .htaccess for security
    $htaccess_content = "# Advanced Image Optimizer Security\nOptions -Indexes\n<Files *.php>\nOrder allow,deny\nDeny from all\n</Files>";
    file_put_contents($optimizer_dir . '/.htaccess', $htaccess_content);
    
    // Schedule cleanup task
    if (!wp_next_scheduled('advanced_image_optimizer_cleanup')) {
        wp_schedule_event(time(), 'daily', 'advanced_image_optimizer_cleanup');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook
 */
function advanced_image_optimizer_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('advanced_image_optimizer_cleanup');
    wp_clear_scheduled_hook('advanced_image_optimizer_batch_process');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin uninstall hook
 */
function advanced_image_optimizer_uninstall() {
    // Remove options
    delete_option('advanced_image_optimizer_options');
    delete_option('advanced_image_optimizer_stats');
    delete_option('advanced_image_optimizer_queue');
    
    // Remove transients
    delete_transient('advanced_image_optimizer_system_info');
    delete_transient('advanced_image_optimizer_license_check');
    
    // Clear scheduled events
    wp_clear_scheduled_hook('advanced_image_optimizer_cleanup');
    wp_clear_scheduled_hook('advanced_image_optimizer_batch_process');
    
    // Remove upload directories (optional - user choice)
    $remove_data = get_option('advanced_image_optimizer_remove_data_on_uninstall', false);
    if ($remove_data) {
        $upload_dir = wp_upload_dir();
        $optimizer_dir = $upload_dir['basedir'] . '/advanced-image-optimizer';
        
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
register_activation_hook(__FILE__, 'advanced_image_optimizer_activate');
register_deactivation_hook(__FILE__, 'advanced_image_optimizer_deactivate');
register_uninstall_hook(__FILE__, 'advanced_image_optimizer_uninstall');

// Load Composer autoloader
if (file_exists(ADVANCED_IMAGE_OPTIMIZER_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once ADVANCED_IMAGE_OPTIMIZER_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Initialize the plugin
 */
function advanced_image_optimizer_init() {
    // Check requirements on every load
    $errors = advanced_image_optimizer_check_requirements();
    if (!empty($errors)) {
        add_action('admin_notices', function() use ($errors) {
            echo '<div class="notice notice-error"><p>' . implode('</p><p>', $errors) . '</p></div>';
        });
        return;
    }
    
    // Load text domain
    load_plugin_textdomain(
        'advanced-image-optimizer',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
    
    // Initialize main plugin class
    if (class_exists('AdvancedImageOptimizer\Core\Plugin')) {
        $plugin = new AdvancedImageOptimizer\Core\Plugin();
        $plugin->init();
    }
}

// Initialize plugin after WordPress is fully loaded
add_action('plugins_loaded', 'advanced_image_optimizer_init');

/**
 * Add plugin action links
 */
function advanced_image_optimizer_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=advanced-image-optimizer') . '">' . __('Settings', 'advanced-image-optimizer') . '</a>';
    $pro_link = '<a href="https://example.com/advanced-image-optimizer-pro" target="_blank" style="color: #d54e21; font-weight: bold;">' . __('Go Pro', 'advanced-image-optimizer') . '</a>';
    
    array_unshift($links, $settings_link, $pro_link);
    
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'advanced_image_optimizer_plugin_action_links');

/**
 * Add plugin meta links
 */
function advanced_image_optimizer_plugin_row_meta($links, $file) {
    if (plugin_basename(__FILE__) === $file) {
        $meta_links = [
            '<a href="https://example.com/advanced-image-optimizer/docs" target="_blank">' . __('Documentation', 'advanced-image-optimizer') . '</a>',
            '<a href="https://example.com/support" target="_blank">' . __('Support', 'advanced-image-optimizer') . '</a>',
            '<a href="https://example.com/advanced-image-optimizer/changelog" target="_blank">' . __('Changelog', 'advanced-image-optimizer') . '</a>'
        ];
        
        $links = array_merge($links, $meta_links);
    }
    
    return $links;
}
add_filter('plugin_row_meta', 'advanced_image_optimizer_plugin_row_meta', 10, 2);

/**
 * Emergency deactivation function for debugging
 */
if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['advanced_image_optimizer_emergency_deactivate'])) {
    deactivate_plugins(plugin_basename(__FILE__));
    wp_redirect(admin_url('plugins.php?deactivate=true'));
    exit;
}