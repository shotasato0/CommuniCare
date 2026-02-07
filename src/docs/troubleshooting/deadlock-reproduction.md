# デッドロック再現手順

このドキュメントでは、MySQL でデッドロックを意図的に再現し、挙動を観察する方法を説明します。

## 前提条件

-   Laravel Sail 環境が起動していること
-   MySQL コンテナが正常に動作していること

## デッドロックとは

デッドロックは、2 つ以上のトランザクションが互いに相手が保持しているロックを待つ状態で、どちらも進行できなくなる現象です。

**典型的な発生パターン**:

1. トランザクション A が行 1 をロック
2. トランザクション B が行 2 をロック
3. トランザクション A が行 2 をロックしようとする（B が待機）
4. トランザクション B が行 1 をロックしようとする（A が待機）
5. → デッドロック発生

## 再現手順

### 1. テスト用テーブルの作成

まず、テスト用のテーブルとデータを作成します。

**ターミナル 1**で以下を実行:

```bash
# MySQLに接続
./vendor/bin/sail mysql

# テスト用テーブルを作成
CREATE TABLE IF NOT EXISTS deadlock_test (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    value INT NOT NULL,
    INDEX idx_name (name)
) ENGINE=InnoDB;

# テストデータを投入
INSERT INTO deadlock_test (name, value) VALUES
    ('row1', 100),
    ('row2', 200),
    ('row3', 300);

# データ確認
SELECT * FROM deadlock_test;
```

### 2. デッドロックの再現

**2 つのターミナル**を開き、それぞれで MySQL に接続します。

#### ターミナル 1（セッション A）

```bash
./vendor/bin/sail mysql
```

```sql
-- セッションA: トランザクション開始
START TRANSACTION;

-- 行1をロック（FOR UPDATEで排他ロック）
SELECT * FROM deadlock_test WHERE id = 1 FOR UPDATE;

-- ここで一旦停止（ターミナル2の操作を待つ）
-- 数秒待ってから次を実行
```

#### ターミナル 2（セッション B）

```bash
# 別のターミナルでMySQLに接続
./vendor/bin/sail mysql
```

```sql
-- セッションB: トランザクション開始
START TRANSACTION;

-- 行2をロック（セッションAとは異なる行）
SELECT * FROM deadlock_test WHERE id = 2 FOR UPDATE;

-- ここで一旦停止
```

#### デッドロック発生のタイミング

**ターミナル 1（セッション A）**で以下を実行:

```sql
-- セッションA: 行2をロックしようとする（セッションBが既にロック中）
SELECT * FROM deadlock_test WHERE id = 2 FOR UPDATE;
-- ↑ ここでブロック（セッションBのロック解除を待つ）
```

**ターミナル 2（セッション B）**で以下を実行（ターミナル 1 がブロックしている間に）:

```sql
-- セッションB: 行1をロックしようとする（セッションAが既にロック中）
SELECT * FROM deadlock_test WHERE id = 1 FOR UPDATE;
-- ↑ デッドロック発生！MySQLが自動検出してエラーを返す
```

**期待される結果**:

-   ターミナル 2 で以下のようなエラーが表示されます:

```
ERROR 1213 (40001): Deadlock found when trying to get lock; try restarting transaction
```

-   ターミナル 1 のクエリは成功します（デッドロック検出により、セッション B のトランザクションがロールバックされたため）

### 3. デッドロックの詳細確認

デッドロックが発生した際の詳細情報を確認するには、MySQL のデッドロックログを確認します。

```bash
# MySQLコンテナ内でデッドロックログを確認
./vendor/bin/sail exec mysql mysql -uroot -ppassword -e "SHOW ENGINE INNODB STATUS\G" | grep -A 50 "LATEST DETECTED DEADLOCK"
```

または、MySQL 8.0 以降では、`performance_schema`を使用してデッドロック情報を取得できます:

```sql
-- デッドロックイベントの確認
SELECT * FROM performance_schema.events_statements_history_long
WHERE sql_text LIKE '%deadlock_test%'
ORDER BY thread_id, event_id
LIMIT 20;
```

### 4. より複雑なデッドロックパターン

#### パターン 1: 更新操作によるデッドロック

```sql
-- セッションA
START TRANSACTION;
UPDATE deadlock_test SET value = value + 10 WHERE id = 1;
-- ここで一旦停止

-- セッションB（別ターミナル）
START TRANSACTION;
UPDATE deadlock_test SET value = value + 10 WHERE id = 2;
-- ここで一旦停止

-- セッションA
UPDATE deadlock_test SET value = value + 10 WHERE id = 2;
-- ブロック

-- セッションB
UPDATE deadlock_test SET value = value + 10 WHERE id = 1;
-- デッドロック発生
```

#### パターン 2: インデックス順序によるデッドロック

```sql
-- セッションA
START TRANSACTION;
SELECT * FROM deadlock_test WHERE name = 'row1' FOR UPDATE;
-- 一旦停止

-- セッションB
START TRANSACTION;
SELECT * FROM deadlock_test WHERE name = 'row2' FOR UPDATE;
-- 一旦停止

-- セッションA
SELECT * FROM deadlock_test WHERE name = 'row2' FOR UPDATE;
-- ブロック

-- セッションB
SELECT * FROM deadlock_test WHERE name = 'row1' FOR UPDATE;
-- デッドロック発生
```

## デッドロックの観察ポイント

### 1. ロックの種類

-   **共有ロック（S Lock）**: `SELECT ... LOCK IN SHARE MODE`
-   **排他ロック（X Lock）**: `SELECT ... FOR UPDATE` または `UPDATE`/`DELETE`

### 2. ロックのスコープ

-   **行ロック**: 特定の行のみをロック
-   **ギャップロック**: インデックスレコード間のギャップをロック
-   **ネクストキーロック**: 行ロック + ギャップロック

### 3. デッドロック検出の動作

MySQL はデッドロックを検出すると:

1. 一方のトランザクションを自動的にロールバック
2. エラー `1213` を返す
3. もう一方のトランザクションは継続可能

### 4. ロック待機のタイムアウト

```sql
-- ロック待機タイムアウトの設定確認
SHOW VARIABLES LIKE 'innodb_lock_wait_timeout';
-- デフォルト: 50秒

-- タイムアウト設定（セッション単位）
SET innodb_lock_wait_timeout = 10;
```

## 実際のアプリケーションでの対策

### 1. ロック取得順序の統一

```php
// ❌ 危険: 異なる順序でロックを取得
// トランザクションA: 行1 → 行2
// トランザクションB: 行2 → 行1

// ✅ 安全: 常に同じ順序でロックを取得
// すべてのトランザクション: 行1 → 行2（ID順など）
```

### 2. トランザクションの短縮

```php
// ❌ 危険: 長時間トランザクション
DB::transaction(function () {
    $data = fetchData(); // 時間がかかる処理
    processData($data);
    saveData($data);
});

// ✅ 安全: トランザクションを短く
$data = fetchData(); // トランザクション外
DB::transaction(function () use ($data) {
    processData($data);
    saveData($data);
});
```

### 3. デッドロックエラーのハンドリング

```php
try {
    DB::transaction(function () {
        // データベース操作
    });
} catch (\Illuminate\Database\QueryException $e) {
    // デッドロックエラー（1213）の場合、リトライ
    if ($e->getCode() == 1213) {
        // リトライロジック
        return retryTransaction();
    }
    throw $e;
}
```

### 4. 適切なインデックスの使用

```sql
-- インデックスがない場合、テーブルロックが発生しやすい
-- 適切なインデックスを設定することで、行ロックに限定できる
CREATE INDEX idx_tenant_id ON posts(tenant_id);
```

## クリーンアップ

テストが終わったら、テスト用テーブルを削除:

```sql
DROP TABLE IF EXISTS deadlock_test;
```

## 参考資料

-   [MySQL 公式ドキュメント: InnoDB デッドロック](https://dev.mysql.com/doc/refman/8.0/en/innodb-deadlocks.html)
-   [MySQL 公式ドキュメント: InnoDB ロック](https://dev.mysql.com/doc/refman/8.0/en/innodb-locking.html)
