<script setup>
import { ref, watch, computed, onUnmounted } from "vue";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Checkbox } from "@/Components/ui/checkbox";
import { Label } from "@/Components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import {
    Search,
    SlidersHorizontal,
    RotateCcw,
    X,
    Check,
} from "lucide-vue-next";

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            search: "",
            search_targets: ["name", "instructor"],
            type_id: "",
            year: "",
            status: "all",
        }),
    },
    types: {
        type: Array,
        default: () => [],
    },
    years: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["apply"]);

// États locaux
const search = ref(props.filters.search || "");
const searchTargets = ref(["name", "instructor"]);
const tempSearchTargets = ref(["name", "instructor"]);
const isPopoverOpen = ref(false);

const selectedType = ref(props.filters.type_id ? String(props.filters.type_id) : "all");
const selectedYear = ref(props.filters.year ? String(props.filters.year) : "all");
const selectedStatus = ref(props.filters.status || "all");

let searchTimeout = null;

// Synchronisation systématique à la réception des props Inertia
watch(
    () => props.filters,
    (newFilters) => {
        if (!newFilters) return;

        search.value = newFilters.search || "";

        // Normalise sous forme de vrai tableau JS (même si PHP renvoie un objet associatif)
        let targets = ["name", "instructor"];
        if (newFilters.search_targets) {
            targets = Array.isArray(newFilters.search_targets)
                ? [...newFilters.search_targets]
                : Object.values(newFilters.search_targets);
        }

        searchTargets.value = targets.length > 0 ? targets : ["name", "instructor"];
        tempSearchTargets.value = [...searchTargets.value];

        selectedType.value = newFilters.type_id ? String(newFilters.type_id) : "all";
        selectedYear.value = newFilters.year ? String(newFilters.year) : "all";
        selectedStatus.value = newFilters.status || "all";
    },
    { deep: true, immediate: true }
);

// Détecte si des filtres personnalisés sont actifs
const hasActiveFilters = computed(() => {
    const isDefaultTargets =
        searchTargets.value.length === 2 &&
        searchTargets.value.includes("name") &&
        searchTargets.value.includes("instructor");

    return (
        search.value !== "" ||
        selectedType.value !== "all" ||
        selectedYear.value !== "all" ||
        selectedStatus.value !== "all" ||
        !isDefaultTargets
    );
});

// Émission des critères vers Index.vue
const emitFilters = () => {
    emit("apply", {
        search: search.value,
        search_targets: searchTargets.value,
        type_id: selectedType.value === "all" ? "" : selectedType.value,
        year: selectedYear.value === "all" ? "" : selectedYear.value,
        status: selectedStatus.value === "all" ? "" : selectedStatus.value,
    });
};

// Debounce sur la recherche texte
const handleSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(emitFilters, 300);
};

const clearSearch = () => {
    search.value = "";
    emitFilters();
};

// Ouverture du Popover (synchronise l'état temporaire)
const handlePopoverOpen = (open) => {
    isPopoverOpen.value = open;
    if (open) {
        tempSearchTargets.value = [...searchTargets.value];
    }
};

// Modification temporaire d'une case dans le Popover (avec double binding)
const toggleTempTarget = (target, checked) => {
    const isChecked = Boolean(checked);
    if (isChecked) {
        if (!tempSearchTargets.value.includes(target)) {
            tempSearchTargets.value = [...tempSearchTargets.value, target];
        }
    } else {
        // Maintient au moins une cible active
        if (tempSearchTargets.value.length > 1) {
            tempSearchTargets.value = tempSearchTargets.value.filter((t) => t !== target);
        }
    }
};

// Clic sur "Appliquer" dans le Popover
const applySearchTargets = () => {
    searchTargets.value = [...tempSearchTargets.value];
    isPopoverOpen.value = false;
    emitFilters();
};

// Changements sur les Selects
watch([selectedType, selectedYear, selectedStatus], emitFilters);

// Réinitialisation
const resetFilters = () => {
    search.value = "";
    searchTargets.value = ["name", "instructor"];
    tempSearchTargets.value = ["name", "instructor"];
    selectedType.value = "all";
    selectedYear.value = "all";
    selectedStatus.value = "all";
    emitFilters();
};

onUnmounted(() => {
    clearTimeout(searchTimeout);
});
</script>

<template>
    <div class="p-4 rounded-xl border bg-card shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-3.5">
        <!-- 1. Recherche Texte & Bouton Filtres -->
        <div class="flex items-center gap-2 flex-1 min-w-0">
            <!-- Champ de recherche -->
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                    v-model="search"
                    placeholder="Rechercher..."
                    class="pl-9 pr-8 h-9 text-xs bg-background"
                    @input="handleSearchInput"
                />
                <button
                    v-if="search"
                    type="button"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    @click="clearSearch"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>

            <!-- Popover "Filtres" -->
            <Popover :open="isPopoverOpen" @update:open="handlePopoverOpen">
                <PopoverTrigger as-child>
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-9 px-2.5 text-xs gap-1.5 shrink-0 bg-background shadow-2xs"
                        :class="searchTargets.includes('student') ? 'border-primary text-primary' : ''"
                    >
                        <SlidersHorizontal class="h-3.5 w-3.5" />
                        <span>Filtres</span>
                        <Badge
                            v-if="searchTargets.includes('student')"
                            variant="secondary"
                            class="text-[9px] py-0 px-1 ml-0.5 bg-primary/10 text-primary"
                        >
                            + Élève
                        </Badge>
                    </Button>
                </PopoverTrigger>
                <PopoverContent align="start" class="w-64 p-3.5 space-y-3 text-xs">
                    <div class="space-y-0.5">
                        <h4 class="font-bold text-foreground">Champs de recherche</h4>
                        <p class="text-[11px] text-muted-foreground leading-relaxed">
                            Sélectionnez les éléments inclus lors de la recherche texte.
                        </p>
                    </div>

                    <div class="space-y-2 pt-1 border-t">
                        <!-- 1. Nom du cours (Défaut : Actif) -->
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="target_name"
                                :checked="tempSearchTargets.includes('name')"
                                :model-value="tempSearchTargets.includes('name')"
                                @update:checked="(val) => toggleTempTarget('name', val)"
                                @update:model-value="(val) => toggleTempTarget('name', val)"
                            />
                            <Label for="target_name" class="text-xs cursor-pointer select-none">
                                Nom du cours & sous-type
                            </Label>
                        </div>

                        <!-- 2. Professeur (Défaut : Actif) -->
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="target_instructor"
                                :checked="tempSearchTargets.includes('instructor')"
                                :model-value="tempSearchTargets.includes('instructor')"
                                @update:checked="(val) => toggleTempTarget('instructor', val)"
                                @update:model-value="(val) => toggleTempTarget('instructor', val)"
                            />
                            <Label for="target_instructor" class="text-xs cursor-pointer select-none">
                                Professeur assigné
                            </Label>
                        </div>

                        <!-- 3. Élève / Participant -->
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="target_student"
                                :checked="tempSearchTargets.includes('student')"
                                :model-value="tempSearchTargets.includes('student')"
                                @update:checked="(val) => toggleTempTarget('student', val)"
                                @update:model-value="(val) => toggleTempTarget('student', val)"
                            />
                            <Label for="target_student" class="text-xs cursor-pointer select-none font-medium">
                                Élève / Participant inscrit
                            </Label>
                        </div>
                    </div>

                    <!-- Bouton Appliquer -->
                    <div class="pt-2 border-t flex justify-end">
                        <Button
                            type="button"
                            size="sm"
                            class="h-7 text-xs px-3 gap-1 font-semibold"
                            @click="applySearchTargets"
                        >
                            <Check class="h-3 w-3" />
                            <span>Appliquer</span>
                        </Button>
                    </div>
                </PopoverContent>
            </Popover>
        </div>

        <!-- 2. Filtres déroulants -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <!-- Statut (Non publiés) -->
            <Select v-model="selectedStatus">
                <SelectTrigger class="h-9 w-[140px] text-xs bg-background">
                    <SelectValue placeholder="Statut" />
                </SelectTrigger>
                <SelectContent align="end">
                    <SelectItem value="all" class="text-xs">Tous statuts</SelectItem>
                    <SelectItem value="upcoming" class="text-xs">En cours & à venir</SelectItem>
                    <SelectItem value="past" class="text-xs">Terminés</SelectItem>
                    <SelectItem value="inactive" class="text-xs">Non publiés</SelectItem>
                    <SelectItem value="active" class="text-xs">Publiés uniquement</SelectItem>
                </SelectContent>
            </Select>

            <!-- Formule / Type -->
            <Select v-model="selectedType">
                <SelectTrigger class="h-9 w-[135px] text-xs bg-background">
                    <SelectValue placeholder="Formules" />
                </SelectTrigger>
                <SelectContent align="end">
                    <SelectItem value="all" class="text-xs">Toutes formules</SelectItem>
                    <SelectItem
                        v-for="t in types"
                        :key="t.id"
                        :value="String(t.id)"
                        class="text-xs"
                    >
                        {{ t.name }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <!-- Année -->
            <Select v-model="selectedYear">
                <SelectTrigger class="h-9 w-[115px] text-xs bg-background">
                    <SelectValue placeholder="Année" />
                </SelectTrigger>
                <SelectContent align="end">
                    <SelectItem value="all" class="text-xs">Toutes années</SelectItem>
                    <SelectItem
                        v-for="y in years"
                        :key="y"
                        :value="String(y)"
                        class="text-xs"
                    >
                        {{ y }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <!-- Bouton Effacer -->
            <Button
                v-if="hasActiveFilters"
                type="button"
                variant="ghost"
                size="sm"
                class="h-9 px-2 text-xs text-muted-foreground hover:text-foreground gap-1 shrink-0"
                @click="resetFilters"
            >
                <RotateCcw class="h-3.5 w-3.5" />
                <span class="hidden sm:inline">Effacer</span>
            </Button>
        </div>
    </div>
</template>
