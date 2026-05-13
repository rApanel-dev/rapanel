<script setup>
import { ref, watch, onUnmounted, computed } from 'vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({ show: Boolean });
const emit = defineEmits(['close']);

const token = ref(null);
const secondsLeft = ref(0);
const interval = ref(null);
const copied = ref(false);

const timerDisplay = computed(() => {
    const totalSeconds = Math.max(0, Number(secondsLeft.value));
    const m = Math.floor(totalSeconds / 60);
    const s = totalSeconds % 60;
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
});

const linkCommand = computed(() => token.value ? `/link ${token.value}` : '...');

const fetchToken = async () => {
    try {
        const response = await axios.get(route('game-accounts.claim.token'));
        token.value = response.data.token;
        secondsLeft.value = parseInt(response.data.expires_in);
        startTimer();
    } catch (e) { 
        console.error(__("Error fetching token")); 
    }
};

const startTimer = () => {
    clearInterval(interval.value);
    interval.value = setInterval(() => {
        if (secondsLeft.value > 0) {
            secondsLeft.value--;
        } else {
            clearInterval(interval.value);
        }
    }, 1000);
};

const copyCommand = () => {
    const textToCopy = linkCommand.value;
    
    // Crear elemento temporal para asegurar el copiado en HTTP/IPs
    const textArea = document.createElement("textarea");
    textArea.value = textToCopy;
    // Aseguramos que no se vea pero que esté en el DOM
    textArea.style.position = "fixed";
    textArea.style.left = "-9999px";
    textArea.style.top = "0";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            copied.value = true;
            setTimeout(() => copied.value = false, 2000);
        }
    } catch (err) {
        console.error('Unable to copy', err);
    }
    
    document.body.removeChild(textArea);
};

watch(() => props.show, (val) => {
    if (val) fetchToken();
    else {
        clearInterval(interval.value);
    }
});

onUnmounted(() => clearInterval(interval.value));
</script>

<template>
    <Modal :show="show" @close="emit('close')">
        <div class="p-6 bg-white dark:bg-rapanel-navy-800 transition-colors duration-300">
            
            <div v-if="secondsLeft <= 0 && token" class="absolute inset-0 z-20 bg-white/95 dark:bg-rapanel-navy-900/95 backdrop-blur-sm flex flex-col items-center justify-center p-6 text-center">
                <p class="text-red-600 dark:text-rapanel-danger font-bold text-xl mb-4 uppercase">
                    {{ __('The code has expired') }}
                </p>
                <PrimaryButton @click="fetchToken">
                    {{ __('Generate New Code') }}
                </PrimaryButton>
            </div>

            <h2 class="text-xl font-bold text-gray-900 dark:text-white uppercase tracking-widest mb-2">
                {{ __('Claim your Account') }}
            </h2>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                {{ __('Copy the command below and paste it in the game chat to verify ownership.') }}
            </p>

            <div 
                @click="copyCommand"
                class="bg-gray-100 dark:bg-rapanel-navy-900 border-2 border-dashed border-blue-500/30 dark:border-rapanel-blue/30 rounded-xl p-8 mb-6 group hover:border-blue-500 dark:hover:border-rapanel-blue transition-all cursor-pointer relative"
            >
                <div class="flex flex-col items-center">
                    <span 
                        class="text-[10px] font-bold uppercase mb-3 tracking-widest"
                        :class="copied ? 'text-green-500 dark:text-rapanel-success' : 'text-blue-600 dark:text-rapanel-blue'"
                    >
                        {{ copied ? __('Command Copied!') : __('Click to copy command') }}
                    </span>
                    <div class="text-3xl font-mono font-bold text-gray-900 dark:text-white tracking-widest">
                        {{ linkCommand }}
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-3 bg-gray-50 dark:bg-rapanel-navy-900 px-4 py-2 rounded-full border border-gray-200 dark:border-white/5">
                    <div class="w-2 h-2 rounded-full bg-red-500 dark:bg-rapanel-danger animate-ping"></div>
                    <span class="text-sm font-mono text-red-600 dark:text-rapanel-danger font-bold">{{ timerDisplay }}</span>
                </div>
            </div>

            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-rapanel-navy-900/50 p-5 rounded-xl border border-gray-200 dark:border-white/5">
                <p class="flex gap-3"><span class="text-blue-600 dark:text-rapanel-blue font-bold">01.</span> {{ __('Copy the /link command above.') }}</p>
                <p class="flex gap-3"><span class="text-blue-600 dark:text-rapanel-blue font-bold">02.</span> {{ __('Log in to RO with the account you want to claim.') }}</p>
                <p class="flex gap-3"><span class="text-blue-600 dark:text-rapanel-blue font-bold">03.</span> {{ __('Paste the command in any chat window.') }}</p>
            </div>

            <div class="mt-8 flex justify-end">
                <PrimaryButton @click="emit('close')">
                    {{ __('Close') }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>