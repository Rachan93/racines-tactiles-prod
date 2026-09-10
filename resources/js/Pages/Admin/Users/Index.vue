<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { pluralize } from "@/Utils/formatters";
import AdminLayout from "@/Layouts/AdminLayout.vue";

import UserSearchFilters from "@/Components/admin/users/UserSearchFilters.vue";
import UserTable from "@/Components/admin/users/UserTable.vue";
import AttendeeTable from "@/Components/admin/users/AttendeeTable.vue";
import UserEmailSheet from "@/Components/admin/users/UserEmailSheet.vue";

import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/Components/ui/tabs";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Checkbox } from "@/Components/ui/checkbox";
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
    User,
    Users,
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

const activeTab = ref(props.filters.tab || "users");

const selectedUserIds = ref([]);
const selectAllMatchingUsers = ref(false);

const selectedAttendeeIds = ref([]);
const selectAllMatchingAttendees = ref(false);

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

const selectedCount = computed(() => {
    if (activeTab.value === "users") {
        return selectAllMatchingUsers.value
            ? Number(props.usersPagination.total)
            : selectedUserIds.value.length;
    }
    return selectAllMatchingAttendees.value
        ? Number(props.attendeesPagination.total)
        : selectedAttendeeIds.value.length;
});

const csvExportButtonText = computed(() => {
    if (activeTab.value === "users") {
        const total = props.usersPagination.total;

        return total === 1
            ? "Exporter le membre en CSV"
            : `Exporter les ${pluralize(total, "membre")} en CSV`;
    }

    const total = props.attendeesPagination.total;

    return total === 1
        ? "Exporter l’invité en CSV"
        : `Exporter les ${pluralize(total, "invité")} en CSV`;
});

const navigateWithFilters = (customParams = {}) => {
    const merged = { ...props.filters, ...customParams };
    router.get(route("users.index"), merged, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const handleTabChange = (newTab) => {
    activeTab.value = newTab;
    const fromP = newTab === "attendees" ? "users_" : "attendees_";
    const toP = newTab === "attendees" ? "attendees_" : "users_";

    const params = { ...props.filters, tab: newTab };

    params[`${toP}search`] = props.filters[`${fromP}search`] || "";
    params[`${toP}course_id`] = props.filters[`${fromP}course_id`] || "";
    params[`${toP}module_status`] =
        props.filters[`${fromP}module_status`] || "all";

    params[`${toP}lesson_date_operator`] =
        props.filters[`${fromP}lesson_date_operator`] || "";
    params[`${toP}lesson_date`] = props.filters[`${fromP}lesson_date`] || "";
    params[`${toP}lesson_date_end`] =
        props.filters[`${fromP}lesson_date_end`] || "";

    params[`${toP}created_at_operator`] =
        props.filters[`${fromP}created_at_operator`] || "";
    params[`${toP}created_at_date`] =
        props.filters[`${fromP}created_at_date`] || "";
    params[`${toP}created_at_date_end`] =
        props.filters[`${fromP}created_at_date_end`] || "";

    params[`${toP}birthday_operator`] =
        props.filters[`${fromP}birthday_operator`] || "";
    params[`${toP}birthday_day`] = props.filters[`${fromP}birthday_day`] || "";
    params[`${toP}birthday_month`] =
        props.filters[`${fromP}birthday_month`] || "";
    params[`${toP}birthday_year`] =
        props.filters[`${fromP}birthday_year`] || "";
    params[`${toP}birthday_end_day`] =
        props.filters[`${fromP}birthday_end_day`] || "";
    params[`${toP}birthday_end_month`] =
        props.filters[`${fromP}birthday_end_month`] || "";
    params[`${toP}birthday_end_year`] =
        props.filters[`${fromP}birthday_end_year`] || "";

    navigateWithFilters(params);
};

const handleApplyFilters = (newFilters) => {
    const currentP = activeTab.value === "users" ? "users_" : "attendees_";
    const mirrorP = activeTab.value === "users" ? "attendees_" : "users_";

    newFilters[`${currentP}page`] = 1;
    newFilters[`${mirrorP}page`] = 1;

    newFilters[`${mirrorP}search`] = newFilters[`${currentP}search`] || "";
    newFilters[`${mirrorP}course_id`] =
        newFilters[`${currentP}course_id`] || "";
    newFilters[`${mirrorP}module_status`] =
        newFilters[`${currentP}module_status`] || "all";
    newFilters[`${mirrorP}lesson_date_operator`] =
        newFilters[`${currentP}lesson_date_operator`] || "";
    newFilters[`${mirrorP}lesson_date`] =
        newFilters[`${currentP}lesson_date`] || "";
    newFilters[`${mirrorP}lesson_date_end`] =
        newFilters[`${currentP}lesson_date_end`] || "";
    newFilters[`${mirrorP}created_at_operator`] =
        newFilters[`${currentP}created_at_operator`] || "";
    newFilters[`${mirrorP}created_at_date`] =
        newFilters[`${currentP}created_at_date`] || "";
    newFilters[`${mirrorP}created_at_date_end`] =
        newFilters[`${currentP}created_at_date_end`] || "";
    newFilters[`${mirrorP}birthday_operator`] =
        newFilters[`${currentP}birthday_operator`] || "";
    newFilters[`${mirrorP}birthday_day`] =
        newFilters[`${currentP}birthday_day`] || "";
    newFilters[`${mirrorP}birthday_month`] =
        newFilters[`${currentP}birthday_month`] || "";
    newFilters[`${mirrorP}birthday_year`] =
        newFilters[`${currentP}birthday_year`] || "";
    newFilters[`${mirrorP}birthday_end_day`] =
        newFilters[`${currentP}birthday_end_day`] || "";
    newFilters[`${mirrorP}birthday_end_month`] =
        newFilters[`${currentP}birthday_end_month`] || "";
    newFilters[`${mirrorP}birthday_end_year`] =
        newFilters[`${currentP}birthday_end_year`] || "";

    navigateWithFilters(newFilters);
};

const handleResetFilters = () => {
    selectedUserIds.value = [];
    selectAllMatchingUsers.value = false;
    selectedAttendeeIds.value = [];
    selectAllMatchingAttendees.value = false;

    navigateWithFilters({
        tab: activeTab.value,
        users_page: 1,
        users_search: "",
        users_search_filters: [
            "last_name",
            "first_name",
            "email",
            "phone_number",
        ],
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

const handleUserPageChange = (page) =>
    navigateWithFilters({ users_page: page });
const handleUserPerPageChange = (perPage) =>
    navigateWithFilters({ users_perPage: perPage, users_page: 1 });
const handleUserSortChange = ({ field, direction }) => {
    navigateWithFilters({
        users_sortField: field,
        users_sortDirection: direction,
        users_page: 1,
    });
};

const handleAttendeePageChange = (page) =>
    navigateWithFilters({ attendees_page: page });
const handleAttendeePerPageChange = (perPage) =>
    navigateWithFilters({ attendees_perPage: perPage, attendees_page: 1 });
const handleAttendeeSortChange = ({ field, direction }) => {
    navigateWithFilters({
        attendees_sortField: field,
        attendees_sortDirection: direction,
        attendees_page: 1,
    });
};

const handleExportCsv = () => {
    const isUsers = activeTab.value === "users";
    const selected = isUsers
        ? selectedUserIds.value
        : selectedAttendeeIds.value;
    const selectAll = isUsers
        ? selectAllMatchingUsers.value
        : selectAllMatchingAttendees.value;

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

const isEmailSheetOpen = ref(false);
const emailRecipients = ref([]);

const handleOpenEmptyEmailSheet = () => {
    emailRecipients.value = [];
    selectAllMatchingUsers.value = false;
    isEmailSheetOpen.value = true;
};

const handleEmailSingleUser = (user) => {
    emailRecipients.value = [
        {
            id: user.id,
            full_name: user.full_name,
            first_name: user.first_name,
            last_name: user.last_name,
            email: user.email,
            phone_number: user.phone_number,
            is_custom: false,
        },
    ];
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
            .filter((u) =>
                selectedUserIds.value.map(Number).includes(Number(u.id)),
            )
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

const isDeleteDialogOpen = ref(false);
const isDeleting = ref(false);
const deleteTarget = ref({ type: "users", ids: [], singleName: null });

const handlePromptDeleteUser = (user) => {
    deleteTarget.value = {
        type: "users",
        ids: [user.id],
        singleName: user.full_name,
    };
    isDeleteDialogOpen.value = true;
};

const handlePromptDeleteAttendee = (att) => {
    deleteTarget.value = {
        type: "attendees",
        ids: [att.id],
        singleName: att.full_name,
    };
    isDeleteDialogOpen.value = true;
};

const handlePromptBulkDelete = () => {
    const isUsers = activeTab.value === "users";

    const ids = isUsers ? selectedUserIds.value : selectedAttendeeIds.value;

    let singleName = null;

    if (ids.length === 1) {
        const items = isUsers ? props.users : props.attendees;

        const item = items.find((item) => Number(item.id) === Number(ids[0]));

        singleName = item?.full_name || null;
    }

    deleteTarget.value = {
        type: activeTab.value,
        ids,
        singleName,
    };

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
            const deletedCount = deleteTarget.value.ids.length;
            const deletedName = deleteTarget.value.singleName;

            isDeleteDialogOpen.value = false;

            if (deleteTarget.value.type === "users") {
                selectedUserIds.value = selectedUserIds.value.filter(
                    (id) =>
                        !deleteTarget.value.ids
                            .map(Number)
                            .includes(Number(id)),
                );

                selectAllMatchingUsers.value = false;

                toast.success(
                    deletedCount === 1 && deletedName
                        ? `Suppression de ${deletedName} validée`
                        : `Suppression des ${deletedCount} membres validée`,
                );
            } else {
                selectedAttendeeIds.value = selectedAttendeeIds.value.filter(
                    (id) =>
                        !deleteTarget.value.ids
                            .map(Number)
                            .includes(Number(id)),
                );

                selectAllMatchingAttendees.value = false;

                toast.success(
                    deletedCount === 1 && deletedName
                        ? `Suppression de ${deletedName} validée`
                        : `Suppression des ${deletedCount} invités validée`,
                );
            }
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

const deleteConfirmed = ref(false);
</script>

<template>
    <AdminLayout title="Répertoire des utilisateurs">
        <div class="space-y-6 relative pb-20">
            <!-- ========================================================= -->
            <!-- 1. BREADCRUMBS & EN-TÊTE DE PAGE                          -->
            <!-- ========================================================= -->
            <div class="space-y-3">
                <nav
                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <Link
                        :href="route('dashboard.index')"
                        class="hover:text-foreground transition-colors"
                    >
                        Tableau de bord
                    </Link>
                    <ChevronRight
                        class="h-3.5 w-3.5 text-muted-foreground/60"
                    />
                    <span class="font-semibold text-foreground"
                        >Répertoire des utilisateurs</span
                    >
                </nav>

                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                >
                    <div>
                        <h2
                            class="text-2xl font-bold tracking-tight text-foreground"
                        >
                            Membres et invités
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Consultez les fiches des utilisateurs et et
                            communiquez par e-mail.
                        </p>
                    </div>

                    <!-- Actions globales en haut à droite -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <Button
                            variant="outline"
                            size="sm"
                            class="gap-1.5 shadow-2xs text-xs font-semibold cursor-pointer"
                            @click="handleExportCsv"
                        >
                            <Download class="h-3.5 w-3.5" />
                            <span>{{ csvExportButtonText }}</span>
                        </Button>
                        <Button
                            variant="default"
                            size="sm"
                            class="gap-1.5 shadow-2xs text-xs font-semibold cursor-pointer"
                            @click="handleOpenEmptyEmailSheet"
                        >
                            <Mail class="h-3.5 w-3.5" />
                            <span>Rédiger un e-mail</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 2. ONGLETS MEMBRES / INVITÉS                              -->
            <!-- ========================================================= -->
            <Tabs
                :model-value="activeTab"
                class="w-full space-y-4"
                @update:model-value="handleTabChange"
            >
                <TabsList
                    class="grid h-auto w-full grid-cols-2 justify-start rounded-none border-b border-border bg-transparent p-0 sm:flex sm:w-auto"
                >
                    <TabsTrigger
                        value="users"
                        class="group relative gap-2 rounded-none border-b-2 border-transparent bg-transparent px-4 py-3 text-sm font-medium text-muted-foreground shadow-none transition-colors hover:text-foreground data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground data-[state=active]:shadow-none"
                    >
                        <User
                            class="h-4 w-4 transition-colors group-data-[state=active]:text-primary"
                        />

                        <span>Membres</span>

                        <span
                            class="ml-2 inline-flex min-w-5 items-center justify-center rounded-full bg-muted px-1.5 py-0.5 text-[10px] font-semibold leading-none text-muted-foreground transition-colors group-data-[state=active]:bg-primary/10 group-data-[state=active]:text-primary"
                        >
                            {{ usersPagination.total }}
                        </span>
                    </TabsTrigger>

                    <TabsTrigger
                        value="attendees"
                        class="group relative gap-2 rounded-none border-b-2 border-transparent bg-transparent px-4 py-3 text-sm font-medium text-muted-foreground shadow-none transition-colors hover:text-foreground data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground data-[state=active]:shadow-none"
                    >
                        <Users
                            class="h-4 w-4 transition-colors group-data-[state=active]:text-primary"
                        />

                        <span>Invités</span>

                        <span
                            class="ml-2 inline-flex min-w-5 items-center justify-center rounded-full bg-muted px-1.5 py-0.5 text-[10px] font-semibold leading-none text-muted-foreground transition-colors group-data-[state=active]:bg-primary/10 group-data-[state=active]:text-primary"
                        >
                            {{ attendeesPagination.total }}
                        </span>
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
                        :sorting="{
                            field: filters.users_sortField || 'last_name',
                            direction: filters.users_sortDirection || 'asc',
                        }"
                        :selected-ids="selectedUserIds"
                        :select-all-matching="selectAllMatchingUsers"
                        @update:selected-ids="handleUpdateSelectedUserIds"
                        @update:selectedIds="handleUpdateSelectedUserIds"
                        @update:select-all-matching="
                            handleUpdateSelectAllMatchingUsers
                        "
                        @update:selectAllMatching="
                            handleUpdateSelectAllMatchingUsers
                        "
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
                        :sorting="{
                            field: filters.attendees_sortField || 'last_name',
                            direction: filters.attendees_sortDirection || 'asc',
                        }"
                        :selected-ids="selectedAttendeeIds"
                        :select-all-matching="selectAllMatchingAttendees"
                        @update:selected-ids="handleUpdateSelectedAttendeeIds"
                        @update:selectedIds="handleUpdateSelectedAttendeeIds"
                        @update:select-all-matching="
                            handleUpdateSelectAllMatchingAttendees
                        "
                        @update:selectAllMatching="
                            handleUpdateSelectAllMatchingAttendees
                        "
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
                    <div
                        class="flex items-center gap-1.5 pl-1 border-r border-background/20 dark:border-border pr-3"
                    >
                        <span class="font-bold">
                            {{
                                selectAllMatchingUsers ||
                                selectAllMatchingAttendees
                                    ? `Tous les ${pluralize(
                                          selectedCount,
                                          activeTab === "users"
                                              ? "membre"
                                              : "invité",
                                      )}`
                                    : pluralize(
                                          selectedCount,
                                          activeTab === "users"
                                              ? "membre"
                                              : "invité",
                                      )
                            }}
                        </span>
                    </div>

                    <!-- Envoyer un e-mail (Membres uniquement) -->
                    <Button
                        v-if="activeTab === 'users'"
                        type="button"
                        size="sm"
                        class="h-7 text-xs gap-1.5 rounded-full px-3 shadow-xs cursor-pointer bg-primary text-primary-foreground hover:bg-primary/90"
                        @click="handleOpenBulkEmail"
                    >
                        <Mail class="h-3 w-3" />
                        <span>Envoyer un e-mail</span>
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
            <Dialog
                :open="isDeleteDialogOpen"
                @update:open="
                    (val) => {
                        isDeleteDialogOpen = val;

                        if (!val) {
                            deleteConfirmed = false;
                        }
                    }
                "
            >
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle
                            class="flex items-center gap-2 text-destructive"
                        >
                            <AlertTriangle class="h-5 w-5" />
                            <span>Confirmer la suppression</span>
                        </DialogTitle>

                        <DialogDescription class="text-xs pt-2">
                            <template v-if="deleteTarget.singleName">
                                Êtes-vous sûr de vouloir supprimer
                                définitivement
                                <strong class="text-foreground">
                                    « {{ deleteTarget.singleName }} »
                                </strong>
                                ?
                            </template>

                            <template v-else>
                                Êtes-vous sûr de vouloir supprimer
                                définitivement
                                <strong class="text-foreground">
                                    {{
                                        pluralize(
                                            selectedCount,
                                            activeTab === "users"
                                                ? "membre"
                                                : "invité",
                                        )
                                    }}
                                </strong>
                                sélectionné{{ selectedCount > 1 ? "s" : "" }} ?
                            </template>

                            Cette action est irréversible.
                        </DialogDescription>
                    </DialogHeader>

                    <!-- Confirmation explicite -->
                    <label
                        class="flex items-start gap-2.5 rounded-lg border p-3 cursor-pointer select-none"
                    >
                        <Checkbox
                            v-model="deleteConfirmed"
                            class="mt-0.5 cursor-pointer"
                        />

                        <span class="text-xs leading-5 text-foreground">
                            Je confirme vouloir supprimer définitivement
                            <template v-if="deleteTarget.singleName">
                                ce membre
                            </template>

                            <template v-else>
                                les
                                {{
                                    pluralize(
                                        selectedCount,
                                        activeTab === "users"
                                            ? "membre"
                                            : "invité",
                                    )
                                }}
                                sélectionné{{
                                    selectedCount > 1 ? "s" : ""
                                }} </template
                            >.
                        </span>
                    </label>
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
                            :disabled="isDeleting || !deleteConfirmed"
                            class="gap-1.5 font-semibold cursor-pointer ml-2"
                            @click="executeDelete"
                        >
                            <Loader2
                                v-if="isDeleting"
                                class="h-4 w-4 animate-spin"
                            />
                            <Trash2 v-else class="h-4 w-4" />

                            <span>Confirmer la suppression</span>
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
