import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";
import svgLoader from "vite-svg-loader";
import { resolve } from "node:path";

export default defineConfig({
  plugins: [laravel({ input: ["resources/css/app.css", "resources/js/app.ts"] }), tailwindcss(), vue(), svgLoader()],
  resolve: {
    alias: {
      vue: resolve(import.meta.dirname, "node_modules/vue/dist/vue.esm-bundler.js"),
      "@dsg/table": resolve(import.meta.dirname, "node_modules/@dcodegroup-au/dsg-vue/dist/dsg-vue.table.esm.js"),
      "@dsg": resolve(import.meta.dirname, "node_modules/@dcodegroup-au/dsg-vue/dist/dsg-vue.esm.js"),
      "@components": resolve(import.meta.dirname, "resources/js/components"),
      "@composables": resolve(import.meta.dirname, "resources/js/composables"),
      "@layouts": resolve(import.meta.dirname, "resources/js/layouts"),
      "@js": resolve(import.meta.dirname, "resources/js"),
      "@types": resolve(import.meta.dirname, "resources/js/types/index.ts"),
    },
  },
});
