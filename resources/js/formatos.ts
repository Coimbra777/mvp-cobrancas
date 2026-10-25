// Formatação de exibição em pt-BR. Datas chegam do servidor como "AAAA-MM-DD".

const moeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

export function reais(centavos: number): string {
    return moeda.format(centavos / 100);
}

export function data(iso: string | null): string {
    if (!iso) {
        return '';
    }
    const [ano, mes, dia] = iso.split('-');
    return `${dia}/${mes}/${ano}`;
}

export function telefone(digitos: string): string {
    const ddd = digitos.slice(0, 2);
    const numero = digitos.slice(2);
    const meio = numero.length - 4;
    return `(${ddd}) ${numero.slice(0, meio)}-${numero.slice(meio)}`;
}
