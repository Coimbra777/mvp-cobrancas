<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { HandCoins } from '@lucide/vue';

import Botao from '@/components/Botao.vue';
import CabecalhoPagina from '@/components/CabecalhoPagina.vue';
import Card from '@/components/Card.vue';
import EstadoVazio from '@/components/EstadoVazio.vue';
import TabelaResponsiva from '@/components/TabelaResponsiva.vue';
import { reais } from '@/formatos';
import PainelLayout from '@/layouts/PainelLayout.vue';

interface Emprestimo {
    id: number;
    cliente: string;
    valor_centavos: number;
    total_centavos: number;
    parcelas: number;
    parcelas_pagas: number;
}

defineProps<{ emprestimos: Emprestimo[] }>();

const colunas = [
    { chave: 'cliente', rotulo: 'Cliente' },
    { chave: 'valor_centavos', rotulo: 'Emprestado', alinhamento: 'fim' as const },
    { chave: 'total_centavos', rotulo: 'Total', alinhamento: 'fim' as const },
    { chave: 'parcelas', rotulo: 'Parcelas pagas', alinhamento: 'fim' as const },
];
</script>

<template>
    <PainelLayout>
        <CabecalhoPagina titulo="Empréstimos">
            <Botao href="/emprestimos/novo" variante="primaria">Novo empréstimo</Botao>
        </CabecalhoPagina>

        <Card como="section">
            <TabelaResponsiva
                v-if="emprestimos.length"
                legenda="Empréstimos, do mais recente para o mais antigo"
                :colunas="colunas"
                :linhas="emprestimos as unknown as Record<string, unknown>[]"
                :chave-linha="(linha) => linha.id as number"
            >
                <template #celula-cliente="{ linha }">
                    <Link :href="`/emprestimos/${linha.id}`" class="link">{{ linha.cliente }}</Link>
                </template>
                <template #celula-valor_centavos="{ valor }">{{ reais(valor as number) }}</template>
                <template #celula-total_centavos="{ valor }">{{ reais(valor as number) }}</template>
                <template #celula-parcelas="{ linha }">{{ linha.parcelas_pagas }} de {{ linha.parcelas }}</template>
            </TabelaResponsiva>
            <EstadoVazio
                v-else
                titulo="Nenhum empréstimo ainda"
                descricao="Cadastre o primeiro em “Novo empréstimo”."
                :icone="HandCoins"
            />
        </Card>
    </PainelLayout>
</template>

<style scoped>
.link {
    color: var(--cor-marca);
    font-weight: var(--peso-semi);
}
</style>
