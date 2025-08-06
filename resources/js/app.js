import "./bootstrap";
import "../css/app.css";
import "bootstrap-icons/font/bootstrap-icons.css";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import { createI18n } from "vue-i18n"; // vue-i18nをインポート
import ja from "../../lang/ja.json"; // Laravelのlangディレクトリからja.jsonを読み込み

import SlideUpDown from "vue-slide-up-down"; // vue-slide-up-downをインポート
import { useDialog } from "./composables/dialog"; // dialog.jsのインポート
import { initTheme } from "./Utils/themeUtils"; // テーマ初期化をインポート

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

// vue-i18nの設定
const i18n = createI18n({
    locale: "ja", // デフォルトの言語を日本語に設定
    messages: {
        ja,
    },
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n); // vue-i18nを使用するように設定

        // グローバルにSlideUpDownコンポーネントを登録
        app.component("slide-up-down", SlideUpDown);

        // ダイアログの設定
        const dialog = useDialog();
        app.config.globalProperties.$alert = (message) => {
            return dialog.showDialog(message);
        };

        // テーマ初期化はマウント後に実行して初期表示パフォーマンスを改善
        app.mount(el);
        
        // アプリマウント後の非同期テーマ初期化
        setTimeout(() => {
            initTheme();
        }, 0);

        // SPAの初期表示時に履歴を置き換える
        window.history.replaceState(
            {},
            "",
            window.location.pathname + window.location.search
        );

        // iPhoneスワイプバック対策
        window.addEventListener("popstate", (event) => {
            if (event.state === null) {
                window.history.back();
            }
        });

        document.addEventListener("inertia:finish", (event) => {
            // HTTPレスポンスのステータスコードを確認
            const statusCode = event.detail?.response?.status;

            if (statusCode === 419) {
                // セッション切れの場合の処理

                alert("セッションが切れています。リロードします。");
                window.location.reload();
                // } else {
                //     // 特定のページでリロードする場合（例: ダッシュボード）
                //     const currentPath = window.location.pathname;
                //     if (currentPath === "/home" || currentPath === "/dashboard") {
                //         console.log(
                //             "ホームまたはダッシュボードにリダイレクトされました。リロードします。"
                //         );
                //         window.location.reload();
                //     }
            }
        });
    },
    progress: {
        color: "#4B5563",
    },
});
