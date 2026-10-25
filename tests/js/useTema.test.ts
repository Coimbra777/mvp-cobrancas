import { beforeEach, describe, expect, it, vi } from 'vitest';

type Ouvinte = () => void;

function simularSistema(escuro: boolean) {
    const ouvintes: Ouvinte[] = [];
    const consulta = {
        matches: escuro,
        addEventListener: (_: string, fn: Ouvinte) => ouvintes.push(fn),
    };
    vi.stubGlobal('matchMedia', vi.fn(() => consulta));
    return {
        mudar(novoEscuro: boolean) {
            consulta.matches = novoEscuro;
            ouvintes.forEach((fn) => fn());
        },
    };
}

// O composable guarda estado no módulo; cada teste carrega uma cópia nova.
async function carregarUseTema() {
    vi.resetModules();
    return (await import('@/composables/useTema')).useTema;
}

const temaNoHtml = () => document.documentElement.getAttribute('data-theme');

describe('useTema', () => {
    beforeEach(() => {
        localStorage.clear();
        document.documentElement.removeAttribute('data-theme');
        vi.unstubAllGlobals();
    });

    it('segue o sistema quando não há escolha salva', async () => {
        simularSistema(true);
        const { preferencia, temaEfetivo } = (await carregarUseTema())();

        expect(preferencia.value).toBe('auto');
        expect(temaEfetivo.value).toBe('escuro');
        expect(temaNoHtml()).toBe('dark');
    });

    it('salva a escolha manual e a respeita ao recarregar', async () => {
        simularSistema(true);
        (await carregarUseTema())().definirPreferencia('claro');

        expect(localStorage.getItem('caderneta.tema')).toBe('claro');

        const { temaEfetivo } = (await carregarUseTema())();
        expect(temaEfetivo.value).toBe('claro');
        expect(temaNoHtml()).toBe('light');
    });

    it('voltar para Automático apaga a escolha salva', async () => {
        simularSistema(false);
        const { definirPreferencia } = (await carregarUseTema())();
        definirPreferencia('escuro');
        definirPreferencia('auto');

        expect(localStorage.getItem('caderneta.tema')).toBeNull();
        expect(temaNoHtml()).toBe('light');
    });

    it('em Automático acompanha a mudança do sistema', async () => {
        const sistema = simularSistema(false);
        const { temaEfetivo } = (await carregarUseTema())();

        sistema.mudar(true);

        expect(temaEfetivo.value).toBe('escuro');
        expect(temaNoHtml()).toBe('dark');
    });

    it('com escolha manual ignora a mudança do sistema', async () => {
        const sistema = simularSistema(false);
        (await carregarUseTema())().definirPreferencia('claro');

        sistema.mudar(true);

        expect(temaNoHtml()).toBe('light');
    });

    it('funciona sem localStorage disponível', async () => {
        simularSistema(false);
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('bloqueado');
        });
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('bloqueado');
        });
        const { definirPreferencia, temaEfetivo } = (await carregarUseTema())();

        expect(() => definirPreferencia('escuro')).not.toThrow();
        expect(temaEfetivo.value).toBe('escuro');
    });
});
