#!/usr/bin/env bash
# Lefthook 用: リポジトリルート相対の {staged_files}（例: src/app/Foo.php）を
# src/ 内で正しく解決できるよう "src/" プレフィックスを除去してコマンドを実行する。
# 使用例: ./scripts/run-in-src.sh ./vendor/bin/sail pint {staged_files}
# → src/ を CWD にし、./vendor/bin/sail pint app/Foo.php を実行
set -e
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$REPO_ROOT/src"
args=()
for arg in "$@"; do
  if [[ "$arg" == src/* ]]; then
    args+=( "${arg#src/}" )
  else
    args+=( "$arg" )
  fi
done
exec "${args[@]}"
