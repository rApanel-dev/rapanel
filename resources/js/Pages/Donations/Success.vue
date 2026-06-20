<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { CheckCircleIcon, SparklesIcon, HomeIcon, GiftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    cashpoints: { type: Number, default: null },
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

// ── Countdown ────────────────────────────────────────────────────────────────
const countdown = ref(6);
let timer = null;

// ── Fireworks ────────────────────────────────────────────────────────────────
const canvasRef = ref(null);
let raf = null;
let fwStop = false;

const COLORS = ['#F59E0B','#3B82F6','#10B981','#A78BFA','#EC4899','#F97316','#FFFFFF','#FCD34D'];

class Particle {
    constructor(x, y, color, vx, vy, size, decay, isTrail = false) {
        this.x = x; this.y = y; this.color = color;
        this.vx = vx; this.vy = vy; this.size = size;
        this.alpha = 1; this.decay = decay;
        this.isTrail = isTrail;
    }
    update() {
        this.x  += this.vx;
        this.y  += this.vy;
        this.vy += this.isTrail ? 0.06 : 0.12;
        this.vx *= 0.98;
        this.alpha -= this.decay;
    }
    draw(ctx) {
        ctx.save();
        ctx.globalAlpha = Math.max(0, this.alpha);
        ctx.fillStyle = this.color;
        if (this.isTrail) {
            ctx.shadowBlur = 6;
            ctx.shadowColor = this.color;
        }
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
    }
}

class Rocket {
    constructor(canvas) {
        this.x  = canvas.width * (0.1 + Math.random() * 0.8);
        this.y  = canvas.height;
        this.vy = -(canvas.height * 0.012 + Math.random() * canvas.height * 0.006);
        this.vx = (Math.random() - 0.5) * 1.5;
        this.color  = COLORS[Math.floor(Math.random() * COLORS.length)];
        this.trail  = [];
        this.done   = false;
        this.peaked = false;
        this.prevVy = this.vy;
    }
    update(particles) {
        if (this.done) return;
        this.trail.push({ x: this.x, y: this.y });
        if (this.trail.length > 8) this.trail.shift();

        this.x  += this.vx;
        this.y  += this.vy;
        this.vy += 0.18;

        if (this.vy >= 0 && !this.peaked) {
            this.peaked = true;
            this.explode(particles);
            this.done = true;
        }
    }
    explode(particles) {
        const count = 80 + Math.floor(Math.random() * 40);
        const color2 = COLORS[Math.floor(Math.random() * COLORS.length)];
        for (let i = 0; i < count; i++) {
            const angle = (Math.PI * 2 * i) / count + (Math.random() - 0.5) * 0.3;
            const speed = 1.5 + Math.random() * 4;
            const dual  = Math.random() > 0.5 ? color2 : this.color;
            particles.push(new Particle(
                this.x, this.y, dual,
                Math.cos(angle) * speed,
                Math.sin(angle) * speed,
                Math.random() * 2.5 + 0.5,
                0.012 + Math.random() * 0.01,
            ));
        }
        // Sparkle ring
        for (let i = 0; i < 20; i++) {
            const angle = Math.random() * Math.PI * 2;
            particles.push(new Particle(
                this.x, this.y, '#FFFFFF',
                Math.cos(angle) * (6 + Math.random() * 3),
                Math.sin(angle) * (6 + Math.random() * 3),
                1, 0.025,
            ));
        }
    }
    draw(ctx) {
        if (this.done) return;
        // Trail
        for (let i = 0; i < this.trail.length; i++) {
            const a = (i / this.trail.length) * 0.6;
            ctx.save();
            ctx.globalAlpha = a;
            ctx.fillStyle = this.color;
            ctx.shadowBlur = 8;
            ctx.shadowColor = this.color;
            ctx.beginPath();
            ctx.arc(this.trail[i].x, this.trail[i].y, 2, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }
        // Head
        ctx.save();
        ctx.globalAlpha = 1;
        ctx.fillStyle = '#fff';
        ctx.shadowBlur = 10;
        ctx.shadowColor = this.color;
        ctx.beginPath();
        ctx.arc(this.x, this.y, 2.5, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
    }
}

function startFireworks(canvas) {
    const ctx  = canvas.getContext('2d');
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;

    const particles = [];
    const rockets   = [];
    let   lastLaunch = 0;
    let   elapsed    = 0;
    let   prev       = performance.now();

    function launch() {
        rockets.push(new Rocket(canvas));
        if (Math.random() > 0.5) rockets.push(new Rocket(canvas));
    }

    function loop(now) {
        if (fwStop) { ctx.clearRect(0, 0, canvas.width, canvas.height); return; }
        raf = requestAnimationFrame(loop);

        const dt = now - prev;
        prev      = now;
        elapsed  += dt;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Launch rockets every ~700ms for 4s, then taper off
        if (elapsed < 4000 && now - lastLaunch > 650 + Math.random() * 200) {
            launch();
            lastLaunch = now;
        }

        rockets.forEach(r => { r.update(particles); r.draw(ctx); });

        for (let i = particles.length - 1; i >= 0; i--) {
            const p = particles[i];
            p.update();
            p.draw(ctx);
            if (p.alpha <= 0) particles.splice(i, 1);
        }

        if (elapsed > 5500 && particles.length === 0 && rockets.every(r => r.done)) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            cancelAnimationFrame(raf);
        }
    }

    launch();
    raf = requestAnimationFrame(loop);
}

onMounted(() => {
    timer = setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            clearInterval(timer);
            router.visit(route('donations'), {
                onError: () => { window.location.href = route('donations'); },
            });
        }
    }, 1000);

    if (canvasRef.value) startFireworks(canvasRef.value);
});

onUnmounted(() => {
    clearInterval(timer);
    fwStop = true;
    cancelAnimationFrame(raf);
});
</script>

<template>
    <Head :title="__('Payment successful!')" />
    <MainLayout :show-bg="true">

        <!-- Fireworks canvas -->
        <canvas ref="canvasRef"
            class="fixed inset-0 w-full h-full pointer-events-none z-10" />

        <div class="relative z-20 min-h-[80vh] flex items-center justify-center px-4 py-10">
            <div class="w-full max-w-md">

                <!-- Card -->
                <div class="relative overflow-hidden rounded-3xl bg-rapanel-navy-900/80 backdrop-blur-2xl border border-white/10 shadow-2xl shadow-black/60 text-center">

                    <!-- Top glow line -->
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-rapanel-success/70 to-transparent" />
                    <!-- Success ambient glow -->
                    <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/2 w-64 h-20 bg-rapanel-success/15 blur-3xl rounded-full pointer-events-none" />

                    <div class="relative px-8 pt-10 pb-8 space-y-6">

                        <!-- Icon -->
                        <div class="flex justify-center">
                            <div class="relative">
                                <div class="absolute inset-0 rounded-full bg-rapanel-success/20 animate-ping" style="animation-duration:2s" />
                                <div class="relative w-20 h-20 rounded-full bg-gradient-to-br from-rapanel-success/30 to-rapanel-success/5 border border-rapanel-success/30 flex items-center justify-center shadow-xl shadow-rapanel-success/20">
                                    <CheckCircleIcon class="w-10 h-10 text-rapanel-success" />
                                </div>
                            </div>
                        </div>

                        <!-- Title -->
                        <div>
                            <h1 class="text-2xl font-display font-bold text-white mb-1">
                                {{ __('Payment successful!') }}
                            </h1>
                            <p class="text-white/50 text-sm">
                                {{ __('Thank you for your donation!') }}
                            </p>
                        </div>

                        <!-- DP Badge -->
                        <div v-if="cashpoints"
                            class="mx-auto w-fit px-6 py-4 rounded-2xl bg-gradient-to-br from-rapanel-gold/15 to-rapanel-gold/5 border border-rapanel-gold/30 shadow-lg shadow-rapanel-gold/10">
                            <div class="flex items-center justify-center gap-2 text-rapanel-gold">
                                <SparklesIcon class="w-5 h-5 flex-shrink-0" />
                                <span class="text-3xl font-extrabold tracking-tight">+{{ cashpoints.toLocaleString() }}</span>
                            </div>
                            <p class="text-rapanel-gold/60 text-xs font-semibold uppercase tracking-widest mt-1">
                                {{ __('Donation Points') }}
                            </p>
                        </div>
                        <div v-else
                            class="mx-auto w-fit px-6 py-4 rounded-2xl bg-white/5 border border-white/10">
                            <div class="flex items-center gap-2 text-white/60">
                                <GiftIcon class="w-5 h-5" />
                                <span class="text-sm">{{ __('Your Donation Points have been added to your master account.') }}</span>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="border-t border-white/8" />

                        <!-- Countdown -->
                        <p class="text-white/25 text-xs">
                            {{ __('Redirecting in :n seconds…', { n: Math.max(1, countdown) }) }}
                        </p>

                        <!-- Buttons -->
                        <div class="flex gap-3">
                            <Link :href="route('donations')" @click="clearInterval(timer)"
                                class="flex-1 flex items-center justify-center gap-1.5 py-3 rounded-xl bg-rapanel-blue text-white font-semibold text-sm hover:opacity-90 active:scale-95 transition shadow-lg shadow-rapanel-blue/30">
                                <GiftIcon class="w-4 h-4" />
                                {{ __('Donate Again') }}
                            </Link>
                            <Link :href="route('dashboard')" @click="clearInterval(timer)"
                                class="flex-1 flex items-center justify-center gap-1.5 py-3 rounded-xl bg-white/8 hover:bg-white/12 border border-white/10 text-white/80 font-semibold text-sm transition active:scale-95">
                                <HomeIcon class="w-4 h-4" />
                                {{ __('Go to Dashboard') }}
                            </Link>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </MainLayout>
</template>
