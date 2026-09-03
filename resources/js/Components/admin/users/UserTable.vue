<script setup>
import { ref, computed, onMounted } from "vue";
import { Link } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { pluralize } from "@/Utils/formatters";

// Composants Shadcn UI
import {
    Table,
    TableHeader,
    TableBody,
    TableHead,
    TableRow,
    TableCell,
} from "@/Components/ui/table";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
     DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/Components/ui/dialog";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import {
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    ChevronLeft,
    ChevronRight,
    MoreHorizontal,
    User as UserIcon,
    Mail,
    Phone,
    Copy,
    Check,
    Minus,
    Trash2,
    BookOpen,
    Users,
    Settings2,
    Cake,
    Calendar,
} from "lucide-vue-next";

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    pagination: {
        type: Object,
        default: () => ({ page: 1, perPage: 25, total: 0, lastPage: 1 }),
    },
    sorting: {
        type: Object,
        default: () => ({ field: "last_name", direction: "asc" }),
    },
    selectedIds: {
        type: Array,
        default: () => [],
    },
    selectAllMatching: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    "update:selected-ids",
    "update:selectedIds",
    "update:select-all-matching",
    "update:selectAllMatching",
    "sort-change",
    "page-change",
    "per-page-change",
    "email-user",
    "delete-user",
]);

// 1. VISIBILITÉ DES COLONNES AVEC COCHE À GAUCHE
const defaultColumns = {
    name: { label: "Membre", visible: true, locked: true },
    email: { label: "Email", visible: true },
    phone_number: { label: "Téléphone", visible: true },
    locality: { label: "Localité", visible: true },
    address: { label: "Adresse", visible: false },
    birthday: { label: "Date d'anniversaire", visible: false },
    company_name: { label: "Société", visible: false },
    company_address: { label: "Adresse société", visible: false },
    vat_number: { label: "N° TVA", visible: false },
    modules: { label: "Modules", visible: true },
    attendees: { label: "Invités", visible: true },
    created_at: { label: "Date d'inscription", visible: true },
};

const columns = ref(JSON.parse(JSON.stringify(defaultColumns)));

onMounted(() => {
    try {
        const saved = localStorage.getItem("admin_users_columns_visibility");
        if (saved) {
            const parsed = JSON.parse(saved);
            if (parsed && typeof parsed === "object") {
                Object.keys(defaultColumns).forEach((key) => {
                    if (typeof parsed[key] === "boolean" && !defaultColumns[key].locked) {
                        columns.value[key].visible = parsed[key];
                    }
                });
            }
        }
    } catch (e) {
        console.error("Erreur lecture colonnes", e);
    }
});

const handleColumnToggle = (key) => {
    if (columns.value[key]?.locked) return;
    columns.value[key].visible = !columns.value[key].visible;
    try {
        const toSave = {};
        Object.keys(columns.value).forEach((k) => {
            toSave[k] = columns.value[k].visible;
        });
        localStorage.setItem("admin_users_columns_visibility", JSON.stringify(toSave));
    } catch (e) {}
};

const visibleColumnsCount = computed(() => {
    return Object.values(columns.value).filter((col) => col.visible).length + 2;
});

// 2. MODALE DÉTAILLÉE DES INVITÉS
const isAttendeesDialogOpen = ref(false);
const selectedUserForAttendees = ref(null);

const openAttendeesDialog = (user) => {
    selectedUserForAttendees.value = user;
    isAttendeesDialogOpen.value = true;
};

// 3. SÉLECTION MULTI-PAGES ROBUSTE
const pageUserIds = computed(() => props.users.map((u) => Number(u.id)));

const isUserSelected = (id) => {
    return props.selectAllMatching || props.selectedIds.map(Number).includes(Number(id));
};

const isAllPageSelected = computed(() => {
    if (pageUserIds.value.length === 0) return false;
    const selectedNumeric = props.selectedIds.map(Number);
    return pageUserIds.value.every((id) => selectedNumeric.includes(id));
});

const isSomePageSelected = computed(() => {
    const selectedNumeric = props.selectedIds.map(Number);
    return pageUserIds.value.some((id) => selectedNumeric.includes(id)) && !isAllPageSelected.value;
});

const emitSelection = (ids) => {
    emit("update:selected-ids", ids);
    emit("update:selectedIds", ids);
};

const emitSelectAll = (val) => {
    emit("update:select-all-matching", val);
    emit("update:selectAllMatching", val);
};

const toggleSelectAllPage = (shouldSelect) => {
    let updated = [...props.selectedIds.map(Number)];
    const currentPageIds = pageUserIds.value;

    if (shouldSelect) {
        currentPageIds.forEach((id) => {
            if (!updated.includes(id)) {
                updated.push(id);
            }
        });
    } else {
        updated = updated.filter((id) => !currentPageIds.includes(id));
        emitSelectAll(false);
    }

    emitSelection(updated);
};

const toggleSelectUser = (id, shouldSelect) => {
    const numericId = Number(id);
    let updated = [...props.selectedIds.map(Number)];

    if (shouldSelect) {
        if (!updated.includes(numericId)) {
            updated.push(numericId);
        }
    } else {
        updated = updated.filter((item) => item !== numericId);
        emitSelectAll(false);
    }

    emitSelection(updated);
};

const selectAllGlobal = () => {
    emitSelectAll(true);
};

const clearAllSelection = () => {
    emitSelection([]);
    emitSelectAll(false);
};

// 4. GESTION DU TRI
const handleSort = (field) => {
    let direction = "asc";
    if (props.sorting.field === field && props.sorting.direction === "asc") {
        direction = "desc";
    }
    emit("sort-change", { field, direction });
};

// 5. COPIE AVEC ANIMATION VERTE
const copiedKey = ref(null);

const copyText = async (text, label, key) => {
    if (!text || text === "-") return;
    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
        } else {
            const textarea = document.createElement("textarea");
            textarea.value = text;
            textarea.style.position = "fixed";
            textarea.style.opacity = "0";
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand("copy");
            document.body.removeChild(textarea);
        }
        copiedKey.value = key;
        toast.success(`${label} copié`, { description: text });
        setTimeout(() => {
            if (copiedKey.value === key) {
                copiedKey.value = null;
            }
        }, 2000);
    } catch (err) {
        console.error("Échec copie :", err);
        toast.error("Impossible de copier", { description: text });
    }
};
</script>

<template>
    <div class="space-y-3">
        <!-- Barre supérieure : Compteur & Menu Engrenage -->
        <div class="flex items-center justify-between gap-2">
            <div>
                <span v-if="selectedIds.length > 0" class="text-xs text-muted-foreground font-medium">
                    <strong>{{ selectedIds.length }}</strong> {{ pluralize(selectedIds.length, 'membre') }} coché(s) sur cette page
                </span>
            </div>

            <!-- Menu Engrenage Colonnes avec coche "v" à gauche -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="sm" class="h-8 gap-1.5 text-xs cursor-pointer shadow-2xs">
                        <Settings2 class="h-3.5 w-3.5 text-muted-foreground" />
                        <span>Colonnes</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56 p-1.5 space-y-0.5 text-xs">
                    <DropdownMenuLabel class="text-xs font-semibold px-2 py-1">Colonnes affichées</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <div
                        v-for="(col, key) in columns"
                        :key="key"
                        class="flex items-center gap-2.5 px-2 py-1.5 rounded-md hover:bg-muted/60 cursor-pointer select-none transition-colors"
                        :class="col.locked ? 'opacity-50 cursor-not-allowed' : ''"
                        @click="handleColumnToggle(key)"
                    >
                        <!-- Coche "v" à gauche du libellé -->
                        <div class="h-4 w-4 flex items-center justify-center shrink-0">
                            <Check v-if="col.visible" class="h-4 w-4 text-primary stroke-[2.5]" />
                        </div>
                        <span class="text-xs font-medium text-foreground">{{ col.label }}</span>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <!-- Bannière contextuelle de sélection globale -->
        <div
            v-if="isAllPageSelected && pagination.total > users.length"
            class="p-2.5 rounded-lg border border-primary/20 bg-primary/5 text-xs text-center text-foreground flex items-center justify-center gap-2 flex-wrap"
        >
            <template v-if="!selectAllMatching">
                <span>
                    Les <strong>{{ users.length }}</strong> membres de cette page sont sélectionnés.
                </span>
                <button
                    type="button"
                    class="font-bold text-primary underline hover:text-primary/80 cursor-pointer"
                    @click="selectAllGlobal"
                >
                    Sélectionner les {{ pagination.total }} membres correspondant à la recherche
                </button>
            </template>
            <template v-else>
                <span class="font-semibold text-primary">
                    ✓ Tous les {{ pagination.total }} membres correspondant à la recherche sont sélectionnés.
                </span>
                <button
                    type="button"
                    class="text-muted-foreground underline hover:text-foreground ml-2 cursor-pointer"
                    @click="clearAllSelection"
                >
                    Effacer la sélection
                </button>
            </template>
        </div>

        <!-- Table principale -->
        <div class="border rounded-xl bg-card overflow-hidden shadow-2xs">
            <Table class="text-xs">
                <TableHeader class="bg-muted/50 border-b">
                    <TableRow class="hover:bg-transparent">
                        <!-- Checkbox Maître du Header (Contrôle direct fiable) -->
                        <TableHead class="w-10 px-3">
                            <button
                                type="button"
                                role="checkbox"
                                :aria-checked="isAllPageSelected ? true : (isSomePageSelected ? 'mixed' : false)"
                                class="h-4 w-4 rounded-[4px] border flex items-center justify-center transition-colors cursor-pointer"
                                :class="isAllPageSelected || isSomePageSelected ? 'bg-primary border-primary text-white' : 'border-muted-foreground/50 bg-background hover:border-primary'"
                                @click="toggleSelectAllPage(!isAllPageSelected)"
                            >
                                <Check v-if="isAllPageSelected" class="h-3 w-3 text-white stroke-[3]" />
                                <Minus v-else-if="isSomePageSelected" class="h-3 w-3 text-white stroke-[3]" />
                            </button>
                        </TableHead>

                        <!-- Nom & Prénom -->
                        <TableHead class="cursor-pointer select-none font-semibold text-foreground" @click="handleSort('last_name')">
                            <div class="flex items-center gap-1.5">
                                <span>Membre</span>
                                <ArrowUp v-if="sorting.field === 'last_name' && sorting.direction === 'asc'" class="h-3 w-3 text-primary" />
                                <ArrowDown v-else-if="sorting.field === 'last_name' && sorting.direction === 'desc'" class="h-3 w-3 text-primary" />
                                <ArrowUpDown v-else class="h-3 w-3 text-muted-foreground/40" />
                            </div>
                        </TableHead>

                        <!-- E-mail -->
                        <TableHead v-if="columns.email.visible" class="cursor-pointer select-none font-semibold text-foreground" @click="handleSort('email')">
                            <div class="flex items-center gap-1.5">
                                <span>Email</span>
                                <ArrowUp v-if="sorting.field === 'email' && sorting.direction === 'asc'" class="h-3 w-3 text-primary" />
                                <ArrowDown v-else-if="sorting.field === 'email' && sorting.direction === 'desc'" class="h-3 w-3 text-primary" />
                                <ArrowUpDown v-else class="h-3 w-3 text-muted-foreground/40" />
                            </div>
                        </TableHead>

                        <!-- Téléphone -->
                        <TableHead v-if="columns.phone_number.visible" class="font-semibold text-foreground">Téléphone</TableHead>

                        <!-- Localité -->
                        <TableHead v-if="columns.locality.visible" class="cursor-pointer select-none font-semibold text-foreground" @click="handleSort('locality')">
                            <div class="flex items-center gap-1.5">
                                <span>Localité</span>
                                <ArrowUp v-if="sorting.field === 'locality' && sorting.direction === 'asc'" class="h-3 w-3 text-primary" />
                                <ArrowDown v-else-if="sorting.field === 'locality' && sorting.direction === 'desc'" class="h-3 w-3 text-primary" />
                                <ArrowUpDown v-else class="h-3 w-3 text-muted-foreground/40" />
                            </div>
                        </TableHead>

                        <!-- Adresse -->
                        <TableHead v-if="columns.address.visible" class="font-semibold text-foreground">Adresse</TableHead>

                        <!-- Anniversaire -->
                        <TableHead v-if="columns.birthday.visible" class="cursor-pointer select-none font-semibold text-foreground" @click="handleSort('birthday')">
                            <div class="flex items-center gap-1.5">
                                <span>Anniversaire</span>
                                <ArrowUp v-if="sorting.field === 'birthday' && sorting.direction === 'asc'" class="h-3 w-3 text-primary" />
                                <ArrowDown v-else-if="sorting.field === 'birthday' && sorting.direction === 'desc'" class="h-3 w-3 text-primary" />
                                <ArrowUpDown v-else class="h-3 w-3 text-muted-foreground/40" />
                            </div>
                        </TableHead>

                        <!-- Société -->
                        <TableHead v-if="columns.company_name.visible" class="cursor-pointer select-none font-semibold text-foreground" @click="handleSort('company_name')">
                            <div class="flex items-center gap-1.5">
                                <span>Société</span>
                                <ArrowUp v-if="sorting.field === 'company_name' && sorting.direction === 'asc'" class="h-3 w-3 text-primary" />
                                <ArrowDown v-else-if="sorting.field === 'company_name' && sorting.direction === 'desc'" class="h-3 w-3 text-primary" />
                                <ArrowUpDown v-else class="h-3 w-3 text-muted-foreground/40" />
                            </div>
                        </TableHead>

                        <!-- Adresse Société -->
                        <TableHead v-if="columns.company_address.visible" class="font-semibold text-foreground">Adresse Société</TableHead>

                        <!-- N° TVA -->
                        <TableHead v-if="columns.vat_number.visible" class="font-semibold text-foreground">N° TVA</TableHead>

                        <!-- Modules -->
                        <TableHead v-if="columns.modules.visible" class="cursor-pointer select-none font-semibold text-foreground" @click="handleSort('modules_count')">
                            <div class="flex items-center gap-1.5">
                                <span>Modules</span>
                                <ArrowUp v-if="sorting.field === 'modules_count' && sorting.direction === 'asc'" class="h-3 w-3 text-primary" />
                                <ArrowDown v-else-if="sorting.field === 'modules_count' && sorting.direction === 'desc'" class="h-3 w-3 text-primary" />
                                <ArrowUpDown v-else class="h-3 w-3 text-muted-foreground/40" />
                            </div>
                        </TableHead>

                        <!-- Invités -->
                        <TableHead v-if="columns.attendees.visible" class="font-semibold text-foreground">Invités</TableHead>

                        <!-- Date d'inscription -->
                        <TableHead v-if="columns.created_at.visible" class="cursor-pointer select-none font-semibold text-foreground" @click="handleSort('created_at')">
                            <div class="flex items-center gap-1.5">
                                <span>Inscrit le</span>
                                <ArrowUp v-if="sorting.field === 'created_at' && sorting.direction === 'asc'" class="h-3 w-3 text-primary" />
                                <ArrowDown v-else-if="sorting.field === 'created_at' && sorting.direction === 'desc'" class="h-3 w-3 text-primary" />
                                <ArrowUpDown v-else class="h-3 w-3 text-muted-foreground/40" />
                            </div>
                        </TableHead>

                        <!-- Actions -->
                        <TableHead class="w-12 text-right pr-4">Action</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody class="divide-y divide-border">
                    <template v-if="users.length > 0">
                        <TableRow
                            v-for="user in users"
                            :key="user.id"
                            class="hover:bg-muted/40 transition-colors"
                            :class="isUserSelected(user.id) ? 'bg-primary/5' : ''"
                        >
                            <!-- Case à cocher de ligne (Contrôle direct fiable) -->
                            <TableCell class="px-3">
                                <button
                                    type="button"
                                    role="checkbox"
                                    :aria-checked="isUserSelected(user.id)"
                                    class="h-4 w-4 rounded-[4px] border flex items-center justify-center transition-colors cursor-pointer"
                                    :class="isUserSelected(user.id) ? 'bg-primary border-primary text-white' : 'border-muted-foreground/50 bg-background hover:border-primary'"
                                    @click.stop="toggleSelectUser(user.id, !isUserSelected(user.id))"
                                >
                                    <Check v-if="isUserSelected(user.id)" class="h-3 w-3 text-white stroke-[3]" />
                                </button>
                            </TableCell>

                            <!-- Nom & Prénom -->
                            <TableCell class="font-medium text-foreground">
                                <Link
                                    :href="route('users.show', user.id)"
                                    class="hover:underline text-primary font-semibold flex items-center gap-1.5"
                                >
                                    {{ user.last_name }} {{ user.first_name }}
                                </Link>
                                <span v-if="!columns.birthday.visible && user.birthday_formatted !== '-'" class="text-[11px] text-muted-foreground block">
                                    Né(e) le {{ user.birthday_formatted }}
                                </span>
                            </TableCell>

                            <!-- E-mail -->
                            <TableCell v-if="columns.email.visible">
                                <span
                                    role="button"
                                    class="inline-flex items-center gap-1 cursor-pointer transition-colors"
                                    :class="copiedKey === `email-${user.id}` ? 'text-emerald-600 font-semibold' : 'text-muted-foreground hover:text-foreground'"
                                    title="Cliquer pour copier"
                                    @click="copyText(user.email, 'Email', `email-${user.id}`)"
                                >
                                    {{ user.email }}
                                    <Check v-if="copiedKey === `email-${user.id}`" class="h-3 w-3 text-emerald-600 animate-in zoom-in-50 duration-150" />
                                    <Copy v-else class="h-2.5 w-2.5 opacity-40 hover:opacity-100" />
                                </span>
                            </TableCell>

                            <!-- Téléphone -->
                            <TableCell v-if="columns.phone_number.visible">
                                <span
                                    v-if="user.phone_number"
                                    role="button"
                                    class="inline-flex items-center gap-1 cursor-pointer transition-colors"
                                    :class="copiedKey === `phone-${user.id}` ? 'text-emerald-600 font-semibold' : 'text-muted-foreground hover:text-foreground'"
                                    title="Cliquer pour copier"
                                    @click="copyText(user.phone_number, 'Téléphone', `phone-${user.id}`)"
                                >
                                    {{ user.phone_number }}
                                    <Check v-if="copiedKey === `phone-${user.id}`" class="h-3 w-3 text-emerald-600 animate-in zoom-in-50 duration-150" />
                                    <Copy v-else class="h-2.5 w-2.5 opacity-40 hover:opacity-100" />
                                </span>
                                <span v-else class="text-muted-foreground/50">-</span>
                            </TableCell>

                            <!-- Localité -->
                            <TableCell v-if="columns.locality.visible" class="text-muted-foreground">
                                <span v-if="user.locality || user.postal_code">
                                    {{ user.postal_code }} {{ user.locality }}
                                </span>
                                <span v-else class="text-muted-foreground/50">-</span>
                            </TableCell>

                            <!-- Adresse -->
                            <TableCell v-if="columns.address.visible" class="text-muted-foreground">
                                <span>{{ user.address || '-' }}</span>
                            </TableCell>

                            <!-- Anniversaire -->
                            <TableCell v-if="columns.birthday.visible" class="text-muted-foreground whitespace-nowrap">
                                <span>{{ user.birthday_formatted }}</span>
                            </TableCell>

                            <!-- Société -->
                            <TableCell v-if="columns.company_name.visible" class="text-muted-foreground">
                                <span v-if="user.company_name" class="font-medium text-foreground">
                                    {{ user.company_name }}
                                </span>
                                <span v-else class="text-muted-foreground/50">-</span>
                            </TableCell>

                            <!-- Adresse Société -->
                            <TableCell v-if="columns.company_address.visible" class="text-muted-foreground text-[11px]">
                                <span v-if="user.company_address">
                                    {{ user.company_address }}, {{ user.company_postal_code }} {{ user.company_locality }}
                                </span>
                                <span v-else class="text-muted-foreground/50">-</span>
                            </TableCell>

                            <!-- N° TVA -->
                            <TableCell v-if="columns.vat_number.visible" class="text-muted-foreground">
                                <span v-if="user.vat_number" class="font-mono text-[11px]">
                                    {{ user.vat_number }}
                                </span>
                                <span v-else class="text-muted-foreground/50">-</span>
                            </TableCell>

                            <!-- Modules count -->
                            <TableCell v-if="columns.modules.visible">
                                <Badge variant="outline" class="text-[10px] font-normal gap-1 bg-background">
                                    <BookOpen class="h-3 w-3 text-primary" />
                                    {{ pluralize(user.modules_count, 'module') }}
                                </Badge>
                            </TableCell>

                            <!-- Invités (3 max + Tooltip bleu & blanc + Modale) -->
                            <TableCell v-if="columns.attendees.visible">
                                <div v-if="user.attendees && user.attendees.length > 0" class="flex flex-wrap items-center gap-1">
                                    <TooltipProvider
                                        v-for="att in user.attendees.slice(0, 3)"
                                        :key="att.id"
                                        :delay-duration="150"
                                    >
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Badge
                                                    variant="secondary"
                                                    class="text-[10px] py-0 px-1.5 font-normal cursor-default"
                                                >
                                                    {{ att.first_name }}
                                                </Badge>
                                            </TooltipTrigger>
                                            <TooltipContent side="top" class="bg-primary text-white border-primary/20 text-xs p-2 space-y-0.5 shadow-lg">
                                                <p class="font-semibold text-white">{{ att.full_name }}</p>
                                                <p v-if="att.birthday_formatted" class="text-[11px] text-white/90 flex items-center gap-1">
                                                    <Cake class="h-3 w-3 text-white" />
                                                    <span>Né(e) le {{ att.birthday_formatted }}</span>
                                                </p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>

                                    <!-- Déclencheur modale si plus de 3 invités -->
                                    <button
                                        v-if="user.attendees.length > 3"
                                        type="button"
                                        class="text-[10px] bg-muted hover:bg-accent text-muted-foreground hover:text-foreground font-semibold px-1.5 py-0.5 rounded transition-colors cursor-pointer border"
                                        title="Voir tous les invités"
                                        @click.stop="openAttendeesDialog(user)"
                                    >
                                        +{{ user.attendees.length - 3 }}...
                                    </button>
                                </div>
                                <span v-else class="text-muted-foreground/50 text-[11px]">Aucun</span>
                            </TableCell>

                            <!-- Date d'inscription -->
                            <TableCell v-if="columns.created_at.visible" class="text-muted-foreground text-[11px] whitespace-nowrap">
                                {{ user.created_at_formatted }}
                            </TableCell>

                            <!-- Menu d'Actions -->
                            <TableCell class="text-right pr-4">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-7 w-7 p-0 cursor-pointer">
                                            <MoreHorizontal class="h-3.5 w-3.5" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-48 text-xs">
                                        <DropdownMenuLabel class="text-xs">Actions</DropdownMenuLabel>
                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem as-child>
                                            <Link :href="route('users.show', user.id)" class="cursor-pointer gap-2">
                                                <UserIcon class="h-3.5 w-3.5" />
                                                <span>Voir la fiche</span>
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem class="cursor-pointer gap-2" @click="emit('email-user', user)">
                                            <Mail class="h-3.5 w-3.5" />
                                            <span>Envoyer un e-mail</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem class="cursor-pointer gap-2" @click="copyText(user.email, 'Email', `action-email-${user.id}`)">
                                            <Copy class="h-3.5 w-3.5" />
                                            <span>Copier l'e-mail</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="user.phone_number"
                                            class="cursor-pointer gap-2"
                                            @click="copyText(user.phone_number, 'Téléphone', `action-phone-${user.id}`)">
                                            <Phone class="h-3.5 w-3.5" />
                                            <span>Copier le téléphone</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem
                                            class="cursor-pointer gap-2 text-destructive focus:text-destructive"
                                            @click="emit('delete-user', user)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                            <span>Supprimer</span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </template>

                    <!-- État vide -->
                    <TableRow v-else>
                        <TableCell :colspan="visibleColumnsCount" class="h-32 text-center text-muted-foreground text-xs">
                            Aucun membre ne correspond à vos critères de recherche.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination & Nombre d'éléments -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-muted-foreground pt-1">
            <div class="flex items-center gap-2">
                <span>Afficher</span>
                <Select
                    :model-value="String(pagination.perPage)"
                    @update:model-value="(val) => emit('per-page-change', Number(val))"
                >
                    <SelectTrigger class="h-8 w-16 text-xs bg-background cursor-pointer">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="10" class="text-xs cursor-pointer">10</SelectItem>
                        <SelectItem value="25" class="text-xs cursor-pointer">25</SelectItem>
                        <SelectItem value="50" class="text-xs cursor-pointer">50</SelectItem>
                        <SelectItem value="100" class="text-xs cursor-pointer">100</SelectItem>
                    </SelectContent>
                </Select>
                <span>par page · <strong>{{ pagination.total }}</strong> résultat(s)</span>
            </div>

            <!-- Boutons de pagination -->
            <div class="flex items-center gap-1.5">
                <span class="text-xs">
                    Page <strong>{{ pagination.page }}</strong> sur <strong>{{ pagination.lastPage || 1 }}</strong>
                </span>

                <div class="flex items-center gap-1 ml-2">
                    <Button
                        variant="outline"
                        size="icon"
                        class="h-8 w-8 cursor-pointer"
                        :disabled="pagination.page <= 1"
                        @click="emit('page-change', pagination.page - 1)"
                    >
                        <ChevronLeft class="h-3.5 w-3.5" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        class="h-8 w-8 cursor-pointer"
                        :disabled="pagination.page >= pagination.lastPage"
                        @click="emit('page-change', pagination.page + 1)"
                    >
                        <ChevronRight class="h-3.5 w-3.5" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Modale détaillée des invités -->
        <Dialog :open="isAttendeesDialogOpen" @update:open="(val) => (isAttendeesDialogOpen = val)">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-sm font-bold">
                        <Users class="h-4 w-4 text-primary" />
                        <span>Invités de {{ selectedUserForAttendees?.full_name }}</span>
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Liste détaillée des {{ selectedUserForAttendees?.attendees?.length }} invité(s) rattaché(s) à ce compte.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-2.5 max-h-64 overflow-y-auto py-2 pr-1 divide-y">
                    <div
                        v-for="att in selectedUserForAttendees?.attendees"
                        :key="att.id"
                        class="flex items-center justify-between pt-2.5 first:pt-0"
                    >
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0">
                                {{ (att.first_name?.[0] || '').toUpperCase() }}{{ (att.last_name?.[0] || '').toUpperCase() }}
                            </div>
                            <div class="space-y-0.5">
                                <p class="text-xs font-semibold text-foreground">{{ att.full_name }}</p>
                                <div class="flex items-center gap-3 text-[11px] text-muted-foreground flex-wrap">
                                    <span v-if="att.birthday_formatted" class="inline-flex items-center gap-1">
                                        <Cake class="h-3 w-3 text-primary shrink-0" />
                                        <span>Né(e) le {{ att.birthday_formatted }}</span>
                                    </span>
                                    <span v-if="att.created_at_formatted" class="inline-flex items-center gap-1">
                                        <Calendar class="h-3 w-3 text-muted-foreground shrink-0" />
                                        <span>Inscrit(e) le {{ att.created_at_formatted }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
