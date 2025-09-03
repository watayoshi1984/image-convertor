# Image Convertor

高性能な画像最適化WordPressプラグイン

## 概要

Image Convertorは、WordPressサイトの画像を自動的に最適化し、WebP/AVIF形式に変換することで、サイトの読み込み速度を大幅に向上させるプラグインです。

## 主な機能

### 無料版機能
- **WebP変換**: JPEG/PNG画像を高品質なWebP形式に自動変換
- **一括最適化**: 既存の画像を一括で最適化
- **自動最適化**: 新しくアップロードされる画像を自動的に最適化
- **フロントエンド配信**: 最適化された画像を自動的に配信
- **バックアップ機能**: 元の画像を安全に保管
- **詳細統計**: 最適化による容量削減効果を可視化
- **管理画面**: 直感的な設定インターフェース

### Pro版機能
- **AVIF対応**: 次世代画像フォーマットAVIFに対応
- **高度な設定**: より細かい品質調整とエンコード設定
- **優先サポート**: 専用サポートチャンネル
- **ライセンス管理**: 複数サイトでの利用

## システム要件

- WordPress 5.0以上
- PHP 7.4以上
- 画像処理バイナリ（cwebp、cavif等）

## インストール

1. プラグインファイルをWordPressの`/wp-content/plugins/`ディレクトリにアップロード
2. WordPress管理画面でプラグインを有効化
3. 「設定」→「Image Convertor」から初期設定を実行

## 使用方法

### 基本設定

1. **一般設定**
   - 自動最適化の有効/無効
   - WebP生成の有効/無効
   - バックアップの有効/無効

2. **品質設定**
   - WebP品質（0-100）
   - JPEG品質（0-100）
   - 最大画像サイズ

3. **配信設定**
   - フロントエンド配信の有効/無効
   - 遅延読み込みの強化
   - プリロードヘッダーの追加

### 一括最適化

1. 「メディア」→「一括最適化」にアクセス
2. 最適化したい画像を選択
3. 「最適化開始」ボタンをクリック
4. 進捗を確認しながら完了を待つ

### 統計の確認

1. 「設定」→「Image Convertor」→「統計」タブ
2. 最適化された画像数、容量削減効果を確認
3. 詳細なレポートをダウンロード可能

## 技術仕様

### サポートフォーマット

**入力フォーマット:**
- JPEG (.jpg, .jpeg)
- PNG (.png)
- GIF (.gif)
- BMP (.bmp)
- TIFF (.tiff, .tif)

**出力フォーマット:**
- WebP (.webp)
- AVIF (.avif) - Pro版のみ

### 使用バイナリ

- **cwebp**: WebP変換用
- **cavif**: AVIF変換用（Pro版）
- **avifenc**: AVIF変換用（Pro版）
- **ImageMagick**: フォールバック用

### ディレクトリ構造

```
image-convertor/
├── image-convertor.php          # メインプラグインファイル
├── includes/
│   ├── Core/
│   │   └── Plugin.php           # プラグインコアクラス
│   ├── Processing/
│   │   ├── BinaryWrapper.php    # バイナリ実行ラッパー
│   │   └── ImageProcessor.php   # 画像処理エンジン
│   ├── Admin/
│   │   ├── AdminManager.php     # 管理画面マネージャー
│   │   └── views/               # 管理画面ビューファイル
│   ├── Media/
│   │   └── MediaManager.php     # メディアライブラリ連携
│   ├── Delivery/
│   │   └── DeliveryManager.php  # フロントエンド配信
│   ├── Pro/
│   │   ├── ProManager.php       # Pro版機能管理
│   │   ├── LicenseManager.php   # ライセンス管理
│   │   └── AvifProcessor.php    # AVIF処理
│   └── Utils/
│       ├── Logger.php           # ログ機能
│       └── Utils.php            # ユーティリティ
├── assets/
│   ├── css/                     # スタイルシート
│   └── js/                      # JavaScript
└── logs/                        # ログファイル
```

## 開発者向け情報

### フック

**アクション:**
- `image_convertor_before_optimization`: 最適化前
- `image_convertor_after_optimization`: 最適化後
- `image_convertor_cleanup`: クリーンアップ時

**フィルター:**
- `image_convertor_supported_formats`: サポートフォーマット
- `image_convertor_quality_settings`: 品質設定
- `image_convertor_delivery_formats`: 配信フォーマット

### API使用例

```php
// プラグインインスタンスの取得
$plugin = ImageConvertor\Core\Plugin::get_instance();

// 画像の最適化
$processor = $plugin->get_image_processor();
$result = $processor->optimize_image('/path/to/image.jpg');

// 統計の取得
$stats = $plugin->get_admin_manager()->get_optimization_stats();
```

## トラブルシューティング

### よくある問題

**Q: 画像が最適化されない**
A: 以下を確認してください：
- 必要なバイナリがインストールされているか
- アップロードディレクトリの書き込み権限
- PHPのメモリ制限

**Q: WebPが表示されない**
A: ブラウザがWebPに対応しているか確認してください。古いブラウザでは自動的に元の形式が配信されます。

**Q: Pro版機能が使用できない**
A: 有効なライセンスキーが設定されているか確認してください。

### ログの確認

1. 「設定」→「Image Convertor」→「システム情報」
2. ログファイルの場所を確認
3. エラーメッセージを確認

## サポート

- **ドキュメント**: [公式ドキュメント](https://example.com/docs)
- **サポートフォーラム**: [WordPress.org](https://wordpress.org/support/plugin/image-convertor)
- **Pro版サポート**: [専用サポート](https://example.com/support)

## ライセンス

GPL v2 or later

## 更新履歴

### 1.0.0
- 初回リリース
- WebP変換機能
- 一括最適化機能
- フロントエンド配信機能
- Pro版AVIF対応
- ライセンス管理システム