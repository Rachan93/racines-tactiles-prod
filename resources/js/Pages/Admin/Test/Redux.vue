<script setup>
import { ref, computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import TabNavigation from "@/Components/test/TabNavigation.vue";
import StatisticsTab from "@/Components/test/StatisticsTab.vue";
import UsersTab from "@/Components/test/UsersTab.vue";
import CoursesTab from "@/Components/test/CoursesTab.vue";
import LessonsTab from "@/Components/test/LessonsTab.vue";
import ModulesTab from "@/Components/test/ModulesTab.vue";
import AttendeesTab from "@/Components/test/AttendeesTab.vue";

const props = defineProps({
    users: Array,
    activeCourses: Array,
    upcomingLessons: Array,
    modules: Array,
    attendees: Array,
    stats: Object,
    lastUpdate: String,
    pagination: Object,
    sorting: Object,
});

const activeTab = ref("stats");

// Prépare les onglets avec leurs données respectives
const tabs = computed(() => [
    { id: "stats", label: "Statistiques" },
    { id: "users", label: "Utilisateurs", count: props.users.length },
    { id: "courses", label: "Cours actifs", count: props.activeCourses.length },
    {
        id: "lessons",
        label: "Séances à venir",
        count: props.upcomingLessons.length,
    },
    { id: "modules", label: "Modules", count: props.modules.length },
    { id: "attendees", label: "Invités", count: props.attendees.length },
]);
</script>

<template>
    <AppLayout title="Test Data Redux">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Test Redux - Vue d'ensemble
                </h2>
                <div class="text-sm text-gray-500">
                    Dernière mise à jour: {{ lastUpdate }}
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6"
                >
                    <!-- Navigation par onglets -->
                    <TabNavigation v-model:activeTab="activeTab" :tabs="tabs" />

                    <!-- Contenu des onglets -->
                    <StatisticsTab
                        v-if="activeTab === 'stats'"
                        :stats="stats"
                    />
                    <UsersTab
                        v-if="activeTab === 'users'"
                        :users="users"
                        :pagination="pagination"
                        :sorting="sorting"
                    />
                    <CoursesTab
                        v-if="activeTab === 'courses'"
                        :activeCourses="activeCourses"
                    />
                    <LessonsTab
                        v-if="activeTab === 'lessons'"
                        :upcomingLessons="upcomingLessons"
                    />
                    <ModulesTab
                        v-if="activeTab === 'modules'"
                        :modules="modules"
                    />
                    <AttendeesTab
                        v-if="activeTab === 'attendees'"
                        :attendees="attendees"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
