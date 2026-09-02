<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head, usePage, Link } from "@inertiajs/vue3";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import interactionPlugin from "@fullcalendar/interaction";
import frLocale from "@fullcalendar/core/locales/fr";

import { useCalendarFilters } from "@/Composables/useCalendarFilters";
import LessonDetailModal from "@/Components/calendar/LessonDetailModal.vue";
import BookingConfirmationModal from "@/Components/calendar/BookingConfirmationModal.vue";
import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";
import { Button } from "@/Components/ui/button";
import { Switch } from "@/Components/ui/switch";
import { Label } from "@/Components/ui/label";
import { Badge } from "@/Components/ui/badge";
import {
    HelpCircle,
    Calendar,
    Clock,
    Shell,
    Hand,
    Sparkles,
    CheckCircle2,
    AlertCircle,
    ArrowRight,
    Search,
} from "lucide-vue-next";
import { pluralize, formatPrice } from "@/Utils/formatters";

const props = defineProps({
    events: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    attendees: { type: Array, default: () => [] },
    activeAbsences: { type: Array, default: () => [] },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

// Vérifie si le compte possède des crédits d'absence exploitables
const hasAbsenceCredits = computed(
    () => props.activeAbsences && props.activeAbsences.length > 0,
);

// 1. Initialisation avec type_id = 1 garanti
const initialFilters = {
    type_id: Number(props.filters?.type_id) || 1,
    spot_type: props.filters?.spot_type || "",
    hide_full:
        props.filters?.hide_full === "1" ||
        props.filters?.hide_full === true ||
        props.filters?.hide_full === 1,
    only_makeups:
        props.filters?.only_makeups === "1" ||
        props.filters?.only_makeups === true ||
        props.filters?.only_makeups === 1,
    ...props.filters,
};

const { filters, setFilter, setDates } = useCalendarFilters(initialFilters);

const parseBool = (val) =>
    val === true || val === 1 || val === "1" || val === "true";

// Bindings réactifs des Switchs
const hideFullChecked = computed({
    get: () => parseBool(filters.hide_full),
    set: (val) => setFilter("hide_full", val ? 1 : 0),
});

const onlyMakeupsChecked = computed({
    get: () => parseBool(filters.only_makeups),
    set: (val) => setFilter("only_makeups", val ? 1 : 0),
});

const activeTypeId = computed(() => {
    const tid = Number(filters.type_id);
    return [1, 2, 3].includes(tid) ? tid : 1;
});

const handleTypeChange = (typeId) => {
    if (activeTypeId.value === typeId) return;
    setFilter("type_id", typeId);
};

const selectedLesson = ref(null);
const isDetailModalOpen = ref(false);
const isBookingModalOpen = ref(false);
const bookingMode = ref("regular");

const calendarRef = ref(null);
const isFindingNextLesson = ref(false);
const nextLessonMessage = ref("");

// Détection Mobile pour verrouiller sur la vue Liste
const isMobile = ref(false);
const checkIsMobile = () => {
    if (typeof window === "undefined") return;

    const wasMobile = isMobile.value;
    isMobile.value = window.innerWidth < 768;

    const calendarApi = calendarRef.value?.getApi();

    if (!calendarApi) return;

    // Mobile = uniquement la vue liste
    if (isMobile.value && calendarApi.view.type !== "listWeek") {
        calendarApi.changeView("listWeek");
    }

    // Si on repasse desktop depuis mobile
    if (wasMobile && !isMobile.value && calendarApi.view.type === "listWeek") {
        calendarApi.changeView("timeGridWeek");
    }
};

onMounted(() => {
    checkIsMobile();
    window.addEventListener("resize", checkIsMobile);
});

onUnmounted(() => {
    if (typeof window !== "undefined") {
        window.removeEventListener("resize", checkIsMobile);
    }
});

const todayIso = new Date().toISOString().split("T")[0];

const calendarEvents = computed(() => {
    return props.events.map((ev) => ({
        ...ev,
        extendedProps: { ...ev },
    }));
});

// Options FullCalendar adaptées Mobile vs Desktop
const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
    initialView: isMobile.value ? "listWeek" : "timeGridWeek",
    validRange: {
        start: todayIso,
    },
    headerToolbar: {
        left: isMobile.value ? "prev,next today" : "prev,next today nextLesson",

        center: "title",

        right: isMobile.value ? "" : "timeGridWeek,dayGridMonth,listWeek",
    },
    customButtons: {
        nextLesson: {
            text: "Prochaine séance dispo",
            click: findNextLesson,
        },
    },
    buttonText: {
        today: "Aujourd'hui",
        month: "Mois",
        week: "Semaine",
        list: "Liste",
    },
    events: calendarEvents.value,
    locale: frLocale,
    firstDay: 1,
    slotMinTime: "08:00:00",
    slotMaxTime: "22:00:00",
    allDaySlot: false,
    height: "auto",
    expandRows: true,
    eventClick: handleEventClick,
    datesSet: handleDatesSet,
    eventTimeFormat: {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    },
}));

function handleEventClick(info) {
    selectedLesson.value = info.event.extendedProps;
    isDetailModalOpen.value = true;
}

function handleDatesSet(dateInfo) {
    const start = dateInfo.startStr.split("T")[0];
    const end = dateInfo.endStr.split("T")[0];
    setDates(start, end);
}

async function findNextLesson() {
    if (isFindingNextLesson.value) return;

    isFindingNextLesson.value = true;
    nextLessonMessage.value = "";

    try {
        const url = new URL(
            route("calendrier.next-lesson"),
            window.location.origin,
        );

        if (activeTypeId.value)
            url.searchParams.set("type_id", activeTypeId.value);
        if (filters.course_id)
            url.searchParams.set("course_id", filters.course_id);
        if (filters.spot_type)
            url.searchParams.set("spot_type", filters.spot_type);
        if (parseBool(filters.hide_full))
            url.searchParams.set("hide_full", "1");
        if (parseBool(filters.only_makeups))
            url.searchParams.set("only_makeups", "1");
        if (filters.end_date)
            url.searchParams.set("from_date", filters.end_date);

        const response = await fetch(url, {
            headers: { Accept: "application/json" },
        });

        if (!response.ok) throw new Error("Erreur");

        const { date } = await response.json();

        if (!date) {
            nextLessonMessage.value =
                "Aucune séance future trouvée correspondant à vos critères.";
            return;
        }

        calendarRef.value?.getApi().gotoDate(date);
    } catch (error) {
        nextLessonMessage.value =
            "Impossible de localiser la prochaine séance pour le moment.";
    } finally {
        isFindingNextLesson.value = false;
    }
}

function requireAuthOrProceed(callback) {
    if (!currentUser.value) {
        const returnTo =
            window.location.pathname +
            window.location.search +
            window.location.hash;

        window.location.href = route("auth.continue", {
            to: returnTo,
        });

        return;
    }

    callback();
}

function handleSelectRegular(lesson) {
    requireAuthOrProceed(() => {
        if (lesson) selectedLesson.value = lesson;
        bookingMode.value = "regular";
        isDetailModalOpen.value = false;
        isBookingModalOpen.value = true;
    });
}

function handleSelectMakeup(lesson) {
    requireAuthOrProceed(() => {
        if (lesson) selectedLesson.value = lesson;
        bookingMode.value = "makeup";
        isDetailModalOpen.value = false;
        isBookingModalOpen.value = true;
    });
}
</script>

<template>
    <Head title="Planning & Calendrier des Cours" />
    <Nav />

    <main class="min-h-screen bg-gray-50/50 font-brand py-6 sm:py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- ========================================================= -->
            <!-- 1. EN-TÊTE & SÉLECTION DES FORMULES                       -->
            <!-- ========================================================= -->
            <div
                class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 bg-white p-5 sm:p-7 rounded-2xl border border-gray-200 shadow-xs"
            >
                <div>
                    <h1
                        class="text-3xl sm:text-5xl font-bold text-gray-900 leading-tight"
                    >
                        Planning des cours
                    </h1>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">
                        Consultez les disponibilités en temps réel et réservez
                        vos séances ou rattrapages.
                    </p>
                </div>

                <!-- Boutons de formule -->
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        type="button"
                        :class="[
                            'text-xs sm:text-sm font-semibold h-10 px-4 rounded-xl transition-all',
                            activeTypeId === 1
                                ? 'bg-sage text-white hover:bg-sage-dark shadow-xs'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border-0',
                        ]"
                        @click="handleTypeChange(1)"
                    >
                        Cours Collectifs
                    </Button>
                    <Button
                        type="button"
                        :class="[
                            'text-xs sm:text-sm font-semibold h-10 px-4 rounded-xl transition-all',
                            activeTypeId === 2
                                ? 'bg-sage text-white hover:bg-sage-dark shadow-xs'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border-0',
                        ]"
                        @click="handleTypeChange(2)"
                    >
                        Stages
                    </Button>
                    <Button
                        type="button"
                        :class="[
                            'text-xs sm:text-sm font-semibold h-10 px-4 rounded-xl transition-all',
                            activeTypeId === 3
                                ? 'bg-sage text-white hover:bg-sage-dark shadow-xs'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border-0',
                        ]"
                        @click="handleTypeChange(3)"
                    >
                        Cours Privés
                    </Button>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 2. BARRE DE FILTRES AVANCÉS & SWITCHS FONCTIONNELS        -->
            <!-- ========================================================= -->
            <div
                class="bg-white p-4 rounded-xl border border-gray-200 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4"
            >
                <!-- Filtre Poste -->
                <div
                    class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl w-full sm:w-fit overflow-x-auto"
                >
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-lg transition-all whitespace-nowrap',
                            !filters.spot_type
                                ? 'bg-white text-gray-900 shadow-xs'
                                : 'text-gray-600 hover:text-gray-900',
                        ]"
                        @click="setFilter('spot_type', '')"
                    >
                        Tous les postes
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap',
                            filters.spot_type === 'wheel'
                                ? 'bg-white text-gray-900 shadow-xs'
                                : 'text-gray-600 hover:text-gray-900',
                        ]"
                        @click="setFilter('spot_type', 'wheel')"
                    >
                        <Shell class="w-3.5 h-3.5" />
                        Tour
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-lg transition-all flex items-center gap-1.5 whitespace-nowrap',
                            filters.spot_type === 'handbuilding'
                                ? 'bg-white text-gray-900 shadow-xs'
                                : 'text-gray-600 hover:text-gray-900',
                        ]"
                        @click="setFilter('spot_type', 'handbuilding')"
                    >
                        <Hand class="w-3.5 h-3.5" />
                        Modelage
                    </button>
                </div>

                <!-- Toggles Shadcn Fiabilisés -->
                <div
                    class="flex flex-wrap items-center gap-5 text-xs font-medium text-gray-700"
                >
                    <!-- Masquer complets -->
                    <div class="flex items-center gap-2">
                        <Switch id="hide_full" v-model="hideFullChecked" />
                        <Label
                            for="hide_full"
                            class="cursor-pointer select-none text-xs"
                        >
                            Masquer les cours complets
                        </Label>
                    </div>

                    <!-- Rattrapages uniquement (Collectifs) -->
                    <div
                        v-if="activeTypeId === 1"
                        class="flex items-center gap-2 sm:border-l sm:border-gray-200 sm:pl-5"
                    >
                        <Switch
                            id="only_makeups"
                            v-model="onlyMakeupsChecked"
                        />
                        <Label
                            for="only_makeups"
                            class="cursor-pointer select-none text-xs flex items-center gap-1"
                        >
                            <Sparkles class="w-3.5 h-3.5 text-sage-dark" />
                            Rattrapages disponibles uniquement
                        </Label>
                    </div>

                    <!-- Bouton Prochaine séance dispo (Mobile) -->
                    <button
                        v-if="isMobile"
                        type="button"
                        class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1 underline ml-auto"
                        @click="findNextLesson"
                    >
                        <Search class="w-3 h-3" /> Prochaine séance
                    </button>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 3. CALENDRIER FULLCALENDAR RESPONSIVE                     -->
            <!-- ========================================================= -->
            <div
                class="bg-white p-3 sm:p-6 rounded-2xl border border-gray-200 shadow-sm calendar-container"
            >
                <FullCalendar ref="calendarRef" :options="calendarOptions">
                    <!-- Template personnalisé des événements -->
                    <template #eventContent="{ event, timeText, view }">
                        <!-- Vue Mois -->
                        <div
                            v-if="view.type === 'dayGridMonth'"
                            class="w-full flex items-center justify-between gap-1 p-0.5 text-[11px] leading-tight overflow-hidden"
                        >
                            <span class="truncate font-semibold">{{
                                event.title
                            }}</span>
                            <span
                                v-if="event.extendedProps.is_user_enrolled"
                                class="w-2 h-2 rounded-full bg-blue-600 shrink-0"
                                title="Inscrit"
                            />
                            <span
                                v-else-if="
                                    event.extendedProps
                                        .total_standard_available === 0
                                "
                                class="text-[9px] text-red-600 font-bold shrink-0"
                            >
                                Complet
                            </span>
                            <span
                                v-else
                                class="text-[9px] text-gray-500 font-medium shrink-0"
                            >
                                {{
                                    event.extendedProps.total_standard_available
                                }}
                                pl.
                            </span>
                        </div>

                        <!-- Vue Semaine / Journée / Liste -->
                        <div
                            v-else
                            class="p-1.5 w-full h-full flex flex-col justify-between overflow-hidden text-xs leading-normal"
                        >
                            <div>
                                <div
                                    class="flex items-start justify-between gap-1"
                                >
                                    <span
                                        class="font-bold text-gray-900 text-xs sm:text-[13px] leading-tight break-words"
                                    >
                                        {{ event.title }}
                                    </span>
                                    <Badge
                                        v-if="
                                            event.extendedProps.is_user_enrolled
                                        "
                                        class="bg-blue-600 text-white text-[9px] px-1 py-0 font-bold shrink-0"
                                    >
                                        Inscrit
                                    </Badge>
                                </div>
                                <div
                                    class="text-[11px] text-gray-600 mt-1 flex items-center gap-1.5 flex-wrap"
                                >
                                    <span>{{ timeText }}</span>
                                    <span>•</span>
                                    <span class="truncate">{{
                                        event.extendedProps.instructor
                                    }}</span>
                                </div>
                            </div>

                            <!-- Jauges de places -->
                            <div
                                class="flex items-center gap-1.5 mt-1.5 pt-1 border-t border-black/5 text-[11px] font-semibold flex-wrap"
                            >
                                <span
                                    v-if="
                                        event.extendedProps
                                            .total_standard_available === 0
                                    "
                                    class="text-red-700 bg-red-100 px-1.5 py-0.5 rounded text-[10px]"
                                >
                                    Complet
                                </span>
                                <template v-else>
                                    <span
                                        v-if="
                                            event.extendedProps.wheel
                                                ?.standard_available > 0
                                        "
                                        class="inline-flex items-center gap-1 text-sky-800 bg-sky-50 px-1.5 py-0.5 rounded text-[10px]"
                                    >
                                        <Shell class="w-3 h-3" />
                                        {{
                                            event.extendedProps.wheel
                                                .standard_available
                                        }}
                                    </span>
                                    <span
                                        v-if="
                                            event.extendedProps.handbuilding
                                                ?.standard_available > 0
                                        "
                                        class="inline-flex items-center gap-1 text-orange-800 bg-orange-50 px-1.5 py-0.5 rounded text-[10px]"
                                    >
                                        <Hand class="w-3 h-3" />
                                        {{
                                            event.extendedProps.handbuilding
                                                .standard_available
                                        }}
                                    </span>
                                </template>

                                <!-- N'affiche "Rattrapage dispo" que si l'utilisateur possède des crédits d'absence -->
                                <span
                                    v-if="
                                        hasAbsenceCredits &&
                                        event.extendedProps.allows_makeup &&
                                        event.extendedProps
                                            .has_makeups_available
                                    "
                                    class="inline-flex items-center gap-1 text-sage-dark bg-sage-light px-1.5 py-0.5 rounded text-[10px]"
                                >
                                    <Sparkles class="w-3 h-3" /> Rattrapage
                                    dispo
                                </span>
                            </div>
                        </div>
                    </template>
                </FullCalendar>

                <p
                    v-if="nextLessonMessage"
                    class="mt-3 text-xs text-amber-800 bg-amber-50 p-3 rounded-xl border border-amber-200 font-medium"
                    aria-live="polite"
                >
                    {{ nextLessonMessage }}
                </p>
            </div>


            <!-- ========================================================= -->
            <!-- 4. CALL TO ACTION FAQ                                     -->
            <!-- ========================================================= -->

            <div
                class="rounded-2xl bg-gray-900 text-white p-6  sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-5"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <HelpCircle class="h-4 w-4 text-gray-300" />

                        <h2 class="font-semibold text-lg">
                            Une question avant de réserver ?
                        </h2>
                    </div>

                    <p class="text-sm text-gray-300 mt-2 max-w-xl">
                        Retrouvez toutes les informations sur les modules, les
                        réservations, les absences et les cours de rattrapage
                        dans notre FAQ.
                    </p>
                </div>

                <Button
                    as-child
                    size="sm"
                    class="group bg-white text-gray-900 hover:bg-gray-100 shrink-0"
                >
                    <Link :href="route('faq.index')">
                        Consulter la FAQ

                        <ArrowRight
                            class="h-4 w-4 ml-1.5 transition-transform group-hover:translate-x-1"
                        />
                    </Link>
                </Button>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- 5. MODALES DE DÉTAIL & DE RÉSERVATION                     -->
        <!-- ========================================================= -->
        <LessonDetailModal
            v-model:open="isDetailModalOpen"
            :lesson="selectedLesson"
            :active-absences="activeAbsences"
            @select-regular="handleSelectRegular"
            @select-makeup="handleSelectMakeup"
        />

        <BookingConfirmationModal
            v-model:open="isBookingModalOpen"
            :lesson="selectedLesson"
            :mode="bookingMode"
            :attendees="attendees"
            :active-absences="activeAbsences"
            @success="isBookingModalOpen = false"
        />
    </main>

    <Footer />
</template>

<style scoped>
:deep(.fc-event) {
    cursor: pointer;
    border-radius: 8px;
    border: none;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    transition:
        transform 0.15s ease,
        box-shadow 0.15s ease;
}

:deep(.fc-event:hover) {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
}

:deep(.fc-timegrid-event) {
    white-space: normal !important;
    min-height: 48px;
}

:deep(.fc-toolbar-title) {
    font-size: 1.15rem;
    font-weight: 700;
    color: #111827;
}

@media (max-width: 640px) {
    :deep(.fc-toolbar-title) {
        font-size: 0.95rem;
    }
}

:deep(.fc-button-primary) {
    background-color: #1c1917;
    border-color: #1c1917;
    color: #ffffff;
    font-size: 0.8125rem;
    font-weight: 600;
    border-radius: 0.6rem;
    padding: 0.4rem 0.75rem;
    transition: all 0.15s ease;
}

:deep(.fc-button-primary:hover) {
    background-color: #44403c;
    border-color: #44403c;
}

:deep(.fc-button-primary:disabled) {
    background-color: #f5f5f4;
    border-color: #e7e5e4;
    color: #a8a29e;
}

:deep(.fc-button-active) {
    background-color: #78716c !important;
    border-color: #78716c !important;
}

:deep(.fc-theme-standard td),
:deep(.fc-theme-standard th) {
    border-color: #f3f4f6;
}

:deep(.fc-col-header-cell-cushion) {
    color: #4b5563;
    font-weight: 600;
    font-size: 0.8125rem;
    padding: 0.5rem 0;
}
</style>
