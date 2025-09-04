<?php
/**
 * Delivery Manager Class
 * 
 * Handles frontend image delivery and picture tag replacement
 * 
 * @package WyoshiImageOptimizer
 * @subpackage Delivery
 * @since 1.0.0
 */

namespace WyoshiImageOptimizer\Delivery;

use WyoshiImageOptimizer\Common\Logger;
use WyoshiImageOptimizer\Common\Utils;

class DeliveryManager {
    
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
     * Supported image extensions
     * 
     * @var array
     */
    private $supported_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    /**
     * Constructor
     * 
     * @param Logger $logger Logger instance
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->options = get_option('wyoshi_img_opt_options', []);
    }
    
    /**
     * Initialize delivery hooks
     * 
     * @return void
     */
    public function init() {
        if (!$this->is_delivery_enabled()) {
            return;
        }
        
        // Content filtering
        add_filter('the_content', [$this, 'replace_images_in_content'], 999);
        add_filter('post_thumbnail_html', [$this, 'replace_images_in_content'], 999);
        add_filter('wp_get_attachment_image', [$this, 'replace_images_in_content'], 999);
        
        // Widget content filtering
        add_filter('widget_text', [$this, 'replace_images_in_content'], 999);
        add_filter('widget_custom_html', [$this, 'replace_images_in_content'], 999);
        
        // Template output buffering for full page replacement
        if ($this->should_use_output_buffering()) {
            add_action('template_redirect', [$this, 'start_output_buffering']);
        }
        
        // Image serving endpoint
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'serve_optimized_image']);
        
        // Preload headers
        add_action('wp_head', [$this, 'add_preload_headers']);
        
        // Lazy loading enhancement
        if ($this->is_lazy_loading_enabled()) {
            add_filter('wp_img_tag_add_loading_attr', [$this, 'enhance_lazy_loading'], 10, 3);
        }
    }
    
    /**
     * Replace images in content with picture tags
     * 
     * @param string $content HTML content
     * @return string Modified content
     */
    public function replace_images_in_content($content) {
        if (is_admin() || !$this->should_process_content()) {
            return $content;
        }
        
        // Find all img tags
        $pattern = '/<img[^>]+>/i';
        
        return preg_replace_callback($pattern, [$this, 'replace_img_tag'], $content);
    }
    
    /**
     * Replace single img tag with picture tag
     * 
     * @param array $matches Regex matches
     * @return string Replacement HTML
     */
    private function replace_img_tag($matches) {
        $img_tag = $matches[0];
        
        // Extract src attribute
        if (!preg_match('/src=["\']([^"\'>]+)["\']/', $img_tag, $src_matches)) {
            return $img_tag;
        }
        
        $src = $src_matches[1];
        
        // Check if it's a local image
        if (!$this->is_local_image($src)) {
            return $img_tag;
        }
        
        // Get image path
        $image_path = $this->url_to_path($src);
        
        if (!$image_path || !file_exists($image_path)) {
            return $img_tag;
        }
        
        // Check if supported format
        $extension = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->supported_extensions)) {
            return $img_tag;
        }
        
        // Generate picture tag
        $picture_html = $this->generate_picture_tag($img_tag, $src, $image_path);
        
        return $picture_html ?: $img_tag;
    }
    
    /**
     * Generate picture tag with multiple sources
     * 
     * @param string $original_img Original img tag
     * @param string $src Image source URL
     * @param string $image_path Image file path
     * @return string Picture tag HTML
     */
    private function generate_picture_tag($original_img, $src, $image_path) {
        $base_dir = dirname($image_path);
        $base_name = pathinfo($image_path, PATHINFO_FILENAME);
        $base_url = dirname($src);
        
        // Check for optimized versions
        $avif_path = $base_dir . '/' . $base_name . '.avif';
        $webp_path = $base_dir . '/' . $base_name . '.webp';
        
        $sources = [];
        
        // Add AVIF source if available
        if (file_exists($avif_path) && $this->should_serve_avif()) {
            $avif_url = $base_url . '/' . $base_name . '.avif';
            $sources[] = '<source srcset="' . esc_url($avif_url) . '" type="image/avif">';
        }
        
        // Add WebP source if available
        if (file_exists($webp_path) && $this->should_serve_webp()) {
            $webp_url = $base_url . '/' . $base_name . '.webp';
            $sources[] = '<source srcset="' . esc_url($webp_url) . '" type="image/webp">';
        }
        
        // If no optimized versions available, return original
        if (empty($sources)) {
            return $original_img;
        }
        
        // Extract attributes from original img tag
        $attributes = $this->extract_img_attributes($original_img);
        
        // Build picture tag
        $picture_html = '<picture>';
        
        // Add sources
        foreach ($sources as $source) {
            $picture_html .= $source;
        }
        
        // Add fallback img tag
        $picture_html .= $original_img;
        $picture_html .= '</picture>';
        
        return $picture_html;
    }
    
    /**
     * Extract attributes from img tag
     * 
     * @param string $img_tag IMG tag HTML
     * @return array Attributes array
     */
    private function extract_img_attributes($img_tag) {
        $attributes = [];
        
        // Common attributes to extract
        $attr_patterns = [
            'src' => '/src=["\']([^"\'>]+)["\']/',
            'alt' => '/alt=["\']([^"\'>]*)["\']/',
            'title' => '/title=["\']([^"\'>]*)["\']/',
            'class' => '/class=["\']([^"\'>]*)["\']/',
            'id' => '/id=["\']([^"\'>]*)["\']/',
            'width' => '/width=["\']?([^"\'>\s]+)["\']?/',
            'height' => '/height=["\']?([^"\'>\s]+)["\']?/',
            'loading' => '/loading=["\']([^"\'>]*)["\']/',
            'decoding' => '/decoding=["\']([^"\'>]*)["\']/',
            'sizes' => '/sizes=["\']([^"\'>]*)["\']/',
            'srcset' => '/srcset=["\']([^"\'>]*)["\']/',
        ];
        
        foreach ($attr_patterns as $attr => $pattern) {
            if (preg_match($pattern, $img_tag, $matches)) {
                $attributes[$attr] = $matches[1];
            }
        }
        
        return $attributes;
    }
    
    /**
     * Start output buffering for full page replacement
     * 
     * @return void
     */
    public function start_output_buffering() {
        if (!$this->should_process_content()) {
            return;
        }
        
        ob_start([$this, 'process_output_buffer']);
    }
    
    /**
     * Process output buffer
     * 
     * @param string $buffer Output buffer content
     * @return string Processed content
     */
    public function process_output_buffer($buffer) {
        // Only process HTML content
        if (!$this->is_html_content()) {
            return $buffer;
        }
        
        return $this->replace_images_in_content($buffer);
    }
    
    /**
     * Add rewrite rules for image serving
     * 
     * @return void
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^image-convertor/([^/]+)/(.+)$',
            'index.php?wyoshi_img_opt_format=$matches[1]&wyoshi_img_opt_path=$matches[2]',
            'top'
        );
        
        add_rewrite_tag('%wyoshi_img_opt_format%', '([^&]+)');
        add_rewrite_tag('%wyoshi_img_opt_path%', '([^&]+)');
    }
    
    /**
     * Serve optimized image
     * 
     * @return void
     */
    public function serve_optimized_image() {
        $format = get_query_var('wyoshi_img_opt_format');
        $path = get_query_var('wyoshi_img_opt_path');
        
        if (!$format || !$path) {
            return;
        }
        
        // Validate format
        if (!in_array($format, ['webp', 'avif'])) {
            status_header(400);
            exit('Invalid format');
        }
        
        // Sanitize path
        $path = sanitize_text_field($path);
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/' . $path;
        
        // Security check - ensure file is within uploads directory
        $real_upload_dir = realpath($upload_dir['basedir']);
        $real_file_path = realpath($file_path);
        
        if (!$real_file_path || strpos($real_file_path, $real_upload_dir) !== 0) {
            status_header(403);
            exit('Access denied');
        }
        
        // Check if file exists
        if (!file_exists($file_path)) {
            status_header(404);
            exit('File not found');
        }
        
        // Set appropriate headers
        $mime_type = $format === 'webp' ? 'image/webp' : 'image/avif';
        
        header('Content-Type: ' . $mime_type);
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: public, max-age=31536000'); // 1 year
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        
        // Add ETag for better caching
        $etag = md5_file($file_path);
        header('ETag: "' . $etag . '"');
        
        // Check if client has cached version
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $etag . '"') {
            status_header(304);
            exit();
        }
        
        // Serve file
        readfile($file_path);
        exit();
    }
    
    /**
     * Add preload headers for critical images
     * 
     * @return void
     */
    public function add_preload_headers() {
        if (!$this->should_add_preload_headers()) {
            return;
        }
        
        // Get featured image for preloading
        if (is_singular() && has_post_thumbnail()) {
            $thumbnail_id = get_post_thumbnail_id();
            $image_path = get_attached_file($thumbnail_id);
            
            if ($image_path && $this->is_supported_image($image_path)) {
                $this->add_image_preload_header($image_path);
            }
        }
    }
    
    /**
     * Add preload header for specific image
     * 
     * @param string $image_path Image file path
     * @return void
     */
    private function add_image_preload_header($image_path) {
        $base_dir = dirname($image_path);
        $base_name = pathinfo($image_path, PATHINFO_FILENAME);
        $base_url = $this->path_to_url($image_path);
        $base_url_dir = dirname($base_url);
        
        // Preload AVIF if available and supported
        $avif_path = $base_dir . '/' . $base_name . '.avif';
        if (file_exists($avif_path) && $this->should_serve_avif()) {
            $avif_url = $base_url_dir . '/' . $base_name . '.avif';
            echo '<link rel="preload" as="image" href="' . esc_url($avif_url) . '" type="image/avif">' . "\n";
            return;
        }
        
        // Preload WebP if available and supported
        $webp_path = $base_dir . '/' . $base_name . '.webp';
        if (file_exists($webp_path) && $this->should_serve_webp()) {
            $webp_url = $base_url_dir . '/' . $base_name . '.webp';
            echo '<link rel="preload" as="image" href="' . esc_url($webp_url) . '" type="image/webp">' . "\n";
            return;
        }
        
        // Fallback to original
        echo '<link rel="preload" as="image" href="' . esc_url($base_url) . '">' . "\n";
    }
    
    /**
     * Enhance lazy loading attributes
     * 
     * @param string $value Loading attribute value
     * @param string $image Image HTML
     * @param string $context Context
     * @return string Modified loading value
     */
    public function enhance_lazy_loading($value, $image, $context) {
        // Force lazy loading for non-critical images
        if ($context !== 'the_post_thumbnail' && !$this->is_above_fold_image($image)) {
            return 'lazy';
        }
        
        return $value;
    }
    
    /**
     * Check if image is above the fold
     * 
     * @param string $image Image HTML
     * @return bool True if above fold
     */
    private function is_above_fold_image($image) {
        // Simple heuristic - check for specific classes or contexts
        $above_fold_indicators = [
            'hero',
            'banner',
            'featured',
            'logo',
            'header'
        ];
        
        foreach ($above_fold_indicators as $indicator) {
            if (strpos($image, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if delivery is enabled
     * 
     * @return bool True if enabled
     */
    private function is_delivery_enabled() {
        return !empty($this->options['enable_delivery']);
    }
    
    /**
     * Check if should use output buffering
     * 
     * @return bool True if should use
     */
    private function should_use_output_buffering() {
        return !empty($this->options['use_output_buffering']);
    }
    
    /**
     * Check if lazy loading is enabled
     * 
     * @return bool True if enabled
     */
    private function is_lazy_loading_enabled() {
        return !empty($this->options['enhance_lazy_loading']);
    }
    
    /**
     * Check if should process content
     * 
     * @return bool True if should process
     */
    private function should_process_content() {
        // Skip for admin, feeds, REST API, etc.
        if (is_admin() || is_feed() || wp_doing_ajax() || wp_doing_cron()) {
            return false;
        }
        
        // Skip for REST API requests
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }
        
        // Skip for specific user agents (crawlers, etc.)
        if ($this->is_excluded_user_agent()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if should serve WebP
     * 
     * @return bool True if should serve
     */
    private function should_serve_webp() {
        if (empty($this->options['generate_webp'])) {
            return false;
        }
        
        // Check browser support
        return $this->browser_supports_webp();
    }
    
    /**
     * Check if should serve AVIF
     * 
     * @return bool True if should serve
     */
    private function should_serve_avif() {
        if (empty($this->options['generate_avif'])) {
            return false;
        }
        
        // Check browser support
        return $this->browser_supports_avif();
    }
    
    /**
     * Check if should add preload headers
     * 
     * @return bool True if should add
     */
    private function should_add_preload_headers() {
        return !empty($this->options['add_preload_headers']);
    }
    
    /**
     * Check if browser supports WebP
     * 
     * @return bool True if supported
     */
    private function browser_supports_webp() {
        if (!isset($_SERVER['HTTP_ACCEPT'])) {
            return false;
        }
        
        return strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
    }
    
    /**
     * Check if browser supports AVIF
     * 
     * @return bool True if supported
     */
    private function browser_supports_avif() {
        if (!isset($_SERVER['HTTP_ACCEPT'])) {
            return false;
        }
        
        return strpos($_SERVER['HTTP_ACCEPT'], 'image/avif') !== false;
    }
    
    /**
     * Check if content is HTML
     * 
     * @return bool True if HTML
     */
    private function is_html_content() {
        $content_type = '';
        
        if (function_exists('headers_list')) {
            $headers = headers_list();
            foreach ($headers as $header) {
                if (stripos($header, 'content-type:') === 0) {
                    $content_type = $header;
                    break;
                }
            }
        }
        
        return empty($content_type) || strpos($content_type, 'text/html') !== false;
    }
    
    /**
     * Check if user agent is excluded
     * 
     * @return bool True if excluded
     */
    private function is_excluded_user_agent() {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        $excluded_agents = [
            'Googlebot',
            'Bingbot',
            'Slurp',
            'DuckDuckBot',
            'Baiduspider',
            'YandexBot',
            'facebookexternalhit',
            'Twitterbot',
            'LinkedInBot',
            'WhatsApp',
            'Telegram'
        ];
        
        foreach ($excluded_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if image is local
     * 
     * @param string $src Image source URL
     * @return bool True if local
     */
    private function is_local_image($src) {
        $site_url = site_url();
        $upload_url = wp_upload_dir()['baseurl'];
        
        return strpos($src, $site_url) === 0 || strpos($src, $upload_url) === 0 || strpos($src, '/') === 0;
    }
    
    /**
     * Convert URL to file path
     * 
     * @param string $url Image URL
     * @return string|false File path or false
     */
    private function url_to_path($url) {
        $upload_dir = wp_upload_dir();
        
        // Handle relative URLs
        if (strpos($url, '/') === 0) {
            $url = site_url() . $url;
        }
        
        // Check if it's in uploads directory
        if (strpos($url, $upload_dir['baseurl']) === 0) {
            return str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
        }
        
        // Handle other local URLs
        $site_url = site_url();
        if (strpos($url, $site_url) === 0) {
            $relative_path = str_replace($site_url, '', $url);
            return ABSPATH . ltrim($relative_path, '/');
        }
        
        return false;
    }
    
    /**
     * Convert file path to URL
     * 
     * @param string $path File path
     * @return string|false URL or false
     */
    private function path_to_url($path) {
        $upload_dir = wp_upload_dir();
        
        // Check if it's in uploads directory
        if (strpos($path, $upload_dir['basedir']) === 0) {
            return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $path);
        }
        
        // Handle other paths
        if (strpos($path, ABSPATH) === 0) {
            $relative_path = str_replace(ABSPATH, '', $path);
            return site_url() . '/' . ltrim($relative_path, '/');
        }
        
        return false;
    }
    
    /**
     * Check if image is supported
     * 
     * @param string $image_path Image file path
     * @return bool True if supported
     */
    private function is_supported_image($image_path) {
        $extension = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
        return in_array($extension, $this->supported_extensions);
    }
}