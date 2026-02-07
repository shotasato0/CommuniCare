# CommuniCareV2 - Cursor Rules

このファイルは、CommuniCareV2プロジェクトの構造・技術スタック・コーディングスタイル・開発ルールを定義します。
AIエージェントがプロジェクト固有のコンテキストを理解し、適切なコード生成を行うための包括的な指針です。

---

## 🚨 【最重要】テスト環境安全性ルール

### ⚠️ 介護施設データ保護の絶対原則

**CommuniCareV2は複数の介護施設の機密データを扱うマルチテナントシステムです。**  
**1つのミスが全介護施設の利用者情報・職員データ・介護記録に影響する可能性があります。**

### 📊 システム重要度レベル
- **データの機密性**: 最高（個人情報保護法対象）
- **システム影響範囲**: 全介護施設（マルチテナント）
- **データベース方式**: シングルDB + tenant_idによる論理分離
- **1つの操作の影響**: 全テナント・全利用者・全職員データ

### ❌ 絶対禁止操作（違反は重大事故に直結）

**完全禁止コマンド**:
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

**危険なテストトレイト**:
```php
// 🚫 RefreshDatabase使用禁止（開発DB破壊リスク）
use Illuminate\Foundation\Testing\RefreshDatabase; // ← 絶対禁止
```

**危険なデータベース操作**:
```php
// 🚫 無条件truncate（全テナントデータ削除）
DB::table('users')->truncate();
DB::table('posts')->truncate();

// 🚫 DROP文実行
DB::statement('DROP TABLE users');
```

### 🛡️ 必須セキュリティチェック

**開発作業開始前の確認（毎回実行）**:
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

**テスト実行前の安全確認**:
```bash
# テスト環境の安全性確認
APP_ENV=testing sail test tests/Security/DangerousOperationTest.php

# セーフティネット動作確認
APP_ENV=testing sail test tests/Unit/PostServiceTest.php
```

### 📚 過去の重大事故事例

**🚨 【事故報告】2024年8月 パフォーマンステスト事故**

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

**⚠️ 重要な宣言：CommuniCareV2では、いかなる開発効率よりもデータの安全性を優先します。**  
**介護施設で働く職員の皆様と、ケアを受ける利用者様の大切な情報を守ることが、私たちの最優先事項です。**

---

## プロジェクト概要

**CommuniCareV2**は、介護施設向けのマルチテナント対応コミュニケーションプラットフォームです。
個人情報保護法対象の機密データを扱うため、セキュリティとデータ保護を最優先に設計されています。

### 主要機能
- 📋 フォーラム・掲示板システム（投稿、コメント、引用、いいね機能）
- 👥 職員管理（部署別管理、権限制御）
- 🏠 利用者（入居者）情報管理
- 🏢 マルチテナント対応（事業所ごとの完全データ分離）
- 🔐 ロールベース認証（管理者・一般ユーザー）

---

## 技術スタック

### バックエンド
- **Laravel**: 12.x (PHP 8.3+)
- **データベース**: MySQL 8.0 (本番), SQLite :memory: (テスト)
- **マルチテナンシー**: stancl/tenancy 3.8+ (single DB + tenant_id 論理分離)
- **認証**: Laravel Breeze + Spatie Laravel Permission 6.9+
- **テスト**: Pest 3.0 + PHPUnit + Mockery
- **コードスタイル**: Laravel Pint (PSR-12準拠)

### フロントエンド
- **Vue.js**: 3.4+ (Composition API推奨)
- **Inertia.js**: 2.0+ (SPA without API)
- **Tailwind CSS**: 3.2+ (ユーティリティファースト)
- **ビルドツール**: Vite 5.0+
- **追加ライブラリ**:
  - vue-i18n: 国際化
  - dayjs: 日付操作
  - vuedraggable: ドラッグ&ドロップ
  - bootstrap-icons: アイコン

### 開発環境
- **Docker**: Laravel Sail
- **Node.js**: 20.18.2+
- **Composer**: 2.8.5+
- **Git Hooks**: Lefthook (pre-commit: Pint, PHPStan, ESLint)

---

## プロジェクト構造

### ディレクトリ構成

```
app/
├── Console/Commands/          # Artisanコマンド
├── Exceptions/Custom/        # カスタム例外（TenantViolationException等）
├── Http/
│   ├── Controllers/          # コントローラー（薄く保つ）
│   │   ├── Admin/            # 管理者専用コントローラー
│   │   ├── Api/              # APIコントローラー
│   │   └── Auth/              # 認証関連コントローラー
│   ├── Middleware/           # カスタムミドルウェア
│   └── Requests/             # フォームリクエスト（バリデーション）
│       ├── Admin/
│       ├── Auth/
│       ├── Comment/
│       ├── Post/
│       ├── Resident/
│       └── Unit/
├── Listeners/                # イベントリスナー
├── Models/                   # Eloquentモデル
├── Providers/               # サービスプロバイダー
├── Services/                # ビジネスロジック層（Service Layer）
└── Traits/                   # 再利用可能トレイト
    ├── SecurityValidationTrait.php
    └── TenantBoundaryCheckTrait.php

resources/js/
├── Components/              # 再利用可能Vueコンポーネント
├── Layouts/                 # レイアウトコンポーネント
├── Pages/                   # Inertia.jsページコンポーネント
└── Utils/                   # ユーティリティ関数

tests/
├── Feature/                 # 機能テスト
├── Unit/                    # ユニットテスト（Service層重点）
├── Security/                # セキュリティテスト
└── TestCase.php             # ベーステストクラス（セーフティネット内蔵）

docs/
└── codex/                   # 機能別仕様書（00_overview.md等）
```

---

## アーキテクチャパターン

### 1. マルチテナント設計（最重要）

**原則**: すべてのテーブルに `tenant_id` カラムが必須。テナント間のデータ分離を厳守。

```php
// ✅ 正しい実装
class Post extends Model
{
    protected $fillable = ['user_id', 'title', 'message', 'tenant_id'];
    
    // クエリ時は必ずtenant_idでフィルタリング
    public function scopeForCurrentTenant($query)
    {
        return $query->where('tenant_id', Auth::user()->tenant_id);
    }
}

// ❌ 禁止: tenant_idフィルタなしの全件取得
Post::all(); // 絶対禁止
Post::query()->get(); // 絶対禁止

// ✅ 正しい: tenant_idでフィルタリング
Post::where('tenant_id', Auth::user()->tenant_id)->get();
```

**テナント境界チェック**:
- Service層: `TenantBoundaryCheckTrait` を使用
- Policy: `checkTenantBoundary()` メソッドで検証
- 例外: `TenantViolationException` をスロー

**介護データの機密性要件**:
- 利用者個人情報（名前、住所、病歴等）
- 職員個人情報（連絡先、勤務情報等）
- 介護記録・医療情報
- 家族連絡先・緊急連絡先

**データアクセス原則**:
- 最小権限の原則（必要最小限のデータのみアクセス）
- テナント境界の絶対遵守（他施設データ非アクセス）
- ログ記録の徹底（全データアクセスを記録）
- 暗号化の実装（機密データの保護）

### 2. Service Layer パターン

**原則**: コントローラーは薄く保ち、ビジネスロジックはServiceクラスに集約。

```php
// ✅ 正しい実装
class PostController extends Controller
{
    public function __construct(
        private PostService $postService
    ) {}
    
    public function store(PostStoreRequest $request)
    {
        $post = $this->postService->createPost($request);
        return redirect()->route('forum.index');
    }
}

class PostService
{
    use TenantBoundaryCheckTrait;
    
    public function createPost(PostStoreRequest $request): Post
    {
        // ビジネスロジックはここに実装
        // テナント境界チェックもここで実施
    }
}
```

### 3. セキュリティファースト設計

**カスタム例外**:
- `TenantViolationException`: テナント境界違反
- `PostOwnershipException`: 所有権違反

**所有権チェック**:
```php
// Service層での実装例
private function validatePostOwnership(Post $post): void
{
    $currentUser = Auth::user();
    
    // テナント境界チェック（必須）
    if ($post->tenant_id !== $currentUser->tenant_id) {
        throw new TenantViolationException(...);
    }
    
    // 所有権チェック
    if ($post->user_id !== $currentUser->id && !$currentUser->hasRole('admin')) {
        throw new PostOwnershipException(...);
    }
}
```

---

## コーディングスタイル

### PHP (Laravel)

**コードフォーマット**:
- Laravel Pint (PSR-12準拠) を使用
- コミット前に自動フォーマット（Lefthook）

**命名規則**:
```php
// クラス名: PascalCase
class PostService {}

// メソッド名: camelCase
public function createPost() {}

// 定数: UPPER_SNAKE_CASE
const MAX_FILE_SIZE = 4096;

// プライベートプロパティ: camelCase
private $postService;
```

**型ヒント**:
```php
// ✅ 型ヒントを明示
public function createPost(PostStoreRequest $request): Post
{
    // ...
}

// ✅ 戻り値の型も明示
public function getPosts(): Collection
{
    // ...
}
```

**コメント**:
```php
/**
 * 投稿を作成する
 * 
 * @param PostStoreRequest $request
 * @return Post
 * @throws TenantViolationException
 */
public function createPost(PostStoreRequest $request): Post
{
    // ...
}
```

### Vue.js / JavaScript

**コンポーネント構造**:
```vue
<template>
  <!-- テンプレート -->
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

// Composition APIを使用
const props = defineProps({
  // props定義
})

const emit = defineEmits(['event-name'])

// リアクティブな状態
const isLoading = ref(false)

// 計算プロパティ
const computedValue = computed(() => {
  // ...
})
</script>

<style scoped>
/* Tailwind CSSクラスを優先 */
</style>
```

**命名規則**:
- コンポーネント: PascalCase (`PostForm.vue`)
- 変数・関数: camelCase (`isLoading`, `handleSubmit`)
- 定数: UPPER_SNAKE_CASE (`MAX_FILE_SIZE`)

**Inertia.js使用時**:
```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  title: '',
  message: '',
})

const submit = () => {
  form.post(route('forum.store'), {
    onSuccess: () => {
      // 成功時の処理
    },
  })
}
</script>
```

---

## データベース設計

### マイグレーション

**命名規則**:
- テーブル作成: `create_{table_name}_table`
- カラム追加: `add_{column_name}_to_{table_name}`
- カラム変更: `modify_{column_name}_in_{table_name}`

**必須カラム**:
```php
// すべてのテーブルに必須
$table->string('tenant_id')->after('id')->index();
$table->timestamps();

// 外部キー制約
$table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
```

**インデックス**:
```php
// tenant_idには必ずインデックス
$table->index('tenant_id');

// 複合インデックス（テナント内での一意性保証）
$table->unique(['tenant_id', 'date']);
```

### モデル

**必須実装**:
```php
class Post extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'tenant_id', // 必須
    ];
    
    // リレーション定義
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // スコープ（テナント境界）
    public function scopeForCurrentTenant($query)
    {
        return $query->where('tenant_id', Auth::user()->tenant_id);
    }
}
```

---

## テスト設計

### テスト構造

**FeatureTest**: APIエンドポイントの動作確認
**UnitTest**: Service層のビジネスロジック検証
**SecurityTest**: テナント境界・権限チェック

**テスト環境**:
- `APP_ENV=testing`
- `DB_CONNECTION=sqlite`
- `DB_DATABASE=:memory:`

**禁止事項**:
- `RefreshDatabase` トレイトの使用
- `migrate:fresh` 等の破壊的コマンド

**テスト例**:
```php
<?php

namespace Tests\Unit;

use Tests\TestCase; // ← 必須：セキュリティ機構内蔵

class PostServiceTest extends TestCase
{
    // RefreshDatabaseは使用しない
    // TestCase.phpのセーフティネットが自動適用
    
    protected function setUp(): void
    {
        parent::setUp(); // ← 必須：3段階セキュリティチェック実行
        
        // 安全なテストデータ作成
        $this->runSafeMigrations();
        $this->createTestData();
    }
    
    protected function createSafeTestData(): void
    {
        // ファクトリを使用した安全なデータ生成
        // truncate()は使用しない
    }
    
    public function test_can_create_post_for_current_tenant(): void
    {
        // テスト実装
    }
    
    public function test_cannot_create_post_with_invalid_tenant(): void
    {
        // テナント境界違反のテスト
    }
}
```

---

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

---

## AIエージェント実行プロセス

### 実行フロー

**入力**:
```
<指示> {{instructions}} </指示>
```

**タスク分析**:
- 主要タスクを簡潔に要約
- 重要要件・制約（機密性、権限、テナント境界、既存仕様）を列挙
- 潜在的な課題とリスクを挙げる
- 実行ステップを詳細化し、最適順序を決める
- 必要ツール／リソース（対象ファイル、テスト、環境）を明示

**実行**:
- ステップを一つずつ実施し、各ステップ完了時に簡潔な進捗を記録
- 不明点や前提不足がある場合は即時報告し、代替案を提示（独断実装は禁止）

**品質管理**:
- 生成物を即時検証する（構文、型、既存テストとの整合）
- エラーや不整合を検知したら直ちに修正
- コマンド実行時は標準出力を確認し、結果を要約

**最終確認**:
- 当初指示との整合をチェックし、必要に応じて微調整
- 変更差分（diff）を提示し、テスト結果を添付

**結果報告（固定フォーマット）**:
```markdown
# 実行結果報告

## 概要
…（全体要約）

## 実行ステップ
1. …（ステップと結果）
2. …

## 最終成果物
- 変更ファイル一覧
- 主要 diff（抜粋）
- 関連テスト結果（抜粋）

## 注意点・改善提案
- …
```

### セーフティ・制約

**絶対禁止（生成・提案ともに不可）**:
- 破壊的コマンド: `migrate:fresh`, `migrate:reset`, `migrate:rollback`, `db:wipe`
- 危険操作: `truncate`, `DROP TABLE`, `ALTER TABLE ... DROP COLUMN`
- テストトレイト: `RefreshDatabase`
- 全テナント読み取り: `Model::all()`, `Model::query()->get()`（tenant_id フィルタなし）

**常時必須**:
- すべての取得・更新・削除で `tenant_id` を必須とする
- 権限制御（Spatie Permission）とポリシーを考慮する
- プロジェクトの仕様ドキュメントと矛盾する生成は行わない
- テストは SQLite(:memory:) 前提、TestCase 継承、セーフティネット有効

**緊急停止条件（検知したら即中断し報告）**:
- 破壊的マイグレーション／危険操作の混入
- `tenant_id` 欠落もしくは越境アクセス
- 仕様ドキュメントに未定義の勝手な拡張・スキーマ変更
- `RefreshDatabase` の使用提案

### 出力・差分提示ルール

- 変更は最小差分で、意味的整合性を担保する
- 大規模変更が不可避な場合、理由・影響範囲・後方互換性を明記する
- 生成後は必ず diff を提示し、関連テストの要旨を添える

---

## Git運用

### 自動Git操作の許可とセーフガード

**許可された自動操作（要確認不要）**:
- 対象ブランチ: `feature/_`, `fix/_`, `chore/_`, `docs/_`
- 操作: `git add`, diff 提示, PR 作成（ドラフト推奨）

**コミット実行前の確認**:
- `git commit` および `git push` を実行する前に、必ずユーザーに確認を求める
- 変更を完了したら、コミットメッセージを提案し、ユーザーの承認を得てから実行する

**禁止（明示許可がない限り不可）**:
- `main`, `develop`, `release/*` への push/rebase
- ユーザー確認なしでの `git commit`/`git push` の実行

**マージ時の PR 作成必須**:
- `main`, `develop`, `release/*` へのマージを行う場合は、必ず事前に Pull Request を作成し、レビューを経てからマージする
- 直接マージ（`git merge` を main に直接実行）は禁止

**例外**: 緊急のホットフィックスなど、明示的な許可がある場合のみ直接マージを許可する

### Git commit 指針

**コミットメッセージテンプレート**:
```
{種類}: {要約}

{理由・詳細説明}
```

**コミット種別**:
- `fix`: バグ修正
- `add`: 新規（ファイル）機能追加
- `update`: 機能修正（バグではない）
- `remove`: 削除（ファイル）
- `refactor`: リファクタリング
- `docs`: ドキュメント

**コミット粒度の方針**:
1. **1つのコミット = 1つの論理的な変更**
   - 複数の機能や修正を1つのコミットにまとめない
   - 関連する変更でも、明確に分離できる場合は別々のコミットにする

2. **小さな変更でも積極的にコミット**
   - ファイル1つの変更でも、意味のある変更であればコミットする
   - 「まだ完成していない」という理由でコミットを遅らせない

3. **機能追加とバグ修正は分離**
   - 新機能の追加とバグ修正が混在している場合は、別々のコミットにする

4. **リファクタリングは独立したコミット**
   - 機能追加やバグ修正と同時に行ったリファクタリングは、別のコミットにする

5. **テスト追加も独立したコミット**
   - テストコードの追加は、実装コードとは別のコミットにすることを検討

### Pull Request 指針

詳細は `.cursor/rules/pull-request-rules.md` を参照してください。

**作成前の事前チェック（必須）**:
```bash
# リモートとの差分を取得
git fetch

# main との差分コミットを確認
git log origin/main..HEAD --oneline

# 差分ファイル・行数を確認
git diff origin/main..HEAD --stat

# クリティカルな修正を確認
git diff origin/main..HEAD
```

**注意**: これらの事前チェック結果は PR 本文には含めない。事前チェックは PR 作成前の確認作業としてのみ実行。

---

## 開発フロー

### 新機能開発時の手順

1. **環境確認** - 現在の作業環境を確認
2. **ブランチ作成** - `feature/機能名`でブランチ作成
3. **セキュリティ設計** - マルチテナント要件の考慮
4. **段階的実装** - 小さな単位での開発とテスト
5. **安全テスト** - TestCase.phpベースのテスト作成
6. **統合確認** - セキュリティ機構との整合確認

### コードレビュー必須項目

- [ ] RefreshDatabaseトレイト使用なし
- [ ] 危険なマイグレーションコマンド不使用
- [ ] テナント境界チェック実装
- [ ] セキュリティ例外ハンドリング
- [ ] マルチテナント要件遵守

### 緊急時の対応方法

**データ消失事故発生時**:
1. **即座停止** - 全開発作業を停止
2. **影響範囲確認** - どのテナント・データが影響を受けたか
3. **バックアップ復旧** - 最新バックアップからの復旧実行
4. **原因調査** - 事故原因の徹底調査
5. **再発防止** - セキュリティ機構の強化
6. **報告書作成** - 事故報告書の作成と共有

---

## ライブラリ追加条件

### 追加前に確認すべき事項

1. **セキュリティ**: マルチテナント環境での安全性
2. **パフォーマンス**: N+1問題やクエリ最適化への影響
3. **依存関係**: Laravel 12.x / Vue 3.x との互換性
4. **メンテナンス**: アクティブな開発が継続されているか

### 追加手順

1. `composer.json` または `package.json` に追加
2. セキュリティテストを追加
3. ドキュメント更新（必要に応じて）
4. コードレビューで承認

---

## ルーティング規則

### テナントルート (`routes/tenant.php`)

**パターン**:
```php
// 認証必須
Route::middleware(['auth'])->group(function () {
    Route::get('/resource', [Controller::class, 'index'])->name('resource.index');
    Route::post('/resource', [Controller::class, 'store'])->name('resource.store');
    Route::put('/resource/{id}', [Controller::class, 'update'])->name('resource.update');
    Route::delete('/resource/{id}', [Controller::class, 'destroy'])->name('resource.destroy');
});
```

**命名規則**:
- リソース名: 単数形 (`post`, `comment`)
- アクション: RESTful (`index`, `store`, `update`, `destroy`)

---

## エラーハンドリング

### カスタム例外

```php
// TenantViolationException
throw new TenantViolationException(
    currentTenantId: (string) $currentUser->tenant_id,
    resourceTenantId: (string) $resource->tenant_id,
    resourceType: get_class($resource),
    resourceId: (int) $resource->id,
    message: "他のテナントのリソースにアクセスしようとしました。"
);
```

### HTTPステータスコード

- `200 OK`: 成功
- `201 Created`: 作成成功
- `403 Forbidden`: 権限不足・テナント境界違反
- `404 Not Found`: リソース不存在
- `409 Conflict`: 重複・競合
- `422 Unprocessable Entity`: バリデーションエラー
- `500 Internal Server Error`: サーバーエラー

---

## ドキュメント規則

### 仕様書 (`docs/codex/`)

機能ごとに以下のファイルを配置:
- `00_overview.md`: 概要・目的・ER図
- `01_schema.md`: テーブル仕様
- `02_api_spec.md`: API仕様
- `03_services.md`: サービス層の責務
- `04_policies_and_permissions.md`: 権限設計
- `05_testing_and_safety.md`: テスト方針
- `06_rollout_plan.md`: 段階導入計画

### トラブルシューティングドキュメント作成ガイドライン

**作成判断基準**:
以下の条件を満たす場合、トラブルシューティングドキュメントを作成することを推奨します：
- 解決に15分以上かかった問題
- 複数のステップが必要な解決手順
- 再発する可能性が高い問題（環境固有の問題、設定ミスなど）
- チーム全体で共有すべき情報

**ドキュメント配置場所**:
トラブルシューティングドキュメントは `docs/troubleshooting/` ディレクトリに配置します。

**ファイル命名規則**: `{エラーコードまたは問題名}-{簡潔な説明}.md`

**ドキュメントテンプレート**:
```markdown
# {エラー名または問題名} 解決手順

## 問題の原因
{問題の原因を簡潔に説明}

## 解決手順

### 1. {最初のステップ}
{手順の説明}

```bash
{実行コマンド}
```

**期待される結果**: {期待される結果}

### 2. {次のステップ}
{手順の説明}

## 追加のトラブルシューティング
{追加の対処法があれば記載}

## 確認コマンド一覧
```bash
# 確認用コマンドを列挙
```

## 参考情報
- {関連ドキュメントへのリンク}
```

---

## 不明点・判断事項の扱い

- 不明点がある場合、作業開始前に明確化する
- 重要判断は逐次報告し、承認を得る
- 予期せぬ問題が生じた場合、即時報告し代替案を提示する
- 独断の仕様追加・既存仕様の逸脱は禁止

---

## Done クリテリア（各タスク共通）

- プロジェクトの仕様ドキュメントとの整合が取れている
- 安全制約（テナント境界・権限・禁止操作）を満たす
- テストが通る（新規・影響範囲の回帰を含む）
- 変更差分が最小で、レビュー観点が記述されている
- コミット／PR が規範に従っている

---

## 重要な注意事項

1. **マルチテナント**: すべてのデータアクセスで `tenant_id` を必須とする
2. **セキュリティ**: 個人情報保護法対象データを扱うため、セキュリティを最優先
3. **テスト**: `RefreshDatabase` は使用禁止。SQLiteメモリDBを使用
4. **コード品質**: Laravel Pint + PHPStan + ESLint で自動チェック
5. **仕様書**: `docs/codex/*` を「正」として参照し、矛盾する実装は禁止

---

## 関連ドキュメント

- `.cursor/rules/pull-request-rules.md`: プルリクエストルール
- `docs/codex/*`: 機能別仕様書
