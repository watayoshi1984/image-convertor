<?php
/**
 * Pro Manager Class
 * 
 * Manages Pro version features and integrations
 * 
 * @package WyoshiImageOptimizer
 * @subpackage Pro
 * @since 1.0.0
 */

namespace WyoshiImageOptimizer\Pro;

use WyoshiImageOptimizer\Common\Logger;
use WyoshiImageOptimizer\Common\Utils;
use WyoshiImageOptimizer\Processing\BinaryWrapper;
use WyoshiImageOptimizer\Processing\ImageProcessor;
use WyoshiImageOptimizer\Pro\LicenseManager;
use WyoshiImageOptimizer\Pro\AvifProcessor;

class ProManager {
    
    /**
     * Logger instance
     * 
     * @var Logger
     */
    private $logger;
    
    /**
     * License manager instance
     * 
     * @var LicenseManager
     */
    private $license_manager;
    
    /**
     * AVIF processor instance
     * 
     * @var AvifProcessor
     */
    private $avif_processor;
    
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
     * Initialization flag
     * 
     * @var bool
     */
    private $initialized = false;
    
    /**
     * Constructor
     * 
     * @param Logger $logger Logger instance
     * @param BinaryWrapper $binary_wrapper Binary wrapper instance
     * @param ImageProcessor $image_processor Image processor instance
     */
    public function __construct(Logger $logger, BinaryWrapper $binary_wrapper, ImageProcessor $image_processor) {
        $this->logger = $logger;
        $this->binary_wrapper = $binary_wrapper;
        $this->image_processor = $image_processor;
    }
    
    /**
     * Initialize Pro manager
     * 
     * @return void
     */
    public function init() {
        if ($this->initialized) {
            return;
        }
        
        try {
            // Initialize license manager
            $this->license_manager = new LicenseManager($this->logger);
            $this->license_manager->init();
            
            // Initialize AVIF processor
            $this->avif_processor = new AvifProcessor(
                $this->binary_wrapper,
                $this->logger,
                $this->license_manager
            );
            
            // Register hooks
            $this->register_hooks();
            
            // Mark as initialized
            $this->initialized = true;
            
            $this->logger->info('Pro Manager initialized successfully');
            
        } catch (\Exception $e) {
            $this->logger->error('Pro Manager initialization failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Register WordPress hooks
     * 
     * @return void
     */
    private function register_hooks() {
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_pro_menu_items'], 20);
            add_action('admin_notices', [$this, 'show_pro_notices']);
            add_filter('wyoshi_img_opt_admin_tabs', [$this, 'add_pro_admin_tabs']);
        }
        
        // フォーマット関連のフィルター
        add_filter('wyoshi_img_opt_supported_formats', [$this, 'add_avif_format']);
        add_filter('wyoshi_img_opt_conversion_options', [$this, 'add_avif_options']);
        add_action('wyoshi_img_opt_after_webp_conversion', [$this, 'maybe_convert_to_avif'], 10, 3);
        
        // 配信関連のフィルター
        add_filter('wyoshi_img_opt_delivery_formats', [$this, 'add_avif_delivery']);
        add_filter('wyoshi_img_opt_picture_sources', [$this, 'add_avif_sources'], 5, 3);
        
        // 設定関連のフィルター
        add_filter('wyoshi_img_opt_default_options', [$this, 'add_pro_default_options']);
        add_filter('wyoshi_img_opt_settings_sections', [$this, 'add_pro_settings_sections']);
        
        // 統計関連のフィルター
        add_filter('wyoshi_img_opt_stats_data', [$this, 'add_pro_stats_data']);
        
        // Ajax アクション
        add_action('wp_ajax_wyoshi_img_opt_bulk_avif_conversion', [$this, 'ajax_bulk_avif_conversion']);
        add_action('wp_ajax_wyoshi_img_opt_test_avif_support', [$this, 'ajax_test_avif_support']);
    }
    
    /**
     * Add Pro menu items
     * 
     * @return void
     */
    public function add_pro_menu_items() {
        if (!$this->license_manager->is_license_active()) {
            add_submenu_page(
                'image-convertor',
                'Pro版にアップグレード',
                '<span style="color: #d63638;">Pro版にアップグレード</span>',
                'manage_options',
                'image-convertor-upgrade',
                [$this, 'render_upgrade_page']
            );
        } else {
            add_submenu_page(
                'image-convertor',
                'ライセンス管理',
                'ライセンス管理',
                'manage_options',
                'image-convertor-license',
                [$this, 'render_license_page']
            );
        }
    }
    
    /**
     * Show Pro-related admin notices
     * 
     * @return void
     */
    public function show_pro_notices() {
        $screen = get_current_screen();
        
        // Only show on plugin pages
        if (!$screen || strpos($screen->id, 'image-convertor') === false) {
            return;
        }
        
        // Show upgrade notice if not licensed
        if (!$this->license_manager->is_license_active()) {
            $dismissed = get_user_meta(get_current_user_id(), 'wyoshi_img_opt_pro_notice_dismissed', true);
            
            if (!$dismissed) {
                echo '<div class="notice notice-info is-dismissible" data-notice="pro-upgrade">';
                echo '<p><strong>Image Convertor Pro:</strong> AVIF対応、一括最適化、優先サポートなどの機能をご利用いただけます。</p>';
                echo '<p><a href="' . admin_url('admin.php?page=image-convertor-upgrade') . '" class="button button-primary">Pro版について詳しく見る</a></p>';
                echo '</div>';
            }
        }
        
        // Show license expiration warning
        $license_data = $this->license_manager->get_license_data();
        if ($license_data && !empty($license_data['expires'])) {
            $expires = strtotime($license_data['expires']);
            $days_until_expiry = ceil(($expires - time()) / DAY_IN_SECONDS);
            
            if ($days_until_expiry <= 30 && $days_until_expiry > 0) {
                echo '<div class="notice notice-warning">';
                echo '<p><strong>Image Convertor Pro:</strong> ライセンスの有効期限まで残り' . $days_until_expiry . '日です。</p>';
                echo '<p><a href="https://example.com/renew" target="_blank" class="button">ライセンスを更新</a></p>';
                echo '</div>';
            } elseif ($days_until_expiry <= 0) {
                echo '<div class="notice notice-error">';
                echo '<p><strong>Image Convertor Pro:</strong> ライセンスの有効期限が切れています。Pro機能をご利用いただくには更新が必要です。</p>';
                echo '<p><a href="https://example.com/renew" target="_blank" class="button button-primary">ライセンスを更新</a></p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Add Pro admin tabs
     * 
     * @param array $tabs Existing tabs
     * @return array Modified tabs
     */
    public function add_pro_admin_tabs($tabs) {
        if ($this->license_manager->is_license_active()) {
            $tabs['avif'] = [
                'title' => 'AVIF設定',
                'callback' => [$this, 'render_avif_settings_tab']
            ];
        }
        
        return $tabs;
    }
    
    /**
     * Add AVIF format to supported formats
     * 
     * @param array $formats Supported formats
     * @return array Modified formats
     */
    public function add_avif_format($formats) {
        if ($this->avif_processor->is_available()) {
            $formats['avif'] = [
                'mime_type' => 'image/avif',
                'extension' => 'avif',
                'quality_range' => [1, 100],
                'default_quality' => 80
            ];
        }
        
        return $formats;
    }
    
    /**
     * Add AVIF conversion options
     * 
     * @param array $options Conversion options
     * @return array Modified options
     */
    public function add_avif_options($options) {
        if ($this->avif_processor->is_available()) {
            $options['generate_avif'] = true;
            $options['avif_quality'] = 80;
            $options['avif_speed'] = 6;
            $options['avif_effort'] = 4;
        }
        
        return $options;
    }
    
    /**
     * Maybe convert to AVIF after WebP conversion
     * 
     * @param string $source_path Source image path
     * @param string $webp_path WebP image path
     * @param array $options Conversion options
     * @return void
     */
    public function maybe_convert_to_avif($source_path, $webp_path, $options) {
        if (!$this->avif_processor->is_available()) {
            return;
        }
        
        $plugin_options = get_option('wyoshi_img_opt_options', []);
        
        if (empty($plugin_options['generate_avif'])) {
            return;
        }
        
        // Generate AVIF path
        $avif_path = preg_replace('/\.(webp)$/i', '.avif', $webp_path);
        
        // Convert to AVIF
        $avif_options = [
            'quality' => $plugin_options['avif_quality'] ?? 80,
            'speed' => $plugin_options['avif_speed'] ?? 6,
            'effort' => $plugin_options['avif_effort'] ?? 4
        ];
        
        $result = $this->avif_processor->convert_to_avif($source_path, $avif_path, $avif_options);
        
        if ($result['success']) {
            // Update statistics
            $this->avif_processor->update_conversion_stats(
                filesize($source_path),
                filesize($avif_path)
            );
            
            do_action('wyoshi_img_opt_avif_conversion_complete', $source_path, $avif_path, $options);
        }
    }
    
    /**
     * Add AVIF to delivery formats
     * 
     * @param array $formats Delivery formats
     * @return array Modified formats
     */
    public function add_avif_delivery($formats) {
        if ($this->avif_processor->is_available()) {
            // AVIF should be first (highest priority)
            array_unshift($formats, [
                'format' => 'avif',
                'mime_type' => 'image/avif',
                'extension' => 'avif',
                'check_support' => [$this->avif_processor, 'browser_supports_avif']
            ]);
        }
        
        return $formats;
    }
    
    /**
     * Add AVIF sources to picture element
     * 
     * @param array $sources Picture sources
     * @param string $image_url Original image URL
     * @param array $options Generation options
     * @return array Modified sources
     */
    public function add_avif_sources($sources, $image_url, $options) {
        if (!$this->avif_processor->is_available()) {
            return $sources;
        }
        
        if (!$this->avif_processor->browser_supports_avif()) {
            return $sources;
        }
        
        // Generate AVIF URL
        $avif_url = preg_replace('/\.(jpe?g|png|webp)$/i', '.avif', $image_url);
        
        // Check if AVIF file exists
        $avif_path = Utils::url_to_path($avif_url);
        if (!file_exists($avif_path)) {
            return $sources;
        }
        
        // Add AVIF source at the beginning (highest priority)
        array_unshift($sources, [
            'srcset' => $avif_url,
            'type' => 'image/avif'
        ]);
        
        return $sources;
    }
    
    /**
     * Add Pro default options
     * 
     * @param array $options Default options
     * @return array Modified options
     */
    public function add_pro_default_options($options) {
        $pro_options = [
            'generate_avif' => false,
            'avif_quality' => 80,
            'avif_speed' => 6,
            'avif_effort' => 4,
            'bulk_optimization_batch_size' => 10,
            'priority_support' => false
        ];
        
        return array_merge($options, $pro_options);
    }
    
    /**
     * Add Pro settings sections
     * 
     * @param array $sections Settings sections
     * @return array Modified sections
     */
    public function add_pro_settings_sections($sections) {
        if ($this->license_manager->is_license_active()) {
            $sections['avif'] = [
                'title' => 'AVIF設定',
                'description' => 'AVIF形式の画像変換設定を行います。',
                'fields' => [
                    'generate_avif' => [
                        'type' => 'checkbox',
                        'title' => 'AVIF生成を有効化',
                        'description' => 'アップロード時にAVIF形式の画像を自動生成します。'
                    ],
                    'avif_quality' => [
                        'type' => 'range',
                        'title' => 'AVIF品質',
                        'min' => 1,
                        'max' => 100,
                        'step' => 1,
                        'description' => 'AVIF画像の品質を設定します（1-100）。'
                    ],
                    'avif_speed' => [
                        'type' => 'range',
                        'title' => 'エンコード速度',
                        'min' => 0,
                        'max' => 10,
                        'step' => 1,
                        'description' => '変換速度を設定します。高い値ほど高速ですが品質が下がります。'
                    ]
                ]
            ];
        }
        
        return $sections;
    }
    
    /**
     * Add Pro statistics data
     * 
     * @param array $stats Statistics data
     * @return array Modified statistics
     */
    public function add_pro_stats_data($stats) {
        if ($this->license_manager->is_license_active()) {
            $avif_stats = $this->avif_processor->get_conversion_stats();
            
            $stats['avif'] = [
                'title' => 'AVIF変換統計',
                'data' => [
                    'total_conversions' => $avif_stats['total_conversions'],
                    'total_savings' => $avif_stats['total_original_size'] - $avif_stats['total_avif_size'],
                    'average_compression' => $avif_stats['average_compression'] . '%',
                    'last_conversion' => $avif_stats['last_conversion']
                ]
            ];
        }
        
        return $stats;
    }
    
    /**
     * AJAX handler for bulk AVIF conversion
     * 
     * @return void
     */
    public function ajax_bulk_avif_conversion() {
        check_ajax_referer('wyoshi_img_opt_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限が不足しています。');
        }
        
        if (!$this->avif_processor->is_available()) {
            wp_send_json_error('AVIF変換機能が利用できません。');
        }
        
        $batch_size = intval($_POST['batch_size'] ?? 10);
        $offset = intval($_POST['offset'] ?? 0);
        
        // Get images to convert
        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png'],
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'post_status' => 'inherit'
        ]);
        
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($attachments as $attachment) {
            $results['processed']++;
            
            $file_path = get_attached_file($attachment->ID);
            if (!$file_path || !file_exists($file_path)) {
                $results['failed']++;
                $results['errors'][] = 'File not found: ' . $attachment->post_title;
                continue;
            }
            
            $avif_path = preg_replace('/\.(jpe?g|png)$/i', '.avif', $file_path);
            
            $result = $this->avif_processor->convert_to_avif($file_path, $avif_path);
            
            if ($result['success']) {
                $results['successful']++;
                
                // Update statistics
                $this->avif_processor->update_conversion_stats(
                    filesize($file_path),
                    filesize($avif_path)
                );
            } else {
                $results['failed']++;
                $results['errors'][] = $attachment->post_title . ': ' . $result['error'];
            }
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler for testing AVIF support
     * 
     * @return void
     */
    public function ajax_test_avif_support() {
        check_ajax_referer('wyoshi_img_opt_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限が不足しています。');
        }
        
        $support_info = [
            'license_active' => $this->license_manager->is_license_active(),
            'avif_available' => $this->avif_processor->is_available(),
            'browser_support' => $this->avif_processor->browser_supports_avif(),
            'binaries' => []
        ];
        
        // Check available binaries
        $binaries = ['cavif', 'avifenc', 'magick', 'convert'];
        foreach ($binaries as $binary) {
            $support_info['binaries'][$binary] = $this->binary_wrapper->has_binary($binary);
        }
        
        wp_send_json_success($support_info);
    }
    
    /**
     * Render upgrade page
     * 
     * @return void
     */
    public function render_upgrade_page() {
        include WYOSHI_IMG_OPT_PLUGIN_DIR . 'includes/Admin/views/upgrade-page.php';
    }
    
    /**
     * Render license page
     * 
     * @return void
     */
    public function render_license_page() {
        include WYOSHI_IMG_OPT_PLUGIN_DIR . 'includes/Admin/views/license-page.php';
    }
    
    /**
     * Render AVIF settings tab
     * 
     * @return void
     */
    public function render_avif_settings_tab() {
        include WYOSHI_IMG_OPT_PLUGIN_DIR . 'includes/Admin/views/avif-settings-tab.php';
    }
    
    /**
     * Get license manager instance
     * 
     * @return LicenseManager
     */
    public function get_license_manager() {
        return $this->license_manager;
    }
    
    /**
     * Get AVIF processor instance
     * 
     * @return AvifProcessor
     */
    public function get_avif_processor() {
        return $this->avif_processor;
    }
    
    /**
     * Check if Pro manager is initialized
     * 
     * @return bool True if initialized
     */
    public function is_initialized() {
        return $this->initialized;
    }
}