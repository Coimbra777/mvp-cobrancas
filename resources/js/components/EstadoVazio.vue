<script setup lang="ts">
import { Inbox } from '@lucide/vue';
import type { Component } from 'vue';

withDefaults(defineProps<{ titulo: string; descricao?: string; icone?: Component; nivelTitulo?: 2 | 3 }>(), {
    icone: () => Inbox,
    nivelTitulo: 2,
});
</script>

<template>
    <div class="vazio">
        <component :is="icone" class="vazio__icone" :size="32" aria-hidden="true" />
        <component :is="`h${nivelTitulo}`" class="vazio__titulo">{{ titulo }}</component>
        <p v-if="descricao" class="vazio__descricao">{{ descricao }}</p>
        <div v-if="$slots.default" class="vazio__acoes">
            <slot />
        </div>
    </div>
</template>

<style scoped>
.vazio {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--espaco-2);
    padding: var(--espaco-10) var(--espaco-4);
    text-align: center;
}

.vazio__icone {
    color: var(--cor-texto-suave);
}

.vazio__titulo {
    font-size: var(--texto-lg);
    font-weight: var(--peso-semi);
}

.vazio__descricao {
    max-width: 32rem;
    color: var(--cor-texto-suave);
}

.vazio__acoes {
    margin-top: var(--espaco-3);
}
</style>
