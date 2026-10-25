<script setup lang="ts">
import CabecalhoPagina from '@/components/CabecalhoPagina.vue';
import Card from '@/components/Card.vue';
import SeloSituacao, { type Situacao } from '@/components/SeloSituacao.vue';
import TabelaResponsiva from '@/components/TabelaResponsiva.vue';
import { data, reais } from '@/formatos';
import PainelLayout from '@/layouts/PainelLayout.vue';

interface Parcela {
    id: number;
    numero: number;
    vencimento: string;
    valor_centavos: number;
    situacao: Situacao;
    pago_em: string | null;
}

defineProps<{
    emprestimo: {
        id: number;
        cliente: string;
        valor_centavos: number;
        taxa_mensal: number;
        metodo: string;
        quantidade_parcelas: number;
        total_centavos: number;
    };
    parcelas: Parcela[];
}>();

const colunas = [
    { chave: 'numero', rotulo: 'Parcela' },
    { chave: 'vencimento', rotulo: 'Vencimento' },
    { chave: 'valor_centavos', rotulo: 'Valor', alinhamento: 'fim' as const },
    { chave: 'situacao', rotulo: 'Situação' },
    { chave: 'pago_em', rotulo: 'Pago em' },
];

const taxa = (valor: number) => `${valor.toLocaleString('pt-BR', { maximumFractionDigits: 2 })}% ao mês`;
</script>

<template>
    <PainelLayout>
        <CabecalhoPagina :titulo="`Empréstimo de ${emprestimo.cliente}`" />

        <div class="emprestimo">
            <Card como="section" aria-label="Dados do empréstimo">
                <dl class="dados">
                    <div><dt>Emprestado</dt><dd>{{ reais(emprestimo.valor_centavos) }}</dd></div>
                    <div><dt>Taxa</dt><dd>{{ taxa(emprestimo.taxa_mensal) }}</dd></div>
                    <div><dt>Método</dt><dd>{{ emprestimo.metodo }}</dd></div>
                    <div><dt>Parcelas</dt><dd>{{ emprestimo.quantidade_parcelas }}</dd></div>
                    <div class="dados__total"><dt>Total a receber</dt><dd>{{ reais(emprestimo.total_centavos) }}</dd></div>
                </dl>
            </Card>

            <Card como="section" aria-labelledby="titulo-parcelas">
                <h2 id="titulo-parcelas" class="titulo-secao">Parcelas</h2>
                <TabelaResponsiva
                    legenda="Parcelas do empréstimo"
                    :colunas="colunas"
                    :linhas="parcelas as unknown as Record<string, unknown>[]"
                    :chave-linha="(linha) => linha.id as number"
                >
                    <template #celula-numero="{ valor }">{{ valor }} de {{ emprestimo.quantidade_parcelas }}</template>
                    <template #celula-vencimento="{ valor }">{{ data(valor as string) }}</template>
                    <template #celula-valor_centavos="{ valor }">{{ reais(valor as number) }}</template>
                    <template #celula-situacao="{ valor }"><SeloSituacao :situacao="valor as Situacao" /></template>
                    <template #celula-pago_em="{ valor }">{{ valor ? data(valor as string) : '—' }}</template>
                </TabelaResponsiva>
            </Card>
        </div>
    </PainelLayout>
</template>

<style scoped>
.emprestimo {
    display: grid;
    gap: var(--espaco-5);
}

.dados {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
    gap: var(--espaco-4);
    margin: 0;
}

.dados dt {
    color: var(--cor-texto-suave);
    font-size: var(--texto-sm);
}

.dados dd {
    margin: 0;
    font-size: var(--texto-lg);
    font-weight: var(--peso-semi);
}

.dados__total dd {
    color: var(--cor-marca);
}

.titulo-secao {
    font-size: var(--texto-lg);
    font-weight: var(--peso-semi);
    margin-bottom: var(--espaco-4);
}
</style>
