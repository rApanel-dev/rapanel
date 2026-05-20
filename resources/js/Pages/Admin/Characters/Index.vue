<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { UserGroupIcon } from '@heroicons/vue/24/outline';
</script>

<template>
    <AdminLayout>
        <div class="space-y-5">

            <PageHeader title="Characters" description="rAthena char table management" />

            <div class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)] p-14 flex flex-col items-center justify-center gap-5 text-center">
                <div class="w-16 h-16 rounded-2xl bg-rapanel-navy-50 dark:bg-white/[0.04] ring-1 ring-rapanel-navy-100 dark:ring-white/[0.07] flex items-center justify-center">
                    <UserGroupIcon class="w-8 h-8 text-rapanel-text-light/30 dark:text-white/20" />
                </div>
                <div>
                    <div class="text-lg font-display font-bold tracking-wide text-rapanel-text-light dark:text-white">Coming Soon</div>
                    <div class="text-sm text-rapanel-text-light/50 dark:text-white/40 mt-1.5 max-w-sm">
                        Character management — search, view details, apply actions on rAthena char table.
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 justify-center mt-1">
                    <span v-for="tag in ['Search & Filter', 'Reset Position', 'Reset Look', 'Inventory View']" :key="tag"
                        class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-rapanel-navy-100 dark:bg-white/[0.06] text-rapanel-text-light/45 dark:text-white/35">
                        {{ tag }}
                    </span>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
