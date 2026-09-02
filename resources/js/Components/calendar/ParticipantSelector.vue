<script setup>
import { ref, computed } from "vue";
import { useForm } from "laravel-precognition-vue-inertia";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { ScrollArea } from "@/Components/ui/scroll-area";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { User, UserPlus, Users, X, Plus, Shell, Hand } from "lucide-vue-next";
import { pluralize } from "@/Utils/formatters";

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: "regular", // 'regular' | 'makeup'
    },
    attendees: {
        type: Array,
        default: () => [],
    },
    currentUser: {
        type: Object,
        default: () => ({}),
    },
    lesson: {
        type: Object,
        default: null,
    },
    activeAbsences: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(["update:modelValue", "change"]);

const showNewAttendeeForm = ref(false);

// Sous-formulaire Precognition d'ajout d'invité à la volée
const attendeeForm = useForm("post", route("attendees.store"), {
    first_name: "",
    last_name: "",
    birthday: "",
});

// Détermine le poste par défaut (Tour si disponible, sinon Modelage)
const getDefaultSpot = () => {
    if (props.mode === "makeup") {
        return (props.lesson?.wheel?.makeups_available ?? 0) > 0 ? "wheel" : "handbuilding";
    }
    return (props.lesson?.wheel?.standard_available ?? 0) > 0 ? "wheel" : "handbuilding";
};

// Capacité totale restante
const totalAvailableSpots = computed(() => {
    if (!props.lesson) return 0;
    if (props.mode === "makeup") {
        return (props.lesson.wheel?.makeups_available ?? 0) + (props.lesson.handbuilding?.makeups_available ?? 0);
    }
    return (props.lesson.wheel?.standard_available ?? 0) + (props.lesson.handbuilding?.standard_available ?? 0);
});

// Limite de capacité atteinte
const isCapacityReached = computed(() => {
    if (props.mode === "makeup") {
        return props.modelValue.length >= 1 || totalAvailableSpots.value <= 0;
    }
    return props.modelValue.length >= totalAvailableSpots.value || totalAvailableSpots.value <= 0;
});

// Vérifie si un participant est déjà ajouté dans le formulaire en cours
const isParticipantSelected = (type, id) => {
    return props.modelValue.some(
        (p) => p.participant_type === type && Number(p.participant_id) === Number(id)
    );
};

// Vérifie si un participant est déjà inscrit ou absent sur ce cours spécifique en BDD
const getLessonEnrollment = (type, id) => {
    if (!props.lesson?.user_enrollments) return null;
    return props.lesson.user_enrollments.find(
        (e) => e.participant_type === type && Number(e.participant_id) === Number(id)
    );
};

const getCandidateStatus = (type, id) => {
    const enr = getLessonEnrollment(type, id);
    if (enr?.is_absent) {
        return { disabled: true, reason: "Déjà noté absent sur cette séance" };
    }
    if (enr && !enr.is_absent) {
        return { disabled: true, reason: "Déjà inscrit à cette séance" };
    }
    return { disabled: false, reason: null };
};

// Liste filtrée des invités disponibles (non encore ajoutés au panier)
const availableAttendees = computed(() => {
    return props.attendees.filter((att) => !isParticipantSelected("App\\Models\\Attendee", att.id));
});

const isUserAvailable = computed(() => {
    if (!props.currentUser?.id) return false;
    return !isParticipantSelected("App\\Models\\User", props.currentUser.id);
});

// Actions d'ajout / suppression
const addParticipant = (type, id, name, label) => {
    const check = getCandidateStatus(type, id);
    if (check.disabled) return;

    const newEntry = {
        participant_type: type,
        participant_id: id,
        name: name,
        label: label,
        spot_type: getDefaultSpot(),
    };

    let updated;
    if (props.mode === "makeup") {
        // En mode rattrapage, on définit le participant unique
        updated = [newEntry];
    } else {
        updated = [...props.modelValue, newEntry];
    }

    emit("update:modelValue", updated);
    emit("change", updated);
    showNewAttendeeForm.value = false;
};

const removeParticipant = (index) => {
    const updated = [...props.modelValue];
    updated.splice(index, 1);
    emit("update:modelValue", updated);
    emit("change", updated);
};

const setParticipantSpot = (index, spotType) => {
    const updated = [...props.modelValue];
    updated[index] = { ...updated[index], spot_type: spotType };
    emit("update:modelValue", updated);
    emit("change", updated);
};

const submitNewAttendee = () => {
    const newFirstName = attendeeForm.first_name;
    const newLastName = attendeeForm.last_name;

    attendeeForm.submit({
        preserveScroll: true,
        onSuccess: (pageData) => {
            showNewAttendeeForm.value = false;
            attendeeForm.reset();

            const refreshedAttendees = pageData?.props?.attendees || props.attendees;
            const newlyCreated = refreshedAttendees.find(
                (a) => a.first_name === newFirstName && a.last_name === newLastName
            ) || refreshedAttendees[refreshedAttendees.length - 1];

            if (newlyCreated) {
                addParticipant(
                    "App\\Models\\Attendee",
                    newlyCreated.id,
                    `${newlyCreated.first_name} ${newlyCreated.last_name}`,
                    "Invité / Enfant"
                );
            }
        },
    });
};
</script>

<template>
    <div class="space-y-3">
        <!-- 1. Liste des Participants Sélectionnés -->
        <div v-if="modelValue.length > 0" class="space-y-2.5">
            <div
                v-for="(participant, index) in modelValue"
                :key="`${participant.participant_type}-${participant.participant_id}`"
                class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl shadow-xs space-y-2.5"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-sage-light border border-sage-border text-sage-dark flex items-center justify-center shrink-0 text-xs font-bold">
                            <component :is="participant.participant_type === 'App\\Models\\User' ? User : Users" class="w-4 h-4" />
                        </div>
                        <div class="truncate">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ participant.name }}</p>
                            <p class="text-xs text-gray-500">{{ participant.label }}</p>
                        </div>
                    </div>

                    <!-- Bouton pour retirer/changer le participant -->
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-full shrink-0"
                        @click="removeParticipant(index)"
                        title="Retirer ce participant"
                    >
                        <X class="w-4 h-4" />
                    </Button>
                </div>

                <!-- Choix du Poste : Tour à gauche, Modelage à droite -->
                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-gray-200">
                    <button
                        type="button"
                        :disabled="mode === 'makeup' ? lesson?.wheel?.makeups_available === 0 : lesson?.wheel?.standard_available === 0"
                        :class="[
                            'py-1.5 px-2.5 text-xs font-medium rounded-lg border transition text-left flex items-center justify-between',
                            participant.spot_type === 'wheel'
                                ? 'bg-gray-900 text-white border-gray-900 shadow-2xs'
                                : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-100',
                            (mode === 'makeup' ? lesson?.wheel?.makeups_available === 0 : lesson?.wheel?.standard_available === 0)
                                ? 'opacity-40 cursor-not-allowed'
                                : 'cursor-pointer'
                        ]"
                        @click="setParticipantSpot(index, 'wheel')"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <Shell class="w-3.5 h-3.5" />
                            Tour
                        </span>
                        <span class="text-[11px] opacity-75">
                            ({{ pluralize(mode === 'makeup' ? lesson?.wheel?.makeups_available : lesson?.wheel?.standard_available, 'dispo') }})
                        </span>
                    </button>

                    <button
                        type="button"
                        :disabled="mode === 'makeup' ? lesson?.handbuilding?.makeups_available === 0 : lesson?.handbuilding?.standard_available === 0"
                        :class="[
                            'py-1.5 px-2.5 text-xs font-medium rounded-lg border transition text-left flex items-center justify-between',
                            participant.spot_type === 'handbuilding'
                                ? 'bg-gray-900 text-white border-gray-900 shadow-2xs'
                                : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-100',
                            (mode === 'makeup' ? lesson?.handbuilding?.makeups_available === 0 : lesson?.handbuilding?.standard_available === 0)
                                ? 'opacity-40 cursor-not-allowed'
                                : 'cursor-pointer'
                        ]"
                        @click="setParticipantSpot(index, 'handbuilding')"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <Hand class="w-3.5 h-3.5" />
                            Modelage
                        </span>
                        <span class="text-[11px] opacity-75">
                            ({{ pluralize(mode === 'makeup' ? lesson?.handbuilding?.makeups_available : lesson?.handbuilding?.standard_available, 'dispo') }})
                        </span>
                    </button>
                </div>

                <p v-if="errors[`participants.${index}.participant_id`]" class="text-xs text-red-600 font-medium pt-1">
                    {{ errors[`participants.${index}.participant_id`] }}
                </p>
            </div>
        </div>

        <!-- Encadré vide quand aucun participant n'est sélectionné -->
        <div v-else class="p-4 border border-dashed border-gray-300 rounded-xl text-center bg-gray-50/50">
            <p class="text-xs text-gray-500">
                {{ mode === 'makeup' ? 'Sélectionnez la personne bénéficiant de ce cours de rattrapage.' : 'Aucun participant sélectionné pour ce module.' }}
            </p>
        </div>

        <!-- 2. Formulaire Inline de création d'invité (Mode Régulier) -->
        <div v-if="showNewAttendeeForm && mode === 'regular'" class="p-3.5 bg-blue-50/60 border border-blue-200 rounded-xl space-y-3">
            <div class="flex items-center justify-between">
                <h5 class="text-xs font-bold text-blue-950 flex items-center gap-1.5">
                    <UserPlus class="w-3.5 h-3.5" /> Nouvel invité / enfant
                </h5>
                <button
                    type="button"
                    class="text-xs text-blue-700 hover:underline font-medium"
                    @click="showNewAttendeeForm = false"
                >
                    Annuler
                </button>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <Input
                        v-model="attendeeForm.first_name"
                        placeholder="Prénom"
                        class="bg-white text-xs h-9 border-gray-200"
                        @change="attendeeForm.validate('first_name')"
                    />
                    <p v-if="attendeeForm.invalid('first_name')" class="text-xs text-red-600 mt-0.5">
                        {{ attendeeForm.errors.first_name }}
                    </p>
                </div>
                <div>
                    <Input
                        v-model="attendeeForm.last_name"
                        placeholder="Nom"
                        class="bg-white text-xs h-9 border-gray-200"
                        @change="attendeeForm.validate('last_name')"
                    />
                    <p v-if="attendeeForm.invalid('last_name')" class="text-xs text-red-600 mt-0.5">
                        {{ attendeeForm.errors.last_name }}
                    </p>
                </div>
            </div>
            <div>
                <Input
                    v-model="attendeeForm.birthday"
                    type="date"
                    class="bg-white text-xs h-9 border-gray-200"
                    @change="attendeeForm.validate('birthday')"
                />
                <p v-if="attendeeForm.invalid('birthday')" class="text-xs text-red-600 mt-0.5">
                    {{ attendeeForm.errors.birthday }}
                </p>
            </div>

            <Button
                type="button"
                size="sm"
                class="w-full bg-blue-600 hover:bg-blue-500 text-white text-xs h-8 font-medium shadow-xs"
                :disabled="attendeeForm.processing"
                @click="submitNewAttendee"
            >
                {{ attendeeForm.processing ? 'Création...' : 'Enregistrer et ajouter' }}
            </Button>
        </div>

        <!-- 3. Bouton "+ Choisir / Ajouter un participant" avec ScrollArea -->
        <div v-if="!showNewAttendeeForm && !isCapacityReached && (isUserAvailable || availableAttendees.length > 0 || mode === 'regular')">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        type="button"
                        variant="outline"
                        class="w-full text-xs sm:text-sm font-medium text-gray-700 border-dashed border-gray-300 hover:border-gray-400 hover:bg-gray-50 justify-center h-9"
                    >
                        <Plus class="w-4 h-4 mr-1.5 text-gray-500" />
                        <span>{{ mode === 'makeup' ? 'Choisir la personne' : 'Ajouter un participant' }}</span>
                    </Button>
                </DropdownMenuTrigger>

               <DropdownMenuContent
    align="center"
    class="w-72 bg-white border-gray-200 p-1 overflow-hidden"
>
    <ScrollArea class="h-64">
        <div class="p-1">
            <!-- Moi-même -->
            <template v-if="isUserAvailable && currentUser">
                <DropdownMenuItem
                    :disabled="
                        getCandidateStatus(
                            'App\\Models\\User',
                            currentUser.id
                        ).disabled
                    "
                    class="text-xs sm:text-sm cursor-pointer flex flex-col items-start py-2.5 px-2.5 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    @click="
                        addParticipant(
                            'App\\Models\\User',
                            currentUser.id,
                            `${currentUser.first_name} ${currentUser.last_name}`,
                            'Moi-même'
                        )
                    "
                >
                    <div class="flex items-center gap-2 font-medium">
                        <User class="w-4 h-4 text-gray-500 shrink-0" />

                        <span class="truncate">
                            Moi-même ({{ currentUser.first_name }})
                        </span>
                    </div>

                    <span
                        v-if="
                            getCandidateStatus(
                                'App\\Models\\User',
                                currentUser.id
                            ).disabled
                        "
                        class="text-[10px] text-amber-700 mt-0.5 pl-6"
                    >
                        {{
                            getCandidateStatus(
                                "App\\Models\\User",
                                currentUser.id
                            ).reason
                        }}
                    </span>
                </DropdownMenuItem>

                <DropdownMenuSeparator
                    v-if="availableAttendees.length > 0"
                />
            </template>

            <!-- Invités -->
            <template v-if="availableAttendees.length > 0">
                <DropdownMenuLabel
                    class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2.5 py-1.5"
                >
                    Mes invités & enfants
                </DropdownMenuLabel>

                <DropdownMenuItem
                    v-for="att in availableAttendees"
                    :key="att.id"
                    :disabled="
                        getCandidateStatus(
                            'App\\Models\\Attendee',
                            att.id
                        ).disabled
                    "
                    class="text-xs sm:text-sm cursor-pointer flex flex-col items-start py-2.5 px-2.5 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    @click="
                        addParticipant(
                            'App\\Models\\Attendee',
                            att.id,
                            `${att.first_name} ${att.last_name}`,
                            'Invité / Enfant'
                        )
                    "
                >
                    <div class="flex items-center gap-2 font-medium w-full">
                        <Users
                            class="w-4 h-4 text-gray-500 shrink-0"
                        />

                        <span class="truncate">
                            {{ att.first_name }} {{ att.last_name }}
                        </span>
                    </div>

                    <span
                        v-if="
                            getCandidateStatus(
                                'App\\Models\\Attendee',
                                att.id
                            ).disabled
                        "
                        class="text-[10px] text-amber-700 mt-0.5 pl-6"
                    >
                        {{
                            getCandidateStatus(
                                "App\\Models\\Attendee",
                                att.id
                            ).reason
                        }}
                    </span>
                </DropdownMenuItem>
            </template>

            <!-- Rien de sélectionnable -->
            <div
                v-if="
                    !isUserAvailable &&
                    availableAttendees.length === 0
                "
                class="px-3 py-5 text-center text-xs text-gray-400"
            >
                Aucun participant disponible.
            </div>
        </div>
    </ScrollArea>

    <!-- Création d'invité reste fixe en bas -->
    <template v-if="mode === 'regular'">
        <DropdownMenuSeparator />

        <DropdownMenuItem
            class="text-xs sm:text-sm cursor-pointer text-blue-600 focus:text-blue-700 flex items-center gap-2 font-medium py-2.5 px-2.5 hover:bg-blue-50"
            @click="showNewAttendeeForm = true"
        >
            <UserPlus class="w-4 h-4 text-blue-600" />

            <span>Ajouter un nouvel invité</span>
        </DropdownMenuItem>
    </template>
</DropdownMenuContent>
            </DropdownMenu>
        </div>

        <p v-if="errors.participants || errors.participant_id" class="text-xs text-red-600 font-medium">
            {{ errors.participants || errors.participant_id }}
        </p>
    </div>
</template>
