<?php
/**
 * Main Plugin Class
 * 
 * Orchestrates all plugin components and handles initialization
 * 
 * @package ImageConvertor
 * @subpackage Core
 * @since 1.0.0
 */

namespace ImageConvertor\Core;

use AdvancedImageOptimizer\Processing\BinaryWrapper;
use AdvancedImageOptimizer\Processing\ImageProcessor;
use AdvancedImageOptimizer\Admin\AdminManager;
use ImageConvertor\Media\MediaManager;
use ImageConvertor\Delivery\DeliveryManager;
use AdvancedImageOptimizer\Common\Utils;
use ImageConvertor\Pro\ProManager;
use AdvancedImageOptimizer\Common\Logger;

class Plugin {
    
    /**
     * Plugin instance
     * 
     * @var Plugin
     */
    private static $instance = null;
    
    /**
     * Logger instance
     * 
     * @var Logger
     */
    private $logger;
    
    /**
     * Binary wrapper instance
     * 
     * @var BinaryWrapper
     */
    private $binary_wrapper;
    
    /**
     * Image processor instance
     * 
     * @var ImageProcessor
     */
    private $image_processor;
    
    /**
     * Admin manager instance
     * 
     * @var AdminManager
     */
    private $admin_manager;
    
    /**
     * Media manager instance
     * 
     * @var MediaManager
     */
    private $media_manager;
    
    /**
     * Delivery manager instance
     * 
     * @var DeliveryManager
     */
    private $delivery_manager;
    
    /**
     * Pro manager instance
     * 
     * @var ProManager
     */
    private $pro_manager;
    
    /**
     * Plugin options
     * 
     * @var array
     */
    private $options;
    
    /**
     * Plugin initialized flag
     * 
     * @var bool
     */
    private $initialized = false;
    
    /**
     * Get plugin instance
     * 
     * @return Plugin
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Load options
        $this->options = get_option('image_convertor_options', []);
        
        // Initialize logger first
        $this->logger = new Logger();
    }
    
    /**
     * Initialize the plugin
     * 
     * @return void
     */
    public function init() {
        if ($this->initialized) {
            return;
        }
        
        try {
            // Initialize core components
            $this->init_core_components();
            
            // Initialize managers
            $this->init_managers();
            
            // Register hooks
            $this->register_hooks();
            
            // Mark as initialized
            $this->initialized = true;
            
            $this->logger->info('Plugin initialized successfully', [
                'version' => IMAGE_CONVERTOR_VERSION,
                'php_version' => PHP_VERSION,
                'wp_version' => get_bloginfo('version')
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Plugin initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Show admin notice
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>Image Convertor:</strong> 初期化に失敗しました: ' . esc_html($e->getMessage());
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Initialize core components
     * 
     * @return void
     */
    private function init_core_components() {
        // Initialize binary wrapper
        $this->binary_wrapper = new BinaryWrapper($this->logger);
        
        // Initialize image processor (expects Logger and optional options)
        $this->image_processor = new ImageProcessor($this->logger, $this->options);
    }
    
    /**
     * Initialize managers
     * 
     * @return void
     */
    private function init_managers() {
        // Initialize admin manager
        if (is_admin()) {
            $this->admin_manager = new AdminManager($this->logger, $this->image_processor);
            $this->admin_manager->init();
        }
        
        // Initialize media manager
        $this->media_manager = new MediaManager($this->image_processor, $this->logger);
        $this->media_manager->init();
        
        // Initialize delivery manager
        if (!is_admin()) {
            $this->delivery_manager = new DeliveryManager($this->logger);
            $this->delivery_manager->init();
        }
        
        // Initialize Pro manager
        $this->pro_manager = new ProManager($this->logger, $this->binary_wrapper, $this->image_processor);
        $this->pro_manager->init();
    }
    
    /**
     * Register WordPress hooks
     * 
     * @return void
     */
    private function register_hooks() {
        // Activation/deactivation hooks
        register_activation_hook(IMAGE_CONVERTOR_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(IMAGE_CONVERTOR_PLUGIN_FILE, [$this, 'deactivate']);
        
        // Uninstall hook
        register_uninstall_hook(IMAGE_CONVERTOR_PLUGIN_FILE, [__CLASS__, 'uninstall']);
        
        // Plugin action links
        add_filter('plugin_action_links_' . plugin_basename(IMAGE_CONVERTOR_PLUGIN_FILE), [$this, 'add_action_links']);
        
        // Plugin row meta
        add_filter('plugin_row_meta', [$this, 'add_row_meta'], 10, 2);
        
        // Admin notices
        add_action('admin_notices', [$this, 'show_admin_notices']);
        
        // AJAX handlers for non-admin users
        if (!is_admin()) {
            add_action('wp_ajax_nopriv_image_convertor_serve', [$this, 'ajax_serve_image']);
        }
        
        // Cron hooks
        add_action('image_convertor_cleanup', [$this, 'cleanup_old_files']);
        
        // Schedule cleanup if not already scheduled
        if (!wp_next_scheduled('image_convertor_cleanup')) {
            wp_schedule_event(time(), 'daily', 'image_convertor_cleanup');
        }
    }
    
    /**
     * Plugin activation
     * 
     * @return void
     */
    public function activate() {
        try {
            // Create default options
            $default_options = $this->get_default_options();
            add_option('image_convertor_options', $default_options);
            
            // Create necessary directories
            $this->create_directories();
            
            // Create database tables if needed
            $this->create_database_tables();
            
            // Set up cron jobs
            $this->setup_cron_jobs();
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            $this->logger->info('Plugin activated successfully', [
                'version' => IMAGE_CONVERTOR_VERSION
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Plugin activation failed', [
                'error' => $e->getMessage()
            ]);
            
            wp_die('Image Convertor activation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Plugin deactivation
     * 
     * @return void
     */
    public function deactivate() {
        try {
            // Clear scheduled cron jobs
            wp_clear_scheduled_hook('image_convertor_cleanup');
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            $this->logger->info('Plugin deactivated successfully');
            
        } catch (Exception $e) {
            $this->logger->error('Plugin deactivation failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Plugin uninstall
     * 
     * @return void
     */
    public static function uninstall() {
        // Remove options
        delete_option('image_convertor_options');
        delete_option('image_convertor_stats');
        
        // Remove transients
        delete_transient('image_convertor_binary_status');
        delete_transient('image_convertor_system_info');
        
        // Remove user meta
        delete_metadata('user', 0, 'image_convertor_dismissed_notices', '', true);
        
        // Clean up files if option is set
        $options = get_option('image_convertor_options', []);
        if (!empty($options['remove_data_on_uninstall'])) {
            self::cleanup_all_files();
        }
        
        // Clear cron jobs
        wp_clear_scheduled_hook('image_convertor_cleanup');
    }
    
    /**
     * Add plugin action links
     * 
     * @param array $links Existing links
     * @return array Modified links
     */
    public function add_action_links($links) {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=image-convertor') . '">設定</a>',
            '<a href="' . admin_url('admin.php?page=image-convertor-stats') . '">統計</a>'
        ];
        
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Add plugin row meta
     * 
     * @param array $links Existing links
     * @param string $file Plugin file
     * @return array Modified links
     */
    public function add_row_meta($links, $file) {
        if (plugin_basename(IMAGE_CONVERTOR_PLUGIN_FILE) === $file) {
            $row_meta = [
                'docs' => '<a href="https://example.com/docs" target="_blank">ドキュメント</a>',
                'support' => '<a href="https://example.com/support" target="_blank">サポート</a>',
                'pro' => '<a href="https://example.com/pro" target="_blank" style="color: #d63638; font-weight: bold;">Pro版</a>'
            ];
            
            return array_merge($links, $row_meta);
        }
        
        return $links;
    }
    
    /**
     * Show admin notices
     * 
     * @return void
     */
    public function show_admin_notices() {
        // Check for missing binaries
        $missing_binaries = $this->binary_wrapper->get_missing_required_binaries();
        if (!empty($missing_binaries)) {
            
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Image Convertor:</strong> 以下のバイナリが見つかりません: ' . implode(', ', array_keys($missing_binaries)) . '</p>';
            echo '<p>最適化機能を使用するには、これらのバイナリをインストールしてください。</p>';
            echo '</div>';
        }
        
        // Check upload directory permissions
        $upload_dir = wp_upload_dir();
        if (!wp_is_writable($upload_dir['basedir'])) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>Image Convertor:</strong> アップロードディレクトリに書き込み権限がありません。</p>';
            echo '<p>ディレクトリ: ' . esc_html($upload_dir['basedir']) . '</p>';
            echo '</div>';
        }
        
        // Show welcome notice for new installations
        if (get_transient('image_convertor_show_welcome')) {
            delete_transient('image_convertor_show_welcome');
            
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Image Convertor が有効化されました！</strong></p>';
            echo '<p><a href="' . admin_url('admin.php?page=image-convertor') . '" class="button button-primary">設定を開始</a></p>';
            echo '</div>';
        }
    }
    
    /**
     * AJAX handler for serving images
     * 
     * @return void
     */
    public function ajax_serve_image() {
        // This is handled by DeliveryManager, but kept for compatibility
        if ($this->delivery_manager) {
            $this->delivery_manager->serve_optimized_image();
        }
    }
    
    /**
     * Cleanup old files
     * 
     * @return void
     */
    public function cleanup_old_files() {
        try {
            $cleanup_days = intval($this->options['cleanup_interval'] ?? 30);
            
            if ($cleanup_days <= 0) {
                return;
            }
            
            $cutoff_time = time() - ($cleanup_days * DAY_IN_SECONDS);
            
            // Clean up log files - handled by cleanup_old_backups method
            $this->cleanup_old_log_files($cutoff_time);
            
            // Clean up temporary files
            $this->cleanup_temp_files($cutoff_time);
            
            $this->logger->info('Cleanup completed', [
                'cleanup_days' => $cleanup_days,
                'cutoff_time' => date('Y-m-d H:i:s', $cutoff_time)
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Cleanup failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get default plugin options
     * 
     * @return array Default options
     */
    private function get_default_options() {
        return [
            // General settings
            'auto_optimize' => true,
            'generate_webp' => true,
            'generate_avif' => false, // Pro feature
            'backup_originals' => true,
            
            // Quality settings
            'webp_quality' => 85,
            'avif_quality' => 80,
            'jpeg_quality' => 85,
            
            // Advanced settings
            'max_width' => 2048,
            'max_height' => 2048,
            'enable_logging' => true,
            'cleanup_interval' => 30,
            
            // Delivery settings
            'enable_delivery' => true,
            'use_output_buffering' => false,
            'enhance_lazy_loading' => true,
            'add_preload_headers' => true,
            
            // Binary settings
            'custom_binary_path' => '',
            'process_timeout' => 30,
            
            // Pro settings
            'license_key' => '',
            'license_status' => 'inactive',
            
            // Cleanup settings
            'remove_data_on_uninstall' => false
        ];
    }
    
    /**
     * Create necessary directories
     * 
     * @return void
     */
    private function create_directories() {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/image-convertor-backups';
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
            
            // Add .htaccess for security
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents($backup_dir . '/.htaccess', $htaccess_content);
        }
        
        // Create logs directory
        $logs_dir = IMAGE_CONVERTOR_PLUGIN_DIR . 'logs';
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);
            
            // Add .htaccess for security
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents($logs_dir . '/.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Create database tables
     * 
     * @return void
     */
    private function create_database_tables() {
        // Currently not using custom tables, but placeholder for future use
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Example table for optimization history (not implemented yet)
        /*
        $table_name = $wpdb->prefix . 'image_convertor_history';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            attachment_id bigint(20) NOT NULL,
            original_size bigint(20) NOT NULL,
            optimized_size bigint(20) NOT NULL,
            format varchar(10) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY attachment_id (attachment_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        */
    }
    
    /**
     * Setup cron jobs
     * 
     * @return void
     */
    private function setup_cron_jobs() {
        // Schedule cleanup job
        if (!wp_next_scheduled('image_convertor_cleanup')) {
            wp_schedule_event(time(), 'daily', 'image_convertor_cleanup');
        }
    }
    
    /**
     * Cleanup temporary files
     * 
     * @param int $cutoff_time Cutoff timestamp
     * @return void
     */
    private function cleanup_temp_files($cutoff_time) {
        $temp_dir = sys_get_temp_dir();
        $pattern = $temp_dir . '/image-convertor-*';
        
        $files = glob($pattern);
        
        if ($files) {
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff_time) {
                    wp_delete_file($file);
                }
            }
        }
    }
    
    /**
     * Cleanup all plugin files
     * 
     * @return void
     */
    private static function cleanup_all_files() {
        $upload_dir = wp_upload_dir();
        
        // Remove backup directory
        $backup_dir = $upload_dir['basedir'] . '/image-convertor-backups';
        if (file_exists($backup_dir)) {
            Utils::delete_directory($backup_dir);
        }
        
        // Remove logs directory
        $logs_dir = IMAGE_CONVERTOR_PLUGIN_DIR . 'logs';
        if (file_exists($logs_dir)) {
            Utils::delete_directory($logs_dir);
        }
        
        // Remove generated WebP/AVIF files
        self::cleanup_generated_files();
    }
    
    /**
     * Cleanup old log files
     * 
     * @param int $cutoff_time Cutoff timestamp
     * @return void
     */
    private function cleanup_old_log_files($cutoff_time) {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/advanced-image-optimizer/logs';
        
        if (!is_dir($log_dir)) {
            return;
        }
        
        $files = glob($log_dir . '/*.log*');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }
    
    /**
     * Cleanup generated WebP/AVIF files
     * 
     * @return void
     */
    private static function cleanup_generated_files() {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];
        
        // Find all WebP and AVIF files
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, ['webp', 'avif'])) {
                    wp_delete_file($file->getPathname());
                }
            }
        }
    }
    
    /**
     * Get logger instance
     * 
     * @return Logger
     */
    public function get_logger() {
        return $this->logger;
    }
    
    /**
     * Get binary wrapper instance
     * 
     * @return BinaryWrapper
     */
    public function get_binary_wrapper() {
        return $this->binary_wrapper;
    }
    
    /**
     * Get image processor instance
     * 
     * @return ImageProcessor
     */
    public function get_image_processor() {
        return $this->image_processor;
    }
    
    /**
     * Get admin manager instance
     * 
     * @return AdminManager|null
     */
    public function get_admin_manager() {
        return $this->admin_manager;
    }
    
    /**
     * Get media manager instance
     * 
     * @return MediaManager
     */
    public function get_media_manager() {
        return $this->media_manager;
    }
    
    /**
     * Get delivery manager instance
     * 
     * @return DeliveryManager|null
     */
    public function get_delivery_manager() {
        return $this->delivery_manager;
    }
    
    /**
     * Get Pro manager instance
     * 
     * @return ProManager
     */
    public function get_pro_manager() {
        return $this->pro_manager;
    }
    
    /**
     * Get plugin options
     * 
     * @return array
     */
    public function get_options() {
        return $this->options;
    }
    
    /**
     * Update plugin options
     * 
     * @param array $options New options
     * @return bool Success status
     */
    public function update_options($options) {
        $this->options = $options;
        return update_option('image_convertor_options', $options);
    }
    
    /**
     * Check if plugin is initialized
     * 
     * @return bool True if initialized
     */
    public function is_initialized() {
        return $this->initialized;
    }
}