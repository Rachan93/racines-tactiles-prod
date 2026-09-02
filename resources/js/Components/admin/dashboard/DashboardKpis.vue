<script setup>
import { Card, CardHeader, CardTitle, CardContent } from "@/Components/ui/card";
import {
    Layers,
    Users,
    CalendarCheck,
    RotateCcw,
    ArrowUpRight,
    ArrowDownRight,
} from "lucide-vue-next";

defineProps({
    kpis: {
        type: Object,
        required: true,
        default: () => ({
            activeModules: { value: 0, newThisMonth: 0, growth: null },
            community: { total: 0, users: 0, attendees: 0, newThisMonth: 0 },
            occupancy: { rate: 0, bookedSpots: 0, totalSpots: 0 },
            absences: { availableForMakeup: 0 },
        }),
    },
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- KPI 1 : Modules en cours -->
        <Card class="shadow-xs">
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"
                    >Modules en cours</CardTitle
                >
                <div
                    class="h-8 w-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0"
                >
                    <Layers class="h-4 w-4" />
                </div>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold tracking-tight">
                    {{ kpis.activeModules.value }}
                </div>
                <div
                    class="flex items-center text-xs text-muted-foreground mt-1 gap-1.5 flex-wrap"
                >
                    <span
                        v-if="kpis.activeModules.growth !== null"
                        class="inline-flex items-center font-semibold"
                        :class="
                            kpis.activeModules.growth >= 0
                                ? 'text-emerald-600'
                                : 'text-rose-600'
                        "
                    >
                        <component
                            :is="
                                kpis.activeModules.growth >= 0
                                    ? ArrowUpRight
                                    : ArrowDownRight
                            "
                            class="h-3.5 w-3.5 mr-0.5 shrink-0"
                        />
                        {{ Math.abs(kpis.activeModules.growth) }}%
                    </span>
                    <span
                        >+{{ kpis.activeModules.newThisMonth }} ce mois-ci</span
                    >
                </div>
            </CardContent>
        </Card>

        <!-- KPI 2 : Élèves & Membres -->
        <Card class="shadow-xs">
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">
                    Membres & Invités
                </CardTitle>
                <div
                    class="h-8 w-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0"
                >
                    <Users class="h-4 w-4" />
                </div>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold tracking-tight">
                    {{ kpis.community.total }}
                </div>
                <p class="text-xs text-muted-foreground mt-1 truncate">
                    {{ kpis.community.users }} membres •
                    {{ kpis.community.attendees }} invités
                </p>
            </CardContent>
        </Card>

        <!-- KPI 3 : Taux d'occupation 7 jours -->
        <Card class="shadow-xs">
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"
                    >Occupation (7 jours)</CardTitle
                >
                <div
                    class="h-8 w-8 rounded-lg bg-sky-500/10 text-sky-600 flex items-center justify-center shrink-0"
                >
                    <CalendarCheck class="h-4 w-4" />
                </div>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold tracking-tight">
                    {{ kpis.occupancy.rate }}%
                </div>
                <p class="text-xs text-muted-foreground mt-1 truncate">
                    {{ kpis.occupancy.bookedSpots }} /
                    {{ kpis.occupancy.totalSpots }} places réservées
                </p>
            </CardContent>
        </Card>

<!-- KPI 4 : Crédits de rattrapage disponibles -->
<Card class="shadow-xs">
    <CardHeader
        class="flex flex-row items-center justify-between space-y-0 pb-2"
    >
        <CardTitle class="text-sm font-medium">
            Rattrapages disponibles
        </CardTitle>

        <div
            class="h-8 w-8 rounded-lg bg-purple-500/10 text-purple-600 flex items-center justify-center shrink-0"
        >
            <RotateCcw class="h-4 w-4" />
        </div>
    </CardHeader>

    <CardContent>
        <div class="text-2xl font-bold tracking-tight">
            {{ kpis.absences.availableForMakeup }}
        </div>

        <p class="text-xs text-muted-foreground mt-1">
            Crédits de rattrapage non utilisés
        </p>
    </CardContent>
</Card>
</div>
</template>
