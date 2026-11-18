import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
  plugins: [laravel({ input: ["resources/css/dcode-starter-kit.css", "resources/js/dcode-starter-kit.ts"] }), tailwindcss(), vue()],
});
