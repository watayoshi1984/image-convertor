<?php
/**
 * Utility Functions
 *
 * @package WyoshiImageOptimizer\Common
 * @since 1.0.0
 */

namespace WyoshiImageOptimizer\Common;

/**
 * Utility Functions Class
 *
 * Provides common utility functions for the plugin
 *
 * @since 1.0.0
 */
class Utils {

    /**
     * Supported image MIME types
     *
     * @var array
     */
    const SUPPORTED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/avif'
    ];

    /**
     * Image file extensions
     *
     * @var array
     */
    const IMAGE_EXTENSIONS = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif'
    ];

    /**
     * Check if file is a supported image
     *
     * @param string $file_path File path
     * @return bool True if supported image
     */
    public static function is_supported_image($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        $mime_type = wp_check_filetype($file_path)['type'];
        return in_array($mime_type, self::SUPPORTED_MIME_TYPES, true);
    }

    /**
     * Get image MIME type from file path
     *
     * @param string $file_path File path
     * @return string|false MIME type or false
     */
    public static function get_image_mime_type($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        $file_info = wp_check_filetype($file_path);
        return $file_info['type'] ?: false;
    }

    /**
     * Get file extension from MIME type
     *
     * @param string $mime_type MIME type
     * @return string|false File extension or false
     */
    public static function get_extension_from_mime($mime_type) {
        $extensions = array_flip(self::IMAGE_EXTENSIONS);
        return $extensions[$mime_type] ?? false;
    }

    /**
     * Get MIME type from file extension
     *
     * @param string $extension File extension
     * @return string|false MIME type or false
     */
    public static function get_mime_from_extension($extension) {
        $extension = strtolower(ltrim($extension, '.'));
        return self::IMAGE_EXTENSIONS[$extension] ?? false;
    }

    /**
     * Generate optimized filename
     *
     * @param string $original_path Original file path
     * @param string $format Target format (webp, avif, etc.)
     * @param string $suffix Optional suffix
     * @return string Optimized filename
     */
    public static function generate_optimized_filename($original_path, $format, $suffix = '') {
        $path_info = pathinfo($original_path);
        $dirname = $path_info['dirname'];
        $filename = $path_info['filename'];
        
        $new_filename = $filename;
        if (!empty($suffix)) {
            $new_filename .= '-' . $suffix;
        }
        
        return $dirname . DIRECTORY_SEPARATOR . $new_filename . '.' . $format;
    }

    /**
     * Get file size in bytes
     *
     * @param string $file_path File path
     * @return int|false File size in bytes or false
     */
    public static function get_file_size($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        return filesize($file_path);
    }

    /**
     * Format file size for display
     *
     * @param int $bytes File size in bytes
     * @param int $precision Decimal precision
     * @return string Formatted file size
     */
    public static function format_file_size($bytes, $precision = 2) {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $unit_index = floor($base);
        
        if ($unit_index >= count($units)) {
            $unit_index = count($units) - 1;
        }
        
        $size = round(pow(1024, $base - $unit_index), $precision);
        return $size . ' ' . $units[$unit_index];
    }

    /**
     * Calculate compression ratio
     *
     * @param int $original_size Original file size
     * @param int $compressed_size Compressed file size
     * @return float Compression ratio (0-100)
     */
    public static function calculate_compression_ratio($original_size, $compressed_size) {
        if ($original_size === 0) {
            return 0;
        }

        $savings = $original_size - $compressed_size;
        return round(($savings / $original_size) * 100, 2);
    }

    /**
     * Create directory recursively
     *
     * @param string $directory Directory path
     * @param int $permissions Directory permissions
     * @return bool Success status
     */
    public static function create_directory($directory, $permissions = 0755) {
        if (is_dir($directory)) {
            return true;
        }

        return wp_mkdir_p($directory);
    }

    /**
     * Copy file with error handling
     *
     * @param string $source Source file path
     * @param string $destination Destination file path
     * @return bool Success status
     */
    public static function copy_file($source, $destination) {
        if (!file_exists($source)) {
            return false;
        }

        $destination_dir = dirname($destination);
        if (!self::create_directory($destination_dir)) {
            return false;
        }

        return copy($source, $destination);
    }

    /**
     * Move file with error handling
     *
     * @param string $source Source file path
     * @param string $destination Destination file path
     * @return bool Success status
     */
    public static function move_file($source, $destination) {
        if (!file_exists($source)) {
            return false;
        }

        $destination_dir = dirname($destination);
        if (!self::create_directory($destination_dir)) {
            return false;
        }

        return rename($source, $destination);
    }

    /**
     * Delete file safely
     *
     * @param string $file_path File path
     * @return bool Success status
     */
    public static function delete_file($file_path) {
        if (!file_exists($file_path)) {
            return true;
        }

        return unlink($file_path);
    }

    /**
     * Delete directory recursively
     *
     * @param string $directory Directory path
     * @return bool Success status
     */
    public static function delete_directory($directory) {
        if (!is_dir($directory)) {
            return true;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            if (!$todo($fileinfo->getRealPath())) {
                return false;
            }
        }

        return rmdir($directory);
    }

    /**
     * Get optimization statistics
     *
     * @return array Optimization statistics
     */
    public static function get_optimization_stats() {
        $stats = get_option('wyoshi_img_opt_stats', [
            'total_images' => 0,
            'optimized_images' => 0,
            'total_savings' => 0,
            'webp_generated' => 0,
            'avif_generated' => 0,
            'last_optimization' => null
        ]);

        return $stats;
    }

    /**
     * Update optimization statistics
     *
     * @param array $data Statistics data to update
     * @return bool Success status
     */
    public static function update_optimization_stats($data) {
        $current_stats = self::get_optimization_stats();
        $updated_stats = array_merge($current_stats, $data);
        $updated_stats['last_optimization'] = current_time('mysql');

        return update_option('wyoshi_img_opt_stats', $updated_stats);
    }

    /**
     * Reset optimization statistics
     *
     * @return bool Success status
     */
    public static function reset_optimization_stats() {
        return delete_option('wyoshi_img_opt_stats');
    }

    /**
     * Get system information
     *
     * @return array System information
     */
    public static function get_system_info() {
        global $wp_version;

        $upload_dir = wp_upload_dir();
        
        return [
            'wordpress_version' => $wp_version,
            'php_version' => PHP_VERSION,
            'php_memory_limit' => ini_get('memory_limit'),
            'php_max_execution_time' => ini_get('max_execution_time'),
            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
            'php_post_max_size' => ini_get('post_max_size'),
            'php_extensions' => [
                'gd' => extension_loaded('gd'),
                'imagick' => extension_loaded('imagick'),
                'exif' => extension_loaded('exif'),
                'fileinfo' => extension_loaded('fileinfo')
            ],
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'operating_system' => PHP_OS,
            'upload_dir' => $upload_dir['basedir'],
            'upload_dir_writable' => is_writable($upload_dir['basedir']),
            'plugin_version' => WYOSHI_IMG_OPT_VERSION,
            'plugin_dir' => WYOSHI_IMG_OPT_PLUGIN_DIR,
            'plugin_dir_writable' => is_writable(WYOSHI_IMG_OPT_PLUGIN_DIR)
        ];
    }

    /**
     * Test binary availability
     *
     * @param string $binary_name Binary name
     * @return array Test result
     */
    public static function test_binary($binary_name) {
        $logger = new Logger('binary-test');
        $binary_wrapper = new \AdvancedImageOptimizer\Processing\BinaryWrapper($logger);
        
        return $binary_wrapper->test_binary($binary_name);
    }

    /**
     * Get WordPress upload directory info
     *
     * @return array Upload directory information
     */
    public static function get_upload_dir_info() {
        $upload_dir = wp_upload_dir();
        
        return [
            'basedir' => $upload_dir['basedir'],
            'baseurl' => $upload_dir['baseurl'],
            'path' => $upload_dir['path'],
            'url' => $upload_dir['url'],
            'subdir' => $upload_dir['subdir'],
            'error' => $upload_dir['error']
        ];
    }

    /**
     * Get plugin upload directories
     *
     * @return array Plugin upload directories
     */
    public static function get_plugin_upload_dirs() {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/advanced-image-optimizer';
        $base_url = $upload_dir['baseurl'] . '/advanced-image-optimizer';
        
        return [
            'base_dir' => $base_dir,
            'base_url' => $base_url,
            'backup_dir' => $base_dir . '/backups',
            'backup_url' => $base_url . '/backups',
            'cache_dir' => $base_dir . '/cache',
            'cache_url' => $base_url . '/cache',
            'webp_dir' => $base_dir . '/webp',
            'webp_url' => $base_url . '/webp',
            'avif_dir' => $base_dir . '/avif',
            'avif_url' => $base_url . '/avif'
        ];
    }

    /**
     * Create plugin upload directories
     *
     * @return bool Success status
     */
    public static function create_plugin_upload_dirs() {
        $dirs = self::get_plugin_upload_dirs();
        
        $success = true;
        foreach ($dirs as $key => $dir) {
            if (strpos($key, '_dir') !== false) {
                if (!self::create_directory($dir)) {
                    $success = false;
                }
            }
        }
        
        // Create .htaccess for security
        $htaccess_content = "# Advanced Image Optimizer Security\nOptions -Indexes\n<Files *.php>\nOrder allow,deny\nDeny from all\n</Files>";
        file_put_contents($dirs['base_dir'] . '/.htaccess', $htaccess_content);
        
        return $success;
    }

    /**
     * Cleanup old files
     *
     * @param int $days Days to keep files
     * @return int Number of files deleted
     */
    public static function cleanup_old_files($days = 30) {
        $dirs = self::get_plugin_upload_dirs();
        $cutoff_time = time() - ($days * 24 * 60 * 60);
        $deleted_count = 0;
        
        $cleanup_dirs = [
            $dirs['cache_dir'],
            $dirs['backup_dir'] . '/temp'
        ];
        
        foreach ($cleanup_dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getMTime() < $cutoff_time) {
                    if (unlink($file->getRealPath())) {
                        $deleted_count++;
                    }
                }
            }
        }
        
        return $deleted_count;
    }

    /**
     * Get image dimensions
     *
     * @param string $file_path Image file path
     * @return array|false Image dimensions or false
     */
    public static function get_image_dimensions($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        $image_info = getimagesize($file_path);
        if ($image_info === false) {
            return false;
        }

        return [
            'width' => $image_info[0],
            'height' => $image_info[1],
            'mime_type' => $image_info['mime']
        ];
    }

    /**
     * Check if image needs resizing
     *
     * @param string $file_path Image file path
     * @param int $max_width Maximum width
     * @param int $max_height Maximum height
     * @return bool True if resizing needed
     */
    public static function needs_resizing($file_path, $max_width, $max_height) {
        $dimensions = self::get_image_dimensions($file_path);
        if (!$dimensions) {
            return false;
        }

        return ($dimensions['width'] > $max_width || $dimensions['height'] > $max_height);
    }

    /**
     * Calculate new dimensions for resizing
     *
     * @param int $original_width Original width
     * @param int $original_height Original height
     * @param int $max_width Maximum width
     * @param int $max_height Maximum height
     * @return array New dimensions
     */
    public static function calculate_resize_dimensions($original_width, $original_height, $max_width, $max_height) {
        $ratio = min($max_width / $original_width, $max_height / $original_height);
        
        if ($ratio >= 1) {
            return [
                'width' => $original_width,
                'height' => $original_height
            ];
        }
        
        return [
            'width' => round($original_width * $ratio),
            'height' => round($original_height * $ratio)
        ];
    }

    /**
     * Generate unique filename
     *
     * @param string $directory Directory path
     * @param string $filename Desired filename
     * @return string Unique filename
     */
    public static function generate_unique_filename($directory, $filename) {
        $path_info = pathinfo($filename);
        $name = $path_info['filename'];
        $extension = $path_info['extension'] ?? '';
        
        $counter = 1;
        $unique_filename = $filename;
        
        while (file_exists($directory . DIRECTORY_SEPARATOR . $unique_filename)) {
            $unique_filename = $name . '-' . $counter;
            if (!empty($extension)) {
                $unique_filename .= '.' . $extension;
            }
            $counter++;
        }
        
        return $unique_filename;
    }

    /**
     * Validate image file
     *
     * @param string $file_path Image file path
     * @return array Validation result
     */
    public static function validate_image_file($file_path) {
        $result = [
            'valid' => false,
            'errors' => []
        ];
        
        if (!file_exists($file_path)) {
            $result['errors'][] = 'File does not exist';
            return $result;
        }
        
        if (!is_readable($file_path)) {
            $result['errors'][] = 'File is not readable';
            return $result;
        }
        
        if (!self::is_supported_image($file_path)) {
            $result['errors'][] = 'Unsupported image format';
            return $result;
        }
        
        $dimensions = self::get_image_dimensions($file_path);
        if (!$dimensions) {
            $result['errors'][] = 'Invalid image file';
            return $result;
        }
        
        if ($dimensions['width'] < 1 || $dimensions['height'] < 1) {
            $result['errors'][] = 'Invalid image dimensions';
            return $result;
        }
        
        $result['valid'] = true;
        return $result;
    }

    /**
     * Get memory usage information
     *
     * @return array Memory usage information
     */
    public static function get_memory_usage() {
        return [
            'current' => memory_get_usage(true),
            'current_formatted' => self::format_file_size(memory_get_usage(true)),
            'peak' => memory_get_peak_usage(true),
            'peak_formatted' => self::format_file_size(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit')
        ];
    }

    /**
     * Check if enough memory is available
     *
     * @param int $required_bytes Required memory in bytes
     * @return bool True if enough memory available
     */
    public static function has_enough_memory($required_bytes) {
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit === '-1') {
            return true; // No memory limit
        }
        
        $memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
        $current_usage = memory_get_usage(true);
        $available_memory = $memory_limit_bytes - $current_usage;
        
        return $available_memory >= $required_bytes;
    }

    /**
     * Estimate memory required for image processing
     *
     * @param string $file_path Image file path
     * @return int Estimated memory requirement in bytes
     */
    public static function estimate_memory_requirement($file_path) {
        $dimensions = self::get_image_dimensions($file_path);
        if (!$dimensions) {
            return 0;
        }
        
        // Rough estimation: width * height * 4 bytes per pixel * 2 (for processing)
        return $dimensions['width'] * $dimensions['height'] * 4 * 2;
    }
}