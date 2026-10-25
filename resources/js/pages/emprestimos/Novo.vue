<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Users } from '@lucide/vue';

import Botao from '@/components/Botao.vue';
import CabecalhoPagina from '@/components/CabecalhoPagina.vue';
import CampoSelecao from '@/components/CampoSelecao.vue';
import CampoTexto from '@/components/CampoTexto.vue';
import Card from '@/components/Card.vue';
import EstadoVazio from '@/components/EstadoVazio.vue';
import PainelLayout from '@/layouts/PainelLayout.vue';

const props = defineProps<{ clientes: Array<{ id: number; nome: string }>; hoje: string }>();

const form = useForm({
    cliente_id: null as number | null,
    valor: '',
    taxa_mensal: '',
    metodo: 'simples',
    quantidade_parcelas: '',
    primeiro_vencimento: props.hoje,
});

const opcoesClientes = props.clientes.map((c) => ({ valor: c.id, rotulo: c.nome }));
const opcoesMetodo = [
    { valor: 'simples', rotulo: 'Juros simples' },
    { valor: 'composto', rotulo: 'Juros compostos' },
];

function salvar() {
    form.post('/emprestimos');
}
</script>

<template>
    <PainelLayout>
        <CabecalhoPagina titulo="Novo empréstimo" />

        <Card como="section" class="novo">
            <form v-if="clientes.length" class="formulario" novalidate @submit.prevent="salvar">
                <CampoSelecao
                    v-model="form.cliente_id" @update:model-value="form.clearErrors('cliente_id')"
                    rotulo="Cliente"
                    :opcoes="opcoesClientes"
                    placeholder="Escolha o cliente"
                    obrigatorio
                    :erro="form.errors.cliente_id"
                />
                <div class="linha">
                    <CampoTexto v-model="form.valor" @update:model-value="form.clearErrors('valor')" rotulo="Valor emprestado (R$)" inputmode="decimal" ajuda="Ex.: 1.500,00" obrigatorio :erro="form.errors.valor" />
                    <CampoTexto v-model="form.taxa_mensal" @update:model-value="form.clearErrors('taxa_mensal')" rotulo="Taxa mensal (%)" inputmode="decimal" ajuda="Ex.: 2,5" obrigatorio :erro="form.errors.taxa_mensal" />
                </div>
                <div class="linha">
                    <CampoSelecao v-model="form.metodo" @update:model-value="form.clearErrors('metodo')" rotulo="Método de cálculo" :opcoes="opcoesMetodo" obrigatorio :erro="form.errors.metodo" />
                    <CampoTexto v-model="form.quantidade_parcelas" @update:model-value="form.clearErrors('quantidade_parcelas')" rotulo="Número de parcelas" tipo="number" inputmode="numeric" obrigatorio :erro="form.errors.quantidade_parcelas" />
                </div>
                <CampoTexto v-model="form.primeiro_vencimento" @update:model-value="form.clearErrors('primeiro_vencimento')" rotulo="Primeiro vencimento" tipo="date" obrigatorio :erro="form.errors.primeiro_vencimento" />
                <p class="nota">As parcelas são geradas ao salvar, uma por mês, no mesmo dia do primeiro vencimento.</p>
                <div class="acoes">
                    <Botao tipo="submit" variante="primaria" :carregando="form.processing">Salvar empréstimo</Botao>
                    <Link href="/emprestimos" class="cancelar">Cancelar</Link>
                </div>
            </form>
            <EstadoVazio v-else titulo="Cadastre um cliente primeiro" descricao="O empréstimo precisa de um cliente." :icone="Users">
                <Botao href="/clientes" variante="primaria">Ir para Clientes</Botao>
            </EstadoVazio>
        </Card>
    </PainelLayout>
</template>

<style scoped>
.novo {
    max-width: 44rem;
}

.formulario {
    display: grid;
    gap: var(--espaco-4);
}

.linha {
    display: grid;
    gap: var(--espaco-4);
}

.nota {
    color: var(--cor-texto-suave);
    font-size: var(--texto-sm);
}

.acoes {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--espaco-4);
}

.cancelar {
    display: inline-flex;
    align-items: center;
    min-height: var(--alvo-toque);
    color: var(--cor-texto-suave);
    font-weight: var(--peso-medio);
}

@media (min-width: 640px) {
    .linha {
        grid-template-columns: 1fr 1fr;
    }
}
</style>
