import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

export const SHORTCUTS = [
    { seq: ['G', 'D'], label: 'Dashboard',        route: 'admin.dashboard',                group: 'Navigate' },
    { seq: ['G', 'U'], label: 'Master Accounts',  route: 'admin.users.index',              group: 'Navigate' },
    { seq: ['G', 'A'], label: 'Game Accounts',    route: 'admin.game-accounts.index',      group: 'Navigate' },
    { seq: ['G', 'C'], label: 'Characters',       route: 'admin.characters.index',         group: 'Navigate' },
    { seq: ['G', 'N'], label: 'News',             route: 'admin.news.index',               group: 'Navigate' },
    { seq: ['G', 'W'], label: 'Wiki',             route: 'admin.wiki.articles.index',      group: 'Navigate' },
    { seq: ['G', 'L'], label: 'Action Logs',      route: 'admin.logs.index',               group: 'Navigate' },
    { seq: ['G', 'T'], label: 'Console',          route: 'admin.console.index',            group: 'Navigate' },
    { seq: ['G', 'H'], label: 'Health Check',     route: 'admin.health.index',             group: 'Navigate' },
    { seq: ['G', 'S'], label: 'Settings',         route: 'admin.settings.index',           group: 'Navigate' },
    { seq: ['?'],      label: 'Show shortcuts',   action: 'help',                          group: 'General' },
];

const isInputFocused = () => {
    const el = document.activeElement;
    return el && (
        el.tagName === 'INPUT' ||
        el.tagName === 'TEXTAREA' ||
        el.tagName === 'SELECT' ||
        el.isContentEditable
    );
};

export function useAdminShortcuts() {
    const showHelp    = ref(false);
    const pendingKey  = ref(null); // first key of a 2-key sequence (e.g. 'G')
    let prefixTimer   = null;

    const safeVisit = (routeName) => {
        try { router.visit(route(routeName)); } catch { /* route not registered */ }
    };

    const resetPrefix = () => {
        pendingKey.value = null;
        clearTimeout(prefixTimer);
    };

    const handleKeydown = (e) => {
        if (isInputFocused()) return;
        if (e.metaKey || e.ctrlKey || e.altKey) return;

        // '?' → toggle help
        if (e.key === '?') {
            e.preventDefault();
            showHelp.value = !showHelp.value;
            return;
        }

        // Escape → close help, cancel pending prefix
        if (e.key === 'Escape') {
            showHelp.value = false;
            resetPrefix();
            return;
        }

        const key = e.key.toUpperCase();

        // We're waiting for the second key of a sequence
        if (pendingKey.value) {
            const match = SHORTCUTS.find(
                s => s.seq.length === 2 && s.seq[0] === pendingKey.value && s.seq[1] === key
            );
            resetPrefix();
            if (match) {
                e.preventDefault();
                safeVisit(match.route);
            }
            return;
        }

        // Check if this key starts a 2-key sequence
        const isPrefix = SHORTCUTS.some(s => s.seq.length === 2 && s.seq[0] === key);
        if (isPrefix) {
            pendingKey.value = key;
            prefixTimer = setTimeout(resetPrefix, 1500);
        }
    };

    onMounted(() => document.addEventListener('keydown', handleKeydown));
    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeydown);
        clearTimeout(prefixTimer);
    });

    return { showHelp, pendingKey };
}
