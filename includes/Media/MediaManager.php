<?php
/**
 * Media Manager Class
 * 
 * Handles WordPress media library integration and automatic image optimization
 * 
 * @package ImageConvertor
 * @subpackage Media
 * @since 1.0.0
 */

namespace ImageConvertor\Media;

use ImageConvertor\Processing\ImageProcessor;
use ImageConvertor\Core\Logger;
use ImageConvertor\Utils\Utils;

class MediaManager {
    
    /**
     * Image processor instance
     * 
     * @var ImageProcessor
     */
    private $image_processor;
    
    /**
     * Logger instance
     * 
     * @var Logger
     */
    private $logger;
    
    /**
     * Plugin options
     * 
     * @var array
     */
    private $options;
    
    /**
     * Constructor
     * 
     * @param ImageProcessor $image_processor Image processor instance
     * @param Logger $logger Logger instance
     */
    public function __construct(ImageProcessor $image_processor, Logger $logger) {
        $this->image_processor = $image_processor;
        $this->logger = $logger;
        $this->options = get_option('image_convertor_options', []);
    }
    
    /**
     * Initialize media hooks
     * 
     * @return void
     */
    public function init() {
        // Auto-optimization hooks
        add_filter('wp_handle_upload', [$this, 'handle_upload'], 10, 2);
        add_filter('wp_generate_attachment_metadata', [$this, 'generate_attachment_metadata'], 10, 2);
        
        // Media library hooks
        add_filter('attachment_fields_to_edit', [$this, 'add_attachment_fields'], 10, 2);
        add_filter('attachment_fields_to_save', [$this, 'save_attachment_fields'], 10, 2);
        add_action('add_meta_boxes_attachment', [$this, 'add_attachment_meta_boxes']);
        
        // AJAX handlers
        add_action('wp_ajax_optimize_single_image', [$this, 'ajax_optimize_single_image']);
        add_action('wp_ajax_get_optimization_status', [$this, 'ajax_get_optimization_status']);
        add_action('wp_ajax_restore_original_image', [$this, 'ajax_restore_original_image']);
        
        // Media list table modifications
        add_filter('manage_media_columns', [$this, 'add_media_columns']);
        add_action('manage_media_custom_column', [$this, 'display_media_column'], 10, 2);
        
        // Bulk actions
        add_filter('bulk_actions-upload', [$this, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-upload', [$this, 'handle_bulk_actions'], 10, 3);
        
        // Delete hooks
        add_action('delete_attachment', [$this, 'delete_attachment_files']);
    }
    
    /**
     * Handle file upload
     * 
     * @param array $upload Upload data
     * @param string $context Upload context
     * @return array Modified upload data
     */
    public function handle_upload($upload, $context = 'upload') {
        if (!$this->should_auto_optimize()) {
            return $upload;
        }
        
        $file_path = $upload['file'];
        
        if (!$this->is_supported_image($file_path)) {
            return $upload;
        }
        
        try {
            $this->logger->info('Auto-optimizing uploaded image', [
                'file' => basename($file_path),
                'size' => filesize($file_path)
            ]);
            
            $result = $this->image_processor->optimize_image($file_path, [
                'quality' => $this->get_quality_setting(),
                'backup' => $this->should_backup_original(),
                'generate_webp' => $this->should_generate_webp(),
                'generate_avif' => $this->should_generate_avif()
            ]);
            
            if ($result['success']) {
                $this->logger->info('Upload optimization completed', [
                    'file' => basename($file_path),
                    'original_size' => $result['original_size'],
                    'optimized_size' => $result['optimized_size'],
                    'savings' => $result['savings_bytes']
                ]);
            }
            
        } catch (Exception $e) {
            $this->logger->error('Upload optimization failed', [
                'file' => basename($file_path),
                'error' => $e->getMessage()
            ]);
        }
        
        return $upload;
    }
    
    /**
     * Generate attachment metadata
     * 
     * @param array $metadata Attachment metadata
     * @param int $attachment_id Attachment ID
     * @return array Modified metadata
     */
    public function generate_attachment_metadata($metadata, $attachment_id) {
        if (!$this->should_auto_optimize()) {
            return $metadata;
        }
        
        $file_path = get_attached_file($attachment_id);
        
        if (!$this->is_supported_image($file_path)) {
            return $metadata;
        }
        
        // Optimize thumbnails
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            $upload_dir = wp_upload_dir();
            $base_dir = dirname($file_path);
            
            foreach ($metadata['sizes'] as $size_name => $size_data) {
                $thumb_path = $base_dir . '/' . $size_data['file'];
                
                if (file_exists($thumb_path)) {
                    try {
                        $result = $this->image_processor->optimize_image($thumb_path, [
                            'quality' => $this->get_quality_setting(),
                            'backup' => false, // Don't backup thumbnails
                            'generate_webp' => $this->should_generate_webp(),
                            'generate_avif' => $this->should_generate_avif()
                        ]);
                        
                        if ($result['success']) {
                            $this->logger->debug('Thumbnail optimized', [
                                'attachment_id' => $attachment_id,
                                'size' => $size_name,
                                'file' => basename($thumb_path),
                                'savings' => $result['savings_bytes']
                            ]);
                        }
                        
                    } catch (Exception $e) {
                        $this->logger->warning('Thumbnail optimization failed', [
                            'attachment_id' => $attachment_id,
                            'size' => $size_name,
                            'file' => basename($thumb_path),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        
        // Store optimization metadata
        $optimization_data = [
            'optimized' => true,
            'optimized_at' => current_time('mysql'),
            'original_size' => filesize($file_path),
            'plugin_version' => IMAGE_CONVERTOR_VERSION
        ];
        
        update_post_meta($attachment_id, '_image_convertor_data', $optimization_data);
        
        return $metadata;
    }
    
    /**
     * Add attachment fields to edit form
     * 
     * @param array $form_fields Form fields
     * @param WP_Post $post Attachment post
     * @return array Modified form fields
     */
    public function add_attachment_fields($form_fields, $post) {
        if (!wp_attachment_is_image($post->ID)) {
            return $form_fields;
        }
        
        $optimization_data = get_post_meta($post->ID, '_image_convertor_data', true);
        $file_path = get_attached_file($post->ID);
        
        // Optimization status
        $status_html = '<div class="image-convertor-status">';
        
        if ($optimization_data && isset($optimization_data['optimized'])) {
            $status_html .= '<span class="status-optimized">✓ 最適化済み</span>';
            $status_html .= '<p><small>最適化日時: ' . $optimization_data['optimized_at'] . '</small></p>';
            
            if (isset($optimization_data['original_size'])) {
                $current_size = filesize($file_path);
                $savings = $optimization_data['original_size'] - $current_size;
                $savings_percent = ($savings / $optimization_data['original_size']) * 100;
                
                $status_html .= '<p><small>';
                $status_html .= 'オリジナル: ' . Utils::format_bytes($optimization_data['original_size']) . ' → ';
                $status_html .= '最適化後: ' . Utils::format_bytes($current_size) . ' ';
                $status_html .= '(' . number_format($savings_percent, 1) . '% 削減)';
                $status_html .= '</small></p>';
            }
        } else {
            $status_html .= '<span class="status-not-optimized">未最適化</span>';
        }
        
        $status_html .= '</div>';
        
        $form_fields['image_convertor_status'] = [
            'label' => 'Image Convertor',
            'input' => 'html',
            'html' => $status_html
        ];
        
        // Action buttons
        $actions_html = '<div class="image-convertor-actions">';
        
        if ($this->is_supported_image($file_path)) {
            $actions_html .= '<button type="button" class="button optimize-single" data-attachment-id="' . $post->ID . '">最適化実行</button> ';
            
            if ($optimization_data && $this->has_backup($post->ID)) {
                $actions_html .= '<button type="button" class="button restore-original" data-attachment-id="' . $post->ID . '">オリジナル復元</button>';
            }
        } else {
            $actions_html .= '<span class="description">サポートされていない画像形式です</span>';
        }
        
        $actions_html .= '</div>';
        
        $form_fields['image_convertor_actions'] = [
            'label' => 'アクション',
            'input' => 'html',
            'html' => $actions_html
        ];
        
        return $form_fields;
    }
    
    /**
     * Save attachment fields
     * 
     * @param array $post Post data
     * @param array $attachment Attachment data
     * @return array Modified post data
     */
    public function save_attachment_fields($post, $attachment) {
        // No specific fields to save for now
        return $post;
    }
    
    /**
     * Add attachment meta boxes
     * 
     * @param WP_Post $post Attachment post
     * @return void
     */
    public function add_attachment_meta_boxes($post) {
        if (!wp_attachment_is_image($post->ID)) {
            return;
        }
        
        add_meta_box(
            'image-convertor-details',
            'Image Convertor 詳細',
            [$this, 'render_attachment_meta_box'],
            'attachment',
            'side',
            'default'
        );
    }
    
    /**
     * Render attachment meta box
     * 
     * @param WP_Post $post Attachment post
     * @return void
     */
    public function render_attachment_meta_box($post) {
        $optimization_data = get_post_meta($post->ID, '_image_convertor_data', true);
        $file_path = get_attached_file($post->ID);
        $upload_dir = wp_upload_dir();
        $base_dir = dirname($file_path);
        $base_name = pathinfo($file_path, PATHINFO_FILENAME);
        
        echo '<div class="image-convertor-meta-box">';
        
        // File information
        echo '<h4>ファイル情報</h4>';
        echo '<p><strong>ファイル名:</strong> ' . basename($file_path) . '</p>';
        echo '<p><strong>サイズ:</strong> ' . Utils::format_bytes(filesize($file_path)) . '</p>';
        echo '<p><strong>形式:</strong> ' . strtoupper(pathinfo($file_path, PATHINFO_EXTENSION)) . '</p>';
        
        // Generated files
        echo '<h4>生成ファイル</h4>';
        echo '<ul>';
        
        $webp_path = $base_dir . '/' . $base_name . '.webp';
        if (file_exists($webp_path)) {
            echo '<li>WebP: ' . Utils::format_bytes(filesize($webp_path)) . '</li>';
        }
        
        $avif_path = $base_dir . '/' . $base_name . '.avif';
        if (file_exists($avif_path)) {
            echo '<li>AVIF: ' . Utils::format_bytes(filesize($avif_path)) . '</li>';
        }
        
        if (!file_exists($webp_path) && !file_exists($avif_path)) {
            echo '<li><em>生成されたファイルはありません</em></li>';
        }
        
        echo '</ul>';
        
        // Optimization history
        if ($optimization_data) {
            echo '<h4>最適化履歴</h4>';
            echo '<p><strong>最適化日時:</strong> ' . $optimization_data['optimized_at'] . '</p>';
            
            if (isset($optimization_data['original_size'])) {
                $current_size = filesize($file_path);
                $savings = $optimization_data['original_size'] - $current_size;
                $savings_percent = ($savings / $optimization_data['original_size']) * 100;
                
                echo '<p><strong>削減量:</strong> ' . Utils::format_bytes($savings) . ' (' . number_format($savings_percent, 1) . '%)</p>';
            }
            
            if (isset($optimization_data['plugin_version'])) {
                echo '<p><strong>プラグインバージョン:</strong> ' . $optimization_data['plugin_version'] . '</p>';
            }
        }
        
        echo '</div>';
    }
    
    /**
     * Add media library columns
     * 
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_media_columns($columns) {
        $columns['image_convertor'] = 'Image Convertor';
        return $columns;
    }
    
    /**
     * Display media column content
     * 
     * @param string $column_name Column name
     * @param int $attachment_id Attachment ID
     * @return void
     */
    public function display_media_column($column_name, $attachment_id) {
        if ($column_name !== 'image_convertor') {
            return;
        }
        
        if (!wp_attachment_is_image($attachment_id)) {
            echo '<span class="description">画像以外</span>';
            return;
        }
        
        $file_path = get_attached_file($attachment_id);
        
        if (!$this->is_supported_image($file_path)) {
            echo '<span class="description">未対応</span>';
            return;
        }
        
        $optimization_data = get_post_meta($attachment_id, '_image_convertor_data', true);
        
        if ($optimization_data && isset($optimization_data['optimized'])) {
            echo '<span class="status-optimized">✓ 最適化済み</span>';
            
            if (isset($optimization_data['original_size'])) {
                $current_size = filesize($file_path);
                $savings = $optimization_data['original_size'] - $current_size;
                $savings_percent = ($savings / $optimization_data['original_size']) * 100;
                
                echo '<br><small>' . number_format($savings_percent, 1) . '% 削減</small>';
            }
        } else {
            echo '<span class="status-not-optimized">未最適化</span>';
            echo '<br><button type="button" class="button-link optimize-single" data-attachment-id="' . $attachment_id . '">最適化</button>';
        }
    }
    
    /**
     * Add bulk actions
     * 
     * @param array $actions Existing actions
     * @return array Modified actions
     */
    public function add_bulk_actions($actions) {
        $actions['optimize_images'] = '画像を最適化';
        $actions['restore_originals'] = 'オリジナルを復元';
        return $actions;
    }
    
    /**
     * Handle bulk actions
     * 
     * @param string $redirect_to Redirect URL
     * @param string $doaction Action name
     * @param array $post_ids Post IDs
     * @return string Modified redirect URL
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction === 'optimize_images') {
            $optimized = 0;
            
            foreach ($post_ids as $post_id) {
                if (wp_attachment_is_image($post_id)) {
                    $file_path = get_attached_file($post_id);
                    
                    if ($this->is_supported_image($file_path)) {
                        try {
                            $result = $this->optimize_single_attachment($post_id);
                            if ($result['success']) {
                                $optimized++;
                            }
                        } catch (Exception $e) {
                            $this->logger->error('Bulk optimization failed', [
                                'attachment_id' => $post_id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
            
            $redirect_to = add_query_arg('optimized', $optimized, $redirect_to);
            
        } elseif ($doaction === 'restore_originals') {
            $restored = 0;
            
            foreach ($post_ids as $post_id) {
                if (wp_attachment_is_image($post_id) && $this->has_backup($post_id)) {
                    try {
                        $result = $this->restore_original_attachment($post_id);
                        if ($result['success']) {
                            $restored++;
                        }
                    } catch (Exception $e) {
                        $this->logger->error('Bulk restore failed', [
                            'attachment_id' => $post_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            $redirect_to = add_query_arg('restored', $restored, $redirect_to);
        }
        
        return $redirect_to;
    }
    
    /**
     * AJAX handler for single image optimization
     * 
     * @return void
     */
    public function ajax_optimize_single_image() {
        check_ajax_referer('image_convertor_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_die('Insufficient permissions');
        }
        
        $attachment_id = intval($_POST['attachment_id']);
        
        if (!wp_attachment_is_image($attachment_id)) {
            wp_send_json_error('Not an image attachment');
        }
        
        try {
            $result = $this->optimize_single_attachment($attachment_id);
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            $this->logger->error('AJAX optimization failed', [
                'attachment_id' => $attachment_id,
                'error' => $e->getMessage()
            ]);
            
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX handler for optimization status
     * 
     * @return void
     */
    public function ajax_get_optimization_status() {
        check_ajax_referer('image_convertor_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_die('Insufficient permissions');
        }
        
        $attachment_id = intval($_POST['attachment_id']);
        
        $optimization_data = get_post_meta($attachment_id, '_image_convertor_data', true);
        $file_path = get_attached_file($attachment_id);
        
        $status = [
            'optimized' => $optimization_data && isset($optimization_data['optimized']),
            'has_backup' => $this->has_backup($attachment_id),
            'file_size' => filesize($file_path),
            'supported' => $this->is_supported_image($file_path)
        ];
        
        if ($optimization_data) {
            $status['optimization_data'] = $optimization_data;
        }
        
        wp_send_json_success($status);
    }
    
    /**
     * AJAX handler for original image restoration
     * 
     * @return void
     */
    public function ajax_restore_original_image() {
        check_ajax_referer('image_convertor_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_die('Insufficient permissions');
        }
        
        $attachment_id = intval($_POST['attachment_id']);
        
        if (!wp_attachment_is_image($attachment_id)) {
            wp_send_json_error('Not an image attachment');
        }
        
        try {
            $result = $this->restore_original_attachment($attachment_id);
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            $this->logger->error('AJAX restore failed', [
                'attachment_id' => $attachment_id,
                'error' => $e->getMessage()
            ]);
            
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Delete attachment files
     * 
     * @param int $attachment_id Attachment ID
     * @return void
     */
    public function delete_attachment_files($attachment_id) {
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }
        
        $file_path = get_attached_file($attachment_id);
        $base_dir = dirname($file_path);
        $base_name = pathinfo($file_path, PATHINFO_FILENAME);
        
        // Delete generated files
        $generated_files = [
            $base_dir . '/' . $base_name . '.webp',
            $base_dir . '/' . $base_name . '.avif'
        ];
        
        foreach ($generated_files as $generated_file) {
            if (file_exists($generated_file)) {
                wp_delete_file($generated_file);
                $this->logger->debug('Deleted generated file', [
                    'file' => basename($generated_file),
                    'attachment_id' => $attachment_id
                ]);
            }
        }
        
        // Delete backup
        $backup_path = $this->get_backup_path($attachment_id);
        if (file_exists($backup_path)) {
            wp_delete_file($backup_path);
            $this->logger->debug('Deleted backup file', [
                'file' => basename($backup_path),
                'attachment_id' => $attachment_id
            ]);
        }
    }
    
    /**
     * Optimize single attachment
     * 
     * @param int $attachment_id Attachment ID
     * @return array Optimization result
     */
    private function optimize_single_attachment($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        
        if (!$this->is_supported_image($file_path)) {
            throw new Exception('Unsupported image format');
        }
        
        $result = $this->image_processor->optimize_image($file_path, [
            'quality' => $this->get_quality_setting(),
            'backup' => $this->should_backup_original(),
            'generate_webp' => $this->should_generate_webp(),
            'generate_avif' => $this->should_generate_avif()
        ]);
        
        if ($result['success']) {
            // Update optimization metadata
            $optimization_data = [
                'optimized' => true,
                'optimized_at' => current_time('mysql'),
                'original_size' => $result['original_size'],
                'optimized_size' => $result['optimized_size'],
                'savings_bytes' => $result['savings_bytes'],
                'savings_percent' => $result['savings_percent'],
                'plugin_version' => IMAGE_CONVERTOR_VERSION
            ];
            
            update_post_meta($attachment_id, '_image_convertor_data', $optimization_data);
            
            $this->logger->info('Single image optimized', [
                'attachment_id' => $attachment_id,
                'file' => basename($file_path),
                'savings' => $result['savings_bytes']
            ]);
        }
        
        return $result;
    }
    
    /**
     * Restore original attachment
     * 
     * @param int $attachment_id Attachment ID
     * @return array Restoration result
     */
    private function restore_original_attachment($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        $backup_path = $this->get_backup_path($attachment_id);
        
        if (!file_exists($backup_path)) {
            throw new Exception('Backup file not found');
        }
        
        // Restore original file
        if (!copy($backup_path, $file_path)) {
            throw new Exception('Failed to restore original file');
        }
        
        // Remove optimization metadata
        delete_post_meta($attachment_id, '_image_convertor_data');
        
        // Delete generated files
        $base_dir = dirname($file_path);
        $base_name = pathinfo($file_path, PATHINFO_FILENAME);
        
        $generated_files = [
            $base_dir . '/' . $base_name . '.webp',
            $base_dir . '/' . $base_name . '.avif'
        ];
        
        foreach ($generated_files as $generated_file) {
            if (file_exists($generated_file)) {
                wp_delete_file($generated_file);
            }
        }
        
        $this->logger->info('Original image restored', [
            'attachment_id' => $attachment_id,
            'file' => basename($file_path)
        ]);
        
        return [
            'success' => true,
            'message' => 'Original image restored successfully'
        ];
    }
    
    /**
     * Get backup path for attachment
     * 
     * @param int $attachment_id Attachment ID
     * @return string Backup file path
     */
    private function get_backup_path($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        $backup_dir = dirname($file_path) . '/image-convertor-backups';
        $filename = basename($file_path);
        
        return $backup_dir . '/' . $filename;
    }
    
    /**
     * Check if attachment has backup
     * 
     * @param int $attachment_id Attachment ID
     * @return bool True if backup exists
     */
    private function has_backup($attachment_id) {
        return file_exists($this->get_backup_path($attachment_id));
    }
    
    /**
     * Check if should auto-optimize
     * 
     * @return bool True if auto-optimization is enabled
     */
    private function should_auto_optimize() {
        return !empty($this->options['auto_optimize']);
    }
    
    /**
     * Check if should backup original
     * 
     * @return bool True if backup is enabled
     */
    private function should_backup_original() {
        return !empty($this->options['backup_originals']);
    }
    
    /**
     * Check if should generate WebP
     * 
     * @return bool True if WebP generation is enabled
     */
    private function should_generate_webp() {
        return !empty($this->options['generate_webp']);
    }
    
    /**
     * Check if should generate AVIF
     * 
     * @return bool True if AVIF generation is enabled
     */
    private function should_generate_avif() {
        return !empty($this->options['generate_avif']);
    }
    
    /**
     * Get quality setting
     * 
     * @return int Quality value
     */
    private function get_quality_setting() {
        return intval($this->options['quality'] ?? 85);
    }
    
    /**
     * Check if image is supported
     * 
     * @param string $file_path File path
     * @return bool True if supported
     */
    private function is_supported_image($file_path) {
        $supported_formats = $this->image_processor->get_supported_formats();
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        return in_array($extension, $supported_formats);
    }
}