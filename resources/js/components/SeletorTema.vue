<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';

import { type PreferenciaTema, useTema } from '@/composables/useTema';

const { preferencia, definirPreferencia } = useTema();

const opcoes: Array<{ valor: PreferenciaTema; rotulo: string; icone: typeof Sun }> = [
    { valor: 'claro', rotulo: 'Claro', icone: Sun },
    { valor: 'escuro', rotulo: 'Escuro', icone: Moon },
    { valor: 'auto', rotulo: 'Automático', icone: Monitor },
];
</script>

<template>
    <div class="tema" role="group" aria-label="Tema">
        <button
            v-for="opcao in opcoes"
            :key="opcao.valor"
            type="button"
            class="tema__opcao"
            :aria-pressed="preferencia === opcao.valor ? 'true' : 'false'"
            @click="definirPreferencia(opcao.valor)"
        >
            <component :is="opcao.icone" :size="16" aria-hidden="true" />
            {{ opcao.rotulo }}
        </button>
    </div>
</template>

<style scoped>
.tema {
    display: flex;
    gap: var(--espaco-1);
    padding: var(--espaco-1);
    background: var(--cor-superficie-suave);
    border-radius: var(--raio-md);
}

.tema__opcao {
    /* Largura pelo conteúdo: "Automático" ocupa mais que "Claro" sem quebrar a palavra */
    flex: 1 1 auto;
    min-width: 0;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    min-height: var(--alvo-toque);
    padding: var(--espaco-1);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--raio-sm);
    font-size: var(--texto-xs);
    font-weight: var(--peso-medio);
    line-height: 1.2;
    overflow-wrap: anywhere;
    cursor: pointer;
}

.tema__opcao svg {
    flex: none;
}

.tema__opcao[aria-pressed='true'] {
    background: var(--cor-superficie);
    border-color: var(--cor-borda-controle);
    font-weight: var(--peso-semi);
}
</style>
