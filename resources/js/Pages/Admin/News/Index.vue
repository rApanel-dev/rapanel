<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    PlusIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/PageHeader.vue';
import ActionButton from '@/Components/ActionButton.vue';

const props = defineProps({
    news: Object,
    filters: Object,
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const typeBadgeClass = (type) => {
    if (type === 1) return 'bg-rapanel-blue/20 text-rapanel-blue border-rapanel-blue/30';
    if (type === 2) return 'bg-rapanel-gold/20 text-rapanel-gold border-rapanel-gold/30';
    if (type === 3) return 'bg-rapanel-danger/20 text-rapanel-danger border-rapanel-danger/30';
    return 'bg-rapanel-blue/20 text-rapanel-blue border-rapanel-blue/30';
};

const openUrl = (url) => window.open(url, '_blank');

const confirmDelete = (id) => {
    if (confirm(__('Delete this news item?'))) {
        router.delete(safeRoute('admin.news.destroy', { news: id }));
    }
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">

            <PageHeader :title="__('News')" :description="`${news.total} ${__('total news entries')}`">
                <Link :href="safeRoute('admin.news.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-rapanel-blue text-white rounded-lg text-sm font-bold hover:opacity-90 transition shadow">
                    <PlusIcon class="w-4 h-4" />
                    {{ __('Create News') }}
                </Link>
            </PageHeader>

            <!-- Table -->
            <div class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] overflow-hidden shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)]">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-12">ID</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-28">{{ __('Type') }}</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-16">{{ __('Image') }}</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">{{ __('Title') }}</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-24">{{ __('Published') }}</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-20">{{ __('Pinned') }}</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">{{ __('Author') }}</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden lg:table-cell">{{ __('Created') }}</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 hidden xl:table-cell">{{ __('Updated') }}</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-28">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                            <tr v-if="!news.data.length">
                                <td colspan="10" class="px-4 py-12 text-center text-rapanel-text-light/40 dark:text-white/30">
                                    {{ __('No news yet. Create the first one!') }}
                                </td>
                            </tr>
                            <tr v-for="item in news.data" :key="item.id"
                                class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                                <td class="px-4 py-3 font-mono text-xs text-rapanel-text-light/60 dark:text-white/50">{{ item.id }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['text-xs font-semibold px-2 py-0.5 rounded border', typeBadgeClass(item.type)]">
                                        {{ item.type_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <img v-if="item.featured_image" :src="item.featured_image"
                                         class="w-10 h-10 rounded object-cover border border-rapanel-navy-100 dark:border-white/10" />
                                    <div v-else class="w-10 h-10 rounded bg-rapanel-navy-100 dark:bg-white/10 flex items-center justify-center text-rapanel-text-light/30 dark:text-white/20 text-xs">—</div>
                                </td>
                                <td class="px-4 py-3 font-medium text-rapanel-text-light dark:text-white max-w-xs truncate">{{ item.title }}</td>
                                <td class="px-4 py-3 text-center">
                                    <CheckCircleIcon v-if="item.is_published" class="w-5 h-5 text-rapanel-success inline" />
                                    <XCircleIcon v-else class="w-5 h-5 text-rapanel-danger inline" />
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <CheckCircleIcon v-if="item.is_pinned" class="w-5 h-5 text-rapanel-success inline" />
                                    <span v-else class="text-rapanel-text-light/25 dark:text-white/20 text-sm">—</span>
                                </td>
                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span v-if="item.created_by_name" class="text-xs font-semibold text-rapanel-text-light dark:text-white">{{ item.created_by_name }}</span>
                                    <span v-else class="text-xs text-rapanel-text-light/30 dark:text-white/20">—</span>
                                </td>
                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span class="block text-xs text-rapanel-text-light/70 dark:text-white/60 whitespace-nowrap">{{ item.created_at }}</span>
                                    <span class="block text-[10px] text-rapanel-text-light/40 dark:text-white/30 mt-0.5">{{ item.created_ago }}</span>
                                </td>
                                <td class="px-4 py-3 hidden xl:table-cell">
                                    <template v-if="item.updated_at">
                                        <span class="block text-xs text-rapanel-text-light/70 dark:text-white/60 whitespace-nowrap">{{ item.updated_at }}</span>
                                        <span class="block text-[10px] text-rapanel-text-light/40 dark:text-white/30 mt-0.5">{{ item.updated_ago }}</span>
                                        <span v-if="item.updated_by_name" class="block text-[10px] font-semibold text-rapanel-text-light/50 dark:text-white/40 mt-0.5">{{ item.updated_by_name }}</span>
                                    </template>
                                    <span v-else class="text-xs text-rapanel-text-light/30 dark:text-white/20">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- View -->
                                        <ActionButton variant="blue" size="icon" :title="__('View news')"
                                            @click="openUrl(safeRoute('news.show', item.slug))">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </ActionButton>
                                        <!-- Edit -->
                                        <ActionButton variant="navy" size="icon" :title="__('Edit news')"
                                            @click="router.visit(safeRoute('admin.news.edit', { news: item.id }))">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                        </ActionButton>
                                        <!-- Delete -->
                                        <ActionButton variant="danger" size="icon" :title="__('Delete news')"
                                            @click="confirmDelete(item.id)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </ActionButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="news.last_page > 1"
                     class="flex items-center justify-between px-4 py-3 border-t border-rapanel-navy-100 dark:border-white/10 text-xs text-rapanel-text-light/50 dark:text-white/40">
                    <span>{{ __('Showing :from–:to of :total results', { from: news.from, to: news.to, total: news.total }) }}</span>
                    <div class="flex gap-1">
                        <Link v-for="link in news.links" :key="link.label"
                              :href="link.url || '#'"
                              :class="[
                                  'px-2.5 py-1 rounded transition',
                                  link.active
                                    ? 'bg-rapanel-blue text-white'
                                    : 'hover:bg-rapanel-navy-100 dark:hover:bg-white/10 text-rapanel-text-light/60 dark:text-white/50',
                                  !link.url && 'opacity-40 pointer-events-none'
                              ]"
                              v-html="link.label" />
                    </div>
                </div>
                <div v-else class="px-4 py-3 border-t border-rapanel-navy-100 dark:border-white/10 text-xs text-rapanel-text-light/50 dark:text-white/40">
                    {{ __('Showing :from–:to of :total results', { from: news.from ?? 0, to: news.to ?? 0, total: news.total }) }}
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
