<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import MobDbModal from '@/Components/MobDbModal.vue';
import { useMobDbModal } from '@/Composables/useMobDbModal.js';

const props = defineProps({
    mapName: String,
    mapSize: Object,   // { width, height }
    spawns:  Array,
});

const page = usePage();
const __   = (key) => page.props.translations?.[key] || key;

const { openMobDb } = useMobDbModal();

// ── Coordinate conversion ─────────────────────────────────────────────
// In RO: (0,0) = bottom-left. On web: (0,0) = top-left.
// left% = (x / mapWidth)  * 100
// top%  = ((mapHeight - y) / mapHeight) * 100
const toWebPct = (x, y) => ({
    left: (x / props.mapSize.width)  * 100,
    top:  ((props.mapSize.height - y) / props.mapSize.height) * 100,
});

// For spawns with a radius area:
// area left% = ((x - rx) / mapWidth)  * 100
// area top%  = ((mapHeight - y - ry) / mapHeight) * 100
// area w%    = (rx * 2 / mapWidth)  * 100
// area h%    = (ry * 2 / mapHeight) * 100
const toAreaPct = (x, y, rx, ry) => ({
    left:   ((x - rx) / props.mapSize.width)   * 100,
    top:    ((props.mapSize.height - y - ry) / props.mapSize.height) * 100,
    width:  (rx * 2 / props.mapSize.width)    * 100,
    height: (ry * 2 / props.mapSize.height)   * 100,
});

// ── Spawn classification ─────────────────────────────────────────────
const randomSpawns = computed(() =>
    props.spawns.filter(s => s.x === 0 && s.y === 0 && s.rx === 0 && s.ry === 0)
);

const placedSpawns = computed(() =>
    props.spawns.filter(s => !(s.x === 0 && s.y === 0 && s.rx === 0 && s.ry === 0))
);

const hasArea = (s) => s.rx > 0 || s.ry > 0;

// ── Group ALL mobs for the sidebar (placed + random) ─────────────────
const allMobsGrouped = computed(() => {
    const map = {};
    for (const s of props.spawns) {
        const isRandom = s.x === 0 && s.y === 0 && s.rx === 0 && s.ry === 0;
        if (!map[s.mob_id]) {
            map[s.mob_id] = {
                mob_id:       s.mob_id,
                mob_name:     s.mob_name,
                is_mvp:       s.is_mvp,
                mob_class:    s.mob_class,
                total_amount: 0,
                delay1:       s.delay1,
                has_random:   false,
                has_placed:   false,
                level:        s.level,
                hp:           s.hp,
                element:      s.element,
                race:         s.race,
                size:         s.size,
            };
        }
        map[s.mob_id].total_amount += s.amount;
        if (isRandom) {
            map[s.mob_id].has_random    = true;
            map[s.mob_id].random_amount = (map[s.mob_id].random_amount ?? 0) + s.amount;
        } else {
            map[s.mob_id].has_placed = true;
        }
        if (!map[s.mob_id].delay1 && s.delay1)  map[s.mob_id].delay1 = s.delay1;
    }
    return Object.values(map).sort((a, b) => {
        if (a.is_mvp !== b.is_mvp)                                   return a.is_mvp ? -1 : 1;
        if ((a.mob_class === 'Boss') !== (b.mob_class === 'Boss'))    return a.mob_class === 'Boss' ? -1 : 1;
        return a.mob_name.localeCompare(b.mob_name);
    });
});

const fmtHp = (hp) => {
    if (!hp) return '—';
    if (hp >= 1_000_000) return (hp / 1_000_000).toFixed(hp % 1_000_000 === 0 ? 0 : 1) + 'M';
    if (hp >= 1_000)     return (hp / 1_000).toFixed(hp % 1_000 === 0 ? 0 : 1) + 'K';
    return hp.toString();
};

// ── Active mob (sidebar hover drives map markers) ────────────────────
const activeMobId  = ref(null);
let clearMobTimer  = null;

const onSidebarEnter = (mob) => {
    clearTimeout(clearMobTimer);
    activeMobId.value = mob.mob_id;
};

const onSidebarLeave = () => {
    clearMobTimer = setTimeout(() => { activeMobId.value = null; }, 150);
};

// ── Tooltip / hover state ────────────────────────────────────────────
const hoveredSpawn = ref(null);
const tooltipStyle = ref({});

const onSpawnEnter = (spawn, event) => {
    clearTimeout(clearMobTimer);
    activeMobId.value  = spawn.mob_id;
    hoveredSpawn.value = spawn;
    updateTooltipPos(event);
};

const onSpawnMove = (event) => {
    if (hoveredSpawn.value) updateTooltipPos(event);
};

const onSpawnLeave = () => {
    hoveredSpawn.value = null;
    clearMobTimer = setTimeout(() => { activeMobId.value = null; }, 150);
};

const updateTooltipPos = (event) => {
    const vw     = window.innerWidth ?? 1200;
    const x      = event.clientX;
    const y      = event.clientY;
    const toLeft = x + 180 > vw;
    tooltipStyle.value = toLeft
        ? { left: `${x - 170}px`, top: `${y + 14}px` }
        : { left: `${x + 14}px`,  top: `${y + 14}px` };
};

// ── Mob colour helpers ────────────────────────────────────────────────
const markerClass = (spawn) => {
    if (spawn.is_mvp)               return 'border-rapanel-gold   shadow-rapanel-gold/40';
    if (spawn.mob_class === 'Boss')  return 'border-orange-500    shadow-orange-400/40';
    return                                   'border-rapanel-blue shadow-rapanel-blue/20';
};

const markerBgClass = (spawn) => {
    if (spawn.is_mvp)               return 'bg-rapanel-gold/30';
    if (spawn.mob_class === 'Boss')  return 'bg-orange-500/30';
    return                                   'bg-rapanel-blue/25';
};

const areaBgClass = (spawn) => {
    if (spawn.is_mvp)               return 'bg-rapanel-gold/15  border-rapanel-gold/40';
    if (spawn.mob_class === 'Boss')  return 'bg-orange-500/15   border-orange-400/40';
    return                                   'bg-rapanel-blue/10 border-rapanel-blue/25';
};

// ── Random spawns agrupados para barra inferior del mapa ──────────────
const randomMobsGrouped = computed(() => {
    const map = {};
    for (const s of randomSpawns.value) {
        if (!map[s.mob_id]) map[s.mob_id] = { ...s, total_amount: 0 };
        map[s.mob_id].total_amount += s.amount;
    }
    return Object.values(map).sort((a, b) => a.mob_name.localeCompare(b.mob_name));
});

const toRandomProxy = (mob) => ({
    mob_id:    mob.mob_id,
    mob_name:  mob.mob_name,
    x: 0, y: 0, rx: 0, ry: 0,
    amount:    mob.total_amount,
    delay1:    mob.delay1,
    is_mvp:    mob.is_mvp,
    mob_class: mob.mob_class,
});

// ── Filter (sidebar only — does NOT affect map markers) ──────────────
const filterText = ref('');
const filterMvp  = ref(false);

const filteredMobs = computed(() => {
    let list = allMobsGrouped.value;
    if (filterMvp.value)  list = list.filter(m => m.is_mvp);
    if (filterText.value) {
        const q = filterText.value.toLowerCase();
        list = list.filter(m => m.mob_name.toLowerCase().includes(q) || String(m.mob_id).includes(q));
    }
    return list;
});

// Map markers: only the active mob's placed spawns (empty when nothing hovered)
const activeSpawns = computed(() =>
    activeMobId.value
        ? placedSpawns.value.filter(s => s.mob_id === activeMobId.value)
        : []
);

// Active mob metadata (for overlay color & GIF)
const activeMob = computed(() =>
    activeMobId.value
        ? allMobsGrouped.value.find(m => m.mob_id === activeMobId.value) ?? null
        : null
);

// True when the active mob has any random (whole-map) spawns
const activeIsRandom = computed(() => activeMob.value?.has_random ?? false);

const randomOverlayClass = computed(() => {
    if (!activeMob.value) return '';
    if (activeMob.value.is_mvp)              return 'bg-rapanel-gold/20  border-rapanel-gold/30';
    if (activeMob.value.mob_class === 'Boss') return 'bg-orange-500/20   border-orange-400/30';
    return                                           'bg-rapanel-blue/15  border-rapanel-blue/20';
});

const formatDelay = (ms) => {
    if (!ms || ms <= 0) return __('Instantly');
    const every = __('Every');
    if (ms < 60_000)        return `${every} ${Math.round(ms / 1_000)} ${__('sec')}`;
    if (ms < 3_600_000)     return `${every} ${Math.round(ms / 60_000)} ${__('min')}`;
    if (ms < 86_400_000)    return `${every} ${Math.round(ms / 3_600_000)} ${__('h')}`;
    return `${every} ${Math.round(ms / 86_400_000)} ${__('d')}`;
};
</script>

<template>
    <Head :title="mapName + ' — ' + __('Map DB')" />
    <MainLayout :show-bg="true">

        <!-- Header -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <!-- Back button -->
                <Link :href="route('info.map-db')"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-blue dark:hover:text-rapanel-blue transition group mb-3">
                    <svg class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    {{ __('Map DB') }}
                </Link>
                <div class="flex items-center gap-2 text-xs text-rapanel-text-light dark:text-rapanel-text-dark mb-2">
                    <span class="font-mono text-rapanel-navy-900 dark:text-white">{{ mapName }}</span>
                </div>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-rapanel-navy-900 dark:text-white tracking-tight font-mono">{{ mapName }}</h1>
                        <p class="mt-0.5 text-xs text-rapanel-text-light dark:text-rapanel-text-dark tabular-nums">
                            {{ mapSize.width }}×{{ mapSize.height }} &nbsp;·&nbsp;
                            {{ placedSpawns.length }} {{ __('placed spawns') }} &nbsp;·&nbsp;
                            {{ randomSpawns.length }} {{ __('random spawns') }}
                        </p>
                    </div>

                    <!-- Filters pill bar -->
                    <div class="flex flex-wrap items-center gap-2">
                        <input v-model="filterText" type="search"
                            :placeholder="__('Filter mob...')"
                            class="px-3 py-1.5 text-xs rounded-full border border-rapanel-navy-100 dark:border-white/10 bg-rapanel-navy-50 dark:bg-white/[0.04] text-rapanel-navy-900 dark:text-white placeholder-rapanel-text-light/40 dark:placeholder-white/25 focus:outline-none focus:border-rapanel-blue/50 transition w-36" />
                        <button @click="filterMvp = !filterMvp"
                            :class="filterMvp ? 'bg-rapanel-gold text-rapanel-navy-900 border-rapanel-gold' : 'bg-transparent text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-200 dark:border-white/10 hover:border-rapanel-gold/50'"
                            class="px-3 py-1.5 rounded-full border text-xs font-bold transition">
                            MVP
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col lg:flex-row gap-6">

                <!-- ── Map Viewer ── -->
                <div class="lg:flex-1 lg:max-w-[52%] min-w-0">
                    <div class="relative map-container select-none rounded-2xl overflow-hidden border border-rapanel-navy-100 dark:border-white/[0.07] shadow-sm bg-rapanel-navy-900"
                        @mousemove="onSpawnMove">

                        <!-- Map image (sets the natural aspect ratio) -->
                        <img
                            :src="`/data/maps/${mapName}.png`"
                            :alt="mapName"
                            class="block w-full h-auto"
                            draggable="false"
                            @error="$event.target.style.opacity='0.1'" />

                        <!-- ── Random mob: full-map overlay ── -->
                        <div v-if="activeIsRandom && activeMob"
                            :class="['absolute inset-0 z-10 flex items-center justify-center rounded-2xl border pointer-events-none transition-opacity duration-200', randomOverlayClass]">
                            <div class="flex flex-col items-center gap-2">
                                <img :src="`/data/monsters/${activeMob.mob_id}.gif`"
                                    class="w-20 h-20 object-contain drop-shadow-xl"
                                    style="image-rendering: pixelated"
                                    @error="$event.target.style.display='none'" />
                                <span class="text-[10px] font-black text-white/70 uppercase tracking-widest bg-black/40 px-2 py-0.5 rounded-full">
                                    {{ __('Random') }}<template v-if="activeMob.random_amount > 1"> ×{{ activeMob.random_amount }}</template>
                                </span>
                            </div>
                        </div>

                        <!-- Spawn overlays — positioned in % so they scale with the image -->
                        <template v-for="(spawn, idx) in activeSpawns" :key="idx">

                            <!-- ── Area spawn (has radius) ── -->
                            <template v-if="hasArea(spawn)">
                                <div
                                    :style="{
                                        position: 'absolute',
                                        left:   toAreaPct(spawn.x, spawn.y, spawn.rx, spawn.ry).left   + '%',
                                        top:    toAreaPct(spawn.x, spawn.y, spawn.rx, spawn.ry).top    + '%',
                                        width:  toAreaPct(spawn.x, spawn.y, spawn.rx, spawn.ry).width  + '%',
                                        height: toAreaPct(spawn.x, spawn.y, spawn.rx, spawn.ry).height + '%',
                                        minWidth:  '8px',
                                        minHeight: '8px',
                                    }"
                                    :class="['rounded border pointer-events-none', areaBgClass(spawn)]" />

                                <!-- GIF centred in the area -->
                                <div
                                    :style="{
                                        position: 'absolute',
                                        left:    toWebPct(spawn.x, spawn.y).left + '%',
                                        top:     toWebPct(spawn.x, spawn.y).top  + '%',
                                        transform: 'translate(-50%, -50%)',
                                    }"
                                    class="cursor-pointer z-10"
                                    @mouseenter="onSpawnEnter(spawn, $event)"
                                    @mouseleave="onSpawnLeave">
                                    <div :class="['rounded border-2 overflow-hidden shadow-lg transition-transform hover:scale-125', markerClass(spawn), markerBgClass(spawn)]"
                                        style="width:30px;height:30px;">
                                        <img
                                            :src="`/data/monsters/${spawn.mob_id}.gif`"
                                            :alt="spawn.mob_name"
                                            class="w-full h-full object-contain"
                                            draggable="false"
                                            @error="$event.target.style.display='none'" />
                                    </div>
                                    <!-- Amount badge -->
                                    <div v-if="spawn.amount > 1"
                                        class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-rapanel-navy-900 border border-white/20 text-[9px] font-black text-white flex items-center justify-center tabular-nums">
                                        {{ spawn.amount }}
                                    </div>
                                </div>
                            </template>

                            <!-- ── Point spawn (exact location) ── -->
                            <div v-else
                                :style="{
                                    position: 'absolute',
                                    left:      toWebPct(spawn.x, spawn.y).left + '%',
                                    top:       toWebPct(spawn.x, spawn.y).top  + '%',
                                    transform: 'translate(-50%, -50%)',
                                }"
                                class="cursor-pointer z-10"
                                @mouseenter="onSpawnEnter(spawn, $event)"
                                @mouseleave="onSpawnLeave">
                                <div :class="['rounded border-2 overflow-hidden shadow-lg transition-transform hover:scale-125', markerClass(spawn), markerBgClass(spawn)]"
                                    style="width:30px;height:30px;">
                                    <img
                                        :src="`/data/monsters/${spawn.mob_id}.gif`"
                                        :alt="spawn.mob_name"
                                        class="w-full h-full object-contain"
                                        draggable="false"
                                        @error="$event.target.style.display='none'" />
                                </div>
                                <div v-if="spawn.amount > 1"
                                    class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-rapanel-navy-900 border border-white/20 text-[9px] font-black text-white flex items-center justify-center tabular-nums">
                                    {{ spawn.amount }}
                                </div>
                            </div>
                        </template>


                        <!-- Tooltip -->
                        <Teleport to="body">
                            <div v-if="hoveredSpawn"
                                :style="{ position: 'fixed', zIndex: 9999, pointerEvents: 'none', ...tooltipStyle }"
                                class="bg-rapanel-navy-900 text-white rounded-xl border border-white/10 shadow-xl px-3 py-2.5 text-xs min-w-[140px]">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <img :src="`/data/monsters/${hoveredSpawn.mob_id}.gif`"
                                        class="w-6 h-6 object-contain"
                                        @error="$event.target.style.display='none'" />
                                    <span class="font-bold text-sm">{{ hoveredSpawn.mob_name }}</span>
                                    <span v-if="hoveredSpawn.is_mvp"
                                        class="ml-auto text-[9px] font-black bg-rapanel-gold/20 text-rapanel-gold border border-rapanel-gold/40 px-1.5 py-0.5 rounded-full">MVP</span>
                                </div>
                                <div class="grid grid-cols-2 gap-x-3 gap-y-0.5 text-[10px] text-white/60">
                                    <span>ID</span>     <span class="text-white font-mono">{{ hoveredSpawn.mob_id }}</span>
                                    <span>{{ __('Pos') }}</span>   <span class="text-white font-mono">{{ hoveredSpawn.x }}, {{ hoveredSpawn.y }}</span>
                                    <span v-if="hasArea(hoveredSpawn)">{{ __('Radius') }}</span>
                                    <span v-if="hasArea(hoveredSpawn)" class="text-white font-mono">{{ hoveredSpawn.rx }}×{{ hoveredSpawn.ry }}</span>
                                    <span>{{ __('Amount') }}</span> <span class="text-white font-mono">{{ hoveredSpawn.amount }}</span>
                                    <span>{{ __('Respawn') }}</span><span class="text-white font-mono">{{ formatDelay(hoveredSpawn.delay1) }}</span>
                                </div>
                            </div>
                        </Teleport>

                        <!-- No image fallback overlay -->
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <!-- intentionally empty — img @error dims it -->
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="mt-3 flex flex-wrap gap-3 text-[10px] text-rapanel-text-light dark:text-rapanel-text-dark">
                        <div class="flex items-center gap-1.5">
                            <div class="w-4 h-4 rounded border-2 border-rapanel-gold bg-[#0b1120]" />
                            <span>MVP</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-4 h-4 rounded border-2 border-orange-500 bg-[#0b1120]" />
                            <span>Boss</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-4 h-4 rounded border-2 border-rapanel-blue bg-[#0b1120]" />
                            <span>{{ __('Normal') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-5 h-3 rounded border border-rapanel-blue/40 bg-rapanel-blue/10" />
                            <span>{{ __('Spawn area') }}</span>
                        </div>
                    </div>
                </div>

                <!-- ── Mob list sidebar ── -->
                <div v-if="allMobsGrouped.length > 0" class="lg:flex-1 shrink-0">
                    <div class="bg-white dark:bg-[#0f1829] rounded-2xl border border-rapanel-navy-100 dark:border-white/[0.07] shadow-sm overflow-hidden sticky top-4">
                        <div class="px-4 py-3 border-b border-rapanel-navy-100 dark:border-white/[0.07]">
                            <h3 class="text-xs font-black uppercase tracking-widest text-rapanel-text-light/60 dark:text-white/40">
                                {{ __('Monsters') }}
                            </h3>
                            <p class="text-[10px] text-rapanel-text-light dark:text-rapanel-text-dark mt-0.5">
                                {{ allMobsGrouped.length }} {{ __('unique mobs on this map') }}
                            </p>
                        </div>
                        <ul class="max-h-[600px] overflow-y-auto divide-y divide-rapanel-navy-50 dark:divide-white/[0.04]">
                            <li v-for="mob in filteredMobs" :key="mob.mob_id"
                                class="flex items-start gap-3 px-4 py-3 hover:bg-rapanel-navy-50 dark:hover:bg-white/[0.03] transition cursor-default"
                                @mouseenter="onSidebarEnter(mob)"
                                @mouseleave="onSidebarLeave">

                                <!-- GIF — libre, sin caja, con texto random debajo -->
                                <div class="shrink-0 flex flex-col items-center gap-0.5 mt-0.5">
                                    <div class="w-11 h-11 flex items-center justify-center">
                                        <img :src="`/data/monsters/${mob.mob_id}.gif`"
                                            class="max-w-full max-h-full object-contain"
                                            @error="$event.target.style.opacity='0'" />
                                    </div>
                                </div>

                                <!-- Columna principal: nombre + stats -->
                                <div class="flex-1 min-w-0 space-y-1">
                                    <!-- Nombre + badges -->
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <p class="text-sm font-bold text-rapanel-navy-900 dark:text-white leading-tight">{{ mob.mob_name }}</p>
                                        <span v-if="mob.is_mvp"
                                            class="text-[8px] font-black px-1.5 py-px rounded-full bg-rapanel-gold/15 text-rapanel-gold border border-rapanel-gold/30 leading-none">MVP</span>
                                        <span v-else-if="mob.mob_class === 'Boss'"
                                            class="text-[8px] font-black px-1.5 py-px rounded-full bg-orange-500/15 text-orange-400 border border-orange-500/30 leading-none">BOSS</span>
                                    </div>
                                    <!-- Lv · HP -->
                                    <div class="flex items-center gap-3">
                                        <span v-if="mob.level" class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark tabular-nums">
                                            <span class="text-rapanel-navy-900/40 dark:text-white/30">Lv</span> {{ mob.level }}
                                        </span>
                                        <span v-if="mob.hp" class="text-xs text-rapanel-text-light dark:text-rapanel-text-dark tabular-nums">
                                            <span class="text-rapanel-navy-900/40 dark:text-white/30">HP</span> {{ fmtHp(mob.hp) }}
                                        </span>
                                    </div>
                                    <!-- Size · Race · Element -->
                                    <div v-if="mob.size || mob.race || mob.element" class="flex items-center gap-1 flex-wrap">
                                        <span v-if="mob.size"
                                            class="text-[10px] px-1.5 py-px rounded bg-rapanel-navy-100/60 dark:bg-white/[0.06] text-rapanel-text-light dark:text-rapanel-text-dark leading-none">
                                            {{ mob.size }}
                                        </span>
                                        <span class="text-rapanel-navy-100 dark:text-white/10 text-[10px]" v-if="mob.race">·</span>
                                        <span v-if="mob.race"
                                            class="text-[10px] px-1.5 py-px rounded bg-rapanel-navy-100/60 dark:bg-white/[0.06] text-rapanel-text-light dark:text-rapanel-text-dark leading-none">
                                            {{ mob.race }}
                                        </span>
                                        <span class="text-rapanel-navy-100 dark:text-white/10 text-[10px]" v-if="mob.element">·</span>
                                        <span v-if="mob.element"
                                            class="text-[10px] px-1.5 py-px rounded bg-rapanel-navy-100/60 dark:bg-white/[0.06] text-rapanel-text-light dark:text-rapanel-text-dark leading-none">
                                            {{ mob.element }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Columna derecha: cantidad + respawn + botón -->
                                <div class="shrink-0 flex flex-col items-end gap-1.5 mt-0.5">
                                    <div class="text-right">
                                        <p class="text-sm font-black text-rapanel-navy-900 dark:text-white tabular-nums leading-tight">× {{ mob.total_amount }}</p>
                                        <p class="text-[10px] text-rapanel-text-light dark:text-rapanel-text-dark tabular-nums leading-tight whitespace-nowrap">
                                            {{ formatDelay(mob.delay1) }}
                                        </p>
                                    </div>
                                    <button
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rapanel-blue/10 text-rapanel-blue text-[10px] font-bold border border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white transition whitespace-nowrap"
                                        @click.stop="openMobDb({ id: mob.mob_id, name: mob.mob_name, is_mvp: mob.is_mvp, class: mob.mob_class, element: mob.element, race: mob.race, size: mob.size })">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                                        </svg>
                                        {{ __('View details') }}
                                    </button>
                                </div>

                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

    </MainLayout>

    <MobDbModal />
</template>
