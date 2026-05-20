<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    show: Boolean,
    account: Object,
});

const emit = defineEmits(['close']);
const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const currentLogs = ref([]);
const isLogsLoading = ref(false);

watch(() => props.show, async (value) => {
    if (value && props.account) {
        isLogsLoading.value = true;
        currentLogs.value = [];
        try {
            const response = await axios.get(route('game-accounts.logs', props.account.account_id));
            currentLogs.value = response.data;
        } catch (e) {
            console.error('Error fetching activity logs:', e);
        } finally {
            isLogsLoading.value = false;
        }
    }
});

const formatDate = (date) => new Date(date).toLocaleString(undefined, {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
});

const isCharLog = (log) => log.category === 'CHARACTER';

const containerClass = (log) => isCharLog(log)
    ? 'border-l-2 border-rapanel-purple pl-4 py-2 rounded-r-lg bg-rapanel-purple/5 dark:bg-rapanel-purple/10'
    : 'border-l-2 border-rapanel-blue   pl-4 py-2 rounded-r-lg bg-rapanel-blue/5   dark:bg-rapanel-blue/10';

const parseMeta = (log) => typeof log.metadata === 'string' ? JSON.parse(log.metadata) : (log.metadata ?? {});

const charName    = (log) => parseMeta(log)?.char_name    ?? null;
const adminName   = (log) => parseMeta(log)?.admin_name   ?? null;
const isAdminLog  = (log) => !!parseMeta(log)?.admin_override;
</script>

<template>
    <Modal :show="show" @close="emit('close')">
        <div class="bg-white dark:bg-rapanel-navy-900 p-6">
            <h2 class="text-lg font-medium text-rapanel-text-light dark:text-rapanel-text-dark mb-4">
                {{ __('Activity Log for') }} <span class="font-bold text-rapanel-blue">{{ account?.userid }}</span>
            </h2>

            <div v-if="isLogsLoading" class="py-8 text-center text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">
                {{ __('Loading activity...') }}
            </div>

            <div v-else-if="currentLogs.length === 0" class="py-8 text-center text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">
                {{ __('No recent activity found.') }}
            </div>

            <div v-else class="space-y-2 max-h-96 overflow-y-auto pr-2">
                <div v-for="log in currentLogs" :key="log.id" :class="containerClass(log)">
                    <p class="text-sm font-semibold text-rapanel-text-light dark:text-rapanel-text-dark leading-snug">
                        {{ __(log.action) }}
                    </p>
                    <p v-if="isCharLog(log) && charName(log)" class="text-xs font-bold text-rapanel-gold mt-0.5">
                        {{ charName(log) }}
                    </p>
                    <p v-if="isAdminLog(log)" class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-rapanel-danger mt-0.5">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        Admin: {{ adminName(log) }}
                    </p>
                    <p class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/50 mt-0.5">
                        {{ formatDate(log.created_at) }} • IP: {{ log.ip_address }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <PrimaryButton @click="emit('close')">{{ __('Close') }}</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
