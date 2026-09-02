<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from "@/Components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { Button } from "@/Components/ui/button";
import {
    VisXYContainer,
    VisGroupedBar,
    VisStackedBar,
    VisLine,
    VisAxis,
} from "@unovis/vue";
import {
    BarChart3,
    Layers3,
    TrendingUp,
    UserPlus,
    Layers,
} from "lucide-vue-next";

const props = defineProps({
    data: {
        type: Array,
        default: () => [],
    },
    selectedPeriod: {
        type: String,
        default: "6m",
    },
});

const chartType = ref("line"); // 'grouped' | 'stacked' | 'line'

const periodOptions = [
    { value: "1w", label: "Depuis 1 semaine" },
    { value: "1m", label: "Depuis 1 mois" },
    { value: "6m", label: "Depuis 6 mois" },
    { value: "1y", label: "Depuis 1 an" },
    { value: "3y", label: "Depuis 3 ans" },
    { value: "all", label: "Depuis toujours" },
];

const handlePeriodChange = (newPeriod) => {
    router.get(
        route("dashboard.index"),
        { period: newPeriod },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ["chartData", "selectedPeriod"],
        }
    );
};

// Accesseurs Unovis
const xAccessor = (d, i) => i;
const yAccessors = [
    (d) => d.users,   // Série 0: Nouveaux inscrits (Bleu Émail)
    (d) => d.modules, // Série 1: Modules réservés (Terracotta)
];
const xTickFormat = (i) => props.data[i]?.label || "";
</script>

<template>
    <Card class="shadow-xs flex flex-col justify-between">
        <CardHeader class="pb-2">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <CardTitle class="text-base font-semibold">Croissance & Réservations</CardTitle>
                    <CardDescription>Évolution des nouveaux membres et des modules réservés</CardDescription>
                </div>

                <!-- Filtres : Période + Type de vue -->
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Sélecteur de période -->
                    <Select :model-value="selectedPeriod" @update:model-value="handlePeriodChange">
                        <SelectTrigger class="h-8 w-[150px] text-xs">
                            <SelectValue placeholder="Choisir la période" />
                        </SelectTrigger>
                        <SelectContent align="end">
                            <SelectItem
                                v-for="opt in periodOptions"
                                :key="opt.value"
                                :value="opt.value"
                                class="text-xs"
                            >
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Bascule Type de graphique -->
                    <div class="flex items-center gap-0.5 bg-muted p-0.5 rounded-lg border">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-7 w-7 rounded-md"
                            :class="chartType === 'grouped' ? 'bg-background shadow-xs text-foreground' : 'text-muted-foreground'"
                            title="Barres groupées"
                            @click="chartType = 'grouped'"
                        >
                            <BarChart3 class="h-3.5 w-3.5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-7 w-7 rounded-md"
                            :class="chartType === 'stacked' ? 'bg-background shadow-xs text-foreground' : 'text-muted-foreground'"
                            title="Barres empilées"
                            @click="chartType = 'stacked'"
                        >
                            <Layers3 class="h-3.5 w-3.5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-7 w-7 rounded-md"
                            :class="chartType === 'line' ? 'bg-background shadow-xs text-foreground' : 'text-muted-foreground'"
                            title="Courbes de tendance"
                            @click="chartType = 'line'"
                        >
                            <TrendingUp class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Légende -->
            <div class="flex items-center gap-4 text-xs pt-3">
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-sky-600" />
                    <span class="text-muted-foreground flex items-center gap-1">
                        <UserPlus class="h-3 w-3 text-sky-600" /> Inscrits sur le site
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-orange-600" />
                    <span class="text-muted-foreground flex items-center gap-1">
                        <Layers class="h-3 w-3 text-orange-600" /> Modules réservés
                    </span>
                </div>
            </div>
        </CardHeader>

        <CardContent class="pt-2">
            <div
                v-if="data && data.length > 0"
                class="w-full h-[280px]"
                style="--vis-color0: var(--chart-1, #0284c7); --vis-color1: var(--chart-2, #ea580c);"
            >
                <VisXYContainer
                    :data="data"
                    :height="280"
                    :margin="{ top: 10, right: 10, bottom: 20, left: 20 }"
                >
                    <VisGroupedBar
                        v-if="chartType === 'grouped'"
                        :x="xAccessor"
                        :y="yAccessors"
                        :rounded-corners="4"
                        :bar-padding="0.2"
                        :group-padding="0.3"
                    />
                    <VisStackedBar
                        v-else-if="chartType === 'stacked'"
                        :x="xAccessor"
                        :y="yAccessors"
                        :rounded-corners="4"
                        :bar-padding="0.2"
                    />
                    <VisLine
                        v-else-if="chartType === 'line'"
                        :x="xAccessor"
                        :y="yAccessors"
                        :stroke-width="2.5"
                    />
                    <VisAxis
                        type="x"
                        :tick-format="xTickFormat"
                        :grid-line="false"
                        :tick-line="false"
                    />
                    <VisAxis
                        type="y"
                        :grid-line="true"
                        :tick-line="false"
                    />
                </VisXYContainer>
            </div>
            <div v-else class="h-[280px] flex items-center justify-center text-sm text-muted-foreground">
                Aucune donnée disponible pour cette période.
            </div>
        </CardContent>
    </Card>
</template>
