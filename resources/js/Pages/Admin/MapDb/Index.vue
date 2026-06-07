<script setup>
import { ref, computed } from 'vue';
import { router, useForm, usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    stats:        Object,
    serverConfig: Object,
    mapList:      Object,
    sort:         { type: String, default: 'map_name' },
    direction:    { type: String, default: 'asc' },
});

const page  = usePage();
const __    = (key) => page.props.translations?.[key] || key;
const flash = computed(() => page.props.flash ?? {});
const dt    = (v) => v ? v.replace('T', ' ').replace(/\.\d+Z?$/, '') : '—';

// ── Map Cache form ─────────────────────────────────────────────────────
const mapCacheForm  = useForm({ map_cache: null });
const mapCacheInput = ref(null);
const mapCacheFile  = ref('');

const onMapCacheChange = (e) => {
    const file = e.target.files?.[0] ?? null;
    mapCacheForm.map_cache = file;
    mapCacheFile.value     = file?.name ?? '';
};

const submitMapCache = () => {
    mapCacheForm.post(route('admin.map-db.import-map-cache'), {
        forceFormData: true,
        onSuccess: () => {
            router.reload({ only: ['stats', 'mapList'] });
            mapCacheForm.reset();
            mapCacheFile.value = '';
            if (mapCacheInput.value) mapCacheInput.value.value = '';
        },
    });
};

// ── Map Info (mapInfo.lua) form ────────────────────────────────────────
const mapInfoForm  = useForm({ map_info: null });
const mapInfoInput = ref(null);
const mapInfoFile  = ref('');

const onMapInfoChange = (e) => {
    const file = e.target.files?.[0] ?? null;
    mapInfoForm.map_info = file;
    mapInfoFile.value    = file?.name ?? '';
};

const submitMapInfo = () => {
    mapInfoForm.post(route('admin.map-db.import-map-info'), {
        forceFormData: true,
        onSuccess: () => {
            router.reload({ only: ['stats', 'mapList'] });
            mapInfoForm.reset();
            mapInfoFile.value = '';
            if (mapInfoInput.value) mapInfoInput.value.value = '';
        },
    });
};

// ── Spawn files form ───────────────────────────────────────────────────
const spawnForm  = useForm({ spawn_files: null });
const spawnInput = ref(null);
const spawnFiles = ref([]);

const onSpawnChange = (e) => {
    const files = Array.from(e.target.files ?? []);
    spawnForm.spawn_files = files.length ? files : null;
    spawnFiles.value      = files.map(f => f.name);
};

const submitSpawns = () => {
    spawnForm.post(route('admin.map-db.import-spawns'), {
        forceFormData: true,
        onSuccess: () => {
            router.reload({ only: ['stats', 'mapList'] });
            spawnForm.reset();
            spawnFiles.value = [];
            if (spawnInput.value) spawnInput.value.value = '';
        },
    });
};

// ── Clear all modals ───────────────────────────────────────────────────
const showClearMapsModal   = ref(false);
const showClearSpawnsModal = ref(false);

const submitClearMaps = () => {
    router.delete(route('admin.map-db.destroy-map-cache'), {
        onSuccess: () => {
            showClearMapsModal.value = false;
            router.reload({ only: ['stats', 'mapList'] });
        },
    });
};

const submitClearSpawns = () => {
    router.delete(route('admin.map-db.destroy-spawns'), {
        onSuccess: () => {
            showClearSpawnsModal.value = false;
            router.reload({ only: ['stats', 'mapList'] });
        },
    });
};

// ── Per-map spawn delete ───────────────────────────────────────────────
const confirmDeleteMap = ref(null);

const deleteMapSpawns = () => {
    if (!confirmDeleteMap.value) return;
    router.delete(route('admin.map-db.destroy-map-spawns', confirmDeleteMap.value), {
        onSuccess: () => {
            confirmDeleteMap.value = null;
            router.reload({ only: ['stats', 'mapList'] });
        },
    });
};

// ── Table search + sort ───────────────────────────────────────────────
const searchInput  = ref('');
const currentSort  = ref(props.sort);
const currentDir   = ref(props.direction);
let searchTimer    = null;

const sortParams = () => ({
    search:    searchInput.value || undefined,
    sort:      currentSort.value !== 'map_name' ? currentSort.value : undefined,
    direction: currentDir.value  !== 'asc'      ? currentDir.value  : undefined,
});

const doSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('admin.map-db.index'), sortParams(), { preserveState: true, replace: true, only: ['mapList'] });
    }, 350);
};

const doSort = (col) => {
    const newDir = currentSort.value === col && currentDir.value === 'asc' ? 'desc' : 'asc';
    currentSort.value = col;
    currentDir.value  = newDir;
    router.get(route('admin.map-db.index'), sortParams(), { preserveState: true, replace: true, only: ['mapList'] });
};

const sortState = (col) => {
    if (currentSort.value !== col) return 'none';
    return currentDir.value;
};
</script>

<template>
    <AdminLayout>

        <PageHeader
            :title="__('Map DB')"
            :description="`${stats.maps.toLocaleString()} ${__('Maps in Cache')} · ${stats.spawn_maps.toLocaleString()} ${__('Maps with Spawns')} · ${stats.total_spawns.toLocaleString()} ${__('Total Spawns')}`"
            class="mb-6">
            <div class="flex gap-2">
                <button @click="showClearMapsModal = true"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-rapanel-danger/40 text-rapanel-danger text-sm font-semibold hover:bg-rapanel-danger/10 transition">
                    {{ __('Clear Map Cache') }}
                </button>
                <button @click="showClearSpawnsModal = true"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-rapanel-danger/40 text-rapanel-danger text-sm font-semibold hover:bg-rapanel-danger/10 transition">
                    {{ __('Clear All Spawns') }}
                </button>
            </div>
        </PageHeader>

        <!-- ── Stats row ── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div v-for="(val, label) in {
                [__('Maps in Cache')]:    stats.maps,
                [__('Maps with Spawns')]: stats.spawn_maps,
                [__('Total Spawns')]:     stats.total_spawns,
            }" :key="label"
                class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] p-4 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-1">{{ label }}</p>
                <p class="text-2xl font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ Number(val).toLocaleString() }}</p>
            </div>
            <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] p-4 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-1">{{ __('Last sync') }}</p>
                <p class="text-sm font-medium text-rapanel-navy-900 dark:text-white truncate">{{ stats.last_sync ?? __('Never') }}</p>
            </div>
        </div>

        <!-- ── Import forms ── -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <!-- Map Cache -->
            <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-sm overflow-hidden">
                <div class="h-[3px] bg-gradient-to-r from-rapanel-blue/70 via-rapanel-blue/30 to-transparent" />
                <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                    <h2 class="font-bold text-rapanel-navy-900 dark:text-white">{{ __('Import Map Cache') }}</h2>
                    <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark mt-0.5">
                        {{ __('Extracts map dimensions (width × height) from the rAthena binary cache.') }}
                    </p>
                </div>
                <form @submit.prevent="submitMapCache" class="px-6 py-5 space-y-4">

                    <!-- Server config (igual que item-db / mob-db) -->
                    <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/[0.04] border border-rapanel-navy-100 dark:border-white/[0.07]">
                        <svg class="w-4 h-4 shrink-0 text-rapanel-blue" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <div class="flex-1 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                            <span class="text-rapanel-text-light dark:text-rapanel-text-dark">
                                {{ __('Emulator') }}:
                                <span class="font-bold text-rapanel-navy-900 dark:text-white capitalize">{{ serverConfig?.emulator ?? 'rathena' }}</span>
                            </span>
                            <span class="text-rapanel-text-light dark:text-rapanel-text-dark">
                                {{ __('Server Mode') }}:
                                <span class="font-bold text-rapanel-navy-900 dark:text-white">{{ serverConfig?.server_mode }}</span>
                            </span>
                        </div>
                        <span class="text-[9px] font-bold text-rapanel-text-light/40 dark:text-white/25 uppercase tracking-wide shrink-0">.env</span>
                    </div>

                    <!-- Advertencia orden de importación -->
                    <div class="flex gap-3 px-3 py-3 rounded-lg bg-amber-500/8 border border-amber-500/25">
                        <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        <div class="text-xs text-amber-700 dark:text-amber-400 space-y-1.5 leading-relaxed">
                            <p class="font-bold">{{ __('Import order matters') }}</p>
                            <p>
                                <span class="font-bold">1.</span>
                                {{ __('First upload the base') }} <span class="font-mono font-bold">db/map_cache.dat</span> {{ __('from the root of your rAthena installation. This contains all maps.') }}
                            </p>
                            <p>
                                <span class="font-bold">2.</span>
                                {{ __('Then upload') }}
                                <span class="font-mono font-bold">{{ serverConfig?.is_pre ? 'db/pre-re/map_cache.dat' : 'db/re/map_cache.dat' }}</span>
                                {{ __('if it exists. It overrides only the maps that differ in') }}
                                <span class="font-bold">{{ serverConfig?.server_mode }}</span>.
                            </p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-2">
                            map_cache.dat <span class="text-rapanel-danger">*</span>
                        </label>
                        <input ref="mapCacheInput" type="file" accept=".dat" required @change="onMapCacheChange"
                            class="w-full text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 transition" />
                        <div v-if="mapCacheFile" class="mt-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rapanel-blue/10 text-rapanel-blue text-[10px] font-bold border border-rapanel-blue/20">
                                {{ mapCacheFile }}
                            </span>
                        </div>
                        <p v-if="mapCacheForm.errors.map_cache" class="mt-1 text-xs text-rapanel-danger">{{ mapCacheForm.errors.map_cache }}</p>
                    </div>
                    <button type="submit" :disabled="mapCacheForm.processing"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition disabled:opacity-60">
                        <svg v-if="mapCacheForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        {{ mapCacheForm.processing ? __('Importing...') : __('Import Map Cache') }}
                    </button>
                    <div v-if="flash.maps_imported !== undefined"
                        class="rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] overflow-hidden">
                        <div :class="['h-[3px]', flash.maps_imported < flash.maps_expected ? 'bg-amber-500' : 'bg-rapanel-success']" />
                        <div class="px-4 py-3">
                            <div class="grid grid-cols-2 gap-2 text-center">
                                <div>
                                    <p class="text-xl font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ flash.maps_imported }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('maps imported') }}</p>
                                </div>
                                <div>
                                    <p class="text-xl font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ flash.maps_expected }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('maps expected') }}</p>
                                </div>
                            </div>
                            <p v-if="flash.maps_imported < flash.maps_expected"
                                class="mt-2 text-[10px] text-amber-600 dark:text-amber-400 text-center leading-relaxed">
                                {{ __('Partial import — the binary format may differ. Try a different map_cache.dat.') }}
                            </p>
                        </div>
                    </div>
                </form>

                <!-- ── Divider ── -->
                <div class="mx-6 border-t border-rapanel-navy-100 dark:border-white/[0.07]" />

                <!-- ── mapInfo.lua form ── -->
                <form @submit.prevent="submitMapInfo" class="px-6 py-5 space-y-4">
                    <div>
                        <p class="text-xs font-bold text-rapanel-navy-900 dark:text-white mb-0.5">{{ __('Map Display Names') }}</p>
                        <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('Imports display names and background info from') }} <span class="font-mono">system/mapInfo.lua</span>.
                            {{ __('Only updates maps already in the cache.') }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-2">
                            mapInfo.lua <span class="text-rapanel-danger">*</span>
                        </label>
                        <input ref="mapInfoInput" type="file" accept=".lua,.txt" required @change="onMapInfoChange"
                            class="w-full text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-blue/10 file:text-rapanel-blue hover:file:bg-rapanel-blue/20 transition" />
                        <div v-if="mapInfoFile" class="mt-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rapanel-blue/10 text-rapanel-blue text-[10px] font-bold border border-rapanel-blue/20">
                                {{ mapInfoFile }}
                            </span>
                        </div>
                        <p v-if="mapInfoForm.errors.map_info" class="mt-1 text-xs text-rapanel-danger">{{ mapInfoForm.errors.map_info }}</p>
                    </div>
                    <button type="submit" :disabled="mapInfoForm.processing"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-rapanel-blue text-white text-sm font-bold hover:opacity-90 transition disabled:opacity-60">
                        <svg v-if="mapInfoForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        {{ mapInfoForm.processing ? __('Importing...') : __('Import Map Names') }}
                    </button>
                    <div v-if="flash.maps_matched !== undefined"
                        class="rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] overflow-hidden">
                        <div class="h-[3px] bg-rapanel-success" />
                        <div class="px-4 py-3">
                            <div class="grid grid-cols-2 gap-2 text-center">
                                <div>
                                    <p class="text-xl font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ flash.maps_matched }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('maps updated') }}</p>
                                </div>
                                <div>
                                    <p class="text-xl font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ flash.maps_parsed }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('entries in file') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div>

            <!-- Spawn files -->
            <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-sm overflow-hidden">
                <div class="h-[3px] bg-gradient-to-r from-rapanel-purple/70 via-rapanel-purple/30 to-transparent" />
                <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                    <h2 class="font-bold text-rapanel-navy-900 dark:text-white">{{ __('Import Spawn Files') }}</h2>
                    <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark mt-0.5">
                        {{ __('Upload one or more rAthena spawn .txt files. You can add files one by one or in batches.') }}
                    </p>
                </div>
                <form @submit.prevent="submitSpawns" class="px-6 py-5 space-y-4">
                    <div class="flex gap-3 px-3 py-3 rounded-lg bg-amber-500/8 border border-amber-500/25">
                        <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        <div class="text-xs text-amber-700 dark:text-amber-400 space-y-1.5 leading-relaxed">
                            <p>{{ __('Re-uploading a file replaces only the spawns for the maps contained in that file. Other maps are not affected.') }}</p>
                            <p>
                                {{ __('PHP limits file uploads to') }}
                                <span class="font-mono font-bold">max_file_uploads</span>
                                {{ __('files per request (default: 20). Upload in batches if needed.') }}
                                {{ __('To increase the limit, add') }}
                                <span class="font-mono font-bold">max_file_uploads = 200</span>
                                {{ __('to your') }}
                                <span class="font-mono font-bold">php.ini</span>
                                {{ __('or a') }}
                                <span class="font-mono font-bold">conf.d</span>
                                {{ __('override file.') }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-2">
                            {{ __('Spawn files') }} (.txt) <span class="text-rapanel-danger">*</span>
                        </label>
                        <input ref="spawnInput" type="file" accept=".txt" multiple required @change="onSpawnChange"
                            class="w-full text-sm text-rapanel-text-light dark:text-rapanel-text-dark file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-rapanel-purple/10 file:text-rapanel-purple hover:file:bg-rapanel-purple/20 transition" />
                        <div v-if="spawnFiles.length" class="mt-2 flex flex-wrap gap-1">
                            <span v-for="name in spawnFiles" :key="name"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rapanel-purple/10 text-rapanel-purple text-[10px] font-bold border border-rapanel-purple/20">
                                {{ name }}
                            </span>
                        </div>
                        <p v-if="spawnForm.errors.spawn_files" class="mt-1 text-xs text-rapanel-danger">{{ spawnForm.errors.spawn_files }}</p>
                    </div>
                    <button type="submit" :disabled="spawnForm.processing"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-rapanel-purple text-white text-sm font-bold hover:opacity-90 transition disabled:opacity-60">
                        <svg v-if="spawnForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        {{ spawnForm.processing ? __('Importing...') : __('Import Spawns') }}
                    </button>
                    <div v-if="flash.spawns_imported !== undefined"
                        class="rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] overflow-hidden">
                        <div class="h-[3px] bg-rapanel-success" />
                        <div class="px-4 py-3 space-y-3">
                            <p class="text-xs font-bold text-rapanel-navy-900 dark:text-white">{{ __('Import complete') }}</p>
                            <div class="grid grid-cols-3 gap-2">
                                <div v-for="(val, label) in {
                                    [__('Spawns')]:        flash.spawns_imported,
                                    [__('Maps affected')]: flash.maps_affected,
                                    [__('Failed')]:        flash.spawns_failed,
                                }" :key="label"
                                    class="text-center p-2 rounded-lg bg-rapanel-navy-50 dark:bg-white/[0.04]">
                                    <p class="text-base font-bold text-rapanel-navy-900 dark:text-white tabular-nums">{{ val ?? 0 }}</p>
                                    <p class="text-[10px] text-rapanel-text-light dark:text-rapanel-text-dark uppercase tracking-wide">{{ label }}</p>
                                </div>
                            </div>
                            <div v-if="flash.maps_detail?.length" class="pt-1">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-rapanel-text-light/50 dark:text-white/35 mb-1.5">{{ __('Maps parsed') }}</p>
                                <div class="flex flex-wrap gap-1 max-h-28 overflow-y-auto">
                                    <span v-for="m in flash.maps_detail" :key="m.name"
                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-rapanel-navy-50 dark:bg-white/[0.06] text-[10px] font-mono text-rapanel-navy-900 dark:text-white border border-rapanel-navy-100 dark:border-white/[0.08]">
                                        {{ m.name }}
                                        <span class="text-rapanel-text-light dark:text-rapanel-text-dark">×{{ m.count }}</span>
                                    </span>
                                </div>
                            </div>
                            <div v-if="flash.skipped_lines?.length" class="pt-1">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-amber-500/70 mb-1.5">{{ __('Skipped lines (sample)') }}</p>
                                <div class="space-y-0.5 max-h-24 overflow-y-auto">
                                    <p v-for="(line, i) in flash.skipped_lines" :key="i"
                                        class="text-[10px] font-mono text-amber-700 dark:text-amber-400 truncate bg-amber-500/5 px-1.5 py-0.5 rounded">
                                        {{ line }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Imported Maps table ── -->
        <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/[0.07] flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="font-bold text-rapanel-navy-900 dark:text-white">{{ __('Imported Maps') }}</h2>
                    <p class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark mt-0.5">
                        {{ __('Re-upload a spawn file to update a specific map. Use the trash button to remove a map\'s spawns only.') }}
                    </p>
                </div>
                <div class="relative shrink-0">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-rapanel-text-light/40 dark:text-white/30" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input v-model="searchInput" type="search" @input="doSearch"
                        :placeholder="__('Search map...')"
                        class="pl-8 pr-3 py-1.5 text-xs rounded-lg border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.04] text-rapanel-navy-900 dark:text-white placeholder-rapanel-text-light/40 focus:outline-none focus:border-rapanel-blue/50 transition w-44" />
                </div>
            </div>

            <div class="overflow-x-auto">
                <table v-if="mapList.data.length > 0" class="w-full text-sm">
                    <thead class="bg-rapanel-navy-100 dark:bg-rapanel-surface">
                        <tr>
                            <th class="px-4 py-2.5 text-left">
                                <button @click="doSort('map_name')" class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest transition"
                                    :class="sortState('map_name') !== 'none' ? 'text-rapanel-blue' : 'text-rapanel-text-light/60 dark:text-white/40 hover:text-rapanel-navy-900 dark:hover:text-white'">
                                    {{ __('Map') }}
                                    <span class="inline-flex flex-col gap-px leading-none">
                                        <svg class="w-2 h-1.5" viewBox="0 0 8 5" fill="currentColor" :class="sortState('map_name') === 'asc' ? 'opacity-100' : 'opacity-30'"><path d="M4 0L8 5H0L4 0Z"/></svg>
                                        <svg class="w-2 h-1.5" viewBox="0 0 8 5" fill="currentColor" :class="sortState('map_name') === 'desc' ? 'opacity-100' : 'opacity-30'"><path d="M4 5L0 0H8L4 5Z"/></svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-4 py-2.5 text-left">
                                <button @click="doSort('display_name')" class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest transition"
                                    :class="sortState('display_name') !== 'none' ? 'text-rapanel-blue' : 'text-rapanel-text-light/60 dark:text-white/40 hover:text-rapanel-navy-900 dark:hover:text-white'">
                                    {{ __('Display Name') }}
                                    <span class="inline-flex flex-col gap-px leading-none">
                                        <svg class="w-2 h-1.5" viewBox="0 0 8 5" fill="currentColor" :class="sortState('display_name') === 'asc' ? 'opacity-100' : 'opacity-30'"><path d="M4 0L8 5H0L4 0Z"/></svg>
                                        <svg class="w-2 h-1.5" viewBox="0 0 8 5" fill="currentColor" :class="sortState('display_name') === 'desc' ? 'opacity-100' : 'opacity-30'"><path d="M4 5L0 0H8L4 5Z"/></svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-black uppercase tracking-widest text-rapanel-text-light/60 dark:text-white/40">
                                {{ __('Background') }}
                            </th>
                            <th class="px-4 py-2.5 text-left">
                                <button @click="doSort('width')" class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest transition"
                                    :class="sortState('width') !== 'none' ? 'text-rapanel-blue' : 'text-rapanel-text-light/60 dark:text-white/40 hover:text-rapanel-navy-900 dark:hover:text-white'">
                                    {{ __('Dimensions') }}
                                    <span class="inline-flex flex-col gap-px leading-none">
                                        <svg class="w-2 h-1.5" viewBox="0 0 8 5" fill="currentColor" :class="sortState('width') === 'asc' ? 'opacity-100' : 'opacity-30'"><path d="M4 0L8 5H0L4 0Z"/></svg>
                                        <svg class="w-2 h-1.5" viewBox="0 0 8 5" fill="currentColor" :class="sortState('width') === 'desc' ? 'opacity-100' : 'opacity-30'"><path d="M4 5L0 0H8L4 5Z"/></svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-4 py-2.5 text-left">
                                <button @click="doSort('updated_at')" class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest transition"
                                    :class="sortState('updated_at') !== 'none' ? 'text-rapanel-blue' : 'text-rapanel-text-light/60 dark:text-white/40 hover:text-rapanel-navy-900 dark:hover:text-white'">
                                    {{ __('Cache updated') }}
                                    <span class="inline-flex flex-col gap-px leading-none">
                                        <svg class="w-2 h-1.5" viewBox="0 0 8 5" fill="currentColor" :class="sortState('updated_at') === 'asc' ? 'opacity-100' : 'opacity-30'"><path d="M4 0L8 5H0L4 0Z"/></svg>
                                        <svg class="w-2 h-1.5" viewBox="0 0 8 5" fill="currentColor" :class="sortState('updated_at') === 'desc' ? 'opacity-100' : 'opacity-30'"><path d="M4 5L0 0H8L4 5Z"/></svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-4 py-2.5 text-right">
                                <button @click="doSort('spawns_count')" class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest transition ml-auto"
                                    :class="sortState('spawns_count') !== 'none' ? 'text-rapanel-blue' : 'text-rapanel-text-light/60 dark:text-white/40 hover:text-rapanel-navy-900 dark:hover:text-white'">
                                    {{ __('Spawns') }}
                                    <span class="inline-flex flex-col gap-px leading-none">
                                        <svg class="w-2 h-1.5" viewBox="0 0 8 5" fill="currentColor" :class="sortState('spawns_count') === 'asc' ? 'opacity-100' : 'opacity-30'"><path d="M4 0L8 5H0L4 0Z"/></svg>
                                        <svg class="w-2 h-1.5" viewBox="0 0 8 5" fill="currentColor" :class="sortState('spawns_count') === 'desc' ? 'opacity-100' : 'opacity-30'"><path d="M4 5L0 0H8L4 5Z"/></svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-4 py-2.5 text-left">
                                <button @click="doSort('spawns_max_updated_at')" class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest transition"
                                    :class="sortState('spawns_max_updated_at') !== 'none' ? 'text-rapanel-blue' : 'text-rapanel-text-light/60 dark:text-white/40 hover:text-rapanel-navy-900 dark:hover:text-white'">
                                    {{ __('Last updated') }}
                                    <span class="inline-flex flex-col gap-px leading-none">
                                        <svg class="w-2 h-1.5" viewBox="0 0 8 5" fill="currentColor" :class="sortState('spawns_max_updated_at') === 'asc' ? 'opacity-100' : 'opacity-30'"><path d="M4 0L8 5H0L4 0Z"/></svg>
                                        <svg class="w-2 h-1.5" viewBox="0 0 8 5" fill="currentColor" :class="sortState('spawns_max_updated_at') === 'desc' ? 'opacity-100' : 'opacity-30'"><path d="M4 5L0 0H8L4 5Z"/></svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-4 py-2.5 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rapanel-navy-50 dark:divide-white/[0.04]">
                        <tr v-for="map in mapList.data" :key="map.map_name"
                            class="hover:bg-rapanel-navy-50/50 dark:hover:bg-white/[0.02] transition">
                            <td class="px-4 py-2.5">
                                <span class="font-mono text-xs font-bold text-rapanel-navy-900 dark:text-white">{{ map.map_name }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-xs text-rapanel-text-light dark:text-rapanel-text-dark">
                                <span v-if="map.display_name" class="text-rapanel-navy-900 dark:text-white">{{ map.display_name }}</span>
                                <span v-else class="text-rapanel-text-light/30 dark:text-white/20">—</span>
                            </td>
                            <td class="px-4 py-2.5">
                                <span v-if="map.background_bmp"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-rapanel-navy-100 dark:bg-white/[0.06] text-rapanel-text-light dark:text-rapanel-text-dark border border-rapanel-navy-100 dark:border-white/[0.08]">
                                    {{ map.background_bmp }}
                                </span>
                                <span v-else class="text-xs text-rapanel-text-light/30 dark:text-white/20">—</span>
                            </td>
                            <td class="px-4 py-2.5 text-xs text-rapanel-text-light dark:text-rapanel-text-dark tabular-nums">
                                {{ map.width }}×{{ map.height }}
                            </td>
                            <td class="px-4 py-2.5 text-xs text-rapanel-text-light dark:text-rapanel-text-dark">
                                {{ dt(map.updated_at) }}
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <span v-if="map.spawns_count > 0"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rapanel-blue/10 text-rapanel-blue border border-rapanel-blue/20 tabular-nums">
                                    {{ map.spawns_count.toLocaleString() }}
                                </span>
                                <span v-else class="text-xs text-rapanel-text-light/40 dark:text-white/20">—</span>
                            </td>
                            <td class="px-4 py-2.5 text-xs text-rapanel-text-light dark:text-rapanel-text-dark">
                                {{ dt(map.spawns_max_updated_at) }}
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <button v-if="map.spawns_count > 0"
                                    @click="confirmDeleteMap = map.map_name"
                                    class="p-1.5 rounded-lg text-rapanel-danger/50 hover:text-rapanel-danger hover:bg-rapanel-danger/10 transition"
                                    :title="__('Delete spawns for this map')">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-else class="px-6 py-10 text-center text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                    {{ searchInput ? __('No maps match your search.') : __('No maps imported yet. Start by uploading map_cache.dat.') }}
                </div>
            </div>

            <div v-if="mapList.last_page > 1"
                class="px-4 py-3 border-t border-rapanel-navy-50 dark:border-white/[0.04] flex justify-center gap-1">
                <Link v-for="link in mapList.links" :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs transition',
                        link.active ? 'bg-rapanel-blue text-white font-bold' : 'text-rapanel-text-light dark:text-rapanel-text-dark hover:bg-rapanel-navy-100 dark:hover:bg-white/[0.06]',
                        !link.url ? 'opacity-40 pointer-events-none' : '',
                    ]" />
            </div>
        </div>

        <!-- ── Clear Map Cache modal ── -->
        <Teleport to="body">
            <div v-if="showClearMapsModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="showClearMapsModal = false">
                <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-xl border border-rapanel-navy-100 dark:border-white/[0.07] w-full max-w-sm overflow-hidden">
                    <div class="h-[3px] bg-gradient-to-r from-rapanel-danger/70 via-rapanel-danger/30 to-transparent" />
                    <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                        <h2 class="font-bold text-rapanel-navy-900 dark:text-white">{{ __('Clear Map Cache') }}</h2>
                    </div>
                    <div class="px-6 py-5 space-y-4">
                        <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('This will delete all map dimension data. The public map viewer will stop working until you re-import.') }}
                        </p>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showClearMapsModal = false"
                                class="px-4 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 text-sm font-semibold text-rapanel-text-light dark:text-rapanel-text-dark hover:border-rapanel-navy-300 transition">
                                {{ __('Cancel') }}
                            </button>
                            <button @click="submitClearMaps"
                                class="px-4 py-2 rounded-lg bg-rapanel-danger text-white text-sm font-bold hover:opacity-90 transition">
                                {{ __('Clear Map Cache') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── Clear All Spawns modal ── -->
        <Teleport to="body">
            <div v-if="showClearSpawnsModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="showClearSpawnsModal = false">
                <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-xl border border-rapanel-navy-100 dark:border-white/[0.07] w-full max-w-sm overflow-hidden">
                    <div class="h-[3px] bg-gradient-to-r from-rapanel-danger/70 via-rapanel-danger/30 to-transparent" />
                    <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                        <h2 class="font-bold text-rapanel-navy-900 dark:text-white">{{ __('Clear All Spawns') }}</h2>
                    </div>
                    <div class="px-6 py-5 space-y-4">
                        <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('Are you sure you want to delete all spawn entries?') }}
                        </p>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showClearSpawnsModal = false"
                                class="px-4 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 text-sm font-semibold text-rapanel-text-light dark:text-rapanel-text-dark hover:border-rapanel-navy-300 transition">
                                {{ __('Cancel') }}
                            </button>
                            <button @click="submitClearSpawns"
                                class="px-4 py-2 rounded-lg bg-rapanel-danger text-white text-sm font-bold hover:opacity-90 transition">
                                {{ __('Clear All Spawns') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── Per-map delete confirm ── -->
        <Teleport to="body">
            <div v-if="confirmDeleteMap"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="confirmDeleteMap = null">
                <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-xl border border-rapanel-navy-100 dark:border-white/[0.07] w-full max-w-sm overflow-hidden">
                    <div class="h-[3px] bg-gradient-to-r from-rapanel-danger/70 via-rapanel-danger/30 to-transparent" />
                    <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                        <h2 class="font-bold text-rapanel-navy-900 dark:text-white">{{ __('Delete spawns') }}</h2>
                    </div>
                    <div class="px-6 py-5 space-y-4">
                        <p class="text-sm text-rapanel-text-light dark:text-rapanel-text-dark">
                            {{ __('Delete all spawn entries for') }}
                            <span class="font-mono font-bold text-rapanel-navy-900 dark:text-white">{{ confirmDeleteMap }}</span>?
                        </p>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="confirmDeleteMap = null"
                                class="px-4 py-2 rounded-lg border border-rapanel-navy-100 dark:border-white/10 text-sm font-semibold text-rapanel-text-light dark:text-rapanel-text-dark hover:border-rapanel-navy-300 transition">
                                {{ __('Cancel') }}
                            </button>
                            <button @click="deleteMapSpawns"
                                class="px-4 py-2 rounded-lg bg-rapanel-danger text-white text-sm font-bold hover:opacity-90 transition">
                                {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </AdminLayout>
</template>
