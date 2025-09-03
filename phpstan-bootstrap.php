<?php
/**
 * PHPStan bootstrap file for WordPress plugin analysis
 * 
 * This file defines WordPress functions and constants that PHPStan needs
 * to understand during static analysis.
 */

// WordPress core functions
if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {}
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') { echo $text; }
}

if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') { return esc_html(__($text, $domain)); }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = 'default') { echo esc_html__($text, $domain); }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = 'default') { return esc_attr(__($text, $domain)); }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e($text, $domain = 'default') { echo esc_attr__($text, $domain); }
}

if (!function_exists('esc_url')) {
    function esc_url($url) { return $url; }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return $str; }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action = -1, $name = '_wpnonce', $referer = true, $echo = true) {}
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) { return true; }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) { return true; }
}

if (!function_exists('add_action')) {
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1) {}
}

if (!function_exists('add_filter')) {
    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1) {}
}

if (!function_exists('remove_action')) {
    function remove_action($hook_name, $callback, $priority = 10) {}
}

if (!function_exists('remove_filter')) {
    function remove_filter($hook_name, $callback, $priority = 10) {}
}

if (!function_exists('do_action')) {
    function do_action($hook_name, ...$args) {}
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook_name, $value, ...$args) { return $value; }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) { return $default; }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) { return true; }
}

if (!function_exists('delete_option')) {
    function delete_option($option) { return true; }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) { return dirname($file) . '/'; }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) { return 'http://example.com/wp-content/plugins/' . basename(dirname($file)) . '/'; }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) { return basename(dirname($file)) . '/' . basename($file); }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir($time = null, $create_dir = true, $refresh_cache = false) {
        return [
            'path' => '/path/to/uploads',
            'url' => 'http://example.com/wp-content/uploads',
            'subdir' => '',
            'basedir' => '/path/to/uploads',
            'baseurl' => 'http://example.com/wp-content/uploads',
            'error' => false
        ];
    }
}

if (!function_exists('wp_get_attachment_metadata')) {
    function wp_get_attachment_metadata($attachment_id, $unfiltered = false) { return []; }
}

if (!function_exists('wp_update_attachment_metadata')) {
    function wp_update_attachment_metadata($attachment_id, $data) { return true; }
}

if (!function_exists('get_attached_file')) {
    function get_attached_file($attachment_id, $unfiltered = false) { return ''; }
}

// WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', '/path/to/wordpress/');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', false);
}

// Plugin specific constants
if (!defined('ADVANCED_IMAGE_OPTIMIZER_VERSION')) {
    define('ADVANCED_IMAGE_OPTIMIZER_VERSION', '1.0.0');
}

if (!defined('ADVANCED_IMAGE_OPTIMIZER_PLUGIN_DIR')) {
    define('ADVANCED_IMAGE_OPTIMIZER_PLUGIN_DIR', __DIR__);
}

if (!defined('ADVANCED_IMAGE_OPTIMIZER_PLUGIN_URL')) {
    define('ADVANCED_IMAGE_OPTIMIZER_PLUGIN_URL', 'http://example.com/wp-content/plugins/advanced-image-optimizer/');
}