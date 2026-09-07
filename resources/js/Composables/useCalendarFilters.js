import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';

const parseBool = (value) =>
    value === true ||
    value === 1 ||
    value === '1' ||
    value === 'true';

const normalizeTypeId = (value) => {
    const typeId = Number(value);

    return [1, 2, 3].includes(typeId) ? typeId : 1;
};

export function useCalendarFilters(initialFilters = {}) {
    const initialTypeId = normalizeTypeId(initialFilters.type_id);

    const filters = reactive({
        type_id: initialTypeId,
        course_id: initialFilters.course_id ?? null,
        spot_type: initialFilters.spot_type ?? '',
        hide_full: parseBool(initialFilters.hide_full) ? 1 : 0,
        only_makeups:
            initialTypeId === 1 && parseBool(initialFilters.only_makeups)
                ? 1
                : 0,
        start_date: initialFilters.start_date ?? null,
        end_date: initialFilters.end_date ?? null,
    });

    const applyFilters = () => {
        router.get(
            window.location.pathname,
            { ...filters },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['events', 'filters'],
            },
        );
    };

    const setFilter = (key, value) => {
        if (key === 'type_id') {
            const typeId = normalizeTypeId(value);

            if (filters.type_id === typeId) return;

            filters.type_id = typeId;

            // Un cours précis peut appartenir à l’ancienne catégorie.
            filters.course_id = null;

            // Les rattrapages concernent uniquement les collectifs.
            filters.only_makeups = 0;

            applyFilters();
            return;
        }

        if (key === 'hide_full' || key === 'only_makeups') {
            value = parseBool(value) ? 1 : 0;
        }

        if (key === 'only_makeups' && filters.type_id !== 1) {
            value = 0;
        }

        if (filters[key] === value) return;

        filters[key] = value;
        applyFilters();
    };

    const setDates = (startDate, endDate) => {
        if (
            filters.start_date === startDate &&
            filters.end_date === endDate
        ) {
            return;
        }

        filters.start_date = startDate;
        filters.end_date = endDate;

        applyFilters();
    };

    const resetFilters = () => {
        Object.assign(filters, {
            type_id: 1,
            course_id: null,
            spot_type: '',
            hide_full: 0,
            only_makeups: 0,
        });

        // On conserve la semaine actuellement affichée.
        applyFilters();
    };

    return {
        filters,
        setFilter,
        setDates,
        resetFilters,
    };
}
