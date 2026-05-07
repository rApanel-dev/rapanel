import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import CountryFlag from 'vue-country-flag-next';
import translations from "@/Mixins/translations.js";

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const VueApp = createApp({ render: () => h(App, props) });
            VueApp.use(plugin)
            .use(ZiggyVue)
            .component('country-flag', CountryFlag)
            .mixin(translations);
        
        return VueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
