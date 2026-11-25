import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            'ziggy-js': path.resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-vue':     ['vue', '@inertiajs/vue3'],
                    'vendor-charts':  ['apexcharts', 'vue3-apexcharts'],
                    'vendor-editor':  ['@tiptap/vue-3', '@tiptap/starter-kit', '@tiptap/extension-link', '@tiptap/extension-underline'],
                    'vendor-barcode': ['html5-qrcode'],
                    'vendor-misc':    ['dayjs', 'lucide-vue-next', 'ziggy-js'],
                },
            },
        },
        chunkSizeWarningLimit: 600,
    },
});
