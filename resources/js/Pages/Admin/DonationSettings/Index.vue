<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { InformationCircleIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';

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
    paypal_enabled:     props.settings.paypal_enabled,
    stripe_enabled:     props.settings.stripe_enabled,
    mp_enabled:         props.settings.mp_enabled,
    donation_cash_from: props.settings.donation_cash_from,
    donation_cash_to:   props.settings.donation_cash_to,
    monthly_cost:       props.settings.monthly_cost,
    monthly_goal:       props.settings.monthly_goal,
});

const submit = () => {
    form.put(route('admin.donation-settings.update'));
};
</script>

<template>
    <AdminLayout>
        <div class="max-w-2xl space-y-6">
            <PageHeader :title="__('Donation Settings')" />

            <form @submit.prevent="submit" class="space-y-5">

                <!-- PayPal -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-rapanel-navy-700 dark:text-white">PayPal</h2>
                            <span v-if="settings.paypal_configured"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-rapanel-success">
                                <CheckCircleIcon class="w-3.5 h-3.5" />
                                {{ __('Configured') }}
                            </span>
                            <span v-else
                                class="inline-flex items-center gap-1 text-xs font-semibold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">
                                <XCircleIcon class="w-3.5 h-3.5" />
                                {{ __('Not configured') }}
                            </span>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <button type="button" @click="form.paypal_enabled = !form.paypal_enabled"
                                :class="['relative w-10 h-5 rounded-full transition-colors duration-200', form.paypal_enabled ? 'bg-rapanel-blue' : 'bg-rapanel-navy-100 dark:bg-white/20']">
                                <span :class="['absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200', form.paypal_enabled ? 'translate-x-5' : 'translate-x-0']" />
                            </button>
                            <span class="text-sm text-rapanel-text-light dark:text-white">{{ __('Enabled') }}</span>
                        </label>
                    </div>
                    <!-- Nota .env -->
                    <div class="flex gap-2 p-3 rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-800 text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                        <InformationCircleIcon class="w-4 h-4 flex-shrink-0 mt-0.5 text-rapanel-gold" />
                        <span>{{ __('API keys must be set in .env:') }} <code class="font-mono text-rapanel-blue">DONATION_PAYPAL_CLIENT_ID</code>, <code class="font-mono text-rapanel-blue">DONATION_PAYPAL_SECRET</code>, <code class="font-mono text-rapanel-blue">DONATION_PAYPAL_SANDBOX</code></span>
                    </div>
                </div>

                <!-- Stripe -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-rapanel-navy-700 dark:text-white">Stripe</h2>
                            <span v-if="settings.stripe_configured"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-rapanel-success">
                                <CheckCircleIcon class="w-3.5 h-3.5" />
                                {{ __('Configured') }}
                            </span>
                            <span v-else
                                class="inline-flex items-center gap-1 text-xs font-semibold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">
                                <XCircleIcon class="w-3.5 h-3.5" />
                                {{ __('Not configured') }}
                            </span>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <button type="button" @click="form.stripe_enabled = !form.stripe_enabled"
                                :class="['relative w-10 h-5 rounded-full transition-colors duration-200', form.stripe_enabled ? 'bg-rapanel-blue' : 'bg-rapanel-navy-100 dark:bg-white/20']">
                                <span :class="['absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200', form.stripe_enabled ? 'translate-x-5' : 'translate-x-0']" />
                            </button>
                            <span class="text-sm text-rapanel-text-light dark:text-white">{{ __('Enabled') }}</span>
                        </label>
                    </div>
                    <!-- Nota .env -->
                    <div class="flex gap-2 p-3 rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-800 text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                        <InformationCircleIcon class="w-4 h-4 flex-shrink-0 mt-0.5 text-rapanel-gold" />
                        <span>{{ __('API keys must be set in .env:') }} <code class="font-mono text-rapanel-blue">DONATION_STRIPE_PUBLIC_KEY</code>, <code class="font-mono text-rapanel-blue">DONATION_STRIPE_SECRET_KEY</code>, <code class="font-mono text-rapanel-blue">DONATION_STRIPE_WEBHOOK_SECRET</code></span>
                    </div>
                    <!-- Webhook URL -->
                    <div class="rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 mb-1">{{ __('Webhook URL') }}</p>
                        <code class="text-xs text-rapanel-blue break-all select-all">{{ settings.stripe_webhook_url }}</code>
                    </div>
                </div>

                <!-- Mercado Pago -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-rapanel-navy-700 dark:text-white">Mercado Pago</h2>
                            <span v-if="settings.mp_configured"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-rapanel-success">
                                <CheckCircleIcon class="w-3.5 h-3.5" />
                                {{ __('Configured') }}
                            </span>
                            <span v-else
                                class="inline-flex items-center gap-1 text-xs font-semibold text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">
                                <XCircleIcon class="w-3.5 h-3.5" />
                                {{ __('Not configured') }}
                            </span>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <button type="button" @click="form.mp_enabled = !form.mp_enabled"
                                :class="['relative w-10 h-5 rounded-full transition-colors duration-200', form.mp_enabled ? 'bg-rapanel-blue' : 'bg-rapanel-navy-100 dark:bg-white/20']">
                                <span :class="['absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200', form.mp_enabled ? 'translate-x-5' : 'translate-x-0']" />
                            </button>
                            <span class="text-sm text-rapanel-text-light dark:text-white">{{ __('Enabled') }}</span>
                        </label>
                    </div>
                    <div class="flex gap-2 p-3 rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-800 text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                        <InformationCircleIcon class="w-4 h-4 flex-shrink-0 mt-0.5 text-rapanel-gold" />
                        <span>{{ __('API keys must be set in .env:') }} <code class="font-mono text-rapanel-blue">DONATION_MP_ACCESS_TOKEN</code>, <code class="font-mono text-rapanel-blue">DONATION_MP_PUBLIC_KEY</code></span>
                    </div>
                    <div class="rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-800 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 mb-1">{{ __('Webhook URL') }}</p>
                        <code class="text-xs text-rapanel-blue break-all select-all">{{ settings.mp_webhook_url }}</code>
                    </div>
                </div>

                <!-- Conversion Rate -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm space-y-4">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-rapanel-navy-700 dark:text-white">{{ __('Donation Points Conversion Rate') }}</h2>
                    <p class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">{{ __('How many Donation Points are needed and how many Cash Points are awarded per conversion unit.') }}</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Donation Points (cost)') }}</label>
                            <input v-model.number="form.donation_cash_from" type="number" min="1"
                                class="w-full px-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                            <p v-if="form.errors.donation_cash_from" class="text-rapanel-danger text-xs mt-1">{{ form.errors.donation_cash_from }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Cash Points (reward)') }}</label>
                            <input v-model.number="form.donation_cash_to" type="number" min="1"
                                class="w-full px-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                            <p v-if="form.errors.donation_cash_to" class="text-rapanel-danger text-xs mt-1">{{ form.errors.donation_cash_to }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-rapanel-text-light/60 dark:text-rapanel-text-dark/60">
                        <span>{{ __('Exchange rate:') }}</span>
                        <span class="font-semibold text-rapanel-gold">{{ form.donation_cash_from }} {{ __('Donation Points') }}</span>
                        <span>→</span>
                        <span class="font-semibold text-rapanel-success">{{ form.donation_cash_to }} {{ __('Cash Points') }}</span>
                    </div>
                </div>

                <!-- Server Costs & Financial Goals -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm space-y-4">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-rapanel-navy-700 dark:text-white">{{ __('Server Costs & Financial Goals') }}</h2>
                        <p class="text-xs text-rapanel-text-light/60 dark:text-rapanel-text-dark/60 mt-1">{{ __('Leave at 0 to disable financial tracking') }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Monthly Server Cost (USD)') }}</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-sm text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 select-none">$</span>
                                <input v-model.number="form.monthly_cost" type="number" min="0" step="0.01"
                                    class="w-full pl-6 pr-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                            </div>
                            <p v-if="form.errors.monthly_cost" class="text-rapanel-danger text-xs mt-1">{{ form.errors.monthly_cost }}</p>
                            <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mt-1">VPS, hosting, dominio, etc.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Monthly Donation Goal (USD)') }}</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-sm text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 select-none">$</span>
                                <input v-model.number="form.monthly_goal" type="number" min="0" step="0.01"
                                    class="w-full pl-6 pr-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                            </div>
                            <p v-if="form.errors.monthly_goal" class="text-rapanel-danger text-xs mt-1">{{ form.errors.monthly_goal }}</p>
                            <p class="text-xs text-rapanel-text-light/40 dark:text-rapanel-text-dark/40 mt-1">Puede ser igual o mayor al costo</p>
                        </div>
                    </div>
                    <div v-if="form.monthly_cost > 0"
                        class="flex gap-2 p-3 rounded-lg bg-rapanel-blue/5 dark:bg-rapanel-blue/10 border border-rapanel-blue/20">
                        <InformationCircleIcon class="w-4 h-4 flex-shrink-0 mt-0.5 text-rapanel-blue" />
                        <span class="text-xs text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">
                            {{ __('Financial tracking will be enabled in the analytics dashboard.') }}
                            Meta efectiva: <strong class="text-rapanel-blue">${{ (form.monthly_goal > 0 ? form.monthly_goal : form.monthly_cost).toFixed(2) }}/mes</strong>
                        </span>
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
