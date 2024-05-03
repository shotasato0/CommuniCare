// Alpine.jsとその他のJavaScript依存関係を読み込みます。これにはBootstrapなどのライブラリも含まれます。
import Alpine from "alpinejs";
import "./bootstrap";
import "./delete";

window.Alpine = Alpine;
Alpine.start();

// Vueと関連コンポーネントをインポートします。
import { createApp } from "vue";
import router from "./router";
import UnitListComponent from "./components/Unit/UnitListComponent.vue";
import BulletinBoard from "./components/Unit/BulletinBoard.vue";

const app = createApp({});

app.component('unit-list-component', UnitListComponent);
app.component('bulletin-board', BulletinBoard);

app.use(router).mount("#app");
