<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import {
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    CheckCircleIcon,
    XCircleIcon,
    EyeIcon,
} from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    news: Object,
    filters: Object,
});

const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const typeBadgeClass = (type) => {
    if (type === 1) return 'bg-rapanel-blue/20 text-rapanel-blue border-rapanel-blue/30';
    if (type === 2) return 'bg-rapanel-gold/20 text-rapanel-gold border-rapanel-gold/30';
    return 'bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-white/60 border-gray-200 dark:border-white/20';
};

const confirmDelete = (id) => {
    if (confirm('Delete this news item?')) {
        router.delete(safeRoute('admin.news.destroy', { news: id }));
    }
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">

            <PageHeader title="News" :description="`${news.total} total entries`">
                <Link :href="safeRoute('admin.news.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-rapanel-blue text-white rounded-lg text-sm font-bold hover:opacity-90 transition shadow">
                    <PlusIcon class="w-4 h-4" />
                    Create News
                </Link>
            </PageHeader>

            <!-- Table -->
            <div class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] overflow-hidden shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)]">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/5">
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-12">ID</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-28">Type</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-16">Image</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">Title</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-24">Published</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-20">Pinned</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-32">Created</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40 w-28">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                            <tr v-if="!news.data.length">
                                <td colspan="8" class="px-4 py-12 text-center text-rapanel-text-light/40 dark:text-white/30">
                                    No news yet. Create the first one!
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
                                    <XCircleIcon v-else class="w-5 h-5 text-rapanel-danger inline opacity-40" />
                                </td>
                                <td class="px-4 py-3 text-xs text-rapanel-text-light/50 dark:text-white/40">{{ item.created_at }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a v-if="item.is_published"
                                           :href="safeRoute('news.show', item.slug)"
                                           target="_blank"
                                           title="View published news"
                                           class="p-1.5 rounded hover:bg-rapanel-navy-100 dark:hover:bg-white/10 text-rapanel-text-light/60 dark:text-white/50 hover:text-rapanel-success transition">
                                            <EyeIcon class="w-4 h-4" />
                                        </a>
                                        <span v-else class="p-1.5 text-rapanel-text-light/20 dark:text-white/20 cursor-not-allowed" title="Publish to preview">
                                            <EyeIcon class="w-4 h-4" />
                                        </span>
                                        <Link :href="safeRoute('admin.news.edit', { news: item.id })"
                                              class="p-1.5 rounded hover:bg-rapanel-navy-100 dark:hover:bg-white/10 text-rapanel-text-light/60 dark:text-white/50 hover:text-rapanel-blue transition">
                                            <PencilSquareIcon class="w-4 h-4" />
                                        </Link>
                                        <button @click="confirmDelete(item.id)"
                                                class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-rapanel-danger/10 text-rapanel-text-light/60 dark:text-white/50 hover:text-rapanel-danger transition">
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="news.last_page > 1"
                     class="flex items-center justify-between px-4 py-3 border-t border-rapanel-navy-100 dark:border-white/10 text-xs text-rapanel-text-light/50 dark:text-white/40">
                    <span>Showing {{ news.from }}–{{ news.to }} of {{ news.total }} results</span>
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
                    Showing {{ news.from ?? 0 }}–{{ news.to ?? 0 }} of {{ news.total }} results
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
