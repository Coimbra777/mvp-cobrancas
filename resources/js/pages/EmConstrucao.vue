<script setup lang="ts">
import { Hammer } from '@lucide/vue';

import CabecalhoPagina from '@/components/CabecalhoPagina.vue';
import Card from '@/components/Card.vue';
import EstadoVazio from '@/components/EstadoVazio.vue';
import PainelLayout from '@/layouts/PainelLayout.vue';

// Página provisória das áreas que ainda não existem (substituída em cada change).
defineProps<{ titulo: string }>();
</script>

<template>
    <PainelLayout>
        <CabecalhoPagina :titulo="titulo" />
        <Card como="section">
            <EstadoVazio
                titulo="Em construção"
                descricao="Esta área ainda está sendo preparada e aparece aqui em breve."
                :icone="Hammer"
            />
        </Card>
    </PainelLayout>
</template>
