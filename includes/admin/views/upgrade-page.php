<?php
/**
 * Upgrade Page Template
 * 
 * @package WyoshiImageOptimizer
 * @subpackage Admin\Views
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
?>

<div class="wrap wyoshi-img-opt-upgrade-page">
    <h1>Image Convertor Pro にアップグレード</h1>
    
    <div class="upgrade-hero">
        <div class="upgrade-hero-content">
            <h2>より高速で高品質な画像最適化を</h2>
            <p class="lead">Pro版では最新のAVIF形式対応、一括最適化、優先サポートなど、さらに強力な機能をご利用いただけます。</p>
        </div>
        <div class="upgrade-hero-image">
            <svg width="200" height="150" viewBox="0 0 200 150" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="200" height="150" rx="8" fill="#f0f6ff"/>
                <rect x="20" y="30" width="60" height="40" rx="4" fill="#3b82f6"/>
                <rect x="90" y="30" width="60" height="40" rx="4" fill="#10b981"/>
                <rect x="160" y="30" width="20" height="40" rx="4" fill="#f59e0b"/>
                <text x="50" y="55" text-anchor="middle" fill="white" font-size="12" font-weight="bold">JPEG</text>
                <text x="120" y="55" text-anchor="middle" fill="white" font-size="12" font-weight="bold">WebP</text>
                <text x="170" y="55" text-anchor="middle" fill="white" font-size="10" font-weight="bold">AVIF</text>
                <text x="100" y="100" text-anchor="middle" fill="#6b7280" font-size="14">最大90%のファイルサイズ削減</text>
                <path d="M50 80 L120 80" stroke="#3b82f6" stroke-width="2" marker-end="url(#arrowhead)"/>
                <path d="M120 80 L170 80" stroke="#10b981" stroke-width="2" marker-end="url(#arrowhead)"/>
                <defs>
                    <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                        <polygon points="0 0, 10 3.5, 0 7" fill="#6b7280"/>
                    </marker>
                </defs>
            </svg>
        </div>
    </div>
    
    <div class="upgrade-features">
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3>AVIF対応</h3>
                <p>次世代画像フォーマットAVIFに対応。WebPよりもさらに高い圧縮率を実現し、ページの読み込み速度を大幅に向上させます。</p>
                <ul>
                    <li>WebPより最大50%小さいファイルサイズ</li>
                    <li>自動ブラウザ判定</li>
                    <li>フォールバック機能</li>
                </ul>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="m9 14 2 2 4-4" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3>一括最適化</h3>
                <p>既存の画像を一括で最適化。数千枚の画像も効率的に処理し、サイト全体のパフォーマンスを向上させます。</p>
                <ul>
                    <li>バックグラウンド処理</li>
                    <li>進捗表示</li>
                    <li>エラーハンドリング</li>
                </ul>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 9V5a3 3 0 0 0-6 0v4" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="2" y="9" width="20" height="12" rx="2" ry="2" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3>優先サポート</h3>
                <p>Pro版ユーザー専用の優先サポートをご利用いただけます。技術的な質問や設定のサポートを迅速に対応いたします。</p>
                <ul>
                    <li>24時間以内の返信保証</li>
                    <li>専用サポートチャンネル</li>
                    <li>設定代行サービス</li>
                </ul>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="3" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3>高度な設定</h3>
                <p>より細かい画質設定、変換オプション、配信設定など、プロフェッショナルな用途に対応した高度な設定が可能です。</p>
                <ul>
                    <li>詳細な品質設定</li>
                    <li>カスタム変換パラメータ</li>
                    <li>条件付き配信ルール</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="pricing-section">
        <h2>料金プラン</h2>
        <div class="pricing-cards">
            <div class="pricing-card">
                <div class="pricing-header">
                    <h3>Personal</h3>
                    <div class="price">
                        <span class="currency">¥</span>
                        <span class="amount">2,980</span>
                        <span class="period">/年</span>
                    </div>
                </div>
                <div class="pricing-features">
                    <ul>
                        <li>1サイトライセンス</li>
                        <li>AVIF対応</li>
                        <li>一括最適化</li>
                        <li>メールサポート</li>
                        <li>1年間のアップデート</li>
                    </ul>
                </div>
                <div class="pricing-action">
                    <a href="#" class="button button-primary button-large">今すぐ購入</a>
                </div>
            </div>
            
            <div class="pricing-card featured">
                <div class="pricing-badge">おすすめ</div>
                <div class="pricing-header">
                    <h3>Professional</h3>
                    <div class="price">
                        <span class="currency">¥</span>
                        <span class="amount">4,980</span>
                        <span class="period">/年</span>
                    </div>
                </div>
                <div class="pricing-features">
                    <ul>
                        <li>5サイトライセンス</li>
                        <li>AVIF対応</li>
                        <li>一括最適化</li>
                        <li>優先サポート</li>
                        <li>1年間のアップデート</li>
                        <li>設定代行サービス</li>
                    </ul>
                </div>
                <div class="pricing-action">
                    <a href="#" class="button button-primary button-large">今すぐ購入</a>
                </div>
            </div>
            
            <div class="pricing-card">
                <div class="pricing-header">
                    <h3>Agency</h3>
                    <div class="price">
                        <span class="currency">¥</span>
                        <span class="amount">9,980</span>
                        <span class="period">/年</span>
                    </div>
                </div>
                <div class="pricing-features">
                    <ul>
                        <li>無制限サイトライセンス</li>
                        <li>AVIF対応</li>
                        <li>一括最適化</li>
                        <li>優先サポート</li>
                        <li>1年間のアップデート</li>
                        <li>設定代行サービス</li>
                        <li>カスタム開発サポート</li>
                    </ul>
                </div>
                <div class="pricing-action">
                    <a href="#" class="button button-primary button-large">今すぐ購入</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="testimonials-section">
        <h2>お客様の声</h2>
        <div class="testimonials-grid">
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>「AVIF対応により、サイトの読み込み速度が劇的に改善されました。特にモバイルでの体感速度の向上が顕著です。」</p>
                </div>
                <div class="testimonial-author">
                    <strong>田中様</strong>
                    <span>ECサイト運営者</span>
                </div>
            </div>
            
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>「一括最適化機能で、5000枚以上の商品画像を効率的に処理できました。サーバー容量も大幅に削減されています。」</p>
                </div>
                <div class="testimonial-author">
                    <strong>佐藤様</strong>
                    <span>Web制作会社</span>
                </div>
            </div>
            
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>「サポートの対応が迅速で丁寧。技術的な質問にも的確に答えていただき、安心して利用できています。」</p>
                </div>
                <div class="testimonial-author">
                    <strong>鈴木様</strong>
                    <span>ブロガー</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="faq-section">
        <h2>よくある質問</h2>
        <div class="faq-list">
            <div class="faq-item">
                <h3>既存のライセンスから移行できますか？</h3>
                <p>はい、既存のライセンスをお持ちの場合は、差額でアップグレードが可能です。詳細はサポートまでお問い合わせください。</p>
            </div>
            
            <div class="faq-item">
                <h3>AVIF対応にはサーバー側の設定が必要ですか？</h3>
                <p>基本的には不要です。プラグインが自動的にブラウザの対応状況を判定し、適切な形式の画像を配信します。</p>
            </div>
            
            <div class="faq-item">
                <h3>返金保証はありますか？</h3>
                <p>ご購入から30日以内であれば、理由を問わず全額返金いたします。安心してお試しください。</p>
            </div>
            
            <div class="faq-item">
                <h3>ライセンスの更新はどのように行いますか？</h3>
                <p>ライセンス期限の30日前にメールでお知らせいたします。管理画面から簡単に更新手続きが可能です。</p>
            </div>
        </div>
    </div>
    
    <div class="cta-section">
        <div class="cta-content">
            <h2>今すぐImage Convertor Proを始めましょう</h2>
            <p>30日間の返金保証付き。リスクなしでお試しいただけます。</p>
            <div class="cta-buttons">
                <a href="#" class="button button-primary button-hero">Pro版を購入する</a>
                <a href="#" class="button button-secondary">デモを見る</a>
            </div>
        </div>
    </div>
</div>

<style>
.image-convertor-upgrade-page {
    max-width: 1200px;
    margin: 0 auto;
}

.upgrade-hero {
    display: flex;
    align-items: center;
    gap: 40px;
    padding: 40px 0;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 40px;
}

.upgrade-hero-content {
    flex: 1;
}

.upgrade-hero h2 {
    font-size: 2.5rem;
    margin-bottom: 16px;
    color: #1f2937;
}

.lead {
    font-size: 1.25rem;
    color: #6b7280;
    line-height: 1.6;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-bottom: 60px;
}

.feature-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
}

.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.feature-icon {
    margin-bottom: 20px;
}

.feature-card h3 {
    font-size: 1.5rem;
    margin-bottom: 16px;
    color: #1f2937;
}

.feature-card p {
    color: #6b7280;
    margin-bottom: 20px;
    line-height: 1.6;
}

.feature-card ul {
    list-style: none;
    padding: 0;
    text-align: left;
}

.feature-card li {
    padding: 4px 0;
    color: #374151;
    position: relative;
    padding-left: 20px;
}

.feature-card li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #10b981;
    font-weight: bold;
}

.pricing-section {
    margin: 60px 0;
    text-align: center;
}

.pricing-section h2 {
    font-size: 2rem;
    margin-bottom: 40px;
    color: #1f2937;
}

.pricing-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    max-width: 900px;
    margin: 0 auto;
}

.pricing-card {
    background: #fff;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 30px;
    position: relative;
    transition: transform 0.2s, box-shadow 0.2s;
}

.pricing-card.featured {
    border-color: #3b82f6;
    transform: scale(1.05);
}

.pricing-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.pricing-card.featured:hover {
    transform: scale(1.05) translateY(-4px);
}

.pricing-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: #3b82f6;
    color: white;
    padding: 6px 20px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.pricing-header h3 {
    font-size: 1.5rem;
    margin-bottom: 16px;
    color: #1f2937;
}

.price {
    margin-bottom: 30px;
}

.currency {
    font-size: 1.25rem;
    color: #6b7280;
    vertical-align: top;
}

.amount {
    font-size: 3rem;
    font-weight: 700;
    color: #1f2937;
}

.period {
    font-size: 1rem;
    color: #6b7280;
}

.pricing-features ul {
    list-style: none;
    padding: 0;
    margin-bottom: 30px;
}

.pricing-features li {
    padding: 8px 0;
    color: #374151;
    position: relative;
    padding-left: 24px;
}

.pricing-features li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #10b981;
    font-weight: bold;
}

.testimonials-section {
    margin: 60px 0;
    background: #f9fafb;
    padding: 60px 40px;
    border-radius: 12px;
}

.testimonials-section h2 {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 40px;
    color: #1f2937;
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}

.testimonial {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.testimonial-content p {
    font-style: italic;
    color: #374151;
    margin-bottom: 20px;
    line-height: 1.6;
}

.testimonial-author strong {
    color: #1f2937;
    display: block;
    margin-bottom: 4px;
}

.testimonial-author span {
    color: #6b7280;
    font-size: 0.875rem;
}

.faq-section {
    margin: 60px 0;
}

.faq-section h2 {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 40px;
    color: #1f2937;
}

.faq-list {
    max-width: 800px;
    margin: 0 auto;
}

.faq-item {
    margin-bottom: 30px;
    padding: 30px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

.faq-item h3 {
    color: #1f2937;
    margin-bottom: 12px;
    font-size: 1.25rem;
}

.faq-item p {
    color: #6b7280;
    line-height: 1.6;
    margin: 0;
}

.cta-section {
    text-align: center;
    padding: 60px 40px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 12px;
    color: white;
    margin: 60px 0;
}

.cta-content h2 {
    font-size: 2rem;
    margin-bottom: 16px;
}

.cta-content p {
    font-size: 1.125rem;
    margin-bottom: 30px;
    opacity: 0.9;
}

.cta-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.button-hero {
    padding: 16px 32px;
    font-size: 1.125rem;
}

.button-secondary {
    background: transparent;
    border: 2px solid white;
    color: white;
}

.button-secondary:hover {
    background: white;
    color: #3b82f6;
}

@media (max-width: 768px) {
    .upgrade-hero {
        flex-direction: column;
        text-align: center;
    }
    
    .upgrade-hero h2 {
        font-size: 2rem;
    }
    
    .feature-grid,
    .pricing-cards,
    .testimonials-grid {
        grid-template-columns: 1fr;
    }
    
    .pricing-card.featured {
        transform: none;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
}
</style>