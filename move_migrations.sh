#!/bin/bash

# 移動元と移動先のディレクトリを設定
SRC_DIR="src/database/migrations/tenant"
DEST_DIR="src/database/migrations"

# migrations直下のファイルリストを取得
EXISTING_FILES=$(ls $DEST_DIR)

# tenant内のファイルをループ処理して移動
for file in $(ls $SRC_DIR); do
  if echo "$EXISTING_FILES" | grep -q "$file"; then
    echo "Skipping $file (already exists)"
  else
    echo "Moving $file to $DEST_DIR"
    mv "$SRC_DIR/$file" "$DEST_DIR/"
  fi
done

# tenantディレクトリを削除（空の場合）
rmdir "$SRC_DIR" 2>/dev/null

