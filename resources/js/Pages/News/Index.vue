<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    news:    Object,
    filters: Object,
});

const page  = usePage();
const __    = (key) => page.props.translations?.[key] || key;
const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const activeType = computed(() => props.filters?.type ?? null);

const filterBy = (type) => {
    router.get(safeRoute('news.index'), type ? { type } : {}, { preserveScroll: false });
};

const typeBadge = (type) => {
    if (type === 1) return 'bg-rapanel-blue/15 text-rapanel-blue border-rapanel-blue/30';
    if (type === 2) return 'bg-rapanel-gold/15 text-rapanel-gold border-rapanel-gold/30';
    if (type === 3) return 'bg-rapanel-danger/15 text-rapanel-danger border-rapanel-danger/30';
    return 'bg-rapanel-blue/15 text-rapanel-blue border-rapanel-blue/30';
};

</script>

<template>
    <Head :title="__('All News')" />

    <MainLayout :show-bg="true">

        <!-- ── Header ── -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-1.5 text-xs text-rapanel-text-light/40 dark:text-white/30 mb-2">
                        <Link :href="safeRoute('home')" class="hover:text-rapanel-blue transition-colors">{{ __('Home') }}</Link>
                        <span>›</span>
                        <span>{{ __('News') }}</span>
                    </nav>
                    <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide">
                        {{ __('All News') }}
                    </h1>
                    <p class="mt-1.5 text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                        {{ __('Latest news, events and notices from the server.') }}
                    </p>
                </div>

                <!-- Type filters -->
                <div class="flex items-center gap-2 shrink-0">
                    <button @click="filterBy(null)" :class="[
                        'px-3 py-1 rounded-full text-xs font-semibold border transition-all',
                        !activeType
                            ? 'bg-rapanel-blue text-white border-rapanel-blue'
                            : 'bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue'
                    ]">{{ __('All') }}</button>
                    <button @click="filterBy('1')" :class="[
                        'px-3 py-1 rounded-full text-xs font-semibold border transition-all',
                        activeType === '1'
                            ? 'bg-rapanel-blue text-white border-rapanel-blue'
                            : 'bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue'
                    ]">{{ __('News') }}</button>
                    <button @click="filterBy('2')" :class="[
                        'px-3 py-1 rounded-full text-xs font-semibold border transition-all',
                        activeType === '2'
                            ? 'bg-rapanel-blue text-white border-rapanel-blue'
                            : 'bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue'
                    ]">{{ __('Event') }}</button>
                    <button @click="filterBy('3')" :class="[
                        'px-3 py-1 rounded-full text-xs font-semibold border transition-all',
                        activeType === '3'
                            ? 'bg-rapanel-blue text-white border-rapanel-blue'
                            : 'bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue'
                    ]">{{ __('Notice') }}</button>
                </div>
            </div>
        </div>

        <!-- ── Content ── -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            <!-- Grid -->
            <div v-if="news.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <Link v-for="item in news.data" :key="item.id"
                      :href="safeRoute('news.show', item.slug)"
                      class="group flex flex-col bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-sm hover:shadow-md dark:hover:shadow-[0_4px_24px_rgba(0,0,0,0.4)] hover:border-rapanel-blue/30 dark:hover:border-rapanel-blue/20 transition-all duration-200 overflow-hidden cursor-pointer">

                    <!-- Featured image -->
                    <div class="relative overflow-hidden aspect-video bg-rapanel-navy-50 dark:bg-rapanel-navy-800">
                        <img v-if="item.featured_image"
                             :src="item.featured_image"
                             :alt="item.title"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        <div v-else
                             class="w-full h-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-rapanel-navy-200 dark:text-white/10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/>
                            </svg>
                        </div>

                        <!-- Pinned badge -->
                        <div v-if="item.is_pinned"
                             class="absolute top-2 left-2 flex items-center gap-1 px-2 py-0.5 rounded-full bg-rapanel-gold text-rapanel-navy-900 text-[10px] font-black uppercase tracking-wide shadow">
                            <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="currentColor"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                            {{ __('Pinned') }}
                        </div>

                        <!-- Type badge overlay -->
                        <div class="absolute top-2 right-2">
                            <span :class="['text-[10px] font-black uppercase px-2 py-0.5 rounded-full border backdrop-blur-sm', typeBadge(item.type)]">
                                {{ item.type_label }}
                            </span>
                        </div>
                    </div>

                    <!-- Card body -->
                    <div class="flex flex-col flex-1 p-5">
                        <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white leading-snug mb-2 group-hover:text-rapanel-blue transition-colors duration-200 line-clamp-2">
                            {{ item.title }}
                        </h2>

                        <div class="mt-auto flex items-center justify-between pt-3 border-t border-rapanel-navy-100 dark:border-white/[0.06]">
                            <span class="text-[11px] text-rapanel-text-light/50 dark:text-white/35" :title="item.created_at">
                                {{ item.created_ago }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-rapanel-blue group-hover:gap-2 transition-all duration-200">
                                {{ __('Read More') }}
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center py-24 text-center">
                <div class="w-16 h-16 rounded-2xl bg-rapanel-navy-50 dark:bg-white/5 flex items-center justify-center mb-4 border border-rapanel-navy-100 dark:border-white/10">
                    <svg class="w-8 h-8 text-rapanel-text-light/30 dark:text-white/20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-rapanel-text-light dark:text-white mb-1">{{ __('No news found.') }}</p>
                <p class="text-xs text-rapanel-text-light/50 dark:text-white/35 max-w-xs">{{ __('There are no published news yet. Check back soon!') }}</p>
                <button v-if="activeType" @click="filterBy(null)"
                        class="mt-5 px-4 py-1.5 rounded-full text-xs font-bold border border-rapanel-blue/40 text-rapanel-blue hover:bg-rapanel-blue hover:text-white transition-colors duration-200">
                    {{ __('All') }}
                </button>
            </div>

            <!-- Pagination -->
            <div v-if="news.last_page > 1"
                 class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t border-rapanel-navy-100 dark:border-white/[0.06]">
                <span class="text-xs text-rapanel-text-light/50 dark:text-white/35">
                    {{ __('Showing') }} {{ news.from }}–{{ news.to }} {{ __('of') }} {{ news.total }} {{ __('results') }}
                </span>
                <div class="flex items-center gap-1">
                    <Link v-for="link in news.links" :key="link.label"
                          :href="link.url ? (activeType ? link.url + '&type=' + activeType : link.url) : '#'"
                          :class="[
                              'inline-flex items-center justify-center min-w-[2rem] h-8 px-2 rounded-lg text-xs font-semibold border transition-all duration-150',
                              link.active
                                  ? 'bg-rapanel-blue text-white border-rapanel-blue'
                                  : 'border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light/60 dark:text-white/40 hover:border-rapanel-blue/40 hover:text-rapanel-blue',
                              !link.url && 'opacity-30 pointer-events-none',
                          ]"
                          v-html="link.label" />
                </div>
            </div>

        </div>
    </MainLayout>
</template>
