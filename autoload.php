<?php
/**
 * Wyoshi Image Optimizer Autoloader
 *
 * @package WyoshiImageOptimizer
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Autoloader for Wyoshi Image Optimizer classes
 */
spl_autoload_register(function ($class) {
    // Check if this is our namespace
    if (strpos($class, 'WyoshiImageOptimizer\\') !== 0) {
        return;
    }
    
    // Remove the namespace prefix
    $class = substr($class, strlen('WyoshiImageOptimizer\\'));
    
    // Convert namespace separators to directory separators
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    
    // Build the file path
    $file = WYOSHI_IMG_OPT_INCLUDES_DIR . strtolower($class) . '.php';
    
    // Load the file if it exists
    if (file_exists($file)) {
        require_once $file;
    }
});