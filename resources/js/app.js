// import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// === Vuetify ===
import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import { createVuetify } from 'vuetify';
import navTo from '@/directives/navTo.js';
const vuetify = createVuetify({ components, directives });

// === Toastification ===
import Toast, { POSITION } from "vue-toastification";
import "vue-toastification/dist/index.css";

// === App Name ===
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// === Init Inertia ===
createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(vuetify)
            .directive('to', navTo)
            .use(Toast, {
                position: POSITION.TOP_RIGHT,
                timeout: 3500,
                closeOnClick: true,
                pauseOnFocusLoss: true,
                pauseOnHover: true,
                draggable: true,
                showCloseButtonOnHover: true,
                draggablePercent: 0.4,
            });

        // ⛔🚫 (Dihapus) Heartbeat Server Spam
        // ❌ setInterval(() => { axios.post('/agents/heartbeat') }, 60000);

        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
