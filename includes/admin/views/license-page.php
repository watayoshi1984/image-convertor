<?php
/**
 * License Management Page Template
 * 
 * @package WyoshiImageOptimizer
 * @subpackage Admin\Views
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$plugin = \WyoshiImageOptimizer\Plugin::get_instance();
$pro_manager = $plugin->get_pro_manager();
$license_manager = $pro_manager->get_license_manager();
$license_data = $license_manager->get_license_data();
$is_active = $license_manager->is_license_active();

?>

<div class="wrap wyoshi-img-opt-license-page">
    <h1>ライセンス管理</h1>
    
    <?php if ($is_active): ?>
        <div class="license-status active">
            <div class="license-status-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="license-status-content">
                <h2>ライセンスがアクティブです</h2>
                <p>Image Convertor Proの全機能をご利用いただけます。</p>
            </div>
        </div>
        
        <div class="license-details">
            <h3>ライセンス詳細</h3>
            <table class="license-info-table">
                <tr>
                    <th>ライセンスキー</th>
                    <td>
                        <code><?php echo esc_html(substr($license_data['license_key'], 0, 8) . '****-****-****-' . substr($license_data['license_key'], -4)); ?></code>
                        <button type="button" class="button button-small" id="show-full-license">全体を表示</button>
                        <div id="full-license" style="display: none;">
                            <code><?php echo esc_html($license_data['license_key']); ?></code>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>プラン</th>
                    <td><?php echo esc_html($license_data['plan'] ?? 'Unknown'); ?></td>
                </tr>
                <tr>
                    <th>サイト制限</th>
                    <td>
                        <?php if ($license_data['site_limit'] == -1): ?>
                            無制限
                        <?php else: ?>
                            <?php echo intval($license_data['site_limit']); ?>サイト
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>アクティベート日</th>
                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($license_data['activated_at']))); ?></td>
                </tr>
                <tr>
                    <th>有効期限</th>
                    <td>
                        <?php if (!empty($license_data['expires'])): ?>
                            <?php 
                            $expires = strtotime($license_data['expires']);
                            $days_until_expiry = ceil(($expires - time()) / DAY_IN_SECONDS);
                            ?>
                            <?php echo esc_html(date_i18n(get_option('date_format'), $expires)); ?>
                            <?php if ($days_until_expiry <= 30 && $days_until_expiry > 0): ?>
                                <span class="expiry-warning">(残り<?php echo $days_until_expiry; ?>日)</span>
                            <?php elseif ($days_until_expiry <= 0): ?>
                                <span class="expiry-expired">(期限切れ)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            無期限
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>最終チェック</th>
                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($license_data['last_check']))); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="license-actions">
            <h3>ライセンス操作</h3>
            <div class="license-action-buttons">
                <button type="button" class="button" id="check-license-status">
                    <span class="dashicons dashicons-update"></span>
                    ライセンス状態を確認
                </button>
                <button type="button" class="button" id="deactivate-license">
                    <span class="dashicons dashicons-dismiss"></span>
                    ライセンスを無効化
                </button>
                <?php if (!empty($license_data['expires'])): ?>
                    <a href="https://example.com/renew?license=<?php echo urlencode($license_data['license_key']); ?>" class="button button-primary" target="_blank">
                        <span class="dashicons dashicons-cart"></span>
                        ライセンスを更新
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
    <?php else: ?>
        <div class="license-status inactive">
            <div class="license-status-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="license-status-content">
                <h2>ライセンスが無効です</h2>
                <p>Pro機能をご利用いただくには、有効なライセンスキーが必要です。</p>
            </div>
        </div>
        
        <div class="license-activation">
            <h3>ライセンスをアクティベート</h3>
            <form id="license-activation-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="license_key">ライセンスキー</label>
                        </th>
                        <td>
                            <input type="text" id="license_key" name="license_key" class="regular-text" placeholder="XXXX-XXXX-XXXX-XXXX" required>
                            <p class="description">
                                ライセンスキーは購入時にお送りしたメールに記載されています。
                                <br>形式: XXXX-XXXX-XXXX-XXXX
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-yes"></span>
                        ライセンスをアクティベート
                    </button>
                    <span class="spinner"></span>
                </p>
            </form>
        </div>
        
        <div class="license-purchase">
            <h3>ライセンスをお持ちでない方</h3>
            <p>Image Convertor Proをご購入いただくと、以下の機能をご利用いただけます：</p>
            <ul class="pro-features-list">
                <li>AVIF形式対応</li>
                <li>一括最適化機能</li>
                <li>優先サポート</li>
                <li>高度な設定オプション</li>
            </ul>
            <p>
                <a href="<?php echo admin_url('admin.php?page=image-convertor-upgrade'); ?>" class="button button-primary button-large">
                    Pro版を購入する
                </a>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="license-support">
        <h3>サポート情報</h3>
        <div class="support-grid">
            <div class="support-item">
                <h4>ドキュメント</h4>
                <p>詳細な使用方法やトラブルシューティングガイドをご確認いただけます。</p>
                <a href="https://example.com/docs" target="_blank" class="button">ドキュメントを見る</a>
            </div>
            
            <div class="support-item">
                <h4>サポートフォーラム</h4>
                <p>他のユーザーとの情報交換や質問の投稿が可能です。</p>
                <a href="https://example.com/forum" target="_blank" class="button">フォーラムを見る</a>
            </div>
            
            <div class="support-item">
                <h4>お問い合わせ</h4>
                <p>技術的な問題やライセンスに関するご質問はこちらから。</p>
                <a href="https://example.com/contact" target="_blank" class="button">お問い合わせ</a>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Show full license key
    $('#show-full-license').on('click', function() {
        $('#full-license').toggle();
        $(this).text($(this).text() === '全体を表示' ? '非表示' : '全体を表示');
    });
    
    // License activation form
    $('#license-activation-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $spinner = $form.find('.spinner');
        var licenseKey = $('#license_key').val().trim();
        
        if (!licenseKey) {
            alert('ライセンスキーを入力してください。');
            return;
        }
        
        // Validate license key format
        if (!/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(licenseKey)) {
            alert('ライセンスキーの形式が正しくありません。XXXX-XXXX-XXXX-XXXX の形式で入力してください。');
            return;
        }
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wyoshi_img_opt_activate_license',
                license_key: licenseKey,
                nonce: '<?php echo wp_create_nonce('wyoshi_img_opt_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('ライセンスが正常にアクティベートされました。ページを再読み込みします。');
                    location.reload();
                } else {
                    alert('エラー: ' + response.data);
                }
            },
            error: function() {
                alert('通信エラーが発生しました。しばらく時間をおいて再度お試しください。');
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Check license status
    $('#check-license-status').on('click', function() {
        var $button = $(this);
        var originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> 確認中...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wyoshi_img_opt_check_license',
                nonce: '<?php echo wp_create_nonce('wyoshi_img_opt_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('ライセンス状態を更新しました。ページを再読み込みします。');
                    location.reload();
                } else {
                    alert('エラー: ' + response.data);
                }
            },
            error: function() {
                alert('通信エラーが発生しました。');
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Deactivate license
    $('#deactivate-license').on('click', function() {
        if (!confirm('ライセンスを無効化しますか？Pro機能が利用できなくなります。')) {
            return;
        }
        
        var $button = $(this);
        var originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> 処理中...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wyoshi_img_opt_deactivate_license',
                nonce: '<?php echo wp_create_nonce('wyoshi_img_opt_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('ライセンスが無効化されました。ページを再読み込みします。');
                    location.reload();
                } else {
                    alert('エラー: ' + response.data);
                }
            },
            error: function() {
                alert('通信エラーが発生しました。');
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Dismiss Pro notice
    $(document).on('click', '.notice[data-notice="pro-upgrade"] .notice-dismiss', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wyoshi_img_opt_dismiss_notice',
                notice: 'pro-upgrade',
                nonce: '<?php echo wp_create_nonce('wyoshi_img_opt_admin_nonce'); ?>'
            }
        });
    });
});
</script>

<style>
.image-convertor-license-page {
    max-width: 1000px;
}

.license-status {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 24px;
    border-radius: 8px;
    margin-bottom: 32px;
}

.license-status.active {
    background: #ecfdf5;
    border: 1px solid #10b981;
}

.license-status.inactive {
    background: #fffbeb;
    border: 1px solid #f59e0b;
}

.license-status-content h2 {
    margin: 0 0 8px 0;
    font-size: 1.25rem;
}

.license-status.active .license-status-content h2 {
    color: #065f46;
}

.license-status.inactive .license-status-content h2 {
    color: #92400e;
}

.license-status-content p {
    margin: 0;
    color: #6b7280;
}

.license-details,
.license-activation,
.license-purchase,
.license-actions,
.license-support {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 24px;
}

.license-details h3,
.license-activation h3,
.license-purchase h3,
.license-actions h3,
.license-support h3 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 1.125rem;
    color: #1f2937;
}

.license-info-table {
    width: 100%;
    border-collapse: collapse;
}

.license-info-table th,
.license-info-table td {
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
    text-align: left;
}

.license-info-table th {
    width: 150px;
    font-weight: 600;
    color: #374151;
}

.license-info-table td {
    color: #6b7280;
}

.license-info-table code {
    background: #f3f4f6;
    padding: 4px 8px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
}

.expiry-warning {
    color: #f59e0b;
    font-weight: 600;
}

.expiry-expired {
    color: #dc2626;
    font-weight: 600;
}

.license-action-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.license-action-buttons .button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.pro-features-list {
    list-style: none;
    padding: 0;
    margin: 16px 0;
}

.pro-features-list li {
    padding: 6px 0;
    position: relative;
    padding-left: 24px;
    color: #374151;
}

.pro-features-list li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #10b981;
    font-weight: bold;
}

.support-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.support-item {
    padding: 20px;
    background: #f9fafb;
    border-radius: 6px;
}

.support-item h4 {
    margin-top: 0;
    margin-bottom: 12px;
    color: #1f2937;
}

.support-item p {
    color: #6b7280;
    margin-bottom: 16px;
    line-height: 1.5;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .license-status {
        flex-direction: column;
        text-align: center;
    }
    
    .license-action-buttons {
        flex-direction: column;
    }
    
    .license-info-table th {
        width: auto;
    }
    
    .support-grid {
        grid-template-columns: 1fr;
    }
}
</style>