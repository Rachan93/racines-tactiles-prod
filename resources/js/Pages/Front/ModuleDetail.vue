<script setup>
//daaaamn le monolithe j'ai oublié de refactoriser
import { computed, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";

import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";

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
import { Separator } from "@/Components/ui/separator";

import {
    ArrowLeft,
    Calendar,
    Clock,
    Sparkles,
    CheckCircle2,
    AlertCircle,
    User,
    Users,
    Ban,
    RotateCcw,
    Check,
    ChevronDown,
    Shell,
    Hand,
} from "lucide-vue-next";

import { formatDate, pluralize } from "@/Utils/formatters";

const props = defineProps({
    module: {
        type: Object,
        required: true,
    },

    enrollments: {
        type: Array,
        default: () => [],
    },
});

/*
|--------------------------------------------------------------------------
| Séparation logique des inscriptions
|--------------------------------------------------------------------------
|
| Les inscriptions régulières composent réellement les 10 / 20 / 30
| séances du module.
|
| Les rattrapages sont des séances supplémentaires et ne doivent donc
| pas modifier la numérotation de la timeline principale.
|
*/

const regularEnrollments = computed(() => {
    return props.enrollments.filter(
        (enrollment) => enrollment.enrollment_type === "regular",
    );
});

const makeupEnrollments = computed(() => {
    return props.enrollments.filter(
        (enrollment) => enrollment.enrollment_type === "makeup",
    );
});

/*
|--------------------------------------------------------------------------
| État des modales
|--------------------------------------------------------------------------
*/

const declaringEnrollment = ref(null);
const cancellingAbsence = ref(null);

const isProcessing = ref(false);
const isAbsenceConfirmed = ref(false);

/*
|--------------------------------------------------------------------------
| Changement de poste
|--------------------------------------------------------------------------
*/

const isSwitchingSpot = ref(null);
const switchingErrors = ref({});

/*
|--------------------------------------------------------------------------
| Progression
|--------------------------------------------------------------------------
*/

const progressPercentage = computed(() => {
    if (!props.module.total_lessons) {
        return 0;
    }

    return Math.min(
        100,
        Math.round(
            (props.module.completed_lessons / props.module.total_lessons) * 100,
        ),
    );
});

/*
|--------------------------------------------------------------------------
| Helpers d'affichage
|--------------------------------------------------------------------------
*/

const formatTime = (timeStr) => {
    if (!timeStr) {
        return "";
    }

    const parts = timeStr.split(":");

    return `${parts[0]}h${parts[1]}`;
};

const getSpotLabel = (spotType) => {
    return spotType === "wheel" ? "Tour" : "Modelage";
};

const getSpotIcon = (spotType) => {
    return spotType === "wheel" ? Shell : Hand;
};

const getOppositeSpot = (spotType) => {
    return spotType === "wheel" ? "handbuilding" : "wheel";
};

const getAvailableSpots = (enrollment, spotType) => {
    return enrollment.lesson?.spots_available?.[spotType] ?? 0;
};

const getEnrollmentStatusLabel = (enrollment, isMakeup = false) => {
    if (enrollment.status === "cancelled") {
        return isMakeup ? "Rattrapage annulé" : "Inscription annulée";
    }

    if (enrollment.status === "absent") {
        if (isMakeup) {
            return enrollment.is_past
                ? "Rattrapage manqué"
                : "Absence signalée";
        }

        return enrollment.is_past
            ? "Absence passée"
            : "Absence signalée";
    }

    if (enrollment.is_past) {
        return isMakeup
            ? "Rattrapage effectué"
            : "Séance passée";
    }

    return isMakeup
        ? "Rattrapage à venir"
        : "À venir";
};

const getEnrollmentStatusClasses = (enrollment) => {
    if (enrollment.status === "cancelled") {
        return "bg-gray-100 text-gray-500 border-gray-200";
    }

    if (enrollment.status === "absent") {
        return "bg-amber-50 text-amber-800 border-amber-300";
    }

    if (enrollment.is_past) {
        return "bg-gray-100 text-gray-700 border-gray-200";
    }

    return "bg-emerald-50 text-emerald-700 border-emerald-200";
};

/*
|--------------------------------------------------------------------------
| Changement Tour / Modelage
|--------------------------------------------------------------------------
*/

const handleSpotChange = (enrollment, newSpotType) => {
    if (enrollment.spot_type === newSpotType) {
        return;
    }

    switchingErrors.value[enrollment.id] = null;
    isSwitchingSpot.value = enrollment.id;

    router.patch(
        route("member.enrollments.update-spot-type", enrollment.id),
        {
            spot_type: newSpotType,
        },
        {
            preserveScroll: true,

            onError: (errors) => {
                if (errors.spot_type) {
                    switchingErrors.value[enrollment.id] =
                        errors.spot_type;
                }
            },

            onFinish: () => {
                isSwitchingSpot.value = null;
            },
        },
    );
};

/*
|--------------------------------------------------------------------------
| Absences
|--------------------------------------------------------------------------
*/

const openDeclareModal = (enrollment) => {
    declaringEnrollment.value = enrollment;
    isAbsenceConfirmed.value = false;
};

const confirmDeclareAbsence = () => {
    if (!declaringEnrollment.value || !isAbsenceConfirmed.value) {
        return;
    }

    isProcessing.value = true;

    router.post(
        route(
            "member.absences.declare",
            declaringEnrollment.value.id,
        ),
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

const confirmCancelAbsence = () => {
    if (!cancellingAbsence.value) {
        return;
    }

    isProcessing.value = true;

    router.post(
        route(
            "member.absences.cancel",
            cancellingAbsence.value.absenceId,
        ),
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
    <Head :title="`Module ${module.total_lessons} séances`" />

    <Nav />

    <main class="min-h-screen bg-white font-brand">
        <div
            class="max-w-6xl mx-auto px-6 lg:px-0 py-8 space-y-6"
        >
            <!-- ===================================================== -->
            <!-- RETOUR                                                -->
            <!-- ===================================================== -->

            <div>
                <Link :href="route('member.dashboard')">
                    <Button
                        variant="ghost"
                        size="sm"
                        class="-ml-2 text-sm text-gray-600 hover:text-gray-900 group"
                    >
                        <ArrowLeft
                            class="w-4 h-4 mr-1.5 text-earth transition-transform group-hover:-translate-x-0.5"
                        />

                        Retour à mon espace membre
                    </Button>
                </Link>
            </div>

            <!-- ===================================================== -->
            <!-- RÉCAPITULATIF DU MODULE                               -->
            <!-- ===================================================== -->

            <section
                class="bg-white p-6 sm:p-7 rounded-xl border border-gray-200 shadow space-y-6"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-start justify-between gap-5"
                >
                    <div>
                        <!-- Badges -->

                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="text-xs font-semibold uppercase tracking-wider px-2.5 py-0.5 rounded-md bg-gray-100 text-gray-700"
                            >
                                {{ module.type.name }}
                            </span>

                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-0.5 rounded-full',
                                    module.is_owner
                                        ? 'bg-gray-900 text-white'
                                        : 'bg-gray-100 text-gray-700 border border-gray-200',
                                ]"
                            >
                                <component
                                    :is="module.is_owner ? User : Users"
                                    class="w-3.5 h-3.5"
                                />

                                {{
                                    module.is_owner
                                        ? "Moi-même"
                                        : module.participant.name
                                }}
                            </span>
                        </div>

                        <h1
                            class="text-3xl sm:text-4xl font-medium tracking-tight text-gray-900 mt-3"
                        >
                            Module de
                            {{ pluralize(module.total_lessons, "séance") }}
                        </h1>

                        <p class="text-sm text-gray-500 mt-2">
                            Acheté le
                            {{ formatDate(module.purchase_date) }}

                            <span v-if="module.expiration_date">
                                · Expire le
                                {{ formatDate(module.expiration_date) }}
                            </span>
                        </p>
                    </div>

                    <!-- CTA Rattrapage -->

                    <div
                        v-if="module.can_book_makeup"
                        class="shrink-0"
                    >
                        <Link
                            :href="
                                route('calendrier.index', {
                                    mode: 'makeup',
                                    module_id: module.id,
                                })
                            "
                        >
                            <Button
                                size="sm"
                                class="group bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium h-9 px-4 shadow-xs"
                            >
                                <RotateCcw class="w-4 h-4 mr-1.5" />

                                Poser un rattrapage

                                <ArrowLeft
                                    class="w-0 h-4 opacity-0 rotate-180 -ml-1 transition-all duration-200 group-hover:w-4 group-hover:opacity-100 group-hover:ml-1"
                                />
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- ================================================= -->
                <!-- PROGRESSION                                       -->
                <!-- ================================================= -->

                <div class="space-y-2 pt-4 border-t border-gray-100">
                    <div
                        class="flex items-center justify-between gap-4 text-sm font-medium"
                    >
                        <span class="text-gray-700">
                            Progression :
                            {{ module.completed_lessons }} sur
                            {{ pluralize(module.total_lessons, "séance") }}
                            effectuée{{
                                module.completed_lessons > 1 ? "s" : ""
                            }}
                        </span>

                        <span class="font-bold text-gray-900">
                            {{ progressPercentage }}%
                        </span>
                    </div>

                    <div
                        class="h-2.5 w-full bg-gray-100 rounded-full overflow-hidden"
                    >
                        <div
                            class="h-full bg-sage rounded-full transition-all duration-500"
                            :style="{
                                width: `${progressPercentage}%`,
                            }"
                        />
                    </div>
                </div>

                <!-- ================================================= -->
                <!-- RATTRAPAGES                                       -->
                <!-- ================================================= -->

                <div
                    class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1"
                >
                    <!-- Utilisés -->

                    <div
                        class="p-4 bg-gray-50 rounded-xl border border-gray-200"
                    >
                        <div
                            class="flex items-center gap-2 text-xs font-medium text-gray-500"
                        >
                            <RotateCcw class="w-4 h-4" />

                            Rattrapages utilisés
                        </div>

                        <p
                            class="text-xl font-bold text-gray-900 mt-1.5"
                        >
                            {{ module.makeups_used_count }}
                            <span
                                class="text-sm font-medium text-gray-400"
                            >
                                / {{ module.max_makeups_allowed }}
                            </span>
                        </p>
                    </div>

                    <!-- Crédits réellement disponibles -->

                    <div
                        class="p-4 bg-sage-light rounded-xl border border-sage-border"
                    >
                        <div
                            class="flex items-center gap-2 text-xs font-medium text-sage-dark"
                        >
                            <Sparkles class="w-4 h-4" />

                            Crédits disponibles
                        </div>

                        <p
                            class="text-xl font-bold text-gray-900 mt-1.5"
                        >
                            {{
                                module.available_makeup_credits_count ?? 0
                            }}
                        </p>
                    </div>

                    <!-- Quota -->

                    <div
                        class="p-4 bg-earth-light rounded-xl border border-earth-border"
                    >
                        <div
                            class="flex items-center gap-2 text-xs font-medium text-earth-header"
                        >
                            <CheckCircle2 class="w-4 h-4" />

                            Quota restant
                        </div>

                        <p
                            class="text-xl font-bold text-gray-900 mt-1.5"
                        >
                            {{ module.remaining_makeups }}
                        </p>
                    </div>
                </div>

                <!-- Information si aucun rattrapage possible -->

                <div
                    v-if="
                        module.is_active &&
                        !module.can_book_makeup
                    "
                    class="flex items-start gap-2.5 p-3.5 bg-gray-50 border border-gray-200 rounded-xl text-xs sm:text-sm text-gray-600"
                >
                    <AlertCircle
                        class="w-4 h-4 mt-0.5 shrink-0 text-gray-400"
                    />

                    <p
                        v-if="
                            module.remaining_makeups <= 0
                        "
                    >
                        Le quota maximal de rattrapages de ce module
                        est atteint.
                    </p>

                    <p
                        v-else-if="
                            (module.available_makeup_credits_count ?? 0) <= 0
                        "
                    >
                        Vous disposez encore d'un quota de
                        rattrapage, mais aucun crédit d'absence
                        n'est actuellement disponible.
                    </p>
                </div>
            </section>

            <!-- ===================================================== -->
            <!-- TIMELINE : SÉANCES RÉGULIÈRES                         -->
            <!-- ===================================================== -->

            <section
                class="bg-white p-6 sm:p-7 rounded-xl border border-gray-200 shadow"
            >
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Séquence des séances du module
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        Les séances régulières qui composent votre
                        module.
                    </p>
                </div>

                <div
                    v-if="regularEnrollments.length > 0"
                >
                    <div
                        v-for="(enrollment, index) in regularEnrollments"
                        :key="enrollment.id"
                        class="relative pb-5"
                    >
                        <!--
                            Ligne verticale.
                            Elle reste volontairement derrière les boules
                            numérotées : ne pas retirer ce positionnement.
                        -->

                        <div
                            v-if="
                                index <
                                regularEnrollments.length - 1
                            "
                            class="absolute left-[17px] top-12 -bottom-12 w-px bg-gray-200"
                        />

                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        >
                            <!-- ===================================== -->
                            <!-- GAUCHE : BOULE + INFORMATIONS         -->
                            <!-- ===================================== -->

                            <div
                                class="flex items-start sm:items-center gap-3.5 min-w-0"
                            >
                                <!-- Boule numérotée -->

                                <div
                                    :class="[
                                        'relative z-10 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm shrink-0 border',
                                        enrollment.status === 'absent'
                                            ? 'bg-amber-50 border-amber-300 text-amber-800'
                                            : enrollment.is_past
                                              ? 'bg-gray-200 border-gray-200 text-gray-600'
                                              : 'bg-gray-900 border-gray-900 text-white',
                                    ]"
                                >
                                    {{ index + 1 }}
                                </div>

                                <div
                                    class="min-w-0 flex flex-col gap-y-1.5"
                                >
                                    <!-- Date -->

                                    <div
                                        class="flex items-center gap-1.5 text-base sm:text-lg font-semibold text-gray-900 capitalize"
                                    >
                                        <Calendar
                                            class="w-5 h-5 text-gray-600 shrink-0"
                                        />

                                        <span class="truncate">
                                            {{
                                                formatDate(
                                                    enrollment.lesson.date,
                                                    {
                                                        weekday: "long",
                                                    },
                                                )
                                            }}
                                        </span>
                                    </div>

                                    <!-- Badges -->

                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <!-- Statut -->

                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border',
                                                getEnrollmentStatusClasses(
                                                    enrollment,
                                                ),
                                            ]"
                                        >
                                            <AlertCircle
                                                v-if="
                                                    enrollment.status ===
                                                    'absent'
                                                "
                                                class="w-3.5 h-3.5"
                                            />

                                            <Ban
                                                v-else-if="
                                                    enrollment.status ===
                                                    'cancelled'
                                                "
                                                class="w-3.5 h-3.5"
                                            />

                                            <Check
                                                v-else-if="
                                                    enrollment.is_past
                                                "
                                                class="w-3.5 h-3.5 text-emerald-600"
                                            />

                                            <CheckCircle2
                                                v-else
                                                class="w-3.5 h-3.5"
                                            />

                                            {{
                                                getEnrollmentStatusLabel(
                                                    enrollment,
                                                )
                                            }}
                                        </span>

                                        <!-- ========================= -->
                                        <!-- POSTE MODIFIABLE          -->
                                        <!-- ========================= -->

                                        <DropdownMenu
                                            v-if="
                                                !enrollment.is_past &&
                                                enrollment.status ===
                                                    'registered'
                                            "
                                        >
                                            <DropdownMenuTrigger
                                                as-child
                                            >
                                                <button
                                                    type="button"
                                                    :disabled="
                                                        isSwitchingSpot ===
                                                        enrollment.id
                                                    "
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-200 transition cursor-pointer disabled:opacity-50"
                                                >
                                                    <component
                                                        :is="
                                                            getSpotIcon(
                                                                enrollment.spot_type,
                                                            )
                                                        "
                                                        class="w-3.5 h-3.5"
                                                    />

                                                    {{
                                                        getSpotLabel(
                                                            enrollment.spot_type,
                                                        )
                                                    }}

                                                    <ChevronDown
                                                        class="w-3.5 h-3.5 text-gray-500"
                                                    />
                                                </button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="start"
                                                class="w-60 bg-white border-gray-200"
                                            >
                                                <DropdownMenuItem
                                                    :disabled="
                                                        getAvailableSpots(
                                                            enrollment,
                                                            getOppositeSpot(
                                                                enrollment.spot_type,
                                                            ),
                                                        ) <= 0 ||
                                                        isSwitchingSpot ===
                                                            enrollment.id
                                                    "
                                                    class="cursor-pointer flex items-center justify-between gap-3 py-2"
                                                    @click="
                                                        handleSpotChange(
                                                            enrollment,
                                                            getOppositeSpot(
                                                                enrollment.spot_type,
                                                            ),
                                                        )
                                                    "
                                                >
                                                    <span
                                                        class="inline-flex items-center gap-2"
                                                    >
                                                        <component
                                                            :is="
                                                                getSpotIcon(
                                                                    getOppositeSpot(
                                                                        enrollment.spot_type,
                                                                    ),
                                                                )
                                                            "
                                                            class="w-4 h-4 text-gray-500"
                                                        />

                                                        Passer en
                                                        {{
                                                            getSpotLabel(
                                                                getOppositeSpot(
                                                                    enrollment.spot_type,
                                                                ),
                                                            )
                                                        }}
                                                    </span>

                                                    <span
                                                        v-if="
                                                            getAvailableSpots(
                                                                enrollment,
                                                                getOppositeSpot(
                                                                    enrollment.spot_type,
                                                                ),
                                                            ) <= 0
                                                        "
                                                        class="text-xs text-gray-400 font-semibold"
                                                    >
                                                        Complet
                                                    </span>

                                                    <span
                                                        v-else
                                                        class="text-xs text-gray-500"
                                                    >
                                                        {{
                                                            getAvailableSpots(
                                                                enrollment,
                                                                getOppositeSpot(
                                                                    enrollment.spot_type,
                                                                ),
                                                            )
                                                        }}
                                                        dispo
                                                    </span>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>

                                        <!-- Poste non modifiable -->

                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200"
                                        >
                                            <component
                                                :is="
                                                    getSpotIcon(
                                                        enrollment.spot_type,
                                                    )
                                                "
                                                class="w-3.5 h-3.5"
                                            />

                                            {{
                                                getSpotLabel(
                                                    enrollment.spot_type,
                                                )
                                            }}
                                        </span>
                                    </div>

                                    <!-- Horaire -->

                                    <div
                                        class="flex items-center gap-1.5 text-sm text-gray-500"
                                    >
                                        <Clock
                                            class="w-4 h-4 text-gray-400 shrink-0"
                                        />

                                        {{
                                            formatTime(
                                                enrollment.lesson
                                                    .start_time,
                                            )
                                        }}
                                        -
                                        {{
                                            formatTime(
                                                enrollment.lesson
                                                    .end_time,
                                            )
                                        }}

                                        <span
                                            class="hidden sm:inline text-gray-300"
                                        >
                                            ·
                                        </span>

                                        <span
                                            class="hidden sm:inline truncate"
                                        >
                                            {{
                                                enrollment.lesson
                                                    .course_name
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- ===================================== -->
                            <!-- DROITE : ACTION ABSENCE              -->
                            <!-- ===================================== -->

                            <div
                                v-if="!enrollment.is_past"
                                class="shrink-0 flex items-center ml-[3.25rem] sm:ml-0"
                            >
                                <!-- Déclarer -->

                                <Button
                                    v-if="
                                        enrollment.status ===
                                        'registered'
                                    "
                                    variant="outline"
                                    size="sm"
                                    class="group/btn text-xs sm:text-sm text-gray-600 hover:text-red-700 hover:bg-red-50 hover:border-red-200 border-gray-200 h-9 font-medium"
                                    @click="
                                        openDeclareModal(enrollment)
                                    "
                                >
                                    <Ban
                                        class="w-4 h-4 mr-1.5 text-gray-400 group-hover/btn:text-red-600 transition-colors"
                                    />

                                    Signaler une absence
                                </Button>

                                <!-- Reprendre -->

                                <Button
                                    v-else-if="
                                        enrollment.status ===
                                            'absent' &&
                                        enrollment.absence
                                    "
                                    variant="outline"
                                    size="sm"
                                    class="group/btn text-xs sm:text-sm text-amber-800 bg-amber-50 border-amber-300 hover:bg-amber-100 hover:text-amber-900 hover:border-amber-400 h-9 font-medium transition"
                                    @click="
                                        cancellingAbsence = {
                                            absenceId:
                                                enrollment.absence.id,
                                            enrollment,
                                        }
                                    "
                                >
                                    <RotateCcw
                                        class="w-4 h-4 mr-1.5"
                                    />

                                    {{
                                        module.is_owner
                                            ? "Reprendre ma place"
                                            : "Reprendre la place"
                                    }}
                                </Button>
                            </div>
                        </div>

                        <!-- Erreur changement poste -->

                        <div
                            v-if="
                                switchingErrors[enrollment.id]
                            "
                            class="ml-[3.25rem] mt-3 p-2.5 bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm rounded-lg flex items-center gap-2 font-medium"
                        >
                            <AlertCircle
                                class="w-4 h-4 shrink-0"
                            />

                            <span>
                                {{
                                    switchingErrors[
                                        enrollment.id
                                    ]
                                }}
                            </span>
                        </div>

                        <!-- Séparation -->

                        <Separator
                            v-if="
                                index <
                                regularEnrollments.length - 1
                            "
                            class="ml-[3.25rem] mt-5 w-[calc(100%-3.25rem)]"
                        />
                    </div>
                </div>

                <!-- État vide -->

                <div
                    v-else
                    class="py-10 text-center"
                >
                    <Calendar
                        class="w-8 h-8 text-gray-300 mx-auto"
                    />

                    <p class="text-sm text-gray-500 mt-2">
                        Aucune séance régulière dans ce module.
                    </p>
                </div>
            </section>

            <!-- ===================================================== -->
            <!-- RATTRAPAGES POSITIONNÉS                               -->
            <!-- ===================================================== -->

            <section
                v-if="makeupEnrollments.length > 0"
                class="bg-white p-6 sm:p-7 rounded-xl border border-gray-200 shadow"
            >
                <div class="mb-5">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-8 h-8 rounded-lg bg-sage-light border border-sage-border text-sage-dark flex items-center justify-center"
                        >
                            <RotateCcw class="w-4 h-4" />
                        </div>

                        <div>
                            <h2
                                class="text-lg font-semibold text-gray-900"
                            >
                                Rattrapages placés
                            </h2>

                            <p
                                class="text-sm text-gray-500 mt-0.5"
                            >
                                Ces séances ne modifient pas la
                                progression ni la numérotation de votre
                                module.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-gray-100">
                    <div
                        v-for="enrollment in makeupEnrollments"
                        :key="enrollment.id"
                        class="py-4 first:pt-0 last:pb-0"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        >
                            <div
                                class="flex items-start gap-3.5 min-w-0"
                            >
                                <!-- Icône rattrapage -->

                                <div
                                    :class="[
                                        'w-9 h-9 rounded-full flex items-center justify-center shrink-0 border',
                                        enrollment.status === 'absent'
                                            ? 'bg-amber-50 border-amber-300 text-amber-800'
                                            : enrollment.is_past
                                              ? 'bg-gray-100 border-gray-200 text-gray-500'
                                              : 'bg-sage-light border-sage-border text-sage-dark',
                                    ]"
                                >
                                    <RotateCcw class="w-4 h-4" />
                                </div>

                                <div class="min-w-0">
                                    <div
                                        class="flex items-center gap-1.5 text-base font-semibold text-gray-900 capitalize"
                                    >
                                        <Calendar
                                            class="w-4 h-4 text-gray-500 shrink-0"
                                        />

                                        {{
                                            formatDate(
                                                enrollment.lesson.date,
                                                {
                                                    weekday: "long",
                                                },
                                            )
                                        }}
                                    </div>

                                    <div
                                        class="flex flex-wrap items-center gap-2 mt-2"
                                    >
                                        <!-- Statut -->

                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border',
                                                getEnrollmentStatusClasses(
                                                    enrollment,
                                                ),
                                            ]"
                                        >
                                            <AlertCircle
                                                v-if="
                                                    enrollment.status ===
                                                    'absent'
                                                "
                                                class="w-3.5 h-3.5"
                                            />

                                            <Ban
                                                v-else-if="
                                                    enrollment.status ===
                                                    'cancelled'
                                                "
                                                class="w-3.5 h-3.5"
                                            />

                                            <Check
                                                v-else-if="
                                                    enrollment.is_past
                                                "
                                                class="w-3.5 h-3.5"
                                            />

                                            <CheckCircle2
                                                v-else
                                                class="w-3.5 h-3.5"
                                            />

                                            {{
                                                getEnrollmentStatusLabel(
                                                    enrollment,
                                                    true,
                                                )
                                            }}
                                        </span>

                                        <!-- Poste -->

                                        <DropdownMenu
                                            v-if="
                                                !enrollment.is_past &&
                                                enrollment.status ===
                                                    'registered'
                                            "
                                        >
                                            <DropdownMenuTrigger
                                                as-child
                                            >
                                                <button
                                                    type="button"
                                                    :disabled="
                                                        isSwitchingSpot ===
                                                        enrollment.id
                                                    "
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-200 transition cursor-pointer disabled:opacity-50"
                                                >
                                                    <component
                                                        :is="
                                                            getSpotIcon(
                                                                enrollment.spot_type,
                                                            )
                                                        "
                                                        class="w-3.5 h-3.5"
                                                    />

                                                    {{
                                                        getSpotLabel(
                                                            enrollment.spot_type,
                                                        )
                                                    }}

                                                    <ChevronDown
                                                        class="w-3.5 h-3.5"
                                                    />
                                                </button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="start"
                                                class="w-60 bg-white"
                                            >
                                                <DropdownMenuItem
                                                    :disabled="
                                                        getAvailableSpots(
                                                            enrollment,
                                                            getOppositeSpot(
                                                                enrollment.spot_type,
                                                            ),
                                                        ) <= 0 ||
                                                        isSwitchingSpot ===
                                                            enrollment.id
                                                    "
                                                    class="cursor-pointer flex items-center justify-between gap-3 py-2"
                                                    @click="
                                                        handleSpotChange(
                                                            enrollment,
                                                            getOppositeSpot(
                                                                enrollment.spot_type,
                                                            ),
                                                        )
                                                    "
                                                >
                                                    <span
                                                        class="inline-flex items-center gap-2"
                                                    >
                                                        <component
                                                            :is="
                                                                getSpotIcon(
                                                                    getOppositeSpot(
                                                                        enrollment.spot_type,
                                                                    ),
                                                                )
                                                            "
                                                            class="w-4 h-4"
                                                        />

                                                        Passer en
                                                        {{
                                                            getSpotLabel(
                                                                getOppositeSpot(
                                                                    enrollment.spot_type,
                                                                ),
                                                            )
                                                        }}
                                                    </span>

                                                    <span
                                                        v-if="
                                                            getAvailableSpots(
                                                                enrollment,
                                                                getOppositeSpot(
                                                                    enrollment.spot_type,
                                                                ),
                                                            ) <= 0
                                                        "
                                                        class="text-xs text-gray-400"
                                                    >
                                                        Complet
                                                    </span>

                                                    <span
                                                        v-else
                                                        class="text-xs text-gray-500"
                                                    >
                                                        {{
                                                            getAvailableSpots(
                                                                enrollment,
                                                                getOppositeSpot(
                                                                    enrollment.spot_type,
                                                                ),
                                                            )
                                                        }}
                                                        dispo
                                                    </span>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>

                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-gray-100 border border-gray-200 text-gray-600 text-xs font-medium"
                                        >
                                            <component
                                                :is="
                                                    getSpotIcon(
                                                        enrollment.spot_type,
                                                    )
                                                "
                                                class="w-3.5 h-3.5"
                                            />

                                            {{
                                                getSpotLabel(
                                                    enrollment.spot_type,
                                                )
                                            }}
                                        </span>
                                    </div>

                                    <!-- Heure -->

                                    <p
                                        class="text-sm text-gray-500 mt-2 flex items-center gap-1.5"
                                    >
                                        <Clock
                                            class="w-4 h-4 text-gray-400 shrink-0"
                                        />

                                        {{
                                            formatTime(
                                                enrollment.lesson
                                                    .start_time,
                                            )
                                        }}
                                        -
                                        {{
                                            formatTime(
                                                enrollment.lesson
                                                    .end_time,
                                            )
                                        }}

                                        <span class="text-gray-300">
                                            ·
                                        </span>

                                        <span class="truncate">
                                            {{
                                                enrollment.lesson
                                                    .course_name
                                            }}
                                        </span>
                                    </p>

                                    <!-- Séance remplacée -->

                                    <div
                                        v-if="enrollment.replaces"
                                        class="mt-2 inline-flex items-center gap-1.5 text-xs text-sage-dark bg-sage-light border border-sage-border rounded-lg px-2.5 py-1"
                                    >
                                        <RotateCcw
                                            class="w-3.5 h-3.5 shrink-0"
                                        />

                                        Rattrape l'absence du
                                        {{
                                            enrollment.replaces
                                                .course_name
                                        }}
                                        du
                                        {{
                                            formatDate(
                                                enrollment.replaces
                                                    .date,
                                            )
                                        }}
                                    </div>
                                </div>
                            </div>

                            <!-- Absence sur un rattrapage -->

                            <Button
                                v-if="
                                    !enrollment.is_past &&
                                    enrollment.status ===
                                        'registered'
                                "
                                variant="outline"
                                size="sm"
                                class="group/btn ml-[3.25rem] sm:ml-0 shrink-0 text-xs sm:text-sm text-gray-600 hover:text-red-700 hover:bg-red-50 hover:border-red-200 border-gray-200 h-9"
                                @click="
                                    openDeclareModal(enrollment)
                                "
                            >
                                <Ban
                                    class="w-4 h-4 mr-1.5 text-gray-400 group-hover/btn:text-red-600"
                                />

                                Signaler une absence
                            </Button>
                        </div>

                        <div
                            v-if="
                                switchingErrors[enrollment.id]
                            "
                            class="ml-[3.25rem] mt-3 p-2.5 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg flex items-center gap-2"
                        >
                            <AlertCircle
                                class="w-4 h-4 shrink-0"
                            />

                            {{
                                switchingErrors[
                                    enrollment.id
                                ]
                            }}
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <Footer />

    <!-- ============================================================= -->
    <!-- MODALE : DÉCLARER UNE ABSENCE                                 -->
    <!-- ============================================================= -->

    <Dialog
        :open="!!declaringEnrollment"
        @update:open="
            (value) =>
                !value && (declaringEnrollment = null)
        "
    >
        <DialogContent
            class="sm:max-w-md bg-white border-gray-200 font-brand"
        >
            <DialogHeader v-if="declaringEnrollment">
                <DialogTitle
                    class="text-lg font-semibold text-gray-900 flex items-center gap-2"
                >
                    <AlertCircle
                        class="w-5 h-5 text-amber-600 shrink-0"
                    />

                    Déclarer une absence
                </DialogTitle>

                <DialogDescription
                    class="text-sm text-gray-500"
                >
                    {{
                        declaringEnrollment.enrollment_type ===
                        "makeup"
                            ? "Cours de rattrapage"
                            : `Séance du module`
                    }}
                    —
                    {{
                        formatDate(
                            declaringEnrollment.lesson.date,
                        )
                    }}
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="declaringEnrollment"
                class="space-y-4 py-2 text-sm text-gray-600"
            >
                <p>
                    Vous êtes sur le point de signaler
                    {{
                        module.is_owner
                            ? "votre absence"
                            : `l'absence de ${module.participant.name}`
                    }}
                    pour cette séance.
                </p>

                <!-- Important pour les rattrapages -->

                <div
                    v-if="
                        declaringEnrollment.enrollment_type ===
                        'makeup'
                    "
                    class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-2.5 text-xs text-amber-900"
                >
                    <AlertCircle
                        class="w-4 h-4 mt-0.5 shrink-0"
                    />

                    <p>
                        Il s'agit d'un cours de rattrapage. Une
                        absence à ce rattrapage consommera le crédit
                        utilisé et ne générera pas un nouveau crédit
                        de rattrapage.
                    </p>
                </div>

                <div
                    v-else
                    class="p-3 bg-gray-50 border border-gray-200 rounded-xl flex items-start gap-2.5 text-xs text-gray-600"
                >
                    <Sparkles
                        class="w-4 h-4 mt-0.5 shrink-0 text-sage-dark"
                    />

                    <p>
                        Si cette absence respecte les conditions de
                        rattrapage de votre module, elle pourra
                        générer un crédit disponible dans votre
                        compte.
                    </p>
                </div>

                <!-- Confirmation obligatoire -->

                <div class="pt-3 border-t border-gray-100">
                    <label
                        class="flex items-start gap-3 text-sm text-gray-800 cursor-pointer select-none font-medium"
                    >
                        <input
                            v-model="isAbsenceConfirmed"
                            type="checkbox"
                            class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer w-4 h-4"
                        />

                        <span>
                            {{
                                module.is_owner
                                    ? "Je confirme que je serai absent(e) à cette séance."
                                    : `Je confirme que ${module.participant.name} sera absent(e) à cette séance.`
                            }}
                        </span>
                    </label>
                </div>
            </div>

            <DialogFooter
                class="flex flex-col sm:flex-row gap-2 pt-3"
            >
                <Button
                    variant="destructive"
                    size="sm"
                    class="text-sm h-9"
                    :disabled="
                        isProcessing || !isAbsenceConfirmed
                    "
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
                    @click="
                        declaringEnrollment = null;
                        isAbsenceConfirmed = false;
                    "
                >
                    Annuler
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- ============================================================= -->
    <!-- MODALE : REPRENDRE SA PLACE                                   -->
    <!-- ============================================================= -->

    <Dialog
        :open="!!cancellingAbsence"
        @update:open="
            (value) =>
                !value && (cancellingAbsence = null)
        "
    >
        <DialogContent
            class="sm:max-w-md bg-white border-gray-200 font-brand"
        >
            <DialogHeader v-if="cancellingAbsence">
                <DialogTitle
                    class="text-lg font-semibold text-gray-900 flex items-center gap-2"
                >
                    <RotateCcw
                        class="w-5 h-5 text-earth-header shrink-0"
                    />

                    {{
                        module.is_owner
                            ? "Reprendre ma place"
                            : "Reprendre la place"
                    }}
                </DialogTitle>

                <DialogDescription
                    class="text-sm text-gray-500"
                >
                    Annuler l'avis d'absence et réintégrer la séance.
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="cancellingAbsence"
                class="space-y-3 py-2 text-sm text-gray-600"
            >
                <div
                    class="p-3.5 rounded-xl bg-gray-50 border border-gray-200"
                >
                    <div class="flex items-center gap-2">
                        <Calendar
                            class="w-4 h-4 text-gray-500 shrink-0"
                        />

                        <span
                            class="font-semibold text-gray-900"
                        >
                            {{
                                formatDate(
                                    cancellingAbsence.enrollment
                                        .lesson.date,
                                )
                            }}
                        </span>
                    </div>

                    <div
                        class="flex items-center gap-2 text-xs text-gray-500 mt-1.5"
                    >
                        <Clock class="w-3.5 h-3.5" />

                        {{
                            formatTime(
                                cancellingAbsence.enrollment
                                    .lesson.start_time,
                            )
                        }}
                        -
                        {{
                            formatTime(
                                cancellingAbsence.enrollment
                                    .lesson.end_time,
                            )
                        }}
                    </div>
                </div>

                <p>
                    Votre avis d'absence sera annulé et
                    l'inscription d'origine repassera immédiatement
                    au statut inscrit, sous réserve des règles
                    applicables à la séance.
                </p>
            </div>

            <DialogFooter
                class="flex flex-col sm:flex-row gap-2 pt-3"
            >
                <Button
                    size="sm"
                    class="text-sm h-9 bg-blue-600 hover:bg-blue-500 text-white"
                    :disabled="isProcessing"
                    @click="confirmCancelAbsence"
                >
                    <RotateCcw
                        v-if="!isProcessing"
                        class="w-4 h-4 mr-1.5"
                    />

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
</template>
