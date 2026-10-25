<script setup lang="ts">
import { NotebookPen } from '@lucide/vue';

import Card from '@/components/Card.vue';
</script>

<template>
    <div class="visitante">
        <main id="conteudo" class="visitante__principal">
            <p class="visitante__marca">
                <NotebookPen :size="28" aria-hidden="true" />
                Caderneta
            </p>
            <Card como="section" class="visitante__cartao">
                <slot />
            </Card>
        </main>
    </div>
</template>

<style scoped>
.visitante {
    display: flex;
    min-height: 100vh;
    align-items: center;
    justify-content: center;
    padding: var(--espaco-6) var(--espaco-4);
}

.visitante__principal {
    width: 100%;
    max-width: 26rem;
}

.visitante__marca {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--espaco-2);
    margin-bottom: var(--espaco-6);
    color: var(--cor-marca);
    font-size: var(--texto-2xl);
    font-weight: var(--peso-forte);
}

.visitante__cartao {
    padding: var(--espaco-6);
}
</style>
