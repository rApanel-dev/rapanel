<script setup>
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    stats:        Object,
    types:        Array,
    serverConfig: Object,
});

const page  = usePage();
const __    = (key) => page.props.translations?.[key] || key;
const flash = computed(() => page.props.flash ?? {});

// ── Import form ──────────────────────────────────────────────────────
const importForm    = useForm({ yml_files: null, lua_info: null });
const ymlFilesInput = ref(null);
const ymlFileNames  = ref([]);

const onYmlChange = (e) => {
    const files = Array.from(e.target.files ?? []);
    importForm.yml_files = files;
    ymlFileNames.value   = files.map(f => f.name);
};

const submitImport = () => {
    importForm.post(route('admin.item-db.import'), {
        forceFormData: true,
        onSuccess: () => {
            router.reload({ only: ['stats'] });
            importForm.reset();
            ymlFileNames.value = [];
            if (ymlFilesInput.value) ymlFilesInput.value.value = '';
        },
    });
};

// ── Clear modal ───────────────────────────────────────────────────────
const showClearModal = ref(false);

const submitClear = () => {
    router.delete(route('admin.item-db.destroy'), {
        onSuccess: () => { showClearModal.value = false; },
    });
};

// ── Type badge colors ────────────────────────────────────────────────
const typeBadge = (type) => {
    const map = {
        Weapon:     'bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/20',
        Armor:      'bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/20',
        Consumable: 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20',
        Etc:        'bg-rapanel-navy-100 text-rapanel-text-light border-rapanel-navy-100 dark:bg-white/5 dark:text-rapanel-text-dark dark:border-white/10',
        Card:       'bg-purple-500/10 text-purple-500 border-purple-500/20',
        Ammo:       'bg-amber-500/10 text-amber-500 border-amber-500/20',
    };
    return map[type] ?? 'bg-rapanel-navy-100 text-rapanel-text-light border-transparent dark:bg-white/5 dark:text-rapanel-text-dark';
};
</script>

<template>
    <AdminLayout>

        <PageHeader :title="__('Item DB')" :description="`${stats.total.toLocaleString()} ${__('Total items')}`" class="mb-6">
            <button @click="showClearModal = true"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-rapanel-danger/40 text-rapanel-danger text-sm font-semibold hover:bg-rapanel-danger/10 transition">
                {{ __('Clear Database') }}
            </button>
        </PageHeader>

        <!-- ── Stats ── -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div v-for="(val, label) in {
                [__('Total items')]: stats.total,
                [__('Custom')]: stats.custom,
            }" :key="label"
                class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] p-4 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-1">{{ label }}</p>
                <p class="text-2xl font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ Number(val).toLocaleString() }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- ── Import Form ── -->
            <div class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-sm overflow-hidden">
                <div class="h-[3px] bg-gradient-to-r from-rapanel-blue/70 via-rapanel-blue/30 to-transparent" />
                <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                    <h2 class="font-bold text-rapanel-navy-900 dark:text-white">{{ __('Import Items') }}</h2>
                    <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark mt-0.5">
                        {{ __('YAML Files') }} + {{ __('LUA Names File') }}
                    </p>
                </div>

                <form @submit.prevent="submitImport" class="px-6 py-5 space-y-5">

                    <!-- Warning: verify .env before importing -->
                    <div class="flex gap-3 px-3 py-3 rounded-lg bg-amber-500/8 border border-amber-500/25">
                        <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        <div class="text-xs text-amber-700 dark:text-amber-400 space-y-1 leading-relaxed">
                            <p class="font-bold">{{ __('Before importing, make sure your .env is correctly configured.') }}</p>
                            <p class="text-amber-600/80 dark:text-amber-400/70">{{ __('The emulator and game mode below are read directly from your .env file. Importing with the wrong settings will tag all items incorrectly in the database.') }}</p>
                        </div>
                    </div>

                    <!-- Server config (read-only from .env) -->
                    <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.07]">
                        <svg class="w-4 h-4 shrink-0 text-rapanel-blue" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <div class="flex-1 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                            <span class="text-rapanel-text-light dark:text-rapanel-text-dark">
                                {{ __('Emulator') }}:
                                <span class="font-bold text-rapanel-navy-900 dark:text-white capitalize">
                                    {{ serverConfig?.emulator ?? 'rathena' }}
                                </span>
                            </span>
                            <span class="text-rapanel-text-light dark:text-rapanel-text-dark">
                                {{ __('Server Mode') }}:
                                <span class="font-bold text-rapanel-navy-900 dark:text-white">
                                    {{ serverConfig?.server_mode }}
                                </span>
                            </span>
                        </div>
                        <span class="text-[9px] font-bold text-rapanel-text-light/40 dark:text-white/25 uppercase tracking-wide shrink-0">.env</span>
                    </div>

                    <!-- YAML files -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-2">
                            {{ __('YAML Files') }} <span class="text-rapanel-danger">*</span>
                        </label>
                        <input
                            ref="ymlFilesInput"
                            type="file"
                            accept=".yml,.yaml"
                            multiple
                            required
                            @change="onYmlChange"
                            class="w-full text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 transition" />
                        <div v-if="ymlFileNames.length" class="mt-2 flex flex-wrap gap-1.5">
                            <span v-for="name in ymlFileNames" :key="name"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rapanel-blue/10 text-rapanel-blue text-[10px] font-bold border border-rapanel-blue/20">
                                {{ name }}
                            </span>
                        </div>
                        <p v-if="importForm.errors.yml_files" class="mt-1 text-xs text-rapanel-danger">{{ importForm.errors.yml_files }}</p>
                    </div>

                    <!-- LUA Item Info -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-1.5">
                            {{ __('LUA Item Info File') }}
                        </label>
                        <p class="text-[11px] text-rapanel-text-light dark:text-rapanel-text-dark mb-2 leading-relaxed">
                            {{ __('itemInfo.lub — contains display names, descriptions and resource names for all items. Usually found in your client\'s') }}
                            <span class="font-mono text-rapanel-navy-900 dark:text-white">data/lua files/datainfo/</span>
                            {{ __('folder.') }}
                        </p>
                        <input
                            type="file"
                            accept=".lua,.lub"
                            @change="e => importForm.lua_info = e.target.files[0] ?? null"
                            class="w-full text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-navy-100 file:text-rapanel-text-light dark:file:bg-white/10 dark:file:text-rapanel-text-dark hover:file:bg-rapanel-navy-200 transition" />
                    </div>

                    <!-- Submit -->
                    <div class="pt-1">
                        <button type="submit" :disabled="importForm.processing"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition disabled:opacity-60">
                            <svg v-if="importForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            {{ importForm.processing ? __('Importing...') : __('Import Items') }}
                        </button>
                    </div>

                    <!-- Validation errors -->
                    <p v-if="importForm.errors.yml_files" class="text-xs text-rapanel-danger">{{ importForm.errors.yml_files }}</p>
                </form>
            </div>

            <!-- ── Result + Info ── -->
            <div class="space-y-4">

                <!-- Result panel (shown after import) -->
                <div v-if="flash.imported !== undefined"
                    class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-sm overflow-hidden">
                    <div class="h-[3px] bg-rapanel-success" />
                    <div class="px-6 py-5">
                        <p class="text-sm font-bold text-rapanel-navy-900 dark:text-white mb-4">{{ __('Import complete') }}</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div v-for="(val, label) in {
                                [__('Items imported')]: flash.imported,
                                [__('Items updated')]:  flash.updated,
                                [__('Items failed')]:   flash.failed,
                            }" :key="label"
                                class="text-center p-3 rounded-lg bg-rapanel-navy-50 dark:bg-white/[0.04]">
                                <p class="text-lg font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ val ?? 0 }}</p>
                                <p class="text-[10px] text-rapanel-text-light dark:text-rapanel-text-dark uppercase tracking-wide">{{ label }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info card -->
                <div class="bg-white dark:bg-[#0f1829] rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-sm p-5 space-y-3">
                    <p class="text-xs font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35">{{ __('Last sync') }}</p>
                    <p class="text-sm font-medium text-rapanel-navy-900 dark:text-white">
                        {{ stats.last_sync ?? __('Never') }}
                    </p>

                    <!-- Type breakdown -->
                    <div v-if="types.length" class="border-t border-rapanel-navy-100 dark:border-white/[0.07] pt-3">
                        <p class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-2">{{ __('Item Type') }}</p>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="t in types" :key="t"
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold border bg-rapanel-navy-50 dark:bg-white/5 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10">
                                {{ t }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Clear modal ── -->
        <Teleport to="body">
            <div v-if="showClearModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="showClearModal = false">
                <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-xl border border-rapanel-navy-100 dark:border-white/[0.07] w-full max-w-sm overflow-hidden">
                    <div class="h-[3px] bg-gradient-to-r from-rapanel-danger/70 via-rapanel-danger/30 to-transparent" />
                    <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                        <h2 class="font-bold text-rapanel-navy-900 dark:text-white">{{ __('Clear Database') }}</h2>
                    </div>
                    <div class="px-6 py-5 space-y-4">
                        <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('Are you sure you want to delete all items?') }}
                        </p>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showClearModal = false"
                                class="px-4 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 text-sm font-semibold text-rapanel-text-light dark:text-rapanel-text-dark hover:border-rapanel-navy-300 transition">
                                {{ __('Cancel') }}
                            </button>
                            <button @click="submitClear"
                                class="px-4 py-2 rounded-lg bg-rapanel-danger text-white text-sm font-bold hover:opacity-90 transition">
                                {{ __('Clear Database') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </AdminLayout>
</template>
