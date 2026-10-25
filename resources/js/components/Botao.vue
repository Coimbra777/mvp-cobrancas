<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LoaderCircle } from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        variante?: 'primaria' | 'secundaria' | 'marca';
        tipo?: 'button' | 'submit';
        carregando?: boolean;
        desabilitado?: boolean;
        larguraTotal?: boolean;
        // Com href, vira um link do Inertia com aparência de botão (navegação, não ação).
        href?: string;
    }>(),
    { variante: 'secundaria', tipo: 'button', carregando: false, desabilitado: false, larguraTotal: false },
);

const emit = defineEmits<{ click: [evento: MouseEvent] }>();

// aria-disabled em vez de disabled: o botão continua focável e anunciado durante o envio.
function aoClicar(evento: MouseEvent) {
    if (props.carregando || props.desabilitado) {
        evento.preventDefault();
        return;
    }
    emit('click', evento);
}
</script>

<template>
    <Link v-if="href" :href="href" class="botao" :class="[`botao--${variante}`, { 'botao--largura-total': larguraTotal }]">
        <slot />
    </Link>
    <button
        v-else
        :type="tipo"
        class="botao"
        :class="[`botao--${variante}`, { 'botao--largura-total': larguraTotal }]"
        :aria-busy="carregando ? 'true' : undefined"
        :aria-disabled="carregando || desabilitado ? 'true' : undefined"
        @click="aoClicar"
    >
        <LoaderCircle v-if="carregando" class="botao__giro" :size="18" aria-hidden="true" />
        <slot />
        <span v-if="carregando" class="sr-only">(carregando)</span>
    </button>
</template>

<style scoped>
.botao {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--espaco-2);
    min-height: var(--alvo-toque);
    padding: 0 var(--espaco-5);
    border: 1px solid transparent;
    border-radius: var(--raio-md);
    font-size: var(--texto-sm);
    font-weight: var(--peso-semi);
    line-height: 1.2;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 120ms ease;
}

.botao--primaria {
    background: var(--cor-primaria);
    color: var(--cor-sobre-primaria);
}
.botao--primaria:hover {
    background: var(--cor-primaria-hover);
}

.botao--secundaria {
    background: var(--cor-superficie-suave);
    color: var(--cor-texto);
    border-color: var(--cor-borda);
}
.botao--secundaria:hover {
    border-color: var(--cor-borda-controle);
}

.botao--marca {
    background: var(--cor-marca);
    color: var(--cor-sobre-marca);
}
.botao--marca:hover {
    background: var(--cor-marca-hover);
}

.botao--largura-total {
    width: 100%;
}

.botao[aria-disabled='true'] {
    cursor: not-allowed;
    opacity: 0.7;
}

.botao__giro {
    animation: girar 0.8s linear infinite;
}

@keyframes girar {
    to {
        transform: rotate(360deg);
    }
}
</style>
