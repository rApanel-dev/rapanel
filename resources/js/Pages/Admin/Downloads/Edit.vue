<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    download:   { type: Object, required: true },
    categories: { type: Array,  default: () => [] },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const form = useForm({
    _method:                'PUT',
    name:                   props.download.name,
    category_id:            props.download.category_id ?? '',
    description:            props.download.description ?? '',
    image:                  null,
    is_external:            props.download.is_external,
    is_external_url_hidden: props.download.is_external_url_hidden,
    file_url:               props.download.file_url ?? '',
    file_name:              props.download.file_name ?? '',
    file:                   null,
    is_only_auth:           props.download.is_only_auth,
    is_active:              props.download.is_active,
    sort_order:             props.download.sort_order ?? 0,
});

const submit = () => {
    form.post(route('admin.downloads.update', props.download.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <AdminLayout>
        <div class="flex items-center gap-3 mb-6">
            <Link :href="route('admin.downloads.index')" class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-blue transition-colors">
                ← {{ __('Downloads') }}
            </Link>
            <span class="text-rapanel-text-light dark:text-rapanel-text-dark opacity-40">/</span>
            <h1 class="text-xl font-bold text-rapanel-navy-900 dark:text-white truncate max-w-xs">{{ download.name }}</h1>
        </div>

        <form @submit.prevent="submit" class="space-y-6 max-w-2xl">

            <!-- Basic Info -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-4">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('Basic Info') }}</h2>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Title') }} *</label>
                    <input v-model="form.name" type="text" required
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Category') }}</label>
                    <select v-model="form.category_id"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                        <option value="">— {{ __('No category') }} —</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.icon ? cat.icon + ' ' : '' }}{{ cat.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Description') }}</label>
                    <textarea v-model="form.description" rows="4"
                        class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue resize-none" />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Card Image') }}</label>
                    <div v-if="download.image_url" class="mb-2">
                        <img :src="download.image_url" class="h-24 w-auto rounded-lg border border-rapanel-navy-100 dark:border-white/10 object-cover" />
                    </div>
                    <input type="file" accept="image/*" @change="e => form.image = e.target.files[0]"
                        class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                    <p v-if="download.image_url" class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Upload a new image to replace the current one.') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Sort Order') }}</label>
                    <input v-model="form.sort_order" type="number" min="0"
                        class="w-32 rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                </div>
            </div>

            <!-- File Source -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-4">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider">{{ __('File Source') }}</h2>

                <div class="flex rounded-lg overflow-hidden border border-rapanel-navy-100 dark:border-white/10 w-fit">
                    <button type="button" @click="form.is_external = true"
                        :class="['px-4 py-2 text-sm font-semibold transition', form.is_external ? 'bg-rapanel-blue text-white' : 'text-rapanel-text-light dark:text-rapanel-text-dark hover:bg-rapanel-navy-50 dark:hover:bg-rapanel-navy-800']">
                        {{ __('External URL') }}
                    </button>
                    <button type="button" @click="form.is_external = false"
                        :class="['px-4 py-2 text-sm font-semibold transition', !form.is_external ? 'bg-rapanel-blue text-white' : 'text-rapanel-text-light dark:text-rapanel-text-dark hover:bg-rapanel-navy-50 dark:hover:bg-rapanel-navy-800']">
                        {{ __('Upload File') }}
                    </button>
                </div>

                <template v-if="form.is_external">
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Download URL') }} *</label>
                        <input v-model="form.file_url" type="url"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue font-mono" />
                        <p v-if="form.errors.file_url" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.file_url }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('File Name') }}</label>
                        <input v-model="form.file_name" type="text"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue font-mono" />
                    </div>

                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="checkbox" v-model="form.is_external_url_hidden" class="mt-0.5 rounded text-rapanel-blue" />
                        <div>
                            <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">{{ __('Hide external URL') }}</span>
                            <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark mt-0.5">{{ __('Stream the file through this server to hide the real URL from users.') }}</p>
                        </div>
                    </label>
                </template>

                <template v-else>
                    <div v-if="download.file_name" class="text-xs font-mono text-rapanel-text-light dark:text-rapanel-text-dark bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10">
                        📦 {{ download.file_name }}
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('Replace File') }}</label>
                        <input type="file" @change="e => form.file = e.target.files[0]"
                            class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 cursor-pointer" />
                        <p class="mt-1 text-xs text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Leave empty to keep the current file. Max 512 MB.') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-rapanel-navy-900 dark:text-white mb-1.5">{{ __('File Name Override') }}</label>
                        <input v-model="form.file_name" type="text"
                            class="w-full rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-3 py-2 text-sm text-rapanel-navy-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rapanel-blue font-mono" />
                    </div>
                </template>
            </div>

            <!-- Visibility -->
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-6 space-y-3">
                <h2 class="text-sm font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wider mb-2">{{ __('Visibility') }}</h2>

                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" v-model="form.is_only_auth" class="rounded text-rapanel-blue" />
                    <div>
                        <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">{{ __('Authenticated users only') }}</span>
                        <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Only logged-in users can see and download this.') }}</p>
                    </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" v-model="form.is_active" class="rounded text-rapanel-blue" />
                    <div>
                        <span class="text-sm font-semibold text-rapanel-navy-900 dark:text-white">{{ __('Active') }}</span>
                        <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Active downloads are visible to users.') }}</p>
                    </div>
                </label>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3">
                <button type="submit" :disabled="form.processing"
                    :class="['px-6 py-2.5 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition', form.processing ? 'opacity-50 cursor-not-allowed' : '']">
                    {{ __('Save Changes') }}
                </button>
                <Link :href="route('admin.downloads.index')" class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-blue transition-colors">
                    {{ __('Cancel') }}
                </Link>
            </div>

        </form>
    </AdminLayout>
</template>
