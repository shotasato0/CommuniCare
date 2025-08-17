# CLAUDE.md - CommuniCareV2 開発ガイド

このファイルは、CommuniCareV2プロジェクトで作業する将来のClaude Codeインスタンスのための包括的なガイドです。

## 重要：自動Git操作の許可とセーフガード
Claude Codeは、**featureブランチや保護されていないブランチ**に対してのみ、明示的な許可なく以下の操作を自動実行できます：
- git add, commit, push
- プルリクエスト作成
- レビュー対応のためのcommit・push

**main, master, developなどの保護ブランチに対しては、必ず明示的な許可または確認を得てから自動操作を実行してください。**

---

# 🚨 CommuniCareV2 テスト環境安全性ルール

## ⚠️ 【最重要】介護施設データ保護の絶対原則

**CommuniCareV2は複数の介護施設の機密データを扱うマルチテナントシステムです。**  
**1つのミスが全介護施設の利用者情報・職員データ・介護記録に影響する可能性があります。**

### 📊 システム重要度レベル
- **データの機密性**: 最高（個人情報保護法対象）
- **システム影響範囲**: 全介護施設（マルチテナント）
- **データベース方式**: シングルDB + tenant_idによる論理分離
- **1つの操作の影響**: 全テナント・全利用者・全職員データ

---

## 🚨 絶対禁止操作（違反は重大事故に直結）

### ❌ 完全禁止コマンド
以下のコマンドは**いかなる状況でも実行禁止**です：

```bash
# 🚫 全テーブル削除・再作成（全介護施設データ消失）
php artisan migrate:fresh

# 🚫 全マイグレーション巻き戻し（データベース構造破壊）
php artisan migrate:reset

# 🚫 全テーブル削除（全データ消失）
php artisan db:wipe

# 🚫 マイグレーション巻き戻し（データ整合性破壊）
php artisan migrate:rollback
```

### ❌ 危険なテストトレイト
```php
// 🚫 RefreshDatabase使用禁止（開発DB破壊リスク）
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase 
{
    use RefreshDatabase; // ← 絶対禁止
}
```

### ❌ 危険なデータベース操作
```php
// 🚫 無条件truncate（全テナントデータ削除）
DB::table('users')->truncate();
DB::table('posts')->truncate();
DB::table('residents')->truncate();

// 🚫 DROP文実行
DB::statement('DROP TABLE users');
```

---

## 🛡️ 必須セキュリティチェック

### ✅ 開発作業開始前の確認（毎回実行）

```bash
# 1. 現在の環境確認
php artisan env
# 期待値: "The application environment is [local]"

# 2. データベース接続先確認  
php artisan tinker --execute="echo 'DB: ' . config('database.default') . PHP_EOL; echo 'Database: ' . config('database.connections.mysql.database') . PHP_EOL;"
# 期待値: DB: mysql, Database: laravel

# 3. テスト環境でのデータベース確認
APP_ENV=testing php artisan tinker --execute="echo 'DB: ' . config('database.default') . PHP_EOL; echo 'Database: ' . config('database.connections.sqlite.database') . PHP_EOL;"
# 期待値: DB: sqlite, Database: :memory:
```

### ✅ テスト実行前の安全確認

```bash
# テスト環境の安全性確認
APP_ENV=testing sail test tests/Security/DangerousOperationTest.php
# 全テストが成功することを確認

# セーフティネット動作確認
APP_ENV=testing sail test tests/Unit/PostServiceTest.php
# セキュリティ機構が正常動作することを確認
```

---

## 📚 過去の重大事故事例

### 🚨 【事故報告】2024年8月 パフォーマンステスト事故

**発生状況:**
- パフォーマンステスト実行中に`RefreshDatabase`トレイトが動作
- `migrate:fresh`が開発環境（`laravel`データベース）で実行される
- 全テナント・ドメイン・ユーザーデータが完全削除

**影響範囲:**
- 🏥 全介護施設のテナントデータ消失
- 👥 全職員アカウント削除
- 🏠 全利用者情報消失  
- 🔐 権限システム完全破壊
- 📋 フォーラム・投稿データ全削除

**復旧作業（8時間）:**
1. テナント・ドメインの手動再作成
2. マイグレーション状態の修正
3. 権限システム（Role・Permission）の再構築
4. テストデータの再投入

**根本原因:**
- `RefreshDatabase`トレイトの無制限使用
- 環境分離の不備（testing/local混在）
- 危険操作に対するセーフティネット不在

**実装された再発防止策:**
- `TestCase.php`に3段階セキュリティチェック機構実装
- 危険コマンドの実行時例外発生
- `.env.testing`でのSQLite強制使用
- 危険トレイトの完全無効化

---

## 🤖 AI駆動開発での注意事項

### Claude Code 使用時の必須確認

```markdown
# Claude Codeへの指示例
「CommuniCareV2のマルチテナント設計を考慮し、以下の制約を厳守してください：
- RefreshDatabaseトレイト使用禁止
- migrate:fresh等の危険コマンド使用禁止  
- 全テナントへの影響を常に考慮
- テスト実行時はAPP_ENV=testingを必須とする」
```

### GitHub Copilot 提案の検証

```php
// ❌ Copilotが提案する危険なパターン
use RefreshDatabase; // ← 採用禁止

// ✅ 安全なCommuniCareV2パターン  
use Tests\TestCase; // ← セキュリティ機構内蔵

class MyTest extends TestCase 
{
    // TestCase.phpのセーフティネットが自動適用
}
```

---

## ✅ 推奨される安全な開発フロー

### 🔄 新機能開発時の手順

1. **環境確認** - 現在の作業環境を確認
2. **ブランチ作成** - feature/機能名でブランチ作成
3. **セキュリティ設計** - マルチテナント要件の考慮
4. **段階的実装** - 小さな単位での開発とテスト
5. **安全テスト** - TestCase.phpベースのテスト作成
6. **統合確認** - セキュリティ機構との整合確認

### 🧪 テスト作成時のテンプレート

```php
<?php

namespace Tests\Unit;

use Tests\TestCase; // ← 必須：セキュリティ機構内蔵

class SafeServiceTest extends TestCase
{
    // RefreshDatabaseは使用しない
    // TestCase.phpのセーフティネットが自動適用
    
    protected function setUp(): void
    {
        parent::setUp(); // ← 必須：3段階セキュリティチェック実行
        
        // 安全なテストデータ作成
        $this->createSafeTestData();
    }
    
    protected function createSafeTestData(): void
    {
        // ファクトリを使用した安全なデータ生成
        // truncate()は使用しない
    }
    
    public function test_tenant_boundary_security()
    {
        // テナント境界チェックのテスト
        // CommuniCareV2のマルチテナント要件を必ず検証
    }
}
```

### 🚨 緊急時の対応方法

**データ消失事故発生時:**

1. **即座停止** - 全開発作業を停止
2. **影響範囲確認** - どのテナント・データが影響を受けたか
3. **バックアップ復旧** - 最新バックアップからの復旧実行
4. **原因調査** - 事故原因の徹底調査
5. **再発防止** - セキュリティ機構の強化
6. **報告書作成** - 事故報告書の作成と共有

---

## 🎯 CommuniCareV2固有の重要事項

### 🏢 マルチテナント環境の特殊性

```php
// ✅ 正しいマルチテナント対応
class PostService 
{
    use SecurityValidationTrait, TenantBoundaryCheckTrait;
    
    public function getPosts(): Collection 
    {
        return Post::where('tenant_id', Auth::user()->tenant_id)
                  ->get(); // ← tenant_id必須
    }
}

// ❌ 危険なマルチテナント違反
class BadService 
{
    public function getAllPosts(): Collection 
    {
        return Post::all(); // ← 全テナントデータ取得（重大違反）
    }
}
```

### 📊 介護データの機密性要件

**個人情報保護法対象データ:**
- 利用者個人情報（名前、住所、病歴等）
- 職員個人情報（連絡先、勤務情報等）
- 介護記録・医療情報
- 家族連絡先・緊急連絡先

**データアクセス原則:**
- 最小権限の原則（必要最小限のデータのみアクセス）
- テナント境界の絶対遵守（他施設データ非アクセス）
- ログ記録の徹底（全データアクセスを記録）
- 暗号化の実装（機密データの保護）

---

## 💡 安全性向上のベストプラクティス

### 📝 コードレビュー必須項目

- [ ] RefreshDatabaseトレイト使用なし
- [ ] 危険なマイグレーションコマンド不使用
- [ ] テナント境界チェック実装
- [ ] セキュリティ例外ハンドリング
- [ ] マルチテナント要件遵守

---

**⚠️ 重要な宣言：CommuniCareV2では、いかなる開発効率よりもデータの安全性を優先します。**  
**介護施設で働く職員の皆様と、ケアを受ける利用者様の大切な情報を守ることが、私たちの最優先事項です。**

---

## プロジェクト概要

**CommuniCareV2**は、介護施設向けのマルチテナント対応コミュニケーションプラットフォームです。Laravel 12.xベースで構築され、Vue.js 3.x + Inertia.jsによるモダンなSPA体験を提供します。

### 主要機能
- 📋 フォーラム・掲示板システム（投稿、コメント、引用、いいね機能）
- 👥 職員管理（部署別管理、権限制御）
- 🏠 利用者（入居者）情報管理
- 🏢 マルチテナント対応（事業所ごとの完全データ分離）
- 🔐 ロールベース認証（管理者・一般ユーザー）

## 技術スタック

### バックエンド
- **Laravel**: 12.x (PHP 8.3+)
- **データベース**: MySQL 8.0
- **マルチテナンシー**: stancl/tenancy 3.8+
- **認証**: Laravel Breeze + Spatie Laravel Permission
- **テスト**: PHPUnit + Mockery + Pest

### フロントエンド
- **Vue.js**: 3.4+
- **Inertia.js**: 2.0+ (SPA without API)
- **Tailwind CSS**: 3.2+
- **ビルドツール**: Vite 5.0+
- **追加ライブラリ**: vue-i18n, dayjs, vuedraggable

### 開発環境
- **Docker**: Laravel Sail (開発環境)
- **Node.js**: 20.18.2+
- **Composer**: 2.8.5+

## 重要なアーキテクチャパターン

### 1. マルチテナント設計
```php
// すべてのモデルにtenant_idカラムが必須
// テナント境界違反は例外として処理
if ($post->tenant_id !== $currentUser->tenant_id) {
    throw new TenantViolationException(...);
}
```

### 2. Service Layer パターン
```php
// コントローラーはServiceクラスに処理を委譲
class PostController {
    public function store(PostStoreRequest $request) {
        $post = $this->postService->createPost($request);
        return redirect()->route('forum.index');
    }
}
```

### 3. セキュリティファースト設計
- カスタム例外: `TenantViolationException`, `PostOwnershipException`
- 所有権チェック: `validatePostOwnership()`, `canDeletePost()`
- セキュリティログ: 詳細なコンテキスト情報を記録

## 重要なディレクトリ構造

```
app/
├── Services/               # ビジネスロジック層
│   ├── PostService.php    # 投稿関連処理
│   └── ForumService.php   # フォーラム関連処理
├── Models/                # Eloquentモデル
│   ├── Post.php           # 投稿モデル
│   ├── Comment.php        # コメントモデル
│   ├── User.php           # ユーザーモデル
│   └── Tenant.php         # テナントモデル
├── Http/
│   ├── Controllers/       # コントローラー層
│   ├── Requests/          # バリデーションリクエスト
│   └── Middleware/        # カスタムミドルウェア
└── Exceptions/Custom/     # カスタム例外クラス
    ├── TenantViolationException.php
    └── PostOwnershipException.php

resources/js/
├── Pages/                 # Inertia.jsページコンポーネント
├── Components/            # 再利用可能コンポーネント
└── Utils/                 # ユーティリティ関数

tests/
├── Unit/                  # ユニットテスト（Service層重点）
└── Feature/               # 機能テスト
```

## 開発コマンド

### PHP/Laravel操作
```bash
# ⚠️ 重要: PHP操作はすべてsailコマンドを使用
sail php artisan migrate
sail php artisan db:seed
sail composer install
sail composer update

# テスト実行
sail test
sail artisan test

# キャッシュクリア
sail php artisan cache:clear
sail php artisan config:clear
sail php artisan route:clear
```

### フロントエンド操作
```bash
# 開発サーバー起動
npm run dev

# 本番ビルド
npm run build

# 依存関係インストール
npm install
```

### Docker環境
```bash
# 環境起動
sail up -d

# 環境停止
sail down

# コンテナ再構築
sail build --no-cache
```

## テスト戦略

### 1. ユニットテスト重点領域
- **Serviceクラス**: PostService, ForumService
- **カスタム例外**: セキュリティ例外クラス
- **セキュリティ機能**: テナント分離、権限チェック

### 2. テスト実行例
```bash
# 全テスト実行
sail test

# 特定テストクラス
sail test tests/Unit/PostServiceTest.php

# セキュリティテスト
sail test tests/Unit/SecurityFunctionTest.php
```

## セキュリティの考慮事項

### 1. マルチテナント分離
```php
// 必須: すべてのデータアクセスでテナント境界チェック
if ($resource->tenant_id !== Auth::user()->tenant_id) {
    throw new TenantViolationException(...);
}
```

### 2. 投稿所有権チェック
```php
// 投稿削除・編集時の権限確認
public function canDeletePost(Post $post): bool {
    $user = Auth::user();
    return $post->user_id === $user->id || $user->hasRole('admin');
}
```

### 3. CSRF保護
- すべてのフォーム送信でCSRFトークン必須
- Inertia.jsが自動的にCSRF処理

### 設定ファイル
- `config/tenancy.php` - マルチテナント設定
- `config/permission.php` - 権限管理設定
- `routes/tenant.php` - テナント専用ルート

### コアサービス
- `app/Services/PostService.php` - 投稿関連ビジネスロジック
- `app/Services/ForumService.php` - フォーラム関連ビジネスロジック

### セキュリティ関連
- `app/Exceptions/Custom/TenantViolationException.php`
- `app/Exceptions/Custom/PostOwnershipException.php`

## 開発時の注意点

### 1. マルチテナント対応必須
- 新しい機能追加時は必ずテナント分離を考慮
- `tenant_id`カラムの追加とバリデーション

### 2. Service層の活用
- コントローラーは薄く保つ
- ビジネスロジックはServiceクラスに実装
- Serviceクラスには必ずユニットテストを追加

### 3. セキュリティファースト
- 権限チェックを必ず実装
- セキュリティテストの追加
- ログ記録の充実

### 4. テスト駆動開発
- 新機能追加前にテストケース作成
- セキュリティ機能は特に重点的にテスト

## パフォーマンス最適化

### 1. データベース
- Eager Loading活用 (`with()`)
- N+1問題の回避
- 適切なインデックス設定

### 2. フロントエンド
- コンポーネントの適切な分割
- 画像の最適化とリサイズ
- Viteによる効率的なビルド

---

**重要**: このプロジェクトは介護施設という機密性の高い環境で使用されるため、セキュリティとデータ保護を最優先に開発を進めてください。新機能追加時は必ずテナント分離とセキュリティテストを忘れずに実装してください。