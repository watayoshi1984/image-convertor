<?php
/**
 * Main Admin Page View
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
    
    <div class="aio-main-page">
        <!-- Dashboard Cards -->
        <div class="aio-dashboard-cards">
            <div class="aio-card aio-stats-card">
                <div class="aio-card-header">
                    <h3><?php _e('Optimization Statistics', 'advanced-image-optimizer'); ?></h3>
                    <span class="dashicons dashicons-chart-bar"></span>
                </div>
                <div class="aio-card-content">
                    <div class="aio-stat-item">
                        <span class="aio-stat-number"><?php echo number_format($stats['optimized_images'] ?? 0); ?></span>
                        <span class="aio-stat-label"><?php _e('Images Optimized', 'advanced-image-optimizer'); ?></span>
                    </div>
                    <div class="aio-stat-item">
                        <span class="aio-stat-number"><?php echo \AdvancedImageOptimizer\Common\Utils::format_file_size($stats['total_savings'] ?? 0); ?></span>
                        <span class="aio-stat-label"><?php _e('Total Savings', 'advanced-image-optimizer'); ?></span>
                    </div>
                    <div class="aio-stat-item">
                        <span class="aio-stat-number"><?php echo number_format($stats['webp_generated'] ?? 0); ?></span>
                        <span class="aio-stat-label"><?php _e('WebP Generated', 'advanced-image-optimizer'); ?></span>
                    </div>
                    <div class="aio-stat-item">
                        <span class="aio-stat-number"><?php echo number_format($stats['avif_generated'] ?? 0); ?></span>
                        <span class="aio-stat-label"><?php _e('AVIF Generated', 'advanced-image-optimizer'); ?></span>
                    </div>
                </div>
            </div>

            <div class="aio-card aio-system-card">
                <div class="aio-card-header">
                    <h3><?php _e('System Status', 'advanced-image-optimizer'); ?></h3>
                    <span class="dashicons dashicons-admin-tools"></span>
                </div>
                <div class="aio-card-content">
                    <div class="aio-system-item">
                        <span class="aio-system-label"><?php _e('PHP Version:', 'advanced-image-optimizer'); ?></span>
                        <span class="aio-system-value <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? 'aio-status-good' : 'aio-status-warning'; ?>">
                            <?php echo PHP_VERSION; ?>
                        </span>
                    </div>
                    <div class="aio-system-item">
                        <span class="aio-system-label"><?php _e('WordPress Version:', 'advanced-image-optimizer'); ?></span>
                        <span class="aio-system-value <?php echo version_compare(get_bloginfo('version'), '5.0', '>=') ? 'aio-status-good' : 'aio-status-warning'; ?>">
                            <?php echo get_bloginfo('version'); ?>
                        </span>
                    </div>
                    <div class="aio-system-item">
                        <span class="aio-system-label"><?php _e('Memory Limit:', 'advanced-image-optimizer'); ?></span>
                        <span class="aio-system-value"><?php echo ini_get('memory_limit'); ?></span>
                    </div>
                    <div class="aio-system-item">
                        <span class="aio-system-label"><?php _e('Upload Directory:', 'advanced-image-optimizer'); ?></span>
                        <span class="aio-system-value <?php echo is_writable(wp_upload_dir()['basedir']) ? 'aio-status-good' : 'aio-status-error'; ?>">
                            <?php echo is_writable(wp_upload_dir()['basedir']) ? __('Writable', 'advanced-image-optimizer') : __('Not Writable', 'advanced-image-optimizer'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="aio-card aio-capabilities-card">
                <div class="aio-card-header">
                    <h3><?php _e('Optimization Capabilities', 'advanced-image-optimizer'); ?></h3>
                    <span class="dashicons dashicons-format-image"></span>
                </div>
                <div class="aio-card-content">
                    <?php foreach ($capabilities as $format => $capability): ?>
                        <div class="aio-capability-item">
                            <span class="aio-capability-format"><?php echo strtoupper($format); ?></span>
                            <span class="aio-capability-status <?php echo $capability['available'] ? 'aio-status-good' : 'aio-status-error'; ?>">
                                <?php if ($capability['available']): ?>
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php _e('Available', 'advanced-image-optimizer'); ?>
                                    <?php if (!empty($capability['version'])): ?>
                                        <small>(v<?php echo esc_html($capability['version']); ?>)</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-dismiss"></span>
                                    <?php _e('Not Available', 'advanced-image-optimizer'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="aio-quick-actions">
            <div class="aio-card">
                <div class="aio-card-header">
                    <h3><?php _e('Quick Actions', 'advanced-image-optimizer'); ?></h3>
                    <span class="dashicons dashicons-controls-play"></span>
                </div>
                <div class="aio-card-content">
                    <div class="aio-action-buttons">
                        <button type="button" class="button button-primary aio-bulk-optimize-btn" id="aio-start-bulk-optimize">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Start Bulk Optimization', 'advanced-image-optimizer'); ?>
                        </button>
                        
                        <button type="button" class="button aio-test-binaries-btn" id="aio-test-binaries">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php _e('Test Binaries', 'advanced-image-optimizer'); ?>
                        </button>
                        
                        <a href="<?php echo admin_url('admin.php?page=advanced-image-optimizer-settings'); ?>" class="button">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Settings', 'advanced-image-optimizer'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=advanced-image-optimizer-stats'); ?>" class="button">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <?php _e('View Statistics', 'advanced-image-optimizer'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Optimization Progress -->
        <div class="aio-bulk-progress" id="aio-bulk-progress" style="display: none;">
            <div class="aio-card">
                <div class="aio-card-header">
                    <h3><?php _e('Bulk Optimization Progress', 'advanced-image-optimizer'); ?></h3>
                    <button type="button" class="button aio-stop-bulk" id="aio-stop-bulk">
                        <?php _e('Stop', 'advanced-image-optimizer'); ?>
                    </button>
                </div>
                <div class="aio-card-content">
                    <div class="aio-progress-bar">
                        <div class="aio-progress-fill" id="aio-progress-fill" style="width: 0%;"></div>
                    </div>
                    <div class="aio-progress-info">
                        <span id="aio-progress-text"><?php _e('Preparing...', 'advanced-image-optimizer'); ?></span>
                        <span id="aio-progress-stats"></span>
                    </div>
                    <div class="aio-progress-log" id="aio-progress-log"></div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="aio-recent-activity">
            <div class="aio-card">
                <div class="aio-card-header">
                    <h3><?php _e('Recent Activity', 'advanced-image-optimizer'); ?></h3>
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="aio-card-content">
                    <?php
                    // Get recent optimizations from logs
                    $logger = new \AdvancedImageOptimizer\Common\Logger('optimization');
                    $recent_logs = $logger->get_log_contents(10);
                    
                    if (!empty($recent_logs)):
                    ?>
                        <div class="aio-activity-list">
                            <?php foreach (array_slice($recent_logs, 0, 5) as $log_entry): ?>
                                <?php
                                $log_data = json_decode($log_entry, true);
                                if (!$log_data) continue;
                                ?>
                                <div class="aio-activity-item">
                                    <div class="aio-activity-time">
                                        <?php echo date_i18n(get_option('time_format'), strtotime($log_data['timestamp'])); ?>
                                    </div>
                                    <div class="aio-activity-message">
                                        <?php echo esc_html($log_data['message']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="aio-activity-footer">
                            <a href="<?php echo admin_url('admin.php?page=advanced-image-optimizer-logs&context=optimization'); ?>" class="button button-small">
                                <?php _e('View All Logs', 'advanced-image-optimizer'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="aio-no-activity">
                            <?php _e('No recent optimization activity.', 'advanced-image-optimizer'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pro Features Promotion -->
        <?php if (!defined('ADVANCED_IMAGE_OPTIMIZER_PRO_VERSION')): ?>
        <div class="aio-pro-promotion">
            <div class="aio-card aio-pro-card">
                <div class="aio-card-header">
                    <h3><?php _e('Upgrade to Pro', 'advanced-image-optimizer'); ?></h3>
                    <span class="dashicons dashicons-star-filled"></span>
                </div>
                <div class="aio-card-content">
                    <div class="aio-pro-features">
                        <ul>
                            <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('AVIF Format Support', 'advanced-image-optimizer'); ?></li>
                            <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Advanced Compression Algorithms', 'advanced-image-optimizer'); ?></li>
                            <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Batch Processing with Queue', 'advanced-image-optimizer'); ?></li>
                            <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('CDN Integration', 'advanced-image-optimizer'); ?></li>
                            <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Priority Support', 'advanced-image-optimizer'); ?></li>
                        </ul>
                    </div>
                    <div class="aio-pro-cta">
                        <a href="#" class="button button-primary button-large aio-pro-button">
                            <?php _e('Upgrade Now', 'advanced-image-optimizer'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Bulk optimization handler
    $('#aio-start-bulk-optimize').on('click', function() {
        if (!confirm(aioAdmin.strings.confirm_bulk)) {
            return;
        }
        
        startBulkOptimization();
    });
    
    // Stop bulk optimization
    $('#aio-stop-bulk').on('click', function() {
        stopBulkOptimization();
    });
    
    // Test binaries
    $('#aio-test-binaries').on('click', function() {
        testBinaries();
    });
    
    let bulkOptimizationRunning = false;
    let bulkOptimizationOffset = 0;
    
    function startBulkOptimization() {
        if (bulkOptimizationRunning) return;
        
        bulkOptimizationRunning = true;
        bulkOptimizationOffset = 0;
        
        $('#aio-bulk-progress').show();
        $('#aio-start-bulk-optimize').prop('disabled', true);
        $('#aio-progress-text').text(aioAdmin.strings.processing);
        
        processBulkBatch();
    }
    
    function processBulkBatch() {
        if (!bulkOptimizationRunning) return;
        
        $.ajax({
            url: aioAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'aio_bulk_optimize',
                nonce: aioAdmin.nonce,
                offset: bulkOptimizationOffset
            },
            success: function(response) {
                if (response.success) {
                    // Update progress
                    bulkOptimizationOffset += response.data.results.length;
                    
                    // Log results
                    response.data.results.forEach(function(result) {
                        const status = result.success ? 'success' : 'error';
                        const message = result.title + ': ' + (result.success ? aioAdmin.strings.optimized : result.message);
                        $('#aio-progress-log').append('<div class="aio-log-entry aio-log-' + status + '">' + message + '</div>');
                    });
                    
                    // Update stats
                    $('#aio-progress-stats').text('Processed: ' + bulkOptimizationOffset + ' images');
                    
                    // Continue if there are more images
                    if (response.data.has_more && bulkOptimizationRunning) {
                        setTimeout(processBulkBatch, 1000); // Wait 1 second between batches
                    } else {
                        completeBulkOptimization();
                    }
                } else {
                    stopBulkOptimization();
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function() {
                stopBulkOptimization();
                alert('AJAX error occurred');
            }
        });
    }
    
    function stopBulkOptimization() {
        bulkOptimizationRunning = false;
        $('#aio-start-bulk-optimize').prop('disabled', false);
        $('#aio-progress-text').text('Stopped');
    }
    
    function completeBulkOptimization() {
        bulkOptimizationRunning = false;
        $('#aio-start-bulk-optimize').prop('disabled', false);
        $('#aio-progress-text').text('Completed!');
        $('#aio-progress-fill').css('width', '100%');
        
        // Refresh stats
        location.reload();
    }
    
    function testBinaries() {
        const $button = $('#aio-test-binaries');
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: aioAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'aio_test_binaries',
                nonce: aioAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Binary test completed. Check the System Info page for details.');
                } else {
                    alert('Binary test failed: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function() {
                alert('AJAX error occurred during binary test');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }
});
</script>