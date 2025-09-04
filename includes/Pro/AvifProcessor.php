<?php
/**
 * AVIF Processor Class
 * 
 * Handles AVIF image format conversion (Pro feature)
 * 
 * @package WyoshiImageOptimizer
 * @subpackage Pro
 * @since 1.0.0
 */

namespace WyoshiImageOptimizer\Pro;

use WyoshiImageOptimizer\Common\Logger;
use WyoshiImageOptimizer\Processing\BinaryWrapper;
use WyoshiImageOptimizer\Common\Utils;

class AvifProcessor {
    
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
     * License manager instance
     * 
     * @var LicenseManager
     */
    private $license_manager;
    
    /**
     * Default AVIF quality
     * 
     * @var int
     */
    private const DEFAULT_QUALITY = 80;
    
    /**
     * Constructor
     * 
     * @param BinaryWrapper $binary_wrapper Binary wrapper instance
     * @param Logger $logger Logger instance
     * @param LicenseManager $license_manager License manager instance
     */
    public function __construct(BinaryWrapper $binary_wrapper, Logger $logger, LicenseManager $license_manager) {
        $this->binary_wrapper = $binary_wrapper;
        $this->logger = $logger;
        $this->license_manager = $license_manager;
    }
    
    /**
     * Check if AVIF conversion is available
     * 
     * @return bool True if AVIF conversion is available
     */
    public function is_available() {
        // Check license
        if (!$this->license_manager->is_feature_available('avif_conversion')) {
            return false;
        }
        
        // Check binary availability
        return $this->binary_wrapper->has_binary('cavif') || $this->binary_wrapper->has_binary('avifenc');
    }
    
    /**
     * Convert image to AVIF format
     * 
     * @param string $source_path Source image path
     * @param string $output_path Output AVIF path
     * @param array $options Conversion options
     * @return array Conversion result
     */
    public function convert_to_avif($source_path, $output_path, $options = []) {
        try {
            // Check if AVIF conversion is available
            if (!$this->is_available()) {
                throw new \Exception('AVIF conversion is not available. Pro license required.');
            }
            
            // Validate input file
            if (!file_exists($source_path)) {
                throw new \Exception('Source file does not exist: ' . $source_path);
            }
            
            if (!Utils::is_supported_image($source_path)) {
                throw new \Exception('Unsupported image format: ' . $source_path);
            }
            
            // Parse options
            $quality = intval($options['quality'] ?? self::DEFAULT_QUALITY);
            $quality = max(1, min(100, $quality)); // Clamp between 1-100
            
            $speed = intval($options['speed'] ?? 6); // 0-10, higher is faster but lower quality
            $speed = max(0, min(10, $speed));
            
            $effort = intval($options['effort'] ?? 4); // 0-9, higher is slower but better compression
            $effort = max(0, min(9, $effort));
            
            // Create output directory if needed
            $output_dir = dirname($output_path);
            if (!file_exists($output_dir)) {
                wp_mkdir_p($output_dir);
            }
            
            // Try different AVIF encoders
            $result = $this->try_avif_conversion($source_path, $output_path, $quality, $speed, $effort);
            
            if ($result['success']) {
                $this->logger->info('AVIF conversion successful', [
                    'source' => basename($source_path),
                    'output' => basename($output_path),
                    'original_size' => filesize($source_path),
                    'avif_size' => filesize($output_path),
                    'compression_ratio' => round((1 - filesize($output_path) / filesize($source_path)) * 100, 2) . '%',
                    'quality' => $quality
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('AVIF conversion failed', [
                'source' => basename($source_path ?? 'unknown'),
                'output' => basename($output_path ?? 'unknown'),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Try AVIF conversion with different encoders
     * 
     * @param string $source_path Source image path
     * @param string $output_path Output AVIF path
     * @param int $quality Quality setting
     * @param int $speed Speed setting
     * @param int $effort Effort setting
     * @return array Conversion result
     */
    private function try_avif_conversion($source_path, $output_path, $quality, $speed, $effort) {
        // Try cavif first (usually faster)
        if ($this->binary_wrapper->has_binary('cavif')) {
            $result = $this->convert_with_cavif($source_path, $output_path, $quality, $speed);
            if ($result['success']) {
                return $result;
            }
        }
        
        // Try avifenc as fallback
        if ($this->binary_wrapper->has_binary('avifenc')) {
            $result = $this->convert_with_avifenc($source_path, $output_path, $quality, $speed, $effort);
            if ($result['success']) {
                return $result;
            }
        }
        
        // Try ImageMagick as last resort
        if ($this->binary_wrapper->has_binary('magick') || $this->binary_wrapper->has_binary('convert')) {
            $result = $this->convert_with_imagemagick($source_path, $output_path, $quality);
            if ($result['success']) {
                return $result;
            }
        }
        
        throw new \Exception('No suitable AVIF encoder found');
    }
    
    /**
     * Convert using cavif
     * 
     * @param string $source_path Source image path
     * @param string $output_path Output AVIF path
     * @param int $quality Quality setting
     * @param int $speed Speed setting
     * @return array Conversion result
     */
    private function convert_with_cavif($source_path, $output_path, $quality, $speed) {
        $binary = $this->binary_wrapper->get_binary_path('cavif');
        
        $args = [
            '--quality=' . $quality,
            '--speed=' . $speed,
            '--overwrite',
            escapeshellarg($source_path),
            '--output=' . escapeshellarg($output_path)
        ];
        
        $command = $binary . ' ' . implode(' ', $args);
        
        return $this->binary_wrapper->execute_command($command, [
            'timeout' => 60,
            'description' => 'AVIF conversion with cavif'
        ]);
    }
    
    /**
     * Convert using avifenc
     * 
     * @param string $source_path Source image path
     * @param string $output_path Output AVIF path
     * @param int $quality Quality setting
     * @param int $speed Speed setting
     * @param int $effort Effort setting
     * @return array Conversion result
     */
    private function convert_with_avifenc($source_path, $output_path, $quality, $speed, $effort) {
        $binary = $this->binary_wrapper->get_binary_path('avifenc');
        
        // Convert quality (0-100) to quantizer (0-63, lower is better)
        $quantizer = round((100 - $quality) * 63 / 100);
        
        $args = [
            '--min=' . $quantizer,
            '--max=' . $quantizer,
            '--speed=' . $speed,
            '--jobs=1', // Single threaded for stability
            escapeshellarg($source_path),
            escapeshellarg($output_path)
        ];
        
        $command = $binary . ' ' . implode(' ', $args);
        
        return $this->binary_wrapper->execute_command($command, [
            'timeout' => 120,
            'description' => 'AVIF conversion with avifenc'
        ]);
    }
    
    /**
     * Convert using ImageMagick
     * 
     * @param string $source_path Source image path
     * @param string $output_path Output AVIF path
     * @param int $quality Quality setting
     * @return array Conversion result
     */
    private function convert_with_imagemagick($source_path, $output_path, $quality) {
        $binary = $this->binary_wrapper->get_binary_path('magick') ?: $this->binary_wrapper->get_binary_path('convert');
        
        $args = [
            escapeshellarg($source_path),
            '-quality', $quality,
            '-define', 'heic:speed=2',
            escapeshellarg($output_path)
        ];
        
        $command = $binary . ' ' . implode(' ', $args);
        
        return $this->binary_wrapper->execute_command($command, [
            'timeout' => 90,
            'description' => 'AVIF conversion with ImageMagick'
        ]);
    }
    
    /**
     * Get optimal AVIF settings for image
     * 
     * @param string $image_path Image path
     * @param array $user_options User-defined options
     * @return array Optimal settings
     */
    public function get_optimal_settings($image_path, $user_options = []) {
        $image_info = getimagesize($image_path);
        
        if (!$image_info) {
            return $this->get_default_settings();
        }
        
        $width = $image_info[0];
        $height = $image_info[1];
        $pixels = $width * $height;
        
        // Adjust settings based on image size
        $settings = [
            'quality' => self::DEFAULT_QUALITY,
            'speed' => 6,
            'effort' => 4
        ];
        
        // For large images, prioritize speed over quality
        if ($pixels > 2000000) { // > 2MP
            $settings['speed'] = 8;
            $settings['effort'] = 2;
        } elseif ($pixels > 500000) { // > 0.5MP
            $settings['speed'] = 7;
            $settings['effort'] = 3;
        }
        
        // For small images, prioritize quality
        if ($pixels < 100000) { // < 0.1MP
            $settings['quality'] = min(90, $settings['quality'] + 10);
            $settings['speed'] = 4;
            $settings['effort'] = 6;
        }
        
        // Apply user overrides
        return array_merge($settings, $user_options);
    }
    
    /**
     * Get default AVIF settings
     * 
     * @return array Default settings
     */
    public function get_default_settings() {
        return [
            'quality' => self::DEFAULT_QUALITY,
            'speed' => 6,
            'effort' => 4
        ];
    }
    
    /**
     * Check if browser supports AVIF
     * 
     * @param string $user_agent User agent string
     * @return bool True if AVIF is supported
     */
    public function browser_supports_avif($user_agent = null) {
        if ($user_agent === null) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        
        // Check Accept header first
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'image/avif') !== false) {
            return true;
        }
        
        // Fallback to user agent detection
        // Chrome 85+, Firefox 93+, Safari 16+
        if (preg_match('/Chrome\/(\d+)/', $user_agent, $matches)) {
            return intval($matches[1]) >= 85;
        }
        
        if (preg_match('/Firefox\/(\d+)/', $user_agent, $matches)) {
            return intval($matches[1]) >= 93;
        }
        
        if (preg_match('/Version\/(\d+).*Safari/', $user_agent, $matches)) {
            return intval($matches[1]) >= 16;
        }
        
        // Edge 85+ (Chromium-based)
        if (preg_match('/Edg\/(\d+)/', $user_agent, $matches)) {
            return intval($matches[1]) >= 85;
        }
        
        return false;
    }
    
    /**
     * Get AVIF file size estimate
     * 
     * @param string $source_path Source image path
     * @param int $quality Quality setting
     * @return int Estimated file size in bytes
     */
    public function estimate_avif_size($source_path, $quality = null) {
        if (!file_exists($source_path)) {
            return 0;
        }
        
        $original_size = filesize($source_path);
        $quality = $quality ?? self::DEFAULT_QUALITY;
        
        // AVIF typically achieves 50-80% compression compared to JPEG
        // and even better compression compared to PNG
        $image_info = getimagesize($source_path);
        
        if (!$image_info) {
            return round($original_size * 0.3); // Conservative estimate
        }
        
        $mime_type = $image_info['mime'];
        
        // Compression ratios based on format and quality
        $compression_ratios = [
            'image/jpeg' => [
                90 => 0.7,
                80 => 0.5,
                70 => 0.4,
                60 => 0.3,
                50 => 0.25
            ],
            'image/png' => [
                90 => 0.4,
                80 => 0.3,
                70 => 0.25,
                60 => 0.2,
                50 => 0.15
            ],
            'image/webp' => [
                90 => 0.8,
                80 => 0.6,
                70 => 0.5,
                60 => 0.4,
                50 => 0.3
            ]
        ];
        
        $ratios = $compression_ratios[$mime_type] ?? $compression_ratios['image/jpeg'];
        
        // Find closest quality setting
        $closest_quality = 80;
        $min_diff = abs($quality - $closest_quality);
        
        foreach (array_keys($ratios) as $q) {
            $diff = abs($quality - $q);
            if ($diff < $min_diff) {
                $min_diff = $diff;
                $closest_quality = $q;
            }
        }
        
        $ratio = $ratios[$closest_quality];
        
        return round($original_size * $ratio);
    }
    
    /**
     * Validate AVIF file
     * 
     * @param string $file_path AVIF file path
     * @return bool True if valid AVIF file
     */
    public function validate_avif_file($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        // Check file size
        if (filesize($file_path) === 0) {
            return false;
        }
        
        // Check AVIF magic bytes
        $handle = fopen($file_path, 'rb');
        if (!$handle) {
            return false;
        }
        
        // Read first 12 bytes
        $header = fread($handle, 12);
        fclose($handle);
        
        // AVIF files start with specific byte patterns
        // Look for 'ftypavif' or 'ftypavis' in the header
        return strpos($header, 'avif') !== false || strpos($header, 'avis') !== false;
    }
    
    /**
     * Get AVIF conversion statistics
     * 
     * @return array Statistics
     */
    public function get_conversion_stats() {
        $stats = get_option('wyoshi_img_opt_avif_stats', [
            'total_conversions' => 0,
            'total_original_size' => 0,
            'total_avif_size' => 0,
            'average_compression' => 0,
            'last_conversion' => null
        ]);
        
        return $stats;
    }
    
    /**
     * Update AVIF conversion statistics
     * 
     * @param int $original_size Original file size
     * @param int $avif_size AVIF file size
     * @return void
     */
    public function update_conversion_stats($original_size, $avif_size) {
        $stats = $this->get_conversion_stats();
        
        $stats['total_conversions']++;
        $stats['total_original_size'] += $original_size;
        $stats['total_avif_size'] += $avif_size;
        $stats['last_conversion'] = current_time('mysql');
        
        // Calculate average compression
        if ($stats['total_original_size'] > 0) {
            $stats['average_compression'] = round(
                (1 - $stats['total_avif_size'] / $stats['total_original_size']) * 100,
                2
            );
        }
        
        update_option('wyoshi_img_opt_avif_stats', $stats);
    }
}