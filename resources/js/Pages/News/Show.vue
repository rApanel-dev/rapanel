<script setup>
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    news:     Object,
    comments: Array,
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;
const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const auth = computed(() => page.props.auth?.user ?? null);
const isAdmin = computed(() => auth.value?.role === 'Admin');

const typeBadge = (type) => {
    if (type === 1) return 'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/30';
    if (type === 2) return 'bg-rapanel-gold/10 text-rapanel-gold border-rapanel-gold/30';
    if (type === 3) return 'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/30';
    return 'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/30';
};

const sideTypeBadge = (type) => {
    if (type === 1) return 'text-rapanel-blue border-rapanel-blue/40 bg-rapanel-blue/10';
    if (type === 2) return 'text-rapanel-gold border-rapanel-gold/40 bg-rapanel-gold/10';
    if (type === 3) return 'text-rapanel-danger border-rapanel-danger/40 bg-rapanel-danger/10';
    return 'text-rapanel-blue border-rapanel-blue/40 bg-rapanel-blue/10';
};

// ── Reactions (optimistic) ──
const liked = ref(props.news.user_has_liked);
const likesCount = ref(props.news.likes_count);
const likeProcessing = ref(false);

const toggleLike = () => {
    if (!auth.value || likeProcessing.value) return;
    likeProcessing.value = true;
    liked.value = !liked.value;
    likesCount.value += liked.value ? 1 : -1;
    router.post(safeRoute('news.react', props.news.slug), {}, {
        preserveScroll: true,
        onFinish: () => { likeProcessing.value = false; },
        onError: () => {
            liked.value = !liked.value;
            likesCount.value += liked.value ? 1 : -1;
        },
    });
};

// ── Comments ──
const commentForm = useForm({ body: '' });

const submitComment = () => {
    commentForm.post(safeRoute('news.comments.store', props.news.slug), {
        preserveScroll: true,
        onSuccess: () => commentForm.reset(),
    });
};

const deleteComment = (commentId) => {
    if (!confirm('¿Eliminar este comentario?')) return;
    router.delete(safeRoute('admin.news-comments.destroy', commentId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="news.title" />

    <MainLayout :show-bg="true">

        <!-- ── Header block ── -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <!-- Breadcrumb -->
                    <nav class="flex items-center gap-1.5 text-xs text-rapanel-text-light/40 dark:text-white/30 mb-4">
                        <Link :href="safeRoute('home')" class="hover:text-rapanel-blue transition-colors">{{ __('Home') }}</Link>
                        <span>›</span>
                        <Link :href="safeRoute('news.index')" class="hover:text-rapanel-blue transition-colors">{{ __('News') }}</Link>
                        <span>›</span>
                        <span class="truncate max-w-[220px] sm:max-w-sm text-rapanel-text-light/60 dark:text-white/50">{{ news.title }}</span>
                    </nav>

                    <!-- Title -->
                    <h1 class="text-2xl sm:text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white leading-tight tracking-wide mb-3">
                        {{ news.title }}
                    </h1>

                    <!-- Type badge + meta -->
                    <div class="flex flex-wrap items-center gap-3">
                        <span :class="['text-xs font-bold uppercase px-2.5 py-1 rounded-full border tracking-wide', typeBadge(news.type)]">
                            {{ news.type_label }}
                        </span>
                        <span class="text-sm text-rapanel-text-light/50 dark:text-white/35">{{ news.created_at }}</span>
                        <span class="text-xs text-rapanel-text-light/35 dark:text-white/25 hidden sm:inline">({{ news.created_ago }})</span>
                        <span v-if="news.created_by_name" class="flex items-center gap-1.5 text-xs">
                            <svg class="w-3.5 h-3.5 shrink-0 text-rapanel-danger dark:text-rapanel-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                            <span class="font-semibold text-rapanel-danger dark:text-rapanel-gold">{{ news.created_by_name }}</span>
                        </span>
                    </div>
                </div>

                <!-- Back link -->
                <Link :href="safeRoute('news.index')"
                      class="inline-flex items-center gap-2 shrink-0 px-3 py-1 rounded-full text-xs font-semibold border transition-all bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-blue hover:text-rapanel-blue">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ __('All News') }}
                </Link>
            </div>
        </div>

        <!-- ── Content ── -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="flex flex-col lg:flex-row gap-8 items-start">

                <!-- ── Article + Comments ── -->
                <div class="flex-1 min-w-0 space-y-6">

                    <article>
                        <!-- Featured image -->
                        <div v-if="news.featured_image"
                             class="rounded-2xl overflow-hidden mb-6 border border-rapanel-navy-100 dark:border-white/10 shadow-sm">
                            <img :src="news.featured_image" :alt="news.title"
                                 class="w-full aspect-video object-cover" />
                        </div>

                        <!-- Body -->
                        <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 sm:p-8">
                            <div class="news-body text-rapanel-text-light dark:text-rapanel-text-dark"
                                 v-html="news.body" />

                            <!-- Like button -->
                            <div class="mt-6 pt-5 border-t border-rapanel-navy-100 dark:border-white/[0.07] flex items-center gap-3">
                                <button
                                    @click="toggleLike"
                                    :disabled="!auth || likeProcessing"
                                    :title="!auth ? __('You must be logged in to comment.') : ''"
                                    :class="[
                                        'inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold border transition-all duration-150',
                                        liked
                                            ? 'bg-rapanel-danger text-white border-rapanel-danger shadow-sm'
                                            : 'bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:border-rapanel-danger hover:text-rapanel-danger',
                                        (!auth || likeProcessing) ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer',
                                    ]"
                                >
                                    <svg :class="['w-4 h-4 transition-transform duration-150', liked ? 'scale-110' : '']"
                                         :fill="liked ? 'currentColor' : 'none'"
                                         stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                                    </svg>
                                    {{ __('Like') }}
                                    <span v-if="likesCount > 0"
                                          class="px-1.5 py-0.5 rounded-full text-[10px] font-bold"
                                          :class="liked ? 'bg-white/20' : 'bg-rapanel-navy-100 dark:bg-white/10'">
                                        {{ likesCount }}
                                    </span>
                                </button>
                                <span v-if="likesCount > 0" class="text-xs text-rapanel-text-light/40 dark:text-white/30">
                                    {{ likesCount }} {{ __('likes') }}
                                </span>
                            </div>
                        </div>
                    </article>

                    <!-- ── Comments section ── -->
                    <section v-if="news.allow_comments">

                        <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden">

                            <!-- Header -->
                            <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/10 flex items-center gap-2">
                                <svg class="w-4 h-4 text-rapanel-blue shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                                </svg>
                                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white">
                                    {{ __('Comments') }}
                                    <span class="ml-1 text-xs font-normal text-rapanel-text-light/50 dark:text-white/40">({{ comments.length }})</span>
                                </h2>
                            </div>

                            <!-- Comment list -->
                            <div v-if="comments.length" class="divide-y divide-rapanel-navy-100 dark:divide-white/[0.06]">
                                <div v-for="comment in comments" :key="comment.id"
                                     class="px-6 py-4 flex gap-3 group/comment">
                                    <!-- Avatar -->
                                    <div class="shrink-0 w-8 h-8 rounded-full bg-rapanel-blue/10 dark:bg-rapanel-blue/20 flex items-center justify-center">
                                        <span class="text-xs font-bold text-rapanel-blue uppercase">
                                            {{ (comment.user_name || '?').charAt(0) }}
                                        </span>
                                    </div>
                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-semibold text-rapanel-navy-900 dark:text-white">{{ comment.user_name }}</span>
                                            <span class="text-[10px] text-rapanel-text-light/40 dark:text-white/30">{{ comment.created_ago }}</span>
                                            <!-- Admin delete -->
                                            <button v-if="isAdmin"
                                                    @click="deleteComment(comment.id)"
                                                    class="ml-auto opacity-0 group-hover/comment:opacity-100 transition-opacity p-1 rounded text-rapanel-danger hover:bg-rapanel-danger/10"
                                                    title="Eliminar comentario">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark leading-relaxed whitespace-pre-line">{{ comment.body }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty state -->
                            <div v-else class="px-6 py-8 text-center">
                                <svg class="w-8 h-8 text-rapanel-navy-200 dark:text-white/15 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                                </svg>
                                <p class="text-sm text-rapanel-text-light/50 dark:text-white/35">{{ __('No comments yet.') }}</p>
                                <p class="text-xs text-rapanel-text-light/35 dark:text-white/25 mt-0.5">{{ __('Be the first to comment!') }}</p>
                            </div>

                            <!-- Comment form -->
                            <div class="px-6 py-5 border-t border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50/50 dark:bg-white/[0.02]">

                                <!-- Logged in: show form -->
                                <form v-if="auth" @submit.prevent="submitComment">
                                    <div class="flex gap-3 items-start">
                                        <!-- Avatar -->
                                        <div class="shrink-0 w-8 h-8 rounded-full bg-rapanel-blue/10 dark:bg-rapanel-blue/20 flex items-center justify-center mt-0.5">
                                            <span class="text-xs font-bold text-rapanel-blue uppercase">
                                                {{ (auth.name || '?').charAt(0) }}
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <textarea
                                                v-model="commentForm.body"
                                                :placeholder="__('Your comment')"
                                                rows="3"
                                                maxlength="1000"
                                                class="w-full rounded-xl border border-rapanel-navy-100 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-sm text-rapanel-text-light dark:text-rapanel-text-dark placeholder-rapanel-text-light/30 dark:placeholder-white/25 px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/30 focus:border-rapanel-blue/50 resize-none transition"
                                            />
                                            <div class="flex items-center justify-between mt-2">
                                                <p v-if="commentForm.errors.body" class="text-xs text-rapanel-danger">{{ commentForm.errors.body }}</p>
                                                <span v-else class="text-[10px] text-rapanel-text-light/30 dark:text-white/25">{{ commentForm.body.length }}/1000</span>
                                                <button
                                                    type="submit"
                                                    :disabled="commentForm.processing || !commentForm.body.trim()"
                                                    class="ml-auto inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-xs font-semibold bg-rapanel-blue text-white hover:bg-rapanel-blue/90 disabled:opacity-50 disabled:cursor-not-allowed transition"
                                                >
                                                    <svg v-if="commentForm.processing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                                    </svg>
                                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                                                    </svg>
                                                    {{ __('Post Comment') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <!-- Not logged in -->
                                <div v-else class="flex items-center justify-between gap-3">
                                    <p class="text-sm text-rapanel-text-light/50 dark:text-white/35">
                                        {{ __('You must be logged in to comment.') }}
                                    </p>
                                    <Link :href="safeRoute('login')"
                                          class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-xs font-semibold bg-rapanel-blue text-white hover:bg-rapanel-blue/90 transition shrink-0">
                                        {{ __('Login to comment') }}
                                    </Link>
                                </div>
                            </div>
                        </div>

                    </section>

                </div>

                <!-- ── Sidebar ── -->
                <aside class="w-full lg:w-72 shrink-0 space-y-4">

                    <!-- Server Status -->
                    <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-5">
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
                         class="bg-white dark:bg-rapanel-navy-900 rounded-2xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm overflow-hidden">
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
                                <div class="shrink-0 w-12 h-9 rounded-md overflow-hidden border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 flex items-center justify-center">
                                    <img v-if="item.featured_image" :src="item.featured_image" :alt="item.title"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                    <svg v-else class="w-4 h-4 text-rapanel-navy-200 dark:text-white/15" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-rapanel-text-light dark:text-white line-clamp-2 leading-snug group-hover:text-rapanel-blue transition-colors">
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

    </MainLayout>
</template>

<style scoped>
/* Prose styles para el contenido HTML generado por Tiptap */
.news-body :deep(h1) { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.5rem; line-height: 1.3; }
.news-body :deep(h2) { font-size: 1.25rem; font-weight: 700; margin: 1rem 0 0.5rem; line-height: 1.3; }
.news-body :deep(h3) { font-size: 1.1rem;  font-weight: 600; margin: 0.75rem 0 0.4rem; line-height: 1.4; }

.news-body :deep(p)  { margin: 0.6rem 0; line-height: 1.75; font-size: 0.9375rem; }

.news-body :deep(ul) { list-style: disc;    padding-left: 1.5rem; margin: 0.6rem 0; }
.news-body :deep(ol) { list-style: decimal; padding-left: 1.5rem; margin: 0.6rem 0; }
.news-body :deep(li) { margin: 0.25rem 0; line-height: 1.65; }

.news-body :deep(a)  { color: #3b82f6; text-decoration: underline; text-underline-offset: 2px; }
.news-body :deep(a:hover) { opacity: 0.8; }

.news-body :deep(strong) { font-weight: 700; }
.news-body :deep(em)     { font-style: italic; }
.news-body :deep(s)      { text-decoration: line-through; opacity: 0.6; }
.news-body :deep(u)      { text-decoration: underline; text-underline-offset: 2px; }

.news-body :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.75rem;
    margin: 0.75rem 0;
    border: 1px solid rgba(0,0,0,0.06);
}

.news-body :deep(blockquote) {
    border-left: 3px solid #3b82f6;
    padding-left: 1rem;
    margin: 0.75rem 0;
    opacity: 0.75;
    font-style: italic;
}

.news-body :deep(hr) {
    border: none;
    border-top: 1px solid rgba(0,0,0,0.08);
    margin: 1.25rem 0;
}

:global(.dark) .news-body :deep(hr)  { border-top-color: rgba(255,255,255,0.08); }
:global(.dark) .news-body :deep(img) { border-color: rgba(255,255,255,0.08); }
</style>
