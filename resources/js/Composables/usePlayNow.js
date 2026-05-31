import { ref } from 'vue';

export const gameOverlayUrl = ref(null);

function isTouchDevice() {
    return navigator.maxTouchPoints > 0 || /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
}

export function playNowMobile(event, url) {
    if (!url) return;

    if (!isTouchDevice()) {
        // Desktop: comportamiento original
        return;
    }

    event.preventDefault();

    const el = document.documentElement;

    const fsPromise = el.requestFullscreen
        ? el.requestFullscreen()
        : el.webkitRequestFullscreen
            ? (el.webkitRequestFullscreen(), Promise.resolve())
            : Promise.resolve();

    const afterFs = (fsPromise instanceof Promise) ? fsPromise : Promise.resolve();

    afterFs
        .then(() => {
            if (screen.orientation?.lock) {
                screen.orientation.lock('landscape').catch(() => {});
            }
        })
        .catch(() => {
            if (screen.orientation?.lock) {
                screen.orientation.lock('landscape').catch(() => {});
            }
        })
        .finally(() => {
            gameOverlayUrl.value = url;
        });
}

export function closeGame() {
    gameOverlayUrl.value = null;
    if (screen.orientation?.unlock) screen.orientation.unlock();
    if (document.exitFullscreen) document.exitFullscreen().catch(() => {});
    else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
}
