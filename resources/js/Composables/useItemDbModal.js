import { ref } from 'vue';

const EMULATOR_TYPE = {
    0: 'Healing', 1: 'Healing',
    2: 'Usable',  3: 'Etc', 4: 'Etc',
    5: 'Weapon',  6: 'Armor',
    7: 'Card',   10: 'Ammo', 11: 'Card',
    18: 'Armor',
};

// Module-level refs — singleton compartido entre componentes
const itemDbItem  = ref(null);
const itemDbCount = ref(null);

export function useItemDbModal() {
    const openItemDb = async (nameid, fallback = {}) => {
        const isForged = fallback.card0 === 255;

        itemDbItem.value = {
            item_id:          nameid,
            name:             fallback.name_english ?? fallback.item_name ?? fallback.display_name ?? fallback.name ?? `Item #${nameid}`,
            display_name:     fallback.name_english ?? fallback.item_name ?? fallback.display_name ?? fallback.name ?? null,
            aegis_name:       null,
            type:             typeof fallback.item_type === 'number'
                                  ? (EMULATOR_TYPE[fallback.item_type] ?? 'Etc')
                                  : (fallback.item_type ?? null),
            subtype:          null,
            slots:            isForged ? 0 : (fallback.item_slots ?? fallback.slots ?? 0),
            description_html: null,
            is_custom:        false,
            properties:       null,
        };
        itemDbCount.value = null;

        try {
            const res = await fetch(`/info/item-db/${nameid}`);
            if (res.ok) {
                const data        = await res.json();
                itemDbItem.value  = isForged ? { ...data, slots: 0 } : data;
                itemDbCount.value = data.server_count ?? null;
            }
        } catch { /* silencioso */ }
    };

    const closeItemDb = () => {
        itemDbItem.value  = null;
        itemDbCount.value = null;
    };

    return { itemDbItem, itemDbCount, openItemDb, closeItemDb };
}
