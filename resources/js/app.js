// Alpine.jsとその他のJavaScript依存関係を読み込みます。これにはBootstrapなどのライブラリも含まれます。
import Alpine from "alpinejs";
import "./bootstrap";
import "./delete";
import "./toggleTenantInput";
import "./persistAdminCheckboxState";

window.Alpine = Alpine;
Alpine.start();
