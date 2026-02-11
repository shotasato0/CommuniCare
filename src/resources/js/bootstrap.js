import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// CSRFトークンを設定
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
} else {
    console.error(
        "CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token"
    );
}

// Ziggyのroute()関数をグローバルに利用可能にする
// window.Ziggyが利用可能になった後、route()関数を構築
// app.jsで設定されるため、ここでは準備のみ
