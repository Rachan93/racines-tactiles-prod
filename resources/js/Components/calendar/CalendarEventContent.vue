<script setup>
import { computed } from "vue";
import { Check, Sparkles } from "lucide-vue-next";

const props = defineProps({
    event: { type: Object, required: true },
    viewType: { type: String, required: true },
    timeText: { type: String, default: "" },
    hasAbsenceCredits: { type: Boolean, default: false },
});

defineEmits(["select"]);

const lesson = computed(() => props.event.extendedProps);
const isMonth = computed(() => props.viewType === "dayGridMonth");
const isList = computed(() => props.viewType === "listWeek");
const isTrue = (value) => [true, 1, "1", "true"].includes(value);
const isEnrolled = computed(() => isTrue(lesson.value.is_user_enrolled));
const available = computed(() => {
    const value = lesson.value.total_standard_available;
    if (value === null || value === undefined || value === "") return null;
    const count = Number(value);
    return Number.isFinite(count) ? Math.max(0, count) : null;
});
const availabilityLabel = computed(() => {
    if (available.value === null) return "Voir les disponibilités";
    if (available.value === 0) return "Complet";
    return `${available.value} place${available.value > 1 ? "s" : ""}`;
});
const hasMakeup = computed(() =>
    props.hasAbsenceCredits &&
    isTrue(lesson.value.allows_makeup) &&
    isTrue(lesson.value.has_makeups_available),
);
const spots = computed(() => [
    { label: "Tour", count: Number(lesson.value.wheel?.standard_available ?? 0) },
    { label: "Modelage", count: Number(lesson.value.handbuilding?.standard_available ?? 0) },
].filter((spot) => spot.count > 0));

const accessibleLabel = computed(() => [
    props.event.title,
    availabilityLabel.value,
    isEnrolled.value ? "Déjà inscrit" : "",
    hasMakeup.value ? "Rattrapage disponible" : "",
    "Afficher les détails",
].filter(Boolean).join(". "));
const weekTime = computed(() => props.event.formatRange({
    day: "numeric", month: "short", hour: "2-digit", minute: "2-digit", hour12: false,
}));
</script>

<template>
    <div v-if="isMonth" class="lesson-month" :title="accessibleLabel">
        <div class="lesson-month__heading">
            <span v-if="timeText" class="lesson-month__time">{{ timeText }}</span>
            <span class="lesson-month__title">{{ event.title }}</span>
            <Check v-if="isEnrolled" class="h-3 w-3 shrink-0" aria-label="Inscrit" />
        </div>
        <span class="lesson-month__status">
            {{ availabilityLabel }}<template v-if="hasMakeup"> · Rattrapage</template>
        </span>
    </div>

    <div
        v-else-if="!isList"
        class="lesson-event lesson-event--week"
        :title="[weekTime, lesson.instructor, accessibleLabel].filter(Boolean).join(' · ')"
    >
        <span class="sr-only">{{ weekTime }}. </span>
        <span class="lesson-event__title">{{ event.title }}</span>
        <div class="lesson-event__availability lesson-event__secondary">
            <span>{{ availabilityLabel }}</span>
            <span v-if="isEnrolled" class="lesson-event__enrolled">
                <Check class="h-3 w-3 shrink-0" aria-hidden="true" /> Inscrit
            </span>
        </div>
        <span v-if="hasMakeup" class="lesson-event__makeup lesson-event__secondary">
            <Sparkles class="h-3 w-3 shrink-0" aria-hidden="true" /> Rattrapage
        </span>
    </div>

    <div v-else class="lesson-event lesson-event--list">
        <div class="lesson-event__heading">
            <button
                type="button"
                class="lesson-event__title lesson-event__button"
                :aria-label="accessibleLabel"
                aria-haspopup="dialog"
                @click.stop="$emit('select', event)"
            >
                {{ event.title }}
            </button>
            <span v-if="isEnrolled" class="lesson-event__enrolled">
                <Check class="h-3 w-3 shrink-0" aria-hidden="true" /> Inscrit
            </span>
        </div>

        <p v-if="lesson.instructor" class="lesson-event__instructor lesson-event__secondary">
            {{ lesson.instructor }}
        </p>

        <div class="lesson-event__availability lesson-event__secondary">
            <template v-if="available !== 0 && spots.length">
                <span v-for="spot in spots" :key="spot.label" class="inline-flex items-baseline gap-1">
                    {{ spot.label }} <strong class="font-semibold">{{ spot.count }}</strong>
                </span>
            </template>
            <span v-else>{{ availabilityLabel }}</span>
        </div>

        <span v-if="hasMakeup" class="lesson-event__makeup lesson-event__secondary">
            <Sparkles class="h-3 w-3 shrink-0" aria-hidden="true" />
            Rattrapage disponible
        </span>
    </div>
</template>

<style scoped>
.lesson-event {
    min-width: 0;
    color: theme("colors.gray.900");
}

.lesson-event--week {
    padding: 7px;
    font-size: 12px;
    line-height: 1.35;
    overflow: hidden;
    height: 100%;
}

.lesson-event__heading {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 5px 8px;
    min-width: 0;
}

.lesson-event__title {
    display: block;
    min-width: 0;
    font-weight: 600;
    overflow-wrap: anywhere;
    text-align: left;
}

.lesson-event--week .lesson-event__title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    width: 100%;
}

.lesson-event__enrolled {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 11px;
    font-weight: 600;
}

.lesson-event__instructor {
    margin: 4px 0 0;
    color: theme("colors.gray.600");
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.lesson-event__availability {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 14px;
    margin-top: 7px;
    font-size: 11px;
    font-weight: 500;
}

.lesson-event__makeup {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 4px;
    font-size: 11px;
}

.lesson-event--list .lesson-event__heading {
    align-items: center;
    justify-content: space-between;
}

.lesson-event--list .lesson-event__title {
    flex: 1 1 12rem;
    font-size: 16px;
    line-height: 1.4;
}

.lesson-event__button {
    border-radius: 3px;
}

.lesson-event__button:focus-visible {
    outline: 2px solid hsl(var(--primary));
    outline-offset: 4px;
}

.lesson-event--list .lesson-event__instructor {
    font-size: 13px;
    white-space: normal;
    overflow-wrap: anywhere;
}

.lesson-event--list .lesson-event__availability,
.lesson-event--list .lesson-event__makeup {
    font-size: 13px;
    line-height: 1.5;
}

.lesson-month {
    padding: 3px 5px;
    overflow: hidden;
    font-size: 11px;
    line-height: 1.4;
}

.lesson-month__heading {
    display: flex;
    align-items: baseline;
    gap: 4px;
    min-width: 0;
}

.lesson-month__time {
    flex-shrink: 0;
    font-variant-numeric: tabular-nums;
}

.lesson-month__title {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 600;
}

.lesson-month__status {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: theme("colors.gray.600");
    font-size: 10px;
}
</style>
