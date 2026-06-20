<script setup>
import { router, usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon, EyeSlashIcon, PhotoIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    packages: { type: Array, default: () => [] },
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

function toggle(pkg) {
    router.patch(route('admin.donation-packages.toggle', pkg.id), {}, { preserveScroll: true });
}

function destroy(pkg) {
    if (!confirm(__('Are you sure you want to delete ":name"?', { name: pkg.title }))) return;
    router.delete(route('admin.donation-packages.destroy', pkg.id), { preserveScroll: true });
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <PageHeader :title="__('Donation Packages')">
                <Link :href="route('admin.donation-packages.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-rapanel-blue text-white rounded-lg text-sm font-semibold hover:opacity-90 transition">
                    <PlusIcon class="w-4 h-4" />
                    {{ __('Add Package') }}
                </Link>
            </PageHeader>

            <!-- Desktop table -->
            <div class="hidden md:block bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-rapanel-navy-100 dark:divide-white/10 text-sm">
                    <thead class="bg-rapanel-navy-100 dark:bg-rapanel-navy-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Package') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Price') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Base DP') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Bonus') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Total DP') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Order') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-rapanel-navy-700 dark:text-rapanel-text-dark">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rapanel-navy-50 dark:divide-white/5">
                        <tr v-for="pkg in packages" :key="pkg.id"
                            class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-lg overflow-hidden bg-rapanel-navy-50 dark:bg-rapanel-navy-800 flex-shrink-0 flex items-center justify-center">
                                        <img v-if="pkg.image_url" :src="pkg.image_url" :alt="pkg.title" class="w-full h-full object-cover" />
                                        <PhotoIcon v-else class="w-6 h-6 text-rapanel-navy-300 dark:text-white/20" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-rapanel-navy-900 dark:text-white">{{ pkg.title }}</p>
                                        <p v-if="pkg.description" class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 line-clamp-1">{{ pkg.description }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-rapanel-gold">${{ Number(pkg.price_usd).toFixed(2) }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-rapanel-text-light dark:text-rapanel-text-dark">
                                {{ pkg.cashpoints.toLocaleString() }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="pkg.bonus_percent > 0"
                                    class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-rapanel-success/10 text-rapanel-success border border-rapanel-success/20">
                                    +{{ pkg.bonus_percent }}%
                                </span>
                                <span v-else class="text-rapanel-text-light/30 dark:text-rapanel-text-dark/30 text-xs">—</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ pkg.total_cashpoints.toLocaleString() }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 text-xs">
                                {{ pkg.sort_order }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button @click="toggle(pkg)"
                                    :class="pkg.is_active
                                        ? 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20'
                                        : 'bg-rapanel-navy-100 dark:bg-rapanel-navy-800 text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 border-transparent'"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border transition">
                                    <EyeIcon v-if="pkg.is_active" class="w-3.5 h-3.5" />
                                    <EyeSlashIcon v-else class="w-3.5 h-3.5" />
                                    {{ pkg.is_active ? __('Active') : __('Inactive') }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="route('admin.donation-packages.edit', pkg.id)"
                                        class="p-1.5 rounded text-rapanel-blue hover:bg-rapanel-blue/10 transition">
                                        <PencilIcon class="w-4 h-4" />
                                    </Link>
                                    <button @click="destroy(pkg)"
                                        class="p-1.5 rounded text-rapanel-danger hover:bg-rapanel-danger/10 transition">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!packages.length">
                            <td colspan="8" class="px-4 py-12 text-center text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                {{ __('No packages configured.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile cards -->
            <div class="md:hidden space-y-3">
                <div v-for="pkg in packages" :key="pkg.id"
                    class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="w-14 h-14 rounded-lg overflow-hidden bg-rapanel-navy-50 dark:bg-rapanel-navy-800 flex-shrink-0 flex items-center justify-center">
                            <img v-if="pkg.image_url" :src="pkg.image_url" :alt="pkg.title" class="w-full h-full object-cover" />
                            <PhotoIcon v-else class="w-7 h-7 text-rapanel-navy-300 dark:text-white/20" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-rapanel-navy-900 dark:text-white truncate">{{ pkg.title }}</p>
                            <div class="flex flex-wrap gap-2 mt-1">
                                <span class="text-xs font-bold text-rapanel-gold">${{ Number(pkg.price_usd).toFixed(2) }}</span>
                                <span class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ pkg.total_cashpoints.toLocaleString() }} DP</span>
                                <span v-if="pkg.bonus_percent > 0" class="text-xs font-bold text-rapanel-success">+{{ pkg.bonus_percent }}%</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-rapanel-navy-50 dark:border-white/5">
                        <button @click="toggle(pkg)"
                            :class="pkg.is_active ? 'text-rapanel-success' : 'text-rapanel-text-light/40 dark:text-rapanel-text-dark/40'"
                            class="text-xs font-semibold flex items-center gap-1">
                            <EyeIcon v-if="pkg.is_active" class="w-3.5 h-3.5" />
                            <EyeSlashIcon v-else class="w-3.5 h-3.5" />
                            {{ pkg.is_active ? __('Active') : __('Inactive') }}
                        </button>
                        <div class="flex gap-3">
                            <Link :href="route('admin.donation-packages.edit', pkg.id)" class="text-rapanel-blue text-xs font-semibold">{{ __('Edit') }}</Link>
                            <button @click="destroy(pkg)" class="text-rapanel-danger text-xs font-semibold">{{ __('Delete') }}</button>
                        </div>
                    </div>
                </div>
                <div v-if="!packages.length" class="text-center py-12 text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 text-sm">
                    {{ __('No packages configured.') }}
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
