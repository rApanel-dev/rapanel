<script setup>
import { computed, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ArrowLeftIcon, PhotoIcon, StarIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const __ = (key, rep = {}) => {
    let t = page.props.translations?.[key] || key;
    Object.entries(rep).forEach(([k, v]) => { t = t.replace(`:${k}`, v); });
    return t;
};

const imagePreview = ref(null);

const COLORS = [
    { key: 'blue',    hex: '#4A90E2', label: 'Blue'    },
    { key: 'gold',    hex: '#F1C40F', label: 'Gold'    },
    { key: 'success', hex: '#2ECC71', label: 'Green'   },
    { key: 'purple',  hex: '#a855f7', label: 'Purple'  },
    { key: 'danger',  hex: '#E74C3C', label: 'Red'     },
    { key: 'navy',    hex: '#334155', label: 'Navy'    },
];

const form = useForm({
    title:         '',
    description:   '',
    image:         null,
    price_usd:     '',
    cashpoints:    '',
    bonus_percent: 0,
    is_active:     true,
    is_featured:   false,
    sort_order:    0,
    border_color:  'blue',
});

const bonusAmount = computed(() => {
    const cp = parseInt(form.cashpoints) || 0;
    return Math.round(cp * (parseInt(form.bonus_percent) || 0) / 100);
});

const totalCp = computed(() => (parseInt(form.cashpoints) || 0) + bonusAmount.value);

const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    imagePreview.value = URL.createObjectURL(file);
};

const submit = () => {
    form.post(route('admin.donation-packages.store'), { forceFormData: true });
};
</script>

<template>
    <AdminLayout>
        <div class="max-w-2xl space-y-6">

            <!-- Header -->
            <div class="flex items-center gap-4 pb-5 border-b border-rapanel-navy-100 dark:border-white/[0.055]">
                <Link :href="route('admin.donation-packages.index')"
                      class="p-2 rounded-lg hover:bg-rapanel-navy-100 dark:hover:bg-white/[0.07] text-rapanel-text-light/55 dark:text-white/45 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-display font-bold tracking-wide text-rapanel-text-light dark:text-white">{{ __('Add Package') }}</h1>
                    <nav class="flex items-center gap-1 text-xs mt-0.5">
                        <Link :href="route('admin.donation-packages.index')" class="text-rapanel-text-light/45 dark:text-white/35 hover:text-rapanel-blue transition-colors">{{ __('Donation Packages') }}</Link>
                        <span class="text-rapanel-text-light/25 dark:text-white/20">›</span>
                        <span class="text-rapanel-text-light/60 dark:text-white/50">{{ __('New') }}</span>
                    </nav>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- Image + Title -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Image upload -->
                    <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                        <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">
                            {{ __('Package Image') }} <span class="normal-case font-normal opacity-70">({{ __('Optional') }})</span>
                        </label>
                        <label class="relative flex flex-col items-center justify-center h-36 rounded-lg border-2 border-dashed border-rapanel-navy-100 dark:border-white/20 hover:border-rapanel-blue/50 transition cursor-pointer overflow-hidden">
                            <img v-if="imagePreview" :src="imagePreview" class="absolute inset-0 w-full h-full object-cover" />
                            <div v-else class="flex flex-col items-center gap-1 text-rapanel-text-light/40 dark:text-white/30">
                                <PhotoIcon class="w-8 h-8" />
                                <span class="text-xs">{{ __('Upload PNG, JPG, GIF up to 2MB') }}</span>
                            </div>
                            <input type="file" class="sr-only" accept="image/*" @change="onImageChange" />
                        </label>
                        <p v-if="form.errors.image" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.image }}</p>
                    </div>

                    <!-- Title + Description -->
                    <div class="space-y-4">
                        <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                            <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-2">{{ __('Title') }}</label>
                            <input v-model="form.title" type="text" required :placeholder="__('e.g. Starter Pack')"
                                class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                            <p v-if="form.errors.title" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.title }}</p>
                        </div>
                        <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                            <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-2">{{ __('Description') }} <span class="normal-case font-normal opacity-70">({{ __('Optional') }})</span></label>
                            <textarea v-model="form.description" rows="3" :placeholder="__('Short description shown on the card...')"
                                class="w-full rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50 resize-none" />
                            <p v-if="form.errors.description" class="mt-1.5 text-xs text-rapanel-danger">{{ form.errors.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Price + Cashpoints + Bonus -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm space-y-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40">{{ __('Pricing') }}</p>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Price (USD)') }}</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-rapanel-gold font-bold text-sm">$</span>
                                <input v-model="form.price_usd" type="number" step="0.01" min="0.01" required
                                    class="w-full pl-7 pr-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                            </div>
                            <p v-if="form.errors.price_usd" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.price_usd }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Base CP') }}</label>
                            <input v-model.number="form.cashpoints" type="number" min="1" required
                                class="w-full px-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                            <p v-if="form.errors.cashpoints" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.cashpoints }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Bonus %') }}</label>
                            <div class="relative">
                                <input v-model.number="form.bonus_percent" type="number" min="0" max="100"
                                    class="w-full pr-7 pl-3 py-2.5 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-rapanel-text-light/50 dark:text-rapanel-text-dark/50 text-sm">%</span>
                            </div>
                            <p v-if="form.errors.bonus_percent" class="mt-1 text-xs text-rapanel-danger">{{ form.errors.bonus_percent }}</p>
                        </div>
                    </div>

                    <!-- Preview CP breakdown -->
                    <div v-if="form.cashpoints" class="flex items-center gap-2 p-3 rounded-lg bg-rapanel-navy-50 dark:bg-rapanel-navy-800 text-sm">
                        <span class="text-rapanel-text-light/70 dark:text-rapanel-text-dark/70">{{ __('Player receives:') }}</span>
                        <span class="font-mono text-rapanel-navy-700 dark:text-white">{{ (parseInt(form.cashpoints)||0).toLocaleString() }} CP</span>
                        <template v-if="bonusAmount > 0">
                            <span class="text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">+</span>
                            <span class="font-mono text-rapanel-success">{{ bonusAmount.toLocaleString() }} {{ __('bonus') }}</span>
                            <span class="text-rapanel-text-light/40 dark:text-rapanel-text-dark/40">=</span>
                            <span class="font-mono font-bold text-rapanel-navy-900 dark:text-white">{{ totalCp.toLocaleString() }} CP {{ __('total') }}</span>
                        </template>
                    </div>
                </div>

                <!-- Border color picker -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-rapanel-text-light/50 dark:text-white/40 mb-3">{{ __('Card border color') }}</label>
                    <div class="flex flex-wrap gap-3">
                        <button v-for="c in COLORS" :key="c.key" type="button"
                            @click="form.border_color = c.key"
                            :title="c.label"
                            :class="['w-9 h-9 rounded-full border-4 transition-transform duration-150 hover:scale-110',
                                form.border_color === c.key ? 'border-white dark:border-white scale-110 shadow-lg' : 'border-transparent opacity-70 hover:opacity-100']"
                            :style="`background: ${c.hex}; box-shadow: ${form.border_color === c.key ? '0 0 0 2px ' + c.hex : 'none'}`" />
                    </div>
                </div>

                <!-- Sort order + Active + Featured -->
                <div class="bg-white dark:bg-rapanel-surface rounded-xl border border-rapanel-navy-100 dark:border-white/10 p-5 shadow-sm flex flex-wrap items-center gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-rapanel-text-light/70 dark:text-rapanel-text-dark mb-1.5">{{ __('Sort Order') }}</label>
                        <input v-model.number="form.sort_order" type="number" min="0" class="w-24 px-3 py-2 rounded-lg bg-rapanel-navy-50 dark:bg-white/5 border border-rapanel-navy-100 dark:border-white/10 text-rapanel-text-light dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-rapanel-blue/50" />
                    </div>
                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <button type="button" @click="form.is_active = !form.is_active"
                            :class="['relative w-10 h-5 rounded-full transition-colors duration-200 focus:outline-none', form.is_active ? 'bg-rapanel-blue' : 'bg-rapanel-navy-100 dark:bg-white/20']">
                            <span :class="['absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200', form.is_active ? 'translate-x-5' : 'translate-x-0']" />
                        </button>
                        <span class="text-sm font-medium text-rapanel-text-light dark:text-white">{{ __('Active') }}</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <button type="button" @click="form.is_featured = !form.is_featured"
                            :class="['relative w-10 h-5 rounded-full transition-colors duration-200 focus:outline-none', form.is_featured ? 'bg-rapanel-gold' : 'bg-rapanel-navy-100 dark:bg-white/20']">
                            <span :class="['absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200', form.is_featured ? 'translate-x-5' : 'translate-x-0']" />
                        </button>
                        <span class="flex items-center gap-1 text-sm font-medium text-rapanel-text-light dark:text-white">
                            <StarIcon class="w-4 h-4 text-rapanel-gold" />
                            {{ __('Most Popular') }}
                        </span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-rapanel-navy-100 dark:border-white/[0.055]">
                    <Link :href="route('admin.donation-packages.index')"
                        class="px-4 py-2 rounded-lg text-sm font-medium text-rapanel-text-light/70 dark:text-white/60 hover:bg-rapanel-navy-100 dark:hover:bg-white/10 transition">
                        {{ __('Cancel') }}
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-5 py-2 rounded-lg bg-rapanel-blue text-white text-sm font-semibold hover:opacity-90 transition shadow disabled:opacity-60">
                        {{ form.processing ? __('Creating…') : __('Create Package') }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
