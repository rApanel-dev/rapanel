<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    settings: { type: Object, default: () => ({ vote_cash_from: 10, vote_cash_to: 100 }) },
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const form = useForm({
    vote_cash_from: props.settings.vote_cash_from,
    vote_cash_to:   props.settings.vote_cash_to,
});

function submit() {
    form.put(route('admin.vote-settings.update'), { preserveScroll: true });
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6 max-w-xl">
            <PageHeader :title="__('Vote Settings')" />

            <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-rapanel-text-light dark:text-rapanel-text-dark mb-6">
                    {{ __('Vote Points → Cash Points Exchange Rate') }}
                </h2>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="flex items-end gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark mb-1 uppercase tracking-wider">
                                {{ __('Vote Points (cost)') }}
                            </label>
                            <input v-model.number="form.vote_cash_from" type="number" min="1"
                                class="w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                            <p v-if="form.errors.vote_cash_from" class="text-rapanel-danger text-xs mt-1">{{ form.errors.vote_cash_from }}</p>
                        </div>

                        <div class="pb-2 text-2xl text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 font-bold select-none">→</div>

                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark mb-1 uppercase tracking-wider">
                                {{ __('Cash Points (reward)') }}
                            </label>
                            <input v-model.number="form.vote_cash_to" type="number" min="1"
                                class="w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                            <p v-if="form.errors.vote_cash_to" class="text-rapanel-danger text-xs mt-1">{{ form.errors.vote_cash_to }}</p>
                        </div>
                    </div>

                    <p class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                        {{ __('Example: 10 vote points = 100 cash points. Players must spend multiples of the cost amount.') }}
                    </p>

                    <div class="flex justify-end">
                        <button type="submit" :disabled="form.processing"
                            class="px-5 py-2 bg-rapanel-blue text-white rounded-lg text-sm font-semibold hover:opacity-90 transition disabled:opacity-50">
                            {{ form.processing ? __('Saving...') : __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
