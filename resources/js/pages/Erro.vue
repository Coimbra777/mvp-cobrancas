<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import Botao from '@/components/Botao.vue';
import VisitanteLayout from '@/layouts/VisitanteLayout.vue';

const props = defineProps<{ status: 404 | 419 | 500 | 503 }>();

const MENSAGENS = {
    404: { titulo: 'Página não encontrada', texto: 'O endereço pode estar errado ou a página foi removida.' },
    419: { titulo: 'Sua sessão expirou', texto: 'Recarregue a página e tente de novo.' },
    500: { titulo: 'Algo deu errado', texto: 'Tente de novo em instantes. O problema foi registrado.' },
    503: { titulo: 'Em manutenção', texto: 'A Caderneta volta em alguns minutos.' },
} as const;

const mensagem = computed(() => MENSAGENS[props.status] ?? MENSAGENS[500]);

function recarregar() {
    window.location.reload();
}
</script>

<template>
    <Head :title="mensagem.titulo" />
    <VisitanteLayout>
        <p class="erro__codigo">Erro {{ status }}</p>
        <h1 class="erro__titulo">{{ mensagem.titulo }}</h1>
        <p class="erro__texto">{{ mensagem.texto }}</p>
        <div class="erro__acoes">
            <Botao v-if="status === 419" variante="primaria" @click="recarregar">Recarregar a página</Botao>
            <Link href="/painel" class="erro__link">Ir para o Painel</Link>
        </div>
    </VisitanteLayout>
</template>

<style scoped>
.erro__codigo {
    color: var(--cor-texto-suave);
    font-size: var(--texto-sm);
    font-weight: var(--peso-medio);
}

.erro__titulo {
    margin: var(--espaco-1) 0 var(--espaco-3);
    font-size: var(--texto-2xl);
    font-weight: var(--peso-semi);
}

.erro__texto {
    color: var(--cor-texto-suave);
    margin-bottom: var(--espaco-6);
}

.erro__acoes {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--espaco-4);
}

.erro__link {
    display: inline-flex;
    align-items: center;
    min-height: var(--alvo-toque);
    color: var(--cor-marca);
    font-weight: var(--peso-semi);
}
</style>
