<script setup>
import { ref, computed, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useForm } from "laravel-precognition-vue-inertia";
import { toast } from "vue-sonner";
import axios from "axios";
import ResponsiveModal from "@/Components/custom/ResponsiveModal.vue";
import ParticipantSelector from "@/Components/calendar/ParticipantSelector.vue";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Calendar, Sparkles, Loader2, Shell, Hand } from "lucide-vue-next";
import {
    pluralize,
    formatPrice,
    formatDate,
    formatDateRange,
} from "@/Utils/formatters";

const props = defineProps({
    open: { type: Boolean, default: false },
    lesson: { type: Object, default: null },
    mode: { type: String, default: "regular" },
    attendees: { type: Array, default: () => [] },
    activeAbsences: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:open", "success"]);
const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const hasEnrollmentOnCurrentLesson = (type, id) => {
    if (!props.lesson?.user_enrollments) {
        return false;
    }

    return props.lesson.user_enrollments.some(
        (enrollment) =>
            enrollment.participant_type === type &&
            Number(enrollment.participant_id) === Number(id),
    );
};

const isCollective = computed(() => props.lesson?.is_collective ?? true);
const activeAbsencesCount = computed(() => props.activeAbsences?.length || 0);

const modulePreview = ref({
    loading: false,
    startDate: null,
    endDate: null,
    totalFound: 0,
    isComplete: true,
});

let abortController = null;

const fetchModulePreview = async (lessonId, totalLessons) => {
    if (!lessonId || props.mode !== "regular" || !isCollective.value) return;

    if (abortController) abortController.abort();
    abortController = new AbortController();

    modulePreview.value.loading = true;
    try {
        const response = await axios.get(
            route("lessons.module-preview", lessonId),
            {
                params: { total_lessons: totalLessons || 10 },
                signal: abortController.signal,
            },
        );

        modulePreview.value = {
            loading: false,
            startDate: response.data.start_date,
            endDate: response.data.end_date,
            totalFound: response.data.total_found,
            isComplete: response.data.is_complete,
        };
    } catch (e) {
        if (e.name !== "CanceledError" && e.code !== "ERR_CANCELED") {
            modulePreview.value.loading = false;
        }
    }
};

const form = useForm("post", route("reservations.store"), {
    lesson_id: null,
    enrollment_type: "regular",
    total_lessons: 10,
    participants: [],
    spot_type: "wheel",
    absence_id: null,
    module_id: null,
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && props.lesson) {
            form.clearErrors();
            form.lesson_id = props.lesson.id;
            form.enrollment_type = props.mode;

            const defaultSpot = (
                props.mode === "makeup"
                    ? props.lesson.wheel?.has_makeup_available
                    : (props.lesson.wheel?.standard_available ?? 0) > 0
            )
                ? "wheel"
                : "handbuilding";

            form.spot_type = defaultSpot;

            if (props.mode === "regular") {
                form.total_lessons = isCollective.value ? 10 : 1;
                form.absence_id = null;
                form.module_id = null;

                const canPrefillCurrentUser =
                    currentUser.value &&
                    !hasEnrollmentOnCurrentLesson(
                        "App\\Models\\User",
                        currentUser.value.id,
                    );

                form.participants = canPrefillCurrentUser
                    ? [
                          {
                              participant_type: "App\\Models\\User",
                              participant_id: currentUser.value.id,
                              name: `${currentUser.value.first_name} ${currentUser.value.last_name}`,
                              label: "Moi-même",
                              spot_type: defaultSpot,
                          },
                      ]
                    : [];
                form.validate();
                if (isCollective.value) {
                    fetchModulePreview(props.lesson.id, form.total_lessons);
                }
            } else {
                form.participants = [];
                form.total_lessons = 1;

                if (props.activeAbsences.length > 0) {
                    const firstAbsence = props.activeAbsences[0];
                    form.absence_id = firstAbsence.id;
                    form.module_id =
                        firstAbsence.enrollment?.module_id ||
                        firstAbsence.module_id;
                    form.validate("absence_id");
                } else {
                    form.absence_id = null;
                    form.module_id = null;
                }
            }
        }
    },
);

const handleTotalLessonsChange = () => {
    form.clearErrors();
    form.validate("total_lessons");
    if (isCollective.value) {
        fetchModulePreview(props.lesson?.id, form.total_lessons);
    }
};

const handleParticipantsChange = () => {
    form.clearErrors();
    form.validate();
};

const handleAbsenceChange = (selectedAbsenceId) => {
    const id = Number(selectedAbsenceId);
    form.absence_id = id;

    const found = props.activeAbsences.find((a) => Number(a.id) === id);
    if (found) {
        form.module_id = found.enrollment?.module_id || found.module_id;
    }
    form.validate("absence_id");
};

// Libellé avec nom réel du participant et nom du cours
const getAbsenceOptionLabel = (absence) => {
    const participant = absence.enrollment?.module?.participant;
    const pName = participant
        ? `${participant.first_name} ${participant.last_name}`
        : currentUser.value
          ? `${currentUser.value.first_name} ${currentUser.value.last_name}`
          : "Moi-même";

    const dateStr = formatDate(absence.notification_date || absence.created_at);
    const courseName = absence.enrollment?.lesson?.course?.name;

    if (courseName) {
        return `Absence de ${pName} du ${dateStr} — ${courseName}`;
    }
    return `Absence de ${pName} du ${dateStr}`;
};

const totalPrice = computed(() => {
    if (!props.lesson) return 0;
    if (props.mode === "makeup") return 0;

    const participantsCount = form.participants?.length || 0;
    if (!isCollective.value) {
        return props.lesson.price * participantsCount;
    }

    const lessonsCount = form.total_lessons || 10;
    return props.lesson.price * lessonsCount * participantsCount;
});

const hasParticipantErrors = computed(() => {
    return Object.keys(form.errors).some((key) =>
        key.startsWith("participants"),
    );
});

const handleSubmit = () => {
    form.submit({
        preserveScroll: true,
        onSuccess: () => {
            const isMakeup = props.mode === "makeup";
            toast.success(
                isMakeup
                    ? "Rattrapage positionné !"
                    : "Réservation confirmée !",
                {
                    description: isMakeup
                        ? "Votre cours de rattrapage a été enregistré avec succès."
                        : `Votre inscription pour « ${props.lesson.title} » a bien été validée.`,
                },
            );
            emit("update:open", false);
            emit("success");
            form.reset();
        },
        onError: (errors) => {
            console.error("ERREURS RATTRAPAGE :", errors);

            const firstError = Object.values(errors)[0];

            toast.error("Erreur de réservation", {
                description:
                    firstError || "La réservation n'a pas pu être enregistrée.",
            });
        },
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
            <span v-if="mode === 'regular'">
                {{
                    isCollective
                        ? "Réservation de Module"
                        : "Réservation de Stage / Cours"
                }}
            </span>
            <span v-else class="flex items-center gap-2">
                <Sparkles class="w-5 h-5 text-sage-dark" />
                Placement d'un Rattrapage
            </span>
        </template>

        <template #description>
            <span v-if="lesson">
                {{ lesson.title }} — {{ formatDate(lesson.start) }}
            </span>
        </template>

        <!-- Formulaire -->
        <form
            @submit.prevent="handleSubmit"
            class="space-y-5 py-1 text-sm text-gray-700"
        >
            <!-- ========================================================= -->
            <!-- CAS 1 : MODE RÉGULIER (MULTI-PARTICIPANTS)               -->
            <!-- ========================================================= -->
            <template v-if="mode === 'regular'">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Label
                            class="text-xs font-semibold uppercase tracking-wider text-gray-700"
                        >
                            1. Participants & Postes
                        </Label>
                        <span class="text-xs text-gray-500 font-medium">
                            {{
                                pluralize(
                                    form.participants.length,
                                    "participant",
                                )
                            }}
                        </span>
                    </div>

                    <ParticipantSelector
                        v-model="form.participants"
                        mode="regular"
                        :attendees="attendees"
                        :current-user="currentUser"
                        :lesson="lesson"
                        :active-absences="activeAbsences"
                        :errors="form.errors"
                        @change="handleParticipantsChange"
                    />
                </div>

                <!-- Durée du module -->
                <div v-if="isCollective" class="space-y-2.5">
                    <Label
                        class="text-xs font-semibold uppercase tracking-wider text-gray-700"
                    >
                        2. Durée du module
                    </Label>
                    <div class="flex items-center gap-3">
                        <Input
                            type="number"
                            min="10"
                            step="10"
                            v-model.number="form.total_lessons"
                            class="w-28 text-sm h-10 border-gray-200 bg-white"
                            @change="handleTotalLessonsChange"
                        />
                        <span class="text-sm text-gray-600 font-medium">
                            {{
                                pluralize(
                                    form.total_lessons || 10,
                                    "séance consécutive",
                                )
                            }}
                            par personne
                        </span>
                    </div>
                    <p
                        v-if="form.invalid('total_lessons')"
                        class="text-xs text-red-600 font-medium"
                    >
                        {{ form.errors.total_lessons }}
                    </p>

                    <!-- Période exacte -->
                    <div
                        v-if="
                            modulePreview.startDate &&
                            modulePreview.endDate &&
                            !form.invalid('total_lessons')
                        "
                        class="flex items-center gap-2.5 p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-700"
                    >
                        <Calendar class="w-4 h-4 text-earth shrink-0" />
                        <span v-if="!modulePreview.loading" class="font-medium">
                            Période :
                            {{
                                formatDateRange(
                                    modulePreview.startDate,
                                    modulePreview.endDate,
                                )
                            }}
                        </span>
                        <span v-else class="text-gray-400 italic"
                            >Calcul du calendrier...</span
                        >
                    </div>
                </div>

                <!-- Stage / Privé -->
                <div
                    v-else
                    class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-700 space-y-1"
                >
                    <div
                        class="flex items-center gap-2 font-semibold text-gray-900"
                    >
                        <Calendar class="w-4 h-4 text-earth shrink-0" />
                        <span>Inscription au forfait complet</span>
                    </div>
                    <p class="text-gray-500">
                        Votre inscription couvre l'ensemble des séances
                        programmées pour ce cours.
                    </p>
                </div>
            </template>

            <!-- ========================================================= -->
            <!-- CAS 2 : MODE RATTRAPAGE (SÉLECTION D'ABSENCE NOMINATIVE)  -->
            <!-- ========================================================= -->
            <template v-else>
                <!-- 1. Choix du poste -->
                <div class="space-y-2">
                    <Label
                        class="text-xs font-semibold uppercase tracking-wider text-gray-700"
                    >
                        1. Choisissez votre poste
                    </Label>
                    <div class="grid grid-cols-2 gap-2.5">
                        <button
                            type="button"
                            :disabled="!lesson?.wheel?.has_makeup_available"
                            :class="[
                                'p-3 border rounded-xl text-left transition flex items-center justify-between',
                                form.spot_type === 'wheel'
                                    ? 'border-gray-900 bg-gray-900 text-white shadow-xs'
                                    : 'bg-white hover:bg-gray-50 border-gray-200 text-gray-800',
                                !lesson?.wheel?.has_makeup_available
                                    ? 'opacity-40 cursor-not-allowed'
                                    : 'cursor-pointer',
                            ]"
                            @click="form.spot_type = 'wheel'"
                        >
                            <span
                                class="font-semibold text-xs inline-flex items-center gap-1.5"
                            >
                                <Shell class="w-4 h-4" />
                                Tour
                            </span>
                            <span class="text-[10px] font-bold opacity-85">
                                {{
                                    lesson?.wheel?.has_makeup_available
                                        ? "Disponible"
                                        : "Complet"
                                }}
                            </span>
                        </button>

                        <button
                            type="button"
                            :disabled="
                                !lesson?.handbuilding?.has_makeup_available
                            "
                            :class="[
                                'p-3 border rounded-xl text-left transition flex items-center justify-between',
                                form.spot_type === 'handbuilding'
                                    ? 'border-gray-900 bg-gray-900 text-white shadow-xs'
                                    : 'bg-white hover:bg-gray-50 border-gray-200 text-gray-800',
                                !lesson?.handbuilding?.has_makeup_available
                                    ? 'opacity-40 cursor-not-allowed'
                                    : 'cursor-pointer',
                            ]"
                            @click="form.spot_type = 'handbuilding'"
                        >
                            <span
                                class="font-semibold text-xs inline-flex items-center gap-1.5"
                            >
                                <Hand class="w-4 h-4" />
                                Modelage
                            </span>
                            <span class="text-[10px] font-bold opacity-85">
                                {{
                                    lesson?.handbuilding?.has_makeup_available
                                        ? "Disponible"
                                        : "Complet"
                                }}
                            </span>
                        </button>
                    </div>
                    <p
                        v-if="form.invalid('spot_type')"
                        class="text-xs text-red-600 font-medium"
                    >
                        {{ form.errors.spot_type }}
                    </p>
                </div>

                <!-- 2. Sélection de l'absence créditée -->
                <div class="space-y-2">
                    <Label
                        class="text-xs font-semibold uppercase tracking-wider text-gray-700"
                    >
                        2. Sélectionnez le crédit d'absence à utiliser
                    </Label>

                    <select
                        v-if="activeAbsences.length > 0"
                        class="w-full text-xs sm:text-sm border-gray-300 rounded-xl focus:ring-gray-900 focus:border-gray-900 py-2.5 bg-white shadow-2xs"
                        :value="form.absence_id"
                        @change="handleAbsenceChange($event.target.value)"
                    >
                        <option
                            v-for="absence in activeAbsences"
                            :key="absence.id"
                            :value="absence.id"
                        >
                            {{ getAbsenceOptionLabel(absence) }}
                        </option>
                    </select>

                    <p
                        v-else
                        class="text-xs text-amber-800 bg-amber-50 border border-amber-200 p-3 rounded-xl"
                    >
                        Aucun crédit d'absence disponible sur votre compte.
                    </p>

                    <p
                        v-if="form.invalid('absence_id')"
                        class="text-xs text-red-600 font-medium"
                    >
                        {{ form.errors.absence_id }}
                    </p>
                </div>
            </template>

            <!-- ========================================================= -->
            <!-- 3. RÉCAPITULATIF FINANCIER                                -->
            <!-- ========================================================= -->
            <div
                class="bg-gray-50 border border-gray-200 p-4 rounded-xl flex justify-between items-center text-sm font-semibold"
            >
                <div class="text-gray-600">
                    <p>
                        {{
                            mode === "regular"
                                ? "Total à régler :"
                                : "Règlement par crédit d'absence :"
                        }}
                    </p>
                    <p
                        v-if="mode === 'regular'"
                        class="text-xs text-gray-400 font-normal mt-0.5"
                    >
                        {{ pluralize(form.participants.length, "participant") }}
                        <span v-if="isCollective">
                            ×
                            {{
                                pluralize(form.total_lessons || 10, "séance")
                            }}</span
                        >
                    </p>
                    <p
                        v-else
                        class="text-xs text-emerald-700 font-medium mt-0.5"
                    >
                        1 crédit utilisé sur
                        {{
                            pluralize(activeAbsencesCount, "crédit disponible")
                        }}
                    </p>
                </div>
                <span class="text-2xl text-gray-900 font-bold">
                    {{ formatPrice(totalPrice) }}
                </span>
            </div>

            <div
                v-if="form.invalid('lesson_id')"
                class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl"
            >
                {{ form.errors.lesson_id }}
            </div>
        </form>

        <!-- Actions -->
        <template #footer>
            <Button
                variant="ghost"
                size="sm"
                type="button"
                class="text-xs sm:text-sm text-gray-600 hover:text-gray-900"
                @click="emit('update:open', false)"
            >
                Annuler
            </Button>

            <Button
                type="button"
                class="text-xs sm:text-sm h-9 bg-blue-600 hover:bg-blue-500 text-white font-medium shadow-xs"
                :disabled="
                    form.processing ||
                    form.validating ||
                    (mode === 'regular' &&
                        (form.participants.length === 0 ||
                            hasParticipantErrors ||
                            (isCollective && form.invalid('total_lessons')))) ||
                    (mode === 'makeup' &&
                        (activeAbsences.length === 0 ||
                            !form.absence_id ||
                            form.invalid('absence_id')))
                "
                @click="handleSubmit"
            >
                <Loader2
                    v-if="form.processing"
                    class="w-4 h-4 mr-2 animate-spin"
                />
                <span>
                    {{
                        form.processing
                            ? "Validation..."
                            : mode === "regular"
                              ? "Confirmer et Régler"
                              : "Confirmer le rattrapage"
                    }}
                </span>
            </Button>
        </template>
    </ResponsiveModal>
</template>
