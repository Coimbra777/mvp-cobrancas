import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { semViolacoesDeA11y } from '../helpers/a11y';

const titulosDaAba: string[] = [];

vi.mock('@inertiajs/vue3', async () => {
    const { defineComponent } = await import('vue');
    return {
        Head: defineComponent({
            props: ['title'],
            setup(props) {
                titulosDaAba.push(props.title as string);
                return () => null;
            },
        }),
    };
});

// O módulo guarda se já houve uma primeira página; cada teste começa do zero.
async function carregar() {
    vi.resetModules();
    return await import('@/components/CabecalhoPagina.vue');
}

describe('CabecalhoPagina', () => {
    afterEach(() => {
        vi.useRealTimers();
        titulosDaAba.length = 0;
    });

    it('mostra a data por extenso no fuso de Fortaleza', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-10-25T13:00:00Z'));
        const { default: CabecalhoPagina } = await carregar();

        const wrapper = mount(CabecalhoPagina, { props: { titulo: 'Painel' } });

        expect(wrapper.find('time').text()).toBe('domingo, 25 de outubro de 2026');
        expect(wrapper.find('time').attributes('datetime')).toBe('2026-10-25');
    });

    it('usa o dia de Fortaleza mesmo quando em UTC já é o dia seguinte', async () => {
        const { dataPorExtenso } = await carregar();

        // 02:00 UTC do dia 26 = 23:00 do dia 25 em Fortaleza (UTC-3).
        expect(dataPorExtenso(new Date('2026-10-26T02:00:00Z'))).toBe('domingo, 25 de outubro de 2026');
    });

    it('tem um único h1 com o título e define o título da aba', async () => {
        const { default: CabecalhoPagina } = await carregar();

        const wrapper = mount(CabecalhoPagina, { props: { titulo: 'Empréstimos' }, attachTo: document.body });

        expect(wrapper.findAll('h1')).toHaveLength(1);
        expect(wrapper.find('h1').text()).toBe('Empréstimos');
        expect(wrapper.find('h1').attributes('tabindex')).toBe('-1');
        expect(titulosDaAba).toContain('Empréstimos');
        await semViolacoesDeA11y(wrapper.element);
    });

    it('não rouba o foco na primeira página, mas foca o título nas navegações seguintes', async () => {
        const { default: CabecalhoPagina } = await carregar();

        const primeira = mount(CabecalhoPagina, { props: { titulo: 'Painel' }, attachTo: document.body });
        expect(document.activeElement).not.toBe(primeira.find('h1').element);
        primeira.unmount();

        // Navegação do Inertia: a nova página monta um novo cabeçalho.
        const seguinte = mount(CabecalhoPagina, { props: { titulo: 'Parcelas' }, attachTo: document.body });
        expect(document.activeElement).toBe(seguinte.find('h1').element);
    });
});
