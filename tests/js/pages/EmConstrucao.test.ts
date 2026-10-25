import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import { semViolacoesDeA11y } from '../helpers/a11y';

vi.mock('@inertiajs/vue3', async () => {
    const falsos = await import('../helpers/inertia');
    return { Link: falsos.LinkFalso, Head: falsos.HeadFalso, usePage: () => falsos.paginaFalsa };
});

const { default: EmConstrucao } = await import('@/pages/EmConstrucao.vue');

describe('Página provisória das áreas', () => {
    it('mostra o título da área e o estado vazio "Em construção", dentro da casca', async () => {
        const wrapper = mount(EmConstrucao, { props: { titulo: 'Clientes' }, attachTo: document.body });

        expect(wrapper.find('main h1').text()).toBe('Clientes');
        expect(wrapper.find('main h2').text()).toBe('Em construção');
        expect(wrapper.find('nav[aria-label="Principal"]').exists()).toBe(true);
        await semViolacoesDeA11y(wrapper.element);
    });
});
