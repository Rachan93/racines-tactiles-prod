<script setup>
import { Button } from "@/Components/ui/button";
import { ChevronLeft, ChevronRight, Search, LoaderCircle } from "lucide-vue-next";

defineProps({
    title: { type: String, default: "" },
    currentView: { type: String, required: true },
    isMobile: { type: Boolean, default: false },
    isReady: { type: Boolean, default: false },
    canGoPrevious: { type: Boolean, default: false },
    isCurrentPeriod: { type: Boolean, default: false },
    isFindingNextLesson: { type: Boolean, default: false },
});

defineEmits(["previous", "next", "today", "change-view", "find-next"]);

const views = [
    { id: "timeGridWeek", label: "Semaine" },
    { id: "dayGridMonth", label: "Mois" },
    { id: "listWeek", label: "Liste" },
];
</script>

<template>
    <div class="space-y-4 border-b border-gray-100 pb-5">
        <div class="flex min-w-0 flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2
                id="calendar-period"
                class="min-w-0 text-xl font-medium leading-snug text-gray-900 sm:text-2xl"
                aria-live="polite"
                aria-atomic="true"
            >
                {{ title || "Chargement du planning…" }}
            </h2>

            <div
                v-if="!isMobile"
                class="inline-flex shrink-0 self-start rounded-lg bg-gray-100 p-1"
                role="group"
                aria-label="Affichage du calendrier"
            >
                <button
                    v-for="view in views"
                    :key="view.id"
                    type="button"
                    :aria-pressed="currentView === view.id"
                    :disabled="!isReady"
                    :class="[
                        'min-h-10 rounded-md px-3 text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary',
                        currentView === view.id
                            ? 'bg-white font-medium text-gray-900 shadow-sm'
                            : 'text-gray-600 hover:text-gray-900',
                    ]"
                    @click="$emit('change-view', view.id)"
                >
                    {{ view.label }}
                </button>
            </div>
        </div>

        <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2" role="group" aria-label="Navigation dans le calendrier">
                <Button
                    type="button"
                    variant="outline"
                    class="h-11 w-11 shrink-0 rounded-lg border-gray-200 bg-white p-0 text-gray-700 hover:bg-gray-50"
                    :disabled="!isReady || !canGoPrevious"
                    aria-label="Période précédente"
                    title="Période précédente"
                    @click="$emit('previous')"
                >
                    <ChevronLeft class="h-4 w-4" aria-hidden="true" />
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    class="h-11 rounded-lg border-gray-200 bg-white px-4 text-sm text-gray-700 hover:bg-gray-50"
                    :disabled="!isReady || isCurrentPeriod"
                    @click="$emit('today')"
                >
                    Aujourd’hui
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    class="h-11 w-11 shrink-0 rounded-lg border-gray-200 bg-white p-0 text-gray-700 hover:bg-gray-50"
                    :disabled="!isReady"
                    aria-label="Période suivante"
                    title="Période suivante"
                    @click="$emit('next')"
                >
                    <ChevronRight class="h-4 w-4" aria-hidden="true" />
                </Button>
            </div>

            <Button
                type="button"
                class="min-h-11 h-auto w-full gap-2 whitespace-normal rounded-lg bg-blue-600 px-4 py-3 text-sm leading-snug text-white hover:bg-blue-500 sm:w-auto"
                :disabled="!isReady || isFindingNextLesson"
                :aria-busy="isFindingNextLesson"
                @click="$emit('find-next')"
            >
                <LoaderCircle v-if="isFindingNextLesson" class="h-4 w-4 shrink-0 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                <Search v-else class="h-4 w-4 shrink-0" aria-hidden="true" />
                {{ isFindingNextLesson ? "Recherche en cours…" : "Prochaine séance disponible" }}
            </Button>
        </div>
    </div>
</template>
