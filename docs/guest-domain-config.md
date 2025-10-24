# ゲストドメイン設定の環境変数化について

本ドキュメントは、ゲストドメイン設定をコード直書きから `.env` に移行する背景と運用手順をまとめたものです。

## 背景（不具合）
- 本番環境で管理者がユーザーを削除できない事象があり、設定値の整合性（ゲストドメイン）が環境ごとに食い違う可能性がありました。
- 環境依存値を `config/guest.php` に直書きすると、デプロイのたびに上書きや差分が発生し、挙動の不一致や混乱の原因になります。

## 対応方針
- 12factor/Laravelの原則に従い、環境依存値は `.env` で切り替えます。
- `config/guest.php` は汎用デフォルト値のみを保持し、実値は `GUEST_DOMAIN_*` 環境変数から解決します。

## 必要な環境変数
- 本番: `.env.production`

```
GUEST_DOMAIN_PRODUCTION=guestdemo.communi-care.jp
```

必要に応じて以下も利用可能です:

```
GUEST_DOMAIN_LOCAL=guestdemo.localhost
GUEST_DOMAIN_STAGING=guestdemo.staging.example.com
GUEST_PROTOCOL=https   # 省略時: localはhttp、それ以外はhttps
```

## 反映手順
1. `.env.production` を更新
2. 設定キャッシュを再生成

```
php artisan optimize:clear
php artisan config:cache
```

## 期待効果
- デプロイ時の差分がなくなり、環境ごとの設定の一貫性を維持
- 本番・ステージング・ローカルで安全にドメインを切替可能
- GitHub上のコードと本番サーバの状態が乖離しにくく、保守性が向上

