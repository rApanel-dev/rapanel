<script setup>
import { ref, reactive, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import {
    PlusIcon, PencilIcon, TrashIcon,
    EyeIcon, EyeSlashIcon, StarIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    sites: { type: Array, default: () => [] },
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

// ── Modal ─────────────────────────────────────────────────────────────────
const showModal  = ref(false);
const editId     = ref(null);
const processing = ref(false);

const blankForm = () => ({
    name:             '',
    icon_url:         '',
    vote_url:         '',
    callback_type:    'generic',
    callback_secret:  '',
    callback_ip:      '',
    points_per_vote:  1,
    cooldown_hours:   12,
    is_active:        true,
    sort_order:       0,
});

const form   = reactive(blankForm());
const errors = ref({});

function openCreate() {
    Object.assign(form, blankForm());
    editId.value = null;
    errors.value = {};
    showModal.value = true;
}

function openEdit(site) {
    Object.assign(form, {
        name:            site.name,
        icon_url:        site.icon_url || '',
        vote_url:        site.vote_url,
        callback_type:   site.callback_type,
        callback_secret: site.callback_secret || '',
        callback_ip:     site.callback_ip || '',
        points_per_vote: site.points_per_vote,
        cooldown_hours:  site.cooldown_hours,
        is_active:       site.is_active,
        sort_order:      site.sort_order,
    });
    editId.value  = site.id;
    errors.value  = {};
    showModal.value = true;
}

function submitForm() {
    processing.value = true;
    errors.value     = {};

    const url    = editId.value ? route('admin.vote-sites.update', editId.value) : route('admin.vote-sites.store');
    const method = editId.value ? 'put' : 'post';

    router[method](url, { ...form }, {
        preserveScroll: true,
        onSuccess: () => { showModal.value = false; },
        onError:   (e) => { errors.value = e; },
        onFinish:  () => { processing.value = false; },
    });
}

function deleteSite(site) {
    if (! confirm(__('Are you sure you want to delete ":name"?', { name: site.name }))) return;
    router.delete(route('admin.vote-sites.destroy', site.id), { preserveScroll: true });
}

function toggleActive(site) {
    router.patch(route('admin.vote-sites.toggle', site.id), {}, { preserveScroll: true });
}

const callbackTypes = [
    { value: 'honor',    label: __('Honor system') },
    { value: 'callback', label: __('Callback (advanced)') },
];

const needsCallbackFields = computed(() => form.callback_type === 'callback');

const callbackHint = computed(() => ({
    honor:    __('Points awarded immediately when the player clicks Vote Now. Works with any voting site.'),
    callback: __('Points awarded when the external site sends a confirmation to your callback URL. Requires the site to support webhooks/postbacks.'),
})[form.callback_type]);
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <PageHeader :title="__('Vote Sites')">
                <button @click="openCreate"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-rapanel-blue text-white rounded-lg text-sm font-semibold hover:opacity-90 transition">
                    <PlusIcon class="w-4 h-4" />
                    {{ __('Add Site') }}
                </button>
            </PageHeader>

            <!-- Descripción de modos -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-rapanel-gold/5 border border-rapanel-gold/20 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-rapanel-gold">{{ __('Honor system') }}</span>
                    </div>
                    <p class="text-xs text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">
                        {{ __('Points are awarded immediately when the player clicks Vote Now. Compatible with any voting site (ToproHispano, RagnaTOP, etc.). No configuration required.') }}
                    </p>
                </div>
                <div class="bg-rapanel-blue/5 border border-rapanel-blue/20 rounded-xl p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-rapanel-blue">{{ __('Callback (advanced)') }}</span>
                    </div>
                    <p class="text-xs text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">
                        {{ __('Points are awarded when the voting site sends a confirmation to your server. Requires the site to support webhooks/postbacks (e.g. TopG, XtremeTop100 Gold).') }}
                    </p>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-rapanel-navy-900 border border-rapanel-navy-100 dark:border-white/10 rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-rapanel-navy-100 dark:divide-white/10 text-sm">
                    <thead class="bg-rapanel-navy-100 dark:bg-rapanel-navy-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-rapanel-navy-700 dark:text-rapanel-text-dark uppercase tracking-wider text-xs">{{ __('Site') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-rapanel-navy-700 dark:text-rapanel-text-dark uppercase tracking-wider text-xs hidden md:table-cell">{{ __('Callback') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-rapanel-navy-700 dark:text-rapanel-text-dark uppercase tracking-wider text-xs">{{ __('Points') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-rapanel-navy-700 dark:text-rapanel-text-dark uppercase tracking-wider text-xs hidden sm:table-cell">{{ __('Cooldown') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-rapanel-navy-700 dark:text-rapanel-text-dark uppercase tracking-wider text-xs">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right font-semibold text-rapanel-navy-700 dark:text-rapanel-text-dark uppercase tracking-wider text-xs">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rapanel-navy-50 dark:divide-white/5">
                        <tr v-for="site in sites" :key="site.id"
                            class="hover:bg-rapanel-navy-50 dark:hover:bg-white/5 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <img v-if="site.icon_url" :src="site.icon_url" :alt="site.name"
                                         class="w-7 h-7 rounded object-contain bg-rapanel-navy-50 dark:bg-rapanel-navy-800 p-0.5 flex-shrink-0" />
                                    <StarIcon v-else class="w-5 h-5 text-rapanel-gold flex-shrink-0" />
                                    <span class="font-medium text-rapanel-navy-900 dark:text-white">{{ site.name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="px-2 py-0.5 rounded text-xs font-mono bg-rapanel-navy-100 dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark">
                                    {{ site.callback_type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-rapanel-gold">+{{ site.points_per_vote }}</span>
                            </td>
                            <td class="px-4 py-3 text-center hidden sm:table-cell text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">
                                {{ site.cooldown_hours }}h
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button @click="toggleActive(site)"
                                    :class="site.is_active
                                        ? 'bg-rapanel-success/10 text-rapanel-success border-rapanel-success/20'
                                        : 'bg-rapanel-navy-100 dark:bg-rapanel-navy-800 text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 border-transparent'"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border transition">
                                    <EyeIcon v-if="site.is_active" class="w-3.5 h-3.5" />
                                    <EyeSlashIcon v-else class="w-3.5 h-3.5" />
                                    {{ site.is_active ? __('Active') : __('Inactive') }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEdit(site)"
                                        class="p-1.5 rounded text-rapanel-blue hover:bg-rapanel-blue/10 transition">
                                        <PencilIcon class="w-4 h-4" />
                                    </button>
                                    <button @click="deleteSite(site)"
                                        class="p-1.5 rounded text-rapanel-danger hover:bg-rapanel-danger/10 transition">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!sites.length">
                            <td colspan="6" class="px-4 py-12 text-center text-rapanel-text-light/50 dark:text-rapanel-text-dark/50">
                                {{ __('No voting sites yet.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="bg-white dark:bg-rapanel-navy-900 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-rapanel-navy-100 dark:border-white/10 flex items-center justify-between">
                    <h2 class="font-display font-bold text-rapanel-navy-900 dark:text-white uppercase tracking-wide">
                        {{ editId ? __('Edit Vote Site') : __('Add Vote Site') }}
                    </h2>
                    <button @click="showModal = false" class="text-rapanel-text-light/50 hover:text-rapanel-text-light dark:hover:text-white transition text-xl leading-none">×</button>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark mb-1 uppercase tracking-wider">{{ __('Name') }}</label>
                        <input v-model="form.name" type="text" required class="w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                        <p v-if="errors.name" class="text-rapanel-danger text-xs mt-1">{{ errors.name }}</p>
                    </div>

                    <!-- Icon URL -->
                    <div>
                        <label class="block text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark mb-1 uppercase tracking-wider">{{ __('Icon URL') }} <span class="opacity-50">({{ __('optional') }})</span></label>
                        <input v-model="form.icon_url" type="url" class="w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                    </div>

                    <!-- Vote URL -->
                    <div>
                        <label class="block text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark mb-1 uppercase tracking-wider">{{ __('Vote URL') }}</label>
                        <input v-model="form.vote_url" type="text" required placeholder="https://topg.org/vote/server-id/?id={user_id}" class="w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue font-mono" />
                        <p class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mt-1">{{ __('Use {user_id} as placeholder for the player\'s panel ID.') }}</p>
                        <p v-if="errors.vote_url" class="text-rapanel-danger text-xs mt-1">{{ errors.vote_url }}</p>
                    </div>

                    <!-- Callback type -->
                    <div>
                        <label class="block text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark mb-1 uppercase tracking-wider">{{ __('Callback Type') }}</label>
                        <select v-model="form.callback_type" class="w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue">
                            <option v-for="t in callbackTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                        <p class="text-xs font-mono text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mt-1">{{ callbackHint }}</p>
                    </div>

                    <!-- Callback URL (solo para tipo callback) -->
                    <div v-if="needsCallbackFields && editId" class="rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 mb-1">{{ __('Your Callback URL') }}</p>
                        <code class="text-xs text-rapanel-blue break-all select-all">{{ route('vote.callback', editId) }}</code>
                        <p class="text-xs text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 mt-1">{{ __('Enter this URL in the voting site\'s postback/webhook settings.') }}</p>
                    </div>

                    <!-- Secret + IP (solo para tipos con callback real) -->
                    <div v-if="needsCallbackFields" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark mb-1 uppercase tracking-wider">{{ __('Secret / Key') }}</label>
                            <input v-model="form.callback_secret" type="text" class="w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue font-mono" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark mb-1 uppercase tracking-wider">{{ __('Allowed IP') }}</label>
                            <input v-model="form.callback_ip" type="text" placeholder="{{ __('auto') }}" class="w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue font-mono" />
                        </div>
                    </div>

                    <!-- Points + Cooldown + Order -->
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark mb-1 uppercase tracking-wider">{{ __('Points') }}</label>
                            <input v-model.number="form.points_per_vote" type="number" min="1" max="9999" class="w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark mb-1 uppercase tracking-wider">{{ __('Cooldown (h)') }}</label>
                            <input v-model.number="form.cooldown_hours" type="number" min="1" max="168" class="w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light dark:text-rapanel-text-dark mb-1 uppercase tracking-wider">{{ __('Order') }}</label>
                            <input v-model.number="form.sort_order" type="number" min="0" class="w-full rounded-md border border-rapanel-navy-200 dark:border-white/10 bg-white dark:bg-rapanel-navy-800 text-rapanel-navy-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue" />
                        </div>
                    </div>

                    <!-- Active -->
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="rounded border-rapanel-navy-300 dark:border-white/20" />
                        <span class="text-sm text-rapanel-navy-900 dark:text-white">{{ __('Active') }}</span>
                    </label>

                    <div class="flex justify-end gap-3 pt-2 border-t border-rapanel-navy-100 dark:border-white/10">
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 text-sm text-rapanel-text-light dark:text-rapanel-text-dark hover:text-rapanel-navy-900 dark:hover:text-white transition">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" :disabled="processing"
                            class="px-4 py-2 bg-rapanel-blue text-white rounded-lg text-sm font-semibold hover:opacity-90 transition disabled:opacity-50">
                            {{ processing ? __('Saving...') : __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
