<script setup>
import { computed } from "vue";
import ResponsiveModal from "@/Components/custom/ResponsiveModal.vue";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
import {
    Calendar,
    Clock,
    User,
    Sparkles,
    Shell,
    Hand,
    AlertCircle,
    CheckCircle2,
    Info,
    HelpCircle,
} from "lucide-vue-next";
import { formatPrice, formatDate, pluralize } from "@/Utils/formatters";

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    lesson: {
        type: Object,
        default: null,
    },
    activeAbsences: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["update:open", "select-regular", "select-makeup"]);

// Vérifie si le compte possède au moins un crédit d'absence actif
const hasAbsenceCredits = computed(() => {
    return props.activeAbsences && props.activeAbsences.length > 0;
});

// Éligibilité pour poser un rattrapage
const canBookMakeup = computed(() => {
    if (!props.lesson) return false;
    return (
        props.lesson.allows_makeup &&
        props.lesson.has_makeups_available &&
        hasAbsenceCredits.value
    );
});

// Éligibilité pour réserver un module régulier
const canBookRegular = computed(() => {
    if (!props.lesson) return false;
    if (props.lesson.is_collective) {
        return (
            props.lesson.is_within_booking_window &&
            props.lesson.total_standard_available > 0
        );
    }
    return props.lesson.total_standard_available > 0;
});

// Formatage de l'heure
const formatTime = (isoString) => {
    if (!isoString) return "";
    return new Date(isoString).toLocaleTimeString("fr-BE", {
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <ResponsiveModal
        :open="open"
        maxWidth="sm:max-w-lg"
        @update:open="(val) => emit('update:open', val)"
    >
        <!-- En-tête -->
        <template #title>
            <div v-if="lesson" class="flex items-center justify-between gap-3 pr-6">
                <span class="truncate">{{ lesson.title }}</span>
                <Badge
                    variant="outline"
                    class="capitalize bg-gray-100 text-gray-700 border-gray-200 text-xs shrink-0 font-medium"
                >
                    {{ lesson.type }}
                </Badge>
            </div>
        </template>

        <template #description>
            <span v-if="lesson">
                {{ formatDate(lesson.start) }}
            </span>
        </template>

        <!-- Corps de la modale -->
        <div v-if="lesson" class="space-y-4">
            <!-- 1. Bannière d'inscription si des membres du compte sont déjà inscrits -->
            <div
                v-if="lesson.is_user_enrolled"
                class="p-3 bg-blue-50/80 border border-blue-200 rounded-xl text-xs space-y-1.5"
            >
                <div class="flex items-center gap-1.5 font-semibold text-blue-950">
                    <CheckCircle2 class="w-4 h-4 text-blue-600 shrink-0" />
                    <span>Inscriptions de votre compte sur cette séance :</span>
                </div>
                <div class="space-y-1 pl-5">
                    <div
                        v-for="enr in lesson.user_enrollments"
                        :key="enr.id"
                        class="flex items-center justify-between text-blue-900"
                    >
                        <span>• {{ enr.name }} ({{ enr.spot_type === 'wheel' ? 'Tour' : 'Modelage' }})</span>
                        <Badge
                            v-if="enr.is_absent"
                            variant="destructive"
                            class="text-[10px] py-0 px-1.5 font-medium"
                        >
                            Absent
                        </Badge>
                        <Badge
                            v-else
                            class="bg-blue-600 text-white text-[10px] py-0 px-1.5 font-medium"
                        >
                            Inscrit
                        </Badge>
                    </div>
                </div>
            </div>

            <!-- 2. Informations Générales de la séance -->
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-2">
                <div class="flex items-center gap-2 text-gray-900 font-medium">
                    <Calendar class="w-4 h-4 text-earth shrink-0" />
                    <span>{{ formatDate(lesson.start) }}</span>
                </div>
                <div class="flex items-center gap-2 text-gray-600">
                    <Clock class="w-4 h-4 text-gray-400 shrink-0" />
                    <span>{{ formatTime(lesson.start) }} - {{ formatTime(lesson.end) }}</span>
                </div>
                <div class="flex items-center gap-2 text-gray-600">
                    <User class="w-4 h-4 text-gray-400 shrink-0" />
                    <span>Professeur : <strong class="text-gray-900 font-semibold">{{ lesson.instructor }}</strong></span>
                </div>
                <div class="pt-2 border-t border-gray-200/80 flex items-center justify-between text-xs text-gray-500">
                    <span>Tarif de la séance :</span>
                    <span class="text-sm font-bold text-gray-900">{{ formatPrice(lesson.price) }}</span>
                </div>
            </div>

            <!-- 3. Jauge d'Occupation Globale (/10) avec Tooltip -->
            <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs">
                <div class="flex items-center gap-1.5 font-semibold text-gray-900">
                    <span>Occupation totale :</span>
                    <span class="text-sm font-bold text-gray-900">
                        {{ lesson.total_regular_taken ?? lesson.total_physical_taken }} / {{ lesson.total_room_max || 10 }}
                    </span>
                    <span class="text-gray-500 font-normal">places réservées</span>
                </div>

                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <button
                                type="button"
                                class="text-gray-400 hover:text-gray-700 transition-colors inline-flex items-center focus:outline-none"
                            >
                                <HelpCircle class="w-4 h-4" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent side="top" class="max-w-xs text-xs text-center p-2.5 bg-gray-900 text-white rounded-lg shadow-lg">
                            L'atelier dispose d'une capacité maximale de 10 places au total, réparties en maximum 8 tours et maximum 4 postes de modelage.
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>

            <!-- 4. Grille des Postes (Modelage / Tour) -->
            <div class="grid grid-cols-2 gap-3">
                <!-- Modelage -->
                <div class="border border-gray-200 rounded-xl p-3.5 bg-white shadow-xs space-y-2">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-1.5">
                        <span class="font-semibold text-gray-900 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <Hand class="w-3.5 h-3.5 text-gray-700" />
                            Modelage
                        </span>
                        <span class="text-xs text-gray-500 font-medium">
                            {{ lesson.handbuilding.regular_taken ?? lesson.handbuilding.physical_taken }} / {{ lesson.handbuilding.max }}
                        </span>
                    </div>

                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Dispo module :</span>
                            <span :class="lesson.handbuilding.standard_available > 0 ? 'text-emerald-700 font-bold' : 'text-gray-400 font-medium'">
                                {{ pluralize(lesson.handbuilding.standard_available, 'place') }}
                            </span>
                        </div>
                        <div v-if="lesson.allows_makeup" class="flex items-center justify-between">
                            <span class="text-gray-500">Rattrapage :</span>
                            <span
                                :class="[
                                    'text-[10px] font-bold px-1.5 py-0.5 rounded',
                                    lesson.handbuilding.has_makeup_available
                                        ? 'bg-sage-light text-sage-dark'
                                        : 'bg-gray-100 text-gray-400'
                                ]"
                            >
                                {{ lesson.handbuilding.has_makeup_available ? 'Disponible' : 'Complet' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tour -->
                <div class="border border-gray-200 rounded-xl p-3.5 bg-white shadow-xs space-y-2">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-1.5">
                        <span class="font-semibold text-gray-900 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <Shell class="w-3.5 h-3.5 text-gray-700" />
                            Tour
                        </span>
                        <span class="text-xs text-gray-500 font-medium">
                            {{ lesson.wheel.regular_taken ?? lesson.wheel.physical_taken }} / {{ lesson.wheel.max }}
                        </span>
                    </div>

                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Dispo module :</span>
                            <span :class="lesson.wheel.standard_available > 0 ? 'text-emerald-700 font-bold' : 'text-gray-400 font-medium'">
                                {{ pluralize(lesson.wheel.standard_available, 'place') }}
                            </span>
                        </div>
                        <div v-if="lesson.allows_makeup" class="flex items-center justify-between">
                            <span class="text-gray-500">Rattrapage :</span>
                            <span
                                :class="[
                                    'text-[10px] font-bold px-1.5 py-0.5 rounded',
                                    lesson.wheel.has_makeup_available
                                        ? 'bg-sage-light text-sage-dark'
                                        : 'bg-gray-100 text-gray-400'
                                ]"
                            >
                                {{ lesson.wheel.has_makeup_available ? 'Disponible' : 'Complet' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Avertissement Règle des 6 Jours (Collectifs uniquement) -->
            <div
                v-if="lesson.is_collective && !lesson.is_within_booking_window"
                class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-xs leading-relaxed flex items-start gap-2"
            >
                <AlertCircle class="w-4 h-4 text-amber-700 shrink-0 mt-0.5" />
                <span>
                    <strong>Note :</strong> Cette séance collective est en dehors de la fenêtre d'inscription de 6 jours. Vous ne pouvez pas y démarrer un nouveau module, mais un rattrapage reste possible si une place est libérée.
                </span>
            </div>

            <!-- 6. Info Rattrapage si pas de crédit sur le compte -->
            <div
                v-if="lesson.allows_makeup && lesson.has_makeups_available && !hasAbsenceCredits"
                class="p-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-600 text-xs flex items-center gap-2"
            >
                <Info class="w-4 h-4 text-gray-400 shrink-0" />
                <span>Votre compte ne dispose d'aucun crédit d'absence actif pour poser un rattrapage.</span>
            </div>
        </div>

        <!-- Actions -->
        <template #footer>
            <Button
                variant="ghost"
                size="sm"
                class="text-xs sm:text-sm text-gray-600 hover:text-gray-900"
                @click="emit('update:open', false)"
            >
                Fermer
            </Button>

            <!-- Bouton Placer un Rattrapage -->
            <Button
                v-if="canBookMakeup"
                variant="outline"
                class="text-xs sm:text-sm h-9 border-sage-border bg-sage-light text-sage-dark hover:bg-sage hover:text-white transition-all font-medium"
                @click="emit('select-makeup', lesson)"
            >
                <Sparkles class="w-4 h-4 mr-1.5" />
                Placer un rattrapage
            </Button>

            <!-- Bouton Réserver un Module / Cours -->
            <Button
                v-if="lesson"
                :disabled="!canBookRegular"
                class="text-xs sm:text-sm h-9 bg-blue-600 hover:bg-blue-500 text-white font-medium shadow-xs disabled:opacity-50 disabled:cursor-not-allowed"
                @click="emit('select-regular', lesson)"
            >
                {{ lesson.is_collective ? 'Réserver un module' : 'Réserver ce cours' }}
            </Button>
        </template>
    </ResponsiveModal>
</template>
