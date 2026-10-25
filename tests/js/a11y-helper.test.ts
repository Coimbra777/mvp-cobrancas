import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { defineComponent } from 'vue';

import { semViolacoesDeA11y } from './helpers/a11y';

describe('semViolacoesDeA11y', () => {
    it('aceita um botão com nome acessível', async () => {
        const Componente = defineComponent({ template: '<button type="button">Salvar</button>' });
        const wrapper = mount(Componente, { attachTo: document.body });

        await expect(semViolacoesDeA11y(wrapper.element)).resolves.toBeUndefined();
    });

    it('reprova um botão sem nome acessível', async () => {
        const Componente = defineComponent({ template: '<button type="button"></button>' });
        const wrapper = mount(Componente, { attachTo: document.body });

        await expect(semViolacoesDeA11y(wrapper.element)).rejects.toThrow(/button-name/);
    });
});
