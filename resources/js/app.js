import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import VueApexCharts from 'vue3-apexcharts';
import { formatDate, formatDateTime, formatHumanDate, formatRelative, daysRemaining, isExpired, dayjs } from './Utils/date';

const appName = import.meta.env.VITE_APP_NAME || 'Inoodex Inventory';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.config.globalProperties.$date = {
            format: formatDate,
            dateTime: formatDateTime,
            human: formatHumanDate,
            relative: formatRelative,
            daysRemaining,
            isExpired,
            dayjs,
        };
        return app
            .use(plugin)
            .use(ZiggyVue)
            .use(VueApexCharts)
            .mount(el);
    },
    progress: {
        color: '#6366f1',
        showSpinner: true,
    },
});
