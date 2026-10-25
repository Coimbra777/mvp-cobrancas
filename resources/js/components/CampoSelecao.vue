<script setup lang="ts">
import { computed, useId } from 'vue';

export interface OpcaoSelecao {
    valor: string | number;
    rotulo: string;
}

const props = withDefaults(
    defineProps<{
        rotulo: string;
        opcoes: OpcaoSelecao[];
        placeholder?: string;
        erro?: string | null;
        obrigatorio?: boolean;
    }>(),
    { placeholder: 'Selecione…', erro: null, obrigatorio: false },
);

const valor = defineModel<string | number | null>({ default: null });

const idCampo = `selecao-${useId()}`;
const idErro = computed(() => (props.erro ? `${idCampo}-erro` : undefined));
</script>

<template>
    <div class="campo" :class="{ 'campo--erro': erro }">
        <label class="campo__rotulo" :for="idCampo">{{ rotulo }}</label>
        <select
            :id="idCampo"
            v-model="valor"
            class="campo__entrada"
            :required="obrigatorio"
            :aria-invalid="erro ? 'true' : undefined"
            :aria-describedby="idErro"
        >
            <option :value="null" disabled>{{ placeholder }}</option>
            <option v-for="opcao in opcoes" :key="opcao.valor" :value="opcao.valor">{{ opcao.rotulo }}</option>
        </select>
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

.campo__entrada {
    width: 100%;
    min-height: var(--alvo-toque);
    padding: 0 var(--espaco-3);
    background: var(--cor-superficie);
    border: 1px solid var(--cor-borda-controle);
    border-radius: var(--raio-md);
}

.campo--erro .campo__entrada {
    border-color: var(--cor-perigo-texto);
    border-width: 2px;
}

.campo__mensagem-erro {
    font-size: var(--texto-sm);
    font-weight: var(--peso-medio);
    color: var(--cor-perigo-texto);
}
</style>
