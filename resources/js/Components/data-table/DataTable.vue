<script setup lang="ts" generic="TData extends object, TValue">
import type {
    ColumnDef,
    SortingState,
    PaginationState,
    VisibilityState,
    RowSelectionState,
    ColumnMeta,
} from "@tanstack/vue-table";
import { ref, watch, computed, provide } from "vue";
import { FlexRender, getCoreRowModel, useVueTable } from "@tanstack/vue-table";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { ScrollArea, ScrollBar } from "@/Components/ui/scroll-area";
import DataTablePagination from "./DataTablePagination.vue";
import DataTableVisibilityToggle from "./DataTableVisibilityToggle.vue";

// Interface pour les métadonnées de colonne
interface CustomColumnMeta {
    label?: string;
}

const props = defineProps({
    // Données et colonnes du tableau
    columns: {
        type: Array as () => ColumnDef<TData, TValue>[],
        required: true,
    },
    data: {
        type: Array as () => TData[],
        required: true,
    },

    // Configuration de pagination
    pagination: {
        type: Object as () => {
            page: number;
            perPage: number;
            total: number;
            lastPage: number;
        },
        default: () => ({
            page: 1,
            perPage: 25,
            total: 0,
            lastPage: 1,
        }),
    },

    // Configuration de tri
    sorting: {
        type: Object as () => {
            field: string;
            direction: string;
        },
        default: () => ({
            field: "",
            direction: "asc",
        }),
    },

    // Options personnalisables
    pageSizeOptions: {
        type: Array as () => number[],
        default: () => [25, 50, 100],
    },
    tableHeight: {
        type: String,
        default: "800px",
    },
    noResultsText: {
        type: String,
        default: "Aucun résultat.",
    },
    noSelectionText: {
        type: String,
        default: "Aucune sélection",
    },
    selectionText: {
        type: String,
        default: "élément(s) sélectionné(s) sur",
    },
});

const emit = defineEmits<{
    pageChange: [page: number];
    perPageChange: [perPage: number];
    sortChange: [field: string, direction: string];
    selectionChange: [selectedRows: TData[]];
}>();

// État local pour la pagination et le tri
const currentPage = ref(props.pagination?.page || 1);
const currentPerPage = ref(props.pagination?.perPage || 25);
const currentSort = ref<SortingState>(
    props.sorting && props.sorting.field
        ? [
              {
                  id: props.sorting.field,
                  desc: props.sorting.direction === "desc",
              },
          ]
        : []
);
// État pour la sélection des lignes
const rowSelection = ref<RowSelectionState>({});

// Recréer un état de pagination compatible avec TanStack Table
const paginationState = computed<PaginationState>(() => ({
    pageIndex: currentPage.value - 1,
    pageSize: currentPerPage.value,
}));

// Ajouter l'état pour gérer la visibilité des colonnes
const columnVisibility = ref<VisibilityState>({});

// Configurer le tableau
const table = useVueTable({
    get data() {
        return props.data;
    },
    get columns() {
        return props.columns;
    },
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    manualSorting: true,
    pageCount: props.pagination
        ? props.pagination.lastPage
        : Math.ceil(props.data.length / currentPerPage.value),
    onColumnVisibilityChange: (updaterOrValue) => {
        columnVisibility.value =
            typeof updaterOrValue === "function"
                ? updaterOrValue(columnVisibility.value)
                : updaterOrValue;
    },
    onRowSelectionChange: (updaterOrValue) => {
        rowSelection.value =
            typeof updaterOrValue === "function"
                ? updaterOrValue(rowSelection.value)
                : updaterOrValue;

        // Émettre les lignes sélectionnées
        emitSelectedRows();
    },
    state: {
        get pagination() {
            return paginationState.value;
        },
        get sorting() {
            return currentSort.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
    },
});

// Gérer les changements de page
function handlePageChange(page: number) {
    currentPage.value = page;
    emit("pageChange", page);
}

// Gérer les changements de taille de page
function handlePerPageChange(perPage: number) {
    currentPerPage.value = perPage;
    currentPage.value = 1; // Réinitialiser à la première page quand on change la taille
    emit("perPageChange", perPage);
}

// Gérer les changements de tri
function handleSortChange(columnId: string, descending: boolean) {
    const direction = descending ? "desc" : "asc";
    currentSort.value = [{ id: columnId, desc: descending }];
    emit("sortChange", columnId, direction);
}

// Gérer les changements de visibilité
function handleVisibilityChange(columnId: string, isVisible: boolean) {
    table.getColumn(columnId)?.toggleVisibility(isVisible);
}

// Récupérer les lignes sélectionnées et les émettre
function emitSelectedRows() {
    // Récupérer toutes les lignes sélectionnées
    const selectedRowModels = table.getSelectedRowModel().rows;
    const selectedRows = selectedRowModels.map(
        (row) => row.original
    ) as TData[];

    // Émettre l'événement
    emit("selectionChange", selectedRows);
}

// Ajoutons un watcher pour détecter les changements de sélection
watch(
    rowSelection,
    () => {
        emitSelectedRows();
    },
    { deep: true }
);

// Exposer des méthodes pour les colonnes
const getIsSorted = (columnId: string) => {
    const sortEntry = currentSort.value.find((d) => d.id === columnId);
    return sortEntry ? (sortEntry.desc ? "desc" : "asc") : false;
};

const toggleSorting = (columnId: string, descending?: boolean) => {
    const isCurrentlyDescending = getIsSorted(columnId) === "desc";
    const newDescending =
        descending !== undefined ? descending : !isCurrentlyDescending;
    handleSortChange(columnId, newDescending);
};

// Exposer ces méthodes aux colonnes via provide/inject
provide("serverSideSort", {
    getIsSorted,
    toggleSorting,
});

// Préparer les données de colonnes pour le toggle de visibilité de manière générique
const visibilityColumns = computed(() =>
    table
        .getAllColumns()
        .filter((column) => column.getCanHide())
        .map((column) => {
            // Extraire le label de la colonne de manière plus générique
            let columnLabel = column.id;
            const columnDef = column.columnDef;

            // Récupérer le libellé depuis meta.label, avec vérification de type
            const meta = columnDef.meta as CustomColumnMeta | undefined;
            if (meta && meta.label) {
                columnLabel = meta.label;
            }

            return {
                id: column.id,
                label: columnLabel,
                isVisible: column.getIsVisible(),
                canHide: column.getCanHide(),
            };
        })
);

// Lignes sélectionnées pour l'affichage
const selectedRowsCount = computed(
    () => table.getFilteredSelectedRowModel().rows.length
);

// Computed properties pour les informations de pagination côté serveur
const serverTotal = computed(() => Number(props.pagination?.total ?? 0));
const serverLastPage = computed(() => Number(props.pagination?.lastPage ?? 1));
const serverPerPage = computed(() => Number(props.pagination?.perPage ?? 10));
const serverPage = computed(() => Number(props.pagination?.page ?? 1));

// Mettre à jour l'état local quand les props changent
watch(
    () => props.pagination?.page,
    (newPage) => {
        if (newPage && newPage !== currentPage.value) {
            currentPage.value = newPage;
        }
    }
);

watch(
    () => props.pagination?.perPage,
    (newPerPage) => {
        if (newPerPage && newPerPage !== currentPerPage.value) {
            currentPerPage.value = newPerPage;
        }
    }
);

watch(
    () => props.sorting,
    (newSorting) => {
        if (newSorting && newSorting.field) {
            currentSort.value = [
                {
                    id: newSorting.field,
                    desc: newSorting.direction === "desc",
                },
            ];
        }
    },
    { deep: true }
);
</script>

<template>
    <div class="space-y-4">
        <!-- En-tête avec contrôle de visibilité -->
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span
                    class="text-sm text-muted-foreground"
                    v-if="selectedRowsCount > 0"
                >
                    {{ selectedRowsCount }} {{ selectionText }}
                    {{ serverTotal }}
                </span>
                <span class="text-sm text-muted-foreground" v-else>
                    {{ noSelectionText }}
                </span>
            </div>
            <DataTableVisibilityToggle
                :columns="visibilityColumns"
                @toggle-visibility="handleVisibilityChange"
            />
        </div>

        <!-- Table avec ScrollArea -->
        <div class="border rounded-md">
            <ScrollArea :style="{ height: tableHeight }">
                <Table>
                    <TableHeader class="sticky top-0 z-10 bg-white">
                        <TableRow
                            v-for="headerGroup in table.getHeaderGroups()"
                            :key="headerGroup.id"
                        >
                            <TableHead
                                v-for="header in headerGroup.headers"
                                :key="header.id"
                            >
                                <FlexRender
                                    v-if="!header.isPlaceholder"
                                    :render="header.column.columnDef.header"
                                    :props="header.getContext()"
                                />
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="table.getRowModel().rows.length">
                            <TableRow
                                v-for="row in table.getRowModel().rows"
                                :key="row.id"
                            >
                                <TableCell
                                    v-for="cell in row.getVisibleCells()"
                                    :key="cell.id"
                                >
                                    <FlexRender
                                        :render="cell.column.columnDef.cell"
                                        :props="cell.getContext()"
                                    />
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else>
                            <TableRow>
                                <TableCell
                                    :colspan="props.columns.length"
                                    class="h-24 text-center"
                                >
                                    {{ noResultsText }}
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
                <ScrollBar orientation="horizontal" />
            </ScrollArea>
        </div>

        <!-- Pagination -->
        <div>
            <DataTablePagination
                :current-page="serverPage"
                :per-page="serverPerPage"
                :total-items="serverTotal"
                :last-page="serverLastPage"
                :page-size-options="pageSizeOptions"
                @page-change="handlePageChange"
                @per-page-change="handlePerPageChange"
            />
        </div>
    </div>
</template>

<style scoped>
/* Style pour maintenir l'en-tête fixe pendant le défilement */
:deep(thead) {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: white;
}

/* Style pour ajouter une ombre sous l'en-tête fixe */
:deep(thead th) {
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

/* Styles pour les cellules du tableau */
:deep(tbody td) {
    vertical-align: top;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
}
</style>
