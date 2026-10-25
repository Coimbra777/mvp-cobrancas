<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { MessageCircle, ReceiptText } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

import AbasFiltro from '@/components/AbasFiltro.vue';
import Botao from '@/components/Botao.vue';
import CabecalhoPagina from '@/components/CabecalhoPagina.vue';
import Card from '@/components/Card.vue';
import EstadoVazio from '@/components/EstadoVazio.vue';
import SeloSituacao, { type Situacao } from '@/components/SeloSituacao.vue';
import TabelaResponsiva from '@/components/TabelaResponsiva.vue';
import { data, reais } from '@/formatos';
import PainelLayout from '@/layouts/PainelLayout.vue';

type Filtro = 'atrasadas' | 'a-vencer';

interface Parcela {
    id: number;
    cliente: string;
    descricao: string;
    emprestimo_id: number;
    vencimento: string;
    valor_centavos: number;
    situacao: Situacao;
    link_whatsapp: string;
}

const props = defineProps<{
    filtro: Filtro;
    parcelas: Parcela[];
    contagem: Record<Filtro, number>;
}>();

const filtroAtual = ref<string>(props.filtro);
watch(filtroAtual, (filtro) => router.get('/cobrancas', { filtro }, { preserveState: true, preserveScroll: true, replace: true }));

const opcoes = computed(() => [
    { valor: 'atrasadas', rotulo: 'Atrasadas', contagem: props.contagem.atrasadas },
    { valor: 'a-vencer', rotulo: 'A vencer', contagem: props.contagem['a-vencer'] },
]);

const colunas = [
    { chave: 'cliente', rotulo: 'Cliente' },
    { chave: 'descricao', rotulo: 'Parcela' },
    { chave: 'vencimento', rotulo: 'Vencimento' },
    { chave: 'valor_centavos', rotulo: 'Valor', alinhamento: 'fim' as const },
    { chave: 'situacao', rotulo: 'Situação' },
    { chave: 'acoes', rotulo: 'Ações' },
];

const baixando = ref<number | null>(null);

function darBaixa(parcela: Parcela) {
    const confirmou = window.confirm(`Dar baixa na ${parcela.descricao.toLowerCase()} de ${parcela.cliente} (${reais(parcela.valor_centavos)}) com a data de hoje?`);
    if (!confirmou) {
        return;
    }
    baixando.value = parcela.id;
    router.post(`/parcelas/${parcela.id}/baixa`, {}, { preserveScroll: true, onFinish: () => (baixando.value = null) });
}

const vazio = computed(() =>
    props.filtro === 'atrasadas'
        ? { titulo: 'Nenhuma parcela atrasada', descricao: 'Tudo em dia por aqui.' }
        : { titulo: 'Nenhuma parcela a vencer', descricao: 'Quando houver empréstimos com parcelas em aberto, elas aparecem aqui.' },
);
</script>

<template>
    <PainelLayout>
        <CabecalhoPagina titulo="Cobranças" />

        <AbasFiltro v-model="filtroAtual" rotulo="Filtrar parcelas em aberto" :opcoes="opcoes" class="filtro" />

        <Card como="section">
            <TabelaResponsiva
                v-if="parcelas.length"
                :legenda="filtro === 'atrasadas' ? 'Parcelas atrasadas' : 'Parcelas a vencer'"
                :colunas="colunas"
                :linhas="parcelas as unknown as Record<string, unknown>[]"
                :chave-linha="(linha) => linha.id as number"
            >
                <template #celula-cliente="{ linha }">
                    <Link :href="`/emprestimos/${linha.emprestimo_id}`" class="link">{{ linha.cliente }}</Link>
                </template>
                <template #celula-vencimento="{ valor }">{{ data(valor as string) }}</template>
                <template #celula-valor_centavos="{ valor }">{{ reais(valor as number) }}</template>
                <template #celula-situacao="{ valor }"><SeloSituacao :situacao="valor as Situacao" /></template>
                <template #celula-acoes="{ linha }">
                    <div class="acoes">
                        <Botao
                            variante="secundaria"
                            :carregando="baixando === linha.id"
                            :aria-label="`Dar baixa: ${linha.descricao} de ${linha.cliente}`"
                            @click="darBaixa(linha as unknown as Parcela)"
                        >
                            Dar baixa
                        </Botao>
                        <a
                            :href="linha.link_whatsapp as string"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="whatsapp"
                            :aria-label="`Enviar no WhatsApp: ${linha.descricao} de ${linha.cliente} (abre em nova aba)`"
                        >
                            <MessageCircle :size="18" aria-hidden="true" />
                            Enviar no WhatsApp
                        </a>
                    </div>
                </template>
            </TabelaResponsiva>
            <EstadoVazio v-else :titulo="vazio.titulo" :descricao="vazio.descricao" :icone="ReceiptText" />
        </Card>
    </PainelLayout>
</template>

<style scoped>
.filtro {
    margin-bottom: var(--espaco-4);
}

.link {
    color: var(--cor-marca);
    font-weight: var(--peso-semi);
}

.acoes {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--espaco-2);
    white-space: nowrap;
}

.whatsapp {
    display: inline-flex;
    align-items: center;
    gap: var(--espaco-2);
    min-height: var(--alvo-toque);
    padding: 0 var(--espaco-4);
    border-radius: var(--raio-md);
    background: var(--cor-marca);
    color: var(--cor-sobre-marca);
    font-size: var(--texto-sm);
    font-weight: var(--peso-semi);
    text-decoration: none;
}

.whatsapp:hover {
    background: var(--cor-marca-hover);
}
</style>
