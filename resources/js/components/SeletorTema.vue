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
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--espaco-1);
    min-height: var(--alvo-toque);
    padding: 0 var(--espaco-2);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--raio-sm);
    font-size: var(--texto-xs);
    font-weight: var(--peso-medio);
    cursor: pointer;
}

.tema__opcao[aria-pressed='true'] {
    background: var(--cor-superficie);
    border-color: var(--cor-borda-controle);
    font-weight: var(--peso-semi);
}
</style>
