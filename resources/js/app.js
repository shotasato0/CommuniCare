import "./bootstrap";
import "../css/app.css";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

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
            .use(ZiggyVue);

        app.mount(el);

        // Inertia.jsのカスタムイベントでリロードをトリガーする
        document.addEventListener("inertia:finish", (event) => {
            console.log("Inertia:finish イベントが発火しました");

            document.addEventListener("inertia:finish", (event) => {
                if (event.detail && event.detail.visit) {
                    // 現在のURLを取得
                    const currentUrl = window.location.href;

                    // URLに 'localhost/home' が含まれているかチェック
                    if (currentUrl.includes("localhost/home")) {
                        console.log(
                            "URLが 'localhost/home' を含んでいます。ページをリロードします。"
                        );
                        // 2秒後にページをリロード
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                }
            });
        });
    },
    progress: {
        color: "#4B5563",
    },
});
