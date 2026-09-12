const CAPACITY_STATUSES = new Set([
    "available",
    "limited",
    "full",
    "outside-window",
]);

const isTrue = (value) =>
    [true, 1, "1", "true"].includes(value);

const isFalse = (value) =>
    [false, 0, "0", "false"].includes(value);
export function resolveCalendarCapacityStatus(event) {
    if (
        isTrue(event.is_collective) &&
        isFalse(event.is_within_booking_window)
    ) {
        return "outside-window";
    }

    if (CAPACITY_STATUSES.has(event.capacity_status)) {
        return event.capacity_status;
    }


    if (CAPACITY_STATUSES.has(event.capacity_status)) {
        return event.capacity_status;
    }

    const available = toFiniteNumber(event.total_standard_available);

    if (available !== null && available <= 0) return "full";

    const roomMax = toFiniteNumber(event.total_room_max);
    const regularTaken = toFiniteNumber(event.total_regular_taken);

    if (roomMax > 0 && regularTaken !== null) {
        const fillRate = Math.min(1, Math.max(0, regularTaken / roomMax));

        return fillRate >= 0.7 ? "limited" : "available";
    }

    // Compatibilité avec les anciennes réponses de l'API.
    return available !== null && available <= 3 ? "limited" : "available";
}

export function calendarCapacityClass(event) {
    return `lesson-capacity--${resolveCalendarCapacityStatus(event)}`;
}
