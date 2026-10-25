/** Cálculo de contraste WCAG 2.x a partir dos tokens declarados em tokens.css. */

type Cores = Record<string, string>;

function luminancia(hex: string): number {
    const valor = hex.replace('#', '');
    const canais = [0, 2, 4].map((i) => parseInt(valor.slice(i, i + 2), 16) / 255);
    const [r, g, b] = canais.map((c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4)) as [number, number, number];
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export function razaoDeContraste(a: string, b: string): number {
    const [maior, menor] = [luminancia(a), luminancia(b)].sort((x, y) => y - x) as [number, number];
    return (maior + 0.05) / (menor + 0.05);
}

function coresDoBloco(css: string, seletor: string): Cores {
    const inicio = css.indexOf(`${seletor} {`);
    if (inicio === -1) {
        throw new Error(`Bloco ${seletor} não encontrado em tokens.css`);
    }
    const bloco = css.slice(inicio, css.indexOf('}', inicio));
    const cores: Cores = {};
    for (const [, nome, valor] of bloco.matchAll(/(--cor-[a-z-]+):\s*(#[0-9a-fA-F]{6})\s*;/g)) {
        cores[nome!] = valor!.toLowerCase();
    }
    return cores;
}

/** Tema claro em :root; o escuro sobrescreve só o que redefine. */
export function temas(css: string): Record<'claro' | 'escuro', Cores> {
    const claro = coresDoBloco(css, ':root');
    return { claro, escuro: { ...claro, ...coresDoBloco(css, "[data-theme='dark']") } };
}

// [texto, fundo, mínimo]: 4,5 para texto normal; 3 para foco, bordas de controle e ícones.
export const PARES: Array<[string, string, number]> = [
    ['--cor-texto', '--cor-superficie', 4.5],
    ['--cor-texto', '--cor-fundo', 4.5],
    ['--cor-texto', '--cor-superficie-suave', 4.5],
    ['--cor-texto-suave', '--cor-superficie', 4.5],
    ['--cor-texto-suave', '--cor-fundo', 4.5],
    ['--cor-texto-suave', '--cor-superficie-suave', 4.5],
    ['--cor-sobre-primaria', '--cor-primaria', 4.5],
    ['--cor-sobre-primaria', '--cor-primaria-hover', 4.5],
    ['--cor-sobre-marca', '--cor-marca', 4.5],
    ['--cor-sobre-marca', '--cor-marca-hover', 4.5],
    ['--cor-sucesso-texto', '--cor-sucesso-fundo', 4.5],
    ['--cor-atencao-texto', '--cor-atencao-fundo', 4.5],
    ['--cor-info-texto', '--cor-info-fundo', 4.5],
    ['--cor-perigo-texto', '--cor-perigo-fundo', 4.5],
    ['--cor-neutro-texto', '--cor-neutro-fundo', 4.5],
    ['--cor-borda-controle', '--cor-superficie', 3],
    ['--cor-foco', '--cor-superficie', 3],
    ['--cor-foco', '--cor-fundo', 3],
    ['--cor-marca', '--cor-superficie', 3],
];

/** Lista os pares abaixo do mínimo, no formato "tema: texto/fundo = razão". */
export function paresReprovados(css: string): string[] {
    const falhas: string[] = [];
    for (const [tema, cores] of Object.entries(temas(css))) {
        for (const [texto, fundo, minimo] of PARES) {
            const a = cores[texto];
            const b = cores[fundo];
            if (!a || !b) {
                falhas.push(`${tema}: ${texto} ou ${fundo} não definido`);
                continue;
            }
            const razao = razaoDeContraste(a, b);
            if (razao < minimo) {
                falhas.push(`${tema}: ${texto}/${fundo} = ${razao.toFixed(2)} (mínimo ${minimo})`);
            }
        }
    }
    return falhas;
}
