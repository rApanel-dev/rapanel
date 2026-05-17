<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import jsVectorMap from 'jsvectormap';
import 'jsvectormap/dist/maps/world.js';
import 'jsvectormap/dist/jsvectormap.min.css';

const props = defineProps({
    series: { type: Object, default: () => ({}) },
});

const containerRef = ref(null);
let mapInstance    = null;

const countryNames = {
    AF:'Afghanistan',AR:'Argentina',AU:'Australia',AT:'Austria',BE:'Belgium',BR:'Brazil',CA:'Canada',
    CL:'Chile',CN:'China',CO:'Colombia',CR:'Costa Rica',CZ:'Czech Republic',DK:'Denmark',DO:'Dominican Republic',
    EC:'Ecuador',EG:'Egypt',FI:'Finland',FR:'France',DE:'Germany',GR:'Greece',GT:'Guatemala',
    HK:'Hong Kong',HU:'Hungary',IN:'India',ID:'Indonesia',IE:'Ireland',IL:'Israel',IT:'Italy',
    JP:'Japan',MY:'Malaysia',MX:'Mexico',MA:'Morocco',NL:'Netherlands',NZ:'New Zealand',NG:'Nigeria',
    NO:'Norway',PA:'Panama',PE:'Peru',PH:'Philippines',PL:'Poland',PT:'Portugal',PR:'Puerto Rico',
    RO:'Romania',RU:'Russia',SA:'Saudi Arabia',SG:'Singapore',ZA:'South Africa',KR:'South Korea',
    ES:'Spain',SE:'Sweden',CH:'Switzerland',TW:'Taiwan',TH:'Thailand',TR:'Turkey',UA:'Ukraine',
    AE:'United Arab Emirates',GB:'United Kingdom',US:'United States',UY:'Uruguay',VE:'Venezuela',VN:'Vietnam',
};

const isDark = () => document.documentElement.classList.contains('dark');

const buildMap = () => {
    if (!containerRef.value) return;

    if (mapInstance) {
        try { mapInstance.destroy(); } catch {}
        mapInstance = null;
    }

    // jsvectormap leaves child nodes — clear manually
    containerRef.value.innerHTML = '';

    const dark = isDark();

    mapInstance = new jsVectorMap({
        selector: containerRef.value,
        map: 'world',
        draggable: true,
        zoomButtons: false,
        zoomOnScroll: false,
        regionsSelectable: false,
        markersSelectable: false,

        backgroundColor: 'transparent',

        regionStyle: {
            initial: {
                fill:        dark ? '#1e3a5f' : '#dbeafe',
                stroke:      dark ? '#0c1929' : '#ffffff',
                strokeWidth: 0.4,
            },
            hover: {
                fillOpacity: 0.75,
                cursor: 'pointer',
            },
        },

        visualizeData: {
            scale:  [dark ? '#1d4ed8' : '#93c5fd', dark ? '#60a5fa' : '#1d4ed8'],
            values: props.series,
        },

        onRegionTooltipShow(_, tooltip, code) {
            const count = props.series[code] || 0;
            const name  = countryNames[code] ?? code;
            tooltip.text(
                `<span style="font-weight:600">${name}</span>` +
                `<br><span style="font-size:11px">${count} user${count !== 1 ? 's' : ''}</span>`,
                true
            );
        },
    });
};

let themeObserver;
let resizeObserver;
let resizeTimer;

const debouncedBuild = () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(buildMap, 120);
};

onMounted(() => {
    buildMap();

    // Rebuild when dark/light mode toggles
    themeObserver = new MutationObserver(buildMap);
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    // Rebuild when the container is resized (e.g. more countries expand the right column)
    resizeObserver = new ResizeObserver(debouncedBuild);
    resizeObserver.observe(containerRef.value);
});

onUnmounted(() => {
    clearTimeout(resizeTimer);
    themeObserver?.disconnect();
    resizeObserver?.disconnect();
    if (mapInstance) {
        try { mapInstance.destroy(); } catch {}
        mapInstance = null;
    }
});
</script>

<style>
.jvm-tooltip {
    background-color: #0f172a !important;
    color: #f1f5f9 !important;
    border: 1px solid rgba(255,255,255,0.12) !important;
    border-radius: 8px !important;
    font-size: 12px !important;
    padding: 7px 12px !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.4) !important;
    font-family: inherit !important;
    line-height: 1.5 !important;
}
.jvm-zoom-btn {
    display: none !important;
}
</style>

<template>
    <div ref="containerRef" class="w-full h-full" style="min-height: 300px;"></div>
</template>
