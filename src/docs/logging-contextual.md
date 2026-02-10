# コンテキスト対応ログシステム

このドキュメントでは、CommuniCareV2で導入されているコンテキスト対応ログシステムの使用方法と設定方法を説明します。

## 概要

コンテキスト対応ログシステムは、実行コンテキスト（Web/CLI、ドメイン、ルート、ユーザーロール等）に基づいて自動的に適切なログチャンネルを選択し、日付ローテーションされたログファイルに出力します。

## 主な特徴

- **自動チャンネル選択**: 実行コンテキストに応じて自動的にログチャンネルを決定
- **日付ローテーション**: 各チャンネルは daily ドライバで日付ごとにログファイルを分割
- **可変チャンネル数**: 環境変数で利用するチャンネル数を制御可能（1つ〜複数）
- **後方互換性**: 既存の `Log::` ファサードと同様のAPIを提供

## ログチャンネル

### web
- **用途**: 一般ユーザー（テナントドメイン経由）のアクセス
- **ログファイル**: `storage/logs/communicare.web-YYYY-MM-DD.log`
- **判定条件**: 
  - Web環境
  - ゲストドメインでない
  - 管理者専用ルートでない

### guest
- **用途**: ゲストユーザー（ゲストドメイン経由）のアクセス
- **ログファイル**: `storage/logs/communicare.guest-YYYY-MM-DD.log`
- **判定条件**:
  - Web環境
  - ゲストドメイン（`config('guest.domains.' . config('app.env'))`）に一致

### admin
- **用途**: 管理者機能へのアクセス
- **ログファイル**: `storage/logs/communicare.admin-YYYY-MM-DD.log`
- **判定条件**:
  - Web環境
  - ルート名が `admin.` で始まる、またはパスが `admin/` で始まる

### console
- **用途**: CLIコマンド（artisan、queue worker等）
- **ログファイル**: `storage/logs/communicare.console-YYYY-MM-DD.log`
- **判定条件**:
  - CLI環境（`app()->runningInConsole()`）

## 使用方法

### 基本的な使用方法

```php
use App\Facades\Logs;

// 情報ログ
Logs::info('ユーザーがログインしました', ['user_id' => $user->id]);

// エラーログ
Logs::error('ファイルの保存に失敗しました', ['exception' => $e]);

// 警告ログ
Logs::warning('セキュリティイベント: 不正アクセス試行', ['ip' => $ip]);

// クリティカルログ
Logs::critical('テナント境界違反', $context);
```

### サポートされているログレベル

- `emergency`
- `alert`
- `critical`
- `error`
- `warning`
- `notice`
- `info`
- `debug`

## 設定

### 環境変数

`.env` ファイルで以下の設定が可能です：

```env
# 利用するログチャンネルをカンマ区切りで指定
LOG_CHANNELS=web,guest,admin,console

# 単一チャンネルのみを使用する場合
LOG_CHANNELS=web

# 2つのチャンネルのみを使用する場合
LOG_CHANNELS=web,console
```

### チャンネル設定

`config/logging.php` で各チャンネルの詳細設定が可能です：

```php
'web' => [
    'driver' => 'daily',
    'path' => storage_path('logs/communicare.web'), // 拡張子なし（dailyドライバが自動的に追加）
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => env('LOG_DAILY_DAYS', 14), // 14日間保持
    'replace_placeholders' => true,
],
```

## チャンネル判定ロジック

チャンネルは以下の優先順位で判定されます：

1. **CLI環境**: `console` チャンネル
2. **ゲストドメイン**: `guest` チャンネル
3. **管理者ルート**: `admin` チャンネル
4. **デフォルト**: `web` チャンネル

## 既存コードからの移行

### 移行前

```php
use Illuminate\Support\Facades\Log;

Log::info('メッセージ', ['context' => 'data']);
Log::error('エラー', ['exception' => $e]);
```

### 移行後

```php
use App\Facades\Logs;

Logs::info('メッセージ', ['context' => 'data']);
Logs::error('エラー', ['exception' => $e]);
```

## ログファイルの場所

すべてのログファイルは `storage/logs/` ディレクトリに保存されます：

```
storage/logs/
├── communicare.web-2026-02-07.log
├── communicare.guest-2026-02-07.log
├── communicare.admin-2026-02-07.log
└── communicare.console-2026-02-07.log
```

日付ローテーションにより、日付ごとにファイルが分割されます。

## トラブルシューティング

### チャンネルが正しく選択されない場合

1. `.env` の `LOG_CHANNELS` 設定を確認
2. `config/logging.php` のチャンネル定義を確認
3. `php artisan config:clear` で設定キャッシュをクリア

### ログファイルが生成されない場合

1. `storage/logs/` ディレクトリの書き込み権限を確認
2. ログレベル（`LOG_LEVEL`）が適切に設定されているか確認
3. アプリケーションの実行環境（Web/CLI）を確認

## 実装詳細

### サービスクラス

`App\Services\ContextualLogService` がチャンネル判定とログ出力を担当します。

### ファサード

`App\Facades\Logs` が `ContextualLogService` へのアクセスを提供します。

### サービスプロバイダー

`App\Providers\AppServiceProvider` でサービスがシングルトンとして登録されます。

## 関連ファイル

- `app/Services/ContextualLogService.php`: ログサービス実装
- `app/Facades/Logs.php`: ファサード定義
- `config/logging.php`: チャンネル設定
- `app/Providers/AppServiceProvider.php`: サービス登録
