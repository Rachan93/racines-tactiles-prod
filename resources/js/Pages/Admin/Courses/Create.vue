<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Link } from "@inertiajs/vue3";
import { useForm } from "laravel-precognition-vue-inertia";
import { toast } from "vue-sonner";
import axios from "axios";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import LessonPreviewList from "@/Components/admin/courses/LessonPreviewList.vue";

// Composants Shadcn UI
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from "@/Components/ui/card";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/Components/ui/tabs";
import { Switch } from "@/Components/ui/switch";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Button } from "@/Components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import {
    ChevronRight,
    Shell,
    Hand,
    Clock,
    Euro,
    Loader2,
    CalendarCheck2,
    NotepadText,
    HelpCircle,
    AlertCircle,
} from "lucide-vue-next";

const props = defineProps({
    types: {
        type: Array,
        default: () => [],
    },
    instructors: {
        type: Array,
        default: () => [],
    },
});

// Options de l'enum sub_type pour les stages
const stageSubTypes = [
    {
        value: "wheel",
        label: "Stages de tournage",
    },
    {
        value: "external",
        label: "Intervenants externes",
    },
    {
        value: "themed",
        label: "Journées thématiques",
    },
    {
        value: "one-off",
        label: "Stages ponctuels",
    },
];

// Formulaire Precognition
const form = useForm("post", route("courses.store"), {
    name: "",
    name_en: "",
    type_id: props.types[0]?.id ? String(props.types[0].id) : "",
    default_instructor_id: props.instructors[0]?.id
        ? String(props.instructors[0].id)
        : "",
    sub_type: null,
    subtitle: "",
    subtitle_en: "",
    description: "",
    description_en: "",
    practical_info: "",
    practical_info_en: "",
    first_lesson_date: "",
    end_date: "",
    default_start_time: "14:00",
    default_end_time: "16:30",
    frequency: 7,
    default_spots_max_wheel: 8,
    default_spots_max_handbuilding: 2,
    default_price: "45.00",
    is_active: true,
    is_featured: false,
    exclude_public_holidays: true,
    exclude_school_holidays: true,
    exclude_studio_closures: true,
    exclude_weekends: true,
    confirmed_dates: [],
});

// Détection de stage
const isStageType = (typeName = "") => {
    return (typeName || "").toLowerCase().includes("stage");
};

const isStage = computed(() => {
    const selectedType = props.types.find(
        (t) => String(t.id) === String(form.type_id),
    );
    return isStageType(selectedType?.name);
});

// Numérotation dynamique des étapes
const calendarStepNumber = computed(() => (isStage.value ? 3 : 2));
const capacityStepNumber = computed(() => (isStage.value ? 4 : 3));

// État de la prévisualisation
const schedule = ref({
    generated_dates: [],
    skipped_dates: [],
    total_generated: 0,
    total_skipped: 0,
});
const isLoadingPreview = ref(false);
let previewTimeout = null;

// Application des règles métier selon le type sélectionné
const applyTypePresets = (typeId) => {
    const selectedType = props.types.find(
        (t) => String(t.id) === String(typeId),
    );

    const isStageSelected = isStageType(selectedType?.name);

    if (isStageSelected) {
        form.frequency = 1;

        form.exclude_school_holidays = false;
        form.exclude_public_holidays = false;
        form.exclude_studio_closures = false;
        form.exclude_weekends = false;

        // Valeurs propres aux stages
        if (!form.sub_type) {
            form.sub_type = "wheel";
        }

        form.is_featured = true;
    } else {
        // Collectif / privé / autre cours
        form.frequency = 7;

        form.exclude_school_holidays = true;
        form.exclude_public_holidays = true;
        form.exclude_studio_closures = true;
        form.exclude_weekends = true;

        // IMPORTANT :
        // ces champs ne doivent jamais appartenir à un cours non-stage
        form.sub_type = null;
        form.is_featured = false;
    }
};

const handleTypeChange = (newTypeId) => {
    form.type_id = String(newTypeId);
    form.validate("type_id");
    applyTypePresets(newTypeId);
};

// Initialisation au montage
onMounted(() => {
    if (form.type_id) {
        applyTypePresets(form.type_id);
    }
});

// Requête de recalcul du planning
const fetchPreview = () => {
    if (!form.first_lesson_date || !form.end_date || !form.frequency) {
        schedule.value = {
            generated_dates: [],
            skipped_dates: [],
            total_generated: 0,
            total_skipped: 0,
        };
        form.confirmed_dates = [];
        return;
    }

    isLoadingPreview.value = true;

    axios
        .post(route("courses.preview"), {
            first_lesson_date: form.first_lesson_date,
            end_date: form.end_date,
            frequency: form.frequency,
            type_id: form.type_id,
            exclude_public_holidays: form.exclude_public_holidays,
            exclude_school_holidays: form.exclude_school_holidays,
            exclude_studio_closures: form.exclude_studio_closures,
            exclude_weekends: form.exclude_weekends,
        })
        .then((res) => {
            schedule.value = res.data;
            form.confirmed_dates = (res.data.generated_dates || []).map(
                (d) => d.date,
            );
            form.validate("confirmed_dates");
        })
        .catch((err) => {
            console.error("Erreur lors du calcul du planning :", err);
        })
        .finally(() => {
            isLoadingPreview.value = false;
        });
};

// Observer les changements pour recalculer en direct (200ms debounce)
watch(
    [
        () => form.first_lesson_date,
        () => form.end_date,
        () => form.frequency,
        () => form.type_id,
        () => form.exclude_public_holidays,
        () => form.exclude_school_holidays,
        () => form.exclude_studio_closures,
        () => form.exclude_weekends,
    ],
    () => {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(fetchPreview, 200);
    },
);

// Soumission avec notifications Sonner
const submit = () => {
    form.submit({
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Cours créé avec succès", {
                description: `Le cours « ${form.name} » et ses ${form.confirmed_dates.length} séances ont été générés.`,
            });
        },
        onError: () => {
            toast.error("Erreur de validation", {
                description:
                    "Veuillez vérifier les informations indiquées en rouge.",
            });
        },
    });
};
</script>

<template>
    <AdminLayout title="Création d'un cours">
        <div class="space-y-6">
            <!-- ========================================================= -->
            <!-- 1. BREADCRUMBS & EN-TÊTE DE PAGE                          -->
            <!-- ========================================================= -->
            <div class="space-y-3">
                <nav
                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <Link
                        :href="route('dashboard.index')"
                        class="hover:text-foreground transition-colors"
                    >
                        Tableau de bord
                    </Link>
                    <ChevronRight
                        class="h-3.5 w-3.5 text-muted-foreground/60"
                    />
                    <Link
                        :href="route('courses.index')"
                        class="hover:text-foreground transition-colors"
                    >
                        Cours & Séances
                    </Link>
                    <ChevronRight
                        class="h-3.5 w-3.5 text-muted-foreground/60"
                    />
                    <span class="font-semibold text-foreground"
                        >Créer un cours</span
                    >
                </nav>

                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                >
                    <div class="space-y-1">
                        <h2
                            class="text-2xl font-bold tracking-tight text-foreground"
                        >
                            Nouveau cours & Planning
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Configurez les règles de récurrence, les options de
                            publication et validez les séances.
                        </p>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 2. FORMULAIRE PRINCIPAL (2 COLONNES)                     -->
            <!-- ========================================================= -->
            <form @submit.prevent="submit">
                <div class="grid gap-8 lg:grid-cols-12 items-start">
                    <!-- ========================================================= -->
                    <!-- COLONNE GAUCHE (7 cols) : ÉTAPES DU COURS                 -->
                    <!-- ========================================================= -->
                    <div class="lg:col-span-7 space-y-6">
                        <!-- ÉTAPE 1 : Informations Générales & Publication -->
                        <Card class="shadow-xs">
                            <CardHeader class="pb-3">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div>
                                        <CardTitle
                                            class="text-base font-semibold"
                                            >1. Informations
                                            générales</CardTitle
                                        >
                                        <CardDescription
                                            >Intitulé, formule et professeur
                                            titulaire</CardDescription
                                        >
                                    </div>

                                    <!-- Switch Publié (is_active) -->
                                    <div
                                        class="flex items-center gap-2 bg-muted/40 px-3 py-1.5 rounded-lg border"
                                    >
                                        <Switch
                                            id="course_is_active"
                                            :checked="Boolean(form.is_active)"
                                            :model-value="
                                                Boolean(form.is_active)
                                            "
                                            @update:checked="
                                                (val) => {
                                                    form.is_active =
                                                        Boolean(val);
                                                    form.validate('is_active');
                                                }
                                            "
                                            @update:model-value="
                                                (val) => {
                                                    form.is_active =
                                                        Boolean(val);
                                                    form.validate('is_active');
                                                }
                                            "
                                        />
                                        <div class="flex items-center gap-1">
                                            <Label
                                                for="course_is_active"
                                                class="text-xs font-semibold cursor-pointer select-none"
                                            >
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
                                                            <HelpCircle
                                                                class="h-3.5 w-3.5"
                                                            />
                                                        </button>
                                                    </TooltipTrigger>
                                                    <TooltipContent side="top">
                                                        <p
                                                            class="text-xs max-w-xs"
                                                        >
                                                            Si activé, ce cours
                                                            est visible sur le
                                                            planning et ouvert
                                                            aux réservations.
                                                        </p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        </div>
                                    </div>
                                </div>
                            </CardHeader>

                            <CardContent class="space-y-4">
                                <!-- Nom du cours -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="course_name"
                                        class="text-xs font-semibold"
                                    >
                                        Nom du cours
                                        <span
                                            v-if="isStage"
                                            class="text-muted-foreground font-normal"
                                            >(FR)</span
                                        >
                                        <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="course_name"
                                        v-model="form.name"
                                        placeholder="Ex: Tournage Débutant - Mardi Après-midi"
                                        class="bg-background text-xs"
                                        :class="{
                                            'border-destructive focus-visible:ring-destructive/30':
                                                form.errors.name,
                                        }"
                                        @change="form.validate('name')"
                                        @blur="form.validate('name')"
                                    />
                                    <p
                                        v-if="form.errors.name"
                                        class="text-xs text-destructive mt-1 font-medium"
                                    >
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <div class="grid sm:grid-cols-2 gap-4">
                                    <!-- Type de cours -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="type_id"
                                            class="text-xs font-semibold"
                                        >
                                            Type de formule
                                            <span class="text-destructive"
                                                >*</span
                                            >
                                        </Label>
                                        <Select
                                            :model-value="form.type_id"
                                            @update:model-value="
                                                handleTypeChange
                                            "
                                        >
                                            <SelectTrigger
                                                id="type_id"
                                                class="bg-background text-xs"
                                                :class="{
                                                    'border-destructive focus-visible:ring-destructive/30':
                                                        form.errors.type_id,
                                                }"
                                            >
                                                <SelectValue
                                                    placeholder="Choisir une formule"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="t in types"
                                                    :key="t.id"
                                                    :value="String(t.id)"
                                                    class="text-xs"
                                                >
                                                    {{ t.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p
                                            v-if="form.errors.type_id"
                                            class="text-xs text-destructive mt-1 font-medium"
                                        >
                                            {{ form.errors.type_id }}
                                        </p>
                                    </div>

                                    <!-- Professeur responsable -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="instructor_id"
                                            class="text-xs font-semibold"
                                        >
                                            Professeur par défaut
                                            <span class="text-destructive"
                                                >*</span
                                            >
                                        </Label>
                                        <Select
                                            :model-value="
                                                form.default_instructor_id
                                            "
                                            @update:model-value="
                                                (val) => {
                                                    form.default_instructor_id =
                                                        String(val);
                                                    form.validate(
                                                        'default_instructor_id',
                                                    );
                                                }
                                            "
                                        >
                                            <SelectTrigger
                                                id="instructor_id"
                                                class="bg-background text-xs"
                                                :class="{
                                                    'border-destructive focus-visible:ring-destructive/30':
                                                        form.errors
                                                            .default_instructor_id,
                                                }"
                                            >
                                                <SelectValue
                                                    placeholder="Choisir un professeur"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="inst in instructors"
                                                    :key="inst.id"
                                                    :value="String(inst.id)"
                                                    class="text-xs"
                                                >
                                                    {{ inst.first_name }}
                                                    {{ inst.last_name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p
                                            v-if="
                                                form.errors
                                                    .default_instructor_id
                                            "
                                            class="text-xs text-destructive mt-1 font-medium"
                                        >
                                            {{
                                                form.errors
                                                    .default_instructor_id
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- ÉTAPE 2 (Conditionnelle) : Fiche descriptive du Stage (FR / EN) -->
                        <Card
                            v-if="isStage"
                            class="shadow-xs border-amber-300/40 bg-amber-500/5"
                        >
                            <CardHeader class="pb-3">
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <NotepadText
                                            class="h-4 w-4 text-amber-700 dark:text-amber-400"
                                        />
                                        <div>
                                            <CardTitle
                                                class="text-base font-semibold"
                                                >2. Fiche descriptive du
                                                stage</CardTitle
                                            >
                                            <CardDescription
                                                >Présentation éditoriale
                                                bilingue pour le
                                                public</CardDescription
                                            >
                                        </div>
                                    </div>

                                    <!-- Switch Mis en avant avec Tooltip (ON par défaut) -->
                                    <div
                                        class="flex items-center gap-2 bg-background px-3 py-1.5 rounded-lg border border-amber-300/40"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <Label
                                                for="is_featured_switch"
                                                class="text-xs font-semibold cursor-pointer select-none"
                                            >
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
                                                            <HelpCircle
                                                                class="h-3.5 w-3.5"
                                                            />
                                                        </button>
                                                    </TooltipTrigger>
                                                    <TooltipContent side="top">
                                                        <p
                                                            class="text-xs max-w-xs"
                                                        >
                                                            Si activé, ce stage
                                                            apparaîtra en tête
                                                            d'affiche sur la
                                                            page publique des
                                                            stages.
                                                        </p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        </div>
                                        <Switch
                                            id="is_featured_switch"
                                            :checked="Boolean(form.is_featured)"
                                            :model-value="
                                                Boolean(form.is_featured)
                                            "
                                            @update:checked="
                                                (val) => {
                                                    form.is_featured =
                                                        Boolean(val);
                                                    form.validate(
                                                        'is_featured',
                                                    );
                                                }
                                            "
                                            @update:model-value="
                                                (val) => {
                                                    form.is_featured =
                                                        Boolean(val);
                                                    form.validate(
                                                        'is_featured',
                                                    );
                                                }
                                            "
                                        />
                                    </div>
                                </div>
                            </CardHeader>

                            <CardContent class="space-y-4">
                                <!-- Sous-catégorie / Type de stage (COMMUN AUX DEUX LANGUES) -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="course_sub_type"
                                        class="text-xs font-semibold"
                                    >
                                        Catégorie du stage
                                        <span class="text-destructive">*</span>
                                    </Label>
                                    <Select
                                        :model-value="form.sub_type"
                                        @update:model-value="
                                            (val) => {
                                                form.sub_type = String(val);
                                                form.validate('sub_type');
                                            }
                                        "
                                    >
                                        <SelectTrigger
                                            id="course_sub_type"
                                            class="bg-background text-xs"
                                            :class="{
                                                'border-destructive focus-visible:ring-destructive/30':
                                                    form.errors.sub_type,
                                            }"
                                        >
                                            <SelectValue
                                                placeholder="Choisir une catégorie de stage"
                                            />
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
                                    <p
                                        v-if="form.errors.sub_type"
                                        class="text-xs text-destructive mt-1 font-medium"
                                    >
                                        {{ form.errors.sub_type }}
                                    </p>
                                </div>

                                <!-- Onglets Bilingues FR / EN -->
                                <Tabs default-value="fr" class="w-full pt-1">
                                    <TabsList
                                        class="grid w-full grid-cols-2 mb-3 bg-background"
                                    >
                                        <TabsTrigger
                                            value="fr"
                                            class="text-xs gap-1.5"
                                        >
                                            🇫🇷 Français (FR)
                                        </TabsTrigger>
                                        <TabsTrigger
                                            value="en"
                                            class="text-xs gap-1.5"
                                        >
                                            🇬🇧 English (EN)
                                        </TabsTrigger>
                                    </TabsList>

                                    <!-- Volet Français -->
                                    <TabsContent
                                        value="fr"
                                        class="space-y-3 mt-0"
                                    >
                                        <div class="space-y-1.5">
                                            <Label
                                                for="course_subtitle"
                                                class="text-xs font-semibold"
                                                >Sous-titre (FR)</Label
                                            >
                                            <Input
                                                id="course_subtitle"
                                                type="text"
                                                v-model="form.subtitle"
                                                placeholder="Ex: L’art du Buncheong & de l’Inhwamun"
                                                class="bg-background text-xs"
                                                :class="{
                                                    'border-destructive focus-visible:ring-destructive/30':
                                                        form.errors.subtitle,
                                                }"
                                                @change="
                                                    form.validate('subtitle')
                                                "
                                                @blur="
                                                    form.validate('subtitle')
                                                "
                                            />
                                            <p
                                                v-if="form.errors.subtitle"
                                                class="text-xs text-destructive mt-1 font-medium"
                                            >
                                                {{ form.errors.subtitle }}
                                            </p>
                                        </div>

                                        <div class="space-y-1.5">
                                            <Label
                                                for="course_description"
                                                class="text-xs font-semibold"
                                                >Description complète
                                                (FR)</Label
                                            >
                                            <textarea
                                                id="course_description"
                                                v-model="form.description"
                                                rows="4"
                                                placeholder="Présentation du stage, démarche artistique..."
                                                class="w-full rounded-md border bg-background px-3 py-2 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                                :class="{
                                                    'border-destructive focus:ring-destructive':
                                                        form.errors.description,
                                                }"
                                                @change="
                                                    form.validate('description')
                                                "
                                                @blur="
                                                    form.validate('description')
                                                "
                                            />
                                            <p
                                                v-if="form.errors.description"
                                                class="text-xs text-destructive mt-1 font-medium"
                                            >
                                                {{ form.errors.description }}
                                            </p>
                                        </div>

                                        <div class="space-y-1.5">
                                            <Label
                                                for="course_practical"
                                                class="text-xs font-semibold"
                                                >Informations pratiques
                                                (FR)</Label
                                            >
                                            <textarea
                                                id="course_practical"
                                                v-model="form.practical_info"
                                                rows="3"
                                                placeholder="– Prérequis : savoir centrer 2 kg&#10;– Stage sur 4 jours, de 10h à 17h&#10;– Lunch inclus..."
                                                class="w-full rounded-md border bg-background px-3 py-2 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                                :class="{
                                                    'border-destructive focus:ring-destructive':
                                                        form.errors
                                                            .practical_info,
                                                }"
                                                @change="
                                                    form.validate(
                                                        'practical_info',
                                                    )
                                                "
                                                @blur="
                                                    form.validate(
                                                        'practical_info',
                                                    )
                                                "
                                            />
                                            <p
                                                v-if="
                                                    form.errors.practical_info
                                                "
                                                class="text-xs text-destructive mt-1 font-medium"
                                            >
                                                {{ form.errors.practical_info }}
                                            </p>
                                        </div>
                                    </TabsContent>

                                    <!-- Volet Anglais -->
                                    <TabsContent
                                        value="en"
                                        class="space-y-3 mt-0"
                                    >
                                        <div class="space-y-1.5">
                                            <Label
                                                for="course_name_en"
                                                class="text-xs font-semibold"
                                                >Nom du cours (EN)</Label
                                            >
                                            <Input
                                                id="course_name_en"
                                                type="text"
                                                v-model="form.name_en"
                                                placeholder="Ex: Moonjars with Master Choi Woo Cheal"
                                                class="bg-background text-xs"
                                                :class="{
                                                    'border-destructive focus-visible:ring-destructive/30':
                                                        form.errors.name_en,
                                                }"
                                                @change="
                                                    form.validate('name_en')
                                                "
                                                @blur="form.validate('name_en')"
                                            />
                                            <p
                                                v-if="form.errors.name_en"
                                                class="text-xs text-destructive mt-1 font-medium"
                                            >
                                                {{ form.errors.name_en }}
                                            </p>
                                        </div>

                                        <div class="space-y-1.5">
                                            <Label
                                                for="course_subtitle_en"
                                                class="text-xs font-semibold"
                                                >Sous-titre (EN)</Label
                                            >
                                            <Input
                                                id="course_subtitle_en"
                                                type="text"
                                                v-model="form.subtitle_en"
                                                placeholder="Ex: The Art of Buncheong & Inhwamun"
                                                class="bg-background text-xs"
                                                :class="{
                                                    'border-destructive focus-visible:ring-destructive/30':
                                                        form.errors.subtitle_en,
                                                }"
                                                @change="
                                                    form.validate('subtitle_en')
                                                "
                                                @blur="
                                                    form.validate('subtitle_en')
                                                "
                                            />
                                            <p
                                                v-if="form.errors.subtitle_en"
                                                class="text-xs text-destructive mt-1 font-medium"
                                            >
                                                {{ form.errors.subtitle_en }}
                                            </p>
                                        </div>

                                        <div class="space-y-1.5">
                                            <Label
                                                for="course_description_en"
                                                class="text-xs font-semibold"
                                                >Description complète
                                                (EN)</Label
                                            >
                                            <textarea
                                                id="course_description_en"
                                                v-model="form.description_en"
                                                rows="4"
                                                placeholder="Immerse yourself in a unique masterclass..."
                                                class="w-full rounded-md border bg-background px-3 py-2 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                                :class="{
                                                    'border-destructive focus:ring-destructive':
                                                        form.errors
                                                            .description_en,
                                                }"
                                                @change="
                                                    form.validate(
                                                        'description_en',
                                                    )
                                                "
                                                @blur="
                                                    form.validate(
                                                        'description_en',
                                                    )
                                                "
                                            />
                                            <p
                                                v-if="
                                                    form.errors.description_en
                                                "
                                                class="text-xs text-destructive mt-1 font-medium"
                                            >
                                                {{ form.errors.description_en }}
                                            </p>
                                        </div>

                                        <div class="space-y-1.5">
                                            <Label
                                                for="course_practical_en"
                                                class="text-xs font-semibold"
                                                >Informations pratiques
                                                (EN)</Label
                                            >
                                            <textarea
                                                id="course_practical_en"
                                                v-model="form.practical_info_en"
                                                rows="3"
                                                placeholder="– Prerequisite: ability to center 2 kg&#10;– 4-day workshop, 10:00 to 17:00&#10;– Lunch included..."
                                                class="w-full rounded-md border bg-background px-3 py-2 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                                :class="{
                                                    'border-destructive focus:ring-destructive':
                                                        form.errors
                                                            .practical_info_en,
                                                }"
                                                @change="
                                                    form.validate(
                                                        'practical_info_en',
                                                    )
                                                "
                                                @blur="
                                                    form.validate(
                                                        'practical_info_en',
                                                    )
                                                "
                                            />
                                            <p
                                                v-if="
                                                    form.errors
                                                        .practical_info_en
                                                "
                                                class="text-xs text-destructive mt-1 font-medium"
                                            >
                                                {{
                                                    form.errors
                                                        .practical_info_en
                                                }}
                                            </p>
                                        </div>
                                    </TabsContent>
                                </Tabs>
                            </CardContent>
                        </Card>

                        <!-- ÉTAPE 3 (ou 2 si pas stage) : Calendrier & Horaires -->
                        <Card class="shadow-xs">
                            <CardHeader class="pb-3">
                                <CardTitle class="text-base font-semibold">
                                    {{ calendarStepNumber }}. Calendrier &
                                    Horaires
                                </CardTitle>
                                <CardDescription
                                    >Période de cours et heures de début /
                                    fin</CardDescription
                                >
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <Label
                                            for="first_lesson_date"
                                            class="text-xs font-semibold"
                                        >
                                            Date 1ère séance
                                            <span class="text-destructive"
                                                >*</span
                                            >
                                        </Label>
                                        <Input
                                            id="first_lesson_date"
                                            type="date"
                                            v-model="form.first_lesson_date"
                                            class="bg-background text-xs"
                                            :class="{
                                                'border-destructive focus-visible:ring-destructive/30':
                                                    form.errors
                                                        .first_lesson_date,
                                            }"
                                            @change="
                                                form.validate(
                                                    'first_lesson_date',
                                                )
                                            "
                                            @blur="
                                                form.validate(
                                                    'first_lesson_date',
                                                )
                                            "
                                        />
                                        <p
                                            v-if="form.errors.first_lesson_date"
                                            class="text-xs text-destructive mt-1 font-medium"
                                        >
                                            {{ form.errors.first_lesson_date }}
                                        </p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="end_date"
                                            class="text-xs font-semibold"
                                        >
                                            Date de fin du cours
                                            <span class="text-destructive"
                                                >*</span
                                            >
                                        </Label>
                                        <Input
                                            id="end_date"
                                            type="date"
                                            v-model="form.end_date"
                                            class="bg-background text-xs"
                                            :class="{
                                                'border-destructive focus-visible:ring-destructive/30':
                                                    form.errors.end_date,
                                            }"
                                            @change="form.validate('end_date')"
                                            @blur="form.validate('end_date')"
                                        />
                                        <p
                                            v-if="form.errors.end_date"
                                            class="text-xs text-destructive mt-1 font-medium"
                                        >
                                            {{ form.errors.end_date }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-1 sm:grid-cols-3 gap-4"
                                >
                                    <div class="space-y-1.5">
                                        <Label
                                            for="start_time"
                                            class="text-xs font-semibold"
                                        >
                                            Heure de début
                                            <span class="text-destructive"
                                                >*</span
                                            >
                                        </Label>
                                        <Input
                                            id="start_time"
                                            type="time"
                                            v-model="form.default_start_time"
                                            class="bg-background text-xs"
                                            :class="{
                                                'border-destructive focus-visible:ring-destructive/30':
                                                    form.errors
                                                        .default_start_time,
                                            }"
                                            @change="
                                                form.validate(
                                                    'default_start_time',
                                                )
                                            "
                                            @blur="
                                                form.validate(
                                                    'default_start_time',
                                                )
                                            "
                                        />
                                        <p
                                            v-if="
                                                form.errors.default_start_time
                                            "
                                            class="text-xs text-destructive mt-1 font-medium"
                                        >
                                            {{ form.errors.default_start_time }}
                                        </p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="end_time"
                                            class="text-xs font-semibold"
                                        >
                                            Heure de fin
                                            <span class="text-destructive"
                                                >*</span
                                            >
                                        </Label>
                                        <Input
                                            id="end_time"
                                            type="time"
                                            v-model="form.default_end_time"
                                            class="bg-background text-xs"
                                            :class="{
                                                'border-destructive focus-visible:ring-destructive/30':
                                                    form.errors
                                                        .default_end_time,
                                            }"
                                            @change="
                                                form.validate(
                                                    'default_end_time',
                                                )
                                            "
                                            @blur="
                                                form.validate(
                                                    'default_end_time',
                                                )
                                            "
                                        />
                                        <p
                                            v-if="form.errors.default_end_time"
                                            class="text-xs text-destructive mt-1 font-medium"
                                        >
                                            {{ form.errors.default_end_time }}
                                        </p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="frequency"
                                            class="text-xs font-semibold"
                                        >
                                            Fréquence (jours)
                                            <span class="text-destructive"
                                                >*</span
                                            >
                                        </Label>
                                        <Input
                                            id="frequency"
                                            type="number"
                                            min="1"
                                            max="365"
                                            v-model.number="form.frequency"
                                            placeholder="7 = hebdo, 1 = quotidien"
                                            class="bg-background text-xs"
                                            :class="{
                                                'border-destructive focus-visible:ring-destructive/30':
                                                    form.errors.frequency,
                                            }"
                                            @change="form.validate('frequency')"
                                            @blur="form.validate('frequency')"
                                        />
                                        <p
                                            v-if="form.errors.frequency"
                                            class="text-xs text-destructive mt-1 font-medium"
                                        >
                                            {{ form.errors.frequency }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- ÉTAPE 4 (ou 3 si pas stage) : Postes & Tarification -->
                        <Card class="shadow-xs">
                            <CardHeader class="pb-3">
                                <CardTitle class="text-base font-semibold">
                                    {{ capacityStepNumber }}. Postes &
                                    Tarification
                                </CardTitle>
                                <CardDescription
                                    >Nombre de tours de potier, places de
                                    modelage et prix</CardDescription
                                >
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-3 gap-4"
                                >
                                    <!-- Places Tours (Shell) -->
                                    <div
                                        class="p-3 rounded-lg bg-sky-500/5 border border-sky-500/20 space-y-1.5"
                                    >
                                        <Label
                                            for="spots_wheel"
                                            class="flex items-center gap-1.5 text-xs font-semibold text-sky-800 dark:text-sky-300"
                                        >
                                            <Shell
                                                class="h-3.5 w-3.5 text-sky-600"
                                            />
                                            <span>Postes Tour</span>
                                        </Label>
                                        <Input
                                            id="spots_wheel"
                                            type="number"
                                            min="0"
                                            max="50"
                                            v-model.number="
                                                form.default_spots_max_wheel
                                            "
                                            class="bg-background text-xs"
                                            :class="{
                                                'border-destructive focus-visible:ring-destructive/30':
                                                    form.errors
                                                        .default_spots_max_wheel,
                                            }"
                                            @change="
                                                form.validate(
                                                    'default_spots_max_wheel',
                                                )
                                            "
                                            @blur="
                                                form.validate(
                                                    'default_spots_max_wheel',
                                                )
                                            "
                                        />
                                        <p
                                            v-if="
                                                form.errors
                                                    .default_spots_max_wheel
                                            "
                                            class="text-xs text-destructive mt-1 font-medium leading-tight"
                                        >
                                            {{
                                                form.errors
                                                    .default_spots_max_wheel
                                            }}
                                        </p>
                                    </div>

                                    <!-- Places Modelage (Hand) -->
                                    <div
                                        class="p-3 rounded-lg bg-orange-500/5 border border-orange-500/20 space-y-1.5"
                                    >
                                        <Label
                                            for="spots_handbuilding"
                                            class="flex items-center gap-1.5 text-xs font-semibold text-orange-800 dark:text-orange-300"
                                        >
                                            <Hand
                                                class="h-3.5 w-3.5 text-orange-600"
                                            />
                                            <span>Postes Modelage</span>
                                        </Label>
                                        <Input
                                            id="spots_handbuilding"
                                            type="number"
                                            min="0"
                                            max="50"
                                            v-model.number="
                                                form.default_spots_max_handbuilding
                                            "
                                            class="bg-background text-xs"
                                            :class="{
                                                'border-destructive focus-visible:ring-destructive/30':
                                                    form.errors
                                                        .default_spots_max_handbuilding,
                                            }"
                                            @change="
                                                form.validate(
                                                    'default_spots_max_handbuilding',
                                                )
                                            "
                                            @blur="
                                                form.validate(
                                                    'default_spots_max_handbuilding',
                                                )
                                            "
                                        />
                                        <p
                                            v-if="
                                                form.errors
                                                    .default_spots_max_handbuilding
                                            "
                                            class="text-xs text-destructive mt-1 font-medium leading-tight"
                                        >
                                            {{
                                                form.errors
                                                    .default_spots_max_handbuilding
                                            }}
                                        </p>
                                    </div>

                                    <!-- Prix unitaire -->
                                    <div
                                        class="p-3 rounded-lg bg-muted/60 border space-y-1.5"
                                    >
                                        <Label
                                            for="price"
                                            class="flex items-center gap-1 text-xs font-semibold text-foreground"
                                        >
                                            <Euro
                                                class="h-3.5 w-3.5 text-muted-foreground"
                                            />
                                            <span>{{
                                                isStage
                                                    ? "Prix total stage (€)"
                                                    : "Prix / séance (€)"
                                            }}</span>
                                        </Label>
                                        <Input
                                            id="price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            v-model="form.default_price"
                                            class="bg-background text-xs"
                                            :class="{
                                                'border-destructive focus-visible:ring-destructive/30':
                                                    form.errors.default_price,
                                            }"
                                            @change="
                                                form.validate('default_price')
                                            "
                                            @blur="
                                                form.validate('default_price')
                                            "
                                        />
                                        <p
                                            v-if="form.errors.default_price"
                                            class="text-xs text-destructive mt-1 font-medium leading-tight"
                                        >
                                            {{ form.errors.default_price }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Erreur globale si aucune date cochée -->
                        <div
                            v-if="form.errors.confirmed_dates"
                            class="p-3.5 rounded-lg bg-destructive/10 border border-destructive/30 text-destructive text-xs font-medium flex items-center gap-2.5"
                        >
                            <AlertCircle class="h-4 w-4 shrink-0" />
                            <span>{{ form.errors.confirmed_dates }}</span>
                        </div>

                        <!-- Bouton de Soumission Final -->
                        <div class="pt-2">
                            <Button
                                type="submit"
                                size="lg"
                                class="w-full gap-2 shadow-sm font-semibold text-sm"
                                :disabled="
                                    form.processing ||
                                    form.confirmed_dates.length === 0
                                "
                            >
                                <Loader2
                                    v-if="form.processing"
                                    class="h-4 w-4 animate-spin"
                                />
                                <CalendarCheck2 v-else class="h-4 w-4" />
                                <span>
                                    Créer le cours et générer les
                                    {{ form.confirmed_dates.length }} séances
                                </span>
                            </Button>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- COLONNE DROITE (5 cols) : PRÉVISUALISATION DU PLANNING    -->
                    <!-- ========================================================= -->
                    <div class="lg:col-span-5">
                        <LessonPreviewList
                            :schedule="schedule"
                            v-model="form.confirmed_dates"
                            v-model:exclude-public-holidays="
                                form.exclude_public_holidays
                            "
                            v-model:exclude-school-holidays="
                                form.exclude_school_holidays
                            "
                            v-model:exclude-studio-closures="
                                form.exclude_studio_closures
                            "
                            v-model:exclude-weekends="form.exclude_weekends"
                            :loading="isLoadingPreview"
                            :start-time="form.default_start_time"
                            :end-time="form.default_end_time"
                        />
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
