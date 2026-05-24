// Singleton z-index manager for stacked modals.
// Each modal that opens receives a z-index higher than all currently open ones,
// regardless of which page or modal triggered the open.
let current = 60;

export function useModalStack() {
    const acquire = () => {
        current += 10;
        return current;
    };

    return { acquire };
}
