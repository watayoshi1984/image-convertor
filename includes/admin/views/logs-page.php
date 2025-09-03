<?php
/**
 * Logs Page View
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
    
    <div class="aio-logs-page">
        <!-- Log Controls -->
        <div class="aio-logs-controls">
            <div class="aio-card">
                <div class="aio-card-header">
                    <h3><?php _e('Log Controls', 'advanced-image-optimizer'); ?></h3>
                    <div class="aio-log-actions">
                        <button type="button" class="button" id="aio-refresh-logs">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Refresh', 'advanced-image-optimizer'); ?>
                        </button>
                        <button type="button" class="button" id="aio-clear-logs">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Clear Logs', 'advanced-image-optimizer'); ?>
                        </button>
                        <button type="button" class="button" id="aio-download-logs">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Download', 'advanced-image-optimizer'); ?>
                        </button>
                    </div>
                </div>
                <div class="aio-card-content">
                    <div class="aio-log-filters">
                        <div class="aio-filter-group">
                            <label for="aio-log-level"><?php _e('Log Level:', 'advanced-image-optimizer'); ?></label>
                            <select id="aio-log-level" class="aio-log-filter">
                                <option value=""><?php _e('All Levels', 'advanced-image-optimizer'); ?></option>
                                <option value="emergency"><?php _e('Emergency', 'advanced-image-optimizer'); ?></option>
                                <option value="alert"><?php _e('Alert', 'advanced-image-optimizer'); ?></option>
                                <option value="critical"><?php _e('Critical', 'advanced-image-optimizer'); ?></option>
                                <option value="error"><?php _e('Error', 'advanced-image-optimizer'); ?></option>
                                <option value="warning"><?php _e('Warning', 'advanced-image-optimizer'); ?></option>
                                <option value="notice"><?php _e('Notice', 'advanced-image-optimizer'); ?></option>
                                <option value="info"><?php _e('Info', 'advanced-image-optimizer'); ?></option>
                                <option value="debug"><?php _e('Debug', 'advanced-image-optimizer'); ?></option>
                            </select>
                        </div>
                        
                        <div class="aio-filter-group">
                            <label for="aio-log-context"><?php _e('Context:', 'advanced-image-optimizer'); ?></label>
                            <select id="aio-log-context" class="aio-log-filter">
                                <option value=""><?php _e('All Contexts', 'advanced-image-optimizer'); ?></option>
                                <?php foreach ($log_contexts as $context => $label): ?>
                                    <option value="<?php echo esc_attr($context); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="aio-filter-group">
                            <label for="aio-log-search"><?php _e('Search:', 'advanced-image-optimizer'); ?></label>
                            <input type="text" id="aio-log-search" class="aio-log-filter" placeholder="<?php _e('Search in messages...', 'advanced-image-optimizer'); ?>">
                        </div>
                        
                        <div class="aio-filter-group">
                            <label for="aio-log-date"><?php _e('Date:', 'advanced-image-optimizer'); ?></label>
                            <input type="date" id="aio-log-date" class="aio-log-filter">
                        </div>
                        
                        <div class="aio-filter-group">
                            <button type="button" class="button" id="aio-apply-filters">
                                <?php _e('Apply Filters', 'advanced-image-optimizer'); ?>
                            </button>
                            <button type="button" class="button" id="aio-reset-filters">
                                <?php _e('Reset', 'advanced-image-optimizer'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Log Statistics -->
        <div class="aio-logs-stats">
            <div class="aio-card">
                <div class="aio-card-header">
                    <h3><?php _e('Log Statistics', 'advanced-image-optimizer'); ?></h3>
                </div>
                <div class="aio-card-content">
                    <div class="aio-stats-grid">
                        <div class="aio-stat-item">
                            <div class="aio-stat-value"><?php echo number_format($log_stats['total_entries'] ?? 0); ?></div>
                            <div class="aio-stat-label"><?php _e('Total Entries', 'advanced-image-optimizer'); ?></div>
                        </div>
                        <div class="aio-stat-item aio-stat-error">
                            <div class="aio-stat-value"><?php echo number_format($log_stats['error_count'] ?? 0); ?></div>
                            <div class="aio-stat-label"><?php _e('Errors', 'advanced-image-optimizer'); ?></div>
                        </div>
                        <div class="aio-stat-item aio-stat-warning">
                            <div class="aio-stat-value"><?php echo number_format($log_stats['warning_count'] ?? 0); ?></div>
                            <div class="aio-stat-label"><?php _e('Warnings', 'advanced-image-optimizer'); ?></div>
                        </div>
                        <div class="aio-stat-item">
                            <div class="aio-stat-value"><?php echo !empty($log_stats['file_size']) ? size_format($log_stats['file_size']) : '0 B'; ?></div>
                            <div class="aio-stat-label"><?php _e('Log File Size', 'advanced-image-optimizer'); ?></div>
                        </div>
                        <div class="aio-stat-item">
                            <div class="aio-stat-value"><?php echo !empty($log_stats['last_entry']) ? human_time_diff(strtotime($log_stats['last_entry'])) . ' ' . __('ago', 'advanced-image-optimizer') : __('Never', 'advanced-image-optimizer'); ?></div>
                            <div class="aio-stat-label"><?php _e('Last Entry', 'advanced-image-optimizer'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Log Entries -->
        <div class="aio-logs-entries">
            <div class="aio-card">
                <div class="aio-card-header">
                    <h3><?php _e('Log Entries', 'advanced-image-optimizer'); ?></h3>
                    <div class="aio-log-info">
                        <span id="aio-log-count"><?php echo sprintf(__('Showing %d entries', 'advanced-image-optimizer'), count($log_entries)); ?></span>
                        <span class="aio-log-auto-refresh">
                            <label>
                                <input type="checkbox" id="aio-auto-refresh"> 
                                <?php _e('Auto-refresh', 'advanced-image-optimizer'); ?>
                            </label>
                        </span>
                    </div>
                </div>
                <div class="aio-card-content">
                    <?php if (empty($log_entries)): ?>
                        <div class="aio-no-logs">
                            <div class="aio-no-logs-icon">
                                <span class="dashicons dashicons-admin-page"></span>
                            </div>
                            <h4><?php _e('No Log Entries Found', 'advanced-image-optimizer'); ?></h4>
                            <p><?php _e('No log entries match your current filters, or logging is disabled.', 'advanced-image-optimizer'); ?></p>
                            <?php if (!get_option('aio_enable_logging', false)): ?>
                                <p>
                                    <a href="<?php echo admin_url('admin.php?page=advanced-image-optimizer-settings'); ?>" class="button button-primary">
                                        <?php _e('Enable Logging', 'advanced-image-optimizer'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="aio-log-table-wrapper">
                            <table class="aio-log-table">
                                <thead>
                                    <tr>
                                        <th class="aio-log-timestamp"><?php _e('Timestamp', 'advanced-image-optimizer'); ?></th>
                                        <th class="aio-log-level"><?php _e('Level', 'advanced-image-optimizer'); ?></th>
                                        <th class="aio-log-context"><?php _e('Context', 'advanced-image-optimizer'); ?></th>
                                        <th class="aio-log-message"><?php _e('Message', 'advanced-image-optimizer'); ?></th>
                                        <th class="aio-log-actions"><?php _e('Actions', 'advanced-image-optimizer'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="aio-log-entries">
                                    <?php foreach ($log_entries as $index => $entry): ?>
                                        <tr class="aio-log-entry aio-log-level-<?php echo esc_attr($entry['level']); ?>" data-entry-index="<?php echo $index; ?>">
                                            <td class="aio-log-timestamp">
                                                <span class="aio-log-time" title="<?php echo esc_attr($entry['timestamp']); ?>">
                                                    <?php echo esc_html(date('M j, H:i:s', strtotime($entry['timestamp']))); ?>
                                                </span>
                                            </td>
                                            <td class="aio-log-level">
                                                <span class="aio-log-level-badge aio-level-<?php echo esc_attr($entry['level']); ?>">
                                                    <?php echo esc_html(strtoupper($entry['level'])); ?>
                                                </span>
                                            </td>
                                            <td class="aio-log-context">
                                                <span class="aio-log-context-badge">
                                                    <?php echo esc_html($entry['context'] ?? 'general'); ?>
                                                </span>
                                            </td>
                                            <td class="aio-log-message">
                                                <div class="aio-log-message-text">
                                                    <?php echo esc_html($entry['message']); ?>
                                                </div>
                                                <?php if (!empty($entry['extra_data'])): ?>
                                                    <div class="aio-log-extra-data" style="display: none;">
                                                        <pre><?php echo esc_html(json_encode($entry['extra_data'], JSON_PRETTY_PRINT)); ?></pre>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="aio-log-actions">
                                                <?php if (!empty($entry['extra_data'])): ?>
                                                    <button type="button" class="button-link aio-toggle-extra-data" title="<?php _e('Toggle Extra Data', 'advanced-image-optimizer'); ?>">
                                                        <span class="dashicons dashicons-visibility"></span>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="button-link aio-copy-entry" title="<?php _e('Copy Entry', 'advanced-image-optimizer'); ?>">
                                                    <span class="dashicons dashicons-clipboard"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="aio-log-pagination">
                                <div class="aio-pagination-info">
                                    <?php echo sprintf(
                                        __('Page %d of %d (%d total entries)', 'advanced-image-optimizer'),
                                        $current_page,
                                        $total_pages,
                                        $total_entries
                                    ); ?>
                                </div>
                                <div class="aio-pagination-controls">
                                    <?php if ($current_page > 1): ?>
                                        <button type="button" class="button aio-page-nav" data-page="1">
                                            <?php _e('First', 'advanced-image-optimizer'); ?>
                                        </button>
                                        <button type="button" class="button aio-page-nav" data-page="<?php echo $current_page - 1; ?>">
                                            <?php _e('Previous', 'advanced-image-optimizer'); ?>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                        <button type="button" class="button aio-page-nav <?php echo $i === $current_page ? 'button-primary' : ''; ?>" data-page="<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </button>
                                    <?php endfor; ?>
                                    
                                    <?php if ($current_page < $total_pages): ?>
                                        <button type="button" class="button aio-page-nav" data-page="<?php echo $current_page + 1; ?>">
                                            <?php _e('Next', 'advanced-image-optimizer'); ?>
                                        </button>
                                        <button type="button" class="button aio-page-nav" data-page="<?php echo $total_pages; ?>">
                                            <?php _e('Last', 'advanced-image-optimizer'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    let autoRefreshInterval;
    
    // Refresh logs
    function refreshLogs() {
        const filters = {
            level: $('#aio-log-level').val(),
            context: $('#aio-log-context').val(),
            search: $('#aio-log-search').val(),
            date: $('#aio-log-date').val(),
            page: 1
        };
        
        loadLogs(filters);
    }
    
    // Load logs with filters
    function loadLogs(filters = {}, page = 1) {
        $.ajax({
            url: aioAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'aio_get_logs',
                nonce: aioAdmin.nonce,
                filters: filters,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    // Update log entries
                    $('#aio-log-entries').html(response.data.html);
                    $('#aio-log-count').text(response.data.count_text);
                    
                    // Update pagination if provided
                    if (response.data.pagination) {
                        $('.aio-log-pagination').html(response.data.pagination);
                    }
                    
                    // Update stats if provided
                    if (response.data.stats) {
                        updateLogStats(response.data.stats);
                    }
                }
            },
            error: function() {
                console.error('Failed to load logs');
            }
        });
    }
    
    // Update log statistics
    function updateLogStats(stats) {
        $('.aio-stats-grid .aio-stat-item').each(function() {
            const $item = $(this);
            const label = $item.find('.aio-stat-label').text().toLowerCase();
            
            if (label.includes('total') && stats.total_entries !== undefined) {
                $item.find('.aio-stat-value').text(stats.total_entries.toLocaleString());
            } else if (label.includes('error') && stats.error_count !== undefined) {
                $item.find('.aio-stat-value').text(stats.error_count.toLocaleString());
            } else if (label.includes('warning') && stats.warning_count !== undefined) {
                $item.find('.aio-stat-value').text(stats.warning_count.toLocaleString());
            } else if (label.includes('size') && stats.file_size !== undefined) {
                $item.find('.aio-stat-value').text(stats.file_size_formatted || '0 B');
            }
        });
    }
    
    // Event handlers
    $('#aio-refresh-logs').on('click', refreshLogs);
    
    $('#aio-apply-filters').on('click', function() {
        refreshLogs();
    });
    
    $('#aio-reset-filters').on('click', function() {
        $('.aio-log-filter').val('');
        refreshLogs();
    });
    
    // Auto-refresh toggle
    $('#aio-auto-refresh').on('change', function() {
        if ($(this).is(':checked')) {
            autoRefreshInterval = setInterval(refreshLogs, 30000); // 30 seconds
        } else {
            clearInterval(autoRefreshInterval);
        }
    });
    
    // Pagination
    $(document).on('click', '.aio-page-nav', function() {
        const page = $(this).data('page');
        const filters = {
            level: $('#aio-log-level').val(),
            context: $('#aio-log-context').val(),
            search: $('#aio-log-search').val(),
            date: $('#aio-log-date').val()
        };
        
        loadLogs(filters, page);
    });
    
    // Toggle extra data
    $(document).on('click', '.aio-toggle-extra-data', function() {
        const $button = $(this);
        const $extraData = $button.closest('tr').find('.aio-log-extra-data');
        
        $extraData.slideToggle();
        $button.find('.dashicons').toggleClass('dashicons-visibility dashicons-hidden');
    });
    
    // Copy log entry
    $(document).on('click', '.aio-copy-entry', function() {
        const $row = $(this).closest('tr');
        const timestamp = $row.find('.aio-log-timestamp').text().trim();
        const level = $row.find('.aio-log-level').text().trim();
        const context = $row.find('.aio-log-context').text().trim();
        const message = $row.find('.aio-log-message-text').text().trim();
        
        const entryText = `[${timestamp}] ${level} (${context}): ${message}`;
        
        // Create temporary textarea to copy text
        const $temp = $('<textarea>').val(entryText).appendTo('body').select();
        
        try {
            document.execCommand('copy');
            alert('<?php _e('Log entry copied to clipboard.', 'advanced-image-optimizer'); ?>');
        } catch (err) {
            alert('<?php _e('Failed to copy to clipboard.', 'advanced-image-optimizer'); ?>');
        }
        
        $temp.remove();
    });
    
    // Clear logs
    $('#aio-clear-logs').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to clear all logs? This action cannot be undone.', 'advanced-image-optimizer'); ?>')) {
            return;
        }
        
        const $button = $(this);
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update aio-spin"></span> <?php _e('Clearing...', 'advanced-image-optimizer'); ?>');
        
        $.ajax({
            url: aioAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'aio_clear_logs',
                nonce: aioAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    refreshLogs();
                } else {
                    alert('<?php _e('Failed to clear logs.', 'advanced-image-optimizer'); ?>');
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
    
    // Download logs
    $('#aio-download-logs').on('click', function() {
        const filters = {
            level: $('#aio-log-level').val(),
            context: $('#aio-log-context').val(),
            search: $('#aio-log-search').val(),
            date: $('#aio-log-date').val()
        };
        
        // Create form and submit
        const $form = $('<form>', {
            method: 'POST',
            action: aioAdmin.ajaxUrl
        });
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'aio_download_logs'
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: aioAdmin.nonce
        }));
        
        $form.append($('<input>', {
            type: 'hidden',
            name: 'filters',
            value: JSON.stringify(filters)
        }));
        
        $form.appendTo('body').submit().remove();
    });
    
    // Filter on Enter key
    $('.aio-log-filter').on('keypress', function(e) {
        if (e.which === 13) {
            refreshLogs();
        }
    });
    
    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
    });
});
</script>