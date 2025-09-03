/**
 * Advanced Image Optimizer - Admin JavaScript
 *
 * @package AdvancedImageOptimizer
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Global variables
    let bulkOptimizationRunning = false;
    let bulkOptimizationPaused = false;
    let currentBulkBatch = 0;
    let totalBulkImages = 0;
    let processedBulkImages = 0;
    let bulkOptimizationInterval;

    /**
     * Initialize admin functionality
     */
    function init() {
        initBulkOptimization();
        initSingleOptimization();
        initBinaryTest();
        initSettingsForm();
        initStatistics();
        initTooltips();
        initConfirmDialogs();
        
        // Auto-refresh stats every 30 seconds
        setInterval(refreshStats, 30000);
    }

    /**
     * Initialize bulk optimization functionality
     */
    function initBulkOptimization() {
        $('#aio-start-bulk-optimization').on('click', function() {
            if (bulkOptimizationRunning) {
                pauseBulkOptimization();
            } else {
                startBulkOptimization();
            }
        });

        $('#aio-stop-bulk-optimization').on('click', function() {
            stopBulkOptimization();
        });
    }

    /**
     * Start bulk optimization process
     */
    function startBulkOptimization() {
        const $button = $('#aio-start-bulk-optimization');
        const $stopButton = $('#aio-stop-bulk-optimization');
        const $container = $('.aio-bulk-optimization');

        // Show bulk optimization container
        $container.addClass('active');

        // Update button states
        $button.text(aioAdmin.strings.pause).removeClass('button-primary').addClass('button-secondary');
        $stopButton.show();

        // Reset counters
        currentBulkBatch = 0;
        processedBulkImages = 0;
        bulkOptimizationRunning = true;
        bulkOptimizationPaused = false;

        // Get total images count first
        $.ajax({
            url: aioAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'aio_get_bulk_count',
                nonce: aioAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    totalBulkImages = response.data.total;
                    updateBulkProgress(0, totalBulkImages);
                    
                    if (totalBulkImages > 0) {
                        processBulkBatch();
                    } else {
                        addBulkLogEntry('No images found for optimization.', 'warning');
                        stopBulkOptimization();
                    }
                } else {
                    addBulkLogEntry('Failed to get images count: ' + (response.data || 'Unknown error'), 'error');
                    stopBulkOptimization();
                }
            },
            error: function() {
                addBulkLogEntry('AJAX error occurred while getting images count.', 'error');
                stopBulkOptimization();
            }
        });
    }

    /**
     * Process a batch of images
     */
    function processBulkBatch() {
        if (!bulkOptimizationRunning || bulkOptimizationPaused) {
            return;
        }

        $.ajax({
            url: aioAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'aio_bulk_optimize',
                nonce: aioAdmin.nonce,
                batch: currentBulkBatch,
                batch_size: 5 // Process 5 images at a time
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Update progress
                    processedBulkImages += data.processed;
                    updateBulkProgress(processedBulkImages, totalBulkImages);
                    
                    // Add log entries
                    if (data.results && data.results.length > 0) {
                        data.results.forEach(function(result) {
                            const status = result.success ? 'success' : 'error';
                            addBulkLogEntry(result.message, status);
                        });
                    }
                    
                    // Check if we're done
                    if (data.completed || processedBulkImages >= totalBulkImages) {
                        addBulkLogEntry('Bulk optimization completed!', 'success');
                        stopBulkOptimization();
                        refreshStats();
                    } else {
                        // Continue with next batch
                        currentBulkBatch++;
                        setTimeout(processBulkBatch, 1000); // 1 second delay between batches
                    }
                } else {
                    addBulkLogEntry('Batch processing failed: ' + (response.data || 'Unknown error'), 'error');
                    stopBulkOptimization();
                }
            },
            error: function() {
                addBulkLogEntry('AJAX error occurred during batch processing.', 'error');
                stopBulkOptimization();
            }
        });
    }

    /**
     * Pause bulk optimization
     */
    function pauseBulkOptimization() {
        bulkOptimizationPaused = true;
        const $button = $('#aio-start-bulk-optimization');
        $button.text(aioAdmin.strings.resume).removeClass('button-secondary').addClass('button-primary');
        addBulkLogEntry('Bulk optimization paused.', 'warning');
    }

    /**
     * Resume bulk optimization
     */
    function resumeBulkOptimization() {
        bulkOptimizationPaused = false;
        const $button = $('#aio-start-bulk-optimization');
        $button.text(aioAdmin.strings.pause).removeClass('button-primary').addClass('button-secondary');
        addBulkLogEntry('Bulk optimization resumed.', 'success');
        processBulkBatch();
    }

    /**
     * Stop bulk optimization
     */
    function stopBulkOptimization() {
        bulkOptimizationRunning = false;
        bulkOptimizationPaused = false;
        
        const $button = $('#aio-start-bulk-optimization');
        const $stopButton = $('#aio-stop-bulk-optimization');
        
        $button.text(aioAdmin.strings.startBulk).removeClass('button-secondary').addClass('button-primary');
        $stopButton.hide();
        
        if (bulkOptimizationInterval) {
            clearInterval(bulkOptimizationInterval);
        }
    }

    /**
     * Update bulk optimization progress
     */
    function updateBulkProgress(processed, total) {
        const percentage = total > 0 ? Math.round((processed / total) * 100) : 0;
        
        $('.aio-progress-fill').css('width', percentage + '%');
        $('.aio-progress-text').text(percentage + '%');
        $('.aio-progress-info .aio-processed').text(processed);
        $('.aio-progress-info .aio-total').text(total);
    }

    /**
     * Add entry to bulk optimization log
     */
    function addBulkLogEntry(message, type = 'info') {
        const $log = $('.aio-bulk-log');
        const timestamp = new Date().toLocaleTimeString();
        const entry = `<div class="aio-bulk-log-entry ${type}">[${timestamp}] ${message}</div>`;
        
        $log.append(entry);
        $log.scrollTop($log[0].scrollHeight);
    }

    /**
     * Initialize single image optimization
     */
    function initSingleOptimization() {
        $(document).on('click', '.aio-optimize-single', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const attachmentId = $button.data('attachment-id');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text(aioAdmin.strings.optimizing);
            
            $.ajax({
                url: aioAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aio_optimize_single',
                    nonce: aioAdmin.nonce,
                    attachment_id: attachmentId
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data.message, 'success');
                        
                        // Update UI with new data if provided
                        if (response.data.stats) {
                            updateImageStats($button, response.data.stats);
                        }
                        
                        refreshStats();
                    } else {
                        showNotice(response.data || aioAdmin.strings.optimizationFailed, 'error');
                    }
                },
                error: function() {
                    showNotice(aioAdmin.strings.ajaxError, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    /**
     * Update image statistics in UI
     */
    function updateImageStats($button, stats) {
        const $row = $button.closest('tr, .aio-image-item');
        
        if (stats.original_size && stats.optimized_size) {
            $row.find('.aio-original-size').text(formatFileSize(stats.original_size));
            $row.find('.aio-optimized-size').text(formatFileSize(stats.optimized_size));
            $row.find('.aio-savings').text(stats.savings_percentage + '%');
        }
        
        if (stats.webp_generated) {
            $row.find('.aio-webp-status').addClass('aio-status-good').text('✓');
        }
        
        if (stats.avif_generated) {
            $row.find('.aio-avif-status').addClass('aio-status-good').text('✓');
        }
    }

    /**
     * Initialize binary testing
     */
    function initBinaryTest() {
        $('#aio-test-binaries').on('click', function() {
            const $button = $(this);
            const originalText = $button.html();
            
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update aio-spin"></span> ' + aioAdmin.strings.testing);
            
            $.ajax({
                url: aioAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aio_test_binaries',
                    nonce: aioAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(aioAdmin.strings.testCompleted, 'success');
                        
                        // Update capability indicators
                        if (response.data.results) {
                            updateCapabilityIndicators(response.data.results);
                        }
                    } else {
                        showNotice(response.data || aioAdmin.strings.testFailed, 'error');
                    }
                },
                error: function() {
                    showNotice(aioAdmin.strings.ajaxError, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalText);
                }
            });
        });
    }

    /**
     * Update capability indicators
     */
    function updateCapabilityIndicators(results) {
        Object.keys(results).forEach(function(format) {
            const result = results[format];
            const $indicator = $(`.aio-capability-${format}`);
            
            if ($indicator.length) {
                const $status = $indicator.find('.aio-capability-status');
                
                if (result.available) {
                    $status.removeClass('aio-status-error').addClass('aio-status-good');
                    $status.find('.dashicons').removeClass('dashicons-dismiss').addClass('dashicons-yes-alt');
                    $status.find('span:last-child').text(aioAdmin.strings.available);
                } else {
                    $status.removeClass('aio-status-good').addClass('aio-status-error');
                    $status.find('.dashicons').removeClass('dashicons-yes-alt').addClass('dashicons-dismiss');
                    $status.find('span:last-child').text(aioAdmin.strings.notAvailable);
                }
            }
        });
    }

    /**
     * Initialize settings form
     */
    function initSettingsForm() {
        // Quality sliders
        $('.aio-quality-slider').on('input', function() {
            const $slider = $(this);
            const $value = $slider.siblings('.aio-quality-value');
            $value.text($slider.val());
        });

        // Settings form submission
        $('#aio-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitButton = $form.find('input[type="submit"]');
            const originalValue = $submitButton.val();
            
            $submitButton.prop('disabled', true).val(aioAdmin.strings.saving);
            
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    showNotice(aioAdmin.strings.settingsSaved, 'success');
                },
                error: function() {
                    showNotice(aioAdmin.strings.settingsSaveFailed, 'error');
                },
                complete: function() {
                    $submitButton.prop('disabled', false).val(originalValue);
                }
            });
        });

        // Reset settings
        $('#aio-reset-settings').on('click', function() {
            if (confirm(aioAdmin.strings.confirmReset)) {
                const $button = $(this);
                const originalText = $button.text();
                
                $button.prop('disabled', true).text(aioAdmin.strings.resetting);
                
                $.ajax({
                    url: aioAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'aio_reset_settings',
                        nonce: aioAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotice(aioAdmin.strings.settingsReset, 'success');
                            location.reload();
                        } else {
                            showNotice(response.data || aioAdmin.strings.resetFailed, 'error');
                        }
                    },
                    error: function() {
                        showNotice(aioAdmin.strings.ajaxError, 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text(originalText);
                    }
                });
            }
        });
    }

    /**
     * Initialize statistics functionality
     */
    function initStatistics() {
        // Refresh stats
        $('#aio-refresh-stats').on('click', function() {
            refreshStats();
        });

        // Export stats
        $('#aio-export-stats').on('click', function() {
            const $button = $(this);
            const originalText = $button.text();
            
            $button.prop('disabled', true).text(aioAdmin.strings.exporting);
            
            $.ajax({
                url: aioAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aio_export_stats',
                    nonce: aioAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        downloadFile(response.data.filename, response.data.content);
                        showNotice(aioAdmin.strings.statsExported, 'success');
                    } else {
                        showNotice(response.data || aioAdmin.strings.exportFailed, 'error');
                    }
                },
                error: function() {
                    showNotice(aioAdmin.strings.ajaxError, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });

        // Reset stats
        $('#aio-reset-stats').on('click', function() {
            if (confirm(aioAdmin.strings.confirmStatsReset)) {
                const $button = $(this);
                const originalText = $button.text();
                
                $button.prop('disabled', true).text(aioAdmin.strings.resetting);
                
                $.ajax({
                    url: aioAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'aio_reset_stats',
                        nonce: aioAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotice(aioAdmin.strings.statsReset, 'success');
                            refreshStats();
                        } else {
                            showNotice(response.data || aioAdmin.strings.resetFailed, 'error');
                        }
                    },
                    error: function() {
                        showNotice(aioAdmin.strings.ajaxError, 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text(originalText);
                    }
                });
            }
        });
    }

    /**
     * Refresh statistics
     */
    function refreshStats() {
        $.ajax({
            url: aioAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'aio_get_stats',
                nonce: aioAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateStatsDisplay(response.data);
                }
            },
            error: function() {
                console.error('Failed to refresh statistics');
            }
        });
    }

    /**
     * Update statistics display
     */
    function updateStatsDisplay(stats) {
        // Update stat values
        $('.aio-stat-optimized-images .aio-stat-value').text(stats.optimized_images || 0);
        $('.aio-stat-total-savings .aio-stat-value').text(formatFileSize(stats.total_savings || 0));
        $('.aio-stat-webp-generated .aio-stat-value').text(stats.webp_generated || 0);
        $('.aio-stat-avif-generated .aio-stat-value').text(stats.avif_generated || 0);
        $('.aio-stat-average-savings .aio-stat-value').text((stats.average_savings || 0) + '%');
        $('.aio-stat-processing-time .aio-stat-value').text((stats.average_processing_time || 0) + 's');
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        $('[title]').each(function() {
            const $element = $(this);
            const title = $element.attr('title');
            
            if (title) {
                $element.removeAttr('title').attr('data-tooltip', title);
            }
        });

        // Simple tooltip implementation
        $(document).on('mouseenter', '[data-tooltip]', function() {
            const $element = $(this);
            const tooltip = $element.attr('data-tooltip');
            
            if (tooltip) {
                const $tooltip = $('<div class="aio-tooltip">' + tooltip + '</div>');
                $('body').append($tooltip);
                
                const offset = $element.offset();
                $tooltip.css({
                    top: offset.top - $tooltip.outerHeight() - 5,
                    left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                });
            }
        });

        $(document).on('mouseleave', '[data-tooltip]', function() {
            $('.aio-tooltip').remove();
        });
    }

    /**
     * Initialize confirmation dialogs
     */
    function initConfirmDialogs() {
        $(document).on('click', '[data-confirm]', function(e) {
            const message = $(this).attr('data-confirm');
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type = 'info') {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Manual dismiss
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }

    /**
     * Format file size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Download file
     */
    function downloadFile(filename, content) {
        const blob = new Blob([content], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialize when document is ready
    $(document).ready(init);

    // Handle page visibility change to pause/resume bulk optimization
    $(document).on('visibilitychange', function() {
        if (document.hidden && bulkOptimizationRunning && !bulkOptimizationPaused) {
            // Optionally pause when tab is not visible
            // pauseBulkOptimization();
        }
    });

    // Handle beforeunload to warn about ongoing bulk optimization
    $(window).on('beforeunload', function() {
        if (bulkOptimizationRunning && !bulkOptimizationPaused) {
            return aioAdmin.strings.bulkOptimizationRunning;
        }
    });

})(jQuery);

/**
 * CSS for tooltips and additional styling
 */
if (!document.getElementById('aio-dynamic-styles')) {
    const style = document.createElement('style');
    style.id = 'aio-dynamic-styles';
    style.textContent = `
        .aio-tooltip {
            position: absolute;
            background: #333;
            color: #fff;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            z-index: 9999;
            white-space: nowrap;
            pointer-events: none;
        }
        
        .aio-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border: 5px solid transparent;
            border-top-color: #333;
        }
        
        .notice.is-dismissible {
            position: relative;
            padding-right: 38px;
        }
        
        .notice-dismiss {
            position: absolute;
            top: 0;
            right: 1px;
            border: none;
            margin: 0;
            padding: 9px;
            background: none;
            color: #666;
            cursor: pointer;
        }
        
        .notice-dismiss:hover {
            color: #000;
        }
        
        .notice-dismiss::before {
            content: '\\f153';
            font-family: dashicons;
            font-size: 16px;
            line-height: 20px;
        }
    `;
    document.head.appendChild(style);
}