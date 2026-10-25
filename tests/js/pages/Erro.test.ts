import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import { semViolacoesDeA11y } from '../helpers/a11y';

vi.mock('@inertiajs/vue3', async () => {
    const falsos = await import('../helpers/inertia');
    return { Link: falsos.LinkFalso, Head: falsos.HeadFalso };
});

const { default: Erro } = await import('@/pages/Erro.vue');

describe('Página de erro', () => {
    it.each([
        [404, 'Página não encontrada'],
        [419, 'Sua sessão expirou'],
        [500, 'Algo deu errado'],
        [503, 'Em manutenção'],
    ] as const)('status %i mostra "%s" e o caminho de volta ao Painel', async (status, titulo) => {
        const wrapper = mount(Erro, { props: { status }, attachTo: document.body });

        expect(wrapper.find('h1').text()).toBe(titulo);
        expect(wrapper.find('a[href="/painel"]').text()).toBe('Ir para o Painel');
        await semViolacoesDeA11y(wrapper.element);
    });

    it('sessão expirada oferece recarregar a página', async () => {
        const recarregar = vi.fn();
        vi.stubGlobal('location', { ...window.location, reload: recarregar });
        const wrapper = mount(Erro, { props: { status: 419 } });

        await wrapper.find('button').trigger('click');

        expect(wrapper.find('button').text()).toBe('Recarregar a página');
        expect(recarregar).toHaveBeenCalled();
        vi.unstubAllGlobals();
    });
});
