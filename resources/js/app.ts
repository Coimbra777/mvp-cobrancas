import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, type DefineComponent, h } from 'vue';

const paginas = import.meta.glob<{ default: DefineComponent }>('./pages/**/*.vue');

createInertiaApp({
    title: (titulo) => (titulo ? `${titulo} · Caderneta` : 'Caderneta'),
    resolve: async (nome) => {
        const carregar = paginas[`./pages/${nome}.vue`];
        if (!carregar) {
            throw new Error(`Página Inertia não encontrada: ${nome}`);
        }
        return (await carregar()).default;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    // A barra de progresso aceita qualquer valor CSS: usa o token da marca (acompanha o tema).
    progress: { color: 'var(--cor-marca)', delay: 250 },
});
