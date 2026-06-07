<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    category: { type: Object, required: true },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const form = useForm({
    name:        props.category.name,
    description: props.category.description ?? '',
    icon:        props.category.icon ?? '',
    sort_order:  props.category.sort_order ?? 0,
    is_active:   props.category.is_active,
});

const submit = () => form.put(route('admin.download-categories.update', props.category.id));
</script>

<template>
    <AdminLayout>
        <div class="flex items-center gap-3 mb-6">
            <Link :href="route('admin.download-categories.index')" class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-blue transition-colors">
                ← {{ __('Download Categories') }}
            </Link>
            <span class="opacity-40">/</span>
            <h1 class="text-xl font-bold text-rapanel-navy-900 dark:text-white">{{ category.name }}</h1>
        </div>

        <form @submit.prevent="submit" class="space-y-6 max-w-lg">
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-4">

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Name') }} *</label>
                    <input v-model="form.name" type="text" required
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Description') }}</label>
                    <input v-model="form.description" type="text"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Icon') }}</label>
                        <input v-model="form.icon" type="text" maxlength="4" placeholder="📦"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Sort Order') }}</label>
                        <input v-model="form.sort_order" type="number" min="0"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.05] px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    </div>
                </div>

                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" v-model="form.is_active" class="rounded text-rapanel-blue" />
                    <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">{{ __('Active') }}</span>
                </label>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" :disabled="form.processing"
                    :class="['px-6 py-2.5 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition', form.processing ? 'opacity-50 cursor-not-allowed' : '']">
                    {{ __('Save Changes') }}
                </button>
                <Link :href="route('admin.download-categories.index')" class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-blue transition-colors">
                    {{ __('Cancel') }}
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
