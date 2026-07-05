import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/admin.css',
                'resources/js/app.js',
            ],
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
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        host: 'localhost',
        port: 5173,
        allowedHosts: true,
        hmr: {
            host: 'localhost',
            port: 5173,
        },
        watch: {
            ignored: [
                '**/storage/framework/views/**',
                '**/.env',
                '**/.env.*',
            ],
        },
    },
    build: {
        sourcemap: false,
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['vue', '@inertiajs/vue3'],
                    charts: ['apexcharts', 'vue3-apexcharts'],
                },
            },
        },
    },
})

