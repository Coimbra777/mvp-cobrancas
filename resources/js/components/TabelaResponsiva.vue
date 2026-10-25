<script setup lang="ts" generic="T extends Record<string, unknown>">
export interface ColunaTabela {
    chave: string;
    rotulo: string;
    alinhamento?: 'inicio' | 'fim';
}

defineProps<{
    // Descrição da tabela (caption). Fica oculta visualmente quando a página já tem um título.
    legenda: string;
    legendaVisivel?: boolean;
    colunas: ColunaTabela[];
    linhas: T[];
    chaveLinha: (linha: T) => string | number;
}>();

defineSlots<Record<`celula-${string}`, (props: { linha: T; valor: unknown }) => unknown>>();
</script>

<template>
    <div class="tabela">
        <!-- Papéis explícitos: alguns navegadores perdem a semântica de tabela ao mudar o display no celular. -->
        <table role="table">
            <caption :class="{ 'sr-only': !legendaVisivel }">{{ legenda }}</caption>
            <thead role="rowgroup">
                <tr role="row">
                    <th
                        v-for="coluna in colunas"
                        :key="coluna.chave"
                        scope="col"
                        role="columnheader"
                        :class="{ 'tabela--fim': coluna.alinhamento === 'fim' }"
                    >
                        {{ coluna.rotulo }}
                    </th>
                </tr>
            </thead>
            <tbody role="rowgroup">
                <tr v-for="linha in linhas" :key="chaveLinha(linha)" role="row">
                    <td
                        v-for="coluna in colunas"
                        :key="coluna.chave"
                        role="cell"
                        :data-rotulo="coluna.rotulo"
                        :class="{ 'tabela--fim': coluna.alinhamento === 'fim' }"
                    >
                        <slot :name="`celula-${coluna.chave}`" :linha="linha" :valor="linha[coluna.chave]">
                            {{ linha[coluna.chave] }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
.tabela table {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--texto-sm);
}

caption {
    text-align: left;
    font-weight: var(--peso-semi);
    padding-bottom: var(--espaco-2);
}

th {
    text-align: left;
    font-weight: var(--peso-medio);
    color: var(--cor-texto-suave);
    padding: var(--espaco-2) var(--espaco-3);
    border-bottom: 1px solid var(--cor-borda);
}

td {
    padding: var(--espaco-3);
    border-bottom: 1px solid var(--cor-borda);
}

.tabela--fim {
    text-align: right;
}

/* Abaixo de 640px cada linha vira um cartão com "rótulo: valor". */
@media (max-width: 639.98px) {
    table,
    tbody,
    tr,
    td {
        display: block;
        width: 100%;
    }

    thead {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
    }

    tr {
        margin-bottom: var(--espaco-3);
        background: var(--cor-superficie);
        border: 1px solid var(--cor-borda);
        border-radius: var(--raio-md);
        padding: var(--espaco-2) var(--espaco-3);
    }

    td {
        display: flex;
        justify-content: space-between;
        gap: var(--espaco-4);
        padding: var(--espaco-2) 0;
        text-align: right;
    }

    tr td:last-child {
        border-bottom: none;
    }

    td::before {
        content: attr(data-rotulo);
        color: var(--cor-texto-suave);
        font-weight: var(--peso-medio);
        text-align: left;
    }
}
</style>
