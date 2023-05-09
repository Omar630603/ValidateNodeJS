import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

const host = "validatenodejs.test";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
    ],
    server: {
        host,
        hmr: { host },
        https: {
            key: "D:/laragon/etc/ssl/laragon.key",
            cert: "D:/laragon/etc/ssl/laragon.crt",
        },
        watch: {
            ignored: ["public/storage/**/*", "storage/**/*", "**/.env"],
            // usePolling: true,
        },
    },
});
