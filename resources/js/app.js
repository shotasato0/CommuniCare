// Alpine.jsとその他のJavaScript依存関係を読み込みます。これにはBootstrapなどのライブラリも含まれます。
import Alpine from "alpinejs";
import "./bootstrap";
import "./delete";

window.Alpine = Alpine;
Alpine.start();

// Vueと関連コンポーネントをインポートします。
import { createApp } from "vue";
import router from "./router";

const app = createApp({});
app.use(router).mount("#app");
