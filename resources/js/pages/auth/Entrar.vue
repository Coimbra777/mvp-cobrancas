<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

import Botao from '@/components/Botao.vue';
import CampoTexto from '@/components/CampoTexto.vue';
import VisitanteLayout from '@/layouts/VisitanteLayout.vue';

type CampoComFoco = InstanceType<typeof CampoTexto>;

const form = useForm({
    email: '',
    senha: '',
    manter_conectado: false,
});

const campoEmail = ref<CampoComFoco | null>(null);
const campoSenha = ref<CampoComFoco | null>(null);

// "credenciais" vem do servidor (erro genérico ou bloqueio) e não é um campo do formulário.
const erros = computed(() => form.errors as Record<string, string | undefined>);

function enviar() {
    form.post('/entrar', {
        onError: async (resposta: Record<string, string>) => {
            await nextTick();
            if (resposta.senha && !resposta.email && !resposta.credenciais) {
                campoSenha.value?.focar();
            } else {
                campoEmail.value?.focar();
            }
        },
        onFinish: () => form.reset('senha'),
    });
}
</script>

<template>
    <Head title="Entrar" />
    <VisitanteLayout>
        <h1 class="entrar__titulo">Entrar</h1>

        <div v-if="erros.credenciais" class="entrar__alerta" role="alert">
            {{ erros.credenciais }}
        </div>

        <form class="entrar__form" novalidate @submit.prevent="enviar">
            <CampoTexto
                ref="campoEmail"
                v-model="form.email"
                rotulo="E-mail"
                tipo="email"
                autocomplete="username"
                obrigatorio
                :erro="erros.email"
            />
            <CampoTexto
                ref="campoSenha"
                v-model="form.senha"
                rotulo="Senha"
                tipo="password"
                autocomplete="current-password"
                obrigatorio
                :erro="erros.senha"
            />
            <label class="entrar__lembrar">
                <input v-model="form.manter_conectado" type="checkbox" />
                Manter conectado
            </label>
            <Botao tipo="submit" variante="primaria" largura-total :carregando="form.processing">Entrar</Botao>
        </form>
    </VisitanteLayout>
</template>

<style scoped>
.entrar__titulo {
    font-size: var(--texto-2xl);
    font-weight: var(--peso-semi);
    margin-bottom: var(--espaco-5);
}

.entrar__alerta {
    margin-bottom: var(--espaco-4);
    padding: var(--espaco-3) var(--espaco-4);
    border-radius: var(--raio-md);
    background: var(--cor-perigo-fundo);
    color: var(--cor-perigo-texto);
    font-weight: var(--peso-medio);
}

.entrar__form {
    display: flex;
    flex-direction: column;
    gap: var(--espaco-4);
}

.entrar__lembrar {
    display: flex;
    align-items: center;
    gap: var(--espaco-2);
    min-height: var(--alvo-toque);
    font-size: var(--texto-sm);
    cursor: pointer;
}

.entrar__lembrar input {
    width: 1.25rem;
    height: 1.25rem;
    accent-color: var(--cor-marca);
}
</style>
