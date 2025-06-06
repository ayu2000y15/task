import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.js",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            // プロジェクト独自のカラーパレットや設定をここに追加できます
            // 例: BootstrapのプライマリカラーをTailwindで使えるようにする
            // colors: {
            //     'bs-primary': '#0d6efd',
            // }
        },
    },

    plugins: [forms, require("@tailwindcss/aspect-ratio")],
};
