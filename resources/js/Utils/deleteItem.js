import axios from "axios";
import { router } from "@inertiajs/vue3";

/**
 * 汎用的な削除ロジック
 * @param {string} type 削除対象の種類 ('post', 'comment', 'user' など)
 * @param {number|string} id 削除対象のID
 * @param {Function} callback 成功時に実行するコールバック関数
 */
export const deleteItem = (type, id, callback) => {
    const isConfirmed = confirm("本当に削除しますか？");

    if (!isConfirmed) {
        return;
    }

    // CSRFトークンを取得
    const token = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

    // 削除用のルートを設定
    const deleteUrl =
        type === "post" ? route('forum.destroy', { id }) : route('comment.destroy', { id });

    // DELETEメソッドでリクエストを送信
    router.delete(deleteUrl, {
        preserveScroll: true,
        onSuccess: () => {
            callback(id);
        },
        onError: (errors) => {
            console.error("削除エラー:", errors);
        }
    });
};
