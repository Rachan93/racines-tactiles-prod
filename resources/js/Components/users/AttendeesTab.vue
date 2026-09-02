<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import DataTable from "@/Components/data-table/DataTable.vue";
import DataTableSearch from "@/Components/data-table/DataTableSearch.vue";
import DateFilter from "@/Components/filters/DateFilter.vue";
import BirthdayFilter from "@/Components/filters/BirthdayFilter.vue"; // Ajout de l'import
import { attendeeColumns, Attendee } from "./attendeeColumns";
import { Button } from "@/Components/ui/button";

// Déclaration de la fonction route
declare function route(name: string, params?: Record<string, any>): string;

const props = defineProps({
    attendees: {
        type: Array as () => Attendee[],
        required: true,
    },
    pagination: {
        type: Object as () => {
            page: number;
            perPage: number;
            total: number;
            lastPage: number;
        },
        default: () => ({
            page: 1,
            perPage: 25, // Modifié: pagination par défaut à 25 éléments
            total: 0,
            lastPage: 1,
        }),
    },
    sorting: {
        type: Object as () => {
            field: string;
            direction: string;
        },
        default: () => ({
            field: "last_name", // Modifié pour tri par défaut sur nom de famille
            direction: "asc",
        }),
    },
    search: {
        type: String,
        default: "",
    },
    registrationDateFilter: {
        type: Object,
        default: () => ({
            operator: "",
            date: "",
            dateEnd: "",
        }),
    },
    birthdayFilter: {
        type: Object,
        default: () => ({
            operator: "",
            day: "",
            month: "",
            year: "",
            endDay: "",
            endMonth: "",
            endYear: "",
        }),
    },
});

const attendeesData = computed(() => props.attendees);
const searchQuery = ref(props.search);

// Options de filtres pour la recherche d'accompagnants - tous activés par défaut
const attendeeFilterOptions = ref([
    { id: "last_name", label: "Nom", enabled: true },
    { id: "first_name", label: "Prénom", enabled: true },
    { id: "user_name", label: "Nom du responsable", enabled: true },
    { id: "user_email", label: "Email du responsable", enabled: true },
]);

const registrationDateFilterModel = ref(
    props.registrationDateFilter || {
        operator: "",
        date: "",
        dateEnd: "",
    }
);

const birthdayFilterModel = ref(
    props.birthdayFilter || {
        operator: "",
        day: "",
        month: "",
        year: "",
        endDay: "",
        endMonth: "",
        endYear: "",
    }
);

// Fonction pour gérer la recherche avec état persistant
function handleSearch(query: string, activeFilters: string[]) {
    searchQuery.value = query; // Mise à jour de la valeur locale

    const url = new URL(window.location.href);
    const params = url.searchParams;

    router.visit(route("users.index"), {
        data: {
            attendees_page: 1,
            attendees_perPage: props.pagination?.perPage || 25,
            attendees_sortField: props.sorting?.field || "last_name",
            attendees_sortDirection: props.sorting?.direction || "asc",
            attendees_search: query,
            attendees_search_filters: activeFilters,
            // Ajouter les filtres de date pour qu'ils soient préservés pendant la recherche
            attendees_created_at_operator:
                registrationDateFilterModel.value.operator || "",
            attendees_created_at_date:
                registrationDateFilterModel.value.date || "",
            attendees_created_at_date_end:
                registrationDateFilterModel.value.dateEnd || "",
            // Ajouter les filtres d'anniversaire
            attendees_birthday_operator:
                birthdayFilterModel.value.operator || "",
            attendees_birthday_day: birthdayFilterModel.value.day || "",
            attendees_birthday_month: birthdayFilterModel.value.month || "",
            attendees_birthday_year: birthdayFilterModel.value.year || "",
            attendees_birthday_end_day: birthdayFilterModel.value.endDay || "",
            attendees_birthday_end_month:
                birthdayFilterModel.value.endMonth || "",
            attendees_birthday_end_year:
                birthdayFilterModel.value.endYear || "",
            tab: "attendees",
            // Préserver les valeurs de l'autre onglet
            users_page: params.get("users_page") || 1,
            users_perPage: params.get("users_perPage") || 25,
            users_sortField: params.get("users_sortField") || "last_name",
            users_sortDirection: params.get("users_sortDirection") || "asc",
            users_search: params.get("users_search") || "",
            // Préserver aussi les filtres de date des utilisateurs
            users_created_at_operator:
                params.get("users_created_at_operator") || "",
            users_created_at_date: params.get("users_created_at_date") || "",
            users_created_at_date_end:
                params.get("users_created_at_date_end") || "",
        },
        preserveState: true,
        replace: true,
    });
}

// Fonction pour gérer le changement de filtres
function handleFilterChange(activeFilters: string[]) {
    // Si l'utilisateur a déjà entré une requête, on lance la recherche
    if (searchQuery.value.trim()) {
        handleSearch(searchQuery.value, activeFilters);
    }
}

// Fonction pour effacer la recherche
function clearSearch() {
    handleSearch("", []);
}

// Gestionnaire de changement de page - préserve la recherche
function handlePageChange(page: number) {
    router.visit(route("users.index"), {
        data: {
            attendees_page: page,
            attendees_perPage: props.pagination?.perPage || 25,
            attendees_sortField: props.sorting?.field || "last_name",
            attendees_sortDirection: props.sorting?.direction || "asc",
            attendees_search: searchQuery.value, // Préserver la recherche
            attendees_search_filters: getAttendeeFilterOptions(), // Préserver les filtres actifs
            tab: "attendees",
            // Préserver les valeurs de l'autre onglet
            users_page:
                new URL(window.location.href).searchParams.get("users_page") ||
                1,
            users_perPage:
                new URL(window.location.href).searchParams.get(
                    "users_perPage"
                ) || 25,
            users_sortField:
                new URL(window.location.href).searchParams.get(
                    "users_sortField"
                ) || "last_name",
            users_sortDirection:
                new URL(window.location.href).searchParams.get(
                    "users_sortDirection"
                ) || "asc",
            users_search:
                new URL(window.location.href).searchParams.get(
                    "users_search"
                ) || "",
        },
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

// Gestionnaire de changement du nombre d'éléments par page
function handlePerPageChange(perPage: number) {
    router.visit(route("users.index"), {
        data: {
            attendees_page: 1, // Reset à la première page quand on change le nombre d'éléments
            attendees_perPage: perPage,
            attendees_sortField: props.sorting?.field || "last_name",
            attendees_sortDirection: props.sorting?.direction || "asc",
            attendees_search: searchQuery.value, // Préserver la recherche
            attendees_search_filters: getAttendeeFilterOptions(), // Préserver les filtres actifs
            tab: "attendees",
            // Préserver les valeurs de l'autre onglet
            users_page:
                new URL(window.location.href).searchParams.get("users_page") ||
                1,
            users_perPage:
                new URL(window.location.href).searchParams.get(
                    "users_perPage"
                ) || 25,
            users_sortField:
                new URL(window.location.href).searchParams.get(
                    "users_sortField"
                ) || "last_name",
            users_sortDirection:
                new URL(window.location.href).searchParams.get(
                    "users_sortDirection"
                ) || "asc",
        },
        preserveState: true,
        replace: true,
    });
}

// Gestionnaire de changement de tri
function handleSortChange(field: string, direction: string) {
    router.visit(route("users.index"), {
        data: {
            attendees_page: props.pagination?.page || 1,
            attendees_perPage: props.pagination?.perPage || 25,
            attendees_sortField: field,
            attendees_sortDirection: direction,
            attendees_search: searchQuery.value, // Préserver la recherche
            attendees_search_filters: getAttendeeFilterOptions(), // Préserver les filtres actifs
            tab: "attendees",
            // Préserver les valeurs de l'autre onglet
            users_page:
                new URL(window.location.href).searchParams.get("users_page") ||
                1,
            users_perPage:
                new URL(window.location.href).searchParams.get(
                    "users_perPage"
                ) || 25,
            users_sortField:
                new URL(window.location.href).searchParams.get(
                    "users_sortField"
                ) || "last_name",
            users_sortDirection:
                new URL(window.location.href).searchParams.get(
                    "users_sortDirection"
                ) || "asc",
        },
        preserveState: true,
        replace: true,
    });
}

// Fonction utilitaire pour récupérer les filtres actifs
function getAttendeeFilterOptions(): string[] {
    return attendeeFilterOptions.value
        .filter((option) => option.enabled)
        .map((option) => option.id);
}

// Fonction pour appliquer le filtre de date d'inscription
function handleRegistrationDateFilter(filterData: {
    operator: string;
    date: string;
    dateEnd: string;
}) {
    const url = new URL(window.location.href);
    const params = url.searchParams;

    // Mettre à jour registrationDateFilterModel pour qu'il reste synchronisé
    registrationDateFilterModel.value = filterData;

    router.visit(route("users.index"), {
        data: {
            attendees_page: 1, // Reset à la première page lors d'un filtrage
            attendees_perPage: props.pagination?.perPage || 25,
            attendees_sortField: props.sorting?.field || "last_name",
            attendees_sortDirection: props.sorting?.direction || "asc",
            attendees_search: searchQuery.value,
            attendees_search_filters: getAttendeeFilterOptions(),
            attendees_created_at_operator: filterData.operator,
            attendees_created_at_date: filterData.date,
            attendees_created_at_date_end: filterData.dateEnd,
            // Ajouter les filtres d'anniversaire
            attendees_birthday_operator:
                birthdayFilterModel.value.operator || "",
            attendees_birthday_day: birthdayFilterModel.value.day || "",
            attendees_birthday_month: birthdayFilterModel.value.month || "",
            attendees_birthday_year: birthdayFilterModel.value.year || "",
            attendees_birthday_end_day: birthdayFilterModel.value.endDay || "",
            attendees_birthday_end_month:
                birthdayFilterModel.value.endMonth || "",
            attendees_birthday_end_year:
                birthdayFilterModel.value.endYear || "",
            tab: "attendees",
            // Préserver les valeurs de l'autre onglet
            users_page: params.get("users_page") || 1,
            users_perPage: params.get("users_perPage") || 25,
            users_sortField: params.get("users_sortField") || "last_name",
            users_sortDirection: params.get("users_sortDirection") || "asc",
            users_search: params.get("users_search") || "",
            // Préserver aussi les filtres de date des utilisateurs
            users_created_at_operator:
                params.get("users_created_at_operator") || "",
            users_created_at_date: params.get("users_created_at_date") || "",
            users_created_at_date_end:
                params.get("users_created_at_date_end") || "",
        },
        preserveState: true,
        replace: true,
    });
}

// Fonction pour appliquer le filtre de date d'anniversaire
function handleBirthdayFilter(filterData: {
    operator: string;
    day: string;
    month: string;
    year: string;
    endDay: string;
    endMonth: string;
    endYear: string;
}) {
    const url = new URL(window.location.href);
    const params = url.searchParams;

    // Mettre à jour birthdayFilterModel pour qu'il reste synchronisé
    birthdayFilterModel.value = filterData;

    router.visit(route("users.index"), {
        data: {
            attendees_page: 1, // Reset à la première page lors d'un filtrage
            attendees_perPage: props.pagination?.perPage || 25,
            attendees_sortField: props.sorting?.field || "last_name",
            attendees_sortDirection: props.sorting?.direction || "asc",
            attendees_search: searchQuery.value,
            attendees_search_filters: getAttendeeFilterOptions(),
            // Préserver les filtres de date d'inscription
            attendees_created_at_operator:
                registrationDateFilterModel.value.operator || "",
            attendees_created_at_date:
                registrationDateFilterModel.value.date || "",
            attendees_created_at_date_end:
                registrationDateFilterModel.value.dateEnd || "",
            // Ajouter les filtres d'anniversaire
            attendees_birthday_operator: filterData.operator,
            attendees_birthday_day: filterData.day,
            attendees_birthday_month: filterData.month,
            attendees_birthday_year: filterData.year,
            attendees_birthday_end_day: filterData.endDay,
            attendees_birthday_end_month: filterData.endMonth,
            attendees_birthday_end_year: filterData.endYear,
            tab: "attendees",
            // Préserver les valeurs de l'autre onglet
            users_page: params.get("users_page") || 1,
            users_perPage: params.get("users_perPage") || 25,
            users_sortField: params.get("users_sortField") || "last_name",
            users_sortDirection: params.get("users_sortDirection") || "asc",
            users_search: params.get("users_search") || "",
            // Préserver aussi les filtres des utilisateurs
            users_created_at_operator:
                params.get("users_created_at_operator") || "",
            users_created_at_date: params.get("users_created_at_date") || "",
            users_created_at_date_end:
                params.get("users_created_at_date_end") || "",
            users_birthday_operator:
                params.get("users_birthday_operator") || "",
            users_birthday_day: params.get("users_birthday_day") || "",
            users_birthday_month: params.get("users_birthday_month") || "",
            users_birthday_year: params.get("users_birthday_year") || "",
            users_birthday_end_day: params.get("users_birthday_end_day") || "",
            users_birthday_end_month:
                params.get("users_birthday_end_month") || "",
            users_birthday_end_year:
                params.get("users_birthday_end_year") || "",
        },
        preserveState: true,
        replace: true,
    });
}

// Mise à jour: ajoutons un watcher pour garder le modèle local synchro avec les props
watch(
    () => props.registrationDateFilter,
    (newVal) => {
        registrationDateFilterModel.value = { ...newVal };
    },
    { deep: true, immediate: true }
);

// Ajout du watcher pour le filtre d'anniversaire
watch(
    () => props.birthdayFilter,
    (newVal) => {
        birthdayFilterModel.value = { ...newVal };
    },
    { deep: true, immediate: true }
);

// Gestion des accompagnants sélectionnés
const selectedAttendees = ref<Attendee[]>([]);

function handleSelectionChange(attendees: Attendee[]) {
    selectedAttendees.value = attendees;
}

// Actions possibles sur les sélections
function handleBulkAction(action: string) {
    if (selectedAttendees.value.length === 0) return;

    switch (action) {
        case "delete":
            if (
                confirm(
                    `Voulez-vous vraiment supprimer ${selectedAttendees.value.length} accompagnant(s) ?`
                )
            ) {
                router.delete(route("admin.attendees.bulk-delete"), {
                    data: { ids: selectedAttendees.value.map((a) => a.id) },
                    preserveScroll: true,
                    onSuccess: () => (selectedAttendees.value = []),
                });
            }
            break;
    }
}

// Référence au composant de recherche
const searchComponent = ref(null);

// Exposer une méthode pour réinitialiser la recherche
defineExpose({
    resetSearch() {
        if (searchComponent.value) {
            searchComponent.value.reset();
        }
    },
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-bold">Accompagnants</h3>

            <!-- Actions groupées -->
            <div v-if="selectedAttendees.length > 0" class="flex space-x-2">
                <Button
                    variant="destructive"
                    size="sm"
                    @click="handleBulkAction('delete')"
                >
                    Supprimer sélection
                </Button>
            </div>
        </div>

        <!-- Filtres réorganisés -->
        <!-- Première ligne: barre de recherche -->
        <div class="w-full">
            <DataTableSearch
                ref="searchComponent"
                placeholder="Rechercher un accompagnant..."
                :search-query="searchQuery"
                :filter-options="attendeeFilterOptions"
                @search="handleSearch"
                @filter-change="handleFilterChange"
                @clear="clearSearch"
            />
        </div>

        <!-- Deuxième ligne: Filtres supplémentaires -->
        <div class="flex flex-wrap gap-4">
            <!-- Filtre par date d'inscription -->
            <div class="min-w-[300px] max-w-[360px] w-auto">
                <DateFilter
                    v-model="registrationDateFilterModel"
                    label="Date d'inscription"
                    @filter="handleRegistrationDateFilter"
                />
            </div>

            <!-- Filtre par date d'anniversaire -->
            <div class="min-w-[300px] max-w-[360px] w-auto">
                <BirthdayFilter
                    v-model="birthdayFilterModel"
                    label="Date d'anniversaire"
                    @filter="handleBirthdayFilter"
                />
            </div>
            <!-- Emplacement pour futurs filtres -->
        </div>

        <!-- DataTable -->
        <DataTable
            :columns="attendeeColumns"
            :data="attendeesData"
            :pagination="pagination"
            :sorting="sorting"
            :table-height="'800px'"
            @page-change="handlePageChange"
            @per-page-change="handlePerPageChange"
            @sort-change="handleSortChange"
            @selection-change="handleSelectionChange"
        />
    </div>
</template>
