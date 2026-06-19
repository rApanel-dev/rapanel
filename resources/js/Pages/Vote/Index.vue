<script setup>
import { ref, computed, reactive, watch } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { StarIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    sites:             { type: Array,  default: () => [] },
    userVotes:         { type: Object, default: () => ({}) },
    votePoints:        { type: Number, default: 0 },
    cashPointsEnabled: { type: Boolean, default: false },
    gameAccounts:      { type: Array,  default: () => [] },
    onlineAccountIds:  { type: Array,  default: () => [] },
    conversionRate:    { type: Object, default: () => ({ from: 10, to: 100 }) },
});

const page = usePage();
const __   = (key, r = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(r).forEach(([k, v]) => { t = t.replace(':' + k, v); });
    return t;
};

const flashSuccess = computed(() => page.props.flash?.success);
const flashError   = computed(() => page.props.flash?.error);

// ── Countdown ──────────────────────────────────────────────────────────────
const now = ref(new Date());
setInterval(() => { now.value = new Date(); }, 1000);

function remainingTime(nextVoteAt) {
    const diff = new Date(nextVoteAt) - now.value;
    if (diff <= 0) return null;
    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    return `${h}h ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}s`;
}

const HONOR_TYPES = ['honor'];

const localVotes      = reactive({ ...props.userVotes });
const localVotePoints = ref(props.votePoints);
watch(() => props.votePoints, (v) => { localVotePoints.value = v; });

function canVoteNow(siteId) {
    const v = localVotes[siteId];
    if (!v) return true;
    if (v.can_vote) return true;
    return new Date(v.next_vote_at) <= now.value;
}

// Honor system
const clickingIds = reactive({});

async function honorVote(site) {
    if (clickingIds[site.id] || !canVoteNow(site.id)) return;
    window.open(site.vote_url, '_blank', 'noopener,noreferrer');
    clickingIds[site.id] = true;
    try {
        const res = await window.axios.post(route('vote.click', site.id));
        if (res.data?.next_vote_at) {
            localVotes[site.id] = { can_vote: false, next_vote_at: res.data.next_vote_at };
            localVotePoints.value += res.data.points ?? 0;
        }
    } catch (_) {
        // silencioso
    } finally {
        clickingIds[site.id] = false;
    }
}

// ── Convert modal ──────────────────────────────────────────────────────────
const showConvert  = ref(false);
const convertForm  = useForm({ vote_points: props.conversionRate.from, account_id: '' });

function openConvertModal() {
    showConvert.value = true;
    router.reload({ only: ['onlineAccountIds', 'gameAccounts'] });
}

// Refresca estado online cada vez que el usuario cambia de cuenta en el modal
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
    const units = Math.floor(convertForm.vote_points / props.conversionRate.from);
    return units > 0 ? units * props.conversionRate.to : 0;
});

const maxConvertable = computed(() =>
    Math.floor(localVotePoints.value / props.conversionRate.from) * props.conversionRate.from
);

function submitConvert() {
    convertForm.post(route('vote.convert'), {
        preserveScroll: true,
        onSuccess: () => { showConvert.value = false; convertForm.reset(); },
    });
}
</script>

<template>
    <MainLayout :show-bg="true">
        <Head :title="__('Vote 4 Points')" />

        <!-- Page header -->
        <div class="bg-white dark:bg-rapanel-navy-900 border-b border-rapanel-navy-100 dark:border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                    <div>
                        <h1 class="text-3xl font-display font-bold text-rapanel-navy-900 dark:text-white tracking-wide uppercase">
                            {{ __('Vote 4 Points') }}
                        </h1>
                        <p class="mt-1 text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                            {{ __('Vote for our server and earn points you can convert to Cash Points.') }}
                        </p>
                        <div v-if="cashPointsEnabled" class="mt-2 text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                            {{ __('Exchange rate:') }}
                            <span class="font-semibold text-rapanel-gold">{{ conversionRate.from }} {{ __('Vote Points') }}</span>
                            <span class="mx-1">→</span>
                            <span class="font-semibold text-rapanel-success">{{ conversionRate.to }} {{ __('Cash Points') }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <button
                            v-if="cashPointsEnabled && localVotePoints >= conversionRate.from && gameAccounts.length"
                            @click="openConvertModal"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90 transition shadow-sm text-white bg-rapanel-danger dark:bg-rapanel-gold dark:text-rapanel-navy-900"
                        >
                            <ArrowPathIcon class="w-4 h-4" />
                            {{ __('Convert to Cash Points') }}
                        </button>
                        <div class="bg-rapanel-gold/10 border border-rapanel-gold/30 rounded-xl px-5 py-3 text-center min-w-[96px]">
                            <div class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 uppercase tracking-widest mb-0.5">
                                {{ __('Vote Points') }}
                            </div>
                            <div class="text-2xl font-bold font-display text-rapanel-gold leading-none">
                                {{ localVotePoints.toLocaleString() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            <FlashMessages :success="flashSuccess" :error="flashError" class="mb-6" />

            <!-- Empty state -->
            <div v-if="!sites.length" class="text-center py-24 text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                <StarIcon class="w-16 h-16 mx-auto mb-4 opacity-30" />
                <p class="text-lg font-display">{{ __('No voting sites configured yet.') }}</p>
            </div>

            <!-- Sites grid -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="site in sites" :key="site.id"
                    class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-2xl shadow-xl dark:shadow-black/30 p-6 flex flex-col gap-4"
                >
                    <!-- Site header -->
                    <div class="flex items-center gap-3">
                        <img v-if="site.icon_url" :src="site.icon_url" :alt="site.name"
                             class="h-16 w-auto max-w-[120px] object-contain flex-shrink-0" />
                        <StarIcon v-else class="h-16 w-16 text-rapanel-gold/30 flex-shrink-0" />
                        <div>
                            <h3 class="font-display font-bold text-rapanel-navy-900 dark:text-white text-base leading-tight">
                                {{ site.name }}
                            </h3>
                            <span class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                {{ __('Cooldown:') }} {{ site.cooldown_hours }}h
                            </span>
                        </div>
                    </div>

                    <!-- Points badge -->
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-rapanel-gold/10 text-rapanel-gold text-sm font-bold border border-rapanel-gold/20">
                            <StarIcon class="w-3.5 h-3.5" />
                            +{{ site.points_per_vote }} {{ __('Vote Points') }}
                        </span>
                    </div>

                    <!-- Action -->
                    <div class="mt-auto">
                        <template v-if="canVoteNow(site.id)">
                            <button
                                v-if="HONOR_TYPES.includes(site.callback_type)"
                                @click="honorVote(site)"
                                :disabled="clickingIds[site.id]"
                                class="block w-full text-center px-4 py-2.5 bg-rapanel-blue hover:opacity-90 text-white font-semibold rounded-lg transition text-sm disabled:opacity-60"
                            >
                                {{ __('Vote Now') }}
                            </button>
                            <a
                                v-else
                                :href="site.vote_url" target="_blank" rel="noopener noreferrer"
                                class="block w-full text-center px-4 py-2.5 bg-rapanel-blue hover:opacity-90 text-white font-semibold rounded-lg transition text-sm"
                            >
                                {{ __('Vote Now') }}
                            </a>
                        </template>

                        <!-- Cooldown -->
                        <div v-else class="w-full text-center px-4 py-2.5 bg-rapanel-navy-100 dark:bg-rapanel-navy-800 text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 font-mono text-sm rounded-lg cursor-not-allowed">
                            {{ remainingTime(localVotes[site.id]?.next_vote_at) ?? __('Available soon') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Convert modal -->
        <Modal :show="showConvert" @close="showConvert = false">
            <div class="p-6">
                <h2 class="text-lg font-display font-bold text-rapanel-navy-900 dark:text-white mb-4 uppercase tracking-wide">
                    {{ __('Convert to Cash Points') }}
                </h2>

                <form @submit.prevent="submitConvert" class="space-y-4">
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
                        <!-- Balance Cash Points de la cuenta seleccionada -->
                        <div v-if="selectedAccountCp !== null && !selectedAccountOnline" class="mt-2 flex items-center justify-between px-3 py-1.5 rounded-lg bg-rapanel-blue/10 border border-rapanel-blue/20 text-xs">
                            <span class="text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ __('Current Cash Points:') }}</span>
                            <span class="font-bold text-rapanel-blue">{{ selectedAccountCp.toLocaleString() }} CP</span>
                        </div>

                        <InputError :message="convertForm.errors.account_id" class="mt-1" />

                        <!-- Aviso: personaje online -->
                        <div v-if="selectedAccountOnline" class="mt-2 flex items-start gap-2 rounded-lg bg-rapanel-danger border border-rapanel-danger px-3 py-2 text-xs text-white">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                            <span>{{ __('You must log out all characters before converting vote points.') }}</span>
                        </div>
                    </div>

                    <!-- Aviso general siempre visible -->
                    <div class="flex items-start gap-2 rounded-lg bg-amber-100 border border-amber-300 text-amber-800 dark:bg-rapanel-gold/10 dark:border-rapanel-gold/30 dark:text-rapanel-gold px-3 py-2 text-xs">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z" />
                        </svg>
                        <span>{{ __('vote_convert_offline_warning') }}</span>
                    </div>

                    <div>
                        <InputLabel :value="__('Vote Points to spend')" />
                        <input
                            v-model.number="convertForm.vote_points"
                            type="number"
                            :min="conversionRate.from"
                            :max="maxConvertable"
                            :step="conversionRate.from"
                            class="mt-1 block w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue"
                        />
                        <InputError :message="convertForm.errors.vote_points" class="mt-1" />
                    </div>

                    <div class="rounded-lg border border-rapanel-navy-200 dark:border-white/10 bg-rapanel-navy-100 dark:bg-rapanel-navy-800 px-4 py-3 text-sm flex justify-between">
                        <span class="text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">{{ __('You will receive:') }}</span>
                        <span class="font-bold text-rapanel-success">{{ cashResult.toLocaleString() }} {{ __('Cash Points') }}</span>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <SecondaryButton @click="showConvert = false">{{ __('Cancel') }}</SecondaryButton>
                        <PrimaryButton type="submit" :disabled="convertForm.processing || cashResult === 0 || !convertForm.account_id || selectedAccountOnline">
                            {{ __('Convert') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </MainLayout>
</template>
