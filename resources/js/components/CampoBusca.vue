<script setup lang="ts">
import { Search } from '@lucide/vue';

import CampoTexto from './CampoTexto.vue';

withDefaults(defineProps<{ rotulo?: string; id?: string }>(), { rotulo: 'Buscar' });

const termo = defineModel<string>({ default: '' });
</script>

<template>
    <div role="search">
        <CampoTexto :id="id" v-model="termo" :rotulo="rotulo" tipo="search" autocomplete="off" :icone="Search" />
    </div>
</template>
