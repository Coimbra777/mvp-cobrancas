<script setup lang="ts">
import { computed, ref, useId, type Component } from 'vue';

const props = withDefaults(
    defineProps<{
        rotulo: string;
        tipo?: 'text' | 'email' | 'password' | 'search' | 'tel' | 'number' | 'date';
        erro?: string | null;
        ajuda?: string;
        id?: string;
        autocomplete?: string;
        obrigatorio?: boolean;
        icone?: Component;
        inputmode?: 'text' | 'decimal' | 'numeric' | 'tel' | 'email';
    }>(),
    { tipo: 'text', erro: null, obrigatorio: false },
);

const valor = defineModel<string>({ default: '' });

const idGerado = useId();
const idCampo = computed(() => props.id ?? `campo-${idGerado}`);
const idAjuda = computed(() => `${idCampo.value}-ajuda`);
const idErro = computed(() => `${idCampo.value}-erro`);
const descritoPor = computed(
    () => [props.erro ? idErro.value : null, props.ajuda ? idAjuda.value : null].filter(Boolean).join(' ') || undefined,
);

const entrada = ref<HTMLInputElement | null>(null);

// Usado pelos formulários para levar o foco ao primeiro campo com erro.
defineExpose({ focar: () => entrada.value?.focus() });
</script>

<template>
    <div class="campo" :class="{ 'campo--erro': erro }">
        <label class="campo__rotulo" :for="idCampo">{{ rotulo }}</label>
        <div class="campo__caixa">
            <component :is="icone" v-if="icone" class="campo__icone" :size="18" aria-hidden="true" />
            <input
                :id="idCampo"
                ref="entrada"
                v-model="valor"
                class="campo__entrada"
                :class="{ 'campo__entrada--com-icone': icone }"
                :type="tipo"
                :autocomplete="autocomplete"
                :inputmode="inputmode"
                :required="obrigatorio"
                :aria-invalid="erro ? 'true' : undefined"
                :aria-describedby="descritoPor"
            />
        </div>
        <p v-if="ajuda" :id="idAjuda" class="campo__ajuda">{{ ajuda }}</p>
        <p v-if="erro" :id="idErro" class="campo__mensagem-erro">{{ erro }}</p>
    </div>
</template>

<style scoped>
.campo {
    display: flex;
    flex-direction: column;
    gap: var(--espaco-1);
}

.campo__rotulo {
    font-size: var(--texto-sm);
    font-weight: var(--peso-medio);
}

.campo__caixa {
    position: relative;
}

.campo__icone {
    position: absolute;
    top: 50%;
    left: var(--espaco-3);
    transform: translateY(-50%);
    color: var(--cor-texto-suave);
    pointer-events: none;
}

.campo__entrada {
    width: 100%;
    min-height: var(--alvo-toque);
    padding: 0 var(--espaco-3);
    background: var(--cor-superficie);
    border: 1px solid var(--cor-borda-controle);
    border-radius: var(--raio-md);
}

.campo__entrada--com-icone {
    padding-left: calc(var(--espaco-3) + 18px + var(--espaco-2));
}

.campo--erro .campo__entrada {
    border-color: var(--cor-perigo-texto);
    border-width: 2px;
}

.campo__ajuda {
    font-size: var(--texto-sm);
    color: var(--cor-texto-suave);
}

.campo__mensagem-erro {
    font-size: var(--texto-sm);
    font-weight: var(--peso-medio);
    color: var(--cor-perigo-texto);
}
</style>
