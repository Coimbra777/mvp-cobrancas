import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AvatarIniciais from '@/components/AvatarIniciais.vue';
import Card from '@/components/Card.vue';
import SeloSituacao, { type Situacao } from '@/components/SeloSituacao.vue';

import { semViolacoesDeA11y } from '../helpers/a11y';

describe('Card', () => {
    it('renderiza o conteúdo na tag escolhida', () => {
        const wrapper = mount(Card, { props: { como: 'section' }, slots: { default: '<p>Saldo</p>' } });

        expect(wrapper.element.tagName).toBe('SECTION');
        expect(wrapper.text()).toBe('Saldo');
    });
});

describe('AvatarIniciais', () => {
    it.each([
        ['Ariel Hikmat', 'AH'],
        ['maria da silva', 'MS'],
        ['Dario', 'D'],
        ['  ', '?'],
    ])('"%s" vira "%s"', (nome, iniciais) => {
        expect(mount(AvatarIniciais, { props: { nome } }).text()).toBe(iniciais);
    });

    it('expõe o nome completo a leitores de tela', async () => {
        const wrapper = mount(AvatarIniciais, { props: { nome: 'Ariel Hikmat' }, attachTo: document.body });

        expect(wrapper.attributes('role')).toBe('img');
        expect(wrapper.attributes('aria-label')).toBe('Ariel Hikmat');
        await semViolacoesDeA11y(wrapper.element);
    });

    it('decorativo fica oculto de leitores de tela', () => {
        const wrapper = mount(AvatarIniciais, { props: { nome: 'Ariel Hikmat', decorativo: true } });

        expect(wrapper.attributes('aria-hidden')).toBe('true');
        expect(wrapper.attributes('aria-label')).toBeUndefined();
    });
});

describe('SeloSituacao', () => {
    it.each([
        ['paga', 'Paga'],
        ['parcial', 'Parcial'],
        ['atrasada', 'Atrasada'],
        ['vence-hoje', 'Vence hoje'],
        ['vence-em-breve', 'Vence em breve'],
        ['a-vencer', 'A vencer'],
    ] as Array<[Situacao, string]>)('"%s" mostra o texto "%s" e um ícone oculto', async (situacao, texto) => {
        const wrapper = mount(SeloSituacao, { props: { situacao }, attachTo: document.body });

        expect(wrapper.text()).toBe(texto);
        const icone = wrapper.find('svg');
        expect(icone.exists()).toBe(true);
        expect(icone.attributes('aria-hidden')).toBe('true');
        await semViolacoesDeA11y(wrapper.element);
    });

    it('variante neutra usa o rótulo informado', () => {
        expect(mount(SeloSituacao, { props: { situacao: 'neutro', rotulo: 'Rascunho' } }).text()).toBe('Rascunho');
    });
});
