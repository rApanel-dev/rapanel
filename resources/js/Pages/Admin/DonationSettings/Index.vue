<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { InformationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: { type: Object, required: true },
});

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const form = useForm({
    paypal_enabled:        props.settings.paypal_enabled,
    paypal_client_id:      '',
    paypal_secret:         '',
    paypal_sandbox:        props.settings.paypal_sandbox,
    stripe_enabled:        props.settings.stripe_enabled,
    stripe_public_key:     props.settings.stripe_public_key || '',
    stripe_secret_key:     '',
    stripe_webhook_secret: '',
});

const submit = () => {
    form.put(route('admin.donation-settings.update'));
};
</script>

<template>
    <AdminLayout>
        <div class="max-w-2xl space-y-6">
            <PageHeader :title="__('Donation Settings')" />

            <!-- Security notice -->
            <div class="flex gap-3 p-4 rounded-xl bg-rapanel-gold/5 border border-rapanel-gold/20">
                <InformationCircleIcon class="w-5 h-5 text-rapanel-gold flex-shrink-0 mt-0.5" />
                <p class="text-sm text-rapanel-text-light/80 dark:text-rapanel-text-dark/80">
                    {{ __('API keys are stored in .env for security.') }}
                    {{ __('Leave a key field empty to keep the current value.') }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- PayPal -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-rapanel-navy-700 dark:text-white">{{ __('PayPal Settings') }}</h2>
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <button type="button" @click="form.paypal_enabled = !form.paypal_enabled"
                                :class="['relative w-10 h-5 rounded-full transition-colors duration-200', form.paypal_enabled ? 'bg-rapanel-blue' : 'bg-rapanel-navy-100 dark:bg-white/20']">
                                <span :class="['absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200', form.paypal_enabled ? 'translate-x-5' : 'translate-x-0']" />
                            </button>
                            <span class="text-sm text-rapanel-text-light dark:text-white">{{ __('Enabled') }}</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-4" :class="{ 'opacity-50 pointer-events-none': !form.paypal_enabled }">
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Client ID') }}</label>
                            <input v-model="form.paypal_client_id" type="text"
                                :placeholder="settings.paypal_client_id ? '••••••••' : __('Enter PayPal Client ID')"
                                class="w-full px-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                            <p v-if="form.errors.paypal_client_id" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.paypal_client_id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Secret Key') }}</label>
                            <input v-model="form.paypal_secret" type="password"
                                :placeholder="settings.paypal_secret ? '••••••••' : __('Enter PayPal Secret')"
                                class="w-full px-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                            <p v-if="form.errors.paypal_secret" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.paypal_secret }}</p>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <button type="button" @click="form.paypal_sandbox = !form.paypal_sandbox"
                                :class="['relative w-10 h-5 rounded-full transition-colors duration-200', form.paypal_sandbox ? 'bg-rapanel-gold' : 'bg-rapanel-navy-100 dark:bg-white/20']">
                                <span :class="['absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200', form.paypal_sandbox ? 'translate-x-5' : 'translate-x-0']" />
                            </button>
                            <span class="text-sm text-rapanel-text-light dark:text-white">{{ __('Sandbox mode') }}</span>
                            <span class="text-xs text-rapanel-gold/70">({{ __('Disable for production') }})</span>
                        </label>
                    </div>
                </div>

                <!-- Stripe -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-rapanel-navy-700 dark:text-white">{{ __('Stripe Settings') }}</h2>
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <button type="button" @click="form.stripe_enabled = !form.stripe_enabled"
                                :class="['relative w-10 h-5 rounded-full transition-colors duration-200', form.stripe_enabled ? 'bg-rapanel-blue' : 'bg-rapanel-navy-100 dark:bg-white/20']">
                                <span :class="['absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200', form.stripe_enabled ? 'translate-x-5' : 'translate-x-0']" />
                            </button>
                            <span class="text-sm text-rapanel-text-light dark:text-white">{{ __('Enabled') }}</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-4" :class="{ 'opacity-50 pointer-events-none': !form.stripe_enabled }">
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Public Key') }}</label>
                            <input v-model="form.stripe_public_key" type="text"
                                :placeholder="settings.stripe_public_key ? settings.stripe_public_key : 'pk_live_...'"
                                class="w-full px-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                            <p v-if="form.errors.stripe_public_key" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.stripe_public_key }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Secret Key') }}</label>
                            <input v-model="form.stripe_secret_key" type="password"
                                :placeholder="settings.stripe_secret_key ? '••••••••' : 'sk_live_...'"
                                class="w-full px-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                            <p v-if="form.errors.stripe_secret_key" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.stripe_secret_key }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Webhook Secret') }}</label>
                            <input v-model="form.stripe_webhook_secret" type="password"
                                :placeholder="settings.stripe_webhook_secret ? '••••••••' : 'whsec_...'"
                                class="w-full px-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm font-mono focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                            <p v-if="form.errors.stripe_webhook_secret" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.stripe_webhook_secret }}</p>
                        </div>
                        <div class="rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 mb-1">{{ __('Webhook URL') }}</p>
                            <code class="text-xs text-rapanel-blue break-all select-all">{{ $page.props.ziggy?.url ?? '' }}/donations/stripe/webhook</code>
                        </div>
                    </div>
                </div>

                <!-- Save -->
                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing"
                        class="px-6 py-2.5 rounded-lg bg-rapanel-blue text-white text-sm font-semibold hover:opacity-90 transition shadow disabled:opacity-60">
                        {{ form.processing ? __('Saving…') : __('Save Settings') }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
