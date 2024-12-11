import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";
import axios from "axios";

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

    // 削除用のルートを設定
    const route = type === 'post' 
        ? `/forum/post/${id}`
        : `/forum/comment/${id}`;

    // DELETEメソッドでリクエストを送信
    axios.delete(route)
        .then(response => {
            if (response.status === 200) {
                callback(id);
            }
        })
        .catch(error => {
            console.error('削除エラー:', error);
        });
};
