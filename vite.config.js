import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig(({ mode }) => {
    const tenantDomain = process.env.TENANT_DOMAIN || ""; // .env にサブドメイン情報がある場合は使用
    const centralDomain = process.env.APP_URL || ""; // セントラルドメインを使用

    return {
        plugins: [
            laravel({
                input: "resources/js/app.js",
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        build: {
            outDir: "public/build",
        },
        base: tenantDomain
            ? `${tenantDomain}/build/`
            : `${centralDomain}/build/`,
    };
});
