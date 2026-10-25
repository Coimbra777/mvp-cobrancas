<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Ellipsis, LogOut, NotebookPen } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import AvatarIniciais from '@/components/AvatarIniciais.vue';
import SeletorTema from '@/components/SeletorTema.vue';
import { AREAS, AREAS_BARRA_INFERIOR, AREAS_MENU_MAIS, areaAtiva } from '@/navegacao';

const pagina = usePage();
const nomeUsuario = computed(() => pagina.props.auth.usuario?.nome ?? '');
const ativa = (href: string) => areaAtiva(href, pagina.url);
const maisAtivo = computed(() => AREAS_MENU_MAIS.some((area) => ativa(area.href)));

// Menu "Mais" (padrão disclosure): Esc e clique fora fecham; Esc devolve o foco ao botão.
const menuAberto = ref(false);
const botaoMais = ref<HTMLButtonElement | null>(null);
const areaMais = ref<HTMLElement | null>(null);

function fecharMenu(devolverFoco = false) {
    menuAberto.value = false;
    if (devolverFoco) {
        botaoMais.value?.focus();
    }
}

function aoTeclar(evento: KeyboardEvent) {
    if (evento.key === 'Escape' && menuAberto.value) {
        fecharMenu(true);
    }
}

function aoClicarFora(evento: MouseEvent) {
    if (menuAberto.value && areaMais.value && !areaMais.value.contains(evento.target as Node)) {
        fecharMenu();
    }
}

onMounted(() => {
    document.addEventListener('keydown', aoTeclar);
    document.addEventListener('click', aoClicarFora);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', aoTeclar);
    document.removeEventListener('click', aoClicarFora);
});
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
                <ul class="lateral__lista">
                    <li v-for="area in AREAS" :key="area.href">
                        <Link
                            :href="area.href"
                            class="lateral__link"
                            :aria-current="ativa(area.href) ? 'page' : undefined"
                        >
                            <component :is="area.icone" :size="20" aria-hidden="true" />
                            {{ area.rotulo }}
                        </Link>
                    </li>
                </ul>
            </nav>
            <div class="lateral__rodape">
                <SeletorTema />
                <p class="lateral__usuario">
                    <AvatarIniciais :nome="nomeUsuario" decorativo />
                    <span>{{ nomeUsuario }}</span>
                </p>
                <Link href="/sair" method="post" as="button" class="lateral__link lateral__sair">
                    <LogOut :size="20" aria-hidden="true" />
                    Sair
                </Link>
            </div>
        </aside>

        <main id="conteudo" class="painel__conteudo" tabindex="-1">
            <slot />
        </main>

        <nav class="inferior" aria-label="Principal no celular">
            <ul class="inferior__lista">
                <li v-for="area in AREAS_BARRA_INFERIOR" :key="area.href">
                    <Link
                        :href="area.href"
                        class="inferior__item"
                        :aria-current="ativa(area.href) ? 'page' : undefined"
                    >
                        <component :is="area.icone" :size="22" aria-hidden="true" />
                        <span>{{ area.rotulo }}</span>
                    </Link>
                </li>
                <li ref="areaMais" class="inferior__mais">
                    <button
                        ref="botaoMais"
                        type="button"
                        class="inferior__item"
                        :class="{ 'inferior__item--ativo': maisAtivo }"
                        :aria-expanded="menuAberto ? 'true' : 'false'"
                        aria-controls="menu-mais"
                        @click="menuAberto = !menuAberto"
                    >
                        <Ellipsis :size="22" aria-hidden="true" />
                        <span>Mais</span>
                    </button>
                    <div v-show="menuAberto" id="menu-mais" class="inferior__menu">
                        <ul class="inferior__menu-lista">
                            <li v-for="area in AREAS_MENU_MAIS" :key="area.href">
                                <Link
                                    :href="area.href"
                                    class="lateral__link"
                                    :aria-current="ativa(area.href) ? 'page' : undefined"
                                    @click="fecharMenu()"
                                >
                                    <component :is="area.icone" :size="20" aria-hidden="true" />
                                    {{ area.rotulo }}
                                </Link>
                            </li>
                        </ul>
                        <SeletorTema />
                        <Link href="/sair" method="post" as="button" class="lateral__link lateral__sair">
                            <LogOut :size="20" aria-hidden="true" />
                            Sair
                        </Link>
                    </div>
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

.lateral__lista,
.inferior__lista,
.inferior__menu-lista {
    margin: 0;
    padding: 0;
    list-style: none;
}

.lateral__lista {
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
    gap: var(--espaco-3);
    margin-top: auto;
}

.lateral__usuario {
    display: flex;
    align-items: center;
    gap: var(--espaco-3);
    padding: 0 var(--espaco-1);
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
    /* minmax(0, 1fr): colunas podem encolher; com texto ampliado os rótulos quebram em vez de vazar */
    grid-template-columns: repeat(5, minmax(0, 1fr));
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

.inferior__item[aria-current='page'],
.inferior__item--ativo {
    color: var(--cor-texto);
    font-weight: var(--peso-semi);
}

.inferior__item[aria-current='page'] svg,
.inferior__item--ativo svg {
    color: var(--cor-marca);
}

.inferior__mais {
    position: relative;
}

.inferior__menu {
    position: absolute;
    right: var(--espaco-2);
    bottom: calc(100% + var(--espaco-2));
    display: flex;
    flex-direction: column;
    gap: var(--espaco-3);
    width: min(18rem, calc(100vw - 2 * var(--espaco-2)));
    padding: var(--espaco-3);
    background: var(--cor-superficie);
    border: 1px solid var(--cor-borda-controle);
    border-radius: var(--raio-lg);
    box-shadow: var(--sombra-menu);
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
