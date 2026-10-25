<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Users } from '@lucide/vue';

import Botao from '@/components/Botao.vue';
import CabecalhoPagina from '@/components/CabecalhoPagina.vue';
import CampoTexto from '@/components/CampoTexto.vue';
import Card from '@/components/Card.vue';
import EstadoVazio from '@/components/EstadoVazio.vue';
import TabelaResponsiva from '@/components/TabelaResponsiva.vue';
import { telefone } from '@/formatos';
import PainelLayout from '@/layouts/PainelLayout.vue';

interface Cliente {
    id: number;
    nome: string;
    telefone: string;
}

defineProps<{ clientes: Cliente[] }>();

const form = useForm({ nome: '', telefone: '' });

function cadastrar() {
    form.post('/clientes', { preserveScroll: true, onSuccess: () => form.reset() });
}

const colunas = [
    { chave: 'nome', rotulo: 'Nome' },
    { chave: 'telefone', rotulo: 'Telefone' },
];
</script>

<template>
    <PainelLayout>
        <CabecalhoPagina titulo="Clientes" />

        <div class="clientes">
            <Card como="section" aria-labelledby="titulo-novo-cliente">
                <h2 id="titulo-novo-cliente" class="titulo-secao">Novo cliente</h2>
                <form class="formulario" novalidate @submit.prevent="cadastrar">
                    <CampoTexto v-model="form.nome" @update:model-value="form.clearErrors('nome')" rotulo="Nome" autocomplete="off" obrigatorio :erro="form.errors.nome" />
                    <CampoTexto
                        v-model="form.telefone" @update:model-value="form.clearErrors('telefone')"
                        rotulo="Celular com DDD"
                        tipo="tel"
                        inputmode="tel"
                        autocomplete="off"
                        ajuda="Ex.: (85) 99999-0000"
                        obrigatorio
                        :erro="form.errors.telefone"
                    />
                    <Botao tipo="submit" variante="primaria" :carregando="form.processing">Cadastrar</Botao>
                </form>
            </Card>

            <Card como="section" aria-labelledby="titulo-lista-clientes">
                <h2 id="titulo-lista-clientes" class="titulo-secao">Clientes cadastrados</h2>
                <TabelaResponsiva
                    v-if="clientes.length"
                    legenda="Clientes cadastrados"
                    :colunas="colunas"
                    :linhas="clientes as unknown as Record<string, unknown>[]"
                    :chave-linha="(linha) => linha.id as number"
                >
                    <template #celula-telefone="{ valor }">{{ telefone(valor as string) }}</template>
                </TabelaResponsiva>
                <EstadoVazio
                    v-else
                    titulo="Nenhum cliente ainda"
                    descricao="Cadastre o primeiro cliente no formulário."
                    :icone="Users"
                    :nivel-titulo="3"
                />
            </Card>
        </div>
    </PainelLayout>
</template>

<style scoped>
.clientes {
    display: grid;
    gap: var(--espaco-5);
}

.titulo-secao {
    font-size: var(--texto-lg);
    font-weight: var(--peso-semi);
    margin-bottom: var(--espaco-4);
}

.formulario {
    display: grid;
    gap: var(--espaco-4);
}

@media (min-width: 1024px) {
    .clientes {
        grid-template-columns: minmax(18rem, 1fr) 2fr;
        align-items: start;
    }
}
</style>
