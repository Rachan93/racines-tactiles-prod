<script setup>
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Button } from "@/Components/ui/button";
import {
    Calendar,
    Clock,
    User,
    Users,
    AlertCircle,
    CheckCircle2,
    RotateCcw,
    Ban,
    Sparkles,
    ChevronDown,
    ChevronUp,
    Shell,
    Hand,
} from "lucide-vue-next";
import { formatDate, pluralize } from "@/Utils/formatters";

const props = defineProps({
    enrollments: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

// État d'affichage (2 semaines vs tout voir)
const showAllLessons = ref(false);

// Modales d'action
const declaringEnrollment = ref(null);
const cancellingAbsence = ref(null);
const isProcessing = ref(false);
const isAbsenceConfirmed = ref(false);

// Gestion du changement de poste
const isSwitchingSpot = ref(null);
const switchingErrors = ref({});

// Vérifie si une inscription appartient au titulaire du compte
const isOwnerEnrollment = (enrollment) => {
    return (
        enrollment?.participant?.type === "user" ||
        enrollment?.participant?.id === currentUser.value?.id
    );
};

// Date limite à J+14
const twoWeeksCutoff = computed(() => {
    const d = new Date();
    d.setHours(23, 59, 59, 999);
    d.setDate(d.getDate() + 14);
    return d;
});

const isWithinTwoWeeks = (dateString) => {
    if (!dateString) return false;
    const d = new Date(dateString);
    return d <= twoWeeksCutoff.value;
};

const allEnrollments = computed(() => {
    return props.enrollments.slice(0, 100);
});

const twoWeeksEnrollments = computed(() => {
    return allEnrollments.value.filter((e) => isWithinTwoWeeks(e.lesson.date));
});

const displayedEnrollments = computed(() => {
    return showAllLessons.value
        ? allEnrollments.value
        : twoWeeksEnrollments.value;
});

const formatTime = (timeStr) => {
    if (!timeStr) return "";
    const parts = timeStr.split(":");
    return `${parts[0]}h${parts[1]}`;
};

// Action : Changement de Poste
const handleSpotChange = (enrollment, newSpotType) => {
    if (enrollment.spot_type === newSpotType) return;

    switchingErrors.value[enrollment.id] = null;
    isSwitchingSpot.value = enrollment.id;

    router.patch(
        route("member.enrollments.update-spot-type", enrollment.id),
        { spot_type: newSpotType },
        {
            preserveScroll: true,
            onError: (errors) => {
                if (errors.spot_type) {
                    switchingErrors.value[enrollment.id] = errors.spot_type;
                }
            },
            onFinish: () => {
                isSwitchingSpot.value = null;
            },
        },
    );
};

// Actions Déclaration d'absence
const openDeclareModal = (enrollment) => {
    declaringEnrollment.value = enrollment;
    isAbsenceConfirmed.value = false;
};

const confirmDeclareAbsence = () => {
    if (!declaringEnrollment.value || !isAbsenceConfirmed.value) return;

    isProcessing.value = true;
    router.post(
        route("member.absences.declare", declaringEnrollment.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
                declaringEnrollment.value = null;
                isAbsenceConfirmed.value = false;
            },
        },
    );
};

// Actions Annulation d'absence
const openCancelModal = (enrollment) => {
    if (!enrollment.absence) return;
    cancellingAbsence.value = {
        absenceId: enrollment.absence.id,
        enrollment: enrollment,
    };
};

const confirmCancelAbsence = () => {
    if (!cancellingAbsence.value) return;

    isProcessing.value = true;
    router.post(
        route("member.absences.cancel", cancellingAbsence.value.absenceId),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
                cancellingAbsence.value = null;
            },
        },
    );
};
</script>

<template>
    <div class="space-y-4">
        <!-- 1. En-tête avec toggle 2 semaines -->
        <div
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-2"
        >
            <div class="flex items-center gap-2">
                <span
                    class="text-xs font-semibold uppercase tracking-wider text-gray-700"
                >
                    {{
                        showAllLessons
                            ? "Toutes les séances"
                            : "Prochaines 2 semaines"
                    }}
                </span>
                <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700"
                >
                    {{ pluralize(displayedEnrollments.length, "séance") }}
                </span>
            </div>

            <button
                v-if="allEnrollments.length > twoWeeksEnrollments.length"
                type="button"
                class="text-xs sm:text-sm font-medium text-gray-600 hover:text-gray-900 flex items-center gap-1.5 transition"
                @click="showAllLessons = !showAllLessons"
            >
                <span>
                    {{
                        showAllLessons
                            ? "Réduire aux 2 prochaines semaines"
                            : `Afficher toutes les séances à venir (${allEnrollments.length})`
                    }}
                </span>
                <component
                    :is="showAllLessons ? ChevronUp : ChevronDown"
                    class="w-4 h-4"
                />
            </button>
        </div>

        <!-- 2. État vide absolu -->
        <div
            v-if="enrollments.length === 0"
            class="p-8 text-center bg-gray-50 border border-dashed border-gray-300 rounded-xl space-y-2"
        >
            <Calendar class="w-8 h-8 mx-auto text-gray-400" />
            <h4 class="text-base font-semibold text-gray-700">
                Aucune séance à venir
            </h4>
            <p class="text-sm text-gray-500 max-w-sm mx-auto">
                Vous n'avez pas de cours programmé prochainement. Vous pouvez
                réserver un nouveau module via le calendrier.
            </p>
        </div>

        <!-- 3. État vide temporaire -->
        <div
            v-else-if="twoWeeksEnrollments.length === 0 && !showAllLessons"
            class="p-6 text-center bg-gray-50/70 border border-gray-200 shadow rounded-xl space-y-3"
        >
            <Calendar class="w-6 h-6 mx-auto text-gray-500" />
            <p class="text-sm text-gray-600 font-medium">
                Aucune séance programmée dans les 14 prochains jours.
            </p>
            <Button
                variant="outline"
                size="sm"
                class="text-xs sm:text-sm h-9 text-gray-700 border-gray-300 hover:bg-gray-100"
                @click="showAllLessons = true"
            >
                Afficher toutes les séances à venir ({{
                    allEnrollments.length
                }})
            </Button>
        </div>

        <!-- 4. Liste des séances -->
        <div v-else class="grid gap-3.5">
            <div
                v-for="enrollment in displayedEnrollments"
                :key="enrollment.id"
                :class="[
                    'p-4 sm:p-5 rounded-xl border transition flex flex-col justify-between gap-4 shadow',
                    enrollment.status === 'absent'
                        ? 'bg-amber-50/40 border-amber-200 border-l-4 border-l-amber-400'
                        : isOwnerEnrollment(enrollment)
                          ? 'bg-white border-gray-200 border-l-4 border-l-gray-900'
                          : 'bg-white border-gray-200 border-l-4 border-l-earth',
                ]"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                >
                    <!-- Infos Séance -->
                    <div class="space-y-2.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Statut -->
                            <span
                                v-if="enrollment.status === 'registered'"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                            >
                                <CheckCircle2 class="w-3.5 h-3.5" /> Inscrit
                            </span>
                            <span
                                v-else-if="enrollment.status === 'absent'"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-300"
                            >
                                <AlertCircle class="w-3.5 h-3.5" /> Absence
                                signalée
                            </span>

                            <!-- Badge Titulaire vs Invité -->
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold',
                                    isOwnerEnrollment(enrollment)
                                        ? 'bg-gray-900 text-white'
                                        : 'bg-gray-100 text-gray-700 border border-gray-200',
                                ]"
                            >
                                <component
                                    :is="
                                        isOwnerEnrollment(enrollment)
                                            ? User
                                            : Users
                                    "
                                    class="w-3.5 h-3.5"
                                />
                                {{
                                    isOwnerEnrollment(enrollment)
                                        ? "Moi-même"
                                        : enrollment.participant.name
                                }}
                            </span>

                            <!-- Type d'inscription -->
                            <span
                                v-if="enrollment.enrollment_type === 'makeup'"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-earth-light text-earth-header border border-earth-border"
                            >
                                <Sparkles class="w-3.5 h-3.5" /> Rattrapage
                            </span>

                            <!-- Poste avec dropdown -->
                            <DropdownMenu
                                v-if="enrollment.status === 'registered'"
                            >
                                <DropdownMenuTrigger as-child>
                                    <button
                                        type="button"
                                        :disabled="
                                            isSwitchingSpot === enrollment.id
                                        "
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-200 transition cursor-pointer disabled:opacity-50"
                                        title="Cliquez pour changer de poste"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                        >
                                            <component
                                                :is="
                                                    enrollment.spot_type ===
                                                    'wheel'
                                                        ? Shell
                                                        : Hand
                                                "
                                                class="w-3.5 h-3.5"
                                            />
                                            {{
                                                enrollment.spot_type === "wheel"
                                                    ? "Tour"
                                                    : "Modelage"
                                            }}
                                        </span>
                                        <ChevronDown
                                            class="w-3.5 h-3.5 text-gray-500"
                                        />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    align="start"
                                    class="w-56 text-xs sm:text-sm bg-white border-gray-200"
                                >
                                    <DropdownMenuItem
                                        v-if="enrollment.spot_type === 'wheel'"
                                        :disabled="
                                            enrollment.lesson.spots_available
                                                ?.handbuilding <= 0 ||
                                            isSwitchingSpot === enrollment.id
                                        "
                                        class="cursor-pointer flex items-center justify-between hover:bg-gray-50 py-2"
                                        @click="
                                            handleSpotChange(
                                                enrollment,
                                                'handbuilding',
                                            )
                                        "
                                    >
                                        <span
                                            class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap"
                                        >
                                            <Hand class="w-3.5 h-3.5" />
                                            Passer en modelage
                                        </span>
                                        <span
                                            v-if="
                                                enrollment.lesson
                                                    .spots_available
                                                    ?.handbuilding <= 0
                                            "
                                            class="text-xs text-gray-400 font-semibold"
                                            >(Complet)</span
                                        >
                                        <span
                                            v-else
                                            class="shrink-0 whitespace-nowrap text-xs text-gray-500 font-medium"
                                            >({{
                                                enrollment.lesson
                                                    .spots_available
                                                    ?.handbuilding
                                            }}
                                            dispo)</span
                                        >
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        v-else
                                        :disabled="
                                            enrollment.lesson.spots_available
                                                ?.wheel <= 0 ||
                                            isSwitchingSpot === enrollment.id
                                        "
                                        class="cursor-pointer flex items-center justify-between hover:bg-gray-50 py-2"
                                        @click="
                                            handleSpotChange(
                                                enrollment,
                                                'wheel',
                                            )
                                        "
                                    >
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                        >
                                            <Shell class="w-3.5 h-3.5" />
                                            Passer sur un tour
                                        </span>
                                        <span
                                            v-if="
                                                enrollment.lesson
                                                    .spots_available?.wheel <= 0
                                            "
                                            class="text-xs text-gray-400 font-semibold"
                                            >(Complet)</span
                                        >
                                        <span
                                            v-else
                                            class="shrink-0 whitespace-nowrap text-xs text-gray-500 font-medium"
                                            >({{
                                                enrollment.lesson
                                                    .spots_available?.wheel
                                            }}
                                            dispo)</span
                                        >
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200"
                            >
                                <span class="inline-flex items-center gap-1.5">
                                    <component
                                        :is="
                                            enrollment.spot_type === 'wheel'
                                                ? Shell
                                                : Hand
                                        "
                                        class="w-3.5 h-3.5"
                                    />
                                    {{
                                        enrollment.spot_type === "wheel"
                                            ? "Tour"
                                            : "Modelage"
                                    }}
                                </span>
                            </span>
                        </div>

                        <!-- Titre du cours & Horaires -->
                        <div>
                            <h4 class="text-base font-semibold text-gray-900">
                                {{ enrollment.lesson.course_name }}
                            </h4>
                            <div
                                class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 mt-1"
                            >
                                <span
                                    class="flex items-center gap-1.5 font-medium text-gray-700"
                                >
                                    <Calendar class="w-4 h-4 text-earth" />
                                    {{ formatDate(enrollment.lesson.date) }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <Clock class="w-4 h-4 text-gray-400" />
                                    {{
                                        formatTime(enrollment.lesson.start_time)
                                    }}
                                    -
                                    {{ formatTime(enrollment.lesson.end_time) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center sm:self-center shrink-0">
                        <!-- Déclarer une absence -->
                        <Button
                            v-if="enrollment.status === 'registered'"
                            variant="outline"
                            size="sm"
                            class="group/btn text-xs sm:text-sm text-gray-600 hover:text-red-700 hover:bg-red-50 hover:border-red-200 border-gray-200 h-9 font-medium"
                            @click="openDeclareModal(enrollment)"
                        >
                            <Ban
                                class="w-4 h-4 mr-1.5 text-gray-400 group-hover/btn:text-red-600 transition-colors"
                            />
                            Déclarer une absence
                        </Button>

                        <!-- Reprendre la place -->
                        <Button
                            v-else-if="
                                enrollment.status === 'absent' &&
                                enrollment.absence
                            "
                            variant="outline"
                            size="sm"
                            class="group/btn text-xs sm:text-sm text-amber-800 bg-amber-50 border-amber-300 hover:bg-amber-100 hover:text-amber-900 hover:border-amber-400 h-9 font-medium transition-all duration-150 shadow-2xs"
                            @click="openCancelModal(enrollment)"
                        >
                            <RotateCcw
                                class="w-4 h-4 mr-1.5 text-amber-700 group-hover/btn:text-amber-800 transition-colors"
                            />
                            {{
                                isOwnerEnrollment(enrollment)
                                    ? "Reprendre ma place"
                                    : "Reprendre la place"
                            }}
                        </Button>
                    </div>
                </div>

                <!-- Message d'Erreur Local sur la carte si échec du changement de poste -->
                <div
                    v-if="switchingErrors[enrollment.id]"
                    class="p-2.5 bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm rounded-lg flex items-center gap-2 font-medium"
                >
                    <AlertCircle class="w-4 h-4 shrink-0" />
                    <span>{{ switchingErrors[enrollment.id] }}</span>
                </div>
            </div>
        </div>

        <!-- MODALE 1 : DECLARATION D'ABSENCE -->
        <Dialog
            :open="!!declaringEnrollment"
            @update:open="(val) => !val && (declaringEnrollment = null)"
        >
            <DialogContent
                class="sm:max-w-md bg-white border-gray-200 font-brand"
            >
                <DialogHeader v-if="declaringEnrollment">
                    <DialogTitle
                        class="text-lg font-semibold text-gray-900 flex items-center gap-2"
                    >
                        <AlertCircle class="w-5 h-5 text-amber-600 shrink-0" />
                        Déclarer une absence
                    </DialogTitle>
                    <DialogDescription class="text-sm text-gray-500">
                        {{ declaringEnrollment.lesson.course_name }} —
                        {{ formatDate(declaringEnrollment.lesson.date) }}
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="declaringEnrollment"
                    class="space-y-3.5 py-2 text-sm text-gray-600"
                >
                    <p v-if="isOwnerEnrollment(declaringEnrollment)">
                        Vous êtes sur le point de signaler
                        <strong class="text-gray-900">votre absence</strong>
                        pour cette séance.
                    </p>
                    <p v-else>
                        Vous êtes sur le point de signaler l'absence de
                        <strong class="text-gray-900">{{
                            declaringEnrollment.participant.name
                        }}</strong>
                        pour cette séance.
                    </p>

                    <div
                        v-if="declaringEnrollment.enrollment_type === 'regular'"
                        class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl text-blue-900 space-y-1 text-xs sm:text-sm"
                    >
                        <p class="font-semibold">Crédit de rattrapage :</p>
                        <p>
                            En prévenant à l'avance, la place est libérée et un
                            crédit de rattrapage sera disponible sur votre
                            compte.
                        </p>
                    </div>

                    <div
                        v-else
                        class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 space-y-1 text-xs sm:text-sm"
                    >
                        <p class="font-semibold">
                            ⚠️ Attention (Séance de rattrapage) :
                        </p>
                        <p>
                            Cette séance est déjà un rattrapage. Une absence sur
                            ce créneau ne permettra pas de générer un nouveau
                            crédit de rattrapage.
                        </p>
                    </div>

                    <!-- CASE A COCHER DE SECURITE -->
                    <div class="pt-3 border-t border-gray-100">
                        <label
                            class="flex items-start gap-3 text-sm text-gray-800 cursor-pointer select-none font-medium"
                        >
                            <input
                                type="checkbox"
                                v-model="isAbsenceConfirmed"
                                class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer w-4 h-4"
                            />
                            <span>
                                {{
                                    isOwnerEnrollment(declaringEnrollment)
                                        ? "Je confirme que je serai absent(e) à cette séance."
                                        : `Je confirme que ${declaringEnrollment.participant.name} sera absent(e) à cette séance.`
                                }}
                            </span>
                        </label>
                    </div>
                </div>

                <DialogFooter class="flex flex-col sm:flex-row gap-2 pt-3">
                    <Button
                        variant="destructive"
                        size="sm"
                        class="text-sm h-9"
                        :disabled="isProcessing || !isAbsenceConfirmed"
                        @click="confirmDeclareAbsence"
                    >
                        {{
                            isProcessing
                                ? "Enregistrement..."
                                : "Confirmer l'absence"
                        }}
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-sm h-9 text-gray-600 hover:text-gray-900"
                        :disabled="isProcessing"
                        @click="declaringEnrollment = null"
                    >
                        Retour
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- MODALE 2 : REPRISE DE PLACE -->
        <Dialog
            :open="!!cancellingAbsence"
            @update:open="(val) => !val && (cancellingAbsence = null)"
        >
            <DialogContent
                class="sm:max-w-md bg-white border-gray-200 font-brand"
            >
                <DialogHeader v-if="cancellingAbsence">
                    <DialogTitle
                        class="text-lg font-semibold text-gray-900 flex items-center gap-2"
                    >
                        <RotateCcw class="w-5 h-5 text-earth-header shrink-0" />
                        {{
                            isOwnerEnrollment(cancellingAbsence.enrollment)
                                ? "Reprendre ma place"
                                : "Reprendre la place"
                        }}
                    </DialogTitle>
                    <DialogDescription class="text-sm text-gray-500">
                        {{ cancellingAbsence.enrollment.lesson.course_name }} —
                        {{
                            formatDate(cancellingAbsence.enrollment.lesson.date)
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="cancellingAbsence"
                    class="space-y-3 py-2 text-sm text-gray-600"
                >
                    <p v-if="isOwnerEnrollment(cancellingAbsence.enrollment)">
                        Vous souhaitez annuler votre déclaration d'absence et
                        confirmer votre présence au cours.
                    </p>
                    <p v-else>
                        Vous souhaitez annuler la déclaration d'absence de
                        <strong class="text-gray-900">{{
                            cancellingAbsence.enrollment.participant.name
                        }}</strong>
                        et confirmer sa venue au cours.
                    </p>

                    <div
                        class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-700 text-xs sm:text-sm"
                    >
                        <p>
                            La réinscription sera confirmée immédiatement sous
                            réserve qu'aucun autre élève n'ait réservé cette
                            place en rattrapage entre-temps.
                        </p>
                    </div>
                </div>

                <DialogFooter class="flex flex-col sm:flex-row gap-2 pt-3">
                    <Button
                        size="sm"
                        class="text-sm h-9 bg-gray-900 hover:bg-gray-700 text-white"
                        :disabled="isProcessing"
                        @click="confirmCancelAbsence"
                    >
                        {{
                            isProcessing
                                ? "Vérification..."
                                : "Confirmer la reprise de place"
                        }}
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-sm h-9 text-gray-600 hover:text-gray-900"
                        :disabled="isProcessing"
                        @click="cancellingAbsence = null"
                    >
                        Annuler
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
