<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

/**
 * Botón canónico del proyecto. Una sola fuente para TODOS los botones.
 *   - variant: color (blue · gold · purple · navy · success · danger · reset-look)
 *   - fill:    estilo  → 'soft' (translúcido, por defecto) · 'solid' (relleno, acciones
 *              primarias) · 'ghost' (superficie neutra con borde, secundario/cancelar)
 *   - size:    lg · md (def) · sm · icon
 *   - href:    si se pasa, renderiza como navegación (Inertia <Link>, o <a> si external).
 * Los colores de botón usan tokens rapanel-btn-* → recolorea el panel de Apariencia.
 */
const props = defineProps({
    variant:  { type: String,  default: 'blue' },
    fill:     { type: String,  default: 'soft' },
    size:     { type: String,  default: 'md' },
    disabled: { type: Boolean, default: false },
    title:    { type: String,  default: undefined },
    type:     { type: String,  default: 'button' },
    href:     { type: String,  default: null },
    external: { type: Boolean, default: false },
});

const tag = computed(() => props.href ? (props.external ? 'a' : Link) : 'button');

// soft = translúcido (acciones de fila / secundarias). Usa tokens rapanel-btn-* (themeables).
const soft = {
    blue:         'bg-rapanel-btn-blue/30 dark:bg-rapanel-btn-blue/10 text-rapanel-btn-blue border-rapanel-btn-blue/40 dark:border-rapanel-btn-blue/20 hover:bg-rapanel-btn-blue hover:text-white',
    gold:         'bg-rapanel-btn-gold/30 dark:bg-rapanel-btn-gold/10 text-rapanel-btn-gold border-rapanel-btn-gold/40 dark:border-rapanel-btn-gold/20 hover:bg-rapanel-btn-gold hover:text-white',
    purple:       'bg-rapanel-btn-purple/30 dark:bg-rapanel-btn-purple/10 text-rapanel-btn-purple border-rapanel-btn-purple/40 dark:border-rapanel-btn-purple/20 hover:bg-rapanel-btn-purple hover:text-white',
    danger:       'bg-rapanel-btn-danger/30 dark:bg-rapanel-btn-danger/10 text-rapanel-btn-danger border-rapanel-btn-danger/40 dark:border-rapanel-btn-danger/20 hover:bg-rapanel-btn-danger hover:text-white',
    success:      'bg-rapanel-btn-success/30 dark:bg-rapanel-btn-success/10 text-rapanel-btn-success border-rapanel-btn-success/40 dark:border-rapanel-btn-success/20 hover:bg-rapanel-btn-success hover:text-white',
    navy:         'bg-rapanel-navy-100 dark:bg-rapanel-navy-700 text-rapanel-navy-900 dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-rapanel-navy-600 hover:bg-rapanel-btn-navy hover:text-white dark:hover:bg-rapanel-btn-navy',
    'reset-look': 'bg-rapanel-btn-danger/10 dark:bg-rapanel-btn-gold/10 text-rapanel-btn-danger dark:text-rapanel-btn-gold border-rapanel-btn-danger/40 dark:border-rapanel-btn-gold/20 hover:bg-rapanel-btn-danger hover:text-white dark:hover:bg-rapanel-btn-gold dark:hover:text-rapanel-navy-900',
};
// solid = relleno (acciones primarias: Nuevo · Guardar · Crear)
const solid = {
    blue:    'bg-rapanel-btn-blue text-white border-rapanel-btn-blue hover:bg-rapanel-btn-blue/90',
    gold:    'bg-rapanel-btn-gold text-rapanel-navy-900 border-rapanel-btn-gold hover:bg-rapanel-btn-gold/90',
    purple:  'bg-rapanel-btn-purple text-white border-rapanel-btn-purple hover:bg-rapanel-btn-purple/90',
    danger:  'bg-rapanel-btn-danger text-white border-rapanel-btn-danger hover:bg-rapanel-btn-danger/90',
    success: 'bg-rapanel-btn-success text-white border-rapanel-btn-success hover:bg-rapanel-btn-success/90',
    navy:    'bg-rapanel-btn-navy text-white border-rapanel-btn-navy hover:bg-rapanel-btn-navy/90',
    'reset-look': 'bg-rapanel-btn-danger text-white border-rapanel-btn-danger hover:bg-rapanel-btn-danger/90',
};
// ghost = superficie neutra con borde (secundario / cancelar) — neutral, el variant no aplica
const ghostClass = 'bg-white dark:bg-rapanel-navy-800 text-rapanel-text-light dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-white/10 hover:bg-rapanel-navy-50 dark:hover:bg-white/5';

const sizeClasses = {
    lg:   'px-4 py-2 text-sm',
    md:   'px-3 py-1.5 text-xs',
    sm:   'px-2.5 py-1 text-xs',
    icon: 'p-1.5 text-xs',
};

const colorClasses = computed(() => {
    if (props.fill === 'ghost') return ghostClass;
    const set = props.fill === 'solid' ? solid : soft;
    return set[props.variant] ?? set.blue;
});
</script>

<template>
    <component
        :is="tag"
        :href="href || undefined"
        :type="href ? undefined : type"
        :disabled="!href && disabled ? true : undefined"
        :target="external ? '_blank' : undefined"
        :rel="external ? 'noopener' : undefined"
        :title="title"
        :aria-label="title"
        class="font-display inline-flex items-center justify-center gap-1.5 rounded-lg font-bold border transition-all"
        :class="[
            colorClasses,
            sizeClasses[size] ?? sizeClasses.md,
            disabled ? 'opacity-30 cursor-not-allowed pointer-events-none' : '',
        ]"
    >
        <slot />
    </component>
</template>
