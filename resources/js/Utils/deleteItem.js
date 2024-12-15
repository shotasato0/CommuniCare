import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";

/**
 * 汎用的な削除ロジック
 * @param {string} type 削除対象の種類 ('post', 'comment', 'user' など)
 * @param {number|string} id 削除対象のID
 * @param {Function} onSuccessCallback 成功時に実行するコールバック関数
 */
export const deleteItem = (type, id, onSuccessCallback) => {
    const confirmMessage =
        type === "post"
            ? "本当に投稿を削除しますか？"
            : type === "comment"
            ? "本当にコメントを削除しますか？"
            : "本当に社員を削除しますか？";

    if (confirm(confirmMessage)) {
        const routeName =
            type === "post"
                ? "forum.destroy"
                : type === "comment"
                ? "comment.destroy"
                : "users.destroy";

        // スクロール位置を保存
        const scrollPosition = window.scrollY;

        router.delete(route(routeName, id), {
            headers: {
                "X-CSRF-TOKEN": getCsrfToken(),
            },
            preserveScroll: true, // スクロール位置を保持
            onSuccess: () => {
                if (onSuccessCallback) {
                    onSuccessCallback(id);
                }
                // 必要に応じて明示的にスクロール位置を復元
                setTimeout(() => {
                    window.scrollTo({
                        top: scrollPosition,
                        behavior: "instant",
                    });
                }, 100);
            },
            onError: (errors) => {
                console.error("削除に失敗しました:", errors);
            },
        });
    }
};
