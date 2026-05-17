<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { UserGroupIcon } from '@heroicons/vue/24/outline';
</script>

<template>
    <AdminLayout>
        <div class="space-y-5">
            <div>
                <h1 class="text-2xl font-bold text-rapanel-text-light dark:text-white">Characters</h1>
                <p class="text-sm text-rapanel-text-light/50 dark:text-white/40 mt-1">rAthena char table management</p>
            </div>

            <div class="bg-white dark:bg-rapanel-navy-800 rounded-xl border border-rapanel-navy-100 dark:border-white/10 shadow-sm p-12 flex flex-col items-center justify-center gap-4 text-center">
                <div class="w-16 h-16 rounded-full bg-rapanel-navy-50 dark:bg-white/5 flex items-center justify-center">
                    <UserGroupIcon class="w-8 h-8 text-rapanel-text-light/30 dark:text-white/20" />
                </div>
                <div>
                    <div class="text-lg font-bold text-rapanel-text-light dark:text-white">Coming Soon</div>
                    <div class="text-sm text-rapanel-text-light/50 dark:text-white/40 mt-1 max-w-sm">
                        Character management — search, view details, apply actions on rAthena char table.
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 mt-2 justify-center">
                    <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light/50 dark:text-white/40">Search & Filter</span>
                    <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light/50 dark:text-white/40">Reset Position</span>
                    <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light/50 dark:text-white/40">Reset Look</span>
                    <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-rapanel-navy-100 dark:bg-white/10 text-rapanel-text-light/50 dark:text-white/40">Inventory View</span>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
