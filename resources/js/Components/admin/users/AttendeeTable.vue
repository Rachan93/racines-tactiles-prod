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
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
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
    Trash2,
    BookOpen,
    Copy,
    Check,
    Minus,
    Mail,
    Phone,
    Settings2,
} from "lucide-vue-next";

const props = defineProps({
    attendees: {
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
    "delete-attendee",
]);

// 1. CONFIGURATION & PERSISTANCE DES COLONNES AVEC COCHE À GAUCHE
const defaultColumns = {
    name: { label: "Nom de l'invité", visible: true, locked: true },
    user_name: { label: "Responsable légal", visible: true },
    modules: { label: "Modules", visible: true },
    birthday: { label: "Date de naissance", visible: true },
    created_at: { label: "Date d'inscription", visible: true },
};

const columns = ref(JSON.parse(JSON.stringify(defaultColumns)));

onMounted(() => {
    try {
        const saved = localStorage.getItem("admin_attendees_columns_visibility");
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
        localStorage.setItem("admin_attendees_columns_visibility", JSON.stringify(toSave));
    } catch (e) {}
};

const visibleColumnsCount = computed(() => {
    return Object.values(columns.value).filter((col) => col.visible).length + 2;
});

// 2. SÉLECTION MULTI-PAGES ROBUSTE
const pageAttendeeIds = computed(() => props.attendees.map((a) => Number(a.id)));

const isAttendeeSelected = (id) => {
    return props.selectAllMatching || props.selectedIds.map(Number).includes(Number(id));
};

const isAllPageSelected = computed(() => {
    if (pageAttendeeIds.value.length === 0) return false;
    const selectedNumeric = props.selectedIds.map(Number);
    return pageAttendeeIds.value.every((id) => selectedNumeric.includes(id));
});

const isSomePageSelected = computed(() => {
    const selectedNumeric = props.selectedIds.map(Number);
    return pageAttendeeIds.value.some((id) => selectedNumeric.includes(id)) && !isAllPageSelected.value;
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
    const currentPageIds = pageAttendeeIds.value;

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

const toggleSelectAttendee = (id, shouldSelect) => {
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

// 3. GESTION DU TRI
const handleSort = (field) => {
    let direction = "asc";
    if (props.sorting.field === field && props.sorting.direction === "asc") {
        direction = "desc";
    }
    emit("sort-change", { field, direction });
};

// 4. COPIE ROBUSTE AVEC ANIMATION VERTE
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
        <!-- Barre supérieure : Compteur & Sélecteur de colonnes -->
        <div class="flex items-center justify-between gap-2">
            <div>
                <span v-if="selectedIds.length > 0" class="text-xs text-muted-foreground font-medium">
                    <strong>{{ selectedIds.length }}</strong> {{ pluralize(selectedIds.length, 'invité') }} coché(s) sur cette page
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
                            <Check v-if="col.visible" class="h-3.5 w-3.5 text-primary stroke-[2.5]" />
                        </div>
                        <span class="text-xs font-medium text-foreground">{{ col.label }}</span>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <!-- Bannière contextuelle de sélection globale -->
        <div
            v-if="isAllPageSelected && pagination.total > attendees.length"
            class="p-2.5 rounded-lg border border-primary/20 bg-primary/5 text-xs text-center text-foreground flex items-center justify-center gap-2 flex-wrap"
        >
            <template v-if="!selectAllMatching">
                <span>
                    Les <strong>{{ attendees.length }}</strong> invités de cette page sont sélectionnés.
                </span>
                <button
                    type="button"
                    class="font-bold text-primary underline hover:text-primary/80 cursor-pointer"
                    @click="selectAllGlobal"
                >
                    Sélectionner les {{ pagination.total }} invités correspondant aux filtres
                </button>
            </template>
            <template v-else>
                <span class="font-semibold text-primary">
                    ✓ Tous les {{ pagination.total }} invités correspondant à la recherche sont sélectionnés.
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

        <!-- Table principale des invités -->
        <div class="border rounded-xl bg-card overflow-hidden shadow-2xs">
            <Table class="text-xs">
                <TableHeader class="bg-muted/50 border-b">
                    <TableRow class="hover:bg-transparent">
                        <!-- Checkbox Maître (Contrôle direct fiable) -->
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

                        <!-- Nom & Prénom Invité -->
                        <TableHead class="cursor-pointer select-none font-semibold text-foreground" @click="handleSort('last_name')">
                            <div class="flex items-center gap-1.5">
                                <span>Invité</span>
                                <ArrowUp v-if="sorting.field === 'last_name' && sorting.direction === 'asc'" class="h-3 w-3 text-primary" />
                                <ArrowDown v-else-if="sorting.field === 'last_name' && sorting.direction === 'desc'" class="h-3 w-3 text-primary" />
                                <ArrowUpDown v-else class="h-3 w-3 text-muted-foreground/40" />
                            </div>
                        </TableHead>

                        <!-- Responsable -->
                        <TableHead v-if="columns.user_name.visible" class="cursor-pointer select-none font-semibold text-foreground" @click="handleSort('user_name')">
                            <div class="flex items-center gap-1.5">
                                <span>Responsable légal</span>
                                <ArrowUp v-if="sorting.field === 'user_name' && sorting.direction === 'asc'" class="h-3 w-3 text-primary" />
                                <ArrowDown v-else-if="sorting.field === 'user_name' && sorting.direction === 'desc'" class="h-3 w-3 text-primary" />
                                <ArrowUpDown v-else class="h-3 w-3 text-muted-foreground/40" />
                            </div>
                        </TableHead>

                        <!-- Modules / Inscriptions -->
                        <TableHead v-if="columns.modules.visible" class="cursor-pointer select-none font-semibold text-foreground" @click="handleSort('modules_count')">
                            <div class="flex items-center gap-1.5">
                                <span>Modules</span>
                                <ArrowUp v-if="sorting.field === 'modules_count' && sorting.direction === 'asc'" class="h-3 w-3 text-primary" />
                                <ArrowDown v-else-if="sorting.field === 'modules_count' && sorting.direction === 'desc'" class="h-3 w-3 text-primary" />
                                <ArrowUpDown v-else class="h-3 w-3 text-muted-foreground/40" />
                            </div>
                        </TableHead>

                        <!-- Date d'anniversaire / naissance -->
                        <TableHead v-if="columns.birthday.visible" class="cursor-pointer select-none font-semibold text-foreground" @click="handleSort('birthday')">
                            <div class="flex items-center gap-1.5">
                                <span>Date de naissance</span>
                                <ArrowUp v-if="sorting.field === 'birthday' && sorting.direction === 'asc'" class="h-3 w-3 text-primary" />
                                <ArrowDown v-else-if="sorting.field === 'birthday' && sorting.direction === 'desc'" class="h-3 w-3 text-primary" />
                                <ArrowUpDown v-else class="h-3 w-3 text-muted-foreground/40" />
                            </div>
                        </TableHead>

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
                    <template v-if="attendees.length > 0">
                        <TableRow
                            v-for="att in attendees"
                            :key="att.id"
                            class="hover:bg-muted/40 transition-colors"
                            :class="isAttendeeSelected(att.id) ? 'bg-primary/5' : ''"
                        >
                            <!-- Case à cocher de ligne (Contrôle direct fiable) -->
                            <TableCell class="px-3">
                                <button
                                    type="button"
                                    role="checkbox"
                                    :aria-checked="isAttendeeSelected(att.id)"
                                    class="h-4 w-4 rounded-[4px] border flex items-center justify-center transition-colors cursor-pointer"
                                    :class="isAttendeeSelected(att.id) ? 'bg-primary border-primary text-white' : 'border-muted-foreground/50 bg-background hover:border-primary'"
                                    @click.stop="toggleSelectAttendee(att.id, !isAttendeeSelected(att.id))"
                                >
                                    <Check v-if="isAttendeeSelected(att.id)" class="h-3 w-3 text-white stroke-[3]" />
                                </button>
                            </TableCell>

                            <!-- Invité -->
                            <TableCell class="font-medium text-foreground">
                                <span class="font-semibold text-foreground">{{ att.last_name }} {{ att.first_name }}</span>
                            </TableCell>

                            <!-- Responsable rattaché -->
                            <TableCell v-if="columns.user_name.visible">
                                <div v-if="att.user" class="space-y-0.5">
                                    <Link
                                        :href="route('users.show', att.user.id)"
                                        class="hover:underline text-primary font-semibold inline-flex items-center gap-1"
                                    >
                                        {{ att.user.last_name }} {{ att.user.first_name }}
                                    </Link>
                                    <div class="flex items-center gap-2 text-[11px] text-muted-foreground">
                                        <span
                                            role="button"
                                            class="inline-flex items-center gap-1 cursor-pointer transition-colors"
                                            :class="copiedKey === `att-email-${att.id}` ? 'text-emerald-600 font-semibold' : 'hover:text-foreground'"
                                            @click="copyText(att.user.email, 'Email', `att-email-${att.id}`)"
                                        >
                                            <Check v-if="copiedKey === `att-email-${att.id}`" class="h-2.5 w-2.5 text-emerald-600 animate-in zoom-in-50" />
                                            <Mail v-else class="h-2.5 w-2.5" />
                                            <span>{{ att.user.email }}</span>
                                        </span>
                                        <span
                                            v-if="att.user.phone_number"
                                            role="button"
                                            class="inline-flex items-center gap-1 cursor-pointer transition-colors"
                                            :class="copiedKey === `att-phone-${att.id}` ? 'text-emerald-600 font-semibold' : 'hover:text-foreground'"
                                            @click="copyText(att.user.phone_number, 'Téléphone', `att-phone-${att.id}`)"
                                        >
                                            <Check v-if="copiedKey === `att-phone-${att.id}`" class="h-2.5 w-2.5 text-emerald-600 animate-in zoom-in-50" />
                                            <Phone v-else class="h-2.5 w-2.5" />
                                            <span>{{ att.user.phone_number }}</span>
                                        </span>
                                    </div>
                                </div>
                                <span v-else class="text-muted-foreground/50">Non assigné</span>
                            </TableCell>

                            <!-- Modules count -->
                            <TableCell v-if="columns.modules.visible">
                                <Badge variant="outline" class="text-[10px] font-normal gap-1 bg-background">
                                    <BookOpen class="h-3 w-3 text-primary" />
                                    {{ pluralize(att.modules_count, 'module') }}
                                </Badge>
                            </TableCell>

                            <!-- Date de naissance / Anniversaire -->
                            <TableCell v-if="columns.birthday.visible" class="text-muted-foreground text-[11px] whitespace-nowrap">
                                {{ att.birthday_formatted }}
                            </TableCell>

                            <!-- Date d'inscription -->
                            <TableCell v-if="columns.created_at.visible" class="text-muted-foreground text-[11px] whitespace-nowrap">
                                {{ att.created_at_formatted }}
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

                                        <DropdownMenuItem v-if="att.user" as-child>
                                            <Link :href="route('users.show', att.user.id)" class="cursor-pointer gap-2">
                                                <UserIcon class="h-3.5 w-3.5" />
                                                <span>Fiche du responsable</span>
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="att.user"
                                            class="cursor-pointer gap-2"
                                            @click="copyText(att.user.email, 'Email', `action-att-email-${att.id}`)"
                                        >
                                            <Mail class="h-3.5 w-3.5" />
                                            <span>Copier l'e-mail responsable</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem
                                            class="cursor-pointer gap-2 text-destructive focus:text-destructive"
                                            @click="emit('delete-attendee', att)"
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
                            Aucun invité ne correspond à vos critères de recherche.
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
    </div>
</template>
