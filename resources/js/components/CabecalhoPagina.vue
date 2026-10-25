<script lang="ts">
// Na primeira página carregada o foco fica onde o navegador colocou;
// nas navegações seguintes ele vai para o título (anunciado por leitores de tela).
let primeiraPagina = true;

const FUSO = 'America/Fortaleza';

export function dataPorExtenso(data: Date): string {
    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'full', timeZone: FUSO }).format(data);
}

export function dataIso(data: Date): string {
    // en-CA formata como AAAA-MM-DD.
    return new Intl.DateTimeFormat('en-CA', { timeZone: FUSO }).format(data);
}
</script>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

defineProps<{ titulo: string }>();

const titulo = ref<HTMLHeadingElement | null>(null);
const agora = new Date();

onMounted(() => {
    if (!primeiraPagina) {
        titulo.value?.focus();
    }
    primeiraPagina = false;
});
</script>

<template>
    <Head :title="$props.titulo" />
    <header class="cabecalho">
        <div class="cabecalho__textos">
            <h1 ref="titulo" class="cabecalho__titulo" tabindex="-1">{{ $props.titulo }}</h1>
            <p class="cabecalho__data">
                <time :datetime="dataIso(agora)">{{ dataPorExtenso(agora) }}</time>
            </p>
        </div>
        <div v-if="$slots.default" class="cabecalho__acoes">
            <slot />
        </div>
    </header>
</template>

<style scoped>
.cabecalho {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: var(--espaco-4);
    margin-bottom: var(--espaco-6);
}

.cabecalho__titulo {
    font-size: var(--texto-2xl);
    font-weight: var(--peso-semi);
}

.cabecalho__data {
    margin-top: var(--espaco-1);
    color: var(--cor-texto-suave);
    font-size: var(--texto-sm);
}

.cabecalho__data::first-letter {
    text-transform: uppercase;
}

.cabecalho__acoes {
    display: flex;
    flex-wrap: wrap;
    gap: var(--espaco-2);
}

@media (min-width: 1024px) {
    .cabecalho__titulo {
        font-size: var(--texto-3xl);
    }
}
</style>
