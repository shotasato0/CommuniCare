# 添付ファイル運用（Repair / GC / 一時保存）

## 一時保存→確定保存（フェーズ1）

- 新規アップロードは `public/temp/attachments/...` に保存し、DBコミット後に `public/attachments/...` へ移動します。
- 投稿が失敗・キャンセルされた場合、tempに残ったファイルは GC で削除されます。

## Repair（中央→テナントの補修コピー）

壊れ添付（DBにあるがテナントFSに実体がない）を中央FSからテナントFSへ補修します。

```
# 影響確認（dry-run）
sail php artisan attachments:repair-files --dry-run

# テナント限定
sail php artisan attachments:repair-files --tenant=<tenant_id> --dry-run

# 実行（dry-runで問題なければ）
sail php artisan attachments:repair-files
```

- [FIXED]: 補修コピー成功。以後は表示可能。
- [MISSING]: 実体が無いため再アップロードが必要。

## GC（ガーベジコレクション）

temp 配下の古い一時ファイルを削除します。デフォルトは 1 日以上経過。

```
# 影響確認（dry-run）
sail php artisan attachments:gc --days=1 --dry-run

# 実行
sail php artisan attachments:gc --days=1
```

スケジュール実行：毎日 03:10（routes/console.php 参照）。

## ログ抑制

`.env` で `ATTACHMENTS_DEBUG_LOG=false`（既定）にするとデバッグログを出しません。

## 制約の統一

- バックエンド：Post/Comment の Request で `files`（最大10） と `files.*`（各10MB）を検証。
- フロント：FileUpload で `maxFiles=10`、各10MBに到達前にUIで抑止。

