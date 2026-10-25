import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

import { paresReprovados, razaoDeContraste } from './helpers/contraste';

const css = readFileSync(resolve(__dirname, '../../resources/css/tokens.css'), 'utf8');

describe('tokens de cor', () => {
    it('calcula a razão de contraste conhecida (preto/branco = 21)', () => {
        expect(razaoDeContraste('#000000', '#ffffff')).toBeCloseTo(21, 1);
    });

    it('todos os pares atendem WCAG 2.2 AA nos temas claro e escuro', () => {
        expect(paresReprovados(css)).toEqual([]);
    });

    it('reprova quando o texto suave fica claro demais', () => {
        const cssRuim = css.replace('--cor-texto-suave: #5a625e;', '--cor-texto-suave: #9a9a9a;');

        expect(paresReprovados(cssRuim)).toContain('claro: --cor-texto-suave/--cor-superficie = 2.81 (mínimo 4.5)');
    });
});
