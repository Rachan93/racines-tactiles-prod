<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { pluralize } from "@/Utils/formatters";
import AdminLayout from "@/Layouts/AdminLayout.vue";

// Composants du répertoire
import UserSearchFilters from "@/Components/admin/users/UserSearchFilters.vue";
import UserTable from "@/Components/admin/users/UserTable.vue";
import AttendeeTable from "@/Components/admin/users/AttendeeTable.vue";
import UserEmailSheet from "@/Components/admin/users/UserEmailSheet.vue";

// Composants Shadcn UI
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/Components/ui/tabs";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";
import {
    ChevronRight,
    Users,
    UserCheck,
    Download,
    Mail,
    Trash2,
    X,
    AlertTriangle,
    Loader2,
} from "lucide-vue-next";

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    usersPagination: {
        type: Object,
        default: () => ({ page: 1, perPage: 25, total: 0, lastPage: 1 }),
    },
    attendees: {
        type: Array,
        default: () => [],
    },
    attendeesPagination: {
        type: Object,
        default: () => ({ page: 1, perPage: 25, total: 0, lastPage: 1 }),
    },
    courses: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

// Onglet actif ('users' | 'attendees')
const activeTab = ref(props.filters.tab || "users");

// 1. SÉLECTION MULTI-PAGES PERSISTANTE
const selectedUserIds = ref([]);
const selectAllMatchingUsers = ref(false);

const selectedAttendeeIds = ref([]);
const selectAllMatchingAttendees = ref(false);

// Handlers dédiés pour garantir la réactivité .value
const handleUpdateSelectedUserIds = (ids) => {
    selectedUserIds.value = (ids || []).map(Number);
};

const handleUpdateSelectAllMatchingUsers = (val) => {
    selectAllMatchingUsers.value = Boolean(val);
};

const handleUpdateSelectedAttendeeIds = (ids) => {
    selectedAttendeeIds.value = (ids || []).map(Number);
};

const handleUpdateSelectAllMatchingAttendees = (val) => {
    selectAllMatchingAttendees.value = Boolean(val);
};

// Nombre effectif d'éléments sélectionnés
const selectedCount = computed(() => {
    if (activeTab.value === "users") {
        return selectAllMatchingUsers.value ? Number(props.usersPagination.total) : selectedUserIds.value.length;
    }
    return selectAllMatchingAttendees.value ? Number(props.attendeesPagination.total) : selectedAttendeeIds.value.length;
});

// Libellé dynamique du bouton CSV principal
const csvExportButtonText = computed(() => {
    if (activeTab.value === "users") {
        return `Exporter les ${pluralize(props.usersPagination.total, "membre")} en CSV`;
    }
    return `Exporter les ${pluralize(props.attendeesPagination.total, "invité")} en CSV`;
});

// Navigation avec conservation des paramètres
const navigateWithFilters = (customParams = {}) => {
    const merged = { ...props.filters, ...customParams };
    router.get(route("users.index"), merged, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Basculement d'onglet avec synchronisation intégrale des filtres
const handleTabChange = (newTab) => {
    activeTab.value = newTab;
    const fromP = newTab === "attendees" ? "users_" : "attendees_";
    const toP = newTab === "attendees" ? "attendees_" : "users_";

    const params = { ...props.filters, tab: newTab };

    params[`${toP}search`] = props.filters[`${fromP}search`] || "";
    params[`${toP}course_id`] = props.filters[`${fromP}course_id`] || "";
    params[`${toP}module_status`] = props.filters[`${fromP}module_status`] || "all";

    params[`${toP}lesson_date_operator`] = props.filters[`${fromP}lesson_date_operator`] || "";
    params[`${toP}lesson_date`] = props.filters[`${fromP}lesson_date`] || "";
    params[`${toP}lesson_date_end`] = props.filters[`${fromP}lesson_date_end`] || "";

    params[`${toP}created_at_operator`] = props.filters[`${fromP}created_at_operator`] || "";
    params[`${toP}created_at_date`] = props.filters[`${fromP}created_at_date`] || "";
    params[`${toP}created_at_date_end`] = props.filters[`${fromP}created_at_date_end`] || "";

    params[`${toP}birthday_operator`] = props.filters[`${fromP}birthday_operator`] || "";
    params[`${toP}birthday_day`] = props.filters[`${fromP}birthday_day`] || "";
    params[`${toP}birthday_month`] = props.filters[`${fromP}birthday_month`] || "";
    params[`${toP}birthday_year`] = props.filters[`${fromP}birthday_year`] || "";
    params[`${toP}birthday_end_day`] = props.filters[`${fromP}birthday_end_day`] || "";
    params[`${toP}birthday_end_month`] = props.filters[`${fromP}birthday_end_month`] || "";
    params[`${toP}birthday_end_year`] = props.filters[`${fromP}birthday_end_year`] || "";

    navigateWithFilters(params);
};

// Application des filtres avec miroir automatique sur les 2 onglets
const handleApplyFilters = (newFilters) => {
    const currentP = activeTab.value === "users" ? "users_" : "attendees_";
    const mirrorP = activeTab.value === "users" ? "attendees_" : "users_";

    newFilters[`${currentP}page`] = 1;
    newFilters[`${mirrorP}page`] = 1;

    newFilters[`${mirrorP}search`] = newFilters[`${currentP}search`] || "";
    newFilters[`${mirrorP}course_id`] = newFilters[`${currentP}course_id`] || "";
    newFilters[`${mirrorP}module_status`] = newFilters[`${currentP}module_status`] || "all";
    newFilters[`${mirrorP}lesson_date_operator`] = newFilters[`${currentP}lesson_date_operator`] || "";
    newFilters[`${mirrorP}lesson_date`] = newFilters[`${currentP}lesson_date`] || "";
    newFilters[`${mirrorP}lesson_date_end`] = newFilters[`${currentP}lesson_date_end`] || "";
    newFilters[`${mirrorP}created_at_operator`] = newFilters[`${currentP}created_at_operator`] || "";
    newFilters[`${mirrorP}created_at_date`] = newFilters[`${currentP}created_at_date`] || "";
    newFilters[`${mirrorP}created_at_date_end`] = newFilters[`${currentP}created_at_date_end`] || "";
    newFilters[`${mirrorP}birthday_operator`] = newFilters[`${currentP}birthday_operator`] || "";
    newFilters[`${mirrorP}birthday_day`] = newFilters[`${currentP}birthday_day`] || "";
    newFilters[`${mirrorP}birthday_month`] = newFilters[`${currentP}birthday_month`] || "";
    newFilters[`${mirrorP}birthday_year`] = newFilters[`${currentP}birthday_year`] || "";
    newFilters[`${mirrorP}birthday_end_day`] = newFilters[`${currentP}birthday_end_day`] || "";
    newFilters[`${mirrorP}birthday_end_month`] = newFilters[`${currentP}birthday_end_month`] || "";
    newFilters[`${mirrorP}birthday_end_year`] = newFilters[`${currentP}birthday_end_year`] || "";

    navigateWithFilters(newFilters);
};

// Réinitialisation simultanée des 2 onglets
const handleResetFilters = () => {
    selectedUserIds.value = [];
    selectAllMatchingUsers.value = false;
    selectedAttendeeIds.value = [];
    selectAllMatchingAttendees.value = false;

    navigateWithFilters({
        tab: activeTab.value,
        users_page: 1,
        users_search: "",
        users_search_filters: ["last_name", "first_name", "email", "phone_number"],
        users_course_id: "",
        users_module_status: "all",
        users_lesson_date_operator: "",
        users_lesson_date: "",
        users_lesson_date_end: "",
        users_created_at_operator: "",
        users_created_at_date: "",
        users_created_at_date_end: "",
        users_birthday_operator: "",
        users_birthday_day: "",
        users_birthday_month: "",
        users_birthday_year: "",
        users_birthday_end_day: "",
        users_birthday_end_month: "",
        users_birthday_end_year: "",

        attendees_page: 1,
        attendees_search: "",
        attendees_search_filters: ["last_name", "first_name", "user_name"],
        attendees_course_id: "",
        attendees_module_status: "all",
        attendees_lesson_date_operator: "",
        attendees_lesson_date: "",
        attendees_lesson_date_end: "",
        attendees_created_at_operator: "",
        attendees_created_at_date: "",
        attendees_created_at_date_end: "",
        attendees_birthday_operator: "",
        attendees_birthday_day: "",
        attendees_birthday_month: "",
        attendees_birthday_year: "",
        attendees_birthday_end_day: "",
        attendees_birthday_end_month: "",
        attendees_birthday_end_year: "",
    });
};

// Pagination & Tri Membres
const handleUserPageChange = (page) => navigateWithFilters({ users_page: page });
const handleUserPerPageChange = (perPage) => navigateWithFilters({ users_perPage: perPage, users_page: 1 });
const handleUserSortChange = ({ field, direction }) => {
    navigateWithFilters({ users_sortField: field, users_sortDirection: direction, users_page: 1 });
};

// Pagination & Tri Invités
const handleAttendeePageChange = (page) => navigateWithFilters({ attendees_page: page });
const handleAttendeePerPageChange = (perPage) => navigateWithFilters({ attendees_perPage: perPage, attendees_page: 1 });
const handleAttendeeSortChange = ({ field, direction }) => {
    navigateWithFilters({ attendees_sortField: field, attendees_sortDirection: direction, attendees_page: 1 });
};

// 2. EXPORT CSV STREAMING
const handleExportCsv = () => {
    const isUsers = activeTab.value === "users";
    const selected = isUsers ? selectedUserIds.value : selectedAttendeeIds.value;
    const selectAll = isUsers ? selectAllMatchingUsers.value : selectAllMatchingAttendees.value;

    const url = new URL(route("users.export-csv"), window.location.origin);
    url.searchParams.set("type", activeTab.value);

    if (selectAll || selected.length === 0) {
        url.searchParams.set("select_all_matching", "true");
        Object.entries(props.filters).forEach(([key, val]) => {
            if (val !== null && val !== "" && !Array.isArray(val)) {
                url.searchParams.set(`filters[${key}]`, val);
            }
        });
    } else {
        selected.forEach((id) => url.searchParams.append("selected_ids[]", id));
    }

    window.location.href = url.toString();
    toast.success("Export CSV démarré");
};

// 3. TIROIR D'E-MAILS GROUPÉS
const isEmailSheetOpen = ref(false);
const emailRecipients = ref([]);

const handleOpenEmptyEmailSheet = () => {
    emailRecipients.value = [];
    selectAllMatchingUsers.value = false;
    isEmailSheetOpen.value = true;
};

const handleEmailSingleUser = (user) => {
    emailRecipients.value = [{
        id: user.id,
        full_name: user.full_name,
        first_name: user.first_name,
        last_name: user.last_name,
        email: user.email,
        phone_number: user.phone_number,
        is_custom: false,
    }];
    isEmailSheetOpen.value = true;
};

const handleOpenBulkEmail = () => {
    if (activeTab.value !== "users") return;

    if (selectAllMatchingUsers.value) {
        emailRecipients.value = props.users.map((u) => ({
            id: u.id,
            full_name: u.full_name,
            first_name: u.first_name,
            last_name: u.last_name,
            email: u.email,
            phone_number: u.phone_number,
            is_custom: false,
        }));
    } else {
        emailRecipients.value = props.users
            .filter((u) => selectedUserIds.value.map(Number).includes(Number(u.id)))
            .map((u) => ({
                id: u.id,
                full_name: u.full_name,
                first_name: u.first_name,
                last_name: u.last_name,
                email: u.email,
                phone_number: u.phone_number,
                is_custom: false,
            }));
    }

    isEmailSheetOpen.value = true;
};

// 4. SUPPRESSION GROUPÉE
const isDeleteDialogOpen = ref(false);
const isDeleting = ref(false);
const deleteTarget = ref({ type: "users", ids: [], singleName: null });

const handlePromptDeleteUser = (user) => {
    deleteTarget.value = { type: "users", ids: [user.id], singleName: user.full_name };
    isDeleteDialogOpen.value = true;
};

const handlePromptDeleteAttendee = (att) => {
    deleteTarget.value = { type: "attendees", ids: [att.id], singleName: att.full_name };
    isDeleteDialogOpen.value = true;
};

const handlePromptBulkDelete = () => {
    const ids = activeTab.value === "users" ? selectedUserIds.value : selectedAttendeeIds.value;
    deleteTarget.value = { type: activeTab.value, ids, singleName: null };
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (deleteTarget.value.ids.length === 0) return;

    isDeleting.value = true;
    router.delete(route("users.bulk-delete"), {
        data: {
            type: deleteTarget.value.type,
            ids: deleteTarget.value.ids,
        },
        preserveScroll: true,
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            if (deleteTarget.value.type === "users") {
                selectedUserIds.value = selectedUserIds.value.filter((id) => !deleteTarget.value.ids.map(Number).includes(Number(id)));
                selectAllMatchingUsers.value = false;
            } else {
                selectedAttendeeIds.value = selectedAttendeeIds.value.filter((id) => !deleteTarget.value.ids.map(Number).includes(Number(id)));
                selectAllMatchingAttendees.value = false;
            }
            toast.success("Suppression validée");
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const clearCurrentSelection = () => {
    if (activeTab.value === "users") {
        selectedUserIds.value = [];
        selectAllMatchingUsers.value = false;
    } else {
        selectedAttendeeIds.value = [];
        selectAllMatchingAttendees.value = false;
    }
};
</script>

<template>
    <AdminLayout title="Répertoire des membres">
        <div class="space-y-6 relative pb-20">
            <!-- ========================================================= -->
            <!-- 1. BREADCRUMBS & EN-TÊTE DE PAGE                          -->
            <!-- ========================================================= -->
            <div class="space-y-3">
                <nav class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Link :href="route('dashboard.index')" class="hover:text-foreground transition-colors">
                        Tableau de bord
                    </Link>
                    <ChevronRight class="h-3.5 w-3.5 text-muted-foreground/60" />
                    <span class="font-semibold text-foreground">Répertoire des membres</span>
                </nav>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-foreground">
                            Répertoire des membres & invités
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Consultez les fiches membres, suivez les inscriptions aux cours et communiquez par e-mail.
                        </p>
                    </div>

                    <!-- Actions globales en haut à droite -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <Button
                            variant="default"
                            size="sm"
                            class="gap-1.5 shadow-2xs text-xs font-semibold cursor-pointer"
                            @click="handleOpenEmptyEmailSheet"
                        >
                            <Mail class="h-3.5 w-3.5" />
                            <span>Rédiger un e-mail</span>
                        </Button>

                        <!-- Bouton CSV dynamique -->
                        <Button
                            variant="outline"
                            size="sm"
                            class="gap-1.5 shadow-2xs text-xs font-semibold cursor-pointer"
                            @click="handleExportCsv"
                        >
                            <Download class="h-3.5 w-3.5" />
                            <span>{{ csvExportButtonText }}</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 2. ONGLETS MEMBRES / INVITÉS                              -->
            <!-- ========================================================= -->
            <Tabs :model-value="activeTab" class="w-full space-y-4" @update:model-value="handleTabChange">
                <TabsList class="grid w-full sm:w-auto grid-cols-2 sm:inline-flex h-9 bg-muted/60 p-1">
                    <TabsTrigger value="users" class="text-xs gap-1.5 px-4 font-semibold cursor-pointer">
                        <Users class="h-3.5 w-3.5" />
                        <span>Membres</span>
                        <Badge variant="secondary" class="text-[10px] px-1.5 py-0 rounded-full font-normal">
                            {{ usersPagination.total }}
                        </Badge>
                    </TabsTrigger>

                    <TabsTrigger value="attendees" class="text-xs gap-1.5 px-4 font-semibold cursor-pointer">
                        <UserCheck class="h-3.5 w-3.5" />
                        <span>Invités</span>
                        <Badge variant="secondary" class="text-[10px] px-1.5 py-0 rounded-full font-normal">
                            {{ attendeesPagination.total }}
                        </Badge>
                    </TabsTrigger>
                </TabsList>

                <!-- ========================================================= -->
                <!-- 3. PANNEAU DE FILTRES MULTI-CRITÈRES                      -->
                <!-- ========================================================= -->
                <UserSearchFilters
                    :active-tab="activeTab"
                    :filters="filters"
                    :courses="courses"
                    @apply="handleApplyFilters"
                    @reset="handleResetFilters"
                />

                <!-- ========================================================= -->
                <!-- 4. TABLEAU DES MEMBRES (USERS)                            -->
                <!-- ========================================================= -->
                <TabsContent value="users" class="space-y-4 mt-0">
                    <UserTable
                        :users="users"
                        :pagination="usersPagination"
                        :sorting="{ field: filters.users_sortField || 'last_name', direction: filters.users_sortDirection || 'asc' }"
                        :selected-ids="selectedUserIds"
                        :select-all-matching="selectAllMatchingUsers"
                        @update:selected-ids="handleUpdateSelectedUserIds"
                        @update:selectedIds="handleUpdateSelectedUserIds"
                        @update:select-all-matching="handleUpdateSelectAllMatchingUsers"
                        @update:selectAllMatching="handleUpdateSelectAllMatchingUsers"
                        @sort-change="handleUserSortChange"
                        @page-change="handleUserPageChange"
                        @per-page-change="handleUserPerPageChange"
                        @email-user="handleEmailSingleUser"
                        @delete-user="handlePromptDeleteUser"
                    />
                </TabsContent>

                <!-- ========================================================= -->
                <!-- 5. TABLEAU DES INVITÉS (ATTENDEES)                       -->
                <!-- ========================================================= -->
                <TabsContent value="attendees" class="space-y-4 mt-0">
                    <AttendeeTable
                        :attendees="attendees"
                        :pagination="attendeesPagination"
                        :sorting="{ field: filters.attendees_sortField || 'last_name', direction: filters.attendees_sortDirection || 'asc' }"
                        :selected-ids="selectedAttendeeIds"
                        :select-all-matching="selectAllMatchingAttendees"
                        @update:selected-ids="handleUpdateSelectedAttendeeIds"
                        @update:selectedIds="handleUpdateSelectedAttendeeIds"
                        @update:select-all-matching="handleUpdateSelectAllMatchingAttendees"
                        @update:selectAllMatching="handleUpdateSelectAllMatchingAttendees"
                        @sort-change="handleAttendeeSortChange"
                        @page-change="handleAttendeePageChange"
                        @per-page-change="handleAttendeePerPageChange"
                        @delete-attendee="handlePromptDeleteAttendee"
                    />
                </TabsContent>
            </Tabs>

            <!-- ========================================================= -->
            <!-- 6. BARRE D'ACTIONS FLOTTANTE (QUAND SÉLECTION > 0)        -->
            <!-- ========================================================= -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform translate-y-8 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform translate-y-8 opacity-0"
            >
                <div
                    v-if="selectedCount > 0"
                    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-foreground text-background dark:bg-card dark:text-card-foreground border border-border/80 shadow-2xl rounded-full px-4 py-2 flex items-center gap-3 text-xs font-medium"
                >
                    <div class="flex items-center gap-1.5 pl-1 border-r border-background/20 dark:border-border pr-3">
                        <span class="font-bold">
                            {{ selectAllMatchingUsers || selectAllMatchingAttendees ? `Tous les ${selectedCount}` : selectedCount }}
                        </span>
                        <span>{{ pluralize(selectedCount, activeTab === 'users' ? 'membre' : 'invité') }}</span>
                    </div>

                    <!-- Rédiger e-mail (Membres uniquement) -->
                    <Button
                        v-if="activeTab === 'users'"
                        type="button"
                        size="sm"
                        variant="secondary"
                        class="h-7 text-xs gap-1.5 rounded-full px-3 shadow-xs cursor-pointer"
                        @click="handleOpenBulkEmail"
                    >
                        <Mail class="h-3 w-3" />
                        <span>Rédiger un e-mail</span>
                    </Button>

                    <!-- Exporter CSV de la sélection -->
                    <Button
                        type="button"
                        size="sm"
                        variant="secondary"
                        class="h-7 text-xs gap-1.5 rounded-full px-3 shadow-xs cursor-pointer"
                        @click="handleExportCsv"
                    >
                        <Download class="h-3 w-3" />
                        <span>Exporter CSV</span>
                    </Button>

                    <!-- Supprimer sélection -->
                    <Button
                        type="button"
                        size="sm"
                        variant="destructive"
                        class="h-7 text-xs gap-1.5 rounded-full px-3 cursor-pointer"
                        @click="handlePromptBulkDelete"
                    >
                        <Trash2 class="h-3 w-3" />
                        <span>Supprimer</span>
                    </Button>

                    <!-- Bouton Annuler la sélection -->
                    <button
                        type="button"
                        class="h-6 w-6 rounded-full flex items-center justify-center hover:bg-background/20 transition-colors ml-1 cursor-pointer"
                        title="Désélectionner tout"
                        @click="clearCurrentSelection"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>
            </transition>

            <!-- ========================================================= -->
            <!-- 7. TIROIR LATÉRAL D'E-MAIL GROUPÉ                        -->
            <!-- ========================================================= -->
            <UserEmailSheet
                v-model:open="isEmailSheetOpen"
                :recipients="emailRecipients"
                :select-all-matching="selectAllMatchingUsers"
                :filters="filters"
                :total-matching-count="usersPagination.total"
                @sent="clearCurrentSelection"
            />

            <!-- ========================================================= -->
            <!-- 8. MODALE DE SUPPRESSION                                  -->
            <!-- ========================================================= -->
            <Dialog :open="isDeleteDialogOpen" @update:open="(val) => (isDeleteDialogOpen = val)">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2 text-destructive">
                            <AlertTriangle class="h-5 w-5" />
                            <span>Confirmer la suppression</span>
                        </DialogTitle>
                        <DialogDescription class="text-xs pt-2">
                            <template v-if="deleteTarget.singleName">
                                Êtes-vous sûr de vouloir supprimer définitivement
                                <strong class="text-foreground">« {{ deleteTarget.singleName }} »</strong> ?
                            </template>
                            <template v-else>
                                Êtes-vous sûr de vouloir supprimer définitivement les
                                <strong class="text-foreground">{{ deleteTarget.ids.length }}</strong> éléments sélectionnés ?
                            </template>
                            Cette action est irréversible.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter class="gap-2 sm:gap-0 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="isDeleteDialogOpen = false"
                        >
                            Annuler
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            size="sm"
                            :disabled="isDeleting"
                            class="gap-1.5 font-semibold cursor-pointer"
                            @click="executeDelete"
                        >
                            <Loader2 v-if="isDeleting" class="h-4 w-4 animate-spin" />
                            <Trash2 v-else class="h-4 w-4" />
                            <span>Confirmer la suppression</span>
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
