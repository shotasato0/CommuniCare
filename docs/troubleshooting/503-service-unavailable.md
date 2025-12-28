# 503 Service Unavailable エラー解決手順

## 問題の原因

ログファイル（`storage/logs/laravel.log`）を確認したところ、以下のMySQL接続エラーが発生しています：

```
PDOException: php_network_getaddresses: getaddrinfo for mysql failed: nodename nor servname provided, or not known
```

アプリケーションがMySQLコンテナに接続できないため、503エラーが発生しています。

## 解決手順

### 1. Dockerコンテナの状態確認

```bash
# Dockerコンテナの状態を確認
docker ps -a

# または、Sailコマンドを使用（推奨）
./vendor/bin/sail ps
```

**期待される状態**: 以下のコンテナがすべて `Up` 状態であること
- `communiv2-laravel.test-1`
- `communiv2-mysql-1`
- `communiv2-redis-1`
- `communiv2-meilisearch-1`
- `communiv2-mailpit-1`
- `communiv2-selenium-1`

### 2. Dockerコンテナの起動

コンテナが停止している場合、以下のコマンドで起動：

```bash
# Sailを使用してコンテナを起動
./vendor/bin/sail up -d

# または、docker-composeを使用
docker-compose up -d
```

### 3. MySQLコンテナのログ確認

MySQLコンテナが正常に起動しているか確認：

```bash
# MySQLコンテナのログを確認
./vendor/bin/sail logs mysql

# または
docker logs communiv2-mysql-1
```

**確認ポイント**:
- `ready for connections` というメッセージが表示されているか
- エラーメッセージが表示されていないか

### 4. データベース接続設定の確認

`.env`ファイルのデータベース設定を確認：

```bash
# .envファイルのDB設定を確認
cat .env | grep DB_
```

**正しい設定例**（Laravel Sail環境）:
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

**注意**: `DB_HOST`は`mysql`（コンテナ名）である必要があります。`127.0.0.1`や`localhost`では動作しません。

### 5. データベース接続テスト

```bash
# データベース接続をテスト
./vendor/bin/sail mysql -e "SELECT 1"

# または、Laravelのtinkerを使用
./vendor/bin/sail artisan tinker
# tinker内で以下を実行:
DB::connection()->getPdo();
```

### 6. キャッシュのクリア

設定を変更した場合は、キャッシュをクリア：

```bash
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
```

### 7. コンテナの再起動

設定を変更した場合は、コンテナを再起動：

```bash
./vendor/bin/sail restart
```

## 追加のトラブルシューティング

### ポート9000が既に使用されている場合

```bash
# ポート9000を使用しているプロセスを確認
lsof -i :9000

# 必要に応じて、.envファイルでAPP_PORTを変更
# APP_PORT=9001
```

### Dockerネットワークの問題

```bash
# Dockerネットワークを確認
docker network ls
docker network inspect communiv2_sail

# コンテナ間の通信をテスト
./vendor/bin/sail exec laravel.test ping mysql
```

### MySQLコンテナが起動しない場合

```bash
# MySQLコンテナのログを詳細に確認
./vendor/bin/sail logs mysql --tail=100

# MySQLコンテナを再構築
./vendor/bin/sail down mysql
./vendor/bin/sail up -d mysql
```

## 確認コマンド一覧

```bash
# 1. コンテナ状態確認
./vendor/bin/sail ps

# 2. アプリケーションログ確認
tail -f storage/logs/laravel.log

# 3. MySQLログ確認
./vendor/bin/sail logs mysql

# 4. データベース接続テスト
./vendor/bin/sail mysql -e "SELECT 1"

# 5. アプリケーションのヘルスチェック
curl http://localhost:9000/up
```

## 参考情報

- Laravel Sail公式ドキュメント: https://laravel.com/docs/sail
- Docker Compose公式ドキュメント: https://docs.docker.com/compose/

