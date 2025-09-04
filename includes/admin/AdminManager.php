<?php
/**
 * Admin Manager Class
 *
 * @package WyoshiImageOptimizer\Admin
 * @since 1.0.0
 */

namespace WyoshiImageOptimizer\Admin;

use WyoshiImageOptimizer\Common\Logger;
use WyoshiImageOptimizer\Common\Utils;
use WyoshiImageOptimizer\Processing\ImageProcessor;

/**
 * Admin Manager Class
 *
 * Handles WordPress admin functionality
 *
 * @since 1.0.0
 */
class AdminManager {

    /**
     * Logger instance
     *
     * @var Logger
     */
    private $logger;

    /**
     * Image processor instance
     *
     * @var ImageProcessor
     */
    private $image_processor;

    /**
     * Plugin options
     *
     * @var array
     */
    private $options;

    /**
     * Constructor
     *
     * @param Logger $logger Logger instance
     * @param ImageProcessor $image_processor Image processor instance
     */
    public function __construct(Logger $logger, ImageProcessor $image_processor) {
        $this->logger = $logger;
        $this->image_processor = $image_processor;
        $this->options = get_option('wyoshi_img_opt_options', []);
    }

    /**
     * Initialize admin functionality
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_notices', [$this, 'show_admin_notices']);
        
        // Media library integration
        add_filter('attachment_fields_to_edit', [$this, 'add_attachment_fields'], 10, 2);
        add_filter('attachment_fields_to_save', [$this, 'save_attachment_fields'], 10, 2);
        add_action('add_meta_boxes_attachment', [$this, 'add_attachment_meta_boxes']);
        
        // Bulk actions
        add_filter('bulk_actions-upload', [$this, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-upload', [$this, 'handle_bulk_actions'], 10, 3);
        
        // AJAX handlers
        add_action('wp_ajax_wyoshi_img_opt_optimize_image', [$this, 'ajax_optimize_image']);
        add_action('wp_ajax_wyoshi_img_opt_bulk_optimize', [$this, 'ajax_bulk_optimize']);
        add_action('wp_ajax_wyoshi_img_opt_get_stats', [$this, 'ajax_get_stats']);
        add_action('wp_ajax_wyoshi_img_opt_test_binaries', [$this, 'ajax_test_binaries']);
        add_action('wp_ajax_wyoshi_img_opt_clear_logs', [$this, 'ajax_clear_logs']);
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Wyoshi Image Optimizer', 'wyoshi-image-optimizer'),
            __('Image Optimizer', 'wyoshi-image-optimizer'),
            'manage_options',
            'wyoshi-img-opt',
            [$this, 'render_main_page'],
            'dashicons-format-image',
            30
        );

        // Settings submenu
        add_submenu_page(
            'wyoshi-img-opt',
            __('Settings', 'wyoshi-image-optimizer'),
            __('Settings', 'wyoshi-image-optimizer'),
            'manage_options',
            'wyoshi-img-opt-settings',
            [$this, 'render_settings_page']
        );

        // Statistics submenu
        add_submenu_page(
            'wyoshi-img-opt',
            __('Statistics', 'wyoshi-image-optimizer'),
            __('Statistics', 'wyoshi-image-optimizer'),
            'manage_options',
            'wyoshi-img-opt-stats',
            [$this, 'render_stats_page']
        );

        // System Info submenu
        add_submenu_page(
            'wyoshi-img-opt',
            __('System Info', 'wyoshi-image-optimizer'),
            __('System Info', 'wyoshi-image-optimizer'),
            'manage_options',
            'wyoshi-img-opt-system',
            [$this, 'render_system_page']
        );

        // Logs submenu
        add_submenu_page(
            'wyoshi-img-opt',
            __('Logs', 'wyoshi-image-optimizer'),
            __('Logs', 'wyoshi-image-optimizer'),
            'manage_options',
            'wyoshi-img-opt-logs',
            [$this, 'render_logs_page']
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'wyoshi_img_opt_options',
            'wyoshi_img_opt_options',
            [$this, 'sanitize_options']
        );

        // General settings section
        add_settings_section(
            'wyoshi_img_opt_general_section',
            __('General Settings', 'wyoshi-image-optimizer'),
            [$this, 'render_general_section'],
            'wyoshi-img-opt-settings'
        );

        // Quality settings section
        add_settings_section(
            'wyoshi_img_opt_quality_section',
            __('Quality Settings', 'wyoshi-image-optimizer'),
            [$this, 'render_quality_section'],
            'wyoshi-img-opt-settings'
        );

        // Advanced settings section
        add_settings_section(
            'wyoshi_img_opt_advanced_section',
            __('Advanced Settings', 'wyoshi-image-optimizer'),
            [$this, 'render_advanced_section'],
            'wyoshi-img-opt-settings'
        );

        $this->add_settings_fields();
    }

    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // General settings fields
        add_settings_field(
            'auto_optimize',
            __('Auto Optimize', 'wyoshi-image-optimizer'),
            [$this, 'render_checkbox_field'],
            'wyoshi-img-opt-settings',
            'wyoshi_img_opt_general_section',
            [
                'name' => 'auto_optimize',
                'description' => __('Automatically optimize images on upload', 'wyoshi-image-optimizer')
            ]
        );

        add_settings_field(
            'generate_webp',
            __('Generate WebP', 'wyoshi-image-optimizer'),
            [$this, 'render_checkbox_field'],
            'wyoshi-img-opt-settings',
            'wyoshi_img_opt_general_section',
            [
                'name' => 'generate_webp',
                'description' => __('Generate WebP versions of images', 'advanced-image-optimizer')
            ]
        );

        add_settings_field(
            'generate_avif',
            __('Generate AVIF', 'wyoshi-image-optimizer'),
            [$this, 'render_checkbox_field'],
            'wyoshi-img-opt-settings',
            'wyoshi_img_opt_general_section',
            [
                'name' => 'generate_avif',
                'description' => __('Generate AVIF versions of images (Pro feature)', 'wyoshi-image-optimizer')
            ]
        );

        add_settings_field(
            'backup_original',
            __('Backup Original', 'wyoshi-image-optimizer'),
            [$this, 'render_checkbox_field'],
            'wyoshi-img-opt-settings',
            'wyoshi_img_opt_general_section',
            [
                'name' => 'backup_original',
                'description' => __('Keep backup of original images', 'wyoshi-image-optimizer')
            ]
        );

        // Quality settings fields
        add_settings_field(
            'webp_quality',
            __('WebP Quality', 'wyoshi-image-optimizer'),
            [$this, 'render_number_field'],
            'wyoshi-img-opt-settings',
            'wyoshi_img_opt_quality_section',
            [
                'name' => 'webp_quality',
                'min' => 1,
                'max' => 100,
                'default' => 80,
                'description' => __('WebP compression quality (1-100)', 'wyoshi-image-optimizer')
            ]
        );

        add_settings_field(
            'avif_quality',
            __('AVIF Quality', 'wyoshi-image-optimizer'),
            [$this, 'render_number_field'],
            'wyoshi-img-opt-settings',
            'wyoshi_img_opt_quality_section',
            [
                'name' => 'avif_quality',
                'min' => 1,
                'max' => 100,
                'default' => 70,
                'description' => __('AVIF compression quality (1-100)', 'wyoshi-image-optimizer')
            ]
        );

        add_settings_field(
            'jpeg_quality',
            __('JPEG Quality', 'wyoshi-image-optimizer'),
            [$this, 'render_number_field'],
            'wyoshi-img-opt-settings',
            'wyoshi_img_opt_quality_section',
            [
                'name' => 'jpeg_quality',
                'min' => 1,
                'max' => 100,
                'default' => 85,
                'description' => __('JPEG compression quality (1-100)', 'wyoshi-image-optimizer')
            ]
        );

        // Advanced settings fields
        add_settings_field(
            'max_width',
            __('Max Width', 'wyoshi-image-optimizer'),
            [$this, 'render_number_field'],
            'wyoshi-img-opt-settings',
            'wyoshi_img_opt_advanced_section',
            [
                'name' => 'max_width',
                'min' => 0,
                'max' => 10000,
                'default' => 0,
                'description' => __('Maximum image width (0 = no limit)', 'wyoshi-image-optimizer')
            ]
        );

        add_settings_field(
            'max_height',
            __('Max Height', 'wyoshi-image-optimizer'),
            [$this, 'render_number_field'],
            'wyoshi-img-opt-settings',
            'wyoshi_img_opt_advanced_section',
            [
                'name' => 'max_height',
                'min' => 0,
                'max' => 10000,
                'default' => 0,
                'description' => __('Maximum image height (0 = no limit)', 'wyoshi-image-optimizer')
            ]
        );

        add_settings_field(
            'enable_logging',
            __('Enable Logging', 'wyoshi-image-optimizer'),
            [$this, 'render_checkbox_field'],
            'wyoshi-img-opt-settings',
            'wyoshi_img_opt_advanced_section',
            [
                'name' => 'enable_logging',
                'description' => __('Enable detailed logging for debugging', 'wyoshi-image-optimizer')
            ]
        );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'advanced-image-optimizer') === false && $hook !== 'upload.php') {
            return;
        }

        wp_enqueue_script(
            'aio-admin-js',
            WYOSHI_IMG_OPT_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            WYOSHI_IMG_OPT_VERSION,
            true
        );

        wp_enqueue_style(
            'aio-admin-css',
            WYOSHI_IMG_OPT_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WYOSHI_IMG_OPT_VERSION
        );

        // Localize script
        wp_localize_script('aio-admin-js', 'aioAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aio_admin_nonce'),
            'strings' => [
                'optimizing' => __('Optimizing...', 'advanced-image-optimizer'),
                'optimized' => __('Optimized', 'advanced-image-optimizer'),
                'error' => __('Error', 'advanced-image-optimizer'),
                'confirm_bulk' => __('Are you sure you want to optimize all selected images?', 'advanced-image-optimizer'),
                'processing' => __('Processing...', 'advanced-image-optimizer')
            ]
        ]);
    }

    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        // Check for missing binaries
        $binary_wrapper = $this->image_processor->get_binary_wrapper();
        $missing_binaries = [];
        
        $required_binaries = ['cwebp'];
        foreach ($required_binaries as $binary) {
            if (!$binary_wrapper->is_binary_available($binary)) {
                $missing_binaries[] = $binary;
            }
        }

        if (!empty($missing_binaries)) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>' . sprintf(
                __('Advanced Image Optimizer: Missing required binaries: %s. Some features may not work properly.', 'advanced-image-optimizer'),
                implode(', ', $missing_binaries)
            ) . '</p>';
            echo '</div>';
        }

        // Check upload directory permissions
        $upload_dirs = Utils::get_plugin_upload_dirs();
        if (!is_writable($upload_dirs['base_dir'])) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . __('Advanced Image Optimizer: Upload directory is not writable. Please check file permissions.', 'advanced-image-optimizer') . '</p>';
            echo '</div>';
        }
    }

    /**
     * Render main admin page
     */
    public function render_main_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wyoshi-image-optimizer'));
        }
        
        $stats = Utils::get_optimization_stats();
        $system_info = Utils::get_system_info();
        $capabilities = $this->image_processor->test_capabilities();
        
        include WYOSHI_IMG_OPT_PLUGIN_DIR . 'includes/admin/views/main-page.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wyoshi-image-optimizer'));
        }
        include WYOSHI_IMG_OPT_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
    }

    /**
     * Render statistics page
     */
    public function render_stats_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wyoshi-image-optimizer'));
        }
        
        $stats = Utils::get_optimization_stats();
        include WYOSHI_IMG_OPT_PLUGIN_DIR . 'includes/admin/views/stats-page.php';
    }

    /**
     * Render system info page
     */
    public function render_system_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wyoshi-image-optimizer'));
        }
        
        $system_info = Utils::get_system_info();
        $capabilities = $this->image_processor->test_capabilities();
        include WYOSHI_IMG_OPT_PLUGIN_DIR . 'includes/admin/views/system-page.php';
    }

    /**
     * Render logs page
     */
    public function render_logs_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wyoshi-image-optimizer'));
        }
        
        $contexts = Logger::get_available_contexts();
        $current_context = $_GET['context'] ?? 'general';
        $logger = new Logger($current_context);
        $log_contents = $logger->get_log_contents(100); // Last 100 lines
        $log_stats = $logger->get_log_stats();
        
        include WYOSHI_IMG_OPT_PLUGIN_DIR . 'includes/admin/views/logs-page.php';
    }

    /**
     * Render settings section descriptions
     */
    public function render_general_section() {
        echo '<p>' . __('Configure general optimization settings.', 'wyoshi-image-optimizer') . '</p>';
    }

    public function render_quality_section() {
        echo '<p>' . __('Adjust quality settings for different image formats.', 'wyoshi-image-optimizer') . '</p>';
    }

    public function render_advanced_section() {
        echo '<p>' . __('Advanced configuration options.', 'wyoshi-image-optimizer') . '</p>';
    }

    /**
     * Render checkbox field
     *
     * @param array $args Field arguments
     */
    public function render_checkbox_field($args) {
        $name = $args['name'];
        $value = isset($this->options[$name]) ? $this->options[$name] : false;
        $description = $args['description'] ?? '';
        
        echo '<label>';
        echo '<input type="checkbox" name="wyoshi_img_opt_options[' . esc_attr($name) . ']" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . esc_html($description);
        echo '</label>';
    }

    /**
     * Render number field
     *
     * @param array $args Field arguments
     */
    public function render_number_field($args) {
        $name = $args['name'];
        $value = isset($this->options[$name]) ? $this->options[$name] : ($args['default'] ?? 0);
        $min = $args['min'] ?? 0;
        $max = $args['max'] ?? 100;
        $description = $args['description'] ?? '';
        
        echo '<input type="number" name="wyoshi_img_opt_options[' . esc_attr($name) . ']" value="' . esc_attr($value) . '" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" class="small-text" />';
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }

    /**
     * Sanitize options
     *
     * @param array $input Input options
     * @return array Sanitized options
     */
    public function sanitize_options($input) {
        $sanitized = [];
        
        // Boolean fields
        $boolean_fields = ['auto_optimize', 'generate_webp', 'generate_avif', 'backup_original', 'enable_logging'];
        foreach ($boolean_fields as $field) {
            $sanitized[$field] = isset($input[$field]) ? (bool) $input[$field] : false;
        }
        
        // Number fields with ranges
        $number_fields = [
            'webp_quality' => ['min' => 1, 'max' => 100, 'default' => 80],
            'avif_quality' => ['min' => 1, 'max' => 100, 'default' => 70],
            'jpeg_quality' => ['min' => 1, 'max' => 100, 'default' => 85],
            'max_width' => ['min' => 0, 'max' => 10000, 'default' => 0],
            'max_height' => ['min' => 0, 'max' => 10000, 'default' => 0]
        ];
        
        foreach ($number_fields as $field => $config) {
            $value = isset($input[$field]) ? intval($input[$field]) : $config['default'];
            $sanitized[$field] = max($config['min'], min($config['max'], $value));
        }
        
        return $sanitized;
    }

    /**
     * Add attachment fields to media library
     *
     * @param array $fields Existing fields
     * @param object $post Attachment post object
     * @return array Modified fields
     */
    public function add_attachment_fields($fields, $post) {
        if (!Utils::is_supported_image(get_attached_file($post->ID))) {
            return $fields;
        }

        $fields['aio_optimize'] = [
            'label' => __('Image Optimization', 'advanced-image-optimizer'),
            'input' => 'html',
            'html' => $this->render_attachment_optimization_field($post->ID)
        ];

        return $fields;
    }

    /**
     * Render attachment optimization field
     *
     * @param int $attachment_id Attachment ID
     * @return string HTML output
     */
    private function render_attachment_optimization_field($attachment_id) {
        $optimized = get_post_meta($attachment_id, '_aio_optimized', true);
        $webp_exists = get_post_meta($attachment_id, '_aio_webp_path', true);
        $avif_exists = get_post_meta($attachment_id, '_aio_avif_path', true);
        
        $html = '<div class="aio-attachment-field">';
        
        if ($optimized) {
            $html .= '<p><span class="dashicons dashicons-yes-alt" style="color: green;"></span> ' . __('Optimized', 'advanced-image-optimizer') . '</p>';
            
            if ($webp_exists) {
                $html .= '<p><span class="dashicons dashicons-format-image"></span> WebP version available</p>';
            }
            
            if ($avif_exists) {
                $html .= '<p><span class="dashicons dashicons-format-image"></span> AVIF version available</p>';
            }
        } else {
            $html .= '<p><span class="dashicons dashicons-warning" style="color: orange;"></span> ' . __('Not optimized', 'advanced-image-optimizer') . '</p>';
        }
        
        $html .= '<button type="button" class="button aio-optimize-single" data-attachment-id="' . $attachment_id . '">';
        $html .= __('Optimize Now', 'advanced-image-optimizer');
        $html .= '</button>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Save attachment fields
     *
     * @param array $post Post data
     * @param array $attachment Attachment data
     * @return array Post data
     */
    public function save_attachment_fields($post, $attachment) {
        // This method can be used to save custom attachment fields if needed
        return $post;
    }

    /**
     * Add attachment meta boxes
     *
     * @param object $post Attachment post object
     */
    public function add_attachment_meta_boxes($post) {
        if (!Utils::is_supported_image(get_attached_file($post->ID))) {
            return;
        }

        add_meta_box(
            'aio-attachment-info',
            __('Image Optimization Info', 'advanced-image-optimizer'),
            [$this, 'render_attachment_meta_box'],
            'attachment',
            'side',
            'default'
        );
    }

    /**
     * Render attachment meta box
     *
     * @param object $post Attachment post object
     */
    public function render_attachment_meta_box($post) {
        $file_path = get_attached_file($post->ID);
        $original_size = Utils::get_file_size($file_path);
        $optimized = get_post_meta($post->ID, '_aio_optimized', true);
        $optimization_data = get_post_meta($post->ID, '_aio_optimization_data', true);
        
        echo '<div class="aio-meta-box">';
        echo '<p><strong>' . __('Original Size:', 'advanced-image-optimizer') . '</strong> ' . Utils::format_file_size($original_size) . '</p>';
        
        if ($optimized && $optimization_data) {
            echo '<p><strong>' . __('Optimized Size:', 'advanced-image-optimizer') . '</strong> ' . Utils::format_file_size($optimization_data['optimized_size']) . '</p>';
            echo '<p><strong>' . __('Savings:', 'advanced-image-optimizer') . '</strong> ' . $optimization_data['compression_ratio'] . '%</p>';
            echo '<p><strong>' . __('Optimized:', 'advanced-image-optimizer') . '</strong> ' . date_i18n(get_option('date_format'), strtotime($optimization_data['date'])) . '</p>';
        }
        
        echo '</div>';
    }

    /**
     * Add bulk actions to media library
     *
     * @param array $actions Existing bulk actions
     * @return array Modified bulk actions
     */
    public function add_bulk_actions($actions) {
        $actions['aio_bulk_optimize'] = __('Optimize Images', 'wyoshi-image-optimizer');
        return $actions;
    }

    /**
     * Handle bulk actions
     *
     * @param string $redirect_to Redirect URL
     * @param string $doaction Action name
     * @param array $post_ids Selected post IDs
     * @return string Redirect URL
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction !== 'aio_bulk_optimize') {
            return $redirect_to;
        }

        $optimized_count = 0;
        foreach ($post_ids as $post_id) {
            if (wp_attachment_is_image($post_id)) {
                $result = $this->optimize_attachment($post_id);
                if ($result['success']) {
                    $optimized_count++;
                }
            }
        }

        $redirect_to = add_query_arg('aio_optimized', $optimized_count, $redirect_to);
        return $redirect_to;
    }

    /**
     * AJAX handler for single image optimization
     */
    public function ajax_optimize_image() {
        check_ajax_referer('aio_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wyoshi-image-optimizer'));
        }

        $attachment_id = intval($_POST['attachment_id']);
        $result = $this->optimize_attachment($attachment_id);
        
        wp_send_json($result);
    }

    /**
     * AJAX handler for bulk optimization
     */
    public function ajax_bulk_optimize() {
        check_ajax_referer('aio_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wyoshi-image-optimizer'));
        }

        $batch_size = 5; // Process 5 images at a time
        $offset = intval($_POST['offset'] ?? 0);
        
        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'meta_query' => [
                [
                    'key' => '_aio_optimized',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);

        $results = [];
        foreach ($attachments as $attachment) {
            $result = $this->optimize_attachment($attachment->ID);
            $results[] = [
                'id' => $attachment->ID,
                'title' => get_the_title($attachment->ID),
                'success' => $result['success'],
                'message' => $result['message'] ?? ''
            ];
        }

        wp_send_json([
            'success' => true,
            'results' => $results,
            'has_more' => count($attachments) === $batch_size
        ]);
    }

    /**
     * AJAX handler for getting statistics
     */
    public function ajax_get_stats() {
        check_ajax_referer('aio_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wyoshi-image-optimizer'));
        }
        
        $stats = Utils::get_optimization_stats();
        wp_send_json_success($stats);
    }

    /**
     * AJAX handler for testing binaries
     */
    public function ajax_test_binaries() {
        check_ajax_referer('aio_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wyoshi-image-optimizer'));
        }

        $capabilities = $this->image_processor->test_capabilities();
        wp_send_json_success($capabilities);
    }

    /**
     * AJAX handler for clearing logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer('aio_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'advanced-image-optimizer'));
        }

        $context = sanitize_text_field($_POST['context'] ?? 'general');
        $logger = new Logger($context);
        $success = $logger->clear_log();
        
        wp_send_json([
            'success' => $success,
            'message' => $success ? __('Log cleared successfully', 'wyoshi-image-optimizer') : __('Failed to clear log', 'wyoshi-image-optimizer')
        ]);
    }

    /**
     * Optimize single attachment
     *
     * @param int $attachment_id Attachment ID
     * @return array Optimization result
     */
    private function optimize_attachment($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        
        if (!Utils::is_supported_image($file_path)) {
            return [
                'success' => false,
                'message' => __('Unsupported image format', 'advanced-image-optimizer')
            ];
        }

        $operations = [];
        
        // Add optimization operation
        $operations[] = ['type' => 'optimize'];
        
        // Add WebP conversion if enabled
        if ($this->options['generate_webp'] ?? false) {
            $operations[] = ['type' => 'webp'];
        }
        
        // Add AVIF conversion if enabled (Pro feature)
        if ($this->options['generate_avif'] ?? false) {
            $operations[] = ['type' => 'avif'];
        }

        $result = $this->image_processor->process_image($file_path, $operations);
        
        if ($result['success']) {
            // Update attachment metadata
            update_post_meta($attachment_id, '_aio_optimized', true);
            update_post_meta($attachment_id, '_aio_optimization_data', [
                'original_size' => $result['original_size'],
                'optimized_size' => $result['original_size'] - $result['total_savings'],
                'compression_ratio' => Utils::calculate_compression_ratio($result['original_size'], $result['original_size'] - $result['total_savings']),
                'date' => current_time('mysql')
            ]);
            
            // Store paths to generated files
            foreach ($result['processed_files'] as $processed_file) {
                if ($processed_file['operation'] === 'webp') {
                    update_post_meta($attachment_id, '_aio_webp_path', $processed_file['output_path']);
                } elseif ($processed_file['operation'] === 'avif') {
                    update_post_meta($attachment_id, '_aio_avif_path', $processed_file['output_path']);
                }
            }
            
            // Update global statistics
            Utils::update_optimization_stats([
                'optimized_images' => (Utils::get_optimization_stats()['optimized_images'] ?? 0) + 1,
                'total_savings' => (Utils::get_optimization_stats()['total_savings'] ?? 0) + $result['total_savings']
            ]);
            
            return [
                'success' => true,
                'message' => sprintf(
                    __('Optimized successfully. Saved %s (%s%%)', 'advanced-image-optimizer'),
                    Utils::format_file_size($result['total_savings']),
                    Utils::calculate_compression_ratio($result['original_size'], $result['original_size'] - $result['total_savings'])
                )
            ];
        } else {
            return [
                'success' => false,
                'message' => implode(', ', $result['errors'])
            ];
        }
    }
}