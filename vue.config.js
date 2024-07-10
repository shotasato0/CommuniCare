import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [vue()],
    server: {
        proxy: {
            "/api": {
                target: "http://localhost:9000", // バックエンドのURL
                changeOrigin: true,
                secure: false,
            },
            "/tenant/register": {
                target: "http://localhost:9000", // バックエンドのURL
                changeOrigin: true,
                secure: false,
            },
            "/register": {
                target: "http://localhost:9000", // バックエンドのURL
                changeOrigin: true,
                secure: false,
            },
        },
    },
});
