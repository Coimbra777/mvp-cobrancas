import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import { paginaFalsa } from '../helpers/inertia';
import { semViolacoesDeA11y } from '../helpers/a11y';

vi.mock('@inertiajs/vue3', async () => {
    const falsos = await import('../helpers/inertia');
    return { Link: falsos.LinkFalso, Head: falsos.HeadFalso, usePage: () => falsos.paginaFalsa };
});

const { default: PainelLayout } = await import('@/layouts/PainelLayout.vue');

function montar(url = '/painel') {
    paginaFalsa.url = url;
    return mount(PainelLayout, { slots: { default: '<h1>Conteúdo</h1>' }, attachTo: document.body });
}

const navLateral = (w: ReturnType<typeof montar>) => w.find('nav[aria-label="Principal"]');
const navInferior = (w: ReturnType<typeof montar>) => w.find('nav[aria-label="Principal no celular"]');
const atuais = (nav: ReturnType<ReturnType<typeof montar>['find']>) =>
    nav.findAll('[aria-current="page"]').map((el) => el.text());

describe('PainelLayout', () => {
    it('lista as áreas na ordem da spec na barra lateral', () => {
        const wrapper = montar();

        expect(navLateral(wrapper).findAll('a').map((a) => [a.text(), a.attributes('href')])).toEqual([
            ['Painel', '/painel'],
            ['Clientes', '/clientes'],
            ['Empréstimos', '/emprestimos'],
            ['Parcelas', '/parcelas'],
            ['Lembretes', '/lembretes'],
            ['Configurações', '/configuracoes'],
        ]);
    });

    it('marca só a área atual com aria-current, inclusive em sub-rotas', () => {
        const wrapper = montar('/clientes/12?pagina=2');

        expect(atuais(navLateral(wrapper))).toEqual(['Clientes']);
        expect(atuais(navInferior(wrapper))).toEqual(['Clientes']);
    });

    it('a barra inferior tem 4 áreas e o botão "Mais"', () => {
        const wrapper = montar();
        const itens = navInferior(wrapper).findAll('.inferior__lista > li > .inferior__item').map((el) => el.text());

        expect(itens).toEqual(['Painel', 'Clientes', 'Empréstimos', 'Parcelas', 'Mais']);
    });

    it('"Mais" abre o menu com Lembretes, Configurações, tema e Sair', async () => {
        const wrapper = montar();
        const botao = wrapper.find('button[aria-controls="menu-mais"]');
        const menu = wrapper.find('#menu-mais');

        expect(botao.attributes('aria-expanded')).toBe('false');
        expect(menu.isVisible()).toBe(false);

        await botao.trigger('click');

        expect(botao.attributes('aria-expanded')).toBe('true');
        expect(menu.isVisible()).toBe(true);
        expect(menu.findAll('a').map((a) => a.text())).toEqual(['Lembretes', 'Configurações']);
        expect(menu.find('[role="group"][aria-label="Tema"]').exists()).toBe(true);
        expect(menu.find('button[data-href="/sair"]').text()).toBe('Sair');
    });

    it('Esc fecha o menu "Mais" e devolve o foco ao botão', async () => {
        const wrapper = montar();
        const botao = wrapper.find('button[aria-controls="menu-mais"]');
        await botao.trigger('click');
        (wrapper.find('#menu-mais a').element as HTMLElement).focus();

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await wrapper.vm.$nextTick();

        expect(botao.attributes('aria-expanded')).toBe('false');
        expect(document.activeElement).toBe(botao.element);
    });

    it('clique fora fecha o menu "Mais"', async () => {
        const wrapper = montar();
        const botao = wrapper.find('button[aria-controls="menu-mais"]');
        await botao.trigger('click');

        document.body.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        await wrapper.vm.$nextTick();

        expect(botao.attributes('aria-expanded')).toBe('false');
    });

    it('destaca "Mais" quando a área atual está dentro dele', () => {
        const wrapper = montar('/lembretes');

        expect(wrapper.find('button[aria-controls="menu-mais"]').classes()).toContain('inferior__item--ativo');
    });

    it('"Pular para o conteúdo" é o primeiro item focável e aponta para a região principal', () => {
        const wrapper = montar();
        const primeiroFocavel = wrapper.element.querySelector('a[href], button');

        expect(primeiroFocavel?.textContent?.trim()).toBe('Pular para o conteúdo');
        expect(primeiroFocavel?.getAttribute('href')).toBe('#conteudo');
        expect(wrapper.find('main#conteudo').exists()).toBe(true);
    });

    it('mostra o nome do usuário e a ação Sair na barra lateral', () => {
        const wrapper = montar();
        const lateral = wrapper.find('.lateral');

        expect(lateral.find('.lateral__usuario').text()).toContain('Dario Souza');
        expect(lateral.find('button[data-href="/sair"]').attributes('data-method')).toBe('post');
    });

    it('passa no axe', async () => {
        await semViolacoesDeA11y(montar().element);
    });
});
