<script setup lang="ts">
import { ref, watch, toRefs } from "vue";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import { Search, SlidersHorizontal, X } from "lucide-vue-next";
import { Checkbox } from "@/Components/ui/checkbox";
import { Label } from "@/Components/ui/label";

interface FilterOption {
    id: string;
    label: string;
    enabled: boolean;
}

interface Props {
    placeholder: string;
    searchQuery: string | null;
    filterOptions: FilterOption[];
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: "Rechercher...",
    searchQuery: "",
});

const { searchQuery, filterOptions } = toRefs(props);
// S'assurer que localSearchQuery est toujours initialisé avec une chaîne, même si searchQuery est null
const localSearchQuery = ref(
    searchQuery.value === null ? "" : searchQuery.value
);
const localFilterOptions = ref(
    filterOptions.value.map((option) => ({ ...option }))
);

const emit = defineEmits(["search", "filter-change", "clear"]);

// État local pour savoir si l'utilisateur a lancé une recherche
const hasSearched = ref(!!searchQuery.value);

// Synchroniser les modifications locales avec les props sans logique complexe
watch(searchQuery, (newValue) => {
    localSearchQuery.value = newValue === null ? "" : newValue;
});

watch(
    filterOptions,
    (newValue) => {
        localFilterOptions.value = newValue.map((option) => ({ ...option }));
    },
    { deep: true }
);

// Simplifier les fonctions de gestion des événements
function handleSearch() {
    emit("search", localSearchQuery.value, getActiveFilters());
}

// Stocker seulement l'état des filtres sans déclencher de recherche immédiate
function handleFilterChange() {
    // Ne pas émettre d'événement, juste mettre à jour l'état local
    // L'application des filtres se fera uniquement lors du clic sur "Rechercher"
}

// Simplifier les fonctions de gestion des événements
function handleClear() {
    localSearchQuery.value = "";
    emit("clear");
}

// Récupérer les filtres actifs
function getActiveFilters() {
    return localFilterOptions.value
        .filter((option) => option.enabled)
        .map((option) => option.id);
}

// Fonction pour réinitialiser la recherche locale à la valeur validée
function reset() {
    localSearchQuery.value =
        searchQuery.value === null ? "" : searchQuery.value;
}

// Exposer la méthode reset au composant parent
defineExpose({ reset });
</script>

<template>
    <div class="flex items-center space-x-2">
        <div class="relative flex-1">
            <Search
                class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground"
            />
            <Input
                v-model="localSearchQuery"
                :placeholder="placeholder"
                class="pl-8 pr-10"
                @keydown.enter="handleSearch"
            />
            <button
                v-if="localSearchQuery"
                @click="handleClear"
                class="absolute right-2.5 top-2.5"
            >
                <X
                    class="h-4 w-4 text-muted-foreground hover:text-foreground"
                />
            </button>
        </div>

        <Popover>
            <PopoverTrigger as-child>
                <Button variant="outline" class="h-10 px-3">
                    <SlidersHorizontal class="h-4 w-4 mr-2" />
                    Filtres
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-80">
                <div class="space-y-4">
                    <h4 class="font-medium">Filtrer par</h4>
                    <div class="space-y-2">
                        <div
                            v-for="option in localFilterOptions"
                            :key="option.id"
                            class="flex items-center space-x-2"
                        >
                            <Checkbox
                                :id="option.id"
                                v-model="option.enabled"
                                @update:model-value="handleFilterChange"
                            />
                            <Label :for="option.id">{{ option.label }}</Label>
                        </div>
                    </div>
                </div>
            </PopoverContent>
        </Popover>

        <Button @click="handleSearch">Rechercher</Button>
    </div>
</template>
