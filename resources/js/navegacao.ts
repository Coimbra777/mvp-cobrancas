import { Bell, HandCoins, LayoutDashboard, ReceiptText, Settings, Users } from '@lucide/vue';
import type { Component } from 'vue';

export interface AreaDoPainel {
    rotulo: string;
    href: string;
    icone: Component;
}

// Fonte única das áreas: a sidebar e a barra inferior leem desta lista.
export const AREAS: AreaDoPainel[] = [
    { rotulo: 'Painel', href: '/painel', icone: LayoutDashboard },
    { rotulo: 'Clientes', href: '/clientes', icone: Users },
    { rotulo: 'Empréstimos', href: '/emprestimos', icone: HandCoins },
    { rotulo: 'Parcelas', href: '/parcelas', icone: ReceiptText },
    { rotulo: 'Lembretes', href: '/lembretes', icone: Bell },
    { rotulo: 'Configurações', href: '/configuracoes', icone: Settings },
];

// No celular: 4 áreas na barra inferior; o resto fica no menu "Mais".
export const AREAS_BARRA_INFERIOR = AREAS.slice(0, 4);
export const AREAS_MENU_MAIS = AREAS.slice(4);

/** A área está ativa na própria URL e nas sub-rotas (ex.: /clientes/12). */
export function areaAtiva(href: string, urlAtual: string): boolean {
    const caminho = urlAtual.split(/[?#]/)[0] ?? '';
    return caminho === href || caminho.startsWith(`${href}/`);
}
