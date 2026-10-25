import { defineComponent, h, reactive } from 'vue';

/** Estado de página falso compartilhado pelos mocks de @inertiajs/vue3 nos testes. */
export const paginaFalsa = reactive({
    url: '/painel',
    props: {
        app: { nome: 'Caderneta' },
        auth: { usuario: { nome: 'Dario Souza' } as { nome: string } | null },
        flash: { sucesso: null, erro: null },
        errors: {},
    },
});

/** Link simplificado: <a href> ou <button> quando as="button". */
export const LinkFalso = defineComponent({
    props: { href: { type: String, required: true }, method: String, as: String },
    setup(props, { slots, attrs }) {
        return () =>
            props.as === 'button'
                ? h('button', { ...attrs, type: 'button', 'data-href': props.href, 'data-method': props.method }, slots.default?.())
                : h('a', { ...attrs, href: props.href }, slots.default?.());
    },
});

export const HeadFalso = defineComponent({ props: ['title'], setup: () => () => null });
