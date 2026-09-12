import { computed, onMounted, onUnmounted, ref } from "vue";

export function useCalendarNavigation(calendarRef, setDates) {
    const isReady = ref(false);
    const isMobile = ref(false);
    const title = ref("");
    const currentView = ref("timeGridWeek");
    const canGoPrevious = ref(false);
    const isCurrentPeriod = ref(true);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Même calendrier local que FullCalendar, sans conversion UTC.
    const todayIso = [
        today.getFullYear(),
        String(today.getMonth() + 1).padStart(2, "0"),
        String(today.getDate()).padStart(2, "0"),
    ].join("-");

    const initialView = computed(() =>
        isMobile.value ? "listWeek" : "timeGridWeek",
    );

    let mediaQuery;
    let desktopView = "timeGridWeek";

    function handleBreakpointChange({ matches }) {
        const api = calendarRef.value?.getApi();

        if (matches && api) desktopView = api.view.type;
        isMobile.value = matches;

        if (api) {
            api.changeView(matches ? "listWeek" : desktopView);
        }
    }

    onMounted(() => {
        mediaQuery = window.matchMedia("(max-width: 767px)");
        isMobile.value = mediaQuery.matches;
        mediaQuery.addEventListener("change", handleBreakpointChange);
        // Évite de monter la semaine puis la liste sur mobile (et deux datesSet).
        isReady.value = true;
    });

    onUnmounted(() => {
        mediaQuery?.removeEventListener("change", handleBreakpointChange);
    });

    function handleDatesSet(info) {
        title.value = info.view.title;
        currentView.value = info.view.type;
        canGoPrevious.value = info.view.currentStart > today;
        isCurrentPeriod.value =
            today >= info.view.currentStart && today < info.view.currentEnd;

        // endStr est une borne exclusive : on conserve le contrat existant.
        setDates(info.startStr.split("T")[0], info.endStr.split("T")[0]);
    }

    function previous() {
        if (canGoPrevious.value) calendarRef.value?.getApi().prev();
    }

    function next() {
        calendarRef.value?.getApi().next();
    }

    function goToday() {
        calendarRef.value?.getApi().today();
    }

    function changeView(view) {
        if (isMobile.value && view !== "listWeek") return;
        if (!["timeGridWeek", "dayGridMonth", "listWeek"].includes(view)) return;

        if (!isMobile.value) desktopView = view;
        calendarRef.value?.getApi().changeView(view);
    }

    return {
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
    };
}
