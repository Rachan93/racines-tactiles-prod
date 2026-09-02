<script setup>
import { computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Button } from "@/Components/ui/button";
import {
    Calendar,
    CalendarDays,
    Sparkles,
    Users,
    BookOpen,
    Blocks,
    ArrowRight,
    CheckCircle2,
    CalendarCheck,
    History,
    User,
} from "lucide-vue-next";
import UpcomingLessonsList from "@/Components/member/UpcomingLessonsList.vue";
import ModuleProgressCard from "@/Components/member/ModuleProgressCard.vue";
import FamilyManager from "@/Components/member/FamilyManager.vue";
import { pluralize } from "@/Utils/formatters";

const props = defineProps({
    modules: {
        type: Array,
        default: () => [],
    },
    upcomingEnrollments: {
        type: Array,
        default: () => [],
    },
    availableMakeups: {
        type: Array,
        default: () => [],
    },
    attendees: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

// 1. Modules actifs du titulaire (User connecté)
const myActiveModules = computed(() => {
    return props.modules.filter(
        (m) =>
            m.participant.type === "user" &&
            m.is_active &&
            m.completed_lessons < m.total_lessons,
    );
});

// 2. Modules actifs des invités / enfants (Attendees)
const attendeesActiveModules = computed(() => {
    return props.modules.filter(
        (m) =>
            m.participant.type === "attendee" &&
            m.is_active &&
            m.completed_lessons < m.total_lessons,
    );
});

// 3. Historique des modules terminés ou inactifs
const pastModules = computed(() => {
    return props.modules.filter(
        (m) => !m.is_active || m.completed_lessons >= m.total_lessons,
    );
});
const availableMakeupsByModule = computed(() => {
    return props.availableMakeups.reduce((counts, makeup) => {
        const moduleId = Number(makeup.module_id);

        counts[moduleId] = (counts[moduleId] ?? 0) + 1;

        return counts;
    }, {});
});
</script>

<template>
    <Nav />
    <div>
        <Head title="Mon Espace membre" />

        <div class="py-8 bg-white min-h-screen font-brand">
            <div class="max-w-6xl mx-auto px-6 lg:px-0 space-y-6">
                <!-- 1. En-tête de page & Accès Rapide au Planning -->
                <div
                    class="flex flex-col md:flex-row md:items-center justify-between gap-5"
                >
                    <div>
                        <h1
                            class="text-5xl leading-9 text-gray-900 sm:text-6xl sm:leading-10 mb-4"
                        >
                            Espace membre
                        </h1>
                        <p class="text-xl leading-7 text-gray-900">
                            Bonjour,
                           {{ currentUser?.first_name }}
                        </p>
                        <p
                            class="text-lg leading-8 text-gray-500 mt-2 max-w-2xl"
                        >
                            Retrouvez vos cours de céramique, gérez vos
                            présences et suivez l'avancement de vos modules.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <Link :href="route('calendrier.index')">
                            <Button
                                class="group bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium h-10 px-4 shadow hover:scale-[1.01] transition duration-150"
                            >
                                <Calendar class="w-4 h-4 mr-2" />
                                Réserver un module
                                <ArrowRight
                                    class="w-4 h-4 ml-2 group-hover:translate-x-0.5 transition-transform"
                                />
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- 2. Message Flash de Succès Global -->
                <div
                    v-if="$page.props.flash?.success"
                    class="p-4 bg-gray-50 border border-gray-200 text-gray-800 rounded-xl text-sm flex items-center gap-3 font-medium shadow"
                >
                    <CheckCircle2 class="w-4 h-4 text-gray-600 shrink-0" />
                    <span>{{ $page.props.flash.success }}</span>
                </div>

                <!-- 3. Indicateurs Clés (KPIs) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Séances à venir (Touche Sauge) -->
                    <div class="p-5 shadow flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-700 shrink-0"
                        >
                            <CalendarCheck class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">
                                Séances à venir
                            </p>
                            <p class="text-2xl font-bold text-gray-900 mt-0.5">
                                {{
                                    pluralize(
                                        upcomingEnrollments.length,
                                        "séance",
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Modules actifs de l'utilisateur et de ses proches -->
                    <div class="p-5 shadow flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-700 shrink-0"
                        >
                            <Blocks class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">
                                Modules actifs
                            </p>
                            <p class="text-2xl font-bold text-gray-900 mt-0.5">
                                {{
                                    pluralize(
                                        myActiveModules.length +
                                            attendeesActiveModules.length,
                                        "module",
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Proches rattachés (Neutre & Sobre) -->
                    <div class="p-5 shadow flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-700 shrink-0"
                        >
                            <Users class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">
                                Invités
                            </p>
                            <p class="text-2xl font-bold text-gray-900 mt-0.5">
                                {{ pluralize(attendees.length, "personne") }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 4. Bannière d'incitation Rattrapage (si crédits dispo) -->
                <div
                    v-if="availableMakeups.length > 0"
                    class="p-5 bg-gray-900 text-white rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow"
                >
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <Sparkles class="w-4 h-4 text-gray-300" />
                            <h3 class="text-base font-semibold text-white">
                                Vous avez
                                {{
                                    pluralize(availableMakeups.length, "crédit")
                                }}
                                de rattrapage en attente
                            </h3>
                        </div>
                        <p class="text-sm text-gray-300">
                            Placez vos rattrapages sur des places disponibles
                            dans le calendrier.
                        </p>
                    </div>

                    <Link :href="route('calendrier.index', { mode: 'makeup' })">
                        <Button
                            size="sm"
                            class="group bg-white text-gray-900 hover:bg-gray-100 text-sm font-medium h-9 px-4 shadow shrink-0"
                        >
                            Poser un rattrapage
                            <ArrowRight
                                class="w-4 h-4 ml-1.5 group-hover:translate-x-0.5 transition-transform"
                            />
                        </Button>
                    </Link>
                </div>

                <!-- 5. Navigation par 2 Onglets Principaux -->
                <Tabs default-value="sessions_modules" class="space-y-6">
                    <TabsList
                        class="grid grid-cols-2 w-full sm:w-[400px] bg-white p-1 h-11"
                    >
                        <TabsTrigger
                            value="sessions_modules"
                            class="text-sm font-medium hover:text-gray-900 hover:underline underline-offset-4 decoration-gray-400 decoration-1 data-[state=active]:bg-gray-100 data-[state=active]:text-gray-900 data-[state=active]:shadow rounded-lg transition-colors duration-150"
                        >
                            Séances & modules
                        </TabsTrigger>
                        <TabsTrigger
                            value="family"
                            class="text-sm font-medium hover:text-gray-900 hover:underline underline-offset-4 decoration-gray-400 decoration-1 data-[state=active]:bg-gray-100 data-[state=active]:text-gray-900 data-[state=active]:shadow rounded-lg transition-colors duration-150"
                        >
                            Mes invités ({{ attendees.length }})
                        </TabsTrigger>
                    </TabsList>

                    <!-- ========================================================= -->
                    <!-- ONGLET 1 : SÉANCES & MODULES DU COMPTE                    -->
                    <!-- ========================================================= -->
                    <TabsContent value="sessions_modules" class="space-y-8">
                        <!-- A. PROCHAINES SÉANCES -->
                        <div class="bg-white p-6 sm:p-7 space-y-4">
                            <div>
                                <h2
                                    class="text-lg font-semibold text-gray-900 flex items-center gap-2"
                                >
                                    <CalendarDays
                                        class="w-5 h-5 text-gray-700"
                                    />
                                    Planning des séances
                                </h2>
                                <p
                                    class="text-sm text-gray-500 mt-3 border-t-2 border-gray-100 pt-4"
                                >
                                    Vos prochaines séances réservées. Vous
                                    pouvez déclarer une absence pour libérer
                                    votre place.
                                </p>
                            </div>

                            <UpcomingLessonsList
                                :enrollments="upcomingEnrollments"
                            />
                        </div>

                        <!-- B. MES MODULES (Titulaire) -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3
                                    class="text-base font-semibold text-gray-900 flex items-center gap-2"
                                >
                                    <User class="w-4 h-4 text-gray-900" />
                                    Mes Modules
                                </h3>
                                <span class="text-sm text-gray-500 font-medium">
                                    {{
                                        pluralize(
                                            myActiveModules.length,
                                            "module actif",
                                            "modules actifs",
                                        )
                                    }}
                                </span>
                            </div>

                            <div
                                v-if="myActiveModules.length === 0"
                                class="p-8 text-center bg-gray-50 border border-dashed border-gray-300 rounded-xl shadow space-y-2"
                            >
                                <BookOpen
                                    class="w-7 h-7 mx-auto text-gray-400"
                                />
                                <h4 class="text-sm font-semibold text-gray-700">
                                    Aucun module actif pour vous
                                </h4>
                                <p
                                    class="text-sm text-gray-500 max-w-sm mx-auto"
                                >
                                    Vous n'avez pas de module personnel en
                                    cours. Réservez votre cycle de cours sur le
                                    planning.
                                </p>
                            </div>

                            <div
                                v-else
                                class="grid grid-cols-1 md:grid-cols-2 gap-4"
                            >
                                <ModuleProgressCard
                                    v-for="module in myActiveModules"
                                    :key="module.id"
                                    :module="module"
                                    :is-owner="true"
                                    :available-makeup-credits="
                                        availableMakeupsByModule[module.id] ?? 0
                                    "
                                />
                            </div>
                        </div>

                        <!-- C. MODULES DES PROCHES / ENFANTS -->
                        <div
                            v-if="attendeesActiveModules.length > 0"
                            class="space-y-4 pt-2"
                        >
                            <div class="flex items-center justify-between">
                                <h3
                                    class="text-base font-semibold text-gray-900 flex items-center gap-2"
                                >
                                    <Users class="w-4 h-4 text-gray-900" />
                                    Modules de mes invités
                                </h3>
                                <span class="text-sm text-gray-500 font-medium">
                                    {{
                                        pluralize(
                                            attendeesActiveModules.length,
                                            "module actif",
                                            "modules actifs",
                                        )
                                    }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <ModuleProgressCard
                                    v-for="module in attendeesActiveModules"
                                    :key="module.id"
                                    :module="module"
                                    :is-owner="false"
                                    :has-available-makeup="
                                        availableMakeups.some(
                                            (makeup) =>
                                                makeup.module_id === module.id,
                                        )
                                    "
                                />
                            </div>
                        </div>

                        <!-- D. HISTORIQUE DES MODULES TERMINÉS -->
                        <div
                            v-if="pastModules.length > 0"
                            class="space-y-4 pt-6 border-t border-gray-200"
                        >
                            <h3
                                class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-2"
                            >
                                <History class="w-4 h-4 text-gray-400" />
                                Historique & Modules terminés
                            </h3>

                            <div
                                class="grid grid-cols-1 md:grid-cols-2 gap-4 opacity-80"
                            >
                                <ModuleProgressCard
                                    v-for="module in pastModules"
                                    :key="module.id"
                                    :module="module"
                                    :is-owner="
                                        module.participant.type === 'user'
                                    "
                                />
                            </div>
                        </div>
                    </TabsContent>

                    <!-- ========================================================= -->
                    <!-- ONGLET 2 : MA FAMILLE & PROCHES                           -->
                    <!-- ========================================================= -->
                    <TabsContent value="family">
                        <div class="p-6 sm:p-7">
                            <FamilyManager :attendees="attendees" />
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </div>
    </div>
    <Footer />
</template>
