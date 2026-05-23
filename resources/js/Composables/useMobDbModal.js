import { ref } from 'vue';

// Module-level refs — singleton compartido entre componentes
const selectedMob   = ref(null);
const mobDetail     = ref(null);
const detailLoading = ref(false);
const mobTab        = ref('info');

export function useMobDbModal() {
    const openMobDb = async (mob) => {
        selectedMob.value   = mob;
        mobDetail.value     = null;
        detailLoading.value = true;
        mobTab.value        = 'info';
        try {
            const res       = await fetch(`/info/mob-db/${mob.id}`);
            mobDetail.value = await res.json();
        } catch { /* silencioso */ }
        finally { detailLoading.value = false; }
    };

    const closeMobDb = () => {
        selectedMob.value = null;
        mobDetail.value   = null;
    };

    return { selectedMob, mobDetail, detailLoading, mobTab, openMobDb, closeMobDb };
}
