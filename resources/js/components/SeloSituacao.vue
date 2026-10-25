<script setup lang="ts">
import { Calendar, CalendarClock, Circle, CircleCheck, CircleDashed, Clock, TriangleAlert } from '@lucide/vue';
import { computed, type Component } from 'vue';

export type Situacao = 'paga' | 'parcial' | 'atrasada' | 'vence-hoje' | 'vence-em-breve' | 'a-vencer' | 'neutro';

type Tom = 'sucesso' | 'atencao' | 'info' | 'perigo' | 'neutro';

const props = defineProps<{
    situacao: Situacao;
    // Texto próprio para a variante "neutro" (ex.: "Rascunho").
    rotulo?: string;
}>();

// Situação sempre por texto + ícone, nunca só por cor.
const SITUACOES: Record<Situacao, { texto: string; tom: Tom; icone: Component }> = {
    paga: { texto: 'Paga', tom: 'sucesso', icone: CircleCheck },
    parcial: { texto: 'Parcial', tom: 'neutro', icone: CircleDashed },
    atrasada: { texto: 'Atrasada', tom: 'perigo', icone: TriangleAlert },
    'vence-hoje': { texto: 'Vence hoje', tom: 'atencao', icone: Clock },
    'vence-em-breve': { texto: 'Vence em breve', tom: 'atencao', icone: CalendarClock },
    'a-vencer': { texto: 'A vencer', tom: 'info', icone: Calendar },
    neutro: { texto: '', tom: 'neutro', icone: Circle },
};

const selo = computed(() => SITUACOES[props.situacao]);
const texto = computed(() => props.rotulo ?? selo.value.texto);
</script>

<template>
    <span class="selo" :class="`selo--${selo.tom}`">
        <component :is="selo.icone" class="selo__icone" :size="14" aria-hidden="true" />
        {{ texto }}
    </span>
</template>

<style scoped>
.selo {
    display: inline-flex;
    align-items: center;
    gap: var(--espaco-1);
    padding: 2px var(--espaco-2);
    border-radius: var(--raio-sm);
    font-size: var(--texto-sm);
    font-weight: var(--peso-semi);
    line-height: 1.4;
    white-space: nowrap;
}

.selo__icone {
    flex: none;
}

.selo--sucesso {
    background: var(--cor-sucesso-fundo);
    color: var(--cor-sucesso-texto);
}
.selo--atencao {
    background: var(--cor-atencao-fundo);
    color: var(--cor-atencao-texto);
}
.selo--info {
    background: var(--cor-info-fundo);
    color: var(--cor-info-texto);
}
.selo--perigo {
    background: var(--cor-perigo-fundo);
    color: var(--cor-perigo-texto);
}
.selo--neutro {
    background: var(--cor-neutro-fundo);
    color: var(--cor-neutro-texto);
}
</style>
