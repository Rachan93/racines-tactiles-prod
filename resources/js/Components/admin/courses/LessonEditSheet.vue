<script setup>
import { ref, watch, computed } from "vue";
import { useForm } from "laravel-precognition-vue-inertia";
import { router } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { pluralize, formatPrice } from "@/Utils/formatters";

// Composants Shadcn UI
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
    SheetFooter,
} from "@/Components/ui/sheet";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Switch } from "@/Components/ui/switch";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import {
    Shell,
    Hand,
    Clock,
    Euro,
    XCircle,
    RotateCcw,
    Loader2,
    Save,
    Calendar,
    User,
    SlidersHorizontal,
    Trash2,
    AlertTriangle,
    HelpCircle,
} from "lucide-vue-next";

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    lesson: {
        type: Object,
        default: null,
    },
    course: {
        type: Object,
        default: null,
    },
    instructors: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["update:open", "saved", "deleted"]);

// Dialogue de confirmation de suppression
const isDeleteDialogOpen = ref(false);
const isDeleting = ref(false);

// Formulaire Precognition
const form = useForm(
    "patch",
    () => (props.lesson ? route("lessons.update", { lesson: props.lesson.id }) : ""),
    {
        is_overridden: false,
        is_cancelled: false,
        cancellation_reason: "",
        date: "",
        override_start_time: "14:00",
        override_end_time: "16:30",
        override_instructor_id: "",
        override_spots_max_wheel: 8,
        override_spots_max_handbuilding: 2,
        override_price: "45.00",
    }
);

// Synchronisation des valeurs à chaque ouverture
const populateForm = (newLesson) => {
    if (!newLesson) return;

    form.is_overridden = Boolean(newLesson.is_overridden);
    form.is_cancelled = Boolean(newLesson.is_cancelled);
    form.cancellation_reason = newLesson.cancellation_reason || "";
    form.date = newLesson.date || "";
    form.override_start_time = newLesson.start_time || props.course?.default_start_time || "14:00";
    form.override_end_time = newLesson.end_time || props.course?.default_end_time || "16:30";
    form.override_instructor_id = newLesson.instructor?.id
        ? String(newLesson.instructor.id)
        : (props.course?.instructor?.id ? String(props.course.instructor.id) : "");
    form.override_spots_max_wheel = newLesson.spots?.wheel_max ?? props.course?.default_spots_max_wheel ?? 8;
    form.override_spots_max_handbuilding = newLesson.spots?.handbuilding_max ?? props.course?.default_spots_max_handbuilding ?? 2;
    form.override_price = String(newLesson.price ?? props.course?.default_price ?? "45.00");

    form.clearErrors();
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && props.lesson) {
            populateForm(props.lesson);
        }
    },
    { immediate: true }
);

watch(
    () => props.lesson,
    (newLesson) => {
        if (newLesson && props.open) {
            populateForm(newLesson);
        }
    },
    { deep: true }
);

// Effectifs inscrits réels
const bookedWheel = computed(() => props.lesson?.spots?.wheel_booked || 0);
const bookedHand = computed(() => props.lesson?.spots?.handbuilding_booked || 0);
const totalBooked = computed(() => props.lesson?.spots?.total_booked || 0);

// Règle métier : suppression uniquement si 0 inscrit
const canDelete = computed(() => totalBooked.value === 0);

// Validation réactive des capacités face aux inscrits
const hasWheelCapacityError = computed(() => {
    return form.is_overridden && Number(form.override_spots_max_wheel) < bookedWheel.value;
});

const hasHandCapacityError = computed(() => {
    return form.is_overridden && Number(form.override_spots_max_handbuilding) < bookedHand.value;
});

// Palette de couleur du type de cours
const getTypeBadgeClass = (typeName) => {
    const name = (typeName || "").toLowerCase();
    if (name.includes("collectif")) {
        return "bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800";
    }
    if (name.includes("stage")) {
        return "bg-amber-50 text-amber-900 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800";
    }
    if (name.includes("privé") || name.includes("prive")) {
        return "bg-indigo-50 text-indigo-800 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800";
    }
    return "bg-secondary text-secondary-foreground border-transparent";
};

// Rétablir les paramètres par défaut du cours
const handleResetToDefault = () => {
    if (!props.course) return;

    form.is_overridden = false;
    form.is_cancelled = false;
    form.cancellation_reason = "";
    form.date = props.lesson?.date || "";
    form.override_start_time = props.course.default_start_time;
    form.override_end_time = props.course.default_end_time;
    form.override_instructor_id = props.course.instructor?.id ? String(props.course.instructor.id) : "";
    form.override_spots_max_wheel = props.course.default_spots_max_wheel;
    form.override_spots_max_handbuilding = props.course.default_spots_max_handbuilding;
    form.override_price = String(props.course.default_price);
};

const close = () => {
    emit("update:open", false);
};

// Enregistrement de la séance
const submit = () => {
    if (!props.lesson) return;

    form.submit({
        preserveScroll: true,
        onSuccess: () => {
            close();
            emit("saved");
            toast.success("Séance mise à jour", {
                description: `La séance du ${props.lesson.date_formatted} a été modifiée.`,
            });
        },
        onError: () => {
            toast.error("Erreur de validation", {
                description: "Veuillez vérifier les informations renseignées.",
            });
        },
    });
};

// Suppression de la séance (uniquement 0 inscrit)
const executeDelete = () => {
    if (!props.lesson || !canDelete.value) return;

    isDeleting.value = true;
    router.delete(route("lessons.delete", { lesson: props.lesson.id }), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            close();
            emit("deleted");
            toast.success("Séance supprimée", {
                description: `La séance du ${props.lesson.date_formatted} a bien été supprimée.`,
            });
        },
        onError: () => {
            toast.error("Erreur de suppression", {
                description: "Impossible de supprimer cette séance.",
            });
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};
</script>

<template>
    <Sheet :open="open" @update:open="(val) => emit('update:open', val)">
        <!-- Animation slide native et fluide via SheetContent -->
        <SheetContent
            side="right"
            class="w-full sm:max-w-xl p-0 flex flex-col h-full bg-background border-l shadow-2xl"
        >
            <!-- En-tête -->
            <SheetHeader class="p-6 border-b shrink-0 bg-muted/20 pr-12">
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <SheetTitle class="text-lg font-bold">Édition de la séance</SheetTitle>
                        <Badge variant="outline" :class="getTypeBadgeClass(course?.type?.name)" class="text-xs font-semibold">
                            {{ course?.type?.name }}
                        </Badge>
                        <Badge v-if="form.is_overridden" variant="outline" class="text-[10px] bg-primary/10 text-primary border-primary/30 font-medium">
                            Personnalisée
                        </Badge>
                        <Badge v-if="form.is_cancelled" variant="destructive" class="text-[10px] font-semibold">
                            Annulée
                        </Badge>
                    </div>
                    <SheetDescription class="text-xs">
                        {{ course?.name }} • {{ lesson?.date_formatted }}
                    </SheetDescription>
                </div>

                <!-- Bandeau Inscrits Réels -->
                <div class="mt-3 p-3 rounded-lg border bg-background flex items-center justify-between gap-3 text-xs shadow-2xs">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-foreground">
                            {{ pluralize(totalBooked, 'élève inscrit') }} :
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-200 text-[11px] font-medium dark:bg-sky-950/40 dark:text-sky-300">
                            <Shell class="h-3 w-3" /> {{ pluralize(bookedWheel, 'Tour', 'Tours') }}
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-orange-50 text-orange-800 border border-orange-200 text-[11px] font-medium dark:bg-orange-950/40 dark:text-orange-300">
                            <Hand class="h-3 w-3" /> {{ pluralize(bookedHand, 'Modelage', 'Modelage') }}
                        </span>
                    </div>

                    <span class="text-[11px] font-medium text-muted-foreground">
                        {{ formatPrice(lesson?.price || course?.default_price || 0) }}
                    </span>
                </div>
            </SheetHeader>

            <!-- Formulaire Scrollable (Tous les champs visibles directement) -->
            <form @submit.prevent="submit" class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- ========================================================= -->
                <!-- 1. BLOC PERSONNALISATION (OVERRIDE) - TOUJOURS VISIBLE   -->
                <!-- ========================================================= -->
                <div
                    class="rounded-xl border p-4.5 space-y-4 transition-all duration-200"
                    :class="form.is_overridden ? 'border-primary/40 bg-primary/5 shadow-xs' : 'border-border bg-card'"
                >
                    <!-- En-tête du bloc avec Switch Personnalisée -->
                    <div class="flex items-center justify-between gap-3 pb-3 border-b border-border/60">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="h-7 w-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <SlidersHorizontal class="h-3.5 w-3.5" />
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-foreground uppercase tracking-wider truncate">
                                    Paramètres spécifiques à cette séance
                                </h4>
                            </div>
                        </div>

                        <!-- Switch Personnalisée -->
                        <div class="flex items-center gap-2">
                            <Label for="override_active" class="text-xs font-semibold cursor-pointer select-none">
                                Personnalisée
                            </Label>
                            <Switch
                                id="override_active"
                                :checked="Boolean(form.is_overridden)"
                                :model-value="Boolean(form.is_overridden)"
                                @update:checked="(val) => { form.is_overridden = Boolean(val); form.validate('is_overridden'); }"
                                @update:model-value="(val) => { form.is_overridden = Boolean(val); form.validate('is_overridden'); }"
                            />
                        </div>
                    </div>

                    <!-- Date & Horaires -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="space-y-1.5">
                            <Label for="lesson_date" class="text-xs font-semibold">Date de la séance</Label>
                            <Input
                                id="lesson_date"
                                type="date"
                                v-model="form.date"
                                class="bg-background text-xs"
                                :class="{ 'border-destructive': form.errors.date }"
                                @change="form.validate('date')"
                            />
                            <p v-if="form.errors.date" class="text-[10px] text-destructive mt-1">{{ form.errors.date }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="start_time" class="text-xs font-semibold">Heure début</Label>
                            <Input
                                id="start_time"
                                type="time"
                                v-model="form.override_start_time"
                                class="bg-background text-xs"
                                :class="{ 'border-destructive': form.errors.override_start_time }"
                                @change="form.validate('override_start_time')"
                            />
                            <p v-if="form.errors.override_start_time" class="text-[10px] text-destructive mt-1">{{ form.errors.override_start_time }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="end_time" class="text-xs font-semibold">Heure fin</Label>
                            <Input
                                id="end_time"
                                type="time"
                                v-model="form.override_end_time"
                                class="bg-background text-xs"
                                :class="{ 'border-destructive': form.errors.override_end_time }"
                                @change="form.validate('override_end_time')"
                            />
                            <p v-if="form.errors.override_end_time" class="text-[10px] text-destructive mt-1">{{ form.errors.override_end_time }}</p>
                        </div>
                    </div>

                    <!-- Professeur pour cette séance -->
                    <div class="space-y-1.5">
                        <Label for="override_instructor" class="text-xs font-semibold">
                            Professeur / Intervenant
                        </Label>
                        <Select
                            :model-value="form.override_instructor_id"
                            @update:model-value="(val) => { form.override_instructor_id = String(val); form.validate('override_instructor_id'); }"
                        >
                            <SelectTrigger id="override_instructor" class="bg-background text-xs" :class="{ 'border-destructive': form.errors.override_instructor_id }">
                                <SelectValue placeholder="Choisir le professeur" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="inst in instructors"
                                    :key="inst.id"
                                    :value="String(inst.id)"
                                    class="text-xs"
                                >
                                    {{ inst.first_name }} {{ inst.last_name }}
                                    <span v-if="props.course?.instructor?.id === inst.id" class="text-xs text-muted-foreground ml-1">
                                        (Défaut du cours)
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.override_instructor_id" class="text-[10px] text-destructive mt-1">{{ form.errors.override_instructor_id }}</p>
                    </div>

                    <!-- Capacités & Tarif -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Tours (Shell) -->
                        <div class="p-2.5 rounded-lg bg-background border space-y-1.5">
                            <Label for="wheel_spots" class="flex items-center justify-between text-xs font-semibold text-sky-800 dark:text-sky-300">
                                <span class="flex items-center gap-1">
                                    <Shell class="h-3.5 w-3.5 text-sky-600" /> Tours max
                                </span>
                                <span class="text-[10px] text-muted-foreground font-normal">Min: {{ bookedWheel }}</span>
                            </Label>
                            <Input
                                id="wheel_spots"
                                type="number"
                                :min="bookedWheel"
                                max="50"
                                v-model.number="form.override_spots_max_wheel"
                                class="text-xs"
                                :class="{ 'border-destructive': form.errors.override_spots_max_wheel || hasWheelCapacityError }"
                                @change="form.validate('override_spots_max_wheel')"
                            />
                            <p v-if="form.errors.override_spots_max_wheel" class="text-[10px] text-destructive mt-1 leading-tight">
                                {{ form.errors.override_spots_max_wheel }}
                            </p>
                        </div>

                        <!-- Modelage (Hand) -->
                        <div class="p-2.5 rounded-lg bg-background border space-y-1.5">
                            <Label for="hand_spots" class="flex items-center justify-between text-xs font-semibold text-orange-800 dark:text-orange-300">
                                <span class="flex items-center gap-1">
                                    <Hand class="h-3.5 w-3.5 text-orange-600" /> Modelage
                                </span>
                                <span class="text-[10px] text-muted-foreground font-normal">Min: {{ bookedHand }}</span>
                            </Label>
                            <Input
                                id="hand_spots"
                                type="number"
                                :min="bookedHand"
                                max="50"
                                v-model.number="form.override_spots_max_handbuilding"
                                class="text-xs"
                                :class="{ 'border-destructive': form.errors.override_spots_max_handbuilding || hasHandCapacityError }"
                                @change="form.validate('override_spots_max_handbuilding')"
                            />
                            <p v-if="form.errors.override_spots_max_handbuilding" class="text-[10px] text-destructive mt-1 leading-tight">
                                {{ form.errors.override_spots_max_handbuilding }}
                            </p>
                        </div>

                        <!-- Tarif exceptionnel -->
                        <div class="p-2.5 rounded-lg bg-background border space-y-1.5">
                            <Label for="lesson_price" class="flex items-center gap-1 text-xs font-semibold">
                                <Euro class="h-3.5 w-3.5 text-muted-foreground" /> Prix / séance
                            </Label>
                            <Input
                                id="lesson_price"
                                type="number"
                                step="0.01"
                                min="0"
                                v-model="form.override_price"
                                class="text-xs"
                                :class="{ 'border-destructive': form.errors.override_price }"
                                @change="form.validate('override_price')"
                            />
                            <p v-if="form.errors.override_price" class="text-[10px] text-destructive mt-1">{{ form.errors.override_price }}</p>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- 2. BLOC ANNULATION EXCEPTIONNELLE - TOUJOURS VISIBLE      -->
                <!-- ========================================================= -->
                <div
                    class="rounded-xl border p-4.5 space-y-3.5 transition-all duration-200"
                    :class="form.is_cancelled ? 'border-destructive/40 bg-destructive/5 shadow-xs' : 'border-border bg-card'"
                >
                    <!-- En-tête avec Switch Annulée -->
                    <div class="flex items-center justify-between gap-3 pb-3 border-b border-border/60">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="h-7 w-7 rounded-lg bg-destructive/10 text-destructive flex items-center justify-center shrink-0">
                                <XCircle class="h-3.5 w-3.5" />
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-destructive uppercase tracking-wider truncate">
                                    Annulation de la séance
                                </h4>
                            </div>
                        </div>

                        <!-- Switch Annulée -->
                        <div class="flex items-center gap-2">
                            <Label for="cancel_active" class="text-xs font-semibold text-destructive cursor-pointer select-none">
                                Annulée
                            </Label>
                            <Switch
                                id="cancel_active"
                                :checked="Boolean(form.is_cancelled)"
                                :model-value="Boolean(form.is_cancelled)"
                                @update:checked="(val) => { form.is_cancelled = Boolean(val); form.validate('is_cancelled'); }"
                                @update:model-value="(val) => { form.is_cancelled = Boolean(val); form.validate('is_cancelled'); }"
                            />
                        </div>
                    </div>

                    <!-- Motif d'annulation -->
                    <div class="space-y-1.5">
                        <Label for="cancel_reason" class="text-xs font-semibold" :class="form.is_cancelled ? 'text-destructive' : 'text-foreground'">
                            Motif communiqué aux élèves <span v-if="form.is_cancelled" class="text-destructive">*</span>
                        </Label>
                        <textarea
                            id="cancel_reason"
                            v-model="form.cancellation_reason"
                            rows="2"
                            placeholder="Ex: Panne temporaire du four de cuisson, imprévu d'atelier..."
                            class="w-full rounded-md border bg-background px-3 py-2 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1"
                            :class="form.is_cancelled ? 'border-destructive/40 focus:ring-destructive' : 'border-input focus:ring-ring'"
                            @change="form.validate('cancellation_reason')"
                        />
                        <p v-if="form.errors.cancellation_reason" class="text-[10px] text-destructive mt-1 font-medium">
                            {{ form.errors.cancellation_reason }}
                        </p>
                    </div>
                </div>
            </form>

            <!-- Pied de page -->
            <SheetFooter class="p-4 border-t shrink-0 bg-muted/10 flex flex-row items-center justify-between w-full gap-3">
                <!-- En bas à gauche : Supprimer la séance (Règle Métier 0 inscrit) -->
                <div class="flex items-center gap-2">
                    <TooltipProvider v-if="!canDelete">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <span>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        disabled
                                        class="h-8 text-xs text-muted-foreground gap-1.5 opacity-50 cursor-not-allowed"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                        <span>Supprimer</span>
                                    </Button>
                                </span>
                            </TooltipTrigger>
                            <TooltipContent side="top">
                                <p class="text-xs max-w-xs">
                                    Impossible de supprimer : {{ pluralize(totalBooked, 'élève est inscrit', 'élèves sont inscrits') }}. Utilisez plutôt l'annulation.
                                </p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>

                    <Button
                        v-else
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-8 text-xs text-destructive hover:bg-destructive/10 hover:text-destructive gap-1.5"
                        @click="isDeleteDialogOpen = true"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        <span>Supprimer la séance</span>
                    </Button>

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-8 text-xs text-muted-foreground hover:text-foreground gap-1.5 hidden sm:inline-flex"
                        :disabled="form.processing || (!form.is_overridden && !form.is_cancelled)"
                        @click="handleResetToDefault"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        <span>Rétablir défauts</span>
                    </Button>
                </div>

                <!-- En bas à droite : Annuler / Enregistrer -->
                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="close"
                    >
                        Fermer
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        class="gap-1.5 font-semibold"
                        :disabled="form.processing || hasWheelCapacityError || hasHandCapacityError"
                        @click="submit"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        <span>Enregistrer</span>
                    </Button>
                </div>
            </SheetFooter>
        </SheetContent>
    </Sheet>

    <!-- Dialogue de confirmation pour la suppression d'une séance -->
    <Dialog :open="isDeleteDialogOpen" @update:open="(val) => (isDeleteDialogOpen = val)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-destructive">
                    <AlertTriangle class="h-5 w-5" />
                    <span>Supprimer cette séance ?</span>
                </DialogTitle>
                <DialogDescription class="text-xs pt-2">
                    Êtes-vous sûr de vouloir supprimer définitivement la séance du
                    <strong class="text-foreground">{{ lesson?.date_formatted }}</strong> ?
                    Cette action est irréversible (aucun élève n'y est actuellement inscrit).
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2 sm:gap-0 pt-4">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="isDeleteDialogOpen = false"
                >
                    Annuler
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    size="sm"
                    :disabled="isDeleting"
                    class="gap-1.5"
                    @click="executeDelete"
                >
                    <Loader2 v-if="isDeleting" class="h-4 w-4 animate-spin" />
                    <Trash2 v-else class="h-4 w-4" />
                    <span>Confirmer la suppression</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
