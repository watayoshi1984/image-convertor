<?php
/**
 * AVIF Settings Tab Template
 * 
 * @package ImageConvertor
 * @subpackage Admin\Views
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$plugin = \ImageConvertor\Core\Plugin::get_instance();
$pro_manager = $plugin->get_pro_manager();
$avif_processor = $pro_manager->get_avif_processor();
$options = get_option('image_convertor_options', []);
$is_available = $avif_processor->is_available();
$browser_support = $avif_processor->browser_supports_avif();
$stats = $avif_processor->get_conversion_stats();

?>

<div class="avif-settings-tab">
    <?php if (!$is_available): ?>
        <div class="notice notice-warning">
            <p><strong>AVIF機能が利用できません。</strong></p>
            <p>AVIF変換を行うには、以下のいずれかのツールがサーバーにインストールされている必要があります：</p>
            <ul>
                <li><code>cavif</code> - 推奨</li>
                <li><code>avifenc</code></li>
                <li><code>ImageMagick</code> (magick/convert)</li>
            </ul>
            <p><a href="#" id="test-avif-support" class="button">サポート状況を確認</a></p>
        </div>
    <?php endif; ?>
    
    <div class="avif-status-section">
        <h3>AVIF対応状況</h3>
        <div class="status-grid">
            <div class="status-item">
                <div class="status-icon <?php echo $is_available ? 'success' : 'error'; ?>">
                    <?php if ($is_available): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="status-content">
                    <h4>サーバー対応</h4>
                    <p><?php echo $is_available ? 'AVIF変換が利用可能です' : 'AVIF変換ツールが見つかりません'; ?></p>
                </div>
            </div>
            
            <div class="status-item">
                <div class="status-icon <?php echo $browser_support ? 'success' : 'warning'; ?>">
                    <?php if ($browser_support): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="status-content">
                    <h4>ブラウザ対応</h4>
                    <p><?php echo $browser_support ? 'お使いのブラウザはAVIFに対応しています' : 'お使いのブラウザはAVIFに対応していません'; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($is_available): ?>
        <form method="post" action="options.php" id="avif-settings-form">
            <?php settings_fields('image_convertor_options'); ?>
            
            <div class="settings-section">
                <h3>AVIF生成設定</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="generate_avif">AVIF生成を有効化</label>
                        </th>
                        <td>
                            <label class="switch">
                                <input type="checkbox" id="generate_avif" name="image_convertor_options[generate_avif]" value="1" <?php checked(!empty($options['generate_avif'])); ?>>
                                <span class="slider"></span>
                            </label>
                            <p class="description">
                                画像アップロード時にAVIF形式の画像を自動生成します。
                                <br><strong>注意:</strong> この機能を有効にすると、変換処理に時間がかかる場合があります。
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="settings-section avif-quality-settings" <?php echo empty($options['generate_avif']) ? 'style="display: none;"' : ''; ?>>
                <h3>AVIF品質設定</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="avif_quality">品質</label>
                        </th>
                        <td>
                            <div class="range-input-group">
                                <input type="range" id="avif_quality" name="image_convertor_options[avif_quality]" min="1" max="100" step="1" value="<?php echo intval($options['avif_quality'] ?? 80); ?>" class="range-input">
                                <span class="range-value"><?php echo intval($options['avif_quality'] ?? 80); ?></span>
                            </div>
                            <p class="description">
                                AVIF画像の品質を設定します（1-100）。
                                <br>推奨値: 70-90（高品質を保ちながらファイルサイズを削減）
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="avif_speed">エンコード速度</label>
                        </th>
                        <td>
                            <div class="range-input-group">
                                <input type="range" id="avif_speed" name="image_convertor_options[avif_speed]" min="0" max="10" step="1" value="<?php echo intval($options['avif_speed'] ?? 6); ?>" class="range-input">
                                <span class="range-value"><?php echo intval($options['avif_speed'] ?? 6); ?></span>
                            </div>
                            <p class="description">
                                変換速度を設定します（0-10）。
                                <br>高い値ほど高速ですが品質が下がる場合があります。推奨値: 4-8
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="avif_effort">エンコード努力値</label>
                        </th>
                        <td>
                            <div class="range-input-group">
                                <input type="range" id="avif_effort" name="image_convertor_options[avif_effort]" min="0" max="9" step="1" value="<?php echo intval($options['avif_effort'] ?? 4); ?>" class="range-input">
                                <span class="range-value"><?php echo intval($options['avif_effort'] ?? 4); ?></span>
                            </div>
                            <p class="description">
                                エンコードの努力値を設定します（0-9）。
                                <br>高い値ほど高品質ですが変換時間が長くなります。推奨値: 3-6
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="settings-section">
                <h3>一括変換設定</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="bulk_batch_size">バッチサイズ</label>
                        </th>
                        <td>
                            <input type="number" id="bulk_batch_size" name="image_convertor_options[bulk_optimization_batch_size]" min="1" max="50" value="<?php echo intval($options['bulk_optimization_batch_size'] ?? 10); ?>" class="small-text">
                            <p class="description">
                                一度に処理する画像の数を設定します。
                                <br>サーバーの性能に応じて調整してください。推奨値: 5-20
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('設定を保存'); ?>
        </form>
        
        <div class="bulk-conversion-section">
            <h3>一括AVIF変換</h3>
            <p>既存の画像をAVIF形式に一括変換します。この処理には時間がかかる場合があります。</p>
            
            <div class="bulk-conversion-controls">
                <button type="button" id="start-bulk-conversion" class="button button-primary">
                    <span class="dashicons dashicons-images-alt2"></span>
                    一括変換を開始
                </button>
                <button type="button" id="stop-bulk-conversion" class="button" style="display: none;">
                    <span class="dashicons dashicons-no"></span>
                    変換を停止
                </button>
            </div>
            
            <div id="bulk-conversion-progress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <div class="progress-info">
                    <span id="progress-text">準備中...</span>
                    <span id="progress-percentage">0%</span>
                </div>
                <div class="conversion-stats">
                    <div class="stat-item">
                        <span class="stat-label">処理済み:</span>
                        <span id="processed-count">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">成功:</span>
                        <span id="success-count">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">失敗:</span>
                        <span id="error-count">0</span>
                    </div>
                </div>
            </div>
            
            <div id="conversion-errors" style="display: none;">
                <h4>エラー詳細</h4>
                <ul id="error-list"></ul>
            </div>
        </div>
        
        <?php if (!empty($stats['total_conversions'])): ?>
            <div class="avif-stats-section">
                <h3>AVIF変換統計</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['total_conversions']); ?></div>
                        <div class="stat-label">総変換数</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo size_format($stats['total_original_size'] - $stats['total_avif_size']); ?></div>
                        <div class="stat-label">節約容量</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['average_compression'], 1); ?>%</div>
                        <div class="stat-label">平均圧縮率</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['last_conversion'] ? date_i18n('m/d', strtotime($stats['last_conversion'])) : '-'; ?></div>
                        <div class="stat-label">最終変換日</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle AVIF quality settings
    $('#generate_avif').on('change', function() {
        $('.avif-quality-settings').toggle($(this).is(':checked'));
    });
    
    // Range input updates
    $('.range-input').on('input', function() {
        $(this).siblings('.range-value').text($(this).val());
    });
    
    // Test AVIF support
    $('#test-avif-support').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.text();
        
        $button.prop('disabled', true).text('確認中...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'image_convertor_test_avif_support',
                nonce: '<?php echo wp_create_nonce('image_convertor_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var message = 'AVIF対応状況:\n\n';
                    message += 'ライセンス: ' + (data.license_active ? '有効' : '無効') + '\n';
                    message += 'AVIF機能: ' + (data.avif_available ? '利用可能' : '利用不可') + '\n';
                    message += 'ブラウザ対応: ' + (data.browser_support ? '対応' : '非対応') + '\n\n';
                    message += 'バイナリ対応状況:\n';
                    
                    for (var binary in data.binaries) {
                        message += '- ' + binary + ': ' + (data.binaries[binary] ? '利用可能' : '利用不可') + '\n';
                    }
                    
                    alert(message);
                } else {
                    alert('エラー: ' + response.data);
                }
            },
            error: function() {
                alert('通信エラーが発生しました。');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Bulk conversion
    var bulkConversionRunning = false;
    var bulkConversionOffset = 0;
    var bulkConversionTotal = 0;
    
    $('#start-bulk-conversion').on('click', function() {
        if (!confirm('既存の画像をAVIF形式に一括変換します。この処理には時間がかかる場合があります。続行しますか？')) {
            return;
        }
        
        bulkConversionRunning = true;
        bulkConversionOffset = 0;
        
        $('#start-bulk-conversion').hide();
        $('#stop-bulk-conversion').show();
        $('#bulk-conversion-progress').show();
        $('#conversion-errors').hide();
        
        // Reset counters
        $('#processed-count, #success-count, #error-count').text('0');
        $('#error-list').empty();
        $('.progress-fill').css('width', '0%');
        $('#progress-percentage').text('0%');
        $('#progress-text').text('変換を開始しています...');
        
        processBulkConversion();
    });
    
    $('#stop-bulk-conversion').on('click', function() {
        bulkConversionRunning = false;
        
        $('#start-bulk-conversion').show();
        $('#stop-bulk-conversion').hide();
        $('#progress-text').text('変換を停止しました。');
    });
    
    function processBulkConversion() {
        if (!bulkConversionRunning) {
            return;
        }
        
        var batchSize = parseInt($('#bulk_batch_size').val()) || 10;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'image_convertor_bulk_avif_conversion',
                batch_size: batchSize,
                offset: bulkConversionOffset,
                nonce: '<?php echo wp_create_nonce('image_convertor_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    // Update counters
                    var currentProcessed = parseInt($('#processed-count').text());
                    var currentSuccess = parseInt($('#success-count').text());
                    var currentErrors = parseInt($('#error-count').text());
                    
                    $('#processed-count').text(currentProcessed + data.processed);
                    $('#success-count').text(currentSuccess + data.successful);
                    $('#error-count').text(currentErrors + data.failed);
                    
                    // Add errors to list
                    if (data.errors && data.errors.length > 0) {
                        data.errors.forEach(function(error) {
                            $('#error-list').append('<li>' + error + '</li>');
                        });
                        $('#conversion-errors').show();
                    }
                    
                    // Update progress
                    bulkConversionOffset += data.processed;
                    
                    if (data.processed > 0) {
                        $('#progress-text').text('変換中... (' + (currentProcessed + data.processed) + '枚処理済み)');
                        
                        // Continue processing
                        setTimeout(processBulkConversion, 1000);
                    } else {
                        // Conversion complete
                        bulkConversionRunning = false;
                        $('#start-bulk-conversion').show();
                        $('#stop-bulk-conversion').hide();
                        $('#progress-text').text('変換が完了しました。');
                        $('.progress-fill').css('width', '100%');
                        $('#progress-percentage').text('100%');
                        
                        alert('一括変換が完了しました。\n\n処理済み: ' + $('#processed-count').text() + '枚\n成功: ' + $('#success-count').text() + '枚\n失敗: ' + $('#error-count').text() + '枚');
                    }
                } else {
                    bulkConversionRunning = false;
                    $('#start-bulk-conversion').show();
                    $('#stop-bulk-conversion').hide();
                    $('#progress-text').text('エラーが発生しました。');
                    alert('エラー: ' + response.data);
                }
            },
            error: function() {
                bulkConversionRunning = false;
                $('#start-bulk-conversion').show();
                $('#stop-bulk-conversion').hide();
                $('#progress-text').text('通信エラーが発生しました。');
                alert('通信エラーが発生しました。');
            }
        });
    }
});
</script>

<style>
.avif-settings-tab {
    max-width: 1000px;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.status-icon {
    flex-shrink: 0;
}

.status-icon.success {
    color: #10b981;
}

.status-icon.warning {
    color: #f59e0b;
}

.status-icon.error {
    color: #dc2626;
}

.status-content h4 {
    margin: 0 0 4px 0;
    font-size: 1rem;
    color: #1f2937;
}

.status-content p {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.settings-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.settings-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #1f2937;
}

.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #3b82f6;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.range-input-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.range-input {
    flex: 1;
    max-width: 300px;
}

.range-value {
    min-width: 30px;
    text-align: center;
    font-weight: 600;
    color: #1f2937;
}

.bulk-conversion-section {
    margin-top: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.bulk-conversion-controls {
    margin: 20px 0;
}

.bulk-conversion-controls .button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: #f3f4f6;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 12px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    width: 0%;
    transition: width 0.3s ease;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    font-size: 0.875rem;
    color: #6b7280;
}

.conversion-stats {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    gap: 6px;
    font-size: 0.875rem;
}

.stat-label {
    color: #6b7280;
}

.avif-stats-section {
    margin-top: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.stat-card {
    text-align: center;
    padding: 20px;
    background: #f9fafb;
    border-radius: 6px;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 8px;
}

.stat-label {
    color: #6b7280;
    font-size: 0.875rem;
}

#conversion-errors {
    margin-top: 20px;
    padding: 16px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 6px;
}

#conversion-errors h4 {
    margin-top: 0;
    color: #dc2626;
}

#error-list {
    margin: 0;
    padding-left: 20px;
    color: #7f1d1d;
}

@media (max-width: 768px) {
    .status-grid,
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .conversion-stats {
        flex-direction: column;
        gap: 8px;
    }
    
    .range-input-group {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>