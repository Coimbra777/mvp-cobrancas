import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import Botao from '@/components/Botao.vue';

import { semViolacoesDeA11y } from '../helpers/a11y';

describe('Botao', () => {
    it.each(['primaria', 'secundaria', 'marca'] as const)('renderiza a variante %s como botão nativo', async (variante) => {
        const wrapper = mount(Botao, { props: { variante }, slots: { default: 'Salvar' }, attachTo: document.body });

        // <button> nativo: Enter e Espaço acionam o clique sem código extra.
        expect(wrapper.element.tagName).toBe('BUTTON');
        expect(wrapper.attributes('type')).toBe('button');
        expect(wrapper.classes()).toContain(`botao--${variante}`);
        await semViolacoesDeA11y(wrapper.element);
    });

    it('emite click quando livre', async () => {
        const wrapper = mount(Botao, { slots: { default: 'Salvar' } });

        await wrapper.trigger('click');

        expect(wrapper.emitted('click')).toHaveLength(1);
    });

    it('no estado carregando bloqueia novo clique, mantém o rótulo e anuncia', async () => {
        const wrapper = mount(Botao, {
            props: { carregando: true, tipo: 'submit' },
            slots: { default: 'Entrar' },
            attachTo: document.body,
        });

        await wrapper.trigger('click');

        expect(wrapper.emitted('click')).toBeUndefined();
        expect(wrapper.attributes('aria-busy')).toBe('true');
        expect(wrapper.attributes('aria-disabled')).toBe('true');
        expect(wrapper.text()).toContain('Entrar');
        expect(wrapper.find('.sr-only').text()).toBe('(carregando)');
        await semViolacoesDeA11y(wrapper.element);
    });

    it.each([
        [false, true],
        [true, false],
    ])('com carregando=%s o envio do formulário acontece=%s', async (carregando, esperado) => {
        const wrapper = mount(
            {
                components: { Botao },
                props: ['carregando'],
                template: '<form @submit.prevent="enviado = true"><Botao tipo="submit" :carregando="carregando">Entrar</Botao></form>',
                data: () => ({ enviado: false }),
            },
            { props: { carregando }, attachTo: document.body },
        );

        await wrapper.find('button').trigger('click');

        expect((wrapper.vm as unknown as { enviado: boolean }).enviado).toBe(esperado);
    });
});
