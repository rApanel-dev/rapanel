<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeftIcon, PhotoIcon, TrashIcon } from '@heroicons/vue/24/outline';
import RichTextEditor from '@/Components/RichTextEditor.vue';

const props = defineProps({ news: Object });

const safeRoute = (name, params = {}) => { try { return route(name, params); } catch { return '#'; } };

const imagePreview = ref(props.news.featured_image_url || null);

const form = useForm({
    _method: 'PUT',
    title: props.news.title,
    body: props.news.body ?? '',
    type: props.news.type,
    is_published: props.news.is_published,
    is_pinned: props.news.is_pinned,
    allow_comments: props.news.allow_comments,
    featured_image: null,
});

const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.featured_image = file;
    imagePreview.value = URL.createObjectURL(file);
};

const submit = () => {
    form.post(safeRoute('admin.news.update', { news: props.news.id }), {
        forceFormData: true,
    });
};

const confirmDelete = () => {
    if (confirm('Delete this news item permanently?')) {
        router.delete(safeRoute('admin.news.destroy', { news: props.news.id }));
    }
};
</script>

<template>
    <AdminLayout>
        <div class="max-w-3xl space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between pb-5 border-b border-rapanel-navy-100 dark:border-white/[0.055]">
                <div class="flex items-center gap-4">
                    <Link :href="safeRoute('admin.news.index')"
                          class="p-2 rounded-lg hover:bg-rapanel-navy-100 dark:hover:bg-white/[0.07] text-rapanel-text-light/55 dark:text-white/45 transition-colors">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-display font-bold tracking-wide text-rapanel-text-light dark:text-white">Edit News</h1>
                        <p class="text-sm text-rapanel-text-light/50 dark:text-white/40 mt-0.5">Admin › News › Edit</p>
                    </div>
                </div>
                <button @click="confirmDelete"
                        class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-rapanel-danger border border-rapanel-danger/30 hover:bg-rapanel-danger/10 transition">
                    <TrashIcon class="w-4 h-4" />
                    Delete
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Category + Image row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                        <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">News Category</label>
                        <select v-model="form.type"
                                class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-800 border border-rapanel-navy-100 dark:border-white/10
                                       text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50">
                            <option style="background:#1e2d4a;color:#E2E8F0" :value="1">News</option>
                            <option style="background:#1e2d4a;color:#E2E8F0" :value="2">Event</option>
                            <option style="background:#1e2d4a;color:#E2E8F0" :value="3">Notice</option>
                        </select>
                    </div>

                    <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                        <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">
                            Featured Image <span class="normal-case font-normal">(Optional)</span>
                        </label>
                        <label class="relative flex flex-col items-center justify-center h-28 rounded-lg border-2 border-dashed
                                      border-rapanel-navy-100 dark:border-white/20 hover:border-rapanel-blue/50 transition cursor-pointer overflow-hidden">
                            <img v-if="imagePreview" :src="imagePreview" class="absolute inset-0 w-full h-full object-cover" />
                            <div v-else class="flex flex-col items-center gap-1 text-rapanel-text-light/40 dark:text-white/30">
                                <PhotoIcon class="w-8 h-8" />
                                <span class="text-xs">Upload to replace</span>
                            </div>
                            <input type="file" class="sr-only" accept="image/*" @change="onImageChange" />
                        </label>
                    </div>
                </div>

                <!-- Title -->
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">Title</label>
                    <input v-model="form.title" type="text"
                           class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10
                                  text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                    <p v-if="form.errors.title" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.title }}</p>
                </div>

                <!-- Body -->
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">Content</label>
                    <RichTextEditor v-model="form.body" />
                    <p v-if="form.errors.body" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.body }}</p>
                </div>

                <!-- Toggles -->
                <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm flex flex-wrap gap-6">
                    <label v-for="(label, field) in { is_published: 'Published', is_pinned: 'Pinned', allow_comments: 'Allow Comments' }"
                           :key="field" class="flex items-center gap-2.5 cursor-pointer select-none">
                        <button type="button" @click="form[field] = !form[field]"
                                :class="['relative w-10 h-5 rounded-full transition-colors duration-200 focus:outline-none',
                                         form[field] ? 'bg-rapanel-blue' : 'bg-rapanel-navy-100 dark:bg-white/20']">
                            <span :class="['absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200',
                                           form[field] ? 'translate-x-5' : 'translate-x-0']" />
                        </button>
                        <span class="text-sm font-medium text-rapanel-text-light dark:text-white">{{ label }}</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="safeRoute('admin.news.index')"
                          class="px-4 py-2 rounded-lg text-sm font-medium text-rapanel-text-light/70 dark:text-white/60
                                 hover:bg-rapanel-navy-100 dark:hover:bg-white/10 transition">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing"
                            class="px-5 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-semibold hover:opacity-90 transition shadow disabled:opacity-60">
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
