import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import vue3ApexCharts from 'vue3-apexcharts'

createInertiaApp({
    title: (title) => title ? `${title} — Heimdall` : 'Heimdall | Gestão 90-Store',

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),

    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('ApexChart', vue3ApexCharts)

        app.mount(el)
        return app
    },

    progress: {
        color: '#6366f1',
        showSpinner: true,
    },
})
