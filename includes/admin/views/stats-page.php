<?php
/**
 * Statistics Page View
 *
 * @package AdvancedImageOptimizer\Admin\Views
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$stats = get_option('wyoshi_img_opt_stats', [
    'optimized_images' => 0,
    'total_savings' => 0,
    'webp_generated' => 0,
    'avif_generated' => 0,
    'jpeg_optimized' => 0,
    'jpeg_savings' => 0,
    'jpeg_avg_compression' => 0,
    'png_optimized' => 0,
    'png_savings' => 0,
    'png_avg_compression' => 0,
    'webp_savings' => 0,
    'webp_avg_compression' => 0,
    'avif_savings' => 0,
    'avif_avg_compression' => 0,
    'avg_processing_time' => 0,
    'total_processing_time' => 0,
    'success_rate' => 0,
    'failed_optimizations' => 0,
    'daily_activity' => array_fill(0, 30, 0)
]);

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="aio-stats-page">
        <!-- Overview Cards -->
        <div class="aio-stats-overview">
            <div class="aio-stat-card aio-stat-primary">
                <div class="aio-stat-icon">
                    <span class="dashicons dashicons-format-image"></span>
                </div>
                <div class="aio-stat-content">
                    <div class="aio-stat-number"><?php echo number_format($stats['optimized_images'] ?? 0); ?></div>
                    <div class="aio-stat-label"><?php _e('Images Optimized', 'advanced-image-optimizer'); ?></div>
                </div>
            </div>
            
            <div class="aio-stat-card aio-stat-success">
                <div class="aio-stat-icon">
                    <span class="dashicons dashicons-download"></span>
                </div>
                <div class="aio-stat-content">
                    <div class="aio-stat-number"><?php echo \AdvancedImageOptimizer\Common\Utils::format_file_size($stats['total_savings'] ?? 0); ?></div>
                    <div class="aio-stat-label"><?php _e('Total Space Saved', 'advanced-image-optimizer'); ?></div>
                </div>
            </div>
            
            <div class="aio-stat-card aio-stat-info">
                <div class="aio-stat-icon">
                    <span class="dashicons dashicons-images-alt2"></span>
                </div>
                <div class="aio-stat-content">
                    <div class="aio-stat-number"><?php echo number_format($stats['webp_generated'] ?? 0); ?></div>
                    <div class="aio-stat-label"><?php _e('WebP Images Generated', 'advanced-image-optimizer'); ?></div>
                </div>
            </div>
            
            <div class="aio-stat-card aio-stat-warning">
                <div class="aio-stat-icon">
                    <span class="dashicons dashicons-star-filled"></span>
                </div>
                <div class="aio-stat-content">
                    <div class="aio-stat-number"><?php echo number_format($stats['avif_generated'] ?? 0); ?></div>
                    <div class="aio-stat-label"><?php _e('AVIF Images Generated', 'advanced-image-optimizer'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Detailed Statistics -->
        <div class="aio-stats-details">
            <div class="aio-stats-section">
                <div class="aio-card">
                    <div class="aio-card-header">
                        <h3><?php _e('Optimization Breakdown', 'advanced-image-optimizer'); ?></h3>
                        <button type="button" class="button aio-refresh-stats" id="aio-refresh-stats">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Refresh', 'advanced-image-optimizer'); ?>
                        </button>
                    </div>
                    <div class="aio-card-content">
                        <div class="aio-stats-grid">
                            <div class="aio-stats-item">
                                <div class="aio-stats-item-header">
                                    <h4><?php _e('JPEG Images', 'advanced-image-optimizer'); ?></h4>
                                </div>
                                <div class="aio-stats-item-content">
                                    <div class="aio-stats-row">
                                        <span class="aio-stats-label"><?php _e('Optimized:', 'advanced-image-optimizer'); ?></span>
                                        <span class="aio-stats-value"><?php echo number_format($stats['jpeg_optimized'] ?? 0); ?></span>
                                    </div>
                                    <div class="aio-stats-row">
                                        <span class="aio-stats-label"><?php _e('Space Saved:', 'advanced-image-optimizer'); ?></span>
                                        <span class="aio-stats-value"><?php echo \AdvancedImageOptimizer\Common\Utils::format_file_size($stats['jpeg_savings'] ?? 0); ?></span>
                                    </div>
                                    <div class="aio-stats-row">
                                        <span class="aio-stats-label"><?php _e('Avg. Compression:', 'advanced-image-optimizer'); ?></span>
                                        <span class="aio-stats-value"><?php echo number_format($stats['jpeg_avg_compression'] ?? 0, 1); ?>%</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="aio-stats-item">
                                <div class="aio-stats-item-header">
                                    <h4><?php _e('PNG Images', 'advanced-image-optimizer'); ?></h4>
                                </div>
                                <div class="aio-stats-item-content">
                                    <div class="aio-stats-row">
                                        <span class="aio-stats-label"><?php _e('Optimized:', 'advanced-image-optimizer'); ?></span>
                                        <span class="aio-stats-value"><?php echo number_format($stats['png_optimized'] ?? 0); ?></span>
                                    </div>
                                    <div class="aio-stats-row">
                                        <span class="aio-stats-label"><?php _e('Space Saved:', 'advanced-image-optimizer'); ?></span>
                                        <span class="aio-stats-value"><?php echo \AdvancedImageOptimizer\Common\Utils::format_file_size($stats['png_savings'] ?? 0); ?></span>
                                    </div>
                                    <div class="aio-stats-row">
                                        <span class="aio-stats-label"><?php _e('Avg. Compression:', 'advanced-image-optimizer'); ?></span>
                                        <span class="aio-stats-value"><?php echo number_format($stats['png_avg_compression'] ?? 0, 1); ?>%</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="aio-stats-item">
                                <div class="aio-stats-item-header">
                                    <h4><?php _e('WebP Images', 'advanced-image-optimizer'); ?></h4>
                                </div>
                                <div class="aio-stats-item-content">
                                    <div class="aio-stats-row">
                                        <span class="aio-stats-label"><?php _e('Generated:', 'advanced-image-optimizer'); ?></span>
                                        <span class="aio-stats-value"><?php echo number_format($stats['webp_generated'] ?? 0); ?></span>
                                    </div>
                                    <div class="aio-stats-row">
                                        <span class="aio-stats-label"><?php _e('Space Saved:', 'advanced-image-optimizer'); ?></span>
                                        <span class="aio-stats-value"><?php echo \AdvancedImageOptimizer\Common\Utils::format_file_size($stats['webp_savings'] ?? 0); ?></span>
                                    </div>
                                    <div class="aio-stats-row">
                                        <span class="aio-stats-label"><?php _e('Avg. Compression:', 'advanced-image-optimizer'); ?></span>
                                        <span class="aio-stats-value"><?php echo number_format($stats['webp_avg_compression'] ?? 0, 1); ?>%</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="aio-stats-item">
                                <div class="aio-stats-item-header">
                                    <h4><?php _e('AVIF Images', 'advanced-image-optimizer'); ?></h4>
                                    <?php if (!defined('WYOSHI_IMG_OPT_PRO_VERSION')): ?>
                                        <span class="aio-pro-badge"><?php _e('Pro', 'advanced-image-optimizer'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="aio-stats-item-content">
                                    <div class="aio-stats-row">
                                        <span class="aio-stats-label"><?php _e('Generated:', 'advanced-image-optimizer'); ?></span>
                                        <span class="aio-stats-value"><?php echo number_format($stats['avif_generated'] ?? 0); ?></span>
                                    </div>
                                    <div class="aio-stats-row">
                                        <span class="aio-stats-label"><?php _e('Space Saved:', 'advanced-image-optimizer'); ?></span>
                                        <span class="aio-stats-value"><?php echo \AdvancedImageOptimizer\Common\Utils::format_file_size($stats['avif_savings'] ?? 0); ?></span>
                                    </div>
                                    <div class="aio-stats-row">
                                        <span class="aio-stats-label"><?php _e('Avg. Compression:', 'advanced-image-optimizer'); ?></span>
                                        <span class="aio-stats-value"><?php echo number_format($stats['avif_avg_compression'] ?? 0, 1); ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Performance Metrics -->
            <div class="aio-stats-section">
                <div class="aio-card">
                    <div class="aio-card-header">
                        <h3><?php _e('Performance Metrics', 'advanced-image-optimizer'); ?></h3>
                    </div>
                    <div class="aio-card-content">
                        <div class="aio-performance-grid">
                            <div class="aio-performance-item">
                                <div class="aio-performance-label"><?php _e('Average Processing Time', 'advanced-image-optimizer'); ?></div>
                                <div class="aio-performance-value"><?php echo number_format($stats['avg_processing_time'] ?? 0, 2); ?>s</div>
                            </div>
                            
                            <div class="aio-performance-item">
                                <div class="aio-performance-label"><?php _e('Total Processing Time', 'advanced-image-optimizer'); ?></div>
                                <div class="aio-performance-value"><?php echo gmdate('H:i:s', $stats['total_processing_time'] ?? 0); ?></div>
                            </div>
                            
                            <div class="aio-performance-item">
                                <div class="aio-performance-label"><?php _e('Success Rate', 'advanced-image-optimizer'); ?></div>
                                <div class="aio-performance-value"><?php echo number_format($stats['success_rate'] ?? 0, 1); ?>%</div>
                            </div>
                            
                            <div class="aio-performance-item">
                                <div class="aio-performance-label"><?php _e('Failed Optimizations', 'advanced-image-optimizer'); ?></div>
                                <div class="aio-performance-value"><?php echo number_format($stats['failed_optimizations'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Chart -->
            <div class="aio-stats-section">
                <div class="aio-card">
                    <div class="aio-card-header">
                        <h3><?php _e('Optimization Activity (Last 30 Days)', 'advanced-image-optimizer'); ?></h3>
                    </div>
                    <div class="aio-card-content">
                        <div class="aio-chart-container">
                            <canvas id="aio-activity-chart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Optimized Images -->
            <div class="aio-stats-section">
                <div class="aio-card">
                    <div class="aio-card-header">
                        <h3><?php _e('Top Optimized Images', 'advanced-image-optimizer'); ?></h3>
                    </div>
                    <div class="aio-card-content">
                        <?php
                        // Get top optimized images
                        $top_images = get_posts([
                            'post_type' => 'attachment',
                            'post_mime_type' => 'image',
                            'posts_per_page' => 10,
                            'meta_key' => '_wyoshi_img_opt_data',
                            'meta_query' => [
                                [
                                    'key' => '_wyoshi_img_opt_optimized',
                                    'value' => true,
                                    'compare' => '='
                                ]
                            ],
                            'orderby' => 'meta_value_num',
                            'order' => 'DESC'
                        ]);
                        
                        if (!empty($top_images)):
                        ?>
                            <div class="aio-top-images-table">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Image', 'advanced-image-optimizer'); ?></th>
                                            <th><?php _e('Original Size', 'advanced-image-optimizer'); ?></th>
                                            <th><?php _e('Optimized Size', 'advanced-image-optimizer'); ?></th>
                                            <th><?php _e('Savings', 'advanced-image-optimizer'); ?></th>
                                            <th><?php _e('Compression', 'advanced-image-optimizer'); ?></th>
                                            <th><?php _e('Date', 'advanced-image-optimizer'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_images as $image): ?>
                                            <?php
                                            $optimization_data = get_post_meta($image->ID, '_wyoshi_img_opt_data', true);
                                            if (!$optimization_data) continue;
                                            
                                            $savings = $optimization_data['original_size'] - $optimization_data['optimized_size'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="aio-image-info">
                                                        <?php echo wp_get_attachment_image($image->ID, 'thumbnail', false, ['style' => 'width: 40px; height: 40px; object-fit: cover;']); ?>
                                                        <div class="aio-image-details">
                                                            <strong><?php echo esc_html(get_the_title($image->ID)); ?></strong>
                                                            <br>
                                                            <small><?php echo esc_html(basename(get_attached_file($image->ID))); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo \AdvancedImageOptimizer\Common\Utils::format_file_size($optimization_data['original_size']); ?></td>
                                                <td><?php echo \AdvancedImageOptimizer\Common\Utils::format_file_size($optimization_data['optimized_size']); ?></td>
                                                <td class="aio-savings"><?php echo \AdvancedImageOptimizer\Common\Utils::format_file_size($savings); ?></td>
                                                <td class="aio-compression"><?php echo number_format($optimization_data['compression_ratio'], 1); ?>%</td>
                                                <td><?php echo date_i18n(get_option('date_format'), strtotime($optimization_data['date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="aio-no-data"><?php _e('No optimized images found.', 'advanced-image-optimizer'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Export/Import Statistics -->
            <div class="aio-stats-section">
                <div class="aio-card">
                    <div class="aio-card-header">
                        <h3><?php _e('Data Management', 'advanced-image-optimizer'); ?></h3>
                    </div>
                    <div class="aio-card-content">
                        <div class="aio-data-management">
                            <div class="aio-data-action">
                                <h4><?php _e('Export Statistics', 'advanced-image-optimizer'); ?></h4>
                                <p><?php _e('Export optimization statistics to CSV file.', 'advanced-image-optimizer'); ?></p>
                                <button type="button" class="button" id="aio-export-stats">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php _e('Export CSV', 'advanced-image-optimizer'); ?>
                                </button>
                            </div>
                            
                            <div class="aio-data-action">
                                <h4><?php _e('Reset Statistics', 'advanced-image-optimizer'); ?></h4>
                                <p><?php _e('Reset all optimization statistics. This action cannot be undone.', 'advanced-image-optimizer'); ?></p>
                                <button type="button" class="button button-secondary" id="aio-reset-stats">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php _e('Reset Statistics', 'advanced-image-optimizer'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Refresh statistics
    $('#aio-refresh-stats').on('click', function() {
        const $button = $(this);
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update aio-spin"></span> <?php _e('Refreshing...', 'advanced-image-optimizer'); ?>');
        
        $.ajax({
            url: aioAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wyoshi_img_opt_get_stats',
                nonce: aioAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('<?php _e('Failed to refresh statistics.', 'advanced-image-optimizer'); ?>');
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
    
    // Export statistics
    $('#aio-export-stats').on('click', function() {
        window.location.href = ajaxurl + '?action=wyoshi_img_opt_export_stats&nonce=' + aioAdmin.nonce;
    });
    
    // Reset statistics
    $('#aio-reset-stats').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to reset all statistics? This action cannot be undone.', 'advanced-image-optimizer'); ?>')) {
            return;
        }
        
        const $button = $(this);
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update aio-spin"></span> <?php _e('Resetting...', 'advanced-image-optimizer'); ?>');
        
        $.ajax({
            url: aioAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wyoshi_img_opt_reset_stats',
                nonce: aioAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('<?php _e('Failed to reset statistics.', 'advanced-image-optimizer'); ?>');
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
    
    // Initialize activity chart
    initActivityChart();
    
    function initActivityChart() {
        const ctx = document.getElementById('aio-activity-chart');
        if (!ctx) return;
        
        // Sample data - in real implementation, this would come from the server
        const chartData = {
            labels: <?php echo json_encode(array_map(function($i) { return date('M j', strtotime('-' . (29 - $i) . ' days')); }, range(0, 29))); ?>,
            datasets: [{
                label: '<?php _e('Images Optimized', 'advanced-image-optimizer'); ?>',
                data: <?php echo json_encode($stats['daily_activity'] ?? array_fill(0, 30, 0)); ?>,
                borderColor: '#0073aa',
                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                tension: 0.4,
                fill: true
            }]
        };
        
        // Simple canvas-based chart implementation
        drawSimpleChart(ctx, chartData);
    }
    
    function drawSimpleChart(canvas, data) {
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;
        const padding = 40;
        
        // Clear canvas
        ctx.clearRect(0, 0, width, height);
        
        // Get data points
        const values = data.datasets[0].data;
        const maxValue = Math.max(...values) || 1;
        const stepX = (width - 2 * padding) / (values.length - 1);
        
        // Draw grid lines
        ctx.strokeStyle = '#e0e0e0';
        ctx.lineWidth = 1;
        
        // Horizontal grid lines
        for (let i = 0; i <= 5; i++) {
            const y = padding + (height - 2 * padding) * i / 5;
            ctx.beginPath();
            ctx.moveTo(padding, y);
            ctx.lineTo(width - padding, y);
            ctx.stroke();
        }
        
        // Draw line
        ctx.strokeStyle = '#0073aa';
        ctx.lineWidth = 2;
        ctx.beginPath();
        
        values.forEach((value, index) => {
            const x = padding + index * stepX;
            const y = height - padding - (value / maxValue) * (height - 2 * padding);
            
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        
        ctx.stroke();
        
        // Draw points
        ctx.fillStyle = '#0073aa';
        values.forEach((value, index) => {
            const x = padding + index * stepX;
            const y = height - padding - (value / maxValue) * (height - 2 * padding);
            
            ctx.beginPath();
            ctx.arc(x, y, 3, 0, 2 * Math.PI);
            ctx.fill();
        });
    }
});
</script>

<style>
.aio-spin {
    animation: aio-spin 1s linear infinite;
}

@keyframes aio-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>