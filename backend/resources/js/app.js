import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import vue3ApexCharts from 'vue3-apexcharts'

// Global hook to dynamically inject table headers as data-label attributes for mobile card-style view
if (typeof window !== 'undefined') {
    const mapTableHeaders = () => {
        document.querySelectorAll('table').forEach(table => {
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.innerText.trim());
            if (headers.length > 0) {
                table.querySelectorAll('tbody tr').forEach(row => {
                    row.querySelectorAll('td').forEach((td, index) => {
                        if (!td.getAttribute('data-label') && headers[index]) {
                            td.setAttribute('data-label', headers[index]);
                        }
                    });
                });
            }
        });
    };

    router.on('navigate', () => {
        setTimeout(mapTableHeaders, 100);
    });

    // Also run on page load / DOM change
    window.addEventListener('DOMContentLoaded', mapTableHeaders);
}

createInertiaApp({
    title: (title) => title ? `${title} — Heimdall` : 'Heimdall | Vigilância Total',

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
