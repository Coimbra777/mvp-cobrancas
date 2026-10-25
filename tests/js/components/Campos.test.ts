import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AbasFiltro from '@/components/AbasFiltro.vue';
import CampoBusca from '@/components/CampoBusca.vue';
import CampoTexto from '@/components/CampoTexto.vue';

import { semViolacoesDeA11y } from '../helpers/a11y';

describe('CampoTexto', () => {
    it('associa o rótulo visível ao campo e faz v-model', async () => {
        const wrapper = mount(CampoTexto, {
            props: { rotulo: 'E-mail', tipo: 'email' as const, modelValue: '', 'onUpdate:modelValue': (v: string) => wrapper.setProps({ modelValue: v }) },
            attachTo: document.body,
        });
        const entrada = wrapper.find('input');
        const rotulo = wrapper.find('label');

        expect(rotulo.text()).toBe('E-mail');
        expect(rotulo.attributes('for')).toBe(entrada.attributes('id'));

        await entrada.setValue('dario@example.com');
        expect(wrapper.props('modelValue')).toBe('dario@example.com');
        await semViolacoesDeA11y(wrapper.element);
    });

    it('com erro marca o campo inválido e liga a mensagem por aria-describedby', async () => {
        const wrapper = mount(CampoTexto, {
            props: { rotulo: 'E-mail', erro: 'Informe um e-mail válido.', ajuda: 'O mesmo do cadastro.' },
            attachTo: document.body,
        });
        const entrada = wrapper.find('input');
        const idsDescricao = entrada.attributes('aria-describedby')!.split(' ');

        expect(entrada.attributes('aria-invalid')).toBe('true');
        const mensagem = wrapper.find('.campo__mensagem-erro');
        expect(mensagem.text()).toBe('Informe um e-mail válido.');
        expect(idsDescricao).toContain(mensagem.attributes('id'));
        expect(idsDescricao).toContain(wrapper.find('.campo__ajuda').attributes('id'));
        await semViolacoesDeA11y(wrapper.element);
    });

    it('sem erro não marca inválido', () => {
        const entrada = mount(CampoTexto, { props: { rotulo: 'Nome' } }).find('input');

        expect(entrada.attributes('aria-invalid')).toBeUndefined();
        expect(entrada.attributes('aria-describedby')).toBeUndefined();
    });

    it('expõe focar() para o formulário levar o foco ao campo', () => {
        const wrapper = mount(CampoTexto, { props: { rotulo: 'Senha', tipo: 'password' }, attachTo: document.body });

        (wrapper.vm as unknown as { focar: () => void }).focar();

        expect(document.activeElement).toBe(wrapper.find('input').element);
    });
});

describe('CampoBusca', () => {
    it('é um campo de busca rotulado dentro de uma região de busca', async () => {
        const wrapper = mount(CampoBusca, { props: { rotulo: 'Buscar cliente' }, attachTo: document.body });

        expect(wrapper.attributes('role')).toBe('search');
        expect(wrapper.find('input').attributes('type')).toBe('search');
        expect(wrapper.find('label').text()).toBe('Buscar cliente');
        await semViolacoesDeA11y(wrapper.element);
    });
});

describe('AbasFiltro', () => {
    const opcoes = [
        { valor: 'todas', rotulo: 'Todas' },
        { valor: 'abertas', rotulo: 'Em aberto', contagem: 3 },
        { valor: 'pagas', rotulo: 'Pagas' },
    ];

    it('marca a opção selecionada com aria-pressed e troca ao acionar', async () => {
        const wrapper = mount(AbasFiltro, {
            props: { rotulo: 'Filtrar parcelas', opcoes, modelValue: 'todas', 'onUpdate:modelValue': (v: string) => wrapper.setProps({ modelValue: v }) },
            attachTo: document.body,
        });
        const botoes = wrapper.findAll('button');

        expect(wrapper.attributes('role')).toBe('group');
        expect(wrapper.attributes('aria-label')).toBe('Filtrar parcelas');
        expect(botoes.map((b) => b.attributes('aria-pressed'))).toEqual(['true', 'false', 'false']);

        // Botões nativos: Tab chega em cada opção e Enter/Espaço disparam o clique.
        await botoes[1]!.trigger('click');

        expect(wrapper.props('modelValue')).toBe('abertas');
        expect(wrapper.findAll('button').map((b) => b.attributes('aria-pressed'))).toEqual(['false', 'true', 'false']);
        await semViolacoesDeA11y(wrapper.element);
    });
});
