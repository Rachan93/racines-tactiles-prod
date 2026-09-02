<script setup>
import { ref, computed, watch } from "vue";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import {
    Search,
    SlidersHorizontal,
    X,
    RotateCcw,
    BookOpen,
    GraduationCap,
    Check,
} from "lucide-vue-next";
import DateFilterPopover from "./DateFilterPopover.vue";
import BirthdayFilterPopover from "./BirthdayFilterPopover.vue";

const props = defineProps({
    activeTab: {
        type: String,
        default: "users", // 'users' | 'attendees'
    },
    filters: {
        type: Object,
        required: true,
    },
    courses: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["apply", "reset"]);

// Fonction utilitaire pour garantir un tableau JS propre
const toArray = (val, fallback = []) => {
    if (Array.isArray(val)) return [...val];
    if (val && typeof val === "object") return Object.values(val);
    return [...fallback];
};

// Préfixe selon l'onglet
const prefix = computed(() => (props.activeTab === "users" ? "users_" : "attendees_"));

// 1. ÉTAT LOCAL DES FILTRES
const search = ref(props.filters[`${prefix.value}search`] || "");
const courseId = ref(props.filters[`${prefix.value}course_id`] ? String(props.filters[`${prefix.value}course_id`]) : "all");
const moduleStatus = ref(props.filters[`${prefix.value}module_status`] || "all");

// Cibles de recherche textuelle
const defaultTargetsUsers = [
    { id: "last_name", label: "Nom" },
    { id: "first_name", label: "Prénom" },
    { id: "email", label: "Email" },
    { id: "phone_number", label: "Téléphone" },
    { id: "address", label: "Adresse" },
    { id: "locality", label: "Localité" },
    { id: "postal_code", label: "Code postal" },
    { id: "company_name", label: "Société" },
    { id: "company_address", label: "Adresse société" },
    { id: "company_locality", label: "Localité société" },
    { id: "company_postal_code", label: "Code postal société" },
    { id: "vat_number", label: "N° TVA" },
];

const defaultTargetsAttendees = [
    { id: "last_name", label: "Nom de l'invité" },
    { id: "first_name", label: "Prénom de l'invité" },
    { id: "user_name", label: "Nom du responsable" },
    { id: "user_email", label: "Email du responsable" },
];

const availableTargets = computed(() => {
    return props.activeTab === "users" ? defaultTargetsUsers : defaultTargetsAttendees;
});

// Initialisation sécurisée
const rawTargets = props.filters[`${prefix.value}search_filters`];
const initialTargets = toArray(rawTargets, availableTargets.value.map((t) => t.id));
const selectedTargets = ref(initialTargets.length > 0 ? initialTargets : availableTargets.value.map((t) => t.id));

// 2. FILTRES TEMPORELS
const lessonDateFilter = ref({
    operator: props.filters[`${prefix.value}lesson_date_operator`] || "",
    date: props.filters[`${prefix.value}lesson_date`] || "",
    dateEnd: props.filters[`${prefix.value}lesson_date_end`] || "",
});

const registrationFilter = ref({
    operator: props.filters[`${prefix.value}created_at_operator`] || "",
    date: props.filters[`${prefix.value}created_at_date`] || "",
    dateEnd: props.filters[`${prefix.value}created_at_date_end`] || "",
});

const birthdayFilter = ref({
    operator: props.filters[`${prefix.value}birthday_operator`] || "",
    day: props.filters[`${prefix.value}birthday_day`] || "all",
    month: props.filters[`${prefix.value}birthday_month`] || "all",
    year: props.filters[`${prefix.value}birthday_year`] || "all",
    endDay: props.filters[`${prefix.value}birthday_end_day`] || "all",
    endMonth: props.filters[`${prefix.value}birthday_end_month`] || "all",
    endYear: props.filters[`${prefix.value}birthday_end_year`] || "all",
});

// Helper pour vérifier si une cible est active
const isTargetSelected = (targetId) => {
    return selectedTargets.value.includes(targetId);
};

// Basculement direct et réactif
const handleToggleTarget = (targetId) => {
    if (selectedTargets.value.includes(targetId)) {
        if (selectedTargets.value.length > 1) {
            selectedTargets.value = selectedTargets.value.filter((id) => id !== targetId);
        }
    } else {
        selectedTargets.value = [...selectedTargets.value, targetId];
    }
};

// Indicateur si au moins un filtre est actif
const hasActiveFilters = computed(() => {
    return Boolean(
        search.value ||
        (courseId.value && courseId.value !== "all") ||
        (moduleStatus.value && moduleStatus.value !== "all") ||
        (lessonDateFilter.value.operator && lessonDateFilter.value.date) ||
        (registrationFilter.value.operator && registrationFilter.value.date) ||
        (birthdayFilter.value.operator && (birthdayFilter.value.day !== "all" || birthdayFilter.value.month !== "all" || birthdayFilter.value.year !== "all"))
    );
});

// Émission des filtres
const apply = () => {
    const payload = {};
    const p = prefix.value;

    payload[`${p}search`] = search.value;
    payload[`${p}search_filters`] = selectedTargets.value;
    payload[`${p}course_id`] = courseId.value === "all" ? "" : courseId.value;
    payload[`${p}module_status`] = moduleStatus.value;

    // Séance
    payload[`${p}lesson_date_operator`] = lessonDateFilter.value.operator;
    payload[`${p}lesson_date`] = lessonDateFilter.value.date;
    payload[`${p}lesson_date_end`] = lessonDateFilter.value.dateEnd;

    // Inscription
    payload[`${p}created_at_operator`] = registrationFilter.value.operator;
    payload[`${p}created_at_date`] = registrationFilter.value.date;
    payload[`${p}created_at_date_end`] = registrationFilter.value.dateEnd;

    // Anniversaire
    payload[`${p}birthday_operator`] = birthdayFilter.value.operator;
    payload[`${p}birthday_day`] = birthdayFilter.value.day === "all" ? "" : birthdayFilter.value.day;
    payload[`${p}birthday_month`] = birthdayFilter.value.month === "all" ? "" : birthdayFilter.value.month;
    payload[`${p}birthday_year`] = birthdayFilter.value.year === "all" ? "" : birthdayFilter.value.year;
    payload[`${p}birthday_end_day`] = birthdayFilter.value.endDay === "all" ? "" : birthdayFilter.value.endDay;
    payload[`${p}birthday_end_month`] = birthdayFilter.value.endMonth === "all" ? "" : birthdayFilter.value.endMonth;
    payload[`${p}birthday_end_year`] = birthdayFilter.value.endYear === "all" ? "" : birthdayFilter.value.endYear;

    emit("apply", payload);
};

const handleClearSearch = () => {
    search.value = "";
    apply();
};

const handleResetAll = () => {
    search.value = "";
    courseId.value = "all";
    moduleStatus.value = "all";
    selectedTargets.value = availableTargets.value.map((t) => t.id);
    lessonDateFilter.value = { operator: "", date: "", dateEnd: "" };
    registrationFilter.value = { operator: "", date: "", dateEnd: "" };
    birthdayFilter.value = { operator: "", day: "all", month: "all", year: "all", endDay: "all", endMonth: "all", endYear: "all" };

    emit("reset");
};

// Synchronisation réactive
watch(
    () => [props.activeTab, props.filters],
    () => {
        const p = prefix.value;
        search.value = props.filters[`${p}search`] || "";
        courseId.value = props.filters[`${p}course_id`] ? String(props.filters[`${p}course_id`]) : "all";
        moduleStatus.value = props.filters[`${p}module_status`] || "all";

        const validTargetIds = availableTargets.value.map((t) => t.id);
        const existingTargets = toArray(props.filters[`${p}search_filters`], validTargetIds);
        const filtered = existingTargets.filter((id) => validTargetIds.includes(id));
        selectedTargets.value = filtered.length > 0 ? filtered : validTargetIds;

        lessonDateFilter.value = {
            operator: props.filters[`${p}lesson_date_operator`] || "",
            date: props.filters[`${p}lesson_date`] || "",
            dateEnd: props.filters[`${p}lesson_date_end`] || "",
        };

        registrationFilter.value = {
            operator: props.filters[`${p}created_at_operator`] || "",
            date: props.filters[`${p}created_at_date`] || "",
            dateEnd: props.filters[`${p}created_at_date_end`] || "",
        };

        birthdayFilter.value = {
            operator: props.filters[`${p}birthday_operator`] || "",
            day: props.filters[`${p}birthday_day`] || "all",
            month: props.filters[`${p}birthday_month`] || "all",
            year: props.filters[`${p}birthday_year`] || "all",
            endDay: props.filters[`${p}birthday_end_day`] || "all",
            endMonth: props.filters[`${p}birthday_end_month`] || "all",
            endYear: props.filters[`${p}birthday_end_year`] || "all",
        };
    },
    { deep: true }
);
</script>

<template>
    <div class="p-4 rounded-xl border bg-card shadow-2xs space-y-3">
        <!-- 1ère ligne : Recherche & Filtres textuels -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                    v-model="search"
                    type="text"
                    :placeholder="props.activeTab === 'users' ? 'Rechercher par nom, email, téléphone, localité, société, TVA...' : 'Rechercher un invité ou son responsable...'"
                    class="pl-9 pr-8 h-9 text-xs bg-background shadow-none"
                    @keydown.enter="apply"
                />
                <button
                    v-if="search"
                    type="button"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground cursor-pointer"
                    @click="handleClearSearch"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>

            <!-- Popover Cibles de recherche (Filtres) -->
            <Popover>
                <PopoverTrigger as-child>
                    <Button variant="outline" size="sm" class="h-9 gap-1.5 text-xs cursor-pointer">
                        <SlidersHorizontal class="h-3.5 w-3.5 text-muted-foreground" />
                        <span>Filtres ({{ selectedTargets.length }})</span>
                    </Button>
                </PopoverTrigger>
                <PopoverContent class="w-64 p-3 space-y-2" align="end">
                    <p class="text-xs font-semibold text-foreground">Rechercher dans :</p>
                    <div class="max-h-60 overflow-y-auto space-y-1 pr-1">
                        <div
                            v-for="target in availableTargets"
                            :key="target.id"
                            class="flex items-center space-x-2.5 p-1.5 rounded-md hover:bg-muted/60 cursor-pointer select-none transition-colors"
                            @click="handleToggleTarget(target.id)"
                        >
                            <!-- Case à cocher visuelle garantie -->
                            <div
                                class="h-4 w-4 rounded-[4px] border flex items-center justify-center transition-colors shrink-0"
                                :class="isTargetSelected(target.id) ? 'bg-primary border-primary text-white' : 'border-muted-foreground/40 bg-background'"
                            >
                                <Check v-if="isTargetSelected(target.id)" class="h-3 w-3 text-white stroke-[3]" />
                            </div>
                            <span class="text-xs font-medium text-foreground">
                                {{ target.label }}
                            </span>
                        </div>
                    </div>
                </PopoverContent>
            </Popover>

            <Button size="sm" class="h-9 px-4 text-xs font-semibold gap-1.5 shadow-2xs cursor-pointer" @click="apply">
                <Search class="h-3.5 w-3.5" />
                <span>Rechercher</span>
            </Button>
        </div>

        <!-- 2ème ligne : Filtres avancés (Cours, Statut, Séance, Inscription, Anniversaire) -->
        <div class="flex flex-wrap items-center gap-2 pt-1 border-t">
            <!-- 1. Filtre Cours -->
            <div class="min-w-[170px]">
                <Select v-model="courseId" @update:model-value="apply">
                    <SelectTrigger
                        class="h-9 text-xs bg-background gap-1.5 cursor-pointer"
                        :class="courseId !== 'all' ? 'border-primary text-primary font-semibold' : ''"
                    >
                        <GraduationCap class="h-3.5 w-3.5 shrink-0" :class="courseId !== 'all' ? 'text-primary' : 'text-muted-foreground'" />
                        <SelectValue placeholder="Inscrit au cours" />
                    </SelectTrigger>
                    <SelectContent class="max-h-60">
                        <SelectItem value="all" class="text-xs cursor-pointer">Tous les cours</SelectItem>
                        <SelectItem
                            v-for="c in courses"
                            :key="c.id"
                            :value="String(c.id)"
                            class="text-xs cursor-pointer"
                        >
                            {{ c.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- 2. Filtre Statut Module -->
            <div class="min-w-[170px]">
                <Select v-model="moduleStatus" @update:model-value="apply">
                    <SelectTrigger
                        class="h-9 text-xs bg-background gap-1.5 cursor-pointer"
                        :class="moduleStatus !== 'all' ? 'border-primary text-primary font-semibold' : ''"
                    >
                        <BookOpen class="h-3.5 w-3.5 shrink-0" :class="moduleStatus !== 'all' ? 'text-primary' : 'text-muted-foreground'" />
                        <SelectValue placeholder="Statut du module" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all" class="text-xs cursor-pointer">Tous les statuts</SelectItem>
                        <SelectItem value="active" class="text-xs font-medium text-emerald-700 dark:text-emerald-400 cursor-pointer">Module en cours (Actif)</SelectItem>
                        <SelectItem value="upcoming" class="text-xs font-medium text-sky-700 dark:text-sky-400 cursor-pointer">Module futur (À venir)</SelectItem>
                        <SelectItem value="completed" class="text-xs font-medium text-muted-foreground cursor-pointer">Module terminé (Passé)</SelectItem>
                        <SelectItem value="none" class="text-xs font-medium text-amber-700 dark:text-amber-400 cursor-pointer">Aucun module</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- 3. Popover Date de séance -->
            <DateFilterPopover
                v-model="lessonDateFilter"
                label="Date de séance"
                prefix-text="Séance"
                @apply="apply"
            />

            <!-- 4. Popover Date d'inscription -->
            <DateFilterPopover
                v-model="registrationFilter"
                label="Date d'inscription"
                prefix-text="Inscrit"
                @apply="apply"
            />

            <!-- 5. Popover Anniversaire -->
            <BirthdayFilterPopover
                v-model="birthdayFilter"
                label="Date d'anniversaire"
                @apply="apply"
            />

            <!-- 6. Bouton Réinitialiser -->
            <Button
                v-if="hasActiveFilters"
                type="button"
                variant="ghost"
                size="sm"
                class="h-9 text-xs text-muted-foreground hover:text-foreground gap-1.5 ml-auto cursor-pointer"
                @click="handleResetAll"
            >
                <RotateCcw class="h-3.5 w-3.5" />
                <span>Réinitialiser</span>
            </Button>
        </div>
    </div>
</template>
