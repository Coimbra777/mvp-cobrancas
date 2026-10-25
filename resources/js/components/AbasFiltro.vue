<script setup lang="ts">
export interface OpcaoFiltro {
    valor: string;
    rotulo: string;
    contagem?: number;
}

defineProps<{
    // Nome do grupo para leitores de tela, ex.: "Filtrar parcelas".
    rotulo: string;
    opcoes: OpcaoFiltro[];
}>();

const selecionado = defineModel<string>({ required: true });
</script>

<template>
    <div class="abas" role="group" :aria-label="rotulo">
        <button
            v-for="opcao in opcoes"
            :key="opcao.valor"
            type="button"
            class="abas__opcao"
            :aria-pressed="selecionado === opcao.valor ? 'true' : 'false'"
            @click="selecionado = opcao.valor"
        >
            {{ opcao.rotulo }}
            <span v-if="opcao.contagem !== undefined" class="abas__contagem">{{ opcao.contagem }}</span>
        </button>
    </div>
</template>

<style scoped>
.abas {
    display: flex;
    flex-wrap: wrap;
    gap: var(--espaco-2);
}

.abas__opcao {
    display: inline-flex;
    align-items: center;
    gap: var(--espaco-2);
    min-height: var(--alvo-toque);
    padding: 0 var(--espaco-4);
    background: var(--cor-superficie);
    border: 1px solid var(--cor-borda);
    border-radius: var(--raio-md);
    font-size: var(--texto-sm);
    font-weight: var(--peso-medio);
    cursor: pointer;
}

.abas__opcao[aria-pressed='true'] {
    background: var(--cor-marca);
    border-color: var(--cor-marca);
    color: var(--cor-sobre-marca);
    font-weight: var(--peso-semi);
}

.abas__contagem {
    font-size: var(--texto-xs);
}
</style>
