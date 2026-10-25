import { readonly, ref } from 'vue';

export type PreferenciaTema = 'claro' | 'escuro' | 'auto';
export type TemaEfetivo = 'claro' | 'escuro';

// Mesma chave e mesma regra do script inline em resources/views/app.blade.php.
const CHAVE = 'caderneta.tema';

const preferencia = ref<PreferenciaTema>('auto');
const temaEfetivo = ref<TemaEfetivo>('claro');
let iniciado = false;

function consultaEscuro(): MediaQueryList | null {
    return typeof window.matchMedia === 'function' ? window.matchMedia('(prefers-color-scheme: dark)') : null;
}

function lerPreferencia(): PreferenciaTema {
    try {
        const salva = localStorage.getItem(CHAVE);
        if (salva === 'claro' || salva === 'escuro') {
            return salva;
        }
    } catch {
        // localStorage indisponível (modo privado, bloqueado): segue o sistema.
    }
    return 'auto';
}

function salvarPreferencia(valor: PreferenciaTema): void {
    try {
        if (valor === 'auto') {
            localStorage.removeItem(CHAVE);
        } else {
            localStorage.setItem(CHAVE, valor);
        }
    } catch {
        // Sem persistência: a escolha vale até recarregar a página.
    }
}

function aplicar(): void {
    const escuro = preferencia.value === 'escuro' || (preferencia.value === 'auto' && !!consultaEscuro()?.matches);
    temaEfetivo.value = escuro ? 'escuro' : 'claro';
    document.documentElement.setAttribute('data-theme', escuro ? 'dark' : 'light');
}

export function useTema() {
    if (!iniciado) {
        iniciado = true;
        preferencia.value = lerPreferencia();
        aplicar();
        consultaEscuro()?.addEventListener('change', () => {
            if (preferencia.value === 'auto') {
                aplicar();
            }
        });
    }

    function definirPreferencia(valor: PreferenciaTema): void {
        preferencia.value = valor;
        salvarPreferencia(valor);
        aplicar();
    }

    return {
        preferencia: readonly(preferencia),
        temaEfetivo: readonly(temaEfetivo),
        definirPreferencia,
    };
}
