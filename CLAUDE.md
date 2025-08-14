# CLAUDE.md - CommuniCareV2 開発ガイド

このファイルは、CommuniCareV2プロジェクトで作業する将来のClaude Codeインスタンスのための包括的なガイドです。

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

### 3. 重要なテストファイル
- `tests/Unit/PostServiceTest.php` - 投稿サービステスト
- `tests/Unit/ForumServiceTest.php` - フォーラムサービステスト
- `tests/Unit/SecurityFunctionTest.php` - セキュリティ機能テスト
- `tests/Unit/TenantViolationExceptionTest.php` - テナント例外テスト

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

## Gitワークフロー

### コミットメッセージ規則
```bash
# 日本語コミットメッセージを使用
[add] 新機能追加
[fix] バグ修正
[update] 既存機能の改善
[remove] 不要コード削除

# 例
git commit -m "[add] PostServiceに画像アップロード機能追加"
git commit -m "[fix] テナント境界チェックの不具合修正"
```

### 推奨ワークフロー
1. 機能ごとにブランチ作成 (`feature/function-name`)
2. 小さな変更で頻繁にコミット
3. テスト追加・実行
4. プルリクエスト作成

## 重要なファイル

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

## デバッグ・トラブルシューティング

### 1. ログ確認
```bash
# Laravelログ
sail logs
tail -f storage/logs/laravel.log

# データベースクエリログ
# config/database.phpでlog_queries有効化
```

### 2. よくある問題
- **テナント境界エラー**: `TenantViolationException`
- **権限エラー**: `PostOwnershipException`
- **CSRF エラー**: フォームのCSRFトークン確認

### 3. デバッグツール
- Laravel Debugbar (開発環境のみ)
- `dd()`, `dump()` for debugging
- Vue.js DevTools

## 今後の開発方針

### 現在注力中
- **Service層テストの充実**: 現在`feature/service-layer-tests`ブランチで実装中
- **セキュリティテストの強化**: 攻撃パターン検出テスト
- **マルチテナント分離の完全性**: データリークの防止

### 将来的な機能
- AI統合（コミュニケーション解析、要約機能）
- 通知システム（メール、プッシュ通知）
- ファイル添付機能の拡張
- 画像複数添付・リサイズ機能

## 関連ドキュメント

- [README.md](./README.md) - プロジェクト全体概要
- [Tenancy for Laravel 公式ドキュメント](https://tenancyforlaravel.com/)
- [Laravel 12.x 公式ドキュメント](https://laravel.com/docs/12.x)
- [Vue.js 3.x 公式ドキュメント](https://vuejs.org/)
- [Inertia.js 公式ドキュメント](https://inertiajs.com/)

---

## Git ワークフローとコミット規則

### 自動コミット・プッシュについて
Claude Codeが変更を完了した際は、適切なコミットメッセージで自動的にcommit・pushを実行してください。

### コミットメッセージ作成・実行
変更内容を分析して、以下のフォーマットでコミットメッセージを作成し、自動的にcommit・pushを実行してください：

#### フォーマット
```
[コミット種別]要約

理由の詳細説明
```

#### コミット種別
- **fix**: バグ修正
- **add**: 新規（ファイル）機能追加  
- **update**: 機能修正（バグではない）
- **remove**: 削除（ファイル）

#### 例
```
[fix] 削除フラグが更新されない不具合の修正

refs #110 更新SQLの対象カラムに削除フラグが含まれていなかったため追加しました。
```

### プルリクエスト作成・実行
ブランチ作業完了時は、以下のテンプレートに沿ってPR文章を作成し、自動的にプルリクエストを作成してください：

#### PRテンプレート
- **タイトル**: [ブランチ名に基づく適切なタイトル]
- **目的**: 
- **達成条件**: 
- **実装の概要**: 
- **対処したバグ**: 
- **必要なかった実装**: 採用を検討したが結果的に削除した内容
- **レビューしてほしいところ**: 
- **不安に思っていること**: 
- **保留していること**: 

### 開発フロー
1. 変更内容の実装
2. Claude Codeが適切なコミットメッセージでcommit・push実行
3. ブランチ作業完了時、Claude CodeがPR文章作成してプルリクエスト作成
4. 必要に応じてレビュー対応・追加commit実行

### Git操作の自動化設定
Claude Codeが以下のGit操作を自動実行します：
- `git add` - 変更ファイルのステージング
- `git commit` - 適切なメッセージでのコミット作成
- `git push` - リモートリポジトリへのプッシュ
- `gh pr create` - プルリクエストの作成

---

**重要**: このプロジェクトは介護施設という機密性の高い環境で使用されるため、セキュリティとデータ保護を最優先に開発を進めてください。新機能追加時は必ずテナント分離とセキュリティテストを忘れずに実装してください。