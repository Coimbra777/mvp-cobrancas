<script setup lang="ts">
withDefaults(defineProps<{ como?: 'div' | 'section' | 'article' | 'li' }>(), { como: 'div' });
</script>

<template>
    <component :is="como" class="card">
        <slot />
    </component>
</template>

<style scoped>
.card {
    background: var(--cor-superficie);
    border: 1px solid var(--cor-borda);
    border-radius: var(--raio-lg);
    box-shadow: var(--sombra-card);
    padding: var(--espaco-5);
}
</style>
