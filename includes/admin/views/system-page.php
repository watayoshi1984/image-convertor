<?php
/**
 * System Info Page View
 *
 * @package AdvancedImageOptimizer\Admin\Views
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="aio-system-page">
        <!-- System Environment -->
        <div class="aio-system-section">
            <div class="aio-card">
                <div class="aio-card-header">
                    <h3><?php _e('System Environment', 'advanced-image-optimizer'); ?></h3>
                    <span class="dashicons dashicons-admin-tools"></span>
                </div>
                <div class="aio-card-content">
                    <table class="aio-system-table">
                        <tbody>
                            <tr>
                                <td class="aio-system-label"><?php _e('Operating System:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value"><?php echo esc_html($system_info['os'] ?? 'Unknown'); ?></td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Architecture:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value"><?php echo esc_html($system_info['architecture'] ?? 'Unknown'); ?></td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('PHP Version:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? 'aio-status-good' : 'aio-status-warning'; ?>">
                                    <?php echo PHP_VERSION; ?>
                                    <?php if (version_compare(PHP_VERSION, '7.4', '<')): ?>
                                        <span class="aio-warning-text"><?php _e('(Minimum PHP 7.4 recommended)', 'advanced-image-optimizer'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('WordPress Version:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value <?php echo version_compare(get_bloginfo('version'), '5.0', '>=') ? 'aio-status-good' : 'aio-status-warning'; ?>">
                                    <?php echo get_bloginfo('version'); ?>
                                    <?php if (version_compare(get_bloginfo('version'), '5.0', '<')): ?>
                                        <span class="aio-warning-text"><?php _e('(Minimum WordPress 5.0 recommended)', 'advanced-image-optimizer'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Plugin Version:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value"><?php echo WYOSHI_IMG_OPT_VERSION; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- PHP Configuration -->
        <div class="aio-system-section">
            <div class="aio-card">
                <div class="aio-card-header">
                    <h3><?php _e('PHP Configuration', 'advanced-image-optimizer'); ?></h3>
                    <span class="dashicons dashicons-admin-settings"></span>
                </div>
                <div class="aio-card-content">
                    <table class="aio-system-table">
                        <tbody>
                            <tr>
                                <td class="aio-system-label"><?php _e('Memory Limit:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value <?php echo (int)ini_get('memory_limit') >= 256 ? 'aio-status-good' : 'aio-status-warning'; ?>">
                                    <?php echo ini_get('memory_limit'); ?>
                                    <?php if ((int)ini_get('memory_limit') < 256): ?>
                                        <span class="aio-warning-text"><?php _e('(256M or higher recommended)', 'advanced-image-optimizer'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Max Execution Time:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value <?php echo (int)ini_get('max_execution_time') >= 60 || ini_get('max_execution_time') == 0 ? 'aio-status-good' : 'aio-status-warning'; ?>">
                                    <?php echo ini_get('max_execution_time') == 0 ? __('Unlimited', 'advanced-image-optimizer') : ini_get('max_execution_time') . 's'; ?>
                                    <?php if ((int)ini_get('max_execution_time') < 60 && ini_get('max_execution_time') != 0): ?>
                                        <span class="aio-warning-text"><?php _e('(60s or higher recommended)', 'advanced-image-optimizer'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Upload Max Filesize:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value"><?php echo ini_get('upload_max_filesize'); ?></td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Post Max Size:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value"><?php echo ini_get('post_max_size'); ?></td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('GD Extension:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value <?php echo extension_loaded('gd') ? 'aio-status-good' : 'aio-status-error'; ?>">
                                    <?php echo extension_loaded('gd') ? __('Enabled', 'advanced-image-optimizer') : __('Disabled', 'advanced-image-optimizer'); ?>
                                    <?php if (!extension_loaded('gd')): ?>
                                        <span class="aio-error-text"><?php _e('(Required for image processing)', 'advanced-image-optimizer'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Imagick Extension:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value <?php echo extension_loaded('imagick') ? 'aio-status-good' : 'aio-status-warning'; ?>">
                                    <?php echo extension_loaded('imagick') ? __('Enabled', 'advanced-image-optimizer') : __('Disabled', 'advanced-image-optimizer'); ?>
                                    <?php if (!extension_loaded('imagick')): ?>
                                        <span class="aio-warning-text"><?php _e('(Recommended for better performance)', 'advanced-image-optimizer'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Exec Function:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value <?php echo function_exists('exec') ? 'aio-status-good' : 'aio-status-error'; ?>">
                                    <?php echo function_exists('exec') ? __('Available', 'advanced-image-optimizer') : __('Disabled', 'advanced-image-optimizer'); ?>
                                    <?php if (!function_exists('exec')): ?>
                                        <span class="aio-error-text"><?php _e('(Required for binary execution)', 'advanced-image-optimizer'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- WordPress Configuration -->
        <div class="aio-system-section">
            <div class="aio-card">
                <div class="aio-card-header">
                    <h3><?php _e('WordPress Configuration', 'advanced-image-optimizer'); ?></h3>
                    <span class="dashicons dashicons-wordpress"></span>
                </div>
                <div class="aio-card-content">
                    <table class="aio-system-table">
                        <tbody>
                            <?php $upload_dir = wp_upload_dir(); ?>
                            <tr>
                                <td class="aio-system-label"><?php _e('Upload Directory:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value <?php echo is_writable($upload_dir['basedir']) ? 'aio-status-good' : 'aio-status-error'; ?>">
                                    <?php echo esc_html($upload_dir['basedir']); ?>
                                    <br>
                                    <small><?php echo is_writable($upload_dir['basedir']) ? __('Writable', 'advanced-image-optimizer') : __('Not Writable', 'advanced-image-optimizer'); ?></small>
                                </td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Plugin Upload Directory:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value">
                                    <?php
                                    $plugin_upload_dirs = \AdvancedImageOptimizer\Common\Utils::get_plugin_upload_dirs();
                                    echo esc_html($plugin_upload_dirs['base_dir']);
                                    ?>
                                    <br>
                                    <small><?php echo is_writable($plugin_upload_dirs['base_dir']) ? __('Writable', 'advanced-image-optimizer') : __('Not Writable', 'advanced-image-optimizer'); ?></small>
                                </td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Debug Mode:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value">
                                    <?php echo defined('WP_DEBUG') && WP_DEBUG ? __('Enabled', 'advanced-image-optimizer') : __('Disabled', 'advanced-image-optimizer'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Multisite:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value">
                                    <?php echo is_multisite() ? __('Yes', 'advanced-image-optimizer') : __('No', 'advanced-image-optimizer'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Binary Capabilities -->
        <div class="aio-system-section">
            <div class="aio-card">
                <div class="aio-card-header">
                    <h3><?php _e('Optimization Capabilities', 'advanced-image-optimizer'); ?></h3>
                    <button type="button" class="button" id="aio-test-capabilities">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Test Capabilities', 'advanced-image-optimizer'); ?>
                    </button>
                </div>
                <div class="aio-card-content">
                    <div class="aio-capabilities-grid">
                        <?php foreach ($capabilities as $format => $capability): ?>
                            <div class="aio-capability-item">
                                <div class="aio-capability-header">
                                    <h4><?php echo strtoupper($format); ?></h4>
                                    <span class="aio-capability-status <?php echo $capability['available'] ? 'aio-status-good' : 'aio-status-error'; ?>">
                                        <?php if ($capability['available']): ?>
                                            <span class="dashicons dashicons-yes-alt"></span>
                                            <?php _e('Available', 'advanced-image-optimizer'); ?>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-dismiss"></span>
                                            <?php _e('Not Available', 'advanced-image-optimizer'); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="aio-capability-details">
                                    <?php if ($capability['available']): ?>
                                        <?php if (!empty($capability['binary_path'])): ?>
                                            <div class="aio-capability-detail">
                                                <strong><?php _e('Binary Path:', 'advanced-image-optimizer'); ?></strong>
                                                <code><?php echo esc_html($capability['binary_path']); ?></code>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($capability['version'])): ?>
                                            <div class="aio-capability-detail">
                                                <strong><?php _e('Version:', 'advanced-image-optimizer'); ?></strong>
                                                <?php echo esc_html($capability['version']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($capability['test_result'])): ?>
                                            <div class="aio-capability-detail">
                                                <strong><?php _e('Test Result:', 'advanced-image-optimizer'); ?></strong>
                                                <span class="aio-test-result <?php echo $capability['test_result']['success'] ? 'aio-status-good' : 'aio-status-error'; ?>">
                                                    <?php echo $capability['test_result']['success'] ? __('Passed', 'advanced-image-optimizer') : __('Failed', 'advanced-image-optimizer'); ?>
                                                </span>
                                                <?php if (!empty($capability['test_result']['message'])): ?>
                                                    <br><small><?php echo esc_html($capability['test_result']['message']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="aio-capability-detail">
                                            <span class="aio-error-text">
                                                <?php if (!empty($capability['error'])): ?>
                                                    <?php echo esc_html($capability['error']); ?>
                                                <?php else: ?>
                                                    <?php _e('Binary not found or not executable', 'advanced-image-optimizer'); ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Server Information -->
        <div class="aio-system-section">
            <div class="aio-card">
                <div class="aio-card-header">
                    <h3><?php _e('Server Information', 'advanced-image-optimizer'); ?></h3>
                    <span class="dashicons dashicons-admin-network"></span>
                </div>
                <div class="aio-card-content">
                    <table class="aio-system-table">
                        <tbody>
                            <tr>
                                <td class="aio-system-label"><?php _e('Server Software:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value"><?php echo esc_html($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Server IP:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value"><?php echo esc_html($_SERVER['SERVER_ADDR'] ?? 'Unknown'); ?></td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Document Root:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value"><?php echo esc_html($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'); ?></td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('User Agent:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value"><?php echo esc_html($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Plugin Information -->
        <div class="aio-system-section">
            <div class="aio-card">
                <div class="aio-card-header">
                    <h3><?php _e('Plugin Information', 'advanced-image-optimizer'); ?></h3>
                    <span class="dashicons dashicons-admin-plugins"></span>
                </div>
                <div class="aio-card-content">
                    <table class="aio-system-table">
                        <tbody>
                            <tr>
                                <td class="aio-system-label"><?php _e('Plugin Directory:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value"><?php echo esc_html(WYOSHI_IMG_OPT_PLUGIN_DIR); ?></td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Plugin URL:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value"><?php echo esc_html(WYOSHI_IMG_OPT_PLUGIN_URL); ?></td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('Binary Directory:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value">
                                    <?php
                                    $binary_dir = WYOSHI_IMG_OPT_PLUGIN_DIR . 'binaries/' . $system_info['os'] . '/' . $system_info['architecture'] . '/';
                                    echo esc_html($binary_dir);
                                    ?>
                                    <br>
                                    <small><?php echo is_dir($binary_dir) ? __('Exists', 'advanced-image-optimizer') : __('Not Found', 'advanced-image-optimizer'); ?></small>
                                </td>
                            </tr>
                            <tr>
                                <td class="aio-system-label"><?php _e('License Status:', 'advanced-image-optimizer'); ?></td>
                                <td class="aio-system-value">
                                    <?php if (defined('WYOSHI_IMG_OPT_PRO_VERSION')): ?>
                                        <span class="aio-status-good"><?php _e('Pro Version Active', 'advanced-image-optimizer'); ?></span>
                                    <?php else: ?>
                                        <span class="aio-status-info"><?php _e('Free Version', 'advanced-image-optimizer'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Export System Info -->
        <div class="aio-system-section">
            <div class="aio-card">
                <div class="aio-card-header">
                    <h3><?php _e('Export System Information', 'advanced-image-optimizer'); ?></h3>
                </div>
                <div class="aio-card-content">
                    <?php $options = get_option('wyoshi_img_opt_options', []); ?>
                    <p><?php _e('Export system information for support purposes.', 'advanced-image-optimizer'); ?></p>
                    <div class="aio-export-actions">
                        <button type="button" class="button button-primary" id="aio-export-system-info">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Export System Info', 'advanced-image-optimizer'); ?>
                        </button>
                        <button type="button" class="button" id="aio-copy-system-info">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php _e('Copy to Clipboard', 'advanced-image-optimizer'); ?>
                        </button>
                    </div>
                    <textarea id="aio-system-info-text" class="aio-system-info-textarea" readonly style="display: none;"><?php
                        echo "=== Advanced Image Optimizer System Information ===\n\n";
                        echo "Plugin Version: " . WYOSHI_IMG_OPT_VERSION . "\n";
                        echo "WordPress Version: " . get_bloginfo('version') . "\n";
                        echo "PHP Version: " . PHP_VERSION . "\n";
                        echo "Operating System: " . ($system_info['os'] ?? 'Unknown') . "\n";
                        echo "Architecture: " . ($system_info['architecture'] ?? 'Unknown') . "\n";
                        echo "Memory Limit: " . ini_get('memory_limit') . "\n";
                        echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";
                        echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
                        echo "GD Extension: " . (extension_loaded('gd') ? 'Yes' : 'No') . "\n";
                        echo "Imagick Extension: " . (extension_loaded('imagick') ? 'Yes' : 'No') . "\n";
                        echo "Exec Function: " . (function_exists('exec') ? 'Available' : 'Disabled') . "\n";
                        echo "Upload Directory Writable: " . (is_writable(wp_upload_dir()['basedir']) ? 'Yes' : 'No') . "\n";
                        echo "\n=== Optimization Capabilities ===\n";
                        foreach ($capabilities as $format => $capability) {
                            echo strtoupper($format) . ": " . ($capability['available'] ? 'Available' : 'Not Available') . "\n";
                            if ($capability['available'] && !empty($capability['version'])) {
                                echo "  Version: " . $capability['version'] . "\n";
                            }
                            if (!empty($capability['binary_path'])) {
                                echo "  Binary Path: " . $capability['binary_path'] . "\n";
                            }
                        }
                    ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Test capabilities
    $('#aio-test-capabilities').on('click', function() {
        const $button = $(this);
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update aio-spin"></span> <?php _e('Testing...', 'advanced-image-optimizer'); ?>');
        
        $.ajax({
            url: aioAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wyoshi_img_opt_test_binaries',
                nonce: aioAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('<?php _e('Failed to test capabilities.', 'advanced-image-optimizer'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('AJAX error occurred.', 'advanced-image-optimizer'); ?>');
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Export system info
    $('#aio-export-system-info').on('click', function() {
        const systemInfo = $('#aio-system-info-text').val();
        const blob = new Blob([systemInfo], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'advanced-image-optimizer-system-info.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    });
    
    // Copy system info to clipboard
    $('#aio-copy-system-info').on('click', function() {
        const $textarea = $('#aio-system-info-text');
        $textarea.show().select();
        
        try {
            document.execCommand('copy');
            alert('<?php _e('System information copied to clipboard.', 'advanced-image-optimizer'); ?>');
        } catch (err) {
            alert('<?php _e('Failed to copy to clipboard. Please select and copy manually.', 'advanced-image-optimizer'); ?>');
        }
        
        $textarea.hide();
    });
});
</script>