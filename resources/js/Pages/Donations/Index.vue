<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { HeartIcon, XMarkIcon, SparklesIcon, StarIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    packages:          { type: Array,   default: () => [] },
    paypalEnabled:     { type: Boolean, default: false },
    stripeEnabled:     { type: Boolean, default: false },
    mpEnabled:         { type: Boolean, default: false },
    paypalClientId:    { type: String,  default: null },
    donationPoints:    { type: Number,  default: null },
    cashPointsEnabled: { type: Boolean, default: false },
    gameAccounts:      { type: Array,   default: () => [] },
    onlineAccountIds:  { type: Array,   default: () => [] },
    conversionRate:    { type: Object,  default: () => ({ from: 10, to: 100 }) },
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const isAuth = !!page.props.auth?.user;

const flashSuccess = computed(() => page.props.flash?.success);
const flashError   = computed(() => page.props.flash?.error);

// ── Donation Points local state ────────────────────────────────────────────
const localDonationPoints = ref(props.donationPoints ?? 0);
watch(() => props.donationPoints, (v) => { if (v !== null) localDonationPoints.value = v; });

// ── Convert modal ──────────────────────────────────────────────────────────
const showConvert = ref(false);
const convertForm = useForm({ donation_points: props.conversionRate.from, account_id: '' });

function openConvertModal() {
    showConvert.value = true;
    router.reload({ only: ['onlineAccountIds', 'gameAccounts', 'donationPoints'] });
}

watch(() => convertForm.account_id, (id) => {
    if (showConvert.value && id) {
        router.reload({ only: ['onlineAccountIds', 'gameAccounts'] });
    }
});

const selectedAccountOnline = computed(() =>
    convertForm.account_id !== '' && props.onlineAccountIds.includes(Number(convertForm.account_id))
);

const selectedAccountCp = computed(() => {
    if (!convertForm.account_id) return null;
    const acc = props.gameAccounts.find(a => a.account_id === Number(convertForm.account_id));
    return acc ? acc.cash_points : null;
});

const cashResult = computed(() => {
    const units = Math.floor(convertForm.donation_points / props.conversionRate.from);
    return units > 0 ? units * props.conversionRate.to : 0;
});

const maxConvertable = computed(() =>
    Math.floor(localDonationPoints.value / props.conversionRate.from) * props.conversionRate.from
);

function submitConvert() {
    convertForm.post(route('donations.convert'), {
        preserveScroll: true,
        onSuccess: () => { showConvert.value = false; convertForm.reset(); },
    });
}

// ── 3D card tilt ──────────────────────────────────────────────────────────
onMounted(() => {
    document.querySelectorAll('.don-card').forEach(card => {
        card.addEventListener('mousemove', e => {
            const r = card.getBoundingClientRect();
            const x = ((e.clientX - r.left) / r.width  - 0.5) * 14;
            const y = ((e.clientY - r.top)  / r.height - 0.5) * 14;
            card.style.transform = `perspective(800px) rotateY(${x}deg) rotateX(${-y}deg) translateZ(6px)`;
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(800px) rotateY(0) rotateX(0) translateZ(0)';
        });
    });
});

// ── Payment modal ──────────────────────────────────────────────────────────
const selectedPackage = ref(null);
const showModal       = ref(false);
const paypalLoading   = ref(false);
const stripeLoading   = ref(false);
const paypalReady     = ref(false);
const paypalError     = ref(null);
let paypalButtonsInstance = null;

async function openDonate(pkg) {
    if (!isAuth) {
        router.visit(route('login'));
        return;
    }
    selectedPackage.value = pkg;
    showModal.value = true;

    if (props.paypalEnabled && props.paypalClientId) {
        await nextTick();
        loadPayPalButtons(pkg);
    }
}

function closeModal() {
    showModal.value = false;
    selectedPackage.value = null;
    paypalReady.value = false;
    if (paypalButtonsInstance) {
        paypalButtonsInstance.close?.();
        paypalButtonsInstance = null;
    }
    const container = document.getElementById('paypal-button-container');
    if (container) container.innerHTML = '';
}

async function loadPayPalButtons(pkg) {
    paypalReady.value = false;
    const container = document.getElementById('paypal-button-container');
    if (container) container.innerHTML = '';

    if (!window.paypal) {
        const script = document.createElement('script');
        script.src = `https://www.paypal.com/sdk/js?client-id=${props.paypalClientId}&currency=USD&disable-funding=card,credit,paylater,venmo`;
        script.onload = () => renderPayPalButtons(pkg);
        document.head.appendChild(script);
    } else {
        renderPayPalButtons(pkg);
    }
}

function resetPayPalButtons(pkg) {
    paypalLoading.value = false;
    paypalReady.value = false;
    if (paypalButtonsInstance) {
        paypalButtonsInstance.close?.();
        paypalButtonsInstance = null;
    }
    const container = document.getElementById('paypal-button-container');
    if (container) container.innerHTML = '';
    renderPayPalButtons(pkg);
}

function renderPayPalButtons(pkg) {
    paypalReady.value = true;
    paypalError.value = null;
    paypalButtonsInstance = window.paypal.Buttons({
        createOrder: async () => {
            paypalError.value = null;
            const res = await fetch(route('donations.paypal.create'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ package_id: pkg.id }),
            });
            const data = await res.json();
            if (!data.order_id) throw new Error(data.error || 'Order creation failed');
            return data.order_id;
        },
        onApprove: async (data) => {
            paypalLoading.value = true;
            const res = await fetch(route('donations.paypal.capture'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ order_id: data.orderID, package_id: pkg.id }),
            });
            const result = await res.json();
            paypalLoading.value = false;
            if (result.success) {
                window.location.href = route('donations.success');
            }
        },
        onCancel: () => {
            resetPayPalButtons(pkg);
        },
        onError: (err) => {
            console.error('PayPal error', err);
            paypalError.value = __('Payment failed. Please try again.');
            resetPayPalButtons(pkg);
        },
    });
    paypalButtonsInstance.render('#paypal-button-container');
}

const mpLoading = ref(false);

async function donateWithMP() {
    if (!selectedPackage.value) return;
    mpLoading.value = true;
    try {
        const res = await fetch(route('donations.mp.create'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ package_id: selectedPackage.value.id }),
        });
        const data = await res.json();
        if (data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            mpLoading.value = false;
        }
    } catch (e) {
        mpLoading.value = false;
    }
}

async function donateWithStripe() {
    if (!selectedPackage.value) return;
    stripeLoading.value = true;
    try {
        const res = await fetch(route('donations.stripe.create'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ package_id: selectedPackage.value.id }),
        });
        const data = await res.json();
        if (data.checkout_url) {
            window.location.href = data.checkout_url;
        }
    } catch (e) {
        console.error(e);
    } finally {
        stripeLoading.value = false;
    }
}
</script>

<template>
    <Head :title="__('Donations')" />

    <MainLayout :show-bg="true">
        <!-- Header -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <HeartIcon class="w-7 h-7 text-rapanel-danger" />
                            <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide uppercase">
                                {{ __('Donations') }}
                            </h1>
                        </div>
                        <p class="text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 text-sm max-w-xl">
                            {{ __('Support the server and receive Donation Points instantly.') }}
                        </p>
                        <div v-if="isAuth" class="mt-2 text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                            {{ __('Exchange rate:') }}
                            <span class="font-semibold text-rapanel-gold">{{ conversionRate.from }} {{ __('Donation Points') }}</span>
                            <span class="mx-1">→</span>
                            <span class="font-semibold text-rapanel-success">{{ conversionRate.to }} {{ __('Cash Points') }}</span>
                        </div>
                    </div>

                    <!-- Balance + botón convertir (solo logueado) -->
                    <div v-if="isAuth" class="flex items-center gap-3 flex-shrink-0">
                        <button
                            v-if="localDonationPoints > 0 && gameAccounts.length"
                            @click="openConvertModal"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90 transition shadow-sm text-white bg-rapanel-danger dark:bg-rapanel-gold dark:text-rapanel-navy-900"
                        >
                            <ArrowPathIcon class="w-4 h-4" />
                            {{ __('Convert to Cash Points') }}
                        </button>
                        <div class="bg-rapanel-gold/10 border border-rapanel-gold/30 rounded-xl px-5 py-3 text-center min-w-[96px]">
                            <div class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 uppercase tracking-widest mb-0.5">
                                {{ __('Donation Points') }}
                            </div>
                            <div class="text-2xl font-bold font-display text-rapanel-gold leading-none">
                                {{ localDonationPoints.toLocaleString() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash messages -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <FlashMessages :success="flashSuccess" :error="flashError" />
        </div>

        <!-- Packages grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div v-if="packages.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="pkg in packages" :key="pkg.id"
                    class="don-card group relative flex flex-col rounded-2xl bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10"
                    :class="pkg.is_featured ? 'shadow-lg' : 'shadow-sm'"
                    :style="`--c: ${pkg.border_hex}`">

                    <!-- Package image -->
                    <div class="aspect-video bg-rapanel-navy-50 dark:bg-rapanel-navy-800 overflow-hidden rounded-t-2xl flex items-center justify-center">
                        <img v-if="pkg.image_url" :src="pkg.image_url" :alt="pkg.title"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        <HeartIcon v-else class="w-16 h-16 text-rapanel-navy-200 dark:text-white/10" />
                    </div>

                    <!-- "Most Popular" banner — franja sólida entre imagen y body -->
                    <div v-if="pkg.is_featured"
                        class="flex items-center justify-center gap-1.5 py-1.5 text-xs font-bold tracking-wide uppercase text-white"
                        :style="`background: ${pkg.border_hex}`">
                        <StarIcon class="w-3 h-3" />
                        {{ __('Most Popular') }}
                    </div>

                    <!-- Card body -->
                    <div class="p-5 flex-1 flex flex-col gap-3">
                        <h2 class="font-display font-bold text-lg text-rapanel-navy-900 dark:text-white tracking-wide">{{ pkg.title }}</h2>
                        <p v-if="pkg.description" class="text-sm text-rapanel-text-light/70 dark:text-rapanel-text-dark/70 leading-relaxed">{{ pkg.description }}</p>

                        <!-- Pricing -->
                        <div class="mt-auto pt-3 border-t border-rapanel-navy-50 dark:border-white/5 space-y-2">
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-2xl font-display font-bold text-rapanel-gold">${{ pkg.price_usd.toFixed(2) }}</span>
                                <span class="text-sm text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">USD</span>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-semibold text-rapanel-navy-700 dark:text-white/80">
                                    {{ pkg.cashpoints.toLocaleString() }} {{ __('Donation Points') }}
                                </span>
                                <template v-if="pkg.bonus_percent > 0">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-rapanel-success/10 text-rapanel-success border border-rapanel-success/20">
                                        <SparklesIcon class="w-3 h-3" />
                                        +{{ pkg.bonus_percent }}%
                                    </span>
                                    <span class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">=</span>
                                    <span class="text-sm font-bold text-rapanel-navy-900 dark:text-white">
                                        {{ pkg.total_cashpoints.toLocaleString() }} {{ __('Donation Points') }} {{ __('total') }}
                                    </span>
                                </template>
                            </div>

                            <button @click="openDonate(pkg)"
                                class="don-btn w-full mt-2 py-2.5 rounded-xl font-display font-bold text-sm uppercase tracking-wide transition-all shadow-sm">
                                {{ __('Donate Now') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center py-24 text-center">
                <HeartIcon class="w-16 h-16 text-rapanel-navy-200 dark:text-white/10 mb-4" />
                <p class="text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">{{ __('No packages configured.') }}</p>
            </div>
        </div>

        <!-- Convert Modal -->
        <Modal :show="showConvert" @close="showConvert = false">
            <div class="p-6">
                <h2 class="text-lg font-display font-bold text-rapanel-navy-900 dark:text-white mb-4 uppercase tracking-wide">
                    {{ __('Convert to Cash Points') }}
                </h2>

                <form @submit.prevent="submitConvert" class="space-y-4">
                    <!-- Cuenta de juego -->
                    <div>
                        <InputLabel :value="__('Game Account')" />
                        <select
                            v-model="convertForm.account_id"
                            class="mt-1 block w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue"
                        >
                            <option value="" disabled>{{ __('Select account...') }}</option>
                            <option v-for="acc in gameAccounts" :key="acc.account_id" :value="acc.account_id">
                                {{ acc.userid }}
                            </option>
                        </select>

                        <!-- Balance CP de la cuenta (solo si cashpoints habilitado) -->
                        <div v-if="cashPointsEnabled && selectedAccountCp !== null && !selectedAccountOnline"
                            class="mt-2 flex items-center justify-between px-3 py-1.5 rounded-lg bg-rapanel-blue/10 border border-rapanel-blue/20 text-xs">
                            <span class="text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ __('Current Cash Points:') }}</span>
                            <span class="font-bold text-rapanel-blue">{{ selectedAccountCp.toLocaleString() }} CP</span>
                        </div>

                        <InputError :message="convertForm.errors.account_id" class="mt-1" />

                        <!-- Personaje online bloqueado -->
                        <div v-if="selectedAccountOnline"
                            class="mt-2 flex items-start gap-2 rounded-lg bg-rapanel-danger border border-rapanel-danger px-3 py-2 text-xs text-white">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                            <span>{{ __('You must log out all characters before converting donation points.') }}</span>
                        </div>
                    </div>

                    <!-- Aviso general -->
                    <div class="flex items-start gap-2 rounded-lg bg-amber-100 border border-amber-300 text-amber-800 dark:bg-rapanel-gold/10 dark:border-rapanel-gold/30 dark:text-rapanel-gold px-3 py-2 text-xs">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z" />
                        </svg>
                        <span>{{ __('vote_convert_offline_warning') }}</span>
                    </div>

                    <!-- Puntos a gastar -->
                    <div>
                        <InputLabel :value="__('Donation Points to spend')" />
                        <input
                            v-model.number="convertForm.donation_points"
                            type="number"
                            :min="conversionRate.from"
                            :max="maxConvertable"
                            :step="conversionRate.from"
                            class="mt-1 block w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue"
                        />
                        <InputError :message="convertForm.errors.donation_points" class="mt-1" />
                    </div>

                    <!-- Preview resultado -->
                    <div class="rounded-lg border border-rapanel-navy-200 dark:border-white/10 bg-rapanel-navy-100 dark:bg-rapanel-navy-800 px-4 py-3 text-sm flex justify-between">
                        <span class="text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">{{ __('You will receive:') }}</span>
                        <span class="font-bold text-rapanel-success">{{ cashResult.toLocaleString() }} {{ __('Cash Points') }}</span>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <SecondaryButton @click="showConvert = false">{{ __('Cancel') }}</SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            :disabled="convertForm.processing || cashResult === 0 || !convertForm.account_id || selectedAccountOnline"
                        >
                            {{ __('Convert') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Payment Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-2xl w-full max-w-sm">
                    <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/10 flex items-center justify-between">
                        <div>
                            <h2 class="font-display font-bold text-rapanel-navy-900 dark:text-white">{{ selectedPackage?.title }}</h2>
                            <p class="text-xs text-rapanel-gold font-bold">${{ selectedPackage?.price_usd.toFixed(2) }} → {{ selectedPackage?.total_cashpoints.toLocaleString() }} {{ __('Donation Points') }}</p>
                        </div>
                        <button @click="closeModal" class="p-1 rounded hover:bg-rapanel-navy-100 dark:hover:bg-white/10 transition text-rapanel-text-light/50 dark:text-white/50">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mb-4">{{ __('Select a payment method') }}</p>

                        <div v-if="paypalEnabled">
                            <div id="paypal-button-container" class="min-h-[45px]">
                                <div v-if="!paypalReady" class="flex items-center justify-center h-11 rounded-lg bg-[#FFC439] animate-pulse">
                                    <span class="text-[#003087] font-bold text-sm">PayPal</span>
                                </div>
                            </div>
                            <p v-if="paypalError" class="mt-2 text-xs text-center text-rapanel-danger">{{ paypalError }}</p>
                        </div>

                        <button v-if="stripeEnabled" @click="donateWithStripe" :disabled="stripeLoading"
                            class="w-full py-3 rounded-xl bg-[#635BFF] text-white font-bold text-sm flex items-center justify-center gap-2 hover:opacity-90 transition disabled:opacity-50">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.591-7.305z"/></svg>
                            {{ stripeLoading ? __('Redirecting…') : 'Stripe' }}
                        </button>

                        <button v-if="mpEnabled" @click="donateWithMP" :disabled="mpLoading"
                            class="w-full py-3 rounded-xl bg-[#009EE3] text-white font-bold text-sm flex items-center justify-center gap-2 hover:opacity-90 transition disabled:opacity-50">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M11.5 0C5.149 0 0 5.149 0 11.5S5.149 23 11.5 23 23 17.851 23 11.5 17.851 0 11.5 0zm4.712 8.337c.244 1.314-.17 2.6-1.04 3.513-.87.914-2.13 1.438-3.564 1.438H9.86l-.63 3.375H7.046l1.87-9.863h3.672c1.303 0 2.37.456 3.017 1.285.3.39.503.846.607 1.252z"/></svg>
                            {{ mpLoading ? __('Redirecting…') : 'Mercado Pago' }}
                        </button>

                        <p v-if="!paypalEnabled && !stripeEnabled && !mpEnabled" class="text-center text-sm text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 py-4">
                            {{ __('No payment methods configured.') }}
                        </p>
                    </div>
                </div>
            </div>
        </Teleport>
    </MainLayout>
</template>

<style scoped>
/* ── Card base — igual a .feat-card en HomeAlt ──────────────────── */
.don-card {
    transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.1s ease;
    will-change: transform;
}

.don-card:hover {
    border-color: var(--c) !important;
    box-shadow: 0 20px 40px rgba(0,0,0,0.12),
                0 0 32px color-mix(in srgb, var(--c) 22%, transparent);
}

.dark .don-card:hover {
    box-shadow: 0 20px 60px rgba(0,0,0,0.5),
                0 0 36px color-mix(in srgb, var(--c) 28%, transparent);
}

/* ── Donate button ──────────────────────────────────────────────── */
.don-btn {
    background: var(--c);
    color: #fff;
}
.don-btn:hover {
    filter: brightness(1.1);
    box-shadow: 0 0 18px color-mix(in srgb, var(--c) 45%, transparent);
}
</style>
