import { ref, onUnmounted } from 'vue';

// ─── ANSI → HTML ─────────────────────────────────────────────────────────────
// Covers all codes defined in rAthena src/common/showmsg.hpp
const COLORS = {
    // CL_LT_* (0;x) — light variants
    '0;30': '#4b5563', '0;31': '#f87171', '0;32': '#86efac', '0;33': '#fde047',
    '0;34': '#93c5fd', '0;35': '#d8b4fe', '0;36': '#67e8f9', '0;37': '#e5e7eb',
    // CL_BT_* / CL_* (1;x) — bold/bright variants
    '1;30': '#9ca3af', '1;31': '#ef4444', '1;32': '#22c55e', '1;33': '#eab308',
    '1;34': '#3b82f6', '1;35': '#a855f7', '1;36': '#06b6d4', '1;37': '#ffffff',
    // CL_PASS — green on green (password hidden), show as green text
    '0;32;42': '#22c55e',
};

export function ansiToHtml(raw) {
    // Strip clear-screen, clear-line y secuencias de cursor del PTY
    let text = raw
        .replace(/\x1b\[[0-9;]*[ABCDEFGHJKSTfhlnprsu]/g, '')
        .replace(/\x1b\[[\x3c-\x3f][0-9;]*[hl]/g, '')
        .replace(/\x1b[^[]/g, '');

    // Escape HTML
    text = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    let openSpan = false;

    text = text.replace(/\x1b\[([0-9;]*)m/g, (_, code) => {
        const close = openSpan ? '</span>' : '';
        openSpan = false;

        // Reset codes: 0, empty, or standalone bold (1) with no color
        if (!code || code === '0' || code === '1') return close;

        // Background-only codes (4x) — ignore, dark terminal makes them unreadable
        if (/^4[0-7]$/.test(code)) return close;

        const color = COLORS[code];
        if (!color) return close;

        openSpan = true;
        return `${close}<span style="color:${color}">`;
    });

    if (openSpan) text += '</span>';
    return text;
}

// ─── Token cache compartido entre todas las instancias ───────────────────────
let _sharedToken   = null;
let _tokenExpiry   = 0;
let _tokenPromise  = null;

async function fetchSharedToken() {
    if (_sharedToken && Date.now() < _tokenExpiry) return _sharedToken;
    if (_tokenPromise) return _tokenPromise;

    _tokenPromise = fetch('/admin/console/token', {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    }).then(async res => {
        if (!res.ok) throw new Error(`Token request failed: ${res.status}`);
        const data = await res.json();
        if (!data.token) throw new Error('No token in response');
        _sharedToken  = data.token;
        _tokenExpiry  = Date.now() + 50_000; // 50 s (JWT dura 60 s)
        _tokenPromise = null;
        return _sharedToken;
    }).catch(err => {
        _tokenPromise = null;
        throw err;
    });

    return _tokenPromise;
}

// ─── Composable ───────────────────────────────────────────────────────────────
export function useConsoleSocket(serverName, wsPublicUrl) {
    const lines     = ref([]);
    const status    = ref('stopped');
    const connected = ref(false);

    let ws          = null;
    let reconnTimer = null;

    async function connect() {
        if (ws?.readyState === WebSocket.OPEN || ws?.readyState === WebSocket.CONNECTING) return;

        try {
            const token = await fetchSharedToken();
            ws = new WebSocket(`${wsPublicUrl}?token=${encodeURIComponent(token)}&server=${serverName}`);

            ws.onopen = () => {
                connected.value = true;
                clearTimeout(reconnTimer);
            };

            ws.onmessage = ({ data }) => {
                try {
                    const msg = JSON.parse(data);
                    if (msg.type === 'line') {
                        lines.value.push(ansiToHtml(msg.data));
                        if (lines.value.length > 500) lines.value.splice(0, lines.value.length - 500);
                    } else if (msg.type === 'state') {
                        status.value = msg.state;
                    }
                } catch { /* ignore malformed frames */ }
            };

            ws.onclose = () => {
                connected.value = false;
                ws = null;
                reconnTimer = setTimeout(connect, 5_000);
            };

            ws.onerror = () => ws?.close();

        } catch {
            reconnTimer = setTimeout(connect, 5_000);
        }
    }

    function disconnect() {
        clearTimeout(reconnTimer);
        ws?.close();
        ws = null;
        connected.value = false;
    }

    onUnmounted(disconnect);

    return { lines, status, connected, connect, disconnect };
}
