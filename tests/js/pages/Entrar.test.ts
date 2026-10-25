import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, reactive } from 'vue';

import { semViolacoesDeA11y } from '../helpers/a11y';

type Opcoes = { onError?: (erros: Record<string, string>) => void; onFinish?: () => void };

// Simula o useForm do Inertia: o "servidor" responde com os erros definidos em cada teste.
let errosDoServidor: Record<string, string> = {};
const formulario = reactive({
    email: '',
    senha: '',
    manter_conectado: false,
    errors: {} as Record<string, string>,
    processing: false,
    post: vi.fn((_url: string, opcoes: Opcoes) => {
        formulario.errors = errosDoServidor;
        opcoes.onError?.(errosDoServidor);
        opcoes.onFinish?.();
    }),
    reset: vi.fn(),
});

vi.mock('@inertiajs/vue3', () => ({
    Head: defineComponent({ props: ['title'], template: '<span hidden />' }),
    useForm: () => formulario,
}));

const { default: Entrar } = await import('@/pages/auth/Entrar.vue');

async function montarEEnviar(erros: Record<string, string>) {
    errosDoServidor = erros;
    const wrapper = mount(Entrar, { attachTo: document.body });
    await wrapper.find('form').trigger('submit');
    await flushPromises();
    return wrapper;
}

describe('Página Entrar', () => {
    beforeEach(() => {
        formulario.errors = {};
        formulario.post.mockClear();
    });

    it('tem campos rotulados, "Manter conectado" e passa no axe', async () => {
        const wrapper = mount(Entrar, { attachTo: document.body });

        expect(wrapper.find('h1').text()).toBe('Entrar');
        expect(wrapper.findAll('label').map((l) => l.text())).toEqual(['E-mail', 'Senha', 'Manter conectado']);
        expect(wrapper.find('input[type="email"]').attributes('autocomplete')).toBe('username');
        expect(wrapper.find('input[type="password"]').attributes('autocomplete')).toBe('current-password');
        await semViolacoesDeA11y(wrapper.element);
    });

    it('envia para /entrar', async () => {
        await montarEEnviar({});

        expect(formulario.post).toHaveBeenCalledWith('/entrar', expect.any(Object));
    });

    it('mostra o erro genérico em alerta e leva o foco ao e-mail', async () => {
        const wrapper = await montarEEnviar({ credenciais: 'E-mail ou senha incorretos.' });

        const alerta = wrapper.find('[role="alert"]');
        expect(alerta.text()).toBe('E-mail ou senha incorretos.');
        expect(document.activeElement).toBe(wrapper.find('input[type="email"]').element);
        // Erro genérico não marca nenhum campo como inválido.
        expect(wrapper.find('input[aria-invalid="true"]').exists()).toBe(false);
        await semViolacoesDeA11y(wrapper.element);
    });

    it('com erro só na senha leva o foco à senha', async () => {
        const wrapper = await montarEEnviar({ senha: 'Informe a senha.' });

        const senha = wrapper.find('input[type="password"]');
        expect(document.activeElement).toBe(senha.element);
        expect(senha.attributes('aria-invalid')).toBe('true');
        expect(wrapper.text()).toContain('Informe a senha.');
    });

    it('com erros nos dois campos leva o foco ao primeiro (e-mail)', async () => {
        const wrapper = await montarEEnviar({ email: 'Informe o e-mail.', senha: 'Informe a senha.' });

        expect(document.activeElement).toBe(wrapper.find('input[type="email"]').element);
        await semViolacoesDeA11y(wrapper.element);
    });
});
