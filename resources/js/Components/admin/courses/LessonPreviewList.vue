<script setup>
import { ref, computed } from "vue";
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from "@/Components/ui/card";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Checkbox } from "@/Components/ui/checkbox";
import { Label } from "@/Components/ui/label";
import {
    CalendarDays,
    Clock,
    AlertCircle,
    CalendarX,
    ScanEye,
    Loader2,
    ChevronDown,
    ChevronUp,
    SlidersHorizontal,
} from "lucide-vue-next";

const props = defineProps({
    schedule: {
        type: Object,
        default: () => ({
            generated_dates: [],
            skipped_dates: [],
            total_generated: 0,
            total_skipped: 0,
            first_date: null,
            last_date: null,
            error: null,
        }),
    },
    modelValue: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    startTime: {
        type: String,
        default: "",
    },
    endTime: {
        type: String,
        default: "",
    },
    excludePublicHolidays: {
        type: Boolean,
        default: true,
    },
    excludeSchoolHolidays: {
        type: Boolean,
        default: true,
    },
    excludeStudioClosures: {
        type: Boolean,
        default: true,
    },
    excludeWeekends: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    "update:modelValue",
    "update:excludePublicHolidays",
    "update:exclude-public-holidays",
    "update:excludeSchoolHolidays",
    "update:exclude-school-holidays",
    "update:excludeStudioClosures",
    "update:exclude-studio-closures",
    "update:excludeWeekends",
    "update:exclude-weekends",
]);

const showSkipped = ref(false);

const isDateSelected = (dateStr) => {
    return props.modelValue.includes(dateStr);
};

const toggleDate = (dateStr) => {
    const current = [...props.modelValue];
    const index = current.indexOf(dateStr);
    if (index > -1) {
        current.splice(index, 1);
    } else {
        current.push(dateStr);
    }
    emit("update:modelValue", current);
};

const selectAll = () => {
    const all = (props.schedule.generated_dates || []).map((d) => d.date);
    emit("update:modelValue", all);
};

const deselectAll = () => {
    emit("update:modelValue", []);
};

const isAllSelected = computed(() => {
    const total = props.schedule.generated_dates?.length || 0;
    return total > 0 && props.modelValue.length === total;
});

// Gestionnaire robuste de bascule des exclusions
const toggleExclude = (key, val) => {
    const boolVal = Boolean(val);
    if (key === "public") {
        emit("update:excludePublicHolidays", boolVal);
        emit("update:exclude-public-holidays", boolVal);
    } else if (key === "school") {
        emit("update:excludeSchoolHolidays", boolVal);
        emit("update:exclude-school-holidays", boolVal);
    } else if (key === "closures") {
        emit("update:excludeStudioClosures", boolVal);
        emit("update:exclude-studio-closures", boolVal);
    } else if (key === "weekends") {
        emit("update:excludeWeekends", boolVal);
        emit("update:exclude-weekends", boolVal);
    }
};
</script>

<template>
    <Card class="shadow-xs border-primary/20 bg-background flex flex-col h-full sticky top-20">
        <!-- En-tête du Panneau -->
        <CardHeader class="pb-3 border-b">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0">
                        <ScanEye class="h-4 w-4" />
                    </div>
                    <div>
                        <CardTitle class="text-base font-semibold">Prévisualisation du planning</CardTitle>
                        <CardDescription class="text-xs">Séances générées automatiquement</CardDescription>
                    </div>
                </div>

                <!-- Indicateur de chargement -->
                <div v-if="loading" class="flex items-center gap-1.5 text-xs text-muted-foreground animate-pulse">
                    <Loader2 class="h-3.5 w-3.5 animate-spin text-primary" />
                    <span>Calcul...</span>
                </div>
            </div>

            <!-- Récapitulatif Statistique -->
            <div class="grid grid-cols-2 gap-2 pt-3">
                <div class="p-2.5 rounded-lg bg-primary/5 border border-primary/10 flex flex-col">
                    <span class="text-[11px] text-muted-foreground font-medium">Séances retenues</span>
                    <span class="text-xl font-bold text-primary">
                        {{ modelValue.length }}
                        <span class="text-xs font-normal text-muted-foreground">/ {{ schedule.total_generated || 0 }}</span>
                    </span>
                </div>

                <div class="p-2.5 rounded-lg bg-muted/60 border flex flex-col">
                    <span class="text-[11px] text-muted-foreground font-medium">Séances sautées (congés)</span>
                    <span class="text-xl font-bold text-foreground">
                        {{ schedule.total_skipped || 0 }}
                    </span>
                </div>
            </div>

            <!-- Actions globales -->
            <div class="flex items-center justify-between pt-2">
                <span class="text-xs text-muted-foreground">
                    Cliquez sur une séance pour l'inclure ou l'exclure
                </span>
                <div class="flex items-center gap-1.5">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 text-xs px-2"
                        @click="isAllSelected ? deselectAll() : selectAll()"
                    >
                        {{ isAllSelected ? 'Tout désélectionner' : 'Tout sélectionner' }}
                    </Button>
                </div>
            </div>
        </CardHeader>

        <!-- Liste des Séances Scrollable -->
        <CardContent class="flex-1 overflow-y-auto p-4 space-y-4 max-h-[380px]">
            <!-- Message d'erreur de date si début > fin -->
            <div v-if="schedule.error" class="p-3 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-xs flex items-center gap-2">
                <AlertCircle class="h-4 w-4 shrink-0" />
                <span>{{ schedule.error }}</span>
            </div>

            <!-- Séances Valides -->
            <div v-else-if="schedule.generated_dates && schedule.generated_dates.length > 0" class="space-y-1.5">
                <div
                    v-for="(item, idx) in schedule.generated_dates"
                    :key="item.date"
                    class="flex items-center justify-between p-2.5 rounded-lg border transition-colors select-none cursor-pointer"
                    :class="[
                        isDateSelected(item.date)
                            ? 'bg-primary/5 border-primary/30 hover:bg-primary/10'
                            : 'bg-muted/30 border-transparent opacity-60 hover:opacity-100',
                    ]"
                    @click="toggleDate(item.date)"
                >
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-foreground">
                                Séance #{{ idx + 1 }}
                            </span>
                            <span class="text-xs font-medium text-foreground">
                                {{ item.date_formatted }}
                            </span>
                        </div>
                        <p v-if="startTime && endTime" class="text-[11px] text-muted-foreground flex items-center gap-1 mt-0.5">
                            <Clock class="h-3 w-3" />
                            {{ startTime }} à {{ endTime }}
                        </p>
                    </div>

                    <Badge
                        :variant="isDateSelected(item.date) ? 'default' : 'outline'"
                        class="text-[10px] font-normal py-0 px-2 shrink-0"
                    >
                        {{ isDateSelected(item.date) ? 'Incluse' : 'Exclue' }}
                    </Badge>
                </div>
            </div>

            <!-- État vide avant sélection -->
            <div v-else class="text-center py-10 text-muted-foreground text-xs space-y-2">
                <CalendarDays class="h-8 w-8 mx-auto text-muted-foreground/40" />
                <p class="font-medium text-foreground text-sm">Aucune date calculée</p>
                <p>Renseignez les dates de début, de fin et la fréquence pour voir apparaître les séances.</p>
            </div>

            <!-- Section dépliable des séances sautées (Congés & Vacances) -->
            <div v-if="schedule.skipped_dates && schedule.skipped_dates.length > 0" class="pt-2 border-t">
                <button
                    type="button"
                    class="flex items-center justify-between w-full text-xs font-medium text-muted-foreground hover:text-foreground py-1"
                    @click="showSkipped = !showSkipped"
                >
                    <span class="flex items-center gap-1.5">
                        <CalendarX class="h-3.5 w-3.5 text-amber-600" />
                        <span>Voir les {{ schedule.skipped_dates.length }} dates sautées (congés)</span>
                    </span>
                    <component :is="showSkipped ? ChevronUp : ChevronDown" class="h-3.5 w-3.5" />
                </button>

                <div v-if="showSkipped" class="mt-2 space-y-1.5">
                    <div
                        v-for="skipped in schedule.skipped_dates"
                        :key="skipped.date"
                        class="flex items-center justify-between p-2 rounded-lg bg-amber-500/5 border border-amber-500/20 text-xs"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-muted-foreground line-through shrink-0">{{ skipped.date_formatted }}</span>
                        </div>
                        <Badge variant="outline" class="text-[10px] bg-amber-500/10 text-amber-700 border-amber-300 dark:text-amber-400 font-normal shrink-0">
                            {{ skipped.reason }}
                        </Badge>
                    </div>
                </div>
            </div>
        </CardContent>

        <!-- Bloc Dédié des Règles d'Exclusion & Congés -->
        <div class="p-4 border-t bg-muted/20 space-y-3 shrink-0 rounded-b-xl">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-foreground uppercase tracking-wider flex items-center gap-1.5">
                    <SlidersHorizontal class="h-3.5 w-3.5 text-primary" /> Filtres d'exclusion
                </span>
                <span class="text-[10px] text-muted-foreground">Impact direct sur le calcul</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <!-- 1. Jours fériés -->
                <div class="flex items-center space-x-2.5 p-2 rounded-md border bg-background hover:bg-muted/30 transition-colors">
                    <Checkbox
                        id="prev_exclude_public"
                        :checked="Boolean(excludePublicHolidays)"
                        :model-value="Boolean(excludePublicHolidays)"
                        @update:checked="(val) => toggleExclude('public', val)"
                        @update:model-value="(val) => toggleExclude('public', val)"
                    />
                    <Label for="prev_exclude_public" class="text-xs font-medium cursor-pointer select-none leading-tight flex-1">
                        Jours fériés (BE)
                    </Label>
                </div>

                <!-- 2. Vacances scolaires -->
                <div class="flex items-center space-x-2.5 p-2 rounded-md border bg-background hover:bg-muted/30 transition-colors">
                    <Checkbox
                        id="prev_exclude_school"
                        :checked="Boolean(excludeSchoolHolidays)"
                        :model-value="Boolean(excludeSchoolHolidays)"
                        @update:checked="(val) => toggleExclude('school', val)"
                        @update:model-value="(val) => toggleExclude('school', val)"
                    />
                    <Label for="prev_exclude_school" class="text-xs font-medium cursor-pointer select-none leading-tight flex-1">
                        Vacances scolaires
                    </Label>
                </div>

                <!-- 3. Fermetures atelier -->
                <div class="flex items-center space-x-2.5 p-2 rounded-md border bg-background hover:bg-muted/30 transition-colors">
                    <Checkbox
                        id="prev_exclude_closures"
                        :checked="Boolean(excludeStudioClosures)"
                        :model-value="Boolean(excludeStudioClosures)"
                        @update:checked="(val) => toggleExclude('closures', val)"
                        @update:model-value="(val) => toggleExclude('closures', val)"
                    />
                    <Label for="prev_exclude_closures" class="text-xs font-medium cursor-pointer select-none leading-tight flex-1">
                        Fermetures atelier
                    </Label>
                </div>

                <!-- 4. Week-ends -->
                <div class="flex items-center space-x-2.5 p-2 rounded-md border bg-background hover:bg-muted/30 transition-colors">
                    <Checkbox
                        id="prev_exclude_weekends"
                        :checked="Boolean(excludeWeekends)"
                        :model-value="Boolean(excludeWeekends)"
                        @update:checked="(val) => toggleExclude('weekends', val)"
                        @update:model-value="(val) => toggleExclude('weekends', val)"
                    />
                    <Label for="prev_exclude_weekends" class="text-xs font-medium cursor-pointer select-none leading-tight flex-1">
                        Exclure les week-ends
                    </Label>
                </div>
            </div>
        </div>
    </Card>
</template>
