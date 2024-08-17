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

            if (event.detail && event.detail.page && event.detail.page.props) {
                console.log("プロパティが存在します:", event.detail.page.props);

                if (event.detail.page.props.refresh) {
                    console.log(
                        "リフレッシュフラグによりページがリロードされます。"
                    );
                    window.location.reload();
                }
            } else {
                console.log("event.detail.page にプロパティが存在しません。");
            }
        });
    },
    progress: {
        color: "#4B5563",
    },
});
