<script setup>
import { computed, onUnmounted, ref, watch } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import interactionPlugin from "@fullcalendar/interaction";
import frLocale from "@fullcalendar/core/locales/fr";
import { useCalendarFilters } from "@/Composables/useCalendarFilters";
import { useCalendarNavigation } from "@/Composables/useCalendarNavigation";
import { calendarCapacityClass } from "@/Utils/calendarCapacity";
import CalendarToolbar from "@/Components/calendar/CalendarToolbar.vue";
import CalendarEventContent from "@/Components/calendar/CalendarEventContent.vue";
import LessonDetailModal from "@/Components/calendar/LessonDetailModal.vue";
import BookingConfirmationModal from "@/Components/calendar/BookingConfirmationModal.vue";
import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";
import { Button } from "@/Components/ui/button";
import { Switch } from "@/Components/ui/switch";
import { Label } from "@/Components/ui/label";
import {
    ArrowRight,
    CalendarDays,
    Hand,
    HelpCircle,
    Shell,
} from "lucide-vue-next";

const props = defineProps({
    events: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    attendees: { type: Array, default: () => [] },
    activeAbsences: { type: Array, default: () => [] },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const hasAbsenceCredits = computed(() => props.activeAbsences.length > 0);
const { filters, setFilter, setDates } = useCalendarFilters(props.filters);

const hideFullChecked = computed({
    get: () => filters.hide_full === 1,
    set: (value) => setFilter("hide_full", value ? 1 : 0),
});
const onlyMakeupsChecked = computed({
    get: () => filters.only_makeups === 1,
    set: (value) => setFilter("only_makeups", value ? 1 : 0),
});
const activeTypeId = computed(() => Number(filters.type_id));
const lessonTypes = [
    { id: 1, label: "Cours collectifs" },
    { id: 2, label: "Stages" },
    { id: 3, label: "Cours privés" },
];
const spotTypes = [
    { id: "", label: "Tous", icon: null },
    { id: "wheel", label: "Tour", icon: Shell },
    { id: "handbuilding", label: "Modelage", icon: Hand },
];

const selectedLesson = ref(null);
const isDetailModalOpen = ref(false);
const isBookingModalOpen = ref(false);
const bookingMode = ref("regular");
const calendarRef = ref(null);
const isFindingNextLesson = ref(false);
const nextLessonMessage = ref("");

const {
    isReady,
    isMobile,
    title,
    currentView,
    canGoPrevious,
    isCurrentPeriod,
    todayIso,
    initialView,
    handleDatesSet,
    previous,
    next,
    goToday,
    changeView,
} = useCalendarNavigation(calendarRef, setDates);

const initialDate =
    filters.start_date && filters.start_date > todayIso
        ? filters.start_date
        : todayIso;

const calendarEvents = computed(() =>
    props.events.map((event) => ({
        ...event,
        classNames: [
            ...(Array.isArray(event.classNames) ? event.classNames : []),
            calendarCapacityClass(event),
        ],
        color: "var(--calendar-event-bg)",
        backgroundColor: "var(--calendar-event-bg)",
        borderColor: "var(--calendar-event-border)",
        textColor: "var(--calendar-event-text)",
        // Les modales reçoivent les données d'origine, y compris les champs métier.
        extendedProps: { ...event },
    })),
);

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
    initialView: initialView.value,
    initialDate,
    validRange: { start: todayIso },
    headerToolbar: false,
    events: calendarEvents.value,
    locale: frLocale,
    firstDay: 1,
    slotMinTime: "08:00:00",
    slotMaxTime: "22:00:00",
    slotDuration: "00:30:00",
    slotLabelInterval: "01:00:00",
    allDaySlot: false,
    height: "auto",
    expandRows: true,
    stickyHeaderDates: false,
    eventInteractive: true,
    eventClick: handleEventClick,
    datesSet: handleDatesSet,
    eventTimeFormat: { hour: "2-digit", minute: "2-digit", hour12: false },
    views: {
        timeGridWeek: {
            displayEventTime: false,
            slotEventOverlap: false,
            eventMinHeight: 24,
            eventShortHeight: 80,
            dayHeaderFormat: { weekday: "short", day: "numeric" },
        },
        dayGridMonth: {
            eventDisplay: "block",
            displayEventTime: true,
            displayEventEnd: false,
            dayMaxEvents: 3,
        },
        listWeek: {
            displayEventTime: true,
            displayEventEnd: true,
            listDayFormat: { weekday: "long", day: "numeric", month: "long" },
            listDaySideFormat: false,
        },
    },
}));

function openLesson(event) {
    selectedLesson.value = event.extendedProps;
    isDetailModalOpen.value = true;
}

function handleEventClick(info) {
    openLesson(info.event);
}

let nextLessonController = null;

function resetNextLessonSearch() {
    nextLessonController?.abort();
    nextLessonController = null;
    isFindingNextLesson.value = false;
    nextLessonMessage.value = "";
}

watch(
    () => [
        filters.type_id,
        filters.course_id,
        filters.spot_type,
        filters.hide_full,
        filters.only_makeups,
        filters.start_date,
        filters.end_date,
    ],
    resetNextLessonSearch,
    { flush: "sync" },
);

onUnmounted(() => nextLessonController?.abort());

async function findNextLesson() {
    if (isFindingNextLesson.value) return;

    const controller = new AbortController();
    nextLessonController = controller;
    isFindingNextLesson.value = true;
    nextLessonMessage.value = "";

    try {
        const url = new URL(
            route("calendrier.next-lesson"),
            window.location.origin,
        );
        url.searchParams.set("type_id", String(activeTypeId.value));
        if (filters.course_id)
            url.searchParams.set("course_id", filters.course_id);
        if (filters.spot_type)
            url.searchParams.set("spot_type", filters.spot_type);
        if (filters.hide_full === 1) url.searchParams.set("hide_full", "1");
        if (activeTypeId.value === 1 && filters.only_makeups === 1) {
            url.searchParams.set("only_makeups", "1");
        }
        if (filters.end_date)
            url.searchParams.set("from_date", filters.end_date);

        const response = await fetch(url, {
            headers: { Accept: "application/json" },
            signal: controller.signal,
        });
        if (!response.ok) throw new Error("Erreur lors de la recherche");

        const { date } = await response.json();
        if (controller.signal.aborted || nextLessonController !== controller)
            return;

        if (!date) {
            nextLessonMessage.value =
                "Aucune séance future trouvée correspondant à vos critères.";
            return;
        }
        calendarRef.value?.getApi().gotoDate(date);
    } catch (error) {
        if (!controller.signal.aborted) {
            nextLessonMessage.value =
                "Impossible de localiser la prochaine séance pour le moment.";
        }
    } finally {
        if (nextLessonController === controller) {
            nextLessonController = null;
            isFindingNextLesson.value = false;
        }
    }
}

function requireAuthOrProceed(callback) {
    if (!currentUser.value) {
        const returnTo =
            window.location.pathname +
            window.location.search +
            window.location.hash;
        window.location.href = route("auth.continue", { to: returnTo });
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

    <main class="min-h-screen bg-white py-6 font-brand sm:py-12">
        <div
            class="mx-auto max-w-6xl space-y-6 px-4 sm:space-y-9 sm:px-6 lg:px-8"
        >
            <header>
                <h1
                    class="text-3xl font-normal leading-tight text-gray-900 sm:text-6xl"
                >
                    Planning des cours
                </h1>
                <p
                    class="mt-3 max-w-2xl text-base leading-relaxed text-gray-500 sm:mt-4 sm:text-lg"
                >
                    Trouvez votre prochaine séance à l’atelier et réservez un
                    cours ou un rattrapage.
                </p>

                <div
                    class="mt-5 grid grid-cols-3 gap-2 sm:mt-6 sm:inline-grid sm:gap-3"
                    role="group"
                    aria-label="Formule de cours"
                >
                    <button
                        v-for="type in lessonTypes"
                        :key="type.id"
                        type="button"
                        :aria-pressed="activeTypeId === type.id"
                        :class="[
                            'min-h-12 min-w-0 rounded-lg border px-2 py-2 text-xs  leading-snug transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 sm:px-5 sm:text-sm',
                            activeTypeId === type.id
                                ? 'border-sage-border bg-sage text-white font-bold'
                                : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-bold',
                        ]"
                        @click="setFilter('type_id', type.id)"
                    >
                        {{ type.label }}
                    </button>
                </div>
            </header>

            <section
                aria-label="Filtres des séances"
                class="space-y-3 border-y border-gray-100 py-4 sm:space-y-5 sm:py-5"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-5"
                >
                    <span
                        id="calendar-spot-label"
                        class="sr-only text-sm font-medium text-gray-700 sm:not-sr-only"
                        >Poste</span
                    >
                    <div
                        class="grid min-w-0 grid-cols-3 gap-1 rounded-lg bg-gray-100 p-1 sm:w-auto"
                        role="group"
                        aria-labelledby="calendar-spot-label"
                    >
                        <button
                            v-for="spot in spotTypes"
                            :key="spot.id"
                            type="button"
                            :aria-pressed="filters.spot_type === spot.id"
                            :class="[
                                'flex min-h-10 min-w-0 items-center justify-center gap-1.5 rounded-md px-2 text-xs transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary sm:px-4 sm:text-sm',
                                filters.spot_type === spot.id
                                    ? 'bg-white font-medium text-gray-900 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900',
                            ]"
                            @click="setFilter('spot_type', spot.id)"
                        >
                            <component
                                :is="spot.icon"
                                v-if="spot.icon"
                                class="h-3.5 w-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            {{ spot.label }}
                        </button>
                    </div>
                </div>

                <div class="grid gap-3 sm:flex sm:flex-wrap sm:gap-x-8">
                    <div class="flex min-h-9 items-center gap-3">
                        <Switch
                            id="hide_full"
                            v-model="hideFullChecked"
                            class="shrink-0"
                        />
                        <Label
                            for="hide_full"
                            class="min-w-0 cursor-pointer text-sm font-normal leading-snug text-gray-600"
                        >
                            Masquer les cours complets
                        </Label>
                    </div>
                    <div
                        v-if="activeTypeId === 1"
                        class="flex min-h-9 items-center gap-3"
                    >
                        <Switch
                            id="only_makeups"
                            v-model="onlyMakeupsChecked"
                            class="shrink-0"
                        />
                        <Label
                            for="only_makeups"
                            class="min-w-0 cursor-pointer text-sm font-normal leading-snug text-gray-600"
                        >
                            Rattrapages disponibles uniquement
                        </Label>
                    </div>
                </div>
            </section>

            <section
                class="calendar-surface min-w-0 rounded-xl border border-gray-200 bg-white p-3 sm:p-5"
                aria-labelledby="calendar-period"
            >
                <CalendarToolbar
                    :title="title"
                    :current-view="currentView"
                    :is-mobile="isMobile"
                    :is-ready="isReady"
                    :can-go-previous="canGoPrevious"
                    :is-current-period="isCurrentPeriod"
                    :is-finding-next-lesson="isFindingNextLesson"
                    @previous="previous"
                    @next="next"
                    @today="goToday"
                    @change-view="changeView"
                    @find-next="findNextLesson"
                />

                <div class="pt-4">
                    <FullCalendar
                        v-if="isReady"
                        ref="calendarRef"
                        :options="calendarOptions"
                    >
                        <template #eventContent="{ event, timeText, view }">
                            <CalendarEventContent
                                :event="event"
                                :time-text="timeText"
                                :view-type="view.type"
                                :has-absence-credits="hasAbsenceCredits"
                                @select="openLesson"
                            />
                        </template>
                        <template #noEventsContent>
                            <div class="mx-auto max-w-sm px-4 py-8 text-center">
                                <CalendarDays
                                    class="mx-auto h-7 w-7 text-gray-400"
                                    aria-hidden="true"
                                />
                                <p
                                    class="mt-4 text-base font-medium text-gray-900"
                                >
                                    Aucune séance à afficher
                                </p>
                                <p
                                    class="mt-2 text-sm leading-relaxed text-gray-500"
                                >
                                    Changez de semaine, ajustez vos filtres ou
                                    recherchez la prochaine séance disponible.
                                </p>
                            </div>
                        </template>
                    </FullCalendar>
                    <div
                        v-else
                        class="flex min-h-64 items-center justify-center text-sm text-gray-500"
                        role="status"
                    >
                        Chargement du calendrier…
                    </div>
                </div>

                <div role="status" aria-live="polite" aria-atomic="true">
                    <p
                        v-if="nextLessonMessage"
                        class="mt-4 rounded-lg border border-earth-border bg-earth-light p-3 text-sm leading-relaxed text-gray-700"
                    >
                        {{ nextLessonMessage }}
                    </p>
                </div>
            </section>

            <aside
                aria-labelledby="faq-cta-title"
                class="!mt-24 flex flex-col justify-between gap-5 rounded-2xl bg-gray-900 p-6 text-white sm:flex-row sm:items-center sm:p-8"
            >
                <div>
                    <h2
                        id="faq-cta-title"
                        class="flex items-center gap-2 text-lg font-semibold"
                    >
                        <HelpCircle
                            class="h-4 w-4 shrink-0 text-gray-300"
                            aria-hidden="true"
                        />

                        Une question avant de réserver ?
                    </h2>

                    <p class="mt-2 max-w-xl text-sm text-gray-300">
                        Modules, réservations, absences et rattrapages :
                        retrouvez les réponses dans notre FAQ.
                    </p>
                </div>

                <Button
                    as-child
                    size="sm"
                    class="shrink-0 bg-white text-gray-900 hover:bg-gray-100"
                >
                    <Link :href="route('faq.index')" class="group">
                        Consulter la FAQ

                        <ArrowRight
                            class="ml-1.5 h-4 w-4 transition-transform duration-200 group-hover:translate-x-1"
                            aria-hidden="true"
                        />
                    </Link>
                </Button>
            </aside>
        </div>

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
.calendar-surface {
    --calendar-event-bg: theme("colors.sage.light");
    --calendar-event-border: theme("colors.sage.border");
    --calendar-event-accent: theme("colors.sage.DEFAULT");
    --calendar-event-hover-border: theme("colors.sage.dark");
    --calendar-event-text: theme("colors.gray.900");
    --fc-border-color: theme("colors.gray.100");
    --fc-today-bg-color: theme("colors.sage.light");
    --fc-neutral-bg-color: theme("colors.gray.50");
    --fc-page-bg-color: white;
    --fc-list-event-hover-bg-color: theme("colors.sage.light");
}

:deep(.fc .lesson-capacity--available) {
    --calendar-event-bg: #e8f2f1;
    --calendar-event-border: #b8d5d2;
    --calendar-event-accent: #5e8e8a;
    --calendar-event-hover-border: #477470;
}

:deep(.fc .lesson-capacity--limited) {
    --calendar-event-bg: #fff3d6;
    --calendar-event-border: #f0cf83;
    --calendar-event-accent: #c47d16;
    --calendar-event-hover-border: #9f6210;
}

:deep(.fc .lesson-capacity--full) {
    --calendar-event-bg: #fdeaea;
    --calendar-event-border: #efb5b5;
    --calendar-event-accent: #c95757;
    --calendar-event-hover-border: #a83f3f;
}

:deep(.fc .lesson-capacity--outside-window) {
    --calendar-event-bg: #f1f5f9;
    --calendar-event-border: #cbd5e1;
    --calendar-event-accent: #94a3b8;
    --calendar-event-hover-border: #64748b;
}

:deep(.fc) {
    font-family: inherit;
    font-size: 13px;
}

:deep(.fc .fc-col-header-cell-cushion) {
    padding: 12px 3px;
    color: theme("colors.gray.600");
    font-size: 12px;
    font-weight: 500;
}

:deep(.fc .fc-day-today .fc-col-header-cell-cushion) {
    color: theme("colors.gray.900");
    font-weight: 600;
}

:deep(.fc .fc-timegrid-slot) {
    height: 1.5rem;
}

:deep(.fc .fc-timegrid-slot-label-cushion) {
    padding: 0 8px;
    color: theme("colors.gray.500");
    font-size: 11px;
    font-variant-numeric: tabular-nums;
}

:deep(.fc .fc-timegrid-slot-minor) {
    border-top-style: dotted;
}

:deep(.fc .fc-timegrid-event),
:deep(.fc .fc-daygrid-block-event) {
    border: 1px solid var(--calendar-event-border);
    border-left: 3px solid var(--calendar-event-accent);
    border-radius: 6px;
    box-shadow: none;
    overflow: hidden;
    cursor: pointer;
    transition:
        border-color 150ms ease,
        box-shadow 150ms ease;
}

:deep(.fc .fc-timegrid-event:hover),
:deep(.fc .fc-daygrid-block-event:hover) {
    border-color: var(--calendar-event-hover-border);
    box-shadow: 0 0 0 1px var(--calendar-event-border);
}

:deep(.fc .fc-timegrid-event:focus-visible),
:deep(.fc .fc-daygrid-block-event:focus-visible) {
    outline: 2px solid hsl(var(--primary));
    outline-offset: 2px;
}

:deep(.fc .fc-timegrid-event .fc-event-main) {
    height: 100%;
    padding: 0;
}

:deep(.fc .fc-timegrid-event-short .lesson-event__secondary),
:deep(.fc .fc-timegrid-event-short .lesson-event__enrolled) {
    display: none;
}

:deep(.fc .fc-timegrid-event-short .lesson-event--week) {
    padding-top: 2px;
    padding-bottom: 2px;
}

:deep(.fc .fc-timegrid-col-events) {
    margin: 0 3px;
}

:deep(.fc .fc-daygrid-day-number) {
    padding: 8px;
    color: theme("colors.gray.600");
    font-size: 12px;
}

:deep(.fc .fc-daygrid-event) {
    margin: 2px 3px;
}

:deep(.fc .fc-daygrid-more-link) {
    color: theme("colors.gray.700");
    font-weight: 500;
}

/* On réorganise les cellules natives : FullCalendar conserve le calcul des heures. */
:deep(.fc .fc-list) {
    border: 0;
}

:deep(.fc .fc-list-table),
:deep(.fc .fc-list-table tbody),
:deep(.fc .fc-list-day),
:deep(.fc .fc-list-day > th) {
    display: block;
    width: 100%;
}

:deep(.fc .fc-list-day > th) {
    border: 0;
}

:deep(.fc .fc-list-day-cushion) {
    display: flex;
    padding: 20px 0 8px;
    background: white;
    color: theme("colors.gray.700");
    font-size: 14px;
    font-weight: 500;
    line-height: 1.5;
}

:deep(
    .fc .fc-list-table tbody > .fc-list-day:first-child .fc-list-day-cushion
) {
    padding-top: 0;
}

:deep(.fc .fc-list-day-text) {
    float: none;
    overflow-wrap: anywhere;
}

:deep(.fc .fc-list-event) {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    margin-top: 10px;
    overflow: hidden;
    border: 1px solid var(--calendar-event-border);
    border-left: 3px solid var(--calendar-event-accent);
    border-radius: 8px;
    background: var(--calendar-event-bg);
    cursor: pointer;
}

:deep(.fc .fc-list-event:hover),
:deep(.fc .fc-list-event:focus-within) {
    border-color: var(--calendar-event-hover-border);
}

:deep(.fc .fc-list-event > td) {
    display: block;
    width: auto;
    min-width: 0;
    border: 0;
    background: transparent;
}

:deep(.fc .fc-list-event > .fc-list-event-graphic) {
    display: none;
}

:deep(.fc .fc-list-event > .fc-list-event-time) {
    padding: 12px 14px 0;
    color: theme("colors.gray.600");
    font-size: 13px;
    font-weight: 500;
    white-space: normal;
    font-variant-numeric: tabular-nums;
}

:deep(.fc .fc-list-event > .fc-list-event-title) {
    padding: 5px 14px 14px;
}

:deep(.fc .fc-list-empty) {
    min-height: 240px;
    background: white;
}

:deep(.fc .fc-list-empty-cushion) {
    margin: 0;
}

@media (prefers-reduced-motion: reduce) {
    :deep(.fc .fc-timegrid-event),
    :deep(.fc .fc-daygrid-block-event) {
        transition: none;
    }
}
</style>
