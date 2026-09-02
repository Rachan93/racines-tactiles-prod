<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { useFormatting } from "@/Composables/useFormatting";
import DataTable from "@/Components/data-table/DataTable.vue";
import { columns, User } from "./columns";
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
        // Rendre optionnel avec valeur par défaut au lieu de required: true
        default: () => ({
            page: 1,
            perPage: 10,
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
            field: "id",
            direction: "asc",
        }),
    },
});

// Plus besoin d'extraire les données depuis users.data
const userData = computed(() => props.users);

// Gestionnaire de changement de page - avec vérification de sécurité
function handlePageChange(page: number) {
    router.visit(route("test.redux"), {
        data: {
            page,
            perPage: props.pagination?.perPage || 10, // Valeur par défaut
            sortField: props.sorting?.field || "id",
            sortDirection: props.sorting?.direction || "asc",
        },
        preserveState: true,
        replace: true,
    });
}

// Gestionnaire de changement du nombre d'éléments par page - avec vérification de sécurité
function handlePerPageChange(perPage: number) {
    router.visit(route("test.redux"), {
        data: {
            page: 1, // Revenir à la première page
            perPage,
            sortField: props.sorting?.field || "id",
            sortDirection: props.sorting?.direction || "asc",
        },
        preserveState: true,
        replace: true,
    });
}

// Gestionnaire de changement de tri - avec vérification de sécurité
function handleSortChange(field: string, direction: string) {
    router.visit(route("test.redux"), {
        data: {
            page: props.pagination?.page || 1,
            perPage: props.pagination?.perPage || 10,
            sortField: field,
            sortDirection: direction,
        },
        preserveState: true,
        replace: true,
    });
}

// Composable pour le formatage
const { formatDate } = useFormatting();

// Gestion des utilisateurs sélectionnés
const selectedUsers = ref<User[]>([]);

function handleSelectionChange(users: User[]) {
    selectedUsers.value = users;
}

// Actions possibles sur les sélections
function handleBulkAction(action: string) {
    if (selectedUsers.value.length === 0) return;

    // Exemple d'actions groupées
    switch (action) {
        case "delete":
            // Confirmation avant suppression
            if (
                confirm(
                    `Voulez-vous vraiment supprimer ${selectedUsers.value.length} utilisateur(s) ?`
                )
            ) {
                console.log(
                    "Suppression de:",
                    selectedUsers.value.map((u) => u.id)
                );
                // Implémentation de la suppression groupée
                // router.delete(route('admin.users.bulk-delete'), {
                //     data: { ids: selectedUsers.value.map(u => u.id) },
                //     preserveScroll: true,
                //     onSuccess: () => selectedUsers.value = []
                // });
            }
            break;
        // Autres actions possibles...
    }
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-bold">Utilisateurs</h3>

            <!-- Actions groupées (apparaît seulement quand des utilisateurs sont sélectionnés) -->
            <div v-if="selectedUsers.length > 0" class="flex space-x-2">
                <Button
                    variant="destructive"
                    size="sm"
                    @click="handleBulkAction('delete')"
                >
                    Supprimer sélection
                </Button>
                <!-- Autres boutons d'action groupée au besoin -->
            </div>
        </div>

        <!-- Utilisation du DataTable avec selection, pagination et tri côté serveur -->
        <!-- Assurer que les props sont toujours passées avec des valeurs par défaut sécurisées -->
        <DataTable
            :columns="columns"
            :data="userData"
            :pagination="pagination"
            :sorting="sorting"
            @page-change="handlePageChange"
            @per-page-change="handlePerPageChange"
            @sort-change="handleSortChange"
            @selection-change="handleSelectionChange"
        />
    </div>
</template>
