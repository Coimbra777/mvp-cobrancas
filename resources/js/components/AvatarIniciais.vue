<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        nome: string;
        variante?: 'marca' | 'primaria';
        // Use true quando o nome já aparece em texto ao lado do avatar.
        decorativo?: boolean;
    }>(),
    { variante: 'marca', decorativo: false },
);

const iniciais = computed(() => {
    const partes = props.nome.trim().split(/\s+/).filter(Boolean);
    if (partes.length === 0) {
        return '?';
    }
    const primeira = partes[0]!.charAt(0);
    const ultima = partes.length > 1 ? partes[partes.length - 1]!.charAt(0) : '';
    return (primeira + ultima).toLocaleUpperCase('pt-BR');
});
</script>

<template>
    <span
        class="avatar"
        :class="`avatar--${variante}`"
        :role="decorativo ? undefined : 'img'"
        :aria-label="decorativo ? undefined : nome"
        :aria-hidden="decorativo ? 'true' : undefined"
    >
        {{ iniciais }}
    </span>
</template>

<style scoped>
.avatar {
    display: inline-flex;
    flex: none;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: var(--raio-md);
    font-size: var(--texto-md);
    font-weight: var(--peso-semi);
}

.avatar--marca {
    background: var(--cor-marca);
    color: var(--cor-sobre-marca);
}

.avatar--primaria {
    background: var(--cor-primaria);
    color: var(--cor-sobre-primaria);
}
</style>
