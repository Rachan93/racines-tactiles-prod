<script setup>
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { useForm } from "laravel-precognition-vue-inertia";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
    Users,
    UserPlus,
    Pencil,
    Trash2,
    Calendar,
    AlertTriangle,
    AlertCircle,
} from "lucide-vue-next";
import { formatDate } from "@/Utils/formatters";

const props = defineProps({
    attendees: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const attendeeError = computed(() => page.props.errors?.attendee);

// Modales d'état
const isCreateOpen = ref(false);
const editingAttendee = ref(null);
const attendeeToDelete = ref(null);
const isDeleting = ref(false);

// 1. Formulaire de Création Precognition
const createForm = useForm("post", route("member.attendees.store"), {
    first_name: "",
    last_name: "",
    birthday: "",
});

// 2. Formulaire de Mise à Jour Precognition avec closure dynamique pour l'URL PUT
const updateForm = useForm(
    "put",
    () =>
        editingAttendee.value
            ? route("member.attendees.update", editingAttendee.value.id)
            : "",
    {
        first_name: "",
        last_name: "",
        birthday: "",
    },
);

// Calcul de l'âge
const calculateAge = (birthday) => {
    if (!birthday) return null;
    const birth = new Date(birthday);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age > 0 ? `${age} ans` : "< 1 an";
};

// Actions Création
const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    isCreateOpen.value = true;
};

const submitCreate = () => {
    createForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
        },
    });
};

// Actions Édition
const openEditModal = (attendee) => {
    editingAttendee.value = attendee;
    updateForm.first_name = attendee.first_name || "";
    updateForm.last_name = attendee.last_name || "";
    updateForm.birthday = attendee.birthday
        ? String(attendee.birthday).split("T")[0]
        : "";
    updateForm.clearErrors();
};

const submitUpdate = () => {
    updateForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            editingAttendee.value = null;
            updateForm.reset();
        },
    });
};

// Actions Suppression
const openDeleteModal = (attendee) => {
    attendeeToDelete.value = attendee;
};

const confirmDelete = () => {
    if (!attendeeToDelete.value) return;

    isDeleting.value = true;
    router.delete(
        route("member.attendees.destroy", attendeeToDelete.value.id),
        {
            preserveScroll: true,
            onFinish: () => {
                isDeleting.value = false;
                attendeeToDelete.value = null;
            },
        },
    );
};
</script>

<template>
    <div class="space-y-6">
        <!-- En-tête : Titre & Bouton d'ajout -->
        <div
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
        >
            <div>
                <h3
                    class="text-lg font-semibold text-gray-900 flex items-center gap-2"
                >
                    <Users class="w-5 h-5 text-gray-900" />
                    Invités
                </h3>
                <p class="text-sm text-gray-500 mt-0.5">
                    Gérez les participants pour lesquels vous pouvez réserver
                    des modules ou placer des rattrapages.
                </p>
            </div>

            <Button
                size="sm"
                class="text-sm bg-gray-900 hover:bg-gray-700 text-white shrink-0 h-9 px-3.5 shadow-xs transition"
                @click="openCreateModal"
            >
                <UserPlus class="w-4 h-4 mr-1.5" />
                Ajouter un invité
            </Button>
        </div>

        <!-- Alerte d'erreur inline (ex: refus de suppression si réservations en cours) -->
        <div
            v-if="attendeeError"
            class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm flex items-center gap-2.5 font-medium shadow-xs"
        >
            <AlertCircle class="w-4 h-4 text-red-600 shrink-0" />
            <span>{{ attendeeError }}</span>
        </div>

        <!-- État vide -->
        <div
            v-if="attendees.length === 0"
            class="p-8 text-center bg-gray-50 border border-dashed border-gray-300 rounded-xl space-y-2"
        >
            <Users class="w-8 h-8 mx-auto text-gray-400" />
            <h4 class="text-base font-semibold text-gray-700">
                Aucun proche enregistré
            </h4>
            <p class="text-sm text-gray-500 max-w-sm mx-auto">
                Vous n'avez pas encore ajouté de proche ou d'enfant à votre
                compte pour réserver à plusieurs.
            </p>
        </div>

        <!-- Grille des membres -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
                v-for="attendee in attendees"
                :key="attendee.id"
                class="p-4 sm:p-5 bg-gray-50 border border-gray-300 rounded-xl shadow flex items-center justify-between gap-4 hover:border-gray-400 hover:shadow-md transition"
            >
                <div class="flex items-center gap-3.5 min-w-0">
                    <div
                        class="w-11 h-11 rounded-full bg-sage-light border border-sage-border flex items-center justify-center shrink-0 text-sage-dark font-bold text-sm"
                    >
                        {{ attendee.first_name?.[0]
                        }}{{ attendee.last_name?.[0] }}
                    </div>
                    <div class="truncate">
                        <h4
                            class="text-base font-semibold text-gray-900 truncate"
                        >
                            {{ attendee.first_name }} {{ attendee.last_name }}
                        </h4>
                        <div
                            class="flex items-center gap-2 text-sm text-gray-600 mt-0.5"
                        >
                            <span
                                v-if="attendee.birthday"
                                class="flex items-center gap-1.5"
                            >
                                <Calendar class="w-3.5 h-3.5 text-gray-500" />
                                {{ formatDate(attendee.birthday) }}
                                <span class="text-gray-500"
                                    >({{
                                        calculateAge(attendee.birthday)
                                    }})</span
                                >
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-1 shrink-0">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 text-gray-500 hover:text-gray-900 hover:bg-gray-200"
                        title="Modifier"
                        @click="openEditModal(attendee)"
                    >
                        <Pencil class="w-4 h-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 text-gray-500 hover:text-red-600 hover:bg-red-100"
                        title="Supprimer"
                        @click="openDeleteModal(attendee)"
                    >
                        <Trash2 class="w-4 h-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- ================================================= -->
        <!-- MODALE 1 : CRÉATION D'UN INVITÉ                   -->
        <!-- ================================================= -->
        <Dialog
            :open="isCreateOpen"
            @update:open="(val) => (isCreateOpen = val)"
        >
            <DialogContent
                class="sm:max-w-md bg-white border-gray-200 font-brand"
            >
                <DialogHeader>
                    <DialogTitle
                        class="text-lg font-semibold text-gray-900 flex items-center gap-2"
                    >
                        <UserPlus class="w-5 h-5 text-gray-900 shrink-0" />
                        Ajouter un invité
                    </DialogTitle>
                    <DialogDescription class="text-sm text-gray-500">
                        Renseignez les informations de la personne à rattacher à
                        votre compte.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCreate" class="space-y-4 py-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-sm font-medium text-gray-700"
                                >Prénom *</Label
                            >
                            <Input
                                v-model="createForm.first_name"
                                placeholder="Ex: Lucas"
                                class="text-sm h-10 border-gray-200"
                                @change="createForm.validate('first_name')"
                            />
                            <p
                                v-if="createForm.invalid('first_name')"
                                class="text-xs text-red-600 font-medium"
                            >
                                {{ createForm.errors.first_name }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-sm font-medium text-gray-700"
                                >Nom *</Label
                            >
                            <Input
                                v-model="createForm.last_name"
                                placeholder="Ex: Dupont"
                                class="text-sm h-10 border-gray-200"
                                @change="createForm.validate('last_name')"
                            />
                            <p
                                v-if="createForm.invalid('last_name')"
                                class="text-xs text-red-600 font-medium"
                            >
                                {{ createForm.errors.last_name }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-sm font-medium text-gray-700"
                            >Date de naissance</Label
                        >
                        <Input
                            v-model="createForm.birthday"
                            type="date"
                            class="text-sm h-10 border-gray-200"
                            @change="createForm.validate('birthday')"
                        />
                        <p
                            v-if="createForm.invalid('birthday')"
                            class="text-xs text-red-600 font-medium"
                        >
                            {{ createForm.errors.birthday }}
                        </p>
                    </div>

                    <DialogFooter class="flex flex-col sm:flex-row gap-2 pt-3">
                        <Button
                            type="submit"
                            size="sm"
                            class="text-sm h-9 bg-gray-900 hover:bg-gray-700 text-white"
                            :disabled="createForm.processing"
                        >
                            {{
                                createForm.processing
                                    ? "Enregistrement..."
                                    : "Ajouter le membre"
                            }}
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="text-sm h-9 text-gray-600 hover:text-gray-900"
                            @click="isCreateOpen = false"
                        >
                            Annuler
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ================================================= -->
        <!-- MODALE 2 : MODIFICATION D'UN INVITÉ               -->
        <!-- ================================================= -->
        <Dialog
            :open="!!editingAttendee"
            @update:open="(val) => !val && (editingAttendee = null)"
        >
            <DialogContent
                class="sm:max-w-md bg-white border-gray-200 font-brand"
            >
                <DialogHeader v-if="editingAttendee">
                    <DialogTitle
                        class="text-lg font-semibold text-gray-900 flex items-center gap-2"
                    >
                        <Pencil class="w-4 h-4 text-gray-900 shrink-0" />
                        Modifier {{ editingAttendee.first_name }}
                    </DialogTitle>
                    <DialogDescription class="text-sm text-gray-500">
                        Mettez à jour les informations du profil.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitUpdate" class="space-y-4 py-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label class="text-sm font-medium text-gray-700"
                                >Prénom *</Label
                            >
                            <Input
                                v-model="updateForm.first_name"
                                class="text-sm h-10 border-gray-200"
                                @change="updateForm.validate('first_name')"
                            />
                            <p
                                v-if="updateForm.invalid('first_name')"
                                class="text-xs text-red-600 font-medium"
                            >
                                {{ updateForm.errors.first_name }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-sm font-medium text-gray-700"
                                >Nom *</Label
                            >
                            <Input
                                v-model="updateForm.last_name"
                                class="text-sm h-10 border-gray-200"
                                @change="updateForm.validate('last_name')"
                            />
                            <p
                                v-if="updateForm.invalid('last_name')"
                                class="text-xs text-red-600 font-medium"
                            >
                                {{ updateForm.errors.last_name }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-sm font-medium text-gray-700"
                            >Date de naissance</Label
                        >
                        <Input
                            v-model="updateForm.birthday"
                            type="date"
                            class="text-sm h-10 border-gray-200"
                            @change="updateForm.validate('birthday')"
                        />
                        <p
                            v-if="updateForm.invalid('birthday')"
                            class="text-xs text-red-600 font-medium"
                        >
                            {{ updateForm.errors.birthday }}
                        </p>
                    </div>

                    <DialogFooter class="flex flex-col sm:flex-row gap-2 pt-3">
                        <Button
                            type="submit"
                            size="sm"
                            class="text-sm h-9 bg-gray-900 hover:bg-gray-700 text-white"
                            :disabled="updateForm.processing"
                        >
                            {{
                                updateForm.processing
                                    ? "Mise à jour..."
                                    : "Sauvegarder"
                            }}
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="text-sm h-9 text-gray-600 hover:text-gray-900"
                            @click="editingAttendee = null"
                        >
                            Annuler
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ================================================= -->
        <!-- MODALE 3 : CONFIRMATION DE SUPPRESSION            -->
        <!-- ================================================= -->
        <Dialog
            :open="!!attendeeToDelete"
            @update:open="(val) => !val && (attendeeToDelete = null)"
        >
            <DialogContent
                class="sm:max-w-md bg-white border-gray-200 font-brand"
            >
                <DialogHeader v-if="attendeeToDelete">
                    <DialogTitle
                        class="text-lg font-semibold text-gray-900 flex items-center gap-2"
                    >
                        <AlertTriangle class="w-5 h-5 text-red-600 shrink-0" />
                        Supprimer {{ attendeeToDelete.first_name }}
                        {{ attendeeToDelete.last_name }}
                    </DialogTitle>
                    <DialogDescription class="text-sm text-gray-500">
                        Cette action retirera ce profil de votre compte.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="attendeeToDelete"
                    class="space-y-3.5 py-2 text-sm text-gray-600"
                >
                    <p>
                        Êtes-vous sûr de vouloir supprimer définitivement le
                        profil de
                        <strong class="text-gray-900">{{
                            attendeeToDelete.first_name
                        }}</strong>
                        ?
                    </p>
                    <div
                        class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-xs sm:text-sm"
                    >
                        <p class="font-semibold">Vérification de sécurité :</p>
                        <p class="mt-0.5">
                            La suppression sera refusée si cet invité a des
                            séances réservées à venir ou un historique de cours.
                        </p>
                    </div>
                </div>

                <DialogFooter class="flex flex-col sm:flex-row gap-2 pt-2">
                    <Button
                        variant="destructive"
                        size="sm"
                        class="text-sm h-9 bg-red-600 text-white hover:bg-red-700"
                        :disabled="isDeleting"
                        @click="confirmDelete"
                    >
                        {{
                            isDeleting
                                ? "Suppression..."
                                : "Confirmer la suppression"
                        }}
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-sm h-9 text-gray-600 hover:text-gray-900"
                        :disabled="isDeleting"
                        @click="attendeeToDelete = null"
                    >
                        Annuler
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
