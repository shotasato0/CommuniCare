import { router } from "@inertiajs/vue3";

// 掲示板に移動する関数
export const redirectToForum = async (
    units,
    users,
    userUnitId,
    routeName = "forum.index", // 掲示板のルート名
    showDialog // ダイアログを表示するかどうか
) => {
    try { // エラーが発生した場合の処理
        const response = await fetch(`/user-forum-id`, { // ユーザーの掲示板IDを取得
            headers: { // ヘッダー
                Accept: "application/json", // レスポンスをJSON形式で取得
            },
        });
        const data = await response.json(); // レスポンスをJSON形式で取得

        if (!response.ok) { // レスポンスがOKでない場合
            throw new Error(data.error || "Failed to fetch forum ID"); // エラーをスロー
        }

        const forumId = data.forum_id || null; // 掲示板ID

        if (userUnitId) { // ユーザーが所属している単位がある場合
            const unit = units.find((u) => u.id === userUnitId); // ユーザーが所属している単位を取得
            const unitUsers = users.filter( // 単位に所属するユーザーを取得
                (user) => user.unit_id === userUnitId // ユーザーのunit_idがuserUnitIdと一致する場合
            );

            if (unit) { // 単位が存在する場合
                sessionStorage.setItem( // セッションストレージに保存
                    "selectedUnitUsers", // キー
                    JSON.stringify(unitUsers) // 単位に所属するユーザーをJSON形式で保存
                );
                sessionStorage.setItem("selectedUnitName", unit.name); // 単位名をセット
                localStorage.setItem("lastSelectedUnitId", userUnitId); // 最後に選択した単位IDをセット
            }
        } else { // ユーザーが所属している単位がない場合
            console.warn("User does not belong to a unit.");
        }

        if (forumId) { // 掲示板IDがある場合
            router.get( // 掲示板に移動
                route(routeName, { // 掲示板のルート名
                    forum_id: forumId, // 掲示板ID
                    active_unit_id: userUnitId, // ユーザーが所属している単位ID
                }),
                {
                    preserveState: false, // ページの状態を保持しない
                }
            );
        } else { // 掲示板IDがない場合
            console.warn("Forum ID not found. Navigation skipped.");
        }
    } catch (error) { // エラーが発生した場合
        console.error("Error fetching user forum ID:", error);

        // ダイアログを表示
        showDialog({
            message: error.message, // エラーメッセージ
            onConfirm: () => console.log("Retry or any custom logic"), // ダイアログのOKボタンの処理
        });
    }
};
