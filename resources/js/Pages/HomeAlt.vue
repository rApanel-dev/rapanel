<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const page = usePage()
const __ = (key) => page.props.translations?.[key] || key
const safeRoute = (name, params = {}) => {
    try { return route(name, params) } catch { return '#' }
}

const st      = computed(() => page.props.siteSettings   ?? {})
const srv     = computed(() => page.props.serverStatus   ?? { online: false, players: 0 })
const news    = computed(() => page.props.latestNews     ?? [])

// ── Refs ────────────────────────────────────────────────────────────────────
const canvasRef      = ref(null)
const statsRef       = ref(null)
const featuresRef    = ref(null)
const infoRef        = ref(null)
const ctaRef         = ref(null)
const ctaBtnRef      = ref(null)
const burstParticles = ref([])
const charFrame      = ref(1)
const charOpacity    = ref(1)

let rafId     = null
let charTimer = null

// Paleta de tarjetas (feat + info) — configurable en Admin → Appearance → Home.
// Lee del tema guardado (siteSettings.theme.home.palette); fallback = colores actuales.
const DEFAULT_PALETTE = ['#4A90E2', '#F1C40F', '#2ECC71', '#a855f7', '#E74C3C', '#e2e8f0']
const cardPalette = computed(() => {
    try {
        const p = JSON.parse(st.value.theme || 'null')?.home?.palette
        return Array.isArray(p) && p.length === 6 ? p : DEFAULT_PALETTE
    } catch { return DEFAULT_PALETTE }
})

const handleScroll = () => {
    charOpacity.value = Math.max(0, 1 - window.scrollY / 280)
}

const charSequence = [1, 2, 3, 2]
let charSeqIndex = 0

const startCharAnimation = () => {
    clearInterval(charTimer)
    charTimer = setInterval(() => {
        charSeqIndex = (charSeqIndex + 1) % charSequence.length
        charFrame.value = charSequence[charSeqIndex]
    }, 800)
}

const onCharEnter = () => {
    clearInterval(charTimer)
    charFrame.value = 4
}

const onCharLeave = () => {
    startCharAnimation()
}

// ── Personaje configurable (Admin → Appearance → Character) ──────────────────
// Sin configuración guardada, todo cae a los valores actuales → cero regresión.
const charEnabled  = computed(() => st.value.home_char_enabled !== '0')
const charOnMobile = computed(() => st.value.home_char_mobile === '1')
const charSrc = computed(() => {
    const custom = st.value['home_char_frame' + charFrame.value]
    return custom ? '/storage/' + custom : `/images/ex_se_star0${charFrame.value}.png`
})
const charPosClass = computed(() => ({
    left:   'bottom-0 left-0',
    center: 'bottom-0 left-1/2 -translate-x-1/2',
    right:  'bottom-0 right-0',
}[st.value.home_char_position] || 'bottom-0 right-0'))
// Tamaño relativo al viewport como el original (.ha-char-img = 72vh). "Grande" = original.
const charSizeStyle = computed(() => {
    const s = st.value.home_char_size
    return s ? { height: ({ sm: '46vh', md: '58vh', lg: '72vh' })[s] ?? '' } : {}
})

// ── Canvas starfield ─────────────────────────────────────────────────────────
const initCanvas = () => {
    const canvas = canvasRef.value
    if (!canvas) return
    const ctx = canvas.getContext('2d')

    const resize = () => {
        canvas.width  = canvas.offsetWidth
        canvas.height = canvas.offsetHeight
    }
    resize()
    window.addEventListener('resize', resize)

    const stars = Array.from({ length: 200 }, () => ({
        x: Math.random(),
        y: Math.random(),
        r: Math.random() * 1.4 + 0.2,
        alpha: Math.random() * 0.7 + 0.15,
        speed: Math.random() * 0.00015 + 0.00005,
        colorDark:  Math.random() > 0.9 ? '#F1C40F'  : Math.random() > 0.75 ? '#4A90E2'  : '#ffffff',
        colorLight: Math.random() > 0.9 ? '#b45309'  : Math.random() > 0.75 ? '#1d4ed8'  : '#334155',
        twinkle: Math.random() * Math.PI * 2,
        twinkleSpeed: Math.random() * 0.02 + 0.005,
    }))

    const nebulasDark = [
        { x: 0.15, y: 0.3,  r: 180, c: 'rgb(var(--ha-accent-rgb,74 144 226) / 0.06)' },
        { x: 0.85, y: 0.6,  r: 200, c: 'rgba(168,85,247,0.05)' },
        { x: 0.5,  y: 0.85, r: 150, c: 'rgba(241,196,15,0.04)'  },
    ]
    const nebulasLight = [
        { x: 0.15, y: 0.3,  r: 180, c: 'rgb(var(--ha-accent-rgb,74 144 226) / 0.18)' },
        { x: 0.85, y: 0.6,  r: 200, c: 'rgba(168,85,247,0.14)' },
        { x: 0.5,  y: 0.85, r: 150, c: 'rgba(241,196,15,0.12)'  },
    ]

    const draw = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height)
        const dark = document.documentElement.classList.contains('dark')
        const nebulas = dark ? nebulasDark : nebulasLight

        // nebulas
        nebulas.forEach(n => {
            const grd = ctx.createRadialGradient(
                n.x * canvas.width, n.y * canvas.height, 0,
                n.x * canvas.width, n.y * canvas.height, n.r
            )
            grd.addColorStop(0, n.c)
            grd.addColorStop(1, 'transparent')
            ctx.fillStyle = grd
            ctx.beginPath()
            ctx.arc(n.x * canvas.width, n.y * canvas.height, n.r, 0, Math.PI * 2)
            ctx.fill()
        })

        // stars
        stars.forEach(s => {
            s.twinkle += s.twinkleSpeed
            const baseAlpha = dark ? s.alpha : Math.min(s.alpha * 1.4, 1)
            const alpha = baseAlpha * (0.6 + 0.4 * Math.sin(s.twinkle))
            s.y -= s.speed
            if (s.y < 0) { s.y = 1; s.x = Math.random() }

            ctx.beginPath()
            ctx.arc(s.x * canvas.width, s.y * canvas.height, s.r, 0, Math.PI * 2)
            ctx.fillStyle = dark ? s.colorDark : s.colorLight
            ctx.globalAlpha = alpha
            ctx.fill()
        })
        ctx.globalAlpha = 1
        rafId = requestAnimationFrame(draw)
    }
    draw()
}

// ── 3D card tilt ─────────────────────────────────────────────────────────────
const initTilt = () => {
    document.querySelectorAll('.feat-card').forEach(card => {
        card.addEventListener('mousemove', e => {
            const r = card.getBoundingClientRect()
            const x = ((e.clientX - r.left) / r.width  - 0.5) * 18
            const y = ((e.clientY - r.top)  / r.height - 0.5) * 18
            card.style.transform = `perspective(700px) rotateY(${x}deg) rotateX(${-y}deg) translateZ(8px)`
        })
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(700px) rotateY(0) rotateX(0) translateZ(0)'
        })
    })
}

// ── Counter ──────────────────────────────────────────────────────────────────
const countUp = (el, target, dur = 1800, suffix = '') => {
    const t0 = performance.now()
    const step = ts => {
        const p = Math.min((ts - t0) / dur, 1)
        const v = Math.floor((1 - Math.pow(1 - p, 3)) * target)
        el.textContent = v.toLocaleString() + suffix
        if (p < 1) requestAnimationFrame(step)
    }
    requestAnimationFrame(step)
}

// ── GSAP loader ──────────────────────────────────────────────────────────────
// Resuelve true/false. NUNCA cuelga: si el CDN está bloqueado (onerror) o tarda
// (>4s), resuelve y el caller revela los textos sin animación (no quedan ocultos).
const loadGSAP = () => new Promise(resolve => {
    if (window.gsap && window.ScrollTrigger) { resolve(true); return }
    let done = false
    const finish = (ok) => { if (!done) { done = true; resolve(ok) } }
    const s1 = document.createElement('script')
    s1.src = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js'
    s1.onerror = () => finish(false)
    s1.onload = () => {
        const s2 = document.createElement('script')
        s2.src = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js'
        s2.onerror = () => finish(false)
        s2.onload = () => { window.gsap.registerPlugin(window.ScrollTrigger); finish(true) }
        document.head.appendChild(s2)
    }
    document.head.appendChild(s1)
    setTimeout(() => finish(!!(window.gsap && window.ScrollTrigger)), 4000)
})

// Fallback: si GSAP no está disponible, muestra los elementos que nacen opacity-0.
const revealAll = () => {
    document.querySelectorAll('.ha-title, .ha-subtitle, .ha-cta, .stat-chip, .feat-card, .info-block, .cta-inner')
        .forEach(el => { el.style.opacity = '1'; el.style.transform = 'none' })
}

const initGSAP = () => {
    const { gsap, ScrollTrigger } = window

    // Hero entrance
    // Hero: la entrada de título/subtítulo/CTA ahora es animación CSS pura
    // (.ha-title/.ha-subtitle/.ha-cta) → no depende de GSAP ni del CDN, los textos
    // siempre se ven. GSAP solo se usa para las animaciones de scroll de abajo.

    // Stats
    ScrollTrigger.create({
        trigger: statsRef.value,
        start: 'top 82%',
        onEnter: () => {
            gsap.fromTo('.stat-chip', { opacity: 0, y: 30 }, { opacity: 1, y: 0, duration: 0.6, stagger: 0.12, ease: 'power2.out' })
            document.querySelectorAll('[data-count]').forEach(el =>
                countUp(el, parseInt(el.dataset.count), 2000, el.dataset.suffix ?? '')
            )
        },
        once: true,
    })

    // Features
    ScrollTrigger.create({
        trigger: featuresRef.value,
        start: 'top 80%',
        onEnter: () => {
            gsap.fromTo('.feat-card', { opacity: 0, y: 40 }, {
                opacity: 1, y: 0, duration: 0.65, stagger: 0.15, ease: 'power3.out',
            })
        },
        once: true,
    })

    // Info blocks
    ScrollTrigger.create({
        trigger: infoRef.value,
        start: 'top 78%',
        onEnter: () => {
            gsap.fromTo('.info-block', { opacity: 0, x: -35 }, {
                opacity: 1, x: 0, duration: 0.6, stagger: 0.1, ease: 'power2.out',
            })
            document.querySelectorAll('[data-count-info]').forEach(el =>
                countUp(el, parseInt(el.dataset.countInfo), 1500)
            )
        },
        once: true,
    })

    // CTA
    ScrollTrigger.create({
        trigger: ctaRef.value,
        start: 'top 80%',
        onEnter: () => gsap.fromTo('.cta-inner', { opacity: 0, scale: 0.94 }, { opacity: 1, scale: 1, duration: 1, ease: 'power2.out' }),
        once: true,
    })
}

// ── Button particle burst ─────────────────────────────────────────────────────
const doBurst = e => {
    const btn = ctaBtnRef.value?.$el ?? ctaBtnRef.value
    if (!btn) return
    const r = btn.getBoundingClientRect()
    const cx = e.clientX - r.left
    const cy = e.clientY - r.top
    burstParticles.value = Array.from({ length: 24 }, (_, i) => {
        const angle = (i / 24) * Math.PI * 2
        const spd = 40 + Math.random() * 55
        return {
            id: Date.now() + i,
            x: cx, y: cy,
            tx: cx + Math.cos(angle) * spd,
            ty: cy + Math.sin(angle) * spd,
            col: Math.random() > 0.5 ? '#F1C40F' : '#4A90E2',
        }
    })
    setTimeout(() => { burstParticles.value = [] }, 900)
}

// ── Feature cards data ────────────────────────────────────────────────────────
const infoBlocks = computed(() => {
    try {
        const raw = st.value.home_info_blocks
        if (raw) return JSON.parse(raw)
    } catch {}
    return [
        { id: 'rates',   show: true, icon_type: 'icon', image: null },
        { id: 'level',   show: true, icon_type: 'icon', image: null },
        { id: 'mode',    show: true, icon_type: 'icon', image: null },
        { id: 'episode', show: true, icon_type: 'icon', image: null },
        { id: 'intl',    show: true, icon_type: 'icon', image: null },
    ]
})

// Color por bloque: usa el guardado en BD o el color de la paleta por índice
const infoColors = computed(() =>
    infoBlocks.value.map((b, i) => b.color || cardPalette.value[i])
)

const highlightCards = computed(() => {
    try {
        const raw = st.value.home_highlight_cards
        if (raw) return JSON.parse(raw).filter(c => c.show !== false)
    } catch {}
    return [
        { image: '', title: 'New Battlegrounds', desc: 'New Battleground modes designed to improve GvG and competitive gameplay, not found on other servers.', url: '#' },
        { image: '', title: 'Custom Events',     desc: 'Regular in-game events with exclusive prizes and unique rewards for all players.',                   url: '#' },
        { image: '', title: 'Exclusive Quests',  desc: 'Unique quest lines with story-driven content and rare item rewards to discover.',                    url: '#' },
        { image: '', title: 'Item Database',     desc: 'Full searchable database of every item imported directly from rAthena data files.',                  url: '#' },
    ]
})

const defaultFeatureCards = [
    { color: '#4A90E2', title: 'Dashboard',    desc: 'Create and manage game accounts, view characters, reset position — all from your browser.', icon_svg: null, enabled: true, icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>` },
    { color: '#F1C40F', title: 'Item DB',      desc: 'Full searchable item database imported from rAthena YAML + itemInfo.lua with drop sources and card slots.', icon_svg: null, enabled: true, icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>` },
    { color: '#2ECC71', title: 'Wiki',         desc: 'Docs-style public knowledge base organized in sections and articles with a rich-text editor.', icon_svg: null, enabled: true, icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>` },
    { color: '#a855f7', title: 'MvP Cards',   desc: 'Live MvP card browser with holder counts, item properties and illustrated card portraits.', icon_svg: null, enabled: true, icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>` },
    { color: '#4A90E2', title: 'Live Console', desc: 'Real-time stdout/stderr for each server process via WebSocket. Start, stop, restart with one click.', icon_svg: null, enabled: true, icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z"/></svg>` },
    { color: '#F1C40F', title: 'Who Sell',     desc: 'Search the live vending market by item name or ID and find sellers in real time.', icon_svg: null, enabled: true, icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>` },
]

const featureCards = computed(() => {
    try {
        const raw = st.value.home_feature_cards
        if (!raw) return defaultFeatureCards
        const saved = JSON.parse(raw)
        return defaultFeatureCards.map((def, i) => {
            const s = saved[i] ?? {}
            return {
                ...def,
                title:     s.title     ?? def.title,
                desc:      s.desc      ?? def.desc,
                color:     s.color     ?? def.color,
                svg_code:  s.svg_code  ?? null,
                image:     s.image     ?? null,
                icon_type: s.icon_type ?? 'icon',
                enabled:   s.enabled   ?? true,
                icon:      s.svg_code  || def.icon,
            }
        }).filter(f => f.enabled)
    } catch {
        return defaultFeatureCards
    }
})

const initGridGlow = () => {
    const hero = document.querySelector('.ha-hero')
    const glow = document.querySelector('.ha-grid-glow')
    if (!hero || !glow) return
    hero.addEventListener('mousemove', e => {
        const r = hero.getBoundingClientRect()
        glow.style.setProperty('--mx', (e.clientX - r.left) + 'px')
        glow.style.setProperty('--my', (e.clientY - r.top)  + 'px')
    }, { passive: true })
    hero.addEventListener('mouseleave', () => {
        glow.style.setProperty('--mx', '-9999px')
        glow.style.setProperty('--my', '-9999px')
    })
}

onMounted(async () => {
    initCanvas()
    initGridGlow()
    const gsapOk = await loadGSAP()
    if (gsapOk && window.gsap) { initGSAP() } else { revealAll() }
    setTimeout(initTilt, 600)
    startCharAnimation(false)
    window.addEventListener('scroll', handleScroll, { passive: true })
})

onUnmounted(() => {
    if (rafId) cancelAnimationFrame(rafId)
    clearInterval(charTimer)
    window.removeEventListener('scroll', handleScroll)
    window.ScrollTrigger?.getAll?.().forEach(t => t.kill())
})
</script>

<template>
    <Head  :title="__('Home')" />

    <MainLayout>
        <div class="ha-root">

            <!-- ══════════ HERO ══════════ -->
            <section ref="heroRef" class="ha-hero relative flex items-center justify-center overflow-hidden">

                <!-- Fondo: video > imagen custom > SVG por defecto -->
                <video v-if="st.home_hero_bg_video"
                       autoplay muted loop playsinline aria-hidden="true"
                       class="absolute inset-0 w-full h-full object-cover pointer-events-none select-none">
                    <source :src="'/storage/' + st.home_hero_bg_video" type="video/mp4" />
                </video>
                <img v-else
                     :src="st.home_hero_bg_image ? '/storage/' + st.home_hero_bg_image : '/images/bg-main.svg'"
                     aria-hidden="true"
                     class="absolute inset-0 w-full h-full object-cover pointer-events-none select-none" />
                <!-- Overlay sobre la imagen -->
                <div class="absolute inset-0 pointer-events-none ha-hero-overlay" />

                <!-- Starfield canvas -->
                <canvas ref="canvasRef" class="absolute inset-0 w-full h-full" />

                <!-- Grid lines base -->
                <div class="ha-grid absolute inset-0 pointer-events-none" />
                <!-- Grid glow (sigue al cursor) -->
                <div class="ha-grid-glow absolute inset-0 pointer-events-none" />

                <!-- Central glow -->
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
                            w-[700px] h-[700px] rounded-full pointer-events-none ha-central-glow" />

                <!-- Personaje (fade-out con scroll, frame cycling) — configurable -->
                <div v-if="charEnabled"
                     :class="['ha-char-wrap absolute', charPosClass, charOnMobile ? 'block' : 'hidden sm:block']"
                     :style="{ opacity: charOpacity }"
                     @mouseenter="onCharEnter"
                     @mouseleave="onCharLeave">
                    <img :src="charSrc"
                         alt=""
                         class="ha-char-img w-auto select-none"
                         :style="charSizeStyle"
                         draggable="false" />
                </div>

                <!-- Content -->
                <div class="relative z-10 flex flex-col items-center text-center px-6 max-w-4xl mx-auto pt-8">

                    <!-- Title -->
                    <h1 class="ha-title font-display font-bold leading-none mb-5
                               text-5xl sm:text-7xl md:text-8xl">
                        <span class="block text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Welcome to') }}</span>
                        <span class="block ha-gradient-text">
                            {{ st.site_name || 'rApanel' }}
                        </span>
                    </h1>

                    <!-- Subtitle -->
                    <p class="ha-subtitle text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 text-lg md:text-xl max-w-2xl mb-10 leading-relaxed">
                        {{ st.home_hero_subtitle || __('A classic Ragnarok Online experience, reimagined for a new era.') }}
                    </p>

                    <!-- CTAs -->
                    <div class="ha-cta flex flex-wrap justify-center gap-4">
                        <Link :href="safeRoute('register')" class="ha-btn-primary font-display text-base md:text-lg font-bold uppercase tracking-widest px-9 py-4 rounded-xl">
                            {{ __('Register Now') }}
                        </Link>
                        <a v-if="st.home_learn_more_url" :href="st.home_learn_more_url"
                           class="ha-btn-ghost font-display text-base md:text-lg font-bold uppercase tracking-widest px-9 py-4 rounded-xl">
                            {{ __('Learn More') }}
                        </a>
                        <button v-else @click="$el.closest('section').nextElementSibling?.scrollIntoView({ behavior: 'smooth' })"
                                class="ha-btn-ghost font-display text-base md:text-lg font-bold uppercase tracking-widest px-9 py-4 rounded-xl">
                            {{ __('Learn More') }}
                        </button>
                    </div>

                    <!-- Scroll arrow -->
                    <div class="mt-16 flex flex-col items-center gap-1 ha-bounce">
                        <span class="text-xs text-rapanel-text-light/45 dark:text-rapanel-text-dark/45 tracking-[0.25em] uppercase">{{ __('Scroll') }}</span>
                        <svg class="w-5 h-5 text-rapanel-text-light/45 dark:text-rapanel-text-dark/45" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
            </section>

            <!-- ══════════ STATS BAR ══════════ -->
            <section v-if="st.home_show_stats !== '0'" ref="statsRef" class="ha-stats-bar border-y border-black/5 dark:border-white/5 py-10">
                <div class="max-w-6xl mx-auto px-6">
                    <div class="flex flex-wrap justify-center gap-x-10 gap-y-6">

                        <template v-for="s in [
                            { key: 'login', label: 'Login Server' },
                            { key: 'char',  label: 'Char Server' },
                            { key: 'map',   label: 'Map Server' },
                            { key: 'web',   label: 'Web Server' },
                        ]" :key="s.key">
                            <div class="stat-chip opacity-0 text-center min-w-[110px]">
                                <div class="font-display text-3xl font-bold"
                                     :class="srv[s.key] ? 'text-[#2ECC71]' : 'text-red-500'">
                                    {{ srv[s.key] ? __('ONLINE') : __('OFFLINE') }}
                                </div>
                                <div class="text-xs text-rapanel-text-light/55 dark:text-rapanel-text-dark/55 uppercase tracking-widest mt-1">{{ __(s.label) }}</div>
                            </div>
                        </template>

                        <!-- roBrowser: solo si está configurado -->
                        <div v-if="page.props.roBrowserUrl"
                             class="stat-chip opacity-0 text-center min-w-[110px]">
                            <div class="font-display text-3xl font-bold"
                                 :class="srv.ws ? 'text-[#2ECC71]' : 'text-red-500'">
                                {{ srv.ws ? __('ONLINE') : __('OFFLINE') }}
                            </div>
                            <div class="text-xs text-rapanel-text-light/55 dark:text-rapanel-text-dark/55 uppercase tracking-widest mt-1">{{ __('roBrowser Server') }}</div>
                        </div>

                        <!-- Peak Players (al final) -->
                        <div class="stat-chip opacity-0 text-center min-w-[90px]">
                            <div class="font-display text-3xl font-bold text-rapanel-danger">
                                <span :data-count="srv.peak ?? 0">{{ srv.peak ?? 0 }}</span>
                            </div>
                            <div class="text-xs text-rapanel-text-light/55 dark:text-rapanel-text-dark/55 uppercase tracking-widest mt-1">{{ __('Peak Players') }}</div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- ══════════ SERVER INFO ══════════ -->
            <section v-if="st.home_show_info !== '0'" ref="infoRef" class="py-24 px-6 ha-info-section relative overflow-hidden">
                <img :src="st.home_info_bg_image ? '/storage/' + st.home_info_bg_image : '/images/bg-main-rapanel.svg'"
                     aria-hidden="true"
                     class="absolute inset-0 w-full h-full object-cover scale-105 blur-md pointer-events-none select-none" />
                <div class="absolute inset-0 ha-hero-overlay pointer-events-none" />
                <div class="relative z-10 max-w-6xl mx-auto">

                    <div class="text-center mb-16">
                        <p class="text-[#F1C40F] text-xs uppercase tracking-[0.3em] font-semibold mb-4">{{ __('The World Awaits') }}</p>
                        <h2 class="font-display text-4xl md:text-6xl font-bold text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Server Information') }}</h2>
                    </div>

                    <div class="flex flex-wrap gap-4">

                        <!-- EXP Rate -->
                        <div v-if="infoBlocks[0]?.show !== false" class="info-block feat-card ha-glass rounded-2xl p-4 flex flex-col items-center text-center gap-3 opacity-0 flex-1 min-w-[140px]" :style="`--c:${infoColors[0]}`">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" :style="`background:${infoColors[0]}20`">
                                <img v-if="infoBlocks[0]?.image" :src="'/storage/' + infoBlocks[0].image" class="w-9 h-9 object-contain" />
                                <span v-else-if="infoBlocks[0]?.svg_code" class="ha-svg-icon w-5 h-5" :style="`color:${infoColors[0]}`" v-html="infoBlocks[0].svg_code" />
                                <svg v-else class="w-5 h-5" :style="`color:${infoColors[0]}`" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-display text-xl font-bold text-rapanel-text-light dark:text-rapanel-text-dark">
                                    <span :data-count-info="st.home_base_rate || 100">{{ st.home_base_rate || 100 }}</span>x /
                                    <span :data-count-info="st.home_job_rate || 100">{{ st.home_job_rate || 100 }}</span>x /
                                    <span :data-count-info="st.home_drop_rate || 100">{{ st.home_drop_rate || 100 }}</span>x
                                </div>
                                <div class="text-rapanel-text-light/55 dark:text-rapanel-text-dark/55 text-xs uppercase tracking-widest mt-1">{{ __('Base / Job / Drop') }}</div>
                            </div>
                        </div>

                        <!-- Max Level -->
                        <div v-if="infoBlocks[1]?.show !== false" class="info-block feat-card ha-glass rounded-2xl p-4 flex flex-col items-center text-center gap-3 opacity-0 flex-1 min-w-[140px]" :style="`--c:${infoColors[1]}`">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" :style="`background:${infoColors[1]}20`">
                                <img v-if="infoBlocks[1]?.image" :src="'/storage/' + infoBlocks[1].image" class="w-9 h-9 object-contain" />
                                <span v-else-if="infoBlocks[1]?.svg_code" class="ha-svg-icon w-5 h-5" :style="`color:${infoColors[1]}`" v-html="infoBlocks[1].svg_code" />
                                <svg v-else class="w-5 h-5" :style="`color:${infoColors[1]}`" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-display text-xl font-bold text-rapanel-text-light dark:text-rapanel-text-dark">
                                    <span :data-count-info="st.home_max_base_level || 99">{{ st.home_max_base_level || 99 }}</span> /
                                    <span :data-count-info="st.home_max_job_level || 70">{{ st.home_max_job_level || 70 }}</span>
                                </div>
                                <div class="text-rapanel-text-light/55 dark:text-rapanel-text-dark/55 text-xs uppercase tracking-widest mt-1">{{ __('Max Base / Job') }}</div>
                            </div>
                        </div>

                        <!-- Game Mode -->
                        <div v-if="infoBlocks[2]?.show !== false" class="info-block feat-card ha-glass rounded-2xl p-4 flex flex-col items-center text-center gap-3 opacity-0 flex-1 min-w-[140px]" :style="`--c:${infoColors[2]}`">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" :style="`background:${infoColors[2]}20`">
                                <img v-if="infoBlocks[2]?.image" :src="'/storage/' + infoBlocks[2].image" class="w-9 h-9 object-contain" />
                                <span v-else-if="infoBlocks[2]?.svg_code" class="ha-svg-icon w-5 h-5" :style="`color:${infoColors[2]}`" v-html="infoBlocks[2].svg_code" />
                                <svg v-else class="w-5 h-5" :style="`color:${infoColors[2]}`" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-display text-xl font-bold text-rapanel-text-light dark:text-rapanel-text-dark capitalize">{{ st.home_game_mode || 'Pre-Renewal' }}</div>
                                <div class="text-rapanel-text-light/55 dark:text-rapanel-text-dark/55 text-xs uppercase tracking-widest mt-1">{{ __('Game Mode') }}</div>
                            </div>
                        </div>

                        <!-- Episode -->
                        <div v-if="infoBlocks[3]?.show !== false" class="info-block feat-card ha-glass rounded-2xl p-4 flex flex-col items-center text-center gap-3 opacity-0 flex-1 min-w-[140px]" :style="`--c:${infoColors[3]}`">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" :style="`background:${infoColors[3]}20`">
                                <img v-if="infoBlocks[3]?.image" :src="'/storage/' + infoBlocks[3].image" class="w-9 h-9 object-contain" />
                                <span v-else-if="infoBlocks[3]?.svg_code" class="ha-svg-icon w-5 h-5" :style="`color:${infoColors[3]}`" v-html="infoBlocks[3].svg_code" />
                                <svg v-else class="w-5 h-5" :style="`color:${infoColors[3]}`" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-display text-xl font-bold text-rapanel-text-light dark:text-rapanel-text-dark">{{ st.home_episode || '13.3+ — El Dicastes' }}</div>
                                <div class="text-rapanel-text-light/55 dark:text-rapanel-text-dark/55 text-xs uppercase tracking-widest mt-1">{{ __('Episode') }}</div>
                            </div>
                        </div>

                        <!-- International -->
                        <div v-if="infoBlocks[4]?.show !== false" class="info-block feat-card ha-glass rounded-2xl p-4 flex flex-col items-center text-center gap-3 opacity-0 flex-1 min-w-[140px]" :style="`--c:${infoColors[4]}`">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" :style="`background:${infoColors[4]}20`">
                                <img v-if="infoBlocks[4]?.image" :src="'/storage/' + infoBlocks[4].image" class="w-9 h-9 object-contain" />
                                <span v-else-if="infoBlocks[4]?.svg_code" class="ha-svg-icon w-5 h-5" :style="`color:${infoColors[4]}`" v-html="infoBlocks[4].svg_code" />
                                <svg v-else class="w-5 h-5" :style="`color:${infoColors[4]}`" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-display text-xl font-bold text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('International') }}</div>
                                <div class="text-rapanel-text-light/55 dark:text-rapanel-text-dark/55 text-xs uppercase tracking-widest mt-1">{{ st.home_intl_text || 'EN · ES · PT · FR' }}</div>
                            </div>
                        </div>

                    </div>

                    <!-- Tarjetas highlight personalizables -->
                    <div class="flex flex-wrap gap-5 mt-12">
                        <div v-for="(card, i) in highlightCards" :key="i" class="ha-hcard flex-1 min-w-[220px]">
                            <!-- Imagen -->
                            <div class="ha-hcard-img">
                                <img v-if="card.image" :src="card.image.startsWith('http') || card.image.startsWith('/') ? card.image : '/storage/' + card.image" :alt="card.title"
                                     class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full ha-hcard-placeholder"
                                     :style="`--ci: ${i}`"></div>
                            </div>
                            <!-- Contenido -->
                            <div class="ha-hcard-body">
                                <h3 class="ha-hcard-title">{{ card.title }}</h3>
                                <p class="ha-hcard-desc">{{ card.desc }}</p>
                                <a :href="card.url" class="ha-hcard-btn">{{ __('More Info') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ══════════ NEWS MARQUEE ══════════ -->
            <section v-if="news.length && st.home_show_news !== '0'" class="py-16 border-y border-black/5 dark:border-white/5 overflow-hidden">
                <div class="text-center mb-10">
                    <h2 class="font-display text-3xl font-bold text-rapanel-text-light dark:text-rapanel-text-dark">{{ __('Latest News') }}</h2>
                </div>
                <div class="ha-marquee-wrap">
                    <div class="ha-marquee-track">
                        <template v-for="n in 2" :key="n">
                            <Link v-for="item in news" :key="`${n}-${item.id}`"
                                  :href="safeRoute('news.show', item.slug)"
                                  class="ha-news-card ha-glass rounded-2xl p-5 flex-shrink-0 w-72 mx-3 block group"
                                  :style="`--nc: ${item.type_color}`">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold uppercase tracking-wide"
                                          :style="`background: ${item.type_color}20; color: ${item.type_color}`">
                                        {{ __(item.type_label) }}
                                    </span>
                                    <span class="text-xs text-rapanel-text-light/55 dark:text-rapanel-text-dark/55">{{ item.created_at }}</span>
                                </div>
                                <h4 class="ha-news-title font-display text-lg font-bold text-rapanel-text-light dark:text-rapanel-text-dark leading-snug line-clamp-2 transition-colors">
                                    {{ item.title }}
                                </h4>
                                <p class="text-rapanel-text-light/70 dark:text-rapanel-text-dark/55 text-xs mt-2 line-clamp-2">{{ item.excerpt }}</p>
                            </Link>
                        </template>
                    </div>
                </div>
            </section>

            <!-- ══════════ FEATURES ══════════ -->
            <section v-if="st.home_show_features !== '0'" ref="featuresRef" class="py-24 md:py-32 px-6">
                <div class="max-w-6xl mx-auto">

                    <div class="text-center mb-16">
                        <p class="text-[#4A90E2] text-xs uppercase tracking-[0.3em] font-semibold mb-4">{{ st.home_features_subtitle || __('Everything in one place') }}</p>
                        <h2 class="font-display text-4xl md:text-6xl font-bold text-rapanel-text-light dark:text-rapanel-text-dark">{{ st.home_features_title || __('Panel Features') }}</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div v-for="(f, i) in featureCards" :key="i"
                             class="feat-card ha-glass rounded-2xl p-6 opacity-0 relative overflow-hidden cursor-default"
                             :style="`--c: ${cardPalette[i % cardPalette.length]}`">

                            <!-- Icon -->
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-5"
                                 :style="`background: ${cardPalette[i % cardPalette.length]}20`">
                                <img v-if="f.image" :src="'/storage/' + f.image" class="w-8 h-8 object-contain" />
                                <span v-else class="ha-svg-icon w-5 h-5" :style="`color: ${cardPalette[i % cardPalette.length]}`" v-html="f.icon" />
                            </div>

                            <h3 class="font-display text-xl font-bold text-rapanel-text-light dark:text-rapanel-text-dark mb-2">{{ f.title }}</h3>
                            <p class="text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 text-sm leading-relaxed">{{ f.desc }}</p>

                            <!-- Top-edge glow line -->
                            <div class="absolute top-0 left-6 right-6 h-px"
                                 :style="`background: linear-gradient(90deg, transparent, ${cardPalette[i % cardPalette.length]}80, transparent)`" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- ══════════ CTA ══════════ -->
            <section v-if="st.home_show_cta !== '0'" ref="ctaRef" class="py-32 px-6 relative overflow-hidden">
                <div class="ha-mesh-bg absolute inset-0 pointer-events-none" />

                <div class="cta-inner opacity-0 relative z-10 text-center max-w-3xl mx-auto">

                    <h2 class="font-display font-bold leading-none mb-6
                               text-5xl sm:text-6xl md:text-8xl">
                        <span class="block text-rapanel-text-light dark:text-rapanel-text-dark">{{ st.home_cta_line1 || __('Start Your') }}</span>
                        <span class="block ha-gradient-text">{{ st.home_cta_line2 || __('Adventure') }}</span>
                    </h2>

                    <p class="text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 text-lg mb-10 max-w-xl mx-auto">
                        {{ st.home_cta_subtitle || __('Join our community and experience Ragnarok Online the way it was meant to be played.') }}
                    </p>

                    <div class="relative inline-block">
                        <Link ref="ctaBtnRef" :href="st.home_cta_btn_url || safeRoute('register')"
                              @click="doBurst"
                              class="ha-btn-primary font-display text-xl font-bold uppercase tracking-widest px-14 py-5 rounded-2xl inline-block">
                            {{ st.home_cta_btn_text || __('Register Free') }}
                        </Link>
                        <!-- burst particles -->
                        <span v-for="p in burstParticles" :key="p.id"
                              class="ha-burst absolute w-2 h-2 rounded-full pointer-events-none"
                              :style="`left:${p.x}px; top:${p.y}px; background:${p.col}; --tx:${p.tx - p.x}px; --ty:${p.ty - p.y}px`" />
                    </div>
                </div>
            </section>

        </div>
    </MainLayout>
</template>

<style scoped>
/* ── Root ───────────────────────────────────────────────────── */
.ha-root {
    background: #f8fafc;
    color: #1d283a;
    font-family: 'Figtree', sans-serif;
}
.dark .ha-root {
    background: #0f172a;
    color: #E2E8F0;
}
.font-display { font-family: 'Rajdhani', sans-serif; }


/* ── Hero overlay ───────────────────────────────────────────── */
.ha-hero-overlay {
    background: linear-gradient(135deg, rgba(239,246,255,0.40) 0%, rgba(238,242,255,0.35) 50%, rgba(245,243,255,0.32) 100%);
}
.dark .ha-hero-overlay {
    background: rgba(15, 23, 42, 0.8);
}

/* ── Central glow ───────────────────────────────────────────── */
.ha-central-glow {
    background: radial-gradient(circle, rgb(var(--ha-accent-rgb,74 144 226) / 0.18) 0%, transparent 70%);
}
.dark .ha-central-glow {
    background: radial-gradient(circle, rgb(var(--ha-accent-rgb,74 144 226) / 0.12) 0%, transparent 70%);
}

/* ── Info section ───────────────────────────────────────────── */
.ha-info-section {
    background: #e8eef6;
}
.dark .ha-info-section {
    background: #080e1a;
}

/* ── Gradient text ──────────────────────────────────────────── */
.ha-gradient-text {
    background: linear-gradient(120deg, var(--ha-grad-from,#4A90E2) 0%, var(--ha-grad-mid,#F1C40F) 45%, var(--ha-grad-to,#a855f7) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ── Hero: entrada por CSS pura (bulletproof — no depende de GSAP/CDN) ── */
@keyframes ha-rise {
    from { opacity: 0; transform: translateY(40px); }
    to   { opacity: 1; transform: translateY(0); }
}
.ha-title    { animation: ha-rise 0.9s cubic-bezier(0.22, 1, 0.36, 1) 0.10s both; }
.ha-subtitle { animation: ha-rise 0.7s cubic-bezier(0.22, 1, 0.36, 1) 0.45s both; }
.ha-cta      { animation: ha-rise 0.7s cubic-bezier(0.22, 1, 0.36, 1) 0.65s both; }
@media (prefers-reduced-motion: reduce) {
    .ha-title, .ha-subtitle, .ha-cta { animation: none; opacity: 1; transform: none; }
}

/* ── Grid glow ──────────────────────────────────────────────── */
.ha-grid-glow {
    background-image:
        linear-gradient(rgb(var(--ha-accent-rgb,74 144 226) / 0.6) 1px, transparent 1px),
        linear-gradient(90deg, rgb(var(--ha-accent-rgb,74 144 226) / 0.6) 1px, transparent 1px);
    background-size: 64px 64px;
    -webkit-mask-image: radial-gradient(circle 96px at var(--mx, -9999px) var(--my, -9999px), black 0%, transparent 100%);
    mask-image: radial-gradient(circle 96px at var(--mx, -9999px) var(--my, -9999px), black 0%, transparent 100%);
}
.dark .ha-grid-glow {
    background-image:
        linear-gradient(rgba(241,196,15,0.5) 1px, transparent 1px),
        linear-gradient(90deg, rgba(241,196,15,0.5) 1px, transparent 1px);
}

/* ── Hero ───────────────────────────────────────────────────── */
.ha-hero {
    min-height: calc(100vh - 8.5rem);
    @media (max-width: 768px) { min-height: calc(100vh - 5rem); }
}
.ha-grid {
    background-image:
        linear-gradient(rgb(var(--ha-accent-rgb,74 144 226) / 0.07) 1px, transparent 1px),
        linear-gradient(90deg, rgb(var(--ha-accent-rgb,74 144 226) / 0.07) 1px, transparent 1px);
    background-size: 64px 64px;
}
.dark .ha-grid {
    background-image:
        linear-gradient(rgb(var(--ha-accent-rgb,74 144 226) / 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgb(var(--ha-accent-rgb,74 144 226) / 0.04) 1px, transparent 1px);
}

/* ── Float ──────────────────────────────────────────────────── */
@keyframes ha-float {
    0%, 100% { transform: translateY(0)   rotate(-0.8deg) scale(1);    }
    50%       { transform: translateY(-18px) rotate(0.8deg) scale(1.02); }
}
.ha-float { animation: ha-float 7s ease-in-out infinite; }

/* ── Bounce ─────────────────────────────────────────────────── */
@keyframes ha-bounce {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(6px); }
}
.ha-bounce { animation: ha-bounce 1.8s ease-in-out infinite; }

/* ── Glass card ─────────────────────────────────────────────── */
.ha-glass {
    background: rgba(255,255,255,0.95);
    border: 1px solid rgba(15,23,42,0.08);
    backdrop-filter: blur(14px);
    transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.1s ease;
}
.dark .ha-glass {
    background: #0f172a;
    border-color: rgba(255,255,255,0.07);
}
.ha-glass:hover {
    border-color: rgb(var(--ha-accent-rgb,74 144 226) / 0.3);
    box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 0 40px rgb(var(--ha-accent-rgb,74 144 226) / 0.08);
}
.dark .ha-glass:hover {
    box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 40px rgb(var(--ha-accent-rgb,74 144 226) / 0.08);
}

/* ── Feature card ───────────────────────────────────────────── */
.feat-card:hover {
    border-color: var(--c, #4A90E2) !important;
    box-shadow: 0 20px 30px rgba(0,0,0,0.12), 0 0 30px color-mix(in srgb, var(--c) 20%, transparent);
}
.dark .feat-card:hover {
    box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 30px color-mix(in srgb, var(--c) 20%, transparent);
}

/* ── Stats bar ──────────────────────────────────────────────── */
.ha-stats-bar { background: rgba(0,0,0,0.03); }
.dark .ha-stats-bar { background: rgba(255,255,255,0.02); }


/* ── Buttons ────────────────────────────────────────────────── */
.ha-btn-primary {
    background: linear-gradient(135deg, rgb(var(--ha-accent-rgb,74 144 226)) 0%, #2563eb 100%);
    color: #fff;
    box-shadow: 0 0 32px rgb(var(--ha-accent-rgb,74 144 226) / 0.45);
    transition: box-shadow 0.3s ease, transform 0.2s ease;
}
.ha-btn-primary:hover {
    box-shadow: 0 0 60px rgb(var(--ha-accent-rgb,74 144 226) / 0.7);
    transform: translateY(-3px);
}
.ha-btn-ghost {
    background: rgba(15,23,42,0.06);
    color: #1d283a;
    border: 1px solid rgba(15,23,42,0.14);
    transition: background 0.3s ease, border-color 0.3s ease, transform 0.2s ease;
}
.dark .ha-btn-ghost {
    background: rgba(255,255,255,0.06);
    color: #fff;
    border-color: rgba(255,255,255,0.14);
}
.ha-btn-ghost:hover {
    background: rgba(15,23,42,0.11);
    border-color: rgba(15,23,42,0.28);
    transform: translateY(-3px);
}
.dark .ha-btn-ghost:hover {
    background: rgba(255,255,255,0.11);
    border-color: rgba(255,255,255,0.28);
}

/* ── Marquee ────────────────────────────────────────────────── */
.ha-marquee-wrap { overflow: hidden; }
.ha-marquee-track {
    display: flex;
    animation: ha-marquee 35s linear infinite;
    width: max-content;
}
.ha-marquee-wrap:hover .ha-marquee-track { animation-play-state: paused; }
@keyframes ha-marquee {
    from { transform: translateX(0); }
    to   { transform: translateX(-50%); }
}
.ha-news-card { transition: border-color 0.3s ease; }
.ha-news-card:hover .ha-news-title { color: var(--nc, #4A90E2); }

/* ── Mesh CTA bg ────────────────────────────────────────────── */
.ha-mesh-bg {
    background:
        radial-gradient(ellipse 70% 70% at 20% 60%, rgb(var(--ha-accent-rgb,74 144 226) / 0.18) 0%, transparent 65%),
        radial-gradient(ellipse 55% 55% at 80% 40%, rgba(168,85,247,0.14) 0%, transparent 65%),
        radial-gradient(ellipse 40% 40% at 50% 90%, rgba(241,196,15,0.08) 0%, transparent 55%);
    animation: ha-mesh-shift 12s ease-in-out infinite alternate;
}
@keyframes ha-mesh-shift {
    from { opacity: 0.8; }
    to   { opacity: 1; }
}

/* ── Burst particle ─────────────────────────────────────────── */
@keyframes ha-burst {
    0%   { transform: translate(0, 0) scale(1);   opacity: 1; }
    100% { transform: translate(var(--tx), var(--ty)) scale(0); opacity: 0; }
}
.ha-burst { animation: ha-burst 0.85s ease-out forwards; }

/* ── Info block icon size ───────────────────────────────────── */
.w-13 { width: 3.25rem; }
.h-13 { height: 3.25rem; }

/* ── Highlight cards ────────────────────────────────────────── */
.ha-hcard {
    background: rgba(255,255,255,0.9);
    border: 1px solid rgba(15,23,42,0.1);
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
}
.dark .ha-hcard {
    background: #0f172a;
    border-color: rgba(255,255,255,0.08);
}
.ha-hcard:hover {
    transform: translateY(-5px);
    border-color: rgb(var(--ha-accent-rgb,74 144 226) / 0.3);
    box-shadow: 0 20px 30px rgba(0,0,0,0.12), 0 0 30px rgb(var(--ha-accent-rgb,74 144 226) / 0.08);
}
.dark .ha-hcard:hover {
    box-shadow: 0 20px 50px rgba(0,0,0,0.5), 0 0 30px rgb(var(--ha-accent-rgb,74 144 226) / 0.08);
}

/* Imagen */
.ha-hcard-img {
    width: 100%;
    height: 180px;
    overflow: hidden;
    flex-shrink: 0;
}
.ha-hcard-img img { transition: transform 0.4s ease; }
.ha-hcard:hover .ha-hcard-img img { transform: scale(1.05); }

/* Placeholder cuando no hay imagen */
.ha-hcard-placeholder {
    background:
        radial-gradient(ellipse at 30% 40%,
            rgb(var(--ha-accent-rgb,74 144 226) / calc(0.15 - var(--ci) * 0.02)) 0%,
            transparent 65%),
        radial-gradient(ellipse at 70% 60%,
            rgba(168,85,247,calc(0.1 - var(--ci) * 0.01)) 0%,
            transparent 65%),
        linear-gradient(135deg, #dbeafe 0%, #ede9fe 100%);
}
.dark .ha-hcard-placeholder {
    background:
        radial-gradient(ellipse at 30% 40%,
            rgb(var(--ha-accent-rgb,74 144 226) / calc(0.25 - var(--ci) * 0.03)) 0%,
            transparent 65%),
        radial-gradient(ellipse at 70% 60%,
            rgba(168,85,247,calc(0.2 - var(--ci) * 0.02)) 0%,
            transparent 65%),
        linear-gradient(135deg, #0d1527 0%, #0f1e3a 100%);
}

/* Contenido */
.ha-hcard-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    flex: 1;
    gap: 10px;
}
.ha-hcard-title {
    font-family: 'Rajdhani', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: #1d283a;
    line-height: 1.2;
}
.dark .ha-hcard-title { color: #fff; }
.ha-hcard-desc {
    font-size: 0.82rem;
    color: rgba(15,23,42,0.55);
    line-height: 1.6;
    flex: 1;
}
.dark .ha-hcard-desc { color: rgba(255,255,255,0.5); }
.ha-hcard-btn {
    display: inline-block;
    margin-top: 4px;
    padding: 8px 24px;
    border-radius: 8px;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 0.9rem;
    letter-spacing: 0.05em;
    color: #fff;
    background: linear-gradient(135deg, rgb(var(--ha-accent-rgb,74 144 226)) 0%, #2563eb 100%);
    box-shadow: 0 0 18px rgb(var(--ha-accent-rgb,74 144 226) / 0.35);
    transition: box-shadow 0.25s ease, transform 0.2s ease;
    text-decoration: none;
}
.ha-hcard-btn:hover {
    box-shadow: 0 0 32px rgb(var(--ha-accent-rgb,74 144 226) / 0.6);
    transform: translateY(-2px);
}

/* ── Char shadow (light mode: sin sombra negra tan dura) ────── */
.ha-char-img {
    filter: drop-shadow(-6px 0 30px rgb(var(--ha-accent-rgb,74 144 226) / 0.35))
            drop-shadow(0 0 50px rgb(var(--ha-accent-rgb,74 144 226) / 0.18))
            drop-shadow(0 20px 25px rgba(0,0,0,0.18));
    transition: transform 0.45s cubic-bezier(0.34, 1.56, 0.64, 1), filter 0.4s ease;
}
.dark .ha-char-img {
    filter: drop-shadow(-6px 0 30px rgba(241,196,15,0.45))
            drop-shadow(0 0 50px rgb(var(--ha-accent-rgb,74 144 226) / 0.25))
            drop-shadow(0 24px 30px rgba(0,0,0,0.55));
}

/* ── Personaje hero ─────────────────────────────────────────── */
.ha-char-wrap {
    transition: opacity 0.15s linear;
    cursor: pointer;
}
.ha-char-wrap::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0; right: 0;
    height: 48px;
    background: radial-gradient(ellipse 55% 100% at 50% 100%, rgb(var(--ha-accent-rgb,74 144 226) / 0.55) 0%, transparent 100%);
    opacity: 0;
    transition: opacity 0.4s ease;
    pointer-events: none;
}
.ha-char-wrap:hover::before { opacity: 1; }

.ha-char-img {
    height: 72vh;
    animation: ha-char-enter 1s cubic-bezier(0.22, 1, 0.36, 1) 0.3s both;
}
.ha-char-wrap:hover .ha-char-img {
    transform: scale(1.05) translateY(-10px);
    filter: drop-shadow(-6px 0 55px rgba(241,196,15,0.75))
            drop-shadow(0 0 90px rgb(var(--ha-accent-rgb,74 144 226) / 0.55))
            drop-shadow(0 30px 40px rgba(0,0,0,0.6));
}
.dark .ha-char-wrap:hover .ha-char-img {
    filter: drop-shadow(-6px 0 55px rgba(241,196,15,0.75))
            drop-shadow(0 0 90px rgb(var(--ha-accent-rgb,74 144 226) / 0.55))
            drop-shadow(0 30px 40px rgba(0,0,0,0.6));
}
@keyframes ha-char-enter {
    from { transform: translateX(70px) translateY(16px); }
    to   { transform: translateX(0)    translateY(0); }
}

/* ── SVG pegado desde heroicons/lucide ──────────────────────── */
.ha-svg-icon {
    display: flex;
    align-items: center;
    justify-content: center;
}
.ha-svg-icon :deep(svg) {
    width: 100%;
    height: 100%;
    display: block;
    flex-shrink: 0;
}
</style>
