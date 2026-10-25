import { HandCoins, ReceiptText, Users } from '@lucide/vue';
import type { Component } from 'vue';

export interface AreaDoPainel {
    rotulo: string;
    href: string;
    icone: Component;
}

// Fonte única das áreas: a barra lateral e a barra inferior leem desta lista.
export const AREAS: AreaDoPainel[] = [
    { rotulo: 'Clientes', href: '/clientes', icone: Users },
    { rotulo: 'Empréstimos', href: '/emprestimos', icone: HandCoins },
    { rotulo: 'Cobranças', href: '/cobrancas', icone: ReceiptText },
];

/** A área está ativa na própria URL e nas sub-rotas (ex.: /emprestimos/12). */
export function areaAtiva(href: string, urlAtual: string): boolean {
    const caminho = urlAtual.split(/[?#]/)[0] ?? '';
    return caminho === href || caminho.startsWith(`${href}/`);
}
