<script setup>
import { ref, computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";
import BookingConfirmationModal from "@/Components/calendar/BookingConfirmationModal.vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import {
    Calendar,
    Clock,
    User,
    Euro,
    ArrowRight,
    Camera,
    Info,
    CheckCircle2,
    AlertCircle,
} from "lucide-vue-next";
import {
    formatPrice,
    formatDate,
    formatDateRange,
    pluralize,
} from "@/Utils/formatters";

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    attendees: {
        type: Array,
        default: () => [],
    },
    activeAbsences: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

// --- Gestion des langues par carte (FR par défaut, EN si sélectionné) ---
const activeLangs = ref({});

const getLang = (stageId) => activeLangs.value[stageId] || "fr";

const setLang = (stageId, lang) => {
    activeLangs.value[stageId] = lang;
};

// Accesseurs de texte bilingue
const getStageTitle = (stage) => {
    const lang = getLang(stage.id);
    return lang === "en" && stage.name_en ? stage.name_en : stage.name;
};

const getStageSubtitle = (stage) => {
    const lang = getLang(stage.id);
    return lang === "en" && stage.subtitle_en
        ? stage.subtitle_en
        : stage.subtitle;
};

const getStageDescription = (stage) => {
    const lang = getLang(stage.id);
    return lang === "en" && stage.description_en
        ? stage.description_en
        : stage.description;
};

const getStagePracticalInfo = (stage) => {
    const lang = getLang(stage.id);
    return lang === "en" && stage.practical_info_en
        ? stage.practical_info_en
        : stage.practical_info;
};

// --- Gestion de la réservation / Modale ---
const isBookingModalOpen = ref(false);
const selectedLesson = ref(null);

const handleStageBooking = (stage) => {
    // Si l'utilisateur n'est pas connecté, redirection login
    if (!currentUser.value) {
        window.location.href = route("login");
        return;
    }

    if (stage.first_lesson) {
        selectedLesson.value = stage.first_lesson;
        isBookingModalOpen.value = true;
    }
};

// Calcul des places restantes sur le premier cours du stage
const getRemainingSpots = (stage) => {
    if (!stage.first_lesson) return 0;
    const wheel = stage.first_lesson.wheel?.standard_available ?? 0;
    const hand = stage.first_lesson.handbuilding?.standard_available ?? 0;
    return wheel + hand;
};

// Formatage de la date du stage
const formatStageDates = (stage) => {
    if (!stage.first_lesson_date) return "";
    if (stage.end_date && stage.end_date !== stage.first_lesson_date) {
        return formatDateRange(stage.first_lesson_date, stage.end_date);
    }
    return formatDate(stage.first_lesson_date);
};
</script>

<template>
    <Head title="Stages & Masterclasses" />
    <Nav />

    <section id="stages" class="py-12 font-brand min-h-screen bg-gray-50/50">
        <div class="pt-2 pb-20 px-4 sm:px-6 lg:pt-0 lg:pb-28 lg:px-8">
            <div class="relative max-w-lg mx-auto lg:max-w-6xl">
                <!-- En-tête de la page -->
                <div class="mb-14">
                    <h1
                        class="text-5xl leading-9 text-gray-900 sm:text-6xl sm:leading-10 mb-4"
                    >
                        Stages & Masterclasses
                    </h1>
                    <p class="text-lg text-gray-600 max-w-3xl leading-relaxed">
                        Des formats immersifs pour explorer des techniques
                        singulières, perfectionner votre pratique du tournage ou
                        vous initier auprès de céramistes invités.
                    </p>
                </div>

                <!-- Si aucun stage n'est programmé -->
                <div
                    v-if="categories.length === 0"
                    class="p-12 text-center bg-white rounded-2xl border border-gray-200 shadow-xs"
                >
                    <Camera
                        class="w-10 h-10 mx-auto text-gray-400 mb-3 stroke-1.5"
                    />
                    <h3 class="text-lg font-semibold text-gray-900">
                        Aucun stage programmé pour le moment
                    </h3>
                    <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                        Les prochaines sessions et masterclasses seront
                        annoncées prochainement. Consultez régulièrement notre
                        calendrier.
                    </p>
                </div>

                <!-- Boucle sur les catégories triées (Tournage -> Extérieur -> Thématique -> Ponctuel) -->
                <div v-else class="space-y-20">
                    <div
                        v-for="category in categories"
                        :key="category.key"
                        class="space-y-8"
                    >
                        <!-- En-tête de la Catégorie -->
                        <div class="border-b border-gray-200 pb-4">
                            <h2
                                class="text-3xl leading-8 font-semibold text-gray-900 flex items-center gap-3"
                            >
                                {{ category.title }}
                            </h2>
                            <p
                                v-if="category.description"
                                class="mt-2 text-sm sm:text-base text-gray-600"
                            >
                                {{ category.description }}
                            </p>
                        </div>

                        <!-- Grille des Cartes de Stages -->
                        <div class="grid grid-cols-1 gap-10">
                            <div
                                v-for="stage in category.stages"
                                :key="stage.id"
                                class="bg-white rounded-2xl border border-gray-200/90 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden flex flex-col lg:flex-row"
                            >
                                <!-- Image de Couverture -->
                                <div
                                    class="lg:w-2/5 relative bg-gray-100 min-h-[260px] lg:min-h-[340px]"
                                >
                                    <img
                                        v-if="stage.cover_image"
                                        :src="stage.cover_image"
                                        :alt="stage.name"
                                        loading="lazy"
                                        class="w-full h-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="w-full h-full flex flex-col items-center justify-center bg-stone-100 text-stone-400 p-6 text-center"
                                    >
                                        <div
                                            class="w-14 h-14 rounded-full bg-white/80 border border-stone-200 flex items-center justify-center mb-3"
                                        >
                                            <Camera
                                                class="w-6 h-6 stroke-1.5"
                                            />
                                        </div>

                                        <span
                                            class="text-sm font-medium text-stone-500"
                                        >
                                            {{ category.title }}
                                        </span>

                                        <span
                                            class="text-[11px] text-stone-400 mt-1"
                                        >
                                            Photo à venir
                                        </span>
                                    </div>

                                    <!-- Badge Places Disponibles en overlay sur l'image -->
                                    <div class="absolute top-4 left-4 z-10">
                                        <Badge
                                            v-if="getRemainingSpots(stage) > 0"
                                            class="bg-emerald-600 hover:bg-emerald-600 text-white font-semibold text-xs px-3 py-1 shadow-xs"
                                        >
                                            {{
                                                pluralize(
                                                    getRemainingSpots(stage),
                                                    "place restante",
                                                )
                                            }}
                                        </Badge>
                                        <Badge
                                            v-else
                                            variant="destructive"
                                            class="bg-red-600 text-white font-semibold text-xs px-3 py-1 shadow-xs"
                                        >
                                            Complet
                                        </Badge>
                                    </div>
                                </div>

                                <!-- Contenu Principal du Stage -->
                                <div
                                    class="p-6 sm:p-8 lg:w-3/5 flex flex-col justify-between space-y-6"
                                >
                                    <div class="space-y-4">
                                        <!-- Barre supérieure : Enseignant & Toggle Langue FR/EN -->
                                        <div
                                            class="flex items-center justify-between gap-4"
                                        >
                                            <div
                                                class="flex items-center gap-2 text-xs text-gray-500 font-medium"
                                            >
                                                <User
                                                    class="w-4 h-4 text-gray-400"
                                                />
                                                <span v-if="stage.instructor">
                                                    Animé par
                                                    <strong
                                                        class="text-gray-900 font-semibold"
                                                        >{{
                                                            stage.instructor
                                                                .name
                                                        }}</strong
                                                    >
                                                </span>
                                                <span v-else
                                                    >Intervenant de
                                                    l'atelier</span
                                                >
                                            </div>

                                            <!-- Toggle Bilingue conditionnel (FR / EN) -->
                                            <div
                                                v-if="stage.has_english"
                                                class="inline-flex items-center rounded-lg bg-gray-100 p-0.5 border border-gray-200"
                                            >
                                                <button
                                                    type="button"
                                                    title="Afficher en français"
                                                    :aria-pressed="
                                                        getLang(stage.id) ===
                                                        'fr'
                                                    "
                                                    :class="[
                                                        'h-8 px-2.5 rounded-md transition-all flex items-center gap-1.5 text-xs font-semibold cursor-pointer',
                                                        getLang(stage.id) ===
                                                        'fr'
                                                            ? 'bg-white text-gray-900 shadow-2xs'
                                                            : 'text-gray-500 hover:text-gray-900 hover:bg-white/60',
                                                    ]"
                                                    @click="
                                                        setLang(stage.id, 'fr')
                                                    "
                                                >
                                                    <span
                                                        class="text-base leading-none"
                                                        aria-hidden="true"
                                                    >
                                                        🇫🇷
                                                    </span>

                                                    <span>FR</span>
                                                </button>

                                                <button
                                                    type="button"
                                                    title="Display in English"
                                                    :aria-pressed="
                                                        getLang(stage.id) ===
                                                        'en'
                                                    "
                                                    :class="[
                                                        'h-8 px-2.5 rounded-md transition-all flex items-center gap-1.5 text-xs font-semibold cursor-pointer',
                                                        getLang(stage.id) ===
                                                        'en'
                                                            ? 'bg-white text-gray-900 shadow-2xs'
                                                            : 'text-gray-500 hover:text-gray-900 hover:bg-white/60',
                                                    ]"
                                                    @click="
                                                        setLang(stage.id, 'en')
                                                    "
                                                >
                                                    <span
                                                        class="text-base leading-none"
                                                        aria-hidden="true"
                                                    >
                                                        🇬🇧
                                                    </span>

                                                    <span>EN</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Titre & Sous-titre -->
                                        <div>
                                            <h3
                                                class="text-2xl font-bold text-gray-900 leading-snug"
                                            >
                                                {{ getStageTitle(stage) }}
                                            </h3>
                                            <p
                                                v-if="getStageSubtitle(stage)"
                                                class="text-sm font-medium text-gray-500 mt-1 italic"
                                            >
                                                {{ getStageSubtitle(stage) }}
                                            </p>
                                        </div>

                                        <!-- Description -->
                                        <p
                                            v-if="getStageDescription(stage)"
                                            class="text-sm leading-relaxed text-gray-600 whitespace-pre-line text-justify"
                                        >
                                            {{ getStageDescription(stage) }}
                                        </p>

                                        <!-- Infos pratiques & Prérequis (Conditionnel) -->
                                        <div
                                            v-if="getStagePracticalInfo(stage)"
                                            class="p-3.5 bg-stone-50 border border-stone-200/80 rounded-xl text-xs text-stone-800 space-y-1.5"
                                        >
                                            <div
                                                class="flex items-center gap-1.5 font-semibold text-stone-900"
                                            >
                                                <Info
                                                    class="w-3.5 h-3.5 text-stone-600"
                                                />
                                                <span
                                                    >Informations pratiques &
                                                    Prérequis</span
                                                >
                                            </div>
                                            <p
                                                class="leading-relaxed whitespace-pre-line text-stone-600"
                                            >
                                                {{
                                                    getStagePracticalInfo(stage)
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Bloc Bas : Dates, Tarif & Call To Action -->
                                    <div
                                        class="pt-5 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                                    >
                                        <!-- Dates & Heures -->
                                        <div
                                            class="space-y-1 text-xs text-gray-600"
                                        >
                                            <div
                                                class="flex items-center gap-2 text-gray-900 font-semibold"
                                            >
                                                <Calendar
                                                    class="w-4 h-4 text-earth shrink-0"
                                                />
                                                <span>{{
                                                    formatStageDates(stage)
                                                }}</span>
                                            </div>
                                            <div
                                                v-if="
                                                    stage.default_start_time &&
                                                    stage.default_end_time
                                                "
                                                class="flex items-center gap-2 text-gray-500 pl-6"
                                            >
                                                <span
                                                    >{{
                                                        stage.default_start_time.slice(
                                                            0,
                                                            5,
                                                        )
                                                    }}
                                                    -
                                                    {{
                                                        stage.default_end_time.slice(
                                                            0,
                                                            5,
                                                        )
                                                    }}</span
                                                >
                                            </div>
                                        </div>

                                        <!-- Tarif & Bouton Réserver -->
                                        <div
                                            class="flex items-center justify-between sm:justify-end gap-5"
                                        >
                                            <div class="text-right">
                                                <span
                                                    class="block text-[11px] text-gray-400 uppercase font-medium"
                                                    >Tarif</span
                                                >
                                                <span
                                                    class="text-xl font-bold text-gray-900"
                                                >
                                                    {{
                                                        formatPrice(
                                                            stage.default_price,
                                                        )
                                                    }}
                                                </span>
                                            </div>

                                            <Button
                                                :disabled="
                                                    getRemainingSpots(stage) ===
                                                    0
                                                "
                                                class="group bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium h-10 px-5 shadow hover:scale-[1.01] transition duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                                                @click="
                                                    handleStageBooking(stage)
                                                "
                                            >
                                                <span
                                                    v-if="
                                                        getRemainingSpots(
                                                            stage,
                                                        ) > 0
                                                    "
                                                >
                                                    {{
                                                        currentUser
                                                            ? "Réserver ce stage"
                                                            : "Se connecter pour réserver"
                                                    }}
                                                </span>
                                                <span v-else>Complet</span>
                                                <ArrowRight
                                                    v-if="
                                                        getRemainingSpots(
                                                            stage,
                                                        ) > 0
                                                    "
                                                    class="w-4 h-4 ml-2 group-hover:translate-x-0.5 transition-transform"
                                                />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modale de Réservation réutilisée du Calendrier -->
    <BookingConfirmationModal
        v-model:open="isBookingModalOpen"
        :lesson="selectedLesson"
        mode="regular"
        :attendees="attendees"
        :active-absences="activeAbsences"
        @success="isBookingModalOpen = false"
    />

    <Footer />
</template>
