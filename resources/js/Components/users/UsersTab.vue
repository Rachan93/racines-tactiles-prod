<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { useFormatting } from "@/Composables/useFormatting";
import DataTable from "@/Components/data-table/DataTable.vue";
import DataTableSearch from "@/Components/data-table/DataTableSearch.vue";
import DateFilter from "@/Components/filters/DateFilter.vue";
import BirthdayFilter from "@/Components/filters/BirthdayFilter.vue";
import { userColumns, User } from "./userColumns";
import { Button } from "@/Components/ui/button";

// Déclaration de la fonction route
declare function route(name: string, params?: Record<string, any>): string;

const props = defineProps({
    users: {
        type: Array as () => User[],
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
            day: "",
            month: "",
            year: "",
        }),
    },
});

const userData = computed(() => props.users);
const searchQuery = ref(props.search);

// Options de filtres pour la recherche d'utilisateurs - tous activés par défaut
const userFilterOptions = ref([
    { id: "last_name", label: "Nom", enabled: true },
    { id: "first_name", label: "Prénom", enabled: true },
    { id: "email", label: "Email", enabled: true },
    { id: "phone_number", label: "Téléphone", enabled: true },
    { id: "locality", label: "Localité", enabled: true },
    { id: "postal_code", label: "Code postal", enabled: true },
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
        day: "",
        month: "",
        year: "",
    }
);

// Fonction pour gérer la recherche avec état persistant
function handleSearch(query: string, activeFilters: string[]) {
    searchQuery.value = query; // Mise à jour de la valeur locale

    const url = new URL(window.location.href);
    const params = url.searchParams;

    router.visit(route("users.index"), {
        data: {
            users_page: 1, // Reset à la première page lors d'une recherche
            users_perPage: props.pagination?.perPage || 25,
            users_sortField: props.sorting?.field || "last_name",
            users_sortDirection: props.sorting?.direction || "asc",
            users_search: query,
            users_search_filters: activeFilters,
            // Ajouter les filtres de date pour qu'ils soient préservés pendant la recherche
            users_created_at_operator:
                registrationDateFilterModel.value.operator || "",
            users_created_at_date: registrationDateFilterModel.value.date || "",
            users_created_at_date_end:
                registrationDateFilterModel.value.dateEnd || "",
            tab: "users",
            // Préserver les valeurs de l'autre onglet
            attendees_page: params.get("attendees_page") || 1,
            attendees_perPage: params.get("attendees_perPage") || 25,
            attendees_sortField:
                params.get("attendees_sortField") || "last_name",
            attendees_sortDirection:
                params.get("attendees_sortDirection") || "asc",
            attendees_search: params.get("attendees_search") || "",
            // Préserver aussi les filtres de date des accompagnants
            attendees_created_at_operator:
                params.get("attendees_created_at_operator") || "",
            attendees_created_at_date:
                params.get("attendees_created_at_date") || "",
            attendees_created_at_date_end:
                params.get("attendees_created_at_date_end") || "",
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

// Gestionnaire de changement de page - avec vérification de sécurité
function handlePageChange(page: number) {
    const url = new URL(window.location.href);
    const params = url.searchParams;

    router.visit(route("users.index"), {
        data: {
            users_page: page,
            users_perPage: props.pagination?.perPage || 25,
            users_sortField: props.sorting?.field || "last_name",
            users_sortDirection: props.sorting?.direction || "asc",
            users_search: searchQuery.value, // Préserver la recherche
            users_search_filters: getUserFilterOptions(),
            // Préserver aussi les filtres de date
            users_created_at_operator:
                registrationDateFilterModel.value.operator || "",
            users_created_at_date: registrationDateFilterModel.value.date || "",
            users_created_at_date_end:
                registrationDateFilterModel.value.dateEnd || "",
            tab: "users",
            // Préserver les valeurs de l'autre onglet
            attendees_page:
                new URL(window.location.href).searchParams.get(
                    "attendees_page"
                ) || 1,
            attendees_perPage:
                new URL(window.location.href).searchParams.get(
                    "attendees_perPage"
                ) || 25,
            attendees_sortField:
                new URL(window.location.href).searchParams.get(
                    "attendees_sortField"
                ) || "last_name",
            attendees_sortDirection:
                new URL(window.location.href).searchParams.get(
                    "attendees_sortDirection"
                ) || "asc",
            attendees_search:
                new URL(window.location.href).searchParams.get(
                    "attendees_search"
                ) || "",
        },
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

// Gestionnaire de changement du nombre d'éléments par page - avec vérification de sécurité
function handlePerPageChange(perPage: number) {
    router.visit(route("users.index"), {
        data: {
            users_page: 1, // Reset à la première page quand on change le nombre d'éléments
            users_perPage: perPage,
            users_sortField: props.sorting?.field || "last_name",
            users_sortDirection: props.sorting?.direction || "asc",
            users_search: searchQuery.value, // Préserver la recherche
            users_search_filters: getUserFilterOptions(), // Préserver les filtres actifs
            tab: "users",
            // Préserver les valeurs de l'autre onglet
            attendees_page:
                new URL(window.location.href).searchParams.get(
                    "attendees_page"
                ) || 1,
            attendees_perPage:
                new URL(window.location.href).searchParams.get(
                    "attendees_perPage"
                ) || 25,
            attendees_sortField:
                new URL(window.location.href).searchParams.get(
                    "attendees_sortField"
                ) || "last_name",
            attendees_sortDirection:
                new URL(window.location.href).searchParams.get(
                    "attendees_sortDirection"
                ) || "asc",
            attendees_search:
                new URL(window.location.href).searchParams.get(
                    "attendees_search"
                ) || "",
        },
        preserveState: true,
        replace: true,
    });
}

// Gestionnaire de changement de tri - avec vérification de sécurité
function handleSortChange(field: string, direction: string) {
    router.visit(route("users.index"), {
        data: {
            users_page: props.pagination?.page || 1,
            users_perPage: props.pagination?.perPage || 25,
            users_sortField: field,
            users_sortDirection: direction,
            users_search: searchQuery.value, // Préserver la recherche
            users_search_filters: getUserFilterOptions(), // Préserver les filtres actifs
            tab: "users",
            // Préserver les valeurs de l'autre onglet
            attendees_page:
                new URL(window.location.href).searchParams.get(
                    "attendees_page"
                ) || 1,
            attendees_perPage:
                new URL(window.location.href).searchParams.get(
                    "attendees_perPage"
                ) || 25,
            attendees_sortField:
                new URL(window.location.href).searchParams.get(
                    "attendees_sortField"
                ) || "last_name",
            attendees_sortDirection:
                new URL(window.location.href).searchParams.get(
                    "attendees_sortDirection"
                ) || "asc",
            attendees_search:
                new URL(window.location.href).searchParams.get(
                    "attendees_search"
                ) || "",
        },
        preserveState: true,
        replace: true,
    });
}

// Gestion des utilisateurs sélectionnés
const selectedUsers = ref<User[]>([]);

function handleSelectionChange(users: User[]) {
    selectedUsers.value = users;
}

// Actions possibles sur les sélections
function handleBulkAction(action: string) {
    if (selectedUsers.value.length === 0) return;

    switch (action) {
        case "delete":
            if (
                confirm(
                    `Voulez-vous vraiment supprimer ${selectedUsers.value.length} utilisateur(s) ?`
                )
            ) {
                router.delete(route("users.bulk-delete"), {
                    data: { ids: selectedUsers.value.map((u) => u.id) },
                    preserveScroll: true,
                    onSuccess: () => (selectedUsers.value = []),
                });
            }
            break;
        case "email":
            // Rediriger vers une page d'envoi d'email groupé
            router.visit(route("users.bulk-email"), {
                data: { ids: selectedUsers.value.map((u) => u.id) },
            });
            break;
    }
}

// Fonction utilitaire pour récupérer les filtres actifs
function getUserFilterOptions(): string[] {
    return userFilterOptions.value
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
            users_page: 1, // Reset à la première page lors d'un filtrage
            users_perPage: props.pagination?.perPage || 25,
            users_sortField: props.sorting?.field || "last_name",
            users_sortDirection: props.sorting?.direction || "asc",
            users_search: searchQuery.value,
            users_search_filters: getUserFilterOptions(),
            users_created_at_operator: filterData.operator,
            users_created_at_date: filterData.date,
            users_created_at_date_end: filterData.dateEnd,
            tab: "users",
            // Préserver les valeurs de l'autre onglet
            attendees_page: params.get("attendees_page") || 1,
            attendees_perPage: params.get("attendees_perPage") || 25,
            attendees_sortField:
                params.get("attendees_sortField") || "last_name",
            attendees_sortDirection:
                params.get("attendees_sortDirection") || "asc",
            attendees_search: params.get("attendees_search") || "",
            // Préserver aussi les filtres de date des accompagnants
            attendees_created_at_operator:
                params.get("attendees_created_at_operator") || "",
            attendees_created_at_date:
                params.get("attendees_created_at_date") || "",
            attendees_created_at_date_end:
                params.get("attendees_created_at_date_end") || "",
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
            users_page: 1, // Reset à la première page lors d'un filtrage
            users_perPage: props.pagination?.perPage || 25,
            users_sortField: props.sorting?.field || "last_name",
            users_sortDirection: props.sorting?.direction || "asc",
            users_search: searchQuery.value,
            users_search_filters: getUserFilterOptions(),
            // Préserver les filtres de date d'inscription
            users_created_at_operator:
                registrationDateFilterModel.value.operator || "",
            users_created_at_date: registrationDateFilterModel.value.date || "",
            users_created_at_date_end:
                registrationDateFilterModel.value.dateEnd || "",
            // Ajouter les filtres d'anniversaire avec l'opérateur
            users_birthday_operator: filterData.operator,
            users_birthday_day: filterData.day,
            users_birthday_month: filterData.month,
            users_birthday_year: filterData.year,
            users_birthday_end_day: filterData.endDay,
            users_birthday_end_month: filterData.endMonth,
            users_birthday_end_year: filterData.endYear,
            tab: "users",
            // Préserver les valeurs de l'autre onglet
            attendees_page: params.get("attendees_page") || 1,
            attendees_perPage: params.get("attendees_perPage") || 25,
            attendees_sortField:
                params.get("attendees_sortField") || "last_name",
            attendees_sortDirection:
                params.get("attendees_sortDirection") || "asc",
            attendees_search: params.get("attendees_search") || "",
            // Préserver aussi les filtres de date des accompagnants
            attendees_created_at_operator:
                params.get("attendees_created_at_operator") || "",
            attendees_created_at_date:
                params.get("attendees_created_at_date") || "",
            attendees_created_at_date_end:
                params.get("attendees_created_at_date_end") || "",
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

// Ajoutons un watcher pour le filtre d'anniversaire
watch(
    () => props.birthdayFilter,
    (newVal) => {
        birthdayFilterModel.value = { ...newVal };
    },
    { deep: true, immediate: true }
);

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
            <h3 class="text-lg font-bold">Utilisateurs</h3>
            <!-- Actions groupées -->
            <div v-if="selectedUsers.length > 0" class="flex space-x-2">
                <Button
                    variant="outline"
                    size="sm"
                    @click="handleBulkAction('email')"
                >
                    Envoyer un email
                </Button>
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
                placeholder="Rechercher un utilisateur..."
                :search-query="searchQuery"
                :filter-options="userFilterOptions"
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
            :columns="userColumns"
            :data="userData"
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
