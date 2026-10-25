<script setup lang="ts">
withDefaults(defineProps<{ rotulo?: string; linhas?: number }>(), { rotulo: 'Carregando…', linhas: 3 });
</script>

<template>
    <div class="carregando" role="status" aria-busy="true">
        <span class="sr-only">{{ rotulo }}</span>
        <div v-for="n in linhas" :key="n" class="carregando__linha" aria-hidden="true" />
    </div>
</template>

<style scoped>
.carregando {
    display: flex;
    flex-direction: column;
    gap: var(--espaco-3);
}

.carregando__linha {
    height: 1rem;
    border-radius: var(--raio-sm);
    background: var(--cor-superficie-suave);
    animation: pulsar 1.2s ease-in-out infinite;
}

.carregando__linha:last-child {
    width: 60%;
}

@keyframes pulsar {
    50% {
        opacity: 0.5;
    }
}
</style>
