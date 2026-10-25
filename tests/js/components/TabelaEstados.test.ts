import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import EstadoCarregando from '@/components/EstadoCarregando.vue';
import EstadoErro from '@/components/EstadoErro.vue';
import EstadoVazio from '@/components/EstadoVazio.vue';
import TabelaResponsiva from '@/components/TabelaResponsiva.vue';

import { semViolacoesDeA11y } from '../helpers/a11y';

describe('TabelaResponsiva', () => {
    const colunas = [
        { chave: 'cliente', rotulo: 'Cliente' },
        { chave: 'valor', rotulo: 'Valor', alinhamento: 'fim' as const },
    ];
    const linhas = [
        { id: 1, cliente: 'Maria', valor: 'R$ 1.050,00' },
        { id: 2, cliente: 'João', valor: 'R$ 500,00' },
    ];

    it('usa legenda e cabeçalhos de coluna semânticos', async () => {
        const wrapper = mount(TabelaResponsiva, {
            props: { legenda: 'Parcelas da semana', colunas, linhas, chaveLinha: (l: Record<string, unknown>) => l.id as number },
            attachTo: document.body,
        });

        expect(wrapper.find('caption').text()).toBe('Parcelas da semana');
        expect(wrapper.findAll('th').map((th) => [th.attributes('scope'), th.text()])).toEqual([
            ['col', 'Cliente'],
            ['col', 'Valor'],
        ]);
        expect(wrapper.findAll('tbody tr')).toHaveLength(2);
        await semViolacoesDeA11y(wrapper.element);
    });

    it('cada célula carrega o rótulo da coluna para o modo cartão do celular', () => {
        const wrapper = mount(TabelaResponsiva, {
            props: { legenda: 'Parcelas', colunas, linhas, chaveLinha: (l: Record<string, unknown>) => l.id as number },
        });

        expect(wrapper.findAll('tbody tr:first-child td').map((td) => [td.attributes('data-rotulo'), td.text()])).toEqual([
            ['Cliente', 'Maria'],
            ['Valor', 'R$ 1.050,00'],
        ]);
    });

    it('permite personalizar a célula por slot', () => {
        const wrapper = mount(TabelaResponsiva, {
            props: { legenda: 'Parcelas', colunas, linhas, chaveLinha: (l: Record<string, unknown>) => l.id as number },
            slots: { 'celula-cliente': '<strong>{{ params.linha.cliente }}</strong>' },
        });

        expect(wrapper.find('tbody td strong').text()).toBe('Maria');
    });
});

describe('Estados', () => {
    it('carregando é uma região de status ocupada com texto para leitores de tela', async () => {
        const wrapper = mount(EstadoCarregando, { attachTo: document.body });

        expect(wrapper.attributes('role')).toBe('status');
        expect(wrapper.attributes('aria-busy')).toBe('true');
        expect(wrapper.find('.sr-only').text()).toBe('Carregando…');
        await semViolacoesDeA11y(wrapper.element);
    });

    it('vazio mostra ícone, título e descrição', async () => {
        const wrapper = mount(EstadoVazio, {
            props: { titulo: 'Nenhuma parcela', descricao: 'Quando houver parcelas, elas aparecem aqui.' },
            attachTo: document.body,
        });

        expect(wrapper.find('h2').text()).toBe('Nenhuma parcela');
        expect(wrapper.text()).toContain('Quando houver parcelas');
        expect(wrapper.find('svg').attributes('aria-hidden')).toBe('true');
        await semViolacoesDeA11y(wrapper.element);
    });

    it('erro é anunciado e oferece "Tentar de novo"', async () => {
        const wrapper = mount(EstadoErro, { attachTo: document.body });

        expect(wrapper.attributes('role')).toBe('alert');
        expect(wrapper.text()).toContain('Não foi possível carregar');

        await wrapper.find('button').trigger('click');

        expect(wrapper.find('button').text()).toBe('Tentar de novo');
        expect(wrapper.emitted('tentarDeNovo')).toHaveLength(1);
        await semViolacoesDeA11y(wrapper.element);
    });
});
