<script setup>
import { ref, watch, onUnmounted, computed } from 'vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({ show: Boolean });
const emit = defineEmits(['close']);

const token = ref(null);
const secondsLeft = ref(0);
const interval = ref(null);
const copied = ref(false);

// Formatear el tiempo MM:SS
const timerDisplay = computed(() => {
    const m = Math.floor(secondsLeft.value / 60);
    const s = secondsLeft.value % 60;
    return `${m}:${s < 10 ? '0' : ''}${s}`;
});

// El comando final que se copiará
const linkCommand = computed(() => token.value ? `/link ${token.value}` : '');

const fetchToken = async () => {
    try {
        const response = await axios.get(route('game-accounts.claim.token'));
        token.value = response.data.token;
        secondsLeft.value = response.data.expires_in;
        startTimer();
    } catch (e) { console.error("Error fetching token"); }
};

const startTimer = () => {
    clearInterval(interval.value);
    interval.value = setInterval(() => {
        if (secondsLeft.value > 0) secondsLeft.value--;
        else clearInterval(interval.value);
    }, 1000);
};

const copyCommand = () => {
    navigator.clipboard.writeText(linkCommand.value);
    copied.value = true;
    setTimeout(() => copied.value = false, 2000);
};

watch(() => props.show, (val) => {
    if (val) fetchToken();
    else clearInterval(interval.value);
});

onUnmounted(() => clearInterval(interval.value));
</script>

<template>
    <Modal :show="show" @close="emit('close')">
        <div class="p-6 bg-white dark:bg-rapanel-navy-800 relative overflow-hidden">
            <div v-if="secondsLeft <= 0 && token" class="absolute inset-0 z-10 bg-rapanel-navy-900/90 backdrop-blur-sm flex flex-col items-center justify-center p-6 text-center">
                <p class="text-rapanel-danger font-bold mb-4">{{ __('The code has expired') }}</p>
                <PrimaryButton @click="fetchToken">{{ __('Generate New Code') }}</PrimaryButton>
            </div>

            <h2 class="text-xl font-bold text-white uppercase tracking-wider mb-2">
                {{ __('Claim your Account') }}
            </h2>
            
            <p class="text-sm text-gray-400 mb-6">
                {{ __('Copy the command below and paste it in the game chat to verify ownership.') }}
            </p>

            <div class="bg-rapanel-navy-900 border-2 border-dashed border-rapanel-blue/40 rounded-xl p-6 mb-6 group hover:border-rapanel-blue transition-all">
                <div class="flex flex-col items-center">
                    <span class="text-xs text-rapanel-blue font-bold uppercase mb-2 tracking-widest">{{ __('Click to copy command') }}</span>
                    <div @click="copyCommand" class="cursor-pointer text-2xl font-mono font-bold text-white group-hover:scale-105 transition-transform">
                        {{ linkCommand }}
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mb-8 px-2">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-rapanel-blue animate-ping"></div>
                    <span class="text-sm font-mono text-gray-300">{{ timerDisplay }}</span>
                </div>
                <span v-if="copied" class="text-xs text-green-400 font-bold animate-bounce">
                    {{ __('Command Copied!') }}
                </span>
            </div>

            <div class="space-y-3 text-sm text-gray-400 bg-rapanel-navy-900/50 p-4 rounded-lg border border-white/5">
                <p><strong>1.</strong> {{ __('Copy the /link command above.') }}</p>
                <p><strong>2.</strong> {{ __('Log in to RO with the account you want to claim.') }}</p>
                <p><strong>3.</strong> {{ __('Paste the command in any chat window.') }}</p>
            </div>

            <div class="mt-8 flex justify-end">
                <SecondaryButton @click="emit('close')">{{ __('Close') }}</SecondaryButton>
            </div>
        </div>
    </Modal>
</template>
