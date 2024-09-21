import "./bootstrap";
import "../css/app.css";
import "bootstrap-icons/font/bootstrap-icons.css";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import { createI18n } from "vue-i18n"; // vue-i18nをインポート
import ja from "../../lang/ja.json"; // Laravelのlangディレクトリからja.jsonを読み込み

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

        app.mount(el);

        // Inertia.jsのカスタムイベントでリロードをトリガーする
        document.addEventListener("inertia:finish", (event) => {
            console.log("Inertia:finish イベントが発火しました");

            // 現在のURLを取得
            const currentUrl = window.location.href;

            // ログイン後またはダッシュボードでのリダイレクトを処理
            if (
                currentUrl.includes("localhost/home") ||
                currentUrl.includes("localhost/dashboard")
            ) {
                console.log(
                    "URLが 'localhost/home'または'localhost/dashboard'を含んでいます。ページをリロードします。"
                );
                window.location.reload();
            }
        });
    },
    progress: {
        color: "#4B5563",
    },
});
