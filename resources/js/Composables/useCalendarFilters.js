import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';

export function useCalendarFilters(initialFilters = {}) {
    // État réactif des filtres (initialisé avec les props transmises par Laravel)
    const filters = reactive({
        type_id: initialFilters.type_id ?? null,
        course_id: initialFilters.course_id ?? null,
        start_date: initialFilters.start_date ?? null,
        end_date: initialFilters.end_date ?? null,
    });

    /**
     * Envoie une requête arrière-plan Inertia pour rafraîchir uniquement les événements
     * sans recharger toute la page web (Rechargement partiel).
     */
    const applyFilters = () => {
        router.get(
            window.location.pathname,
            { ...filters },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['events', 'filters'], // On ne demande à Laravel que les événements rafraîchis
            }
        );
    };

    /**
     * Mettre à jour un filtre spécifique (ex: changer le type de cours)
     */
    const setFilter = (key, value) => {
        filters[key] = value;
        applyFilters();
    };

    /**
     * Mettre à jour la plage de dates.
     * Déclenché automatiquement par FullCalendar lors du clic sur "Semaine suivante / précédente".
     */
    const setDates = (startDate, endDate) => {
        // Évite les requêtes inutiles si la plage n'a pas changé
        if (filters.start_date === startDate && filters.end_date === endDate) {
            return;
        }

        filters.start_date = startDate;
        filters.end_date = endDate;
        applyFilters();
    };

    /**
     * Réinitialiser tous les filtres
     */
    const resetFilters = () => {
        filters.type_id = null;
        filters.course_id = null;
        applyFilters();
    };

    return {
        filters,
        setFilter,
        setDates,
        resetFilters,
    };
}
