<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LogOut, NotebookPen } from '@lucide/vue';
import { computed } from 'vue';

import { AREAS, areaAtiva } from '@/navegacao';

const pagina = usePage();
const nomeUsuario = computed(() => pagina.props.auth.usuario?.nome ?? '');
const flash = computed(() => pagina.props.flash);
const ativa = (href: string) => areaAtiva(href, pagina.url);
</script>

<template>
    <div class="painel">
        <a href="#conteudo" class="painel__pular">Pular para o conteúdo</a>

        <aside class="lateral">
            <p class="lateral__marca">
                <NotebookPen :size="26" aria-hidden="true" />
                Caderneta
            </p>
            <nav aria-label="Principal">
                <ul class="lista">
                    <li v-for="area in AREAS" :key="area.href">
                        <Link :href="area.href" class="lateral__link" :aria-current="ativa(area.href) ? 'page' : undefined">
                            <component :is="area.icone" :size="20" aria-hidden="true" />
                            {{ area.rotulo }}
                        </Link>
                    </li>
                </ul>
            </nav>
            <div class="lateral__rodape">
                <p class="lateral__usuario">{{ nomeUsuario }}</p>
                <Link href="/sair" method="post" as="button" class="lateral__link lateral__sair">
                    <LogOut :size="20" aria-hidden="true" />
                    Sair
                </Link>
            </div>
        </aside>

        <main id="conteudo" class="painel__conteudo" tabindex="-1">
            <p v-if="flash.sucesso" class="aviso aviso--sucesso" role="status">{{ flash.sucesso }}</p>
            <p v-if="flash.erro" class="aviso aviso--erro" role="alert">{{ flash.erro }}</p>
            <slot />
        </main>

        <nav class="inferior" aria-label="Principal no celular">
            <ul class="lista inferior__lista">
                <li v-for="area in AREAS" :key="area.href">
                    <Link :href="area.href" class="inferior__item" :aria-current="ativa(area.href) ? 'page' : undefined">
                        <component :is="area.icone" :size="22" aria-hidden="true" />
                        <span>{{ area.rotulo }}</span>
                    </Link>
                </li>
                <li>
                    <Link href="/sair" method="post" as="button" class="inferior__item">
                        <LogOut :size="22" aria-hidden="true" />
                        <span>Sair</span>
                    </Link>
                </li>
            </ul>
        </nav>
    </div>
</template>

<style scoped>
.painel {
    min-height: 100vh;
}

.painel__pular {
    position: absolute;
    top: var(--espaco-2);
    left: var(--espaco-2);
    z-index: 100;
    padding: var(--espaco-3) var(--espaco-4);
    background: var(--cor-marca);
    color: var(--cor-sobre-marca);
    border-radius: var(--raio-md);
    font-weight: var(--peso-semi);
    transform: translateY(-200%);
}

.painel__pular:focus {
    transform: none;
}

.painel__conteudo {
    padding: var(--espaco-5) var(--espaco-4);
    /* Espaço para a barra inferior fixa no celular */
    padding-bottom: calc(var(--altura-barra-inferior) + env(safe-area-inset-bottom) + var(--espaco-6));
}

.painel__conteudo:focus {
    outline: none;
}

.aviso {
    margin-bottom: var(--espaco-4);
    padding: var(--espaco-3) var(--espaco-4);
    border-radius: var(--raio-md);
    font-weight: var(--peso-medio);
}

.aviso--sucesso {
    background: var(--cor-sucesso-fundo);
    color: var(--cor-sucesso-texto);
}

.aviso--erro {
    background: var(--cor-perigo-fundo);
    color: var(--cor-perigo-texto);
}

.lista {
    margin: 0;
    padding: 0;
    list-style: none;
}

/* ---------- Barra lateral (desktop) ---------- */
.lateral {
    display: none;
}

.lateral__marca {
    display: flex;
    align-items: center;
    gap: var(--espaco-2);
    padding: 0 var(--espaco-3);
    margin-bottom: var(--espaco-8);
    color: var(--cor-marca);
    font-size: var(--texto-xl);
    font-weight: var(--peso-forte);
}

.lateral .lista {
    display: flex;
    flex-direction: column;
    gap: var(--espaco-1);
}

.lateral__link {
    display: flex;
    align-items: center;
    gap: var(--espaco-3);
    width: 100%;
    min-height: var(--alvo-toque);
    padding: 0 var(--espaco-3);
    border: 0;
    border-radius: var(--raio-md);
    background: transparent;
    color: var(--cor-texto);
    font-size: var(--texto-md);
    font-weight: var(--peso-medio);
    text-decoration: none;
    cursor: pointer;
}

.lateral__link:hover {
    background: var(--cor-superficie-suave);
}

.lateral__link[aria-current='page'] {
    background: var(--cor-primaria);
    color: var(--cor-sobre-primaria);
    font-weight: var(--peso-semi);
}

.lateral__rodape {
    display: flex;
    flex-direction: column;
    gap: var(--espaco-2);
    margin-top: auto;
}

.lateral__usuario {
    padding: 0 var(--espaco-3);
    font-weight: var(--peso-semi);
}

.lateral__sair {
    color: var(--cor-texto-suave);
}

/* ---------- Barra inferior (celular) ---------- */
.inferior {
    position: fixed;
    inset: auto 0 0 0;
    z-index: 50;
    background: var(--cor-superficie);
    border-top: 1px solid var(--cor-borda);
    padding-bottom: env(safe-area-inset-bottom);
}

.inferior__lista {
    display: grid;
    /* minmax(0, 1fr): com texto ampliado os rótulos quebram em vez de vazar */
    grid-template-columns: repeat(4, minmax(0, 1fr));
    min-height: var(--altura-barra-inferior);
}

.inferior__item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    width: 100%;
    height: 100%;
    min-height: var(--alvo-toque);
    padding: var(--espaco-1) 2px;
    border: 0;
    background: transparent;
    color: var(--cor-texto-suave);
    font-size: var(--texto-xs);
    font-weight: var(--peso-medio);
    line-height: 1.15;
    text-align: center;
    text-decoration: none;
    overflow-wrap: anywhere;
    hyphens: auto;
    cursor: pointer;
}

.inferior__item svg {
    flex: none;
}

.inferior__item[aria-current='page'] {
    color: var(--cor-texto);
    font-weight: var(--peso-semi);
}

.inferior__item[aria-current='page'] svg {
    color: var(--cor-marca);
}

/* ---------- Desktop: a partir de 1024px ---------- */
@media (min-width: 1024px) {
    .painel {
        display: grid;
        grid-template-columns: var(--largura-sidebar) 1fr;
    }

    .lateral {
        position: sticky;
        top: 0;
        display: flex;
        flex-direction: column;
        height: 100vh;
        padding: var(--espaco-6) var(--espaco-4);
        background: var(--cor-superficie);
        border-right: 1px solid var(--cor-borda);
        overflow-y: auto;
    }

    .inferior {
        display: none;
    }

    .painel__conteudo {
        width: 100%;
        max-width: var(--largura-conteudo);
        padding: var(--espaco-8) var(--espaco-8) var(--espaco-10);
    }
}
</style>
