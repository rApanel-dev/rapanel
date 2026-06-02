<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { useSafeHtml } from '@/Composables/useSafeHtml.js';

const { sanitizeNews } = useSafeHtml();

const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const props = defineProps({
    navSections:    { type: Array,  default: () => [] },
    currentSection: { type: Object, default: null },
    currentArticle: { type: Object, default: null },
});

// Mantener sidebar entre partial reloads
const sidebarSections = ref(props.navSections ?? []);
watch(() => props.navSections, (v) => { if (v?.length) sidebarSections.value = v; });

// Búsqueda client-side sobre títulos de artículos
const search = ref('');
const filteredSections = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return sidebarSections.value;
    return sidebarSections.value
        .map(s => ({
            ...s,
            articles: s.articles.filter(a => a.title.toLowerCase().includes(q)),
        }))
        .filter(s => s.articles.length || s.title.toLowerCase().includes(q));
});

// Mobile sidebar toggle
const sidebarOpen = ref(false);

// Navegación sin recarga de página completa
const navigating = ref(false);
const goTo = (sectionSlug, articleSlug) => {
    sidebarOpen.value = false;
    navigating.value = true;
    router.get(
        safeRoute('info.wiki.show', { section: sectionSlug, article: articleSlug }),
        {},
        {
            only: ['currentSection', 'currentArticle'],
            preserveState: true,
            preserveScroll: false,
            onFinish: () => { navigating.value = false; },
        }
    );
};

const isActive = (sectionSlug, articleSlug) =>
    props.currentSection?.slug === sectionSlug &&
    props.currentArticle?.slug === articleSlug;

// TOC auto-generado desde H2/H3
const toc = ref([]);
const contentRef = ref(null);

const buildToc = () => {
    if (typeof document === 'undefined' || !props.currentArticle?.content) { toc.value = []; return; }
    const doc = new DOMParser().parseFromString(props.currentArticle.content, 'text/html');
    toc.value = Array.from(doc.querySelectorAll('h2, h3')).map((h, i) => ({
        id: `wh-${i}`, text: h.textContent, level: h.tagName === 'H2' ? 2 : 3,
    }));
};
const injectIds = () => {
    contentRef.value?.querySelectorAll('h2, h3').forEach((h, i) => { h.id = `wh-${i}`; });
};

onMounted(() => { buildToc(); injectIds(); });
watch(() => props.currentArticle, () => { buildToc(); setTimeout(injectIds, 30); });

const scrollTo = (id) => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });

const pageTitle = computed(() =>
    props.currentArticle ? `${props.currentArticle.title} — ${__('Wiki')}` : __('Wiki')
);
</script>

<template>
    <Head :title="pageTitle" />

    <MainLayout :show-bg="true">

        <!-- ── Hero header (mismo patrón que MvpCard / MobDB) ────────────── -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                        📖 {{ __('Wiki') }}
                    </h1>
                    <p class="mt-1.5 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                        {{ __('Server guides, mechanics and information') }}
                    </p>
                </div>

                <!-- Buscador -->
                <div class="shrink-0">
                    <input v-model="search" type="text"
                           :placeholder="__('Search articles...')"
                           class="w-full sm:w-64 rounded-xl bg-rapanel-navy-50 dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-white/10
                                  text-rapanel-text-light dark:text-white text-sm px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                </div>
            </div>
        </div>

        <!-- ── Cuerpo: sidebar + contenido dentro del contenedor ─────────── -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex gap-8 items-start">

                <!-- ── Sidebar (solo desktop) ─────────────────────────────── -->
                <aside class="hidden lg:block w-56 xl:w-64 shrink-0 sticky top-6 self-start
                              bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-sm p-4">

                    <nav class="space-y-5">
                        <div v-if="!filteredSections.length" class="text-sm text-rapanel-text-light/40 dark:text-white/30">
                            {{ __('No results.') }}
                        </div>

                        <div v-for="section in filteredSections" :key="section.slug">
                            <!-- Nombre de la sección (label) -->
                            <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light dark:text-rapanel-text-dark px-2 pb-1.5 mb-1.5 border-b border-rapanel-navy-100 dark:border-white/[0.07] flex items-center gap-1.5">
                                <span>{{ section.icon }}</span>
                                <span>{{ section.title }}</span>
                            </p>

                            <!-- Artículos de la sección -->
                            <div class="space-y-0.5">
                                <button v-for="article in section.articles"
                                        :key="article.slug"
                                        @click="goTo(section.slug, article.slug)"
                                        :class="isActive(section.slug, article.slug)
                                            ? 'bg-rapanel-blue/10 dark:bg-rapanel-blue/15 text-rapanel-blue font-semibold'
                                            : 'text-rapanel-text-light dark:text-rapanel-text-dark hover:bg-rapanel-navy-100 dark:hover:bg-white/[0.06]'"
                                        class="w-full text-left text-sm px-3 py-1.5 rounded-lg transition-colors flex items-center gap-2">
                                    <span class="w-1 h-1 rounded-full bg-current shrink-0 opacity-40"></span>
                                    <span class="truncate">{{ article.title }}</span>
                                </button>

                                <p v-if="!section.articles.length"
                                   class="px-3 py-1 text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 italic">
                                    {{ __('No articles yet.') }}
                                </p>
                            </div>
                        </div>
                    </nav>
                </aside>

                <!-- ── Contenido principal ────────────────────────────────── -->
                <div class="flex-1 min-w-0 bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-sm p-6 lg:p-8">

                    <!-- Botón mobile para abrir sidebar -->
                    <div class="lg:hidden mb-5">
                        <button @click="sidebarOpen = true"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-rapanel-navy-100 dark:border-white/10
                                       text-rapanel-text-light/70 dark:text-white/60 text-sm hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.05] transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
                            </svg>
                            {{ currentSection ? currentSection.icon + ' ' + currentSection.title : __('Menu') }}
                        </button>
                    </div>

                    <!-- Spinner de carga -->
                    <div v-if="navigating" class="flex items-center gap-3 text-sm text-rapanel-text-light/50 dark:text-white/40 py-12">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ __('Loading...') }}
                    </div>

                    <!-- Estado vacío (sin artículo seleccionado) -->
                    <div v-if="!currentArticle && !navigating" class="py-20 text-center">
                        <p class="text-6xl mb-5">📖</p>
                        <h2 class="text-2xl font-display font-bold text-rapanel-navy-900 dark:text-white mb-2">
                            {{ __('Wiki') }}
                        </h2>
                        <p class="text-rapanel-text-light/55 dark:text-white/45 text-sm max-w-sm mx-auto">
                            {{ __('Select an article from the sidebar to get started.') }}
                        </p>
                    </div>

                    <!-- Artículo -->
                    <article v-if="currentArticle && !navigating" class="max-w-3xl">

                        <!-- Título + meta -->
                        <div class="mb-6 pb-5 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                            <h1 class="text-2xl sm:text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide leading-tight">
                                {{ currentArticle.title }}
                            </h1>
                            <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                <span>{{ currentArticle.reading_time }} {{ __('min read') }}</span>
                                <span v-if="currentArticle.created_by" class="flex items-center gap-1 text-rapanel-danger dark:text-rapanel-gold font-semibold">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                                    </svg>
                                    {{ currentArticle.created_by }}
                                </span>
                                <span v-if="currentArticle.created_at" class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                    </svg>
                                    {{ __('Published') }}: {{ currentArticle.created_at }}
                                </span>
                                <span v-if="currentArticle.updated_at" class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                    </svg>
                                    {{ __('Updated') }}: {{ currentArticle.updated_at }}
                                </span>
                            </div>
                        </div>

                        <!-- Imagen destacada -->
                        <div v-if="currentArticle.featured_image" class="mb-7 rounded-2xl overflow-hidden">
                            <img :src="currentArticle.featured_image" :alt="currentArticle.title"
                                 class="w-full max-h-60 object-cover"
                                 :style="{ objectPosition: `${currentArticle.focal_x ?? 50}% ${currentArticle.focal_y ?? 50}%` }" />
                        </div>

                        <!-- TOC -->
                        <div v-if="currentArticle.show_toc && toc.length >= 3"
                             class="mb-7 bg-rapanel-navy-50 dark:bg-rapanel-navy-800/60 rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] p-4">
                            <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light/40 dark:text-white/30 mb-2.5">
                                {{ __('Contents') }}
                            </p>
                            <ol class="space-y-1">
                                <li v-for="(item, i) in toc" :key="item.id" :class="item.level === 3 ? 'pl-4' : ''">
                                    <button @click="scrollTo(item.id)"
                                            class="text-sm text-rapanel-blue/80 hover:text-rapanel-blue transition-colors text-left">
                                        <span class="text-rapanel-text-light/30 dark:text-white/25 mr-1">{{ i + 1 }}.</span>
                                        {{ item.text }}
                                    </button>
                                </li>
                            </ol>
                        </div>

                        <!-- Cuerpo del artículo -->
                        <div ref="contentRef"
                             class="prose dark:prose-invert max-w-none
                                    prose-headings:font-display prose-headings:tracking-wide
                                    prose-h2:text-xl prose-h2:font-bold prose-h2:mt-10 prose-h2:mb-3 prose-h2:pb-2
                                    prose-h2:border-b prose-h2:border-rapanel-navy-100 dark:prose-h2:border-white/[0.07]
                                    prose-h3:text-base prose-h3:font-semibold prose-h3:mt-7 prose-h3:mb-2
                                    prose-p:text-rapanel-text-light dark:prose-p:text-white/75 prose-p:leading-relaxed
                                    prose-a:text-rapanel-blue prose-a:no-underline hover:prose-a:underline
                                    prose-strong:text-rapanel-navy-900 dark:prose-strong:text-white
                                    prose-li:text-rapanel-text-light dark:prose-li:text-white/75
                                    prose-code:bg-rapanel-navy-100 dark:prose-code:bg-rapanel-navy-700 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded-md prose-code:text-xs
                                    prose-pre:bg-rapanel-navy-900 dark:prose-pre:bg-black/40 prose-pre:rounded-xl
                                    prose-img:rounded-xl prose-img:shadow-md
                                    prose-blockquote:border-rapanel-blue/40 prose-blockquote:text-rapanel-text-light/70 dark:prose-blockquote:text-white/55
                                    prose-hr:border-rapanel-navy-100 dark:prose-hr:border-white/[0.07]"
                             v-html="sanitizeNews(currentArticle.content)" />

                        <!-- Prev / Next -->
                        <div class="mt-12 pt-5 border-t border-rapanel-navy-100 dark:border-white/[0.07] grid grid-cols-2 gap-4">
                            <button v-if="currentArticle.prev"
                                    @click="goTo(currentSection.slug, currentArticle.prev.slug)"
                                    class="group flex flex-col gap-1 p-4 rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07]
                                           hover:border-rapanel-blue/30 hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.03] transition-all text-left">
                                <span class="text-xs text-rapanel-text-light/40 dark:text-white/30 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                                    </svg>
                                    {{ __('Previous') }}
                                </span>
                                <span class="text-sm font-semibold text-rapanel-text-light dark:text-white group-hover:text-rapanel-blue transition-colors line-clamp-2">
                                    {{ currentArticle.prev.title }}
                                </span>
                            </button>
                            <div v-else />

                            <button v-if="currentArticle.next"
                                    @click="goTo(currentSection.slug, currentArticle.next.slug)"
                                    class="group flex flex-col gap-1 p-4 rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07]
                                           hover:border-rapanel-blue/30 hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.03] transition-all text-right">
                                <span class="text-xs text-rapanel-text-light/40 dark:text-white/30 flex items-center gap-1 justify-end">
                                    {{ __('Next') }}
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                    </svg>
                                </span>
                                <span class="text-sm font-semibold text-rapanel-text-light dark:text-white group-hover:text-rapanel-blue transition-colors line-clamp-2">
                                    {{ currentArticle.next.title }}
                                </span>
                            </button>
                            <div v-else />
                        </div>

                    </article>
                </div>
            </div>
        </div>

        <!-- ── Sidebar mobile (slide-in) ──────────────────────────────────── -->
        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-200" leave-active-class="transition-opacity duration-200"
                        enter-from-class="opacity-0" leave-to-class="opacity-0">
                <div v-if="sidebarOpen" @click="sidebarOpen = false"
                     class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden" />
            </Transition>

            <Transition enter-active-class="transition-transform duration-300" leave-active-class="transition-transform duration-300"
                        enter-from-class="-translate-x-full" leave-to-class="-translate-x-full">
                <div v-if="sidebarOpen"
                     class="fixed inset-y-0 left-0 z-50 w-72 bg-white dark:bg-rapanel-navy-900 border-r border-rapanel-navy-100 dark:border-white/[0.07] flex flex-col lg:hidden">

                    <div class="flex items-center justify-between px-5 h-14 border-b border-rapanel-navy-100 dark:border-white/[0.07] shrink-0">
                        <span class="font-display font-bold text-rapanel-navy-900 dark:text-white">📖 {{ __('Wiki') }}</span>
                        <button @click="sidebarOpen = false"
                                class="p-1.5 rounded-lg text-rapanel-text-light/50 dark:text-white/40 hover:bg-rapanel-navy-100 dark:hover:bg-white/[0.07]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <nav class="flex-1 overflow-y-auto py-5 px-4 space-y-5">
                        <div v-for="section in filteredSections" :key="section.slug">
                            <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light dark:text-rapanel-text-dark px-2 pb-1.5 mb-1.5 border-b border-rapanel-navy-100 dark:border-white/[0.07] flex items-center gap-1.5">
                                <span>{{ section.icon }}</span><span>{{ section.title }}</span>
                            </p>
                            <div class="space-y-0.5">
                                <button v-for="article in section.articles" :key="article.slug"
                                        @click="goTo(section.slug, article.slug)"
                                        :class="isActive(section.slug, article.slug)
                                            ? 'bg-rapanel-blue/10 dark:bg-rapanel-blue/15 text-rapanel-blue font-semibold'
                                            : 'text-rapanel-text-light dark:text-rapanel-text-dark hover:bg-rapanel-navy-100 dark:hover:bg-white/[0.06]'"
                                        class="w-full text-left text-sm px-3 py-1.5 rounded-lg transition-colors flex items-center gap-2">
                                    <span class="w-1 h-1 rounded-full bg-current shrink-0 opacity-40"></span>
                                    <span class="truncate">{{ article.title }}</span>
                                </button>
                            </div>
                        </div>
                    </nav>
                </div>
            </Transition>
        </Teleport>

    </MainLayout>
</template>
