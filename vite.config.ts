import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { resolve } from 'path'
import { readdirSync, existsSync } from 'fs'

// Auto-detect module entry points
function getModuleEntries(): string[] {
    const modulesPath = resolve(__dirname, 'Modules')
    const entries: string[] = []

    if (existsSync(modulesPath)) {
        const modules = readdirSync(modulesPath, { withFileTypes: true })
            .filter(d => d.isDirectory())
            .map(d => d.name)

        for (const mod of modules) {
            const jsEntry = resolve(modulesPath, mod, 'resources/js/app.ts')
            const jsEntryFallback = resolve(modulesPath, mod, 'resources/js/app.js')
            if (existsSync(jsEntry)) entries.push(jsEntry)
            else if (existsSync(jsEntryFallback)) entries.push(jsEntryFallback)
        }
    }

    return entries
}

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/erp.ts',      // ERP admin interface
                'resources/js/store.ts',     // 90+ Store e-commerce
                'resources/js/installer.ts', // Web installer
                ...getModuleEntries(),
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
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '@erp': resolve(__dirname, 'resources/js/erp'),
            '@store': resolve(__dirname, 'resources/js/store'),
            '@modules': resolve(__dirname, 'Modules'),
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vue-vendor': ['vue', '@inertiajs/vue3', 'pinia'],
                    'chart-vendor': ['chart.js', 'vue-chartjs'],
                },
            },
        },
    },
})
