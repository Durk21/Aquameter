import "../css/app.css";
import "leaflet/dist/leaflet.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import LoadingDroplet from "./Components/LoadingDroplet.vue";
import { useDarkMode } from "./composables/useDarkMode";

useDarkMode().initDarkMode();

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue"),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({
            render: () => h("div", [h(App, props), h(LoadingDroplet)]),
        })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
});
