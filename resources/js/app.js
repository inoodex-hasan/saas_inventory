import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import VueApexCharts from 'vue3-apexcharts';
import { formatDate, formatDateTime, formatHumanDate, formatRelative, daysRemaining, isExpired, dayjs } from './Utils/date';
import { t, currentLang, toggleLang, setLang } from './Utils/translations';

const appName = 'InoManage - Cloud Inventory Management Software';

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
        app.config.globalProperties.$t = t;
        app.config.globalProperties.$lang = currentLang;
        app.config.globalProperties.$toggleLang = toggleLang;
        app.config.globalProperties.$setLang = setLang;
        return app
            .use(plugin)
            .use(ZiggyVue)
            .use(VueApexCharts)
            .mount(el);
    },
    progress: {
        delay: 0,
        color: '#6366f1',
        includeCSS: true,
        showSpinner: true,
    },
});

// Enable smooth horizontal wheel scrolling on table overflow containers
if (typeof window !== 'undefined') {
    window.addEventListener('wheel', (e) => {
        if (e.ctrlKey || e.altKey) return;
        const target = e.target;
        if (!target) return;
        const container = target.closest('.overflow-x-auto');
        if (!container) return;
        if (container.scrollWidth > container.clientWidth && Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
            if (container.scrollHeight <= container.clientHeight + 10) {
                container.scrollLeft += e.deltaY;
            }
        }
    }, { passive: true });
}
