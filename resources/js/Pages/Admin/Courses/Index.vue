<script setup>
import { ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { pluralize } from "@/Utils/formatters";
import AdminLayout from "@/Layouts/AdminLayout.vue";

// Composants factorisés
import CourseSearchFilters from "@/Components/admin/courses/CourseSearchFilters.vue";
import CourseAccordionItem from "@/Components/admin/courses/CourseAccordionItem.vue";
import CourseEditModal from "@/Components/admin/courses/CourseEditModal.vue";
import LessonEditSheet from "@/Components/admin/courses/LessonEditSheet.vue";
import LessonAttendeesModal from "@/Components/admin/courses/LessonAttendeesModal.vue";

// Composants Shadcn UI
import { Accordion } from "@/Components/ui/accordion";
import { Button } from "@/Components/ui/button";
import { Checkbox } from "@/Components/ui/checkbox";
import { Label } from "@/Components/ui/label";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";
import {
    Plus,
    GraduationCap,
    Trash2,
    AlertTriangle,
    ChevronRight,
    ArrowUp,
    ArrowDown,
    CalendarClock,
    Loader2,
} from "lucide-vue-next";

const props = defineProps({
    courses: {
        type: Array,
        default: () => [],
    },
    types: {
        type: Array,
        default: () => [],
    },
    instructors: {
        type: Array,
        default: () => [],
    },
    years: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: "",
            search_targets: ["name", "instructor"],
            type_id: "",
            year: "",
            status: "all",
            sort: "oldest",
        }),
    },
});

// 1. Application des filtres
const handleApplyFilters = (newFilters) => {
    router.get(
        route("courses.index"),
        {
            search: newFilters.search,
            search_targets: newFilters.search_targets,
            type_id: newFilters.type_id,
            year: newFilters.year,
            status: newFilters.status,
            sort: props.filters.sort || "oldest",
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

// 2. Gestion du tri au-dessus de la liste
const handleSortChange = (newSort) => {
    router.get(
        route("courses.index"),
        {
            search: props.filters.search,
            search_targets: props.filters.search_targets,
            type_id: props.filters.type_id,
            year: props.filters.year,
            status: props.filters.status,
            sort: newSort,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

// 3. Modale d'édition du cours parent
const isCourseModalOpen = ref(false);
const selectedCourseForEdit = ref(null);

const handleEditCourse = (course) => {
    selectedCourseForEdit.value = course;
    isCourseModalOpen.value = true;
};

// 4. Tiroir latéral d'édition de séance
const isLessonSheetOpen = ref(false);
const selectedLesson = ref(null);
const selectedCourseForLesson = ref(null);

const handleEditLesson = ({ lesson, course }) => {
    selectedLesson.value = lesson;
    selectedCourseForLesson.value = course;
    isLessonSheetOpen.value = true;
};

// 5. Modale de visualisation des participants (icône 👁️)
const isAttendeesModalOpen = ref(false);
const selectedLessonForAttendees = ref(null);
const selectedCourseForAttendees = ref(null);

const handleViewAttendees = ({ lesson, course }) => {
    selectedLessonForAttendees.value = lesson;
    selectedCourseForAttendees.value = course;
    isAttendeesModalOpen.value = true;
};

// 6. Dialogue de confirmation de suppression d'un cours avec case à cocher
const isDeleteDialogOpen = ref(false);
const courseToDelete = ref(null);
const isConfirmDeleteChecked = ref(false);
const isDeleting = ref(false);

const handlePromptDeleteCourse = (course) => {
    courseToDelete.value = course;
    isConfirmDeleteChecked.value = false;
    isDeleteDialogOpen.value = true;
};

const executeDeleteCourse = () => {
    if (!courseToDelete.value || !isConfirmDeleteChecked.value) return;

    isDeleting.value = true;
    router.delete(route("courses.delete", { course: courseToDelete.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            courseToDelete.value = null;
            isConfirmDeleteChecked.value = false;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};
</script>

<template>
    <AdminLayout title="Gestion des cours & séances">
        <div class="space-y-6">
            <!-- ========================================================= -->
            <!-- 1. BREADCRUMB & EN-TÊTE DE LA PAGE                        -->
            <!-- ========================================================= -->
            <div class="space-y-3">
                <!-- Breadcrumb -->
                <nav class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Link :href="route('dashboard.index')" class="hover:text-foreground transition-colors">
                        Tableau de bord
                    </Link>
                    <ChevronRight class="h-3.5 w-3.5 text-muted-foreground/60" />
                    <span class="font-semibold text-foreground">Cours & Séances</span>
                </nav>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-foreground">
                            Catalogue des cours & Séances
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Supervision du planning, des remplacements, des effectifs et des fiches descriptives.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button as-child size="sm" class="gap-1.5 shadow-sm">
                            <Link :href="route('courses.create')">
                                <Plus class="h-4 w-4" />
                                <span>Nouveau cours</span>
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 2. BARRE DE RECHERCHE & FILTRES CIBLÉS                    -->
            <!-- ========================================================= -->
            <CourseSearchFilters
                :filters="filters"
                :types="types"
                :years="years"
                @apply="handleApplyFilters"
            />

            <!-- ========================================================= -->
            <!-- 3. BARRE D'ÉTAT & OPTIONS DE TRI DIRECTES                 -->
            <!-- ========================================================= -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                <div class="text-xs text-muted-foreground font-medium">
                    {{ pluralize(courses.length, 'cours trouvé') }}
                </div>

                <!-- Contrôleur de tri au-dessus de la liste -->
                <div class="flex items-center gap-1 bg-card border rounded-lg p-1 shadow-2xs text-xs self-start sm:self-auto">
                    <span class="text-[11px] font-medium px-1.5 text-muted-foreground">Trier par :</span>

                    <!-- 1. Plus ancien d'abord (Défaut) -->
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md transition-colors font-medium flex items-center gap-1.5"
                        :class="filters.sort === 'oldest' ? 'bg-primary/10 text-primary shadow-2xs font-semibold' : 'text-muted-foreground hover:text-foreground'"
                        @click="handleSortChange('oldest')"
                    >
                        <ArrowUp class="h-3 w-3" />
                        <span>Plus ancien</span>
                    </button>

                    <!-- 2. Plus récent d'abord -->
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md transition-colors font-medium flex items-center gap-1.5"
                        :class="filters.sort === 'newest' ? 'bg-primary/10 text-primary shadow-2xs font-semibold' : 'text-muted-foreground hover:text-foreground'"
                        @click="handleSortChange('newest')"
                    >
                        <ArrowDown class="h-3 w-3" />
                        <span>Plus récent</span>
                    </button>

                    <!-- 3. Séance la plus proche -->
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md transition-colors font-medium flex items-center gap-1.5"
                        :class="filters.sort === 'nearest_lesson' ? 'bg-primary/10 text-primary shadow-2xs font-semibold' : 'text-muted-foreground hover:text-foreground'"
                        @click="handleSortChange('nearest_lesson')"
                    >
                        <CalendarClock class="h-3 w-3" />
                        <span>Séance proche</span>
                    </button>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 4. LISTE DES COURS EN ACCORDÉONS                          -->
            <!-- ========================================================= -->
            <div v-if="courses.length > 0">
                <Accordion type="multiple" class="space-y-4">
                    <CourseAccordionItem
                        v-for="course in courses"
                        :key="course.id"
                        :course="course"
                        @edit-course="handleEditCourse"
                        @edit-lesson="handleEditLesson"
                        @view-attendees="handleViewAttendees"
                        @delete-course="handlePromptDeleteCourse"
                    />
                </Accordion>
            </div>

            <!-- État vide -->
            <div
                v-else
                class="text-center py-16 px-4 rounded-xl border border-dashed bg-card/50 flex flex-col items-center justify-center space-y-3"
            >
                <div class="h-12 w-12 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                    <GraduationCap class="h-6 w-6" />
                </div>
                <div class="space-y-1">
                    <h3 class="font-bold text-base text-foreground">Aucun cours trouvé</h3>
                    <p class="text-xs text-muted-foreground max-w-sm">
                        Aucun cours ne correspond à vos critères de recherche. Essayez de réinitialiser les filtres ou créez un nouveau cours.
                    </p>
                </div>
                <Button size="sm" as-child class="gap-1.5 mt-2 shadow-xs">
                    <Link :href="route('courses.create')">
                        <Plus class="h-4 w-4" />
                        <span>Créer un cours</span>
                    </Link>
                </Button>
            </div>

            <!-- ========================================================= -->
            <!-- 5. MODALE D'ÉDITION DU COURS PARENT                       -->
            <!-- ========================================================= -->
            <CourseEditModal
                v-model:open="isCourseModalOpen"
                :course="selectedCourseForEdit"
                :instructors="instructors"
            />

            <!-- ========================================================= -->
            <!-- 6. TIROIR LATÉRAL D'ÉDITION DE SÉANCE                     -->
            <!-- ========================================================= -->
            <LessonEditSheet
                v-model:open="isLessonSheetOpen"
                :lesson="selectedLesson"
                :course="selectedCourseForLesson"
                :instructors="instructors"
            />

            <!-- ========================================================= -->
            <!-- 7. MODALE DES PARTICIPANTS INSCRITS (👁️ - ON DEMAND)     -->
            <!-- ========================================================= -->
            <LessonAttendeesModal
                v-model:open="isAttendeesModalOpen"
                :lesson="selectedLessonForAttendees"
                :course="selectedCourseForAttendees"
            />

            <!-- ========================================================= -->
            <!-- 8. DIALOGUE DE SUPPRESSION AVEC CASE À COCHER             -->
            <!-- ========================================================= -->
            <Dialog :open="isDeleteDialogOpen" @update:open="(val) => (isDeleteDialogOpen = val)">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2 text-destructive">
                            <AlertTriangle class="h-5 w-5" />
                            <span>Supprimer ce cours ?</span>
                        </DialogTitle>
                        <DialogDescription class="text-xs pt-2 space-y-2">
                            <p>
                                Êtes-vous sûr de vouloir supprimer le cours
                                <strong class="text-foreground">« {{ courseToDelete?.name }} »</strong> ?
                                Cette action supprimera également ses
                                <strong class="text-foreground">{{ courseToDelete?.stats?.total_lessons }} séances</strong> associées.
                            </p>
                        </DialogDescription>
                    </DialogHeader>

                    <!-- Case à cocher de sécurité obligatoire -->
                    <div class="p-3 rounded-lg border border-destructive/30 bg-destructive/5 space-y-2">
                        <div class="flex items-start gap-2.5">
                            <Checkbox
                                id="confirm_course_delete_checkbox"
                                :checked="isConfirmDeleteChecked"
                                v-model:checked="isConfirmDeleteChecked"
                            />
                            <Label
                                for="confirm_course_delete_checkbox"
                                class="text-xs font-semibold text-destructive cursor-pointer select-none leading-relaxed"
                            >
                                Je confirme la suppression définitive de ce cours et de l'ensemble de son calendrier.
                            </Label>
                        </div>
                    </div>

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
                            :disabled="isDeleting || !isConfirmDeleteChecked"
                            class="gap-1.5 font-semibold"
                            @click="executeDeleteCourse"
                        >
                            <Loader2 v-if="isDeleting" class="h-4 w-4 animate-spin" />
                            <Trash2 v-else class="h-4 w-4" />
                            <span>Confirmer la suppression</span>
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
