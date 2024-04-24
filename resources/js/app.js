// Alpine.jsとその他のJavaScript依存関係を読み込みます。これにはBootstrapなどのライブラリも含まれます。
import Alpine from "alpinejs";
import "./bootstrap";
import "./delete";

window.Alpine = Alpine;
Alpine.start();

// Vueと関連コンポーネントをインポートします。
import { createApp } from "vue";
import ExampleComponent from "./components/ExampleComponent.vue";
import router from "./router/index.js";

/**
 * 新しいVueアプリケーションインスタンスを作成し、コンポーネントを登録します。
 * これにより、アプリケーションのビューで使用する準備が整います。
 * <example-component></example-component>のようにコンポーネントを使用できます。
 */
const app = createApp({});
app.component("example-component", ExampleComponent);
app.use(router);

/**
 * Vueコンポーネントを自動的に登録するためのコードです。ディレクトリを再帰的にスキャンし、
 * 見つかったコンポーネントを自動的にアプリケーションに登録します。
 * （この機能を使いたい場合はコメントを外してください）
 */
// Object.entries(import.meta.glob('./**/*.vue', { eager: true })).forEach(([path, definition]) => {
//     app.component(path.split('/').pop().replace(/\.\w+$/, ''), definition.default);
// });

/**
 * アプリケーションインスタンスをHTML要素にアタッチします。
 * "id"属性が"app"の要素にVueアプリケーションをマウントします。
 */
app.mount("#app");
