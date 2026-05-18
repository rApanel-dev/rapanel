<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Footer from '@/Components/Footer.vue';

const props = defineProps({ news: Object });

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;
const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const typeBadgeClass = (type) => {
    if (type === 1) return 'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/30';
    if (type === 2) return 'bg-rapanel-gold/10 text-rapanel-gold border-rapanel-gold/30';
    return 'bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light/60 dark:text-white/50 border-rapanel-navy-100 dark:border-white/10';
};

const sideTypeBadge = (type) => {
    if (type === 1) return 'text-rapanel-blue border-rapanel-blue/40 bg-rapanel-blue/10';
    if (type === 2) return 'text-rapanel-gold border-rapanel-gold/40 bg-rapanel-gold/10';
    return 'text-rapanel-text-light/50 dark:text-white/40 border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5';
};
</script>

<template>
    <Head :title="news.title" />

    <MainLayout :show-bg="true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm text-rapanel-text-light/40 dark:text-white/30 mb-6">
                <Link :href="safeRoute('home')" class="hover:text-rapanel-blue transition">{{ __('Home') }}</Link>
                <span>›</span>
                <span class="text-rapanel-text-light/60 dark:text-white/50">{{ __('News') }}</span>
                <span>›</span>
                <span class="text-rapanel-text-light dark:text-white truncate max-w-[200px] sm:max-w-xs">{{ news.title }}</span>
            </nav>

            <div class="flex flex-col lg:flex-row gap-8 items-start">

                <!-- ── Main content ── -->
                <article class="flex-1 min-w-0">

                    <!-- Featured image -->
                    <div v-if="news.featured_image"
                         class="rounded-2xl overflow-hidden mb-6 border border-rapanel-navy-100 dark:border-white/10 shadow-lg">
                        <img :src="news.featured_image" :alt="news.title"
                             class="w-full aspect-video object-cover" />
                    </div>

                    <!-- Card wrapper -->
                    <div class="bg-white dark:bg-rapanel-navy-800 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 sm:p-8">

                        <!-- Type badge + date -->
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <span :class="['text-xs font-bold uppercase px-2.5 py-1 rounded-full border tracking-wide', typeBadgeClass(news.type)]">
                                {{ news.type_label }}
                            </span>
                            <span class="text-sm text-rapanel-text-light/40 dark:text-white/30">{{ news.created_at }}</span>
                            <span class="text-rapanel-text-light/25 dark:text-white/20 text-xs hidden sm:inline">({{ news.created_ago }})</span>
                        </div>

                        <!-- Title -->
                        <h1 class="text-2xl sm:text-3xl font-bold text-rapanel-text-light dark:text-white leading-tight mb-6">
                            {{ news.title }}
                        </h1>

                        <!-- Divider -->
                        <div class="border-t border-rapanel-navy-100 dark:border-white/10 mb-6" />

                        <!-- Body -->
                        <div class="text-rapanel-text-light/80 dark:text-white/75 text-base leading-relaxed whitespace-pre-wrap">
                            {{ news.body }}
                        </div>

                    </div>

                    <!-- Back link -->
                    <div class="mt-6">
                        <Link :href="safeRoute('home')"
                              class="inline-flex items-center gap-2 text-sm text-rapanel-text-light/50 dark:text-white/40
                                     hover:text-rapanel-blue dark:hover:text-white transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            {{ __('Back to Home') }}
                        </Link>
                    </div>
                </article>

                <!-- ── Sidebar ── -->
                <aside class="w-full lg:w-72 shrink-0 space-y-4">

                    <!-- Server Status -->
                    <div class="bg-white dark:bg-rapanel-navy-800 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-5">
                        <h3 class="text-xs font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30 mb-4">
                            {{ __('Server Status') }}
                        </h3>
                        <div class="flex items-center gap-2 mb-3">
                            <span :class="['w-2.5 h-2.5 rounded-full shrink-0',
                                           $page.props.serverStatus?.online ? 'bg-rapanel-success animate-pulse' : 'bg-rapanel-danger']" />
                            <span class="text-sm font-semibold text-rapanel-text-light dark:text-white">
                                {{ $page.props.serverStatus?.online ? __('Online') : __('Offline') }}
                            </span>
                        </div>
                        <p class="text-sm text-rapanel-text-light/50 dark:text-white/40">
                            <span class="text-rapanel-gold font-bold text-lg">{{ $page.props.serverStatus?.players ?? 0 }}</span>
                            {{ __('players online') }}
                        </p>
                    </div>

                    <!-- Latest News -->
                    <div v-if="$page.props.latestNews?.length"
                         class="bg-white dark:bg-rapanel-navy-800 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-rapanel-navy-100 dark:border-white/10">
                            <h3 class="text-xs font-black uppercase tracking-widest text-rapanel-text-light/40 dark:text-white/30">
                                {{ __('Latest News') }}
                            </h3>
                        </div>
                        <div class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                            <Link v-for="item in $page.props.latestNews" :key="item.id"
                                  :href="safeRoute('news.show', item.slug)"
                                  :class="['group flex items-center gap-3 px-4 py-3 transition-colors hover:bg-rapanel-navy-50 dark:hover:bg-white/5',
                                           item.slug === news.slug ? 'bg-rapanel-navy-50 dark:bg-white/5' : '']">
                                <div class="shrink-0 w-12 h-9 rounded-md overflow-hidden border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                                    <img v-if="item.featured_image" :src="item.featured_image" :alt="item.title"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                    <div v-else class="w-full h-full flex items-center justify-center text-rapanel-text-light/20 dark:text-white/20 text-sm">📰</div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-rapanel-text-light dark:text-white line-clamp-2 leading-snug
                                               group-hover:text-rapanel-blue transition-colors">
                                        {{ item.title }}
                                    </p>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="text-[10px] text-rapanel-text-light/40 dark:text-white/30">{{ item.created_at }}</span>
                                        <span class="w-0.5 h-0.5 rounded-full bg-rapanel-text-light/20 dark:bg-white/20" />
                                        <span :class="['text-[9px] font-bold uppercase px-1 py-0.5 rounded border', sideTypeBadge(item.type)]">
                                            {{ item.type_label }}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>

                </aside>
            </div>
        </div>

        <Footer />
    </MainLayout>
</template>
