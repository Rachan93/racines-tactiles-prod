<script setup>
import { computed } from "vue";
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from "@/Components/ui/card";
import {
    VisSingleContainer,
    VisDonut,
} from "@unovis/vue";
import { PieChart, BookOpen } from "lucide-vue-next";

const props = defineProps({
    distribution: {
        type: Array,
        default: () => [],
    },
});

const totalModules = computed(() => {
    return props.distribution.reduce((acc, curr) => acc + (curr.value || 0), 0);
});

const getPercentage = (value) => {
    if (!totalModules.value || totalModules.value === 0) return 0;
    return Math.round((value / totalModules.value) * 100);
};
</script>

<template>
    <Card class="shadow-xs flex flex-col justify-between">
        <CardHeader class="pb-2">
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="text-base font-semibold">Formules & Types de cours</CardTitle>
                    <CardDescription>Répartition des modules réservés</CardDescription>
                </div>
                <div class="h-8 w-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <PieChart class="h-4 w-4" />
                </div>
            </div>
        </CardHeader>

        <CardContent class="pt-2 flex flex-col items-center justify-center">
            <div v-if="totalModules > 0" class="w-full">
                <!-- Graphique Donut -->
                <div class="h-[200px] flex items-center justify-center">
                    <VisSingleContainer :data="distribution" :height="200">
                        <VisDonut
                            :value="(d) => d.value"
                            :color="(d) => d.color"
                            :arc-width="28"
                            :central-label="`${totalModules}`"
                            :central-sub-label="'Modules'"
                        />
                    </VisSingleContainer>
                </div>

                <!-- Grille détaillée des types de cours avec pourcentages -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 pt-3 border-t">
                    <div
                        v-for="item in distribution"
                        :key="item.id"
                        class="p-2 rounded-lg bg-muted/50 flex flex-col justify-between gap-1"
                    >
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span
                                class="h-2 w-2 rounded-full shrink-0"
                                :style="{ backgroundColor: item.color }"
                            />
                            <span class="text-xs text-muted-foreground truncate font-medium">
                                {{ item.label }}
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between mt-0.5">
                            <span class="font-bold text-sm text-foreground">
                                {{ item.value }}
                            </span>
                            <span class="text-[11px] text-muted-foreground">
                                {{ getPercentage(item.value) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- État vide -->
            <div v-else class="h-[240px] flex flex-col items-center justify-center text-center p-4">
                <div class="h-10 w-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground mb-2">
                    <BookOpen class="h-5 w-5" />
                </div>
                <p class="text-sm font-medium text-foreground">Aucun module enregistré</p>
                <p class="text-xs text-muted-foreground mt-0.5">
                    Les statistiques s'afficheront dès les premières réservations.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
