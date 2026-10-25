<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue';

import Botao from './Botao.vue';

withDefaults(defineProps<{ titulo?: string; mensagem?: string }>(), {
    titulo: 'Não foi possível carregar',
    mensagem: 'Verifique sua conexão e tente de novo.',
});

const emit = defineEmits<{ tentarDeNovo: [] }>();

// role="alert": anunciado ao aparecer, sem mover o foco.
</script>

<template>
    <div class="erro" role="alert">
        <TriangleAlert class="erro__icone" :size="28" aria-hidden="true" />
        <p class="erro__titulo">{{ titulo }}</p>
        <p class="erro__mensagem">{{ mensagem }}</p>
        <Botao variante="secundaria" @click="emit('tentarDeNovo')">Tentar de novo</Botao>
    </div>
</template>

<style scoped>
.erro {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--espaco-2);
    padding: var(--espaco-8) var(--espaco-4);
    text-align: center;
}

.erro__icone {
    color: var(--cor-perigo-texto);
}

.erro__titulo {
    font-size: var(--texto-lg);
    font-weight: var(--peso-semi);
}

.erro__mensagem {
    color: var(--cor-texto-suave);
    margin-bottom: var(--espaco-2);
}
</style>
