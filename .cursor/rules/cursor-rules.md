# CommuniCareV2 - Cursor Rules

このファイルは、CommuniCareV2プロジェクトの構造・技術スタック・コーディングスタイルを定義します。
AIエージェントがプロジェクト固有のコンテキストを理解し、適切なコード生成を行うための指針です。

**注意**: 実行プロセスやGit運用などの詳細は `rules_for_ai.md` を参照してください。

---

## プロジェクト概要

**CommuniCareV2**は、介護施設向けのマルチテナント対応コミュニケーションプラットフォームです。
個人情報保護法対象の機密データを扱うため、セキュリティとデータ保護を最優先に設計されています。

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
class PostServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->runSafeMigrations();
        $this->createTestData();
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

---

## 関連ドキュメント

- `rules_for_ai.md`: AIエージェントの実行プロセス・Git運用・PR指針
- `AGENTS.md`: 開発ガイド・セーフティルール
- `CLAUDE.md`: プロジェクト概要・技術スタック詳細

---

## 重要な注意事項

1. **マルチテナント**: すべてのデータアクセスで `tenant_id` を必須とする
2. **セキュリティ**: 個人情報保護法対象データを扱うため、セキュリティを最優先
3. **テスト**: `RefreshDatabase` は使用禁止。SQLiteメモリDBを使用
4. **コード品質**: Laravel Pint + PHPStan + ESLint で自動チェック
5. **仕様書**: `docs/codex/*` を「正」として参照し、矛盾する実装は禁止
