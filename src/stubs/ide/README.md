# IDE スタブ（静的解析専用）

Intelephense 等の静的解析で未定義エラーを解消するためのスタブです。
**実装は vendor/ にあり、本ディレクトリは実行時に使用されません。**

| ファイル | スタブ対象 |
|---------|-----------|
| tenant.php | Stancl Tenancy `tenant()` ヘルパー |
| facade-log.php | `Illuminate\Support\Facades\Log` |
| facade-db.php | `Illuminate\Support\Facades\DB` |
| query-builder.php | `Illuminate\Database\Query\Builder` |
| http-request.php | `Illuminate\Http\Request` |
| eloquent-model.php | `Illuminate\Database\Eloquent\Model` |
| eloquent-builder.php | `Illuminate\Database\Eloquent\Builder` |
| auth-user.php | `Illuminate\Foundation\Auth\User` |
| has-factory.php | `Illuminate\Database\Eloquent\Factories\HasFactory` |
| inertia-middleware.php | `Inertia\Middleware` |
| inertia-response.php | `Inertia\Response` |
