<script setup>
import { computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    checks: { type: Array, default: () => [] },
    ranAt:  { type: String, default: null },
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const categories = [
    { key: 'database',     label: 'Database' },
    { key: 'connectivity', label: 'Server Connectivity' },
    { key: 'services',     label: 'Services & Configuration' },
    { key: 'optional',     label: 'Optional Features' },
];

const byCategory = computed(() => {
    const map = {};
    categories.forEach(c => { map[c.key] = []; });
    props.checks.forEach(ch => {
        if (map[ch.category]) map[ch.category].push(ch);
    });
    return map;
});

const summary = computed(() => ({
    errors:  props.checks.filter(c => c.status === 'error').length,
    warns:   props.checks.filter(c => c.status === 'warning').length,
}));

const ranAtFormatted = computed(() =>
    props.ranAt ? new Date(props.ranAt).toLocaleString() : null
);

const statusDot = {
    ok:      'bg-rapanel-success',
    warning: 'bg-rapanel-gold',
    error:   'bg-rapanel-danger',
    skipped: 'bg-rapanel-navy-200 dark:bg-white/20',
};

const statusRowBg = {
    ok:      'bg-rapanel-success/[0.06] dark:bg-rapanel-success/[0.10]',
    warning: 'bg-rapanel-gold/[0.06]    dark:bg-rapanel-gold/[0.10]',
    error:   'bg-rapanel-danger/[0.06]  dark:bg-rapanel-danger/[0.10]',
    skipped: '',
};

const statusLabel = {
    ok:      { text: 'OK',       classes: 'text-rapanel-success bg-rapanel-success/10 dark:bg-rapanel-success/[0.07] border-rapanel-success/30 dark:border-rapanel-success/20' },
    warning: { text: 'Warning',  classes: 'text-rapanel-gold    bg-rapanel-gold/10    dark:bg-rapanel-gold/[0.07]    border-rapanel-gold/30    dark:border-rapanel-gold/20' },
    error:   { text: 'Error',    classes: 'text-rapanel-danger  bg-rapanel-danger/10  dark:bg-rapanel-danger/[0.07]  border-rapanel-danger/30  dark:border-rapanel-danger/20' },
    skipped: { text: 'Disabled', classes: 'text-rapanel-text-light/50 dark:text-white/30 bg-rapanel-navy-50 dark:bg-white/5 border-rapanel-navy-100 dark:border-white/10' },
};

const msgColor = {
    ok:      'text-rapanel-text-light/60 dark:text-white/50',
    warning: 'text-rapanel-gold dark:text-rapanel-gold',
    error:   'text-rapanel-danger dark:text-rapanel-danger',
    skipped: 'text-rapanel-text-light/40 dark:text-white/30',
};

const reload = () => router.reload();
</script>

<template>
    <Head title="Health Check" />
    <AdminLayout>
        <div class="space-y-5">

            <PageHeader :title="__('Health Check')" :description="ranAtFormatted ? __('Last run: :time', { time: ranAtFormatted }) : __('System Diagnosis')">
                <div class="flex items-center gap-3">
                    <span v-if="summary.errors"
                          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rapanel-danger/10 border border-rapanel-danger/30 text-rapanel-danger">
                        <span class="w-1.5 h-1.5 rounded-full bg-rapanel-danger"></span>
                        {{ __(':count errors', { count: summary.errors }) }}
                    </span>
                    <span v-if="summary.warns"
                          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rapanel-gold/10 border border-rapanel-gold/30 text-rapanel-gold">
                        <span class="w-1.5 h-1.5 rounded-full bg-rapanel-gold"></span>
                        {{ __(':count warnings', { count: summary.warns }) }}
                    </span>
                    <span v-if="!summary.errors && !summary.warns"
                          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rapanel-success/10 border border-rapanel-success/30 text-rapanel-success">
                        <span class="w-1.5 h-1.5 rounded-full bg-rapanel-success"></span>
                        {{ __('All systems OK') }}
                    </span>

                    <button @click="reload" :aria-label="__('Re-run health check')"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-display font-semibold hover:bg-rapanel-blue/90 transition-colors shadow-sm">
                        <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                        {{ __('Re-run') }}
                    </button>
                </div>
            </PageHeader>

            <!-- 2-col on md+, 1-col on mobile -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">
                <div v-for="cat in categories" :key="cat.key"
                     class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-[0_4px_20px_rgba(0,0,0,0.22)] dark:shadow-[0_4px_28px_rgba(0,0,0,0.5)] overflow-hidden">

                    <!-- Category header -->
                    <div class="px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/[0.07] bg-rapanel-navy-50 dark:bg-white/5">
                        <h2 class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/40">
                            {{ __(cat.label) }}
                        </h2>
                    </div>

                    <!-- Rows -->
                    <div class="divide-y divide-rapanel-navy-100 dark:divide-white/5">
                        <div v-for="check in byCategory[cat.key]" :key="check.id"
                             class="flex items-center gap-3 px-4 py-3 transition-colors"
                             :class="statusRowBg[check.status] ?? ''">

                            <!-- Dot -->
                            <span class="flex-shrink-0 w-2 h-2 rounded-full" :class="statusDot[check.status]"></span>

                            <!-- Label -->
                            <span class="flex-1 min-w-0 text-sm font-semibold text-rapanel-text-light dark:text-white truncate">
                                {{ check.label }}
                            </span>

                            <!-- Message (hidden on xs, visible sm+) -->
                            <span class="hidden sm:block flex-1 text-xs truncate" :class="msgColor[check.status]">
                                {{ check.message }}
                            </span>

                            <!-- Badge -->
                            <span class="flex-shrink-0 text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded border"
                                  :class="statusLabel[check.status]?.classes">
                                {{ __(statusLabel[check.status]?.text ?? '') }}
                            </span>
                        </div>

                        <div v-if="!byCategory[cat.key].length"
                             class="px-4 py-4 text-xs text-rapanel-text-light/40 dark:text-white/30 italic">
                            {{ __('No checks in this category.') }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
