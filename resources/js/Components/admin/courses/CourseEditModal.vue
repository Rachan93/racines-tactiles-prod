<script setup>
import { ref, watch, computed } from "vue";
import { useForm } from "laravel-precognition-vue-inertia";
import { toast } from "vue-sonner";
import { pluralize, formatPrice, formatDateRange } from "@/Utils/formatters";

// Composants Shadcn UI
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
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/Components/ui/tabs";
import { Switch } from "@/Components/ui/switch";
import { Checkbox } from "@/Components/ui/checkbox";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { ScrollArea } from "@/Components/ui/scroll-area";
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
    Loader2,
    Save,
    Calendar,
    AlertTriangle,
    FileText,
    Layers,
    HelpCircle,
    NotepadText,
    User,
} from "lucide-vue-next";

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
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

const emit = defineEmits(["update:open", "saved"]);

// Options de l'enum sub_type pour les stages
const stageSubTypes = [
    { value: "wheel", label: "Stage de tournage" },
    { value: "external", label: "Intervenants extérieurs" },
    { value: "themed", label: "Journée à thème" },
    { value: "one-off", label: "Stage ponctuel" },
];

// 1. Détection stricte si le cours est un Stage
const isStage = computed(() => {
    const typeName = (props.course?.type?.name || "").toLowerCase();
    return typeName.includes("stage");
});

// 2. Palette de couleurs harmonisée avec CourseAccordionItem
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

// 3. Formulaire Precognition
const form = useForm(
    "patch",
    () => (props.course ? route("courses.update", { course: props.course.id }) : ""),
    {
        name: "",
        name_en: "",
        sub_type: "wheel",
        subtitle: "",
        subtitle_en: "",
        description: "",
        description_en: "",
        practical_info: "",
        practical_info_en: "",
        default_instructor_id: "",
        default_start_time: "10:00",
        default_end_time: "17:00",
        default_spots_max_wheel: 8,
        default_spots_max_handbuilding: 2,
        default_price: "45.00",
        is_active: true,
        is_featured: false,
        reset_future_overrides: false,
        reset_lesson_ids: [],
    }
);

// 4. Liste des séances futures personnalisées
const futureOverriddenLessons = computed(() => {
    return (props.course?.lessons || []).filter(
        (l) => Boolean(l.is_overridden) && !l.is_cancelled && !l.is_past
    );
});

// 5. État réactif natif des IDs sélectionnés
const selectedLessonIds = ref([]);

// 6. Master Checkbox bidirectionnel (Getter / Setter)
const isAllOverriddenSelected = computed({
    get: () => {
        const total = futureOverriddenLessons.value.length;
        if (total === 0) return false;
        return futureOverriddenLessons.value.every((l) =>
            selectedLessonIds.value.includes(Number(l.id))
        );
    },
    set: (isChecked) => {
        if (isChecked) {
            selectedLessonIds.value = futureOverriddenLessons.value.map((l) => Number(l.id));
        } else {
            selectedLessonIds.value = [];
        }
    },
});

// Synchronisation réactive immédiate vers le formulaire Precognition
watch(
    selectedLessonIds,
    (newIds) => {
        form.reset_lesson_ids = [...newIds];
        form.reset_future_overrides =
            futureOverriddenLessons.value.length > 0 &&
            newIds.length === futureOverriddenLessons.value.length;
    },
    { deep: true }
);

// 7. Clic sur une case individuelle
const handleToggleSingleOverride = (lessonId, checked) => {
    const id = Number(lessonId);
    if (Boolean(checked)) {
        if (!selectedLessonIds.value.includes(id)) {
            selectedLessonIds.value = [...selectedLessonIds.value, id];
        }
    } else {
        selectedLessonIds.value = selectedLessonIds.value.filter((item) => item !== id);
    }
};

// 8. Libellé grammaticalement exact pour le bouton Master
const selectAllLabel = computed(() => {
    const count = futureOverriddenLessons.value.length;
    if (count <= 1) {
        return "Tout sélectionner (réaligner la séance)";
    }
    return `Tout sélectionner (réaligner les ${count} séances)`;
});

// 9. Initialisation du formulaire
const populateForm = (courseData) => {
    if (!courseData) return;

    form.name = courseData.name || "";
    form.name_en = courseData.name_en || "";
    form.sub_type = courseData.sub_type || "wheel";
    form.subtitle = courseData.subtitle || "";
    form.subtitle_en = courseData.subtitle_en || "";
    form.description = courseData.description || "";
    form.description_en = courseData.description_en || "";
    form.practical_info = courseData.practical_info || "";
    form.practical_info_en = courseData.practical_info_en || "";
    form.default_instructor_id = courseData.default_instructor_id
        ? String(courseData.default_instructor_id)
        : (courseData.instructor?.id ? String(courseData.instructor.id) : "");
    form.default_start_time = courseData.default_start_time || "10:00";
    form.default_end_time = courseData.default_end_time || "17:00";
    form.default_spots_max_wheel = courseData.default_spots_max_wheel ?? 8;
    form.default_spots_max_handbuilding = courseData.default_spots_max_handbuilding ?? 2;
    form.default_price = String(courseData.default_price ?? "45.00");

    form.is_active = Boolean(courseData.is_active);
    form.is_featured = Boolean(courseData.is_featured);

    selectedLessonIds.value = [];
    form.reset_future_overrides = false;
    form.reset_lesson_ids = [];
    form.clearErrors();
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && props.course) {
            populateForm(props.course);
        }
    },
    { immediate: true }
);

watch(
    () => props.course,
    (newCourse) => {
        if (newCourse && props.open) {
            populateForm(newCourse);
        }
    },
    { deep: true }
);

const close = () => {
    emit("update:open", false);
};

const submit = () => {
    if (!props.course) return;

    form.submit({
        preserveScroll: true,
        onSuccess: () => {
            close();
            emit("saved");
            toast.success("Cours mis à jour", {
                description: `Le cours « ${form.name} » a été modifié avec succès.`,
            });
        },
        onError: () => {
            toast.error("Erreur de validation", {
                description: "Veuillez vérifier les champs du formulaire.",
            });
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-2xl max-h-[90vh] flex flex-col p-0 overflow-hidden">
            <!-- En-tête -->
            <DialogHeader class="p-6 pb-4 border-b shrink-0 bg-muted/20 pr-12">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <DialogTitle class="text-lg font-bold truncate">
                            Modifier le cours — {{ course?.name }}
                        </DialogTitle>

                        <Badge variant="outline" :class="getTypeBadgeClass(course?.type?.name)" class="text-xs font-semibold">
                            {{ course?.type?.name }}
                        </Badge>
                    </div>
                    <DialogDescription class="text-xs">
                        Modifiez les valeurs par défaut et le contenu descriptif public.
                    </DialogDescription>
                </div>
            </DialogHeader>

            <!-- Formulaire Scrollable -->
            <form @submit.prevent="submit" class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- ========================================================= -->
                <!-- 1. INFORMATIONS GÉNÉRALES                                 -->
                <!-- ========================================================= -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                        <FileText class="h-3.5 w-3.5" /> Informations générales
                    </h4>

                    <div class="space-y-1.5">
                        <Label for="course_name" class="text-xs font-semibold">
                            Nom du cours <span v-if="isStage" class="text-muted-foreground font-normal">(FR)</span> <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="course_name"
                            type="text"
                            v-model="form.name"
                            placeholder="Ex: Moonjars avec Maître Choi Woo Cheal"
                            class="bg-background text-xs"
                            :class="{ 'border-destructive': form.errors.name }"
                            @change="form.validate('name')"
                        />
                        <p v-if="form.errors.name" class="text-[10px] text-destructive mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Professeur titulaire par défaut -->
                        <div class="space-y-1.5">
                            <Label for="default_instructor" class="text-xs font-semibold">
                                Professeur par défaut <span class="text-destructive">*</span>
                            </Label>
                            <Select
                                :model-value="form.default_instructor_id"
                                @update:model-value="(val) => { form.default_instructor_id = String(val); form.validate('default_instructor_id'); }"
                            >
                                <SelectTrigger id="default_instructor" class="bg-background text-xs" :class="{ 'border-destructive': form.errors.default_instructor_id }">
                                    <SelectValue placeholder="Sélectionner le professeur" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="inst in instructors"
                                        :key="inst.id"
                                        :value="String(inst.id)"
                                        class="text-xs"
                                    >
                                        {{ inst.first_name }} {{ inst.last_name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.default_instructor_id" class="text-[10px] text-destructive mt-1">{{ form.errors.default_instructor_id }}</p>
                        </div>

                        <!-- Formule (Read-only / KISS) -->
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold text-muted-foreground">Formule associée</Label>
                            <div class="h-9 px-3 rounded-md border bg-muted/40 text-xs text-foreground flex items-center justify-between">
                                <span>{{ course?.type?.name }}</span>
                                <Badge variant="outline" class="text-[10px] bg-background">Fixe</Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- 2. CONTENU ÉDITORIAL & BILINGUE (UNIQUEMENT SI STAGE)     -->
                <!-- ========================================================= -->
                <div v-if="isStage" class="space-y-4 p-4 rounded-xl border bg-muted/20 border-amber-300/40">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <NotepadText class="h-4 w-4 text-amber-700 dark:text-amber-400" />
                            <h4 class="text-xs font-bold text-foreground uppercase tracking-wider">
                                Fiche descriptive du stage
                            </h4>
                        </div>

                        <!-- Switch Mis en avant avec Tooltip -->
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1.5">
                                <Label for="is_featured_switch" class="text-xs font-semibold cursor-pointer select-none">
                                    Mis en avant
                                </Label>
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button
                                                type="button"
                                                tabindex="-1"
                                                class="text-muted-foreground hover:text-foreground inline-flex items-center focus:outline-none"
                                            >
                                                <HelpCircle class="h-3.5 w-3.5" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent side="top">
                                            <p class="text-xs max-w-xs">
                                                Si activé, ce stage apparaîtra en tête d'affiche sur la page publique des stages.
                                            </p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                            <Switch
                                id="is_featured_switch"
                                :checked="Boolean(form.is_featured)"
                                :model-value="Boolean(form.is_featured)"
                                @update:checked="(val) => { form.is_featured = Boolean(val); }"
                                @update:model-value="(val) => { form.is_featured = Boolean(val); }"
                            />
                        </div>
                    </div>

                    <!-- Sous-catégorie / Type de stage (COMMUN AUX DEUX LANGUES) -->
                    <div class="space-y-1.5">
                        <Label for="course_sub_type" class="text-xs font-semibold">
                            Catégorie du stage <span class="text-destructive">*</span>
                        </Label>
                        <Select
                            :model-value="form.sub_type"
                            @update:model-value="(val) => { form.sub_type = String(val); form.validate('sub_type'); }"
                        >
                            <SelectTrigger id="course_sub_type" class="bg-background text-xs" :class="{ 'border-destructive': form.errors.sub_type }">
                                <SelectValue placeholder="Sélectionner une catégorie" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="sub in stageSubTypes"
                                    :key="sub.value"
                                    :value="sub.value"
                                    class="text-xs"
                                >
                                    {{ sub.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.sub_type" class="text-[10px] text-destructive mt-1">{{ form.errors.sub_type }}</p>
                    </div>

                    <!-- Onglets FR / EN -->
                    <Tabs default-value="fr" class="w-full pt-1">
                        <TabsList class="grid w-full grid-cols-2 mb-3">
                            <TabsTrigger value="fr" class="text-xs gap-1.5">
                                🇫🇷 Français (FR)
                            </TabsTrigger>
                            <TabsTrigger value="en" class="text-xs gap-1.5">
                                🇬🇧 English (EN)
                            </TabsTrigger>
                        </TabsList>

                        <!-- Volet Français -->
                        <TabsContent value="fr" class="space-y-3 mt-0">
                            <div class="space-y-1.5">
                                <Label for="course_subtitle" class="text-xs font-semibold">Sous-titre (FR)</Label>
                                <Input
                                    id="course_subtitle"
                                    type="text"
                                    v-model="form.subtitle"
                                    placeholder="Ex: L’art du Buncheong & de l’Inhwamun"
                                    class="bg-background text-xs"
                                    :class="{ 'border-destructive': form.errors.subtitle }"
                                    @change="form.validate('subtitle')"
                                />
                                <p v-if="form.errors.subtitle" class="text-[10px] text-destructive mt-1">{{ form.errors.subtitle }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="course_description" class="text-xs font-semibold">Description complète (FR)</Label>
                                <textarea
                                    id="course_description"
                                    v-model="form.description"
                                    rows="4"
                                    placeholder="Présentation du stage, de la démarche artistique..."
                                    class="w-full rounded-md border bg-background px-3 py-2 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                    :class="{ 'border-destructive': form.errors.description }"
                                    @change="form.validate('description')"
                                />
                                <p v-if="form.errors.description" class="text-[10px] text-destructive mt-1">{{ form.errors.description }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="course_practical" class="text-xs font-semibold">Informations pratiques (FR)</Label>
                                <textarea
                                    id="course_practical"
                                    v-model="form.practical_info"
                                    rows="3"
                                    placeholder="– Prérequis : savoir centrer 2 kg&#10;– Stage sur 4 jours, de 10h à 17h&#10;– Lunch inclus..."
                                    class="w-full rounded-md border bg-background px-3 py-2 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                    :class="{ 'border-destructive': form.errors.practical_info }"
                                    @change="form.validate('practical_info')"
                                />
                                <p v-if="form.errors.practical_info" class="text-[10px] text-destructive mt-1">{{ form.errors.practical_info }}</p>
                            </div>
                        </TabsContent>

                        <!-- Volet Anglais -->
                        <TabsContent value="en" class="space-y-3 mt-0">
                            <div class="space-y-1.5">
                                <Label for="course_name_en" class="text-xs font-semibold">Nom du cours (EN)</Label>
                                <Input
                                    id="course_name_en"
                                    type="text"
                                    v-model="form.name_en"
                                    placeholder="Ex: Moonjars with Master Choi Woo Cheal"
                                    class="bg-background text-xs"
                                    :class="{ 'border-destructive': form.errors.name_en }"
                                    @change="form.validate('name_en')"
                                />
                                <p v-if="form.errors.name_en" class="text-[10px] text-destructive mt-1">{{ form.errors.name_en }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="course_subtitle_en" class="text-xs font-semibold">Sous-titre (EN)</Label>
                                <Input
                                    id="course_subtitle_en"
                                    type="text"
                                    v-model="form.subtitle_en"
                                    placeholder="Ex: The Art of Buncheong & Inhwamun"
                                    class="bg-background text-xs"
                                    :class="{ 'border-destructive': form.errors.subtitle_en }"
                                    @change="form.validate('subtitle_en')"
                                />
                                <p v-if="form.errors.subtitle_en" class="text-[10px] text-destructive mt-1">{{ form.errors.subtitle_en }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="course_description_en" class="text-xs font-semibold">Description complète (EN)</Label>
                                <textarea
                                    id="course_description_en"
                                    v-model="form.description_en"
                                    rows="4"
                                    placeholder="Immerse yourself in a unique masterclass..."
                                    class="w-full rounded-md border bg-background px-3 py-2 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                    :class="{ 'border-destructive': form.errors.description_en }"
                                    @change="form.validate('description_en')"
                                />
                                <p v-if="form.errors.description_en" class="text-[10px] text-destructive mt-1">{{ form.errors.description_en }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="course_practical_en" class="text-xs font-semibold">Informations pratiques (EN)</Label>
                                <textarea
                                    id="course_practical_en"
                                    v-model="form.practical_info_en"
                                    rows="3"
                                    placeholder="– Prerequisite: ability to center 2 kg&#10;– 4-day workshop, 10:00 to 17:00&#10;– Lunch included..."
                                    class="w-full rounded-md border bg-background px-3 py-2 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                    :class="{ 'border-destructive': form.errors.practical_info_en }"
                                    @change="form.validate('practical_info_en')"
                                />
                                <p v-if="form.errors.practical_info_en" class="text-[10px] text-destructive mt-1">{{ form.errors.practical_info_en }}</p>
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>

                <!-- ========================================================= -->
                <!-- 3. HORAIRES & TARIF PAR DÉFAUT                            -->
                <!-- ========================================================= -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                        <Clock class="h-3.5 w-3.5" /> Horaires & Tarif par défaut
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="space-y-1.5">
                            <Label for="course_start_time" class="text-xs font-semibold">Heure de début</Label>
                            <Input
                                id="course_start_time"
                                type="time"
                                v-model="form.default_start_time"
                                class="bg-background text-xs"
                                :class="{ 'border-destructive': form.errors.default_start_time }"
                                @change="form.validate('default_start_time')"
                            />
                            <p v-if="form.errors.default_start_time" class="text-[10px] text-destructive mt-1">{{ form.errors.default_start_time }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="course_end_time" class="text-xs font-semibold">Heure de fin</Label>
                            <Input
                                id="course_end_time"
                                type="time"
                                v-model="form.default_end_time"
                                class="bg-background text-xs"
                                :class="{ 'border-destructive': form.errors.default_end_time }"
                                @change="form.validate('default_end_time')"
                            />
                            <p v-if="form.errors.default_end_time" class="text-[10px] text-destructive mt-1">{{ form.errors.default_end_time }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="course_price" class="text-xs font-semibold">
                                {{ isStage ? 'Prix total stage (€)' : 'Prix / séance (€)' }}
                            </Label>
                            <Input
                                id="course_price"
                                type="number"
                                step="0.01"
                                min="0"
                                v-model="form.default_price"
                                class="bg-background text-xs"
                                :class="{ 'border-destructive': form.errors.default_price }"
                                @change="form.validate('default_price')"
                            />
                            <p v-if="form.errors.default_price" class="text-[10px] text-destructive mt-1">{{ form.errors.default_price }}</p>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- 4. POSTES DE TRAVAIL PAR DÉFAUT                           -->
                <!-- ========================================================= -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                        <Layers class="h-3.5 w-3.5" /> Capacités d'accueil par défaut
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Tours (Shell) -->
                        <div class="p-3 rounded-lg bg-background border space-y-1.5">
                            <Label for="wheel_default_spots" class="flex items-center gap-1.5 text-xs font-semibold text-sky-800 dark:text-sky-300">
                                <Shell class="h-3.5 w-3.5 text-sky-600" /> {{ pluralize(form.default_spots_max_wheel, 'Tour de potier', 'Tours de potier', false) }}
                            </Label>
                            <Input
                                id="wheel_default_spots"
                                type="number"
                                min="0"
                                max="50"
                                v-model.number="form.default_spots_max_wheel"
                                :class="{ 'border-destructive': form.errors.default_spots_max_wheel }"
                                @change="form.validate('default_spots_max_wheel')"
                            />
                            <p v-if="form.errors.default_spots_max_wheel" class="text-[10px] text-destructive mt-1">{{ form.errors.default_spots_max_wheel }}</p>
                        </div>

                        <!-- Modelage (Hand) -->
                        <div class="p-3 rounded-lg bg-background border space-y-1.5">
                            <Label for="hand_default_spots" class="flex items-center gap-1.5 text-xs font-semibold text-orange-800 dark:text-orange-300">
                                <Hand class="h-3.5 w-3.5 text-orange-600" /> {{ pluralize(form.default_spots_max_handbuilding, 'Place modelage', 'Places modelage', false) }}
                            </Label>
                            <Input
                                id="hand_default_spots"
                                type="number"
                                min="0"
                                max="50"
                                v-model.number="form.default_spots_max_handbuilding"
                                :class="{ 'border-destructive': form.errors.default_spots_max_handbuilding }"
                                @change="form.validate('default_spots_max_handbuilding')"
                            />
                            <p v-if="form.errors.default_spots_max_handbuilding" class="text-[10px] text-destructive mt-1">{{ form.errors.default_spots_max_handbuilding }}</p>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- 5. PÉRIODE DU COURS (RAPPEL FIGÉ)                         -->
                <!-- ========================================================= -->
                <div class="p-3 rounded-lg bg-muted/40 border text-xs flex items-center justify-between text-muted-foreground">
                    <span class="flex items-center gap-1.5">
                        <Calendar class="h-3.5 w-3.5 text-foreground" />
                        Période : {{ formatDateRange(course?.first_lesson_date, course?.end_date) }}
                    </span>
                    <Badge variant="outline" class="text-[10px]">
                        {{ pluralize(course?.stats?.total_lessons, 'séance') }}
                    </Badge>
                </div>

                <!-- ========================================================= -->
                <!-- 6. GESTION DES SÉANCES SURCHARGÉES (LISTE & CASES)        -->
                <!-- ========================================================= -->
                <div
                    v-if="futureOverriddenLessons.length > 0"
                    class="p-4 rounded-xl border border-amber-300/60 bg-amber-50/50 dark:bg-amber-950/20 space-y-3"
                >
                    <div class="flex items-start gap-2.5">
                        <AlertTriangle class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" />
                        <div class="text-xs space-y-1">
                            <p class="font-bold text-amber-950 dark:text-amber-200">
                                {{ pluralize(futureOverriddenLessons.length, 'séance future personnalisée') }}
                            </p>
                            <p class="text-muted-foreground text-[11px] leading-relaxed">
                                Cochez les séances que vous souhaitez réaligner sur ces nouvelles valeurs par défaut.
                            </p>
                        </div>
                    </div>

                    <!-- Master Checkbox (Tout sélectionner / Tout désélectionner) -->
                    <div class="flex items-center gap-2.5 p-2 rounded-lg bg-amber-100/60 dark:bg-amber-900/30 border border-amber-300/60 dark:border-amber-800/50">
                        <Checkbox
                            id="toggle_all_overrides"
                            :checked="isAllOverriddenSelected"
                            :model-value="isAllOverriddenSelected"
                            @update:checked="(val) => { isAllOverriddenSelected = Boolean(val); }"
                            @update:model-value="(val) => { isAllOverriddenSelected = Boolean(val); }"
                        />
                        <Label
                            for="toggle_all_overrides"
                            class="text-xs font-bold text-amber-950 dark:text-amber-200 cursor-pointer select-none"
                        >
                            {{ selectAllLabel }}
                        </Label>
                    </div>

                    <!-- ScrollArea dédiée pour la liste des séances personnalisées -->
                    <ScrollArea class="h-48 w-full rounded-lg border bg-background p-2 overscroll-contain">
                        <div class="space-y-1.5 pr-2">
                            <div
                                v-for="l in futureOverriddenLessons"
                                :key="l.id"
                                class="flex items-center justify-between p-2.5 rounded-md border transition-colors hover:bg-muted/50"
                                :class="selectedLessonIds.includes(Number(l.id)) ? 'bg-amber-50/70 border-amber-300 dark:bg-amber-950/40 dark:border-amber-800' : 'bg-card border-border'"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <Checkbox
                                        :id="`override_lesson_${l.id}`"
                                        :checked="selectedLessonIds.includes(Number(l.id))"
                                        :model-value="selectedLessonIds.includes(Number(l.id))"
                                        @update:checked="(val) => handleToggleSingleOverride(l.id, val)"
                                        @update:model-value="(val) => handleToggleSingleOverride(l.id, val)"
                                    />
                                    <Label
                                        :for="`override_lesson_${l.id}`"
                                        class="cursor-pointer select-none space-y-0.5 min-w-0"
                                    >
                                        <div class="font-medium text-foreground">
                                            {{ l.date_formatted }} ({{ l.start_time }} - {{ l.end_time }})
                                        </div>
                                        <div class="text-[11px] text-muted-foreground flex items-center gap-2 flex-wrap">
                                            <span class="flex items-center gap-1">
                                                <User class="h-2.5 w-2.5" />
                                                {{ l.instructor.name }}
                                                <Badge v-if="l.instructor.is_substitute" variant="outline" class="text-[9px] py-0 px-1 text-amber-600 border-amber-300">
                                                    Remplaçant
                                                </Badge>
                                            </span>
                                            <span>•</span>
                                            <span>{{ formatPrice(l.price) }}</span>
                                        </div>
                                    </Label>
                                </div>

                                <div class="flex items-center gap-2 text-[11px] text-muted-foreground shrink-0 pl-2">
                                    <span class="inline-flex items-center gap-0.5 text-sky-700 dark:text-sky-300 font-medium">
                                        <Shell class="h-3 w-3" /> {{ l.spots.wheel_max }}
                                    </span>
                                    <span class="inline-flex items-center gap-0.5 text-orange-700 dark:text-orange-300 font-medium">
                                        <Hand class="h-3 w-3" /> {{ l.spots.handbuilding_max }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </ScrollArea>
                </div>
            </form>

            <!-- Pied de page : Switch Publié en bas à gauche -->
            <DialogFooter class="p-4 border-t shrink-0 bg-muted/10 flex flex-row items-center justify-between w-full gap-3">
                <!-- En bas à gauche : Switch Publié -->
                <div class="flex items-center gap-2">
                    <Switch
                        id="is_active_switch"
                        :checked="Boolean(form.is_active)"
                        :model-value="Boolean(form.is_active)"
                        @update:checked="(val) => { form.is_active = Boolean(val); }"
                        @update:model-value="(val) => { form.is_active = Boolean(val); }"
                    />
                    <div class="flex items-center gap-1">
                        <Label for="is_active_switch" class="text-xs font-semibold cursor-pointer select-none">
                            Publié
                        </Label>
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <button
                                        type="button"
                                        tabindex="-1"
                                        class="text-muted-foreground hover:text-foreground inline-flex items-center focus:outline-none"
                                    >
                                        <HelpCircle class="h-3.5 w-3.5" />
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent side="top">
                                    <p class="text-xs max-w-xs">
                                        Si activé, ce cours est visible dans le calendrier et ouvert aux réservations.
                                    </p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>

                <!-- En bas à droite : Actions -->
                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="close"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        class="gap-1.5 font-semibold"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        <span>Enregistrer</span>
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
