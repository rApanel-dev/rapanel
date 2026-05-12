<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
    account: Object,
});

const emit = defineEmits(['close']);
const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const currentLogs = ref([]);
const isLogsLoading = ref(false);

// En el <script setup>
watch(() => props.show, async (value) => {
    if (value && props.account) {
        isLogsLoading.value = true;
        currentLogs.value = []; // Limpiamos logs anteriores al abrir
        try {
            const response = await axios.get(route('game-accounts.logs', props.account.account_id));
            console.log("Datos recibidos:", response.data); // DEBUG: Mira esto en F12
            currentLogs.value = response.data;
        } catch (e) { 
            console.error("Error en la petición:", e); 
        } finally { 
            isLogsLoading.value = false; 
        }
    }
});

const formatDate = (date) => new Date(date).toLocaleString(undefined, {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
});
</script>

<template>
    <Modal :show="show" @close="emit('close')">
        <div class="bg-white dark:bg-gray-900 p-6">
            <h2 class="text-lg font-medium text-white mb-4">
                {{ __('Activity Log for') }} {{ account?.userid }}
            </h2>

            <div v-if="isLogsLoading" class="py-8 text-center text-gray-400">
                {{ __('Loading activity...') }}
            </div>

            <div v-else-if="currentLogs.length === 0" class="py-8 text-center text-gray-500">
                {{ __('No recent activity found.') }}
            </div>

            <div v-else class="space-y-4 max-h-96 overflow-y-auto pr-2">
                <div v-for="log in currentLogs" :key="log.id" class="border-l-2 border-purple-500 pl-4 py-1">
                    <p class="text-sm text-gray-200 font-semibold">{{ __(log.action) }}</p>
                    <p class="text-xs text-gray-500">{{ formatDate(log.created_at) }} • IP: {{ log.ip_address }}</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="emit('close')">{{ __('Close') }}</SecondaryButton>
            </div>
        </div>
    </Modal>
</template>
