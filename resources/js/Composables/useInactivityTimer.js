import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Cierra la sesión automáticamente tras N minutos de inactividad.
 * Muestra un aviso 2 minutos antes de ejecutar el logout.
 *
 * @param {number} timeoutMinutes  - Minutos de inactividad (0 = desactivado)
 * @param {boolean} isAuthenticated - Solo activa el timer si hay sesión activa
 */
export function useInactivityTimer(timeoutMinutes, isAuthenticated) {
    const showWarning  = ref(false);
    const countdown    = ref(120); // segundos que muestra el aviso antes del logout

    let idleTimer      = null;
    let countdownTimer = null;

    const WARNING_SECONDS = 120; // aviso 2 minutos antes

    const clearTimers = () => {
        clearTimeout(idleTimer);
        clearInterval(countdownTimer);
    };

    const logout = () => {
        clearTimers();
        router.post(route('logout'));
    };

    const startCountdown = () => {
        showWarning.value = true;
        countdown.value   = WARNING_SECONDS;

        countdownTimer = setInterval(() => {
            countdown.value--;
            if (countdown.value <= 0) {
                clearInterval(countdownTimer);
                logout();
            }
        }, 1000);
    };

    const resetTimer = () => {
        if (showWarning.value) return; // no interrumpir el aviso ya activo
        clearTimers();
        idleTimer = setTimeout(startCountdown, (timeoutMinutes * 60 - WARNING_SECONDS) * 1000);
    };

    const stayActive = () => {
        showWarning.value = false;
        clearTimers();
        resetTimer();
    };

    const EVENTS = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'];

    onMounted(() => {
        if (!isAuthenticated || timeoutMinutes <= 0) return;

        EVENTS.forEach(e => window.addEventListener(e, resetTimer, { passive: true }));
        resetTimer();
    });

    onUnmounted(() => {
        clearTimers();
        EVENTS.forEach(e => window.removeEventListener(e, resetTimer));
    });

    return { showWarning, countdown, stayActive };
}
