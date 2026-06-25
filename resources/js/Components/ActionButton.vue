<script setup>
import { computed } from 'vue';

/**
 * Botón canónico del proyecto. Una sola fuente para TODOS los botones.
 *   - variant: color (blue · gold · purple · navy · success · danger · reset-look)
 *   - fill:    estilo  → 'soft' (translúcido, por defecto) · 'solid' (relleno, acciones
 *              primarias) · 'ghost' (superficie neutra con borde, secundario/cancelar)
 *   - size:    lg · md (def) · sm · icon
 * Todos los colores usan tokens rapanel-* → recolorea el panel de Apariencia.
 */
const props = defineProps({
    variant:  { type: String,  default: 'blue' },
    fill:     { type: String,  default: 'soft' },
    size:     { type: String,  default: 'md' },
    disabled: { type: Boolean, default: false },
    title:    { type: String,  default: undefined },
    type:     { type: String,  default: 'button' },
});

// soft = translúcido (acciones de fila / secundarias)
const soft = {
    blue:         'bg-rapanel-blue/30 dark:bg-rapanel-blue/10 text-rapanel-blue border-rapanel-blue/40 dark:border-rapanel-blue/20 hover:bg-rapanel-blue hover:text-white',
    gold:         'bg-rapanel-gold/30 dark:bg-rapanel-gold/10 text-rapanel-gold border-rapanel-gold/40 dark:border-rapanel-gold/20 hover:bg-rapanel-gold hover:text-white',
    purple:       'bg-rapanel-purple/30 dark:bg-rapanel-purple/10 text-rapanel-purple border-rapanel-purple/40 dark:border-rapanel-purple/20 hover:bg-rapanel-purple hover:text-white',
    danger:       'bg-rapanel-danger/30 dark:bg-rapanel-danger/10 text-rapanel-danger border-rapanel-danger/40 dark:border-rapanel-danger/20 hover:bg-rapanel-danger hover:text-white',
    success:      'bg-rapanel-success/30 dark:bg-rapanel-success/10 text-rapanel-success border-rapanel-success/40 dark:border-rapanel-success/20 hover:bg-rapanel-success hover:text-white',
    navy:         'bg-rapanel-navy-100 dark:bg-rapanel-navy-700 text-rapanel-navy-900 dark:text-rapanel-text-dark border-rapanel-navy-100 dark:border-rapanel-navy-600 hover:bg-rapanel-navy-900 hover:text-white dark:hover:bg-rapanel-navy-600',
    'reset-look': 'bg-rapanel-danger/10 dark:bg-rapanel-gold/10 text-rapanel-danger dark:text-rapanel-gold border-rapanel-danger/40 dark:border-rapanel-gold/20 hover:bg-rapanel-danger hover:text-white dark:hover:bg-rapanel-gold dark:hover:text-rapanel-navy-900',
};
// solid = relleno (acciones primarias: Nuevo · Guardar · Crear)
const solid = {
    blue:    'bg-rapanel-blue text-white border-rapanel-blue hover:bg-rapanel-blue/90',
    gold:    'bg-rapanel-gold text-rapanel-navy-900 border-rapanel-gold hover:bg-rapanel-gold/90',
    purple:  'bg-rapanel-purple text-white border-rapanel-purple hover:bg-rapanel-purple/90',
    danger:  'bg-rapanel-danger text-white border-rapanel-danger hover:bg-rapanel-danger/90',
    success: 'bg-rapanel-success text-white border-rapanel-success hover:bg-rapanel-success/90',
    navy:    'bg-rapanel-navy-900 dark:bg-rapanel-navy-700 text-white border-rapanel-navy-900 dark:border-rapanel-navy-600 hover:bg-rapanel-navy-800 dark:hover:bg-rapanel-navy-600',
    'reset-look': 'bg-rapanel-danger text-white border-rapanel-danger hover:bg-rapanel-danger/90',
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
    <button
        :type="type"
        :disabled="disabled"
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
    </button>
</template>
