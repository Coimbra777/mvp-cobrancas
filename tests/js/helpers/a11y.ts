import axe from 'axe-core';

/**
 * Roda o axe-core no elemento e falha listando as violações encontradas.
 * Contraste fica de fora: o jsdom não calcula estilos (ver tests/js/tokens.test.ts).
 */
export async function semViolacoesDeA11y(elemento: Element): Promise<void> {
    const resultado = await axe.run(elemento, {
        rules: {
            'color-contrast': { enabled: false },
            // Componentes isolados não ficam dentro de landmarks; a casca é testada inteira.
            region: { enabled: false },
        },
    });

    if (resultado.violations.length > 0) {
        const detalhes = resultado.violations
            .map((v) => `- ${v.id}: ${v.help} (${v.nodes.map((n) => n.target.join(' ')).join(', ')})`)
            .join('\n');
        throw new Error(`Violações de acessibilidade:\n${detalhes}`);
    }
}
