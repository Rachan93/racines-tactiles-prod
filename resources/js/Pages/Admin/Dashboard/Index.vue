<script setup>
import { Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Button } from "@/Components/ui/button";

// Composants factorisés du Dashboard
import DashboardKpis from "@/Components/admin/dashboard/DashboardKpis.vue";
import ActivityChart from "@/Components/admin/dashboard/ActivityChart.vue";
import ModuleTypesDonut from "@/Components/admin/dashboard/ModuleTypesDonut.vue";
import UpcomingLessonsCard from "@/Components/admin/dashboard/UpcomingLessonsCard.vue";
import RecentModulesCard from "@/Components/admin/dashboard/RecentModulesCard.vue";
import RecentUsersCard from "@/Components/admin/dashboard/RecentUsersCard.vue";

import { Users, Calendar, ArrowRight } from "lucide-vue-next";

defineProps({
    kpis: {
        type: Object,
        required: true,
    },
    selectedPeriod: {
        type: String,
        default: "6m",
    },
    chartData: {
        type: Array,
        default: () => [],
    },
    moduleTypesDistribution: {
        type: Array,
        default: () => [],
    },
    upcomingLessons: {
        type: Array,
        default: () => [],
    },
    recentModules: {
        type: Array,
        default: () => [],
    },
    recentUsers: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <AdminLayout title="Tableau de bord">
        <div class="space-y-8">
            <!-- ========================================================= -->
            <!-- 1. EN-TÊTE DE PAGE & ACTIONS RAPIDES                      -->
            <!-- ========================================================= -->
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <h2
                        class="text-2xl font-bold tracking-tight text-foreground"
                    >
                        Vue d'ensemble
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Suivi global des élèves, des réservations et du planning
                        de l'atelier.
                    </p>
                </div>
                <div class="flex items-center gap-2.5">
                    <Button
                        variant="outline"
                        size="sm"
                        as-child
                        class="gap-1.5 shadow-xs"
                    >
                        <Link :href="route('users.index')">
                            <Users class="h-4 w-4" />
                            <span>Répertoire des utilisateurs</span>
                        </Link>
                    </Button>
                    <Button
                        as-child
                        class="group bg-primary text-primary-foreground"
                    >
                        <Link :href="route('courses.index')">
                            <span>Gérer les cours</span>

                            <ArrowRight
                                class="ml-2 h-4 w-4 transition-transform duration-200 group-hover:translate-x-1"
                            />
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 2. CARTES DE KPI                                          -->
            <!-- ========================================================= -->
            <DashboardKpis :kpis="kpis" />

            <!-- ========================================================= -->
            <!-- 3. SECTION GRAPHIQUES (ÉVOLUTION + DONUT FORMULES)        -->
            <!-- ========================================================= -->
            <div class="grid gap-6 lg:grid-cols-7">
                <!-- Graphique d'évolution Inscrits vs Modules avec sélecteur de période -->
                <ActivityChart
                    class="lg:col-span-4"
                    :data="chartData"
                    :selected-period="selectedPeriod"
                />

                <!-- Graphique Donut de répartition des formules de cours -->
                <ModuleTypesDonut
                    class="lg:col-span-3"
                    :distribution="moduleTypesDistribution"
                />
            </div>

            <!-- ========================================================= -->
            <!-- 4. PROCHAINES SÉANCES DU STUDIO (Anti-débordement)        -->
            <!-- ========================================================= -->
            <UpcomingLessonsCard :lessons="upcomingLessons" />

            <!-- ========================================================= -->
            <!-- 5. DERNIÈRES ACTIVITÉS (RÉSERVATIONS & MEMBRES)           -->
            <!-- ========================================================= -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Derniers modules souscrits -->
                <RecentModulesCard :modules="recentModules" />

                <!-- Derniers membres inscrits sur le site -->
                <RecentUsersCard :users="recentUsers" />
            </div>
        </div>
    </AdminLayout>
</template>
