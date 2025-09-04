<?php
/**
 * License Manager Class
 * 
 * Handles Pro version license validation and management
 * 
 * @package WyoshiImageOptimizer
 * @subpackage Pro
 * @since 1.0.0
 */

namespace WyoshiImageOptimizer\Pro;

use WyoshiImageOptimizer\Common\Logger;
use WyoshiImageOptimizer\Common\Utils;

class LicenseManager {
    
    /**
     * License server URL
     * 
     * @var string
     */
    private const LICENSE_SERVER_URL = 'https://api.example.com/license';
    
    /**
     * License check interval (in seconds)
     * 
     * @var int
     */
    private const LICENSE_CHECK_INTERVAL = 86400; // 24 hours
    
    /**
     * Logger instance
     * 
     * @var Logger
     */
    private $logger;
    
    /**
     * License data cache
     * 
     * @var array|null
     */
    private $license_data = null;
    
    /**
     * Constructor
     * 
     * @param Logger $logger Logger instance
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Initialize license manager
     * 
     * @return void
     */
    public function init() {
        // Schedule license check
        add_action('wyoshi_img_opt_license_check', [$this, 'check_license']);
        
        if (!wp_next_scheduled('wyoshi_img_opt_license_check')) {
            wp_schedule_event(time(), 'daily', 'wyoshi_img_opt_license_check');
        }
        
        // AJAX handlers
        add_action('wp_ajax_wyoshi_img_opt_activate_license', [$this, 'ajax_activate_license']);
        add_action('wp_ajax_wyoshi_img_opt_deactivate_license', [$this, 'ajax_deactivate_license']);
        add_action('wp_ajax_wyoshi_img_opt_check_license', [$this, 'ajax_check_license']);
        
        // Load license data
        $this->load_license_data();
    }
    
    /**
     * Activate license
     * 
     * @param string $license_key License key
     * @return array Activation result
     */
    public function activate_license($license_key) {
        try {
            $license_key = sanitize_text_field(trim($license_key));
            
            if (empty($license_key)) {
                throw new \Exception('ライセンスキーが入力されていません。');
            }
            
            // Validate license key format
            if (!$this->validate_license_key_format($license_key)) {
                throw new \Exception('ライセンスキーの形式が正しくありません。');
            }
            
            // Make API request to activate license
            $response = $this->make_license_request('activate', [
                'license_key' => $license_key,
                'site_url' => home_url(),
                'product' => 'image-convertor-pro'
            ]);
            
            if ($response['success']) {
                // Store license data
                $license_data = [
                    'key' => $license_key,
                    'status' => 'active',
                    'expires' => $response['data']['expires'] ?? null,
                    'customer_name' => $response['data']['customer_name'] ?? '',
                    'customer_email' => $response['data']['customer_email'] ?? '',
                    'activations_left' => $response['data']['activations_left'] ?? 0,
                    'activated_at' => current_time('mysql'),
                    'last_checked' => current_time('mysql')
                ];
                
                $this->save_license_data($license_data);
                
                $this->logger->info('License activated successfully', [
                    'license_key' => substr($license_key, 0, 8) . '...',
                    'customer_name' => $license_data['customer_name']
                ]);
                
                return [
                    'success' => true,
                    'message' => 'ライセンスが正常にアクティベートされました。',
                    'data' => $license_data
                ];
            } else {
                throw new \Exception($response['message'] ?? 'ライセンスのアクティベートに失敗しました。');
            }
            
        } catch (\Exception $e) {
            $this->logger->error('License activation failed', [
                'error' => $e->getMessage(),
                'license_key' => isset($license_key) ? substr($license_key, 0, 8) . '...' : 'N/A'
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Deactivate license
     * 
     * @return array Deactivation result
     */
    public function deactivate_license() {
        try {
            $license_data = $this->get_license_data();
            
            if (!$license_data || empty($license_data['key'])) {
                throw new \Exception('アクティブなライセンスが見つかりません。');
            }
            
            // Make API request to deactivate license
            $response = $this->make_license_request('deactivate', [
                'license_key' => $license_data['key'],
                'site_url' => home_url()
            ]);
            
            // Always clear local license data, even if API call fails
            $this->clear_license_data();
            
            $this->logger->info('License deactivated', [
                'license_key' => substr($license_data['key'], 0, 8) . '...'
            ]);
            
            return [
                'success' => true,
                'message' => 'ライセンスが正常にディアクティベートされました。'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('License deactivation failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check license status
     * 
     * @param bool $force_check Force remote check
     * @return array License status
     */
    public function check_license($force_check = false) {
        try {
            $license_data = $this->get_license_data();
            
            if (!$license_data || empty($license_data['key'])) {
                return [
                    'success' => false,
                    'status' => 'inactive',
                    'message' => 'ライセンスがアクティベートされていません。'
                ];
            }
            
            // Check if we need to verify with server
            $last_checked = strtotime($license_data['last_checked'] ?? '0');
            $should_check = $force_check || (time() - $last_checked) > self::LICENSE_CHECK_INTERVAL;
            
            if ($should_check) {
                // Make API request to check license
                $response = $this->make_license_request('check', [
                    'license_key' => $license_data['key'],
                    'site_url' => home_url()
                ]);
                
                if ($response['success']) {
                    // Update license data
                    $license_data['status'] = $response['data']['status'] ?? 'active';
                    $license_data['expires'] = $response['data']['expires'] ?? null;
                    $license_data['last_checked'] = current_time('mysql');
                    
                    $this->save_license_data($license_data);
                } else {
                    // Mark license as invalid if server says so
                    if (isset($response['data']['status']) && $response['data']['status'] === 'invalid') {
                        $license_data['status'] = 'invalid';
                        $this->save_license_data($license_data);
                    }
                }
            }
            
            // Check expiration
            if (!empty($license_data['expires'])) {
                $expires = strtotime($license_data['expires']);
                if ($expires && $expires < time()) {
                    $license_data['status'] = 'expired';
                    $this->save_license_data($license_data);
                }
            }
            
            return [
                'success' => $license_data['status'] === 'active',
                'status' => $license_data['status'],
                'data' => $license_data,
                'message' => $this->get_status_message($license_data['status'])
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('License check failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'ライセンスの確認中にエラーが発生しました。'
            ];
        }
    }
    
    /**
     * Check if license is active
     * 
     * @return bool True if license is active
     */
    public function is_license_active() {
        $license_check = $this->check_license();
        return $license_check['success'] && $license_check['status'] === 'active';
    }
    
    /**
     * Check if feature is available
     * 
     * @param string $feature Feature name
     * @return bool True if feature is available
     */
    public function is_feature_available($feature) {
        // Always allow basic features
        $basic_features = ['webp_conversion', 'basic_optimization'];
        
        if (in_array($feature, $basic_features)) {
            return true;
        }
        
        // Pro features require active license
        $pro_features = ['avif_conversion', 'bulk_optimization', 'advanced_settings', 'priority_support'];
        
        if (in_array($feature, $pro_features)) {
            return $this->is_license_active();
        }
        
        return false;
    }
    
    /**
     * Get license data
     * 
     * @return array|null License data
     */
    public function get_license_data() {
        if ($this->license_data === null) {
            $this->load_license_data();
        }
        
        return $this->license_data;
    }
    
    /**
     * AJAX handler for license activation
     * 
     * @return void
     */
    public function ajax_activate_license() {
        check_ajax_referer('wyoshi_img_opt_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限が不足しています。');
        }
        
        $license_key = sanitize_text_field($_POST['license_key'] ?? '');
        $result = $this->activate_license($license_key);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for license deactivation
     * 
     * @return void
     */
    public function ajax_deactivate_license() {
        check_ajax_referer('wyoshi_img_opt_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限が不足しています。');
        }
        
        $result = $this->deactivate_license();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for license check
     * 
     * @return void
     */
    public function ajax_check_license() {
        check_ajax_referer('wyoshi_img_opt_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限が不足しています。');
        }
        
        $result = $this->check_license(true);
        wp_send_json_success($result);
    }
    
    /**
     * Load license data from database
     * 
     * @return void
     */
    private function load_license_data() {
        $this->license_data = get_option('wyoshi_img_opt_license_data', null);
    }
    
    /**
     * Save license data to database
     * 
     * @param array $license_data License data
     * @return void
     */
    private function save_license_data($license_data) {
        $this->license_data = $license_data;
        update_option('wyoshi_img_opt_license_data', $license_data);
    }
    
    /**
     * Clear license data from database
     * 
     * @return void
     */
    private function clear_license_data() {
        $this->license_data = null;
        delete_option('wyoshi_img_opt_license_data');
    }
    
    /**
     * Validate license key format
     * 
     * @param string $license_key License key
     * @return bool True if format is valid
     */
    private function validate_license_key_format($license_key) {
        // Expected format: XXXX-XXXX-XXXX-XXXX (32 characters including dashes)
        return preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $license_key);
    }
    
    /**
     * Make license API request
     * 
     * @param string $action API action
     * @param array $data Request data
     * @return array API response
     */
    private function make_license_request($action, $data) {
        $url = self::LICENSE_SERVER_URL . '/' . $action;
        
        $request_data = array_merge($data, [
            'version' => WYOSHI_IMG_OPT_VERSION,
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version')
        ]);
        
        $response = wp_remote_post($url, [
            'timeout' => 30,
            'body' => $request_data,
            'headers' => [
                'User-Agent' => 'Wyoshi Image Optimizer Pro/' . WYOSHI_IMG_OPT_VERSION
            ]
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception('ライセンスサーバーとの通信に失敗しました: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            throw new \Exception('ライセンスサーバーからエラーが返されました (HTTP ' . $response_code . ')');
        }
        
        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('ライセンスサーバーからの応答が正しくありません。');
        }
        
        return $decoded_response;
    }
    
    /**
     * Get status message for license status
     * 
     * @param string $status License status
     * @return string Status message
     */
    private function get_status_message($status) {
        $messages = [
            'active' => 'ライセンスは有効です。',
            'inactive' => 'ライセンスがアクティベートされていません。',
            'expired' => 'ライセンスの有効期限が切れています。',
            'invalid' => 'ライセンスが無効です。',
            'suspended' => 'ライセンスが一時停止されています。',
            'error' => 'ライセンスの確認中にエラーが発生しました。'
        ];
        
        return $messages[$status] ?? 'ライセンスの状態が不明です。';
    }
}