import { reactive } from "vue"; // vueのreactiveをインポート

// ダイアログの状態を管理するためのリアクティブオブジェクト
const state = reactive({
    isVisible: false,
    message: "",
    resolve: null,
});

// ダイアログを表示するための関数
export function useDialog() {
    const showDialog = (message) => {
        return new Promise((resolve) => {
            state.message = message; // メッセージをセット
            state.isVisible = true; // ダイアログを表示
            state.resolve = resolve; // resolve関数をセット
        });
    };

    // ダイアログのOKボタンを押したときの処理
    const confirm = () => {
        state.isVisible = false; // ダイアログを非表示
        if (state.resolve) state.resolve(true); // resolve関数を呼び出す
    };

    // ダイアログのキャンセルボタンを押したときの処理
    const cancel = () => {
        state.isVisible = false; // ダイアログを非表示
        if (state.resolve) state.resolve(false); // resolve関数を呼び出す
    };

    return { // ダイアログの状態と関数を返す
        state, // ダイアログの状態
        showDialog, // ダイアログを表示する関数
        confirm, // ダイアログのOKボタンを押したときの処理
        cancel, // ダイアログのキャンセルボタンを押したときの処理
    };
}
