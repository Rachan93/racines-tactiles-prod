<script setup>
import { ref, computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";
import BookingConfirmationModal from "@/Components/calendar/BookingConfirmationModal.vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Calendar, User, ArrowRight, Camera, Info } from "lucide-vue-next";
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

// Langue sélectionnée pour chaque carte.
const activeLangs = ref({});

const getLang = (stageId) => activeLangs.value[stageId] || "fr";

const setLang = (stageId, lang) => {
    activeLangs.value[stageId] = lang;
};

const getStageTitle = (stage) =>
    getLang(stage.id) === "en" && stage.name_en ? stage.name_en : stage.name;

const getStageSubtitle = (stage) =>
    getLang(stage.id) === "en" && stage.subtitle_en
        ? stage.subtitle_en
        : stage.subtitle;

const getStageDescription = (stage) =>
    getLang(stage.id) === "en" && stage.description_en
        ? stage.description_en
        : stage.description;

const getStagePracticalInfo = (stage) =>
    getLang(stage.id) === "en" && stage.practical_info_en
        ? stage.practical_info_en
        : stage.practical_info;

// Mémorise l'URL en erreur pour chaque stage.
// Une nouvelle URL pourra être chargée si la couverture est remplacée.
const failedCoverImages = ref({});

const hasCoverImage = (stage) =>
    Boolean(stage.cover_image) &&
    failedCoverImages.value[stage.id] !== stage.cover_image;

const handleCoverImageError = (stage, imageUrl) => {
    failedCoverImages.value[stage.id] = imageUrl;
};

// Réservation.
const isBookingModalOpen = ref(false);
const selectedLesson = ref(null);

const handleStageBooking = (stage) => {
    if (!stage.first_lesson || getRemainingSpots(stage) <= 0) {
        return;
    }

    if (!currentUser.value) {
        window.location.href = route("login");
        return;
    }

    selectedLesson.value = stage.first_lesson;
    isBookingModalOpen.value = true;
};

// Places restantes sur la première séance du stage.
const getRemainingSpots = (stage) => {
    if (!stage.first_lesson) return 0;

    const wheel = Number(stage.first_lesson.wheel?.standard_available ?? 0);
    const hand = Number(
        stage.first_lesson.handbuilding?.standard_available ?? 0,
    );

    return (
        (Number.isFinite(wheel) ? Math.max(0, wheel) : 0) +
        (Number.isFinite(hand) ? Math.max(0, hand) : 0)
    );
};

const formatStageDates = (stage) => {
    if (!stage.first_lesson_date) return "";

    if (stage.end_date && stage.end_date !== stage.first_lesson_date) {
        return formatDateRange(stage.first_lesson_date, stage.end_date);
    }

    return formatDate(stage.first_lesson_date);
};
</script>

<template>
    <Head title="Stages" />
    <Nav />

    <section id="stages" class="py-12 font-brand min-h-screen bg-white">
        <div class="pt-2 pb-20 px-4 sm:px-6 lg:pt-0 lg:pb-28 lg:px-8">
            <div class="relative max-w-lg mx-auto lg:max-w-6xl">
                <!-- En-tête -->
                <div class="mb-12">
                    <h1
                        class="text-5xl leading-tight font-normal text-gray-900 sm:text-6xl mb-6"
                    >
                        Stages
                    </h1>
                    <p class="text-xl leading-7 text-gray-500 max-w-3xl">
                        Des formats immersifs pour explorer des techniques
                        singulières, perfectionner votre pratique du tournage ou
                        vous initier auprès de céramistes invités.
                    </p>
                </div>

                <!-- Aucun stage -->
                <div
                    v-if="categories.length === 0"
                    class="p-12 text-center bg-white rounded-2xl border border-gray-200 shadow-xs"
                >
                    <Camera
                        aria-hidden="true"
                        class="w-10 h-10 mx-auto text-gray-400 mb-3 stroke-1.5"
                    />
                    <h2 class="text-lg font-normal text-gray-900">
                        Aucun stage programmé pour le moment
                    </h2>
                    <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                        Les prochaines sessions et masterclasses seront
                        annoncées prochainement. Consultez régulièrement notre
                        calendrier.
                    </p>
                </div>

                <!-- Catégories -->
                <div v-else class="space-y-20">
                    <div
                        v-for="category in categories"
                        :key="category.key"
                        class="space-y-8"
                    >
                        <div class="border-b-2 border-gray-100 pb-8">
                            <h2
                                class="text-3xl leading-9 font-normal text-gray-900 sm:text-4xl sm:leading-10"
                            >
                                {{ category.title }}
                            </h2>
                            <p
                                v-if="category.description"
                                class="mt-5 text-xl leading-7 text-gray-500"
                            >
                                {{ category.description }}
                            </p>
                        </div>

                        <!-- Cartes -->
                        <div class="grid grid-cols-1 gap-10">
                            <article
                                v-for="stage in category.stages"
                                :key="stage.id"
                                class="bg-white rounded-2xl border border-gray-200/90 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden flex flex-col lg:flex-row"
                            >
                                <!--
                                    Mobile : ratio fixe pour éviter les sauts
                                    de mise en page au chargement.

                                    Ordinateur : la couverture occupe toute
                                    la hauteur de la carte sans la déformer.
                                -->
                                <div
                                    class="relative isolate w-full aspect-[4/3] bg-stone-100 lg:w-2/5 lg:shrink-0 lg:aspect-auto lg:min-h-[340px]"
                                >
                                    <img
                                        v-if="hasCoverImage(stage)"
                                        :key="stage.cover_image"
                                        :src="stage.cover_image"
                                        :alt="getStageTitle(stage)"
                                        loading="lazy"
                                        decoding="async"
                                        class="absolute inset-0 block h-full w-full object-cover object-center"
                                        @error="
                                            handleCoverImageError(
                                                stage,
                                                $event.target.getAttribute(
                                                    'src',
                                                ),
                                            )
                                        "
                                    />

                                    <!-- Image absente ou inaccessible -->
                                    <div
                                        v-else
                                        class="absolute inset-0 flex flex-col items-center justify-center bg-stone-100 text-stone-400 p-6 text-center"
                                    >
                                        <div
                                            class="w-14 h-14 rounded-full bg-white/80 border border-stone-200 flex items-center justify-center mb-3"
                                        >
                                            <Camera
                                                aria-hidden="true"
                                                class="w-6 h-6 stroke-1.5"
                                            />
                                        </div>

                                        <span
                                            class="text-sm font-medium text-stone-500"
                                        >
                                            {{ category.title }}
                                        </span>

                                        <span
                                            class="text-xs text-stone-400 mt-1"
                                        >
                                            {{
                                                stage.cover_image
                                                    ? "Photo indisponible"
                                                    : "Photo à venir"
                                            }}
                                        </span>
                                    </div>

                                    <!-- Disponibilité -->
                                    <div
                                        class="absolute top-4 left-4 right-4 z-10"
                                    >
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

                                <!-- Contenu -->
                                <div
                                    class="min-w-0 p-6 sm:p-8 lg:w-3/5 flex flex-col justify-between space-y-6"
                                >
                                    <div class="space-y-4">
                                        <!-- Enseignant et langues -->
                                        <div
                                            class="flex flex-wrap items-center justify-between gap-4"
                                        >
                                            <div
                                                class="flex items-center gap-2 text-xs text-gray-500 font-medium"
                                            >
                                                <User
                                                    aria-hidden="true"
                                                    class="w-4 h-4 text-gray-400 shrink-0"
                                                />
                                                <span v-if="stage.instructor">
                                                    Animé par
                                                    <strong
                                                        class="text-gray-900 font-semibold"
                                                    >
                                                        {{
                                                            stage.instructor
                                                                .name
                                                        }}
                                                    </strong>
                                                </span>
                                                <span v-else>
                                                    Intervenant de l'atelier
                                                </span>
                                            </div>

                                            <div
                                                v-if="stage.has_english"
                                                role="group"
                                                aria-label="Langue de la présentation du stage"
                                                class="inline-flex shrink-0 items-center rounded-lg bg-gray-100 p-0.5 border border-gray-200"
                                            >
                                                <button
                                                    type="button"
                                                    title="Afficher en français"
                                                    aria-label="Afficher en français"
                                                    :aria-pressed="
                                                        getLang(stage.id) ===
                                                        'fr'
                                                    "
                                                    :class="[
                                                        'h-8 px-2.5 rounded-md transition-colors flex items-center gap-1.5 text-xs font-semibold cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600',
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
                                                    aria-label="Display in English"
                                                    :aria-pressed="
                                                        getLang(stage.id) ===
                                                        'en'
                                                    "
                                                    :class="[
                                                        'h-8 px-2.5 rounded-md transition-colors flex items-center gap-1.5 text-xs font-semibold cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600',
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

                                        <!-- Titre -->
                                        <div>
                                            <h3
                                                class="text-2xl leading-8 font-normal text-gray-900 sm:text-3xl sm:leading-9 break-words"
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

                                        <p
                                            v-if="getStageDescription(stage)"
                                            class="text-sm leading-relaxed text-gray-600 whitespace-pre-line break-words"
                                        >
                                            {{ getStageDescription(stage) }}
                                        </p>

                                        <!-- Informations pratiques -->
                                        <div
                                            v-if="getStagePracticalInfo(stage)"
                                            class="p-3.5 bg-stone-50 border border-stone-200/80 rounded-xl text-xs text-stone-800 space-y-1.5"
                                        >
                                            <div
                                                class="flex items-center gap-1.5 font-semibold text-stone-900"
                                            >
                                                <Info
                                                    aria-hidden="true"
                                                    class="w-3.5 h-3.5 text-stone-600 shrink-0"
                                                />
                                                <span>
                                                    Informations pratiques &
                                                    Prérequis
                                                </span>
                                            </div>
                                            <p
                                                class="leading-relaxed whitespace-pre-line break-words text-stone-600"
                                            >
                                                {{
                                                    getStagePracticalInfo(stage)
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Dates, tarif et réservation -->
                                    <div
                                        class="pt-5 border-t border-gray-100 flex flex-wrap items-center justify-between gap-4"
                                    >
                                        <div
                                            class="space-y-1 text-xs text-gray-600"
                                        >
                                            <div
                                                class="flex items-center gap-2 text-gray-900 font-semibold"
                                            >
                                                <Calendar
                                                    aria-hidden="true"
                                                    class="w-4 h-4 text-earth shrink-0"
                                                />
                                                <span>
                                                    {{
                                                        formatStageDates(stage)
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                v-if="
                                                    stage.default_start_time &&
                                                    stage.default_end_time
                                                "
                                                class="flex items-center gap-2 text-gray-500 pl-6"
                                            >
                                                <span>
                                                    {{
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
                                                    }}
                                                </span>
                                            </div>
                                        </div>

                                        <div
                                            class="flex flex-wrap items-center justify-between gap-5 w-full sm:w-auto sm:ml-auto"
                                        >
                                            <div class="text-right">
                                                <span
                                                    class="block text-[11px] text-gray-400 uppercase font-medium"
                                                >
                                                    Tarif
                                                </span>
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
                                                type="button"
                                                :disabled="
                                                    getRemainingSpots(stage) <=
                                                    0
                                                "
                                                class="group inline-flex min-h-10 h-auto max-w-full px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium shadow whitespace-normal disabled:opacity-50 disabled:cursor-not-allowed"
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
                                                    aria-hidden="true"
                                                    class="w-4 h-4 ml-2 shrink-0 transition-transform group-hover:translate-x-1"
                                                />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
