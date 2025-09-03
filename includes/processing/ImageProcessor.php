<?php
/**
 * Image Processor Class
 *
 * @package AdvancedImageOptimizer\Processing
 * @since 1.0.0
 */

namespace AdvancedImageOptimizer\Processing;

use AdvancedImageOptimizer\Common\Logger;
use AdvancedImageOptimizer\Common\Utils;

/**
 * Image Processor Class
 *
 * Handles image optimization and format conversion
 *
 * @since 1.0.0
 */
class ImageProcessor {

    /**
     * Binary wrapper instance
     *
     * @var BinaryWrapper
     */
    private $binary_wrapper;

    /**
     * Logger instance
     *
     * @var Logger
     */
    private $logger;

    /**
     * Processing options
     *
     * @var array
     */
    private $options;

    /**
     * Supported input formats
     *
     * @var array
     */
    private $supported_input_formats = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif'
    ];

    /**
     * Supported output formats
     *
     * @var array
     */
    private $supported_output_formats = [
        'webp',
        'avif',
        'jpeg',
        'png',
        'gif'
    ];

    /**
     * Constructor
     *
     * @param Logger $logger Logger instance
     * @param array $options Processing options
     */
    public function __construct(Logger $logger, array $options = []) {
        $this->logger = $logger;
        $this->binary_wrapper = new BinaryWrapper($logger);
        $this->options = $this->get_default_options($options);
    }

    /**
     * Get default processing options
     *
     * @param array $custom_options Custom options
     * @return array Default options merged with custom
     */
    private function get_default_options(array $custom_options = []) {
        $defaults = [
            'webp_quality' => 80,
            'avif_quality' => 70,
            'jpeg_quality' => 85,
            'png_compression' => 6,
            'gif_optimization' => true,
            'preserve_metadata' => false,
            'backup_original' => true,
            'max_width' => 0,
            'max_height' => 0,
            'auto_orient' => true,
            'strip_metadata' => true,
            'progressive_jpeg' => true,
            'webp_method' => 4,
            'avif_effort' => 4,
            'resize_threshold' => 1920
        ];

        return array_merge($defaults, $custom_options);
    }

    /**
     * Process image with specified operations
     *
     * @param string $input_path Input image path
     * @param array $operations Operations to perform
     * @return array Processing results
     */
    public function process_image($input_path, array $operations = []) {
        $start_time = microtime(true);
        
        $this->logger->info('Starting image processing', [
            'file' => basename($input_path),
            'operations' => $operations
        ]);

        $results = [
            'success' => false,
            'original_file' => $input_path,
            'original_size' => 0,
            'processed_files' => [],
            'total_savings' => 0,
            'errors' => []
        ];

        // Validate input file
        $validation = $this->validate_input_file($input_path);
        if (!$validation['valid']) {
            $results['errors'] = $validation['errors'];
            return $results;
        }

        $results['original_size'] = Utils::get_file_size($input_path);

        // Check memory requirements
        if (!$this->check_memory_requirements($input_path)) {
            $results['errors'][] = 'Insufficient memory for processing';
            return $results;
        }

        // Create backup if enabled
        $backup_path = null;
        if ($this->options['backup_original']) {
            $backup_path = $this->create_backup($input_path);
            if (!$backup_path) {
                $this->logger->warning('Failed to create backup', ['file' => basename($input_path)]);
            }
        }

        // Process each operation
        $processed_any = false;
        foreach ($operations as $operation) {
            $operation_result = $this->process_operation($input_path, $operation);
            
            if ($operation_result['success']) {
                $results['processed_files'][] = $operation_result;
                $processed_any = true;
            } else {
                $results['errors'] = array_merge($results['errors'], $operation_result['errors']);
            }
        }

        // Calculate total savings
        $total_new_size = 0;
        foreach ($results['processed_files'] as $processed_file) {
            $total_new_size += $processed_file['file_size'];
        }
        
        if ($total_new_size > 0) {
            $results['total_savings'] = $results['original_size'] - $total_new_size;
        }

        $results['success'] = $processed_any;
        $results['backup_path'] = $backup_path;

        $this->logger->log_performance('image_processing', $start_time, [
            'file' => basename($input_path),
            'operations_count' => count($operations),
            'success' => $results['success']
        ]);

        return $results;
    }

    /**
     * Process single operation
     *
     * @param string $input_path Input file path
     * @param array $operation Operation configuration
     * @return array Operation result
     */
    private function process_operation($input_path, array $operation) {
        $result = [
            'success' => false,
            'operation' => $operation['type'] ?? 'unknown',
            'output_path' => '',
            'file_size' => 0,
            'compression_ratio' => 0,
            'errors' => []
        ];

        try {
            switch ($operation['type']) {
                case 'webp':
                    $result = $this->convert_to_webp($input_path, $operation);
                    break;
                    
                case 'avif':
                    $result = $this->convert_to_avif($input_path, $operation);
                    break;
                    
                case 'optimize':
                    $result = $this->optimize_image($input_path, $operation);
                    break;
                    
                case 'resize':
                    $result = $this->resize_image($input_path, $operation);
                    break;
                    
                default:
                    $result['errors'][] = 'Unknown operation type: ' . ($operation['type'] ?? 'none');
            }
        } catch (\Exception $e) {
            $result['errors'][] = 'Operation failed: ' . $e->getMessage();
            $this->logger->error('Processing operation failed', [
                'operation' => $operation['type'] ?? 'unknown',
                'file' => basename($input_path),
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Convert image to WebP format
     *
     * @param string $input_path Input file path
     * @param array $options Conversion options
     * @return array Conversion result
     */
    public function convert_to_webp($input_path, array $options = []) {
        $quality = $options['quality'] ?? $this->options['webp_quality'];
        $method = $options['method'] ?? $this->options['webp_method'];
        $output_path = $options['output_path'] ?? Utils::generate_optimized_filename($input_path, 'webp');

        $result = [
            'success' => false,
            'operation' => 'webp',
            'output_path' => $output_path,
            'file_size' => 0,
            'compression_ratio' => 0,
            'errors' => []
        ];

        // Check if cwebp binary is available
        if (!$this->binary_wrapper->is_binary_available('cwebp')) {
            $result['errors'][] = 'cwebp binary not available';
            return $result;
        }

        // Create output directory if needed
        $output_dir = dirname($output_path);
        if (!Utils::create_directory($output_dir)) {
            $result['errors'][] = 'Failed to create output directory';
            return $result;
        }

        // Convert to WebP
        $conversion_result = $this->binary_wrapper->convert_to_webp(
            $input_path,
            $output_path,
            $quality,
            $method
        );

        if ($conversion_result['success']) {
            $result['success'] = true;
            $result['file_size'] = Utils::get_file_size($output_path);
            $original_size = Utils::get_file_size($input_path);
            $result['compression_ratio'] = Utils::calculate_compression_ratio($original_size, $result['file_size']);
            
            $this->logger->log_image_processing('webp_conversion', $input_path, true, [
                'output_file' => basename($output_path),
                'quality' => $quality,
                'original_size' => Utils::format_file_size($original_size),
                'new_size' => Utils::format_file_size($result['file_size']),
                'compression_ratio' => $result['compression_ratio'] . '%'
            ]);
        } else {
            $result['errors'] = $conversion_result['errors'];
            $this->logger->log_image_processing('webp_conversion', $input_path, false, [
                'errors' => $conversion_result['errors']
            ]);
        }

        return $result;
    }

    /**
     * Convert image to AVIF format
     *
     * @param string $input_path Input file path
     * @param array $options Conversion options
     * @return array Conversion result
     */
    public function convert_to_avif($input_path, array $options = []) {
        $quality = $options['quality'] ?? $this->options['avif_quality'];
        $effort = $options['effort'] ?? $this->options['avif_effort'];
        $output_path = $options['output_path'] ?? Utils::generate_optimized_filename($input_path, 'avif');

        $result = [
            'success' => false,
            'operation' => 'avif',
            'output_path' => $output_path,
            'file_size' => 0,
            'compression_ratio' => 0,
            'errors' => []
        ];

        // Check if cavif binary is available
        if (!$this->binary_wrapper->is_binary_available('cavif')) {
            $result['errors'][] = 'cavif binary not available';
            return $result;
        }

        // Create output directory if needed
        $output_dir = dirname($output_path);
        if (!Utils::create_directory($output_dir)) {
            $result['errors'][] = 'Failed to create output directory';
            return $result;
        }

        // Convert to AVIF
        $conversion_result = $this->binary_wrapper->convert_to_avif(
            $input_path,
            $output_path,
            $quality,
            $effort
        );

        if ($conversion_result['success']) {
            $result['success'] = true;
            $result['file_size'] = Utils::get_file_size($output_path);
            $original_size = Utils::get_file_size($input_path);
            $result['compression_ratio'] = Utils::calculate_compression_ratio($original_size, $result['file_size']);
            
            $this->logger->log_image_processing('avif_conversion', $input_path, true, [
                'output_file' => basename($output_path),
                'quality' => $quality,
                'original_size' => Utils::format_file_size($original_size),
                'new_size' => Utils::format_file_size($result['file_size']),
                'compression_ratio' => $result['compression_ratio'] . '%'
            ]);
        } else {
            $result['errors'] = $conversion_result['errors'];
            $this->logger->log_image_processing('avif_conversion', $input_path, false, [
                'errors' => $conversion_result['errors']
            ]);
        }

        return $result;
    }

    /**
     * Optimize image in original format
     *
     * @param string $input_path Input file path
     * @param array $options Optimization options
     * @return array Optimization result
     */
    public function optimize_image($input_path, array $options = []) {
        $output_path = $options['output_path'] ?? $input_path;
        $mime_type = Utils::get_image_mime_type($input_path);

        $result = [
            'success' => false,
            'operation' => 'optimize',
            'output_path' => $output_path,
            'file_size' => 0,
            'compression_ratio' => 0,
            'errors' => []
        ];

        $original_size = Utils::get_file_size($input_path);

        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $optimization_result = $this->optimize_jpeg($input_path, $output_path, $options);
                break;
                
            case 'image/png':
                $optimization_result = $this->optimize_png($input_path, $output_path, $options);
                break;
                
            case 'image/gif':
                $optimization_result = $this->optimize_gif($input_path, $output_path, $options);
                break;
                
            default:
                $result['errors'][] = 'Unsupported format for optimization: ' . $mime_type;
                return $result;
        }

        if ($optimization_result['success']) {
            $result['success'] = true;
            $result['file_size'] = Utils::get_file_size($output_path);
            $result['compression_ratio'] = Utils::calculate_compression_ratio($original_size, $result['file_size']);
            
            $this->logger->log_image_processing('optimization', $input_path, true, [
                'format' => $mime_type,
                'original_size' => Utils::format_file_size($original_size),
                'new_size' => Utils::format_file_size($result['file_size']),
                'compression_ratio' => $result['compression_ratio'] . '%'
            ]);
        } else {
            $result['errors'] = $optimization_result['errors'];
            $this->logger->log_image_processing('optimization', $input_path, false, [
                'format' => $mime_type,
                'errors' => $optimization_result['errors']
            ]);
        }

        return $result;
    }

    /**
     * Optimize JPEG image
     *
     * @param string $input_path Input file path
     * @param string $output_path Output file path
     * @param array $options Optimization options
     * @return array Optimization result
     */
    private function optimize_jpeg($input_path, $output_path, array $options = []) {
        $quality = $options['quality'] ?? $this->options['jpeg_quality'];
        $progressive = $options['progressive'] ?? $this->options['progressive_jpeg'];
        $strip_metadata = $options['strip_metadata'] ?? $this->options['strip_metadata'];

        return $this->binary_wrapper->optimize_jpeg(
            $input_path,
            $output_path,
            $quality,
            $progressive,
            $strip_metadata
        );
    }

    /**
     * Optimize PNG image
     *
     * @param string $input_path Input file path
     * @param string $output_path Output file path
     * @param array $options Optimization options
     * @return array Optimization result
     */
    private function optimize_png($input_path, $output_path, array $options = []) {
        $compression = $options['compression'] ?? $this->options['png_compression'];
        $strip_metadata = $options['strip_metadata'] ?? $this->options['strip_metadata'];

        return $this->binary_wrapper->optimize_png(
            $input_path,
            $output_path,
            $compression,
            $strip_metadata
        );
    }

    /**
     * Optimize GIF image
     *
     * @param string $input_path Input file path
     * @param string $output_path Output file path
     * @param array $options Optimization options
     * @return array Optimization result
     */
    private function optimize_gif($input_path, $output_path, array $options = []) {
        $optimization_level = $options['optimization_level'] ?? 3;

        return $this->binary_wrapper->optimize_gif(
            $input_path,
            $output_path,
            $optimization_level
        );
    }

    /**
     * Resize image
     *
     * @param string $input_path Input file path
     * @param array $options Resize options
     * @return array Resize result
     */
    public function resize_image($input_path, array $options = []) {
        $max_width = $options['max_width'] ?? $this->options['max_width'];
        $max_height = $options['max_height'] ?? $this->options['max_height'];
        $output_path = $options['output_path'] ?? $input_path;

        $result = [
            'success' => false,
            'operation' => 'resize',
            'output_path' => $output_path,
            'file_size' => 0,
            'compression_ratio' => 0,
            'errors' => []
        ];

        if ($max_width <= 0 && $max_height <= 0) {
            $result['errors'][] = 'Invalid resize dimensions';
            return $result;
        }

        $dimensions = Utils::get_image_dimensions($input_path);
        if (!$dimensions) {
            $result['errors'][] = 'Could not get image dimensions';
            return $result;
        }

        // Check if resizing is needed
        if (!Utils::needs_resizing($input_path, $max_width, $max_height)) {
            // Copy original file if different output path
            if ($input_path !== $output_path) {
                if (Utils::copy_file($input_path, $output_path)) {
                    $result['success'] = true;
                    $result['file_size'] = Utils::get_file_size($output_path);
                }
            } else {
                $result['success'] = true;
                $result['file_size'] = Utils::get_file_size($input_path);
            }
            return $result;
        }

        // Calculate new dimensions
        $new_dimensions = Utils::calculate_resize_dimensions(
            $dimensions['width'],
            $dimensions['height'],
            $max_width,
            $max_height
        );

        // Perform resize using WordPress functions
        $resize_result = $this->resize_with_wordpress($input_path, $output_path, $new_dimensions);

        if ($resize_result['success']) {
            $result['success'] = true;
            $result['file_size'] = Utils::get_file_size($output_path);
            $original_size = Utils::get_file_size($input_path);
            $result['compression_ratio'] = Utils::calculate_compression_ratio($original_size, $result['file_size']);
            
            $this->logger->log_image_processing('resize', $input_path, true, [
                'original_dimensions' => $dimensions['width'] . 'x' . $dimensions['height'],
                'new_dimensions' => $new_dimensions['width'] . 'x' . $new_dimensions['height'],
                'original_size' => Utils::format_file_size($original_size),
                'new_size' => Utils::format_file_size($result['file_size'])
            ]);
        } else {
            $result['errors'] = $resize_result['errors'];
        }

        return $result;
    }

    /**
     * Resize image using WordPress image functions
     *
     * @param string $input_path Input file path
     * @param string $output_path Output file path
     * @param array $dimensions New dimensions
     * @return array Resize result
     */
    private function resize_with_wordpress($input_path, $output_path, array $dimensions) {
        $result = [
            'success' => false,
            'errors' => []
        ];

        try {
            $image = wp_get_image_editor($input_path);
            
            if (is_wp_error($image)) {
                $result['errors'][] = 'WordPress image editor error: ' . $image->get_error_message();
                return $result;
            }

            $resize_result = $image->resize($dimensions['width'], $dimensions['height'], false);
            
            if (is_wp_error($resize_result)) {
                $result['errors'][] = 'Resize error: ' . $resize_result->get_error_message();
                return $result;
            }

            $save_result = $image->save($output_path);
            
            if (is_wp_error($save_result)) {
                $result['errors'][] = 'Save error: ' . $save_result->get_error_message();
                return $result;
            }

            $result['success'] = true;
        } catch (\Exception $e) {
            $result['errors'][] = 'Resize exception: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Validate input file
     *
     * @param string $file_path File path
     * @return array Validation result
     */
    private function validate_input_file($file_path) {
        $result = [
            'valid' => false,
            'errors' => []
        ];

        if (!file_exists($file_path)) {
            $result['errors'][] = 'Input file does not exist';
            return $result;
        }

        if (!is_readable($file_path)) {
            $result['errors'][] = 'Input file is not readable';
            return $result;
        }

        $mime_type = Utils::get_image_mime_type($file_path);
        if (!in_array($mime_type, $this->supported_input_formats, true)) {
            $result['errors'][] = 'Unsupported input format: ' . $mime_type;
            return $result;
        }

        $validation = Utils::validate_image_file($file_path);
        if (!$validation['valid']) {
            $result['errors'] = array_merge($result['errors'], $validation['errors']);
            return $result;
        }

        $result['valid'] = true;
        return $result;
    }

    /**
     * Check memory requirements for processing
     *
     * @param string $file_path File path
     * @return bool True if enough memory available
     */
    private function check_memory_requirements($file_path) {
        $required_memory = Utils::estimate_memory_requirement($file_path);
        return Utils::has_enough_memory($required_memory);
    }

    /**
     * Create backup of original file
     *
     * @param string $file_path Original file path
     * @return string|false Backup file path or false on failure
     */
    private function create_backup($file_path) {
        $dirs = Utils::get_plugin_upload_dirs();
        $backup_dir = $dirs['backup_dir'] . '/' . date('Y/m');
        
        if (!Utils::create_directory($backup_dir)) {
            return false;
        }

        $filename = basename($file_path);
        $backup_filename = Utils::generate_unique_filename($backup_dir, $filename);
        $backup_path = $backup_dir . '/' . $backup_filename;

        if (Utils::copy_file($file_path, $backup_path)) {
            return $backup_path;
        }

        return false;
    }

    /**
     * Get processing statistics
     *
     * @return array Processing statistics
     */
    public function get_processing_stats() {
        return Utils::get_optimization_stats();
    }

    /**
     * Update processing statistics
     *
     * @param array $stats Statistics to update
     * @return bool Success status
     */
    public function update_processing_stats(array $stats) {
        return Utils::update_optimization_stats($stats);
    }

    /**
     * Get supported input formats
     *
     * @return array Supported input formats
     */
    public function get_supported_input_formats() {
        return $this->supported_input_formats;
    }

    /**
     * Get supported output formats
     *
     * @return array Supported output formats
     */
    public function get_supported_output_formats() {
        return $this->supported_output_formats;
    }

    /**
     * Get binary wrapper instance
     *
     * @return BinaryWrapper Binary wrapper instance
     */
    public function get_binary_wrapper() {
        return $this->binary_wrapper;
    }

    /**
     * Set processing options
     *
     * @param array $options Processing options
     */
    public function set_options(array $options) {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Get processing options
     *
     * @return array Processing options
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * Test image processing capabilities
     *
     * @return array Test results
     */
    public function test_capabilities() {
        $results = [
            'binaries' => [],
            'formats' => [],
            'wordpress_support' => []
        ];

        // Test binary availability
        $binaries = ['cwebp', 'cavif', 'jpegoptim', 'optipng', 'gifsicle'];
        foreach ($binaries as $binary) {
            $results['binaries'][$binary] = $this->binary_wrapper->is_binary_available($binary);
        }

        // Test format support
        foreach ($this->supported_input_formats as $format) {
            $results['formats'][$format] = true; // Basic support always available
        }

        // Test WordPress image editor support
        $results['wordpress_support']['gd'] = extension_loaded('gd');
        $results['wordpress_support']['imagick'] = extension_loaded('imagick');
        $results['wordpress_support']['image_editor'] = function_exists('wp_get_image_editor');

        return $results;
    }
}