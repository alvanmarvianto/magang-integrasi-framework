import '../css/app.css';
import '@vue-flow/core/dist/style.css';

import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import './config/fontawesome';

// Import SweetAlert2
import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.use(VueSweetalert2, {
            confirmButtonColor: 'var(--primary-color)',
            cancelButtonColor: 'var(--danger-color)',
        });
        app.component('font-awesome-icon', FontAwesomeIcon);
        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
