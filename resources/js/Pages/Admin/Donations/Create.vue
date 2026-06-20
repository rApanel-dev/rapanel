<script setup>
import { computed, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    users:    { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const form = useForm({
    user_id:    '',
    package_id: '',
    amount_usd: '',
    cashpoints: '',
    notes:      '',
});

// Auto-fill amount and CP when package selected
watch(() => form.package_id, (id) => {
    const pkg = props.packages.find(p => p.id == id);
    if (pkg) {
        form.amount_usd = pkg.price_usd;
        form.cashpoints = pkg.total_cashpoints;
    }
});

const submit = () => {
    form.post(route('admin.donations.store'));
};
</script>

<template>
    <AdminLayout>
        <div class="max-w-lg space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4 pb-5 border-b border-rapanel-navy-100 dark:border-white/[0.055]">
                <Link :href="route('admin.donations.index')"
                      class="p-2 rounded-lg hover:bg-rapanel-navy-100 dark:hover:bg-white/[0.07] text-rapanel-text-light/55 dark:text-white/45 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-display font-bold tracking-wide text-rapanel-text-light dark:text-white">{{ __('Add manual donation') }}</h1>
                    <p class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mt-0.5">{{ __('Donation Points are assigned immediately.') }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- User -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-2">{{ __('User') }}</label>
                    <select v-model="form.user_id" required
                        class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50">
                        <option value="">{{ __('Select user...') }}</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
                    </select>
                    <p v-if="form.errors.user_id" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.user_id }}</p>
                </div>

                <!-- Package (optional) -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-2">
                        {{ __('Package') }} <span class="normal-case font-normal opacity-70">({{ __('Optional') }})</span>
                    </label>
                    <select v-model="form.package_id"
                        class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50">
                        <option value="">{{ __('Custom (no package)') }}</option>
                        <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.title }} — ${{ p.price_usd.toFixed(2) }} / {{ p.total_cashpoints.toLocaleString() }} DP</option>
                    </select>
                </div>

                <!-- Amount + CP -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Amount (USD)') }}</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-rapanel-gold font-bold text-sm">$</span>
                            <input v-model="form.amount_usd" type="number" step="0.01" min="0.01" required
                                class="w-full pl-7 pr-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                        </div>
                        <p v-if="form.errors.amount_usd" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.amount_usd }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Donation Points awarded') }}</label>
                        <input v-model.number="form.cashpoints" type="number" min="1" required
                            class="w-full px-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                        <p v-if="form.errors.cashpoints" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.cashpoints }}</p>
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-2">{{ __('Notes') }} <span class="normal-case font-normal opacity-70">({{ __('Optional') }})</span></label>
                    <textarea v-model="form.notes" rows="3" :placeholder="__('Reason for manual donation...')"
                        class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 resize-none" />
                    <p v-if="form.errors.notes" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.notes }}</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-rapanel-navy-100 dark:border-white/[0.055]">
                    <Link :href="route('admin.donations.index')"
                        class="px-4 py-2 rounded-lg text-sm font-medium text-rapanel-text-light/70 dark:text-white/60 hover:bg-rapanel-navy-100 dark:hover:bg-white/10 transition">
                        {{ __('Cancel') }}
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-5 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-semibold hover:opacity-90 transition shadow disabled:opacity-60">
                        {{ form.processing ? __('Adding…') : __('Add Donation') }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
