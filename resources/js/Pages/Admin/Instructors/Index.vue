<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { pluralize } from "@/Utils/formatters";

import AdminLayout from "@/Layouts/AdminLayout.vue";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Checkbox } from "@/Components/ui/checkbox";
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
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";

import {
    Breadcrumb,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbSeparator,
    BreadcrumbPage,
} from "@/Components/ui/breadcrumb";

import {
    Plus,
    Search,
    Pencil,
    Trash2,
    Users,
    RotateCcw,
    X,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    ChevronDown,
} from "lucide-vue-next";

const props = defineProps({
    instructors: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
});

// Recherche et pagination
const search = ref(props.filters.search || "");
const selectedType = ref(props.filters.type || "all");
const perPage = ref(Number(props.instructors.per_page) || 25);
const filtering = ref(false);

let searchTimeout = null;
let cancelSearch = null;
let searchRevision = 0;

const hasActiveFilters = computed(() => {
    return search.value !== "" || selectedType.value !== "all";
});

watch(
    () => props.instructors.per_page,
    (value) => {
        perPage.value = Number(value);
    },
);

watch(
    () => props.filters,
    (filters) => {
        // Préserver une saisie plus récente que la réponse reçue.
        if (filtering.value || searchTimeout !== null) return;

        search.value = filters.search || "";
        selectedType.value = filters.type || "all";
    },
);

function cancelPendingSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = null;

    searchRevision += 1;

    cancelSearch?.cancel();
    cancelSearch = null;
    filtering.value = false;
}

function applyFilters(page = 1) {
    cancelPendingSearch();

    const revision = searchRevision;

    router.get(
        route("instructors.index"),
        {
            search: search.value.trim() || undefined,
            type: selectedType.value === "all" ? undefined : selectedType.value,
            per_page: perPage.value,
            page,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onCancelToken: (token) => {
                cancelSearch = token;
            },
            onStart: () => {
                filtering.value = true;
            },
            onFinish: () => {
                if (revision !== searchRevision) return;

                filtering.value = false;
                cancelSearch = null;
            },
        },
    );
}

function handleSearchInput(value) {
    search.value = String(value ?? "");
    cancelPendingSearch();

    searchTimeout = setTimeout(() => {
        searchTimeout = null;
        applyFilters();
    }, 300);
}

function clearSearch() {
    search.value = "";
    applyFilters();
}

function changeType(value) {
    selectedType.value = value;
    applyFilters();
}

function resetFilters() {
    search.value = "";
    selectedType.value = "all";
    applyFilters();
}

function changePage(page) {
    if (filtering.value || page < 1 || page > props.instructors.last_page) {
        return;
    }

    applyFilters(page);
}

function changePerPage(value) {
    perPage.value = Number(value);
    applyFilters();
}

onUnmounted(cancelPendingSearch);

// Affichage des fiches
const fullName = (instructor) => {
    return `${instructor.first_name} ${instructor.last_name}`.trim();
};

const initials = (instructor) => {
    return (
        `${instructor.first_name?.[0] || ""}` +
        `${instructor.last_name?.[0] || ""}`
    ).toUpperCase();
};

const isUsed = (instructor) => {
    return instructor.courses_count > 0 || instructor.lessons_count > 0;
};

const expandedBios = ref(new Set());

function toggleBio(id) {
    const expanded = new Set(expandedBios.value);

    if (expanded.has(id)) {
        expanded.delete(id);
    } else {
        expanded.add(id);
    }

    expandedBios.value = expanded;
}

// Création et modification
const editorOpen = ref(false);
const editorForm = ref(null);
const editingId = ref(null);

const form = useForm({
    first_name: "",
    last_name: "",
    type: "staff",
    bio: "",
});

function setEditorOpen(open) {
    if (form.processing) return;
    editorOpen.value = open;
}

function openEditor(instructor = null) {
    cancelPendingSearch();

    form.reset();
    form.clearErrors();

    editingId.value = instructor?.id ?? null;
    form.first_name = instructor?.first_name ?? "";
    form.last_name = instructor?.last_name ?? "";
    form.type = instructor?.type ?? "staff";
    form.bio = instructor?.bio ?? "";

    editorOpen.value = true;
}

function submit() {
    if (form.processing) return;

    const editing = editingId.value !== null;
    const name = fullName(form);

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            editorOpen.value = false;

            toast.success(
                editing ? "Fiche mise à jour" : "Instructeur ajouté",
                {
                    description: editing
                        ? `La fiche de ${name} a été mise à jour.`
                        : `La fiche de ${name} a été créée.`,
                },
            );
        },
        onError: () => {
            nextTick(() => {
                editorForm.value
                    ?.querySelector('[aria-invalid="true"]')
                    ?.focus();
            });
        },
    };

    if (editing) {
        form.patch(route("instructors.update", editingId.value), options);
    } else {
        form.post(route("instructors.store"), options);
    }
}

// Suppression
const deletionOpen = ref(false);
const selectedInstructor = ref(null);
const deleteConfirmed = ref(false);
const deleteForm = useForm({});

function setDeletionOpen(open) {
    if (deleteForm.processing) return;

    deletionOpen.value = open;

    if (!open) {
        deleteConfirmed.value = false;
    }
}

function setDeleteConfirmed(value) {
    deleteConfirmed.value = value === true;
}

function openDeletion(instructor) {
    cancelPendingSearch();

    selectedInstructor.value = instructor;
    deleteConfirmed.value = false;
    deleteForm.clearErrors();

    deletionOpen.value = true;
}

function destroy() {
    if (
        !selectedInstructor.value ||
        !deleteConfirmed.value ||
        deleteForm.processing
    ) {
        return;
    }

    const name = fullName(selectedInstructor.value);

    deleteForm.delete(
        route("instructors.destroy", selectedInstructor.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                deletionOpen.value = false;
                deleteConfirmed.value = false;

                toast.success("Fiche supprimée", {
                    description: `La fiche de ${name} a été supprimée.`,
                });

                // Revenir à une page existante si celle-ci est devenue vide.
                if (
                    props.instructors.data.length === 0 &&
                    props.instructors.current_page > 1
                ) {
                    router.get(
                        route("instructors.index"),
                        {
                            ...props.filters,
                            per_page: perPage.value,
                            page: props.instructors.current_page - 1,
                        },
                        {
                            preserveScroll: true,
                            replace: true,
                        },
                    );
                }
            },
        },
    );
}

const overflowingBios = ref({});
const bioObservers = new WeakMap();

function measureBio(element, id) {
    const lineHeight = parseFloat(getComputedStyle(element).lineHeight);

    if (!Number.isFinite(lineHeight)) return;

    // Fonctionne aussi lorsque la biographie est dépliée.
    const overflows = element.scrollHeight > lineHeight * 3 + 1;

    if (overflowingBios.value[id] !== overflows) {
        overflowingBios.value[id] = overflows;
    }
}

const vBioOverflow = {
    mounted(element, binding) {
        const observer = new ResizeObserver(() => {
            measureBio(element, binding.value);
        });

        bioObservers.set(element, observer);
        observer.observe(element);
        measureBio(element, binding.value);
    },

    updated(element, binding) {
        measureBio(element, binding.value);
    },

    beforeUnmount(element) {
        bioObservers.get(element)?.disconnect();
        bioObservers.delete(element);
    },
};
</script>

<template>
    <AdminLayout title="Instructeurs">
        <div class="space-y-6">
            <!-- En-tête et breadcrumb -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <Breadcrumb>
                        <BreadcrumbList class="gap-1.5 text-xs">
                            <BreadcrumbItem>
                                <BreadcrumbLink as-child>
                                    <Link :href="route('dashboard.index')">
                                        Tableau de bord
                                    </Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>

                            <BreadcrumbSeparator />

                            <BreadcrumbItem>
                                <BreadcrumbPage class="font-semibold">
                                    Instructeurs
                                </BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>

                    <h2 class="mt-3 text-2xl font-semibold tracking-tight">
                        Gestion des instructeurs
                    </h2>

                    <p class="mt-2 text-sm text-muted-foreground">
                        Gérez les titulaires et les intervenants externes.
                    </p>
                </div>

                <Button class="shrink-0 gap-2" @click="openEditor()">
                    <Plus class="h-4 w-4" />
                    Ajouter un instructeur
                </Button>
            </div>

            <!-- Recherche automatique et filtre -->
            <div
                class="flex flex-col justify-between gap-3.5 rounded-xl border bg-card p-4 shadow-xs lg:flex-row lg:items-center"
            >
                <div class="relative min-w-0 flex-1">
                    <Search
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />

                    <Input
                        :model-value="search"
                        aria-label="Rechercher un instructeur par prénom ou nom"
                        maxlength="255"
                        placeholder="Rechercher un instructeur…"
                        class="h-9 bg-background pl-9 pr-9 text-xs"
                        @update:model-value="handleSearchInput"
                    />

                    <Button
                        v-if="search"
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="absolute right-1 top-1/2 h-7 w-7 -translate-y-1/2 text-muted-foreground"
                        aria-label="Effacer la recherche"
                        @click="clearSearch"
                    >
                        <X class="h-3.5 w-3.5" />
                    </Button>
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:flex-nowrap">
                    <Select
                        :model-value="selectedType"
                        @update:model-value="changeType"
                    >
                        <SelectTrigger
                            class="h-9 w-full bg-background text-xs sm:w-[190px]"
                            aria-label="Type d’instructeur"
                        >
                            <SelectValue placeholder="Tous les types" />
                        </SelectTrigger>

                        <SelectContent align="end">
                            <SelectItem value="all" class="text-xs">
                                Tous les types
                            </SelectItem>

                            <SelectItem value="staff" class="text-xs">
                                Titulaire
                            </SelectItem>

                            <SelectItem value="external" class="text-xs">
                                Intervenant externe
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Button
                        v-if="hasActiveFilters"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-9 gap-1 px-2 text-xs text-muted-foreground"
                        @click="resetFilters"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        Effacer
                    </Button>
                </div>
            </div>

            <div
                class="flex items-center justify-between text-xs text-muted-foreground"
                aria-live="polite"
            >
                <p>{{ pluralize(instructors.total, "résultat") }}</p>

                <p>{{ filtering ? "Recherche…" : "Tri par nom" }}</p>
            </div>

            <!-- Cartes -->
            <div
                v-if="instructors.data.length"
                class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="instructor in instructors.data"
                    :key="instructor.id"
                    class="flex flex-col rounded-xl border bg-card p-5 shadow-2xs"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary"
                            aria-hidden="true"
                        >
                            {{ initials(instructor) }}
                        </div>

                        <div class="min-w-0">
                            <h3 class="break-words font-semibold">
                                {{ fullName(instructor) }}
                            </h3>

                            <Badge
                                variant="secondary"
                                class="mt-1.5 rounded-full text-xs"
                                :class="
                                    instructor.type === 'staff'
                                        ? 'bg-primary/10 text-primary'
                                        : 'bg-muted text-muted-foreground'
                                "
                            >
                                {{
                                    instructor.type === "staff"
                                        ? "Titulaire"
                                        : "Intervenant externe"
                                }}
                            </Badge>
                        </div>
                    </div>

                    <ScrollArea
                        v-if="expandedBios.has(instructor.id)"
                        class="mt-4 h-48"
                    >
                        <p
                            :id="`instructor-bio-${instructor.id}`"
                            v-bio-overflow="instructor.id"
                            class="whitespace-pre-line break-words pr-4 text-sm leading-relaxed text-muted-foreground"
                        >
                            {{ instructor.bio }}
                        </p>
                    </ScrollArea>

                    <p
                        v-else
                        :id="`instructor-bio-${instructor.id}`"
                        v-bio-overflow="instructor.id"
                        class="mt-4 line-clamp-3 whitespace-pre-line break-words text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ instructor.bio }}
                    </p>
                    <Button
                        v-if="overflowingBios[instructor.id]"
                        type="button"
                        variant="link"
                        size="sm"
                        class="mt-1 h-auto gap-1 self-start px-0 py-1 text-xs"
                        :aria-expanded="expandedBios.has(instructor.id)"
                        :aria-controls="`instructor-bio-${instructor.id}`"
                        @click="toggleBio(instructor.id)"
                    >
                        {{
                            expandedBios.has(instructor.id)
                                ? "Voir moins"
                                : "Voir plus"
                        }}

                        <ChevronUp
                            v-if="expandedBios.has(instructor.id)"
                            class="h-3.5 w-3.5"
                            aria-hidden="true"
                        />
                        <ChevronDown
                            v-else
                            class="h-3.5 w-3.5"
                            aria-hidden="true"
                        />
                    </Button>

                    <div class="mt-auto pt-5">
                        <p
                            class="border-t pt-3 text-xs leading-relaxed text-muted-foreground"
                        >
                            {{
                                pluralize(
                                    instructor.courses_count,
                                    "cours",
                                    "cours",
                                )
                            }}
                            ·
                            {{
                                pluralize(
                                    instructor.lessons_count,
                                    "séance avec référence directe",
                                    "séances avec référence directe",
                                )
                            }}
                        </p>

                        <div
                            class="mt-3 flex items-center justify-between gap-2"
                        >
                            <Button
                                variant="outline"
                                size="sm"
                                class="gap-2"
                                :aria-label="`Modifier la fiche de ${fullName(instructor)}`"
                                @click="openEditor(instructor)"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                                Modifier
                            </Button>

                            <Button
                                variant="ghost"
                                size="sm"
                                class="gap-2 text-destructive"
                                :disabled="isUsed(instructor)"
                                :aria-label="`Supprimer la fiche de ${fullName(instructor)}`"
                                @click="openDeletion(instructor)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                                Supprimer
                            </Button>
                        </div>

                        <p
                            v-if="isUsed(instructor)"
                            class="mt-2 text-xs text-muted-foreground"
                        >
                            Suppression bloquée : instructeur assigné.
                        </p>
                    </div>
                </article>
            </div>

            <!-- État vide -->
            <div
                v-else
                class="rounded-xl border border-dashed bg-card px-6 py-14 text-center"
            >
                <Users class="mx-auto mb-4 h-8 w-8 text-muted-foreground" />

                <h3 class="font-semibold">
                    {{
                        hasActiveFilters
                            ? "Aucun instructeur ne correspond"
                            : "Votre équipe commence ici"
                    }}
                </h3>

                <p class="mt-2 text-sm text-muted-foreground">
                    {{
                        hasActiveFilters
                            ? "Essayez un autre nom ou élargissez le filtre."
                            : "Ajoutez une première fiche pour compléter le répertoire."
                    }}
                </p>

                <Button
                    class="mt-5"
                    variant="outline"
                    @click="hasActiveFilters ? resetFilters() : openEditor()"
                >
                    {{
                        hasActiveFilters
                            ? "Réinitialiser les filtres"
                            : "Ajouter un instructeur"
                    }}
                </Button>
            </div>

            <!-- Pagination inspirée du répertoire des utilisateurs -->
            <nav
                class="flex flex-col items-center justify-between gap-3 pt-1 text-xs text-muted-foreground sm:flex-row"
                aria-label="Pagination des instructeurs"
            >
                <div class="flex items-center gap-2">
                    <Label
                        for="instructors-per-page"
                        class="text-xs font-normal"
                    >
                        Afficher
                    </Label>

                    <Select
                        :model-value="String(perPage)"
                        :disabled="filtering"
                        @update:model-value="changePerPage"
                    >
                        <SelectTrigger
                            id="instructors-per-page"
                            class="h-8 w-16 cursor-pointer bg-background text-xs"
                        >
                            <SelectValue />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem
                                v-for="size in [10, 25, 50, 100]"
                                :key="size"
                                :value="String(size)"
                                class="cursor-pointer text-xs"
                            >
                                {{ size }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <span>
                        par page ·
                        <strong>{{ instructors.total }}</strong>
                        {{
                            pluralize(
                                instructors.total,
                                "résultat",
                                null,
                                false,
                            )
                        }}
                    </span>
                </div>

                <div class="flex items-center gap-1.5">
                    <span>
                        Page
                        <strong>{{ instructors.current_page }}</strong>
                        sur
                        <strong>{{ instructors.last_page || 1 }}</strong>
                    </span>

                    <div class="ml-2 flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="icon"
                            class="h-8 w-8 cursor-pointer"
                            aria-label="Page précédente"
                            :disabled="
                                filtering || instructors.current_page <= 1
                            "
                            @click="changePage(instructors.current_page - 1)"
                        >
                            <ChevronLeft class="h-3.5 w-3.5" />
                        </Button>

                        <Button
                            variant="outline"
                            size="icon"
                            class="h-8 w-8 cursor-pointer"
                            aria-label="Page suivante"
                            :disabled="
                                filtering ||
                                instructors.current_page >=
                                    instructors.last_page
                            "
                            @click="changePage(instructors.current_page + 1)"
                        >
                            <ChevronRight class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Modale de création / modification -->
        <Dialog :open="editorOpen" @update:open="setEditorOpen">
            <DialogContent
                class="max-h-[90dvh] overflow-y-auto sm:max-w-xl"
                @escape-key-down="form.processing && $event.preventDefault()"
                @interact-outside="form.processing && $event.preventDefault()"
            >
                <form
                    ref="editorForm"
                    class="space-y-5"
                    @submit.prevent="submit"
                >
                    <DialogHeader>
                        <DialogTitle>
                            {{
                                editingId !== null
                                    ? "Modifier la fiche"
                                    : "Ajouter un instructeur"
                            }}
                        </DialogTitle>

                        <DialogDescription>
                            Identité, statut et présentation. Tous les champs
                            sont obligatoires.
                        </DialogDescription>
                    </DialogHeader>

                    <fieldset :disabled="form.processing" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <Label
                                    for="instructor-first-name"
                                    class="mb-1.5 block text-sm font-medium"
                                >
                                    Prénom
                                </Label>

                                <Input
                                    id="instructor-first-name"
                                    v-model="form.first_name"
                                    required
                                    maxlength="255"
                                    autocomplete="given-name"
                                    autofocus
                                    :aria-invalid="!!form.errors.first_name"
                                    aria-describedby="first-name-error"
                                />

                                <p
                                    id="first-name-error"
                                    class="mt-1 text-xs text-destructive"
                                    role="alert"
                                >
                                    {{ form.errors.first_name }}
                                </p>
                            </div>

                            <div>
                                <Label
                                    for="instructor-last-name"
                                    class="mb-1.5 block text-sm font-medium"
                                >
                                    Nom
                                </Label>

                                <Input
                                    id="instructor-last-name"
                                    v-model="form.last_name"
                                    required
                                    maxlength="255"
                                    autocomplete="family-name"
                                    :aria-invalid="!!form.errors.last_name"
                                    aria-describedby="last-name-error"
                                />

                                <p
                                    id="last-name-error"
                                    class="mt-1 text-xs text-destructive"
                                    role="alert"
                                >
                                    {{ form.errors.last_name }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <Label
                                for="instructor-type"
                                class="mb-1.5 block text-sm font-medium"
                            >
                                Type d’instructeur
                            </Label>

                            <Select
                                v-model="form.type"
                                :disabled="form.processing"
                            >
                                <SelectTrigger
                                    id="instructor-type"
                                    class="w-full"
                                    :aria-invalid="!!form.errors.type"
                                    aria-describedby="type-error"
                                >
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem value="staff">
                                        Titulaire
                                    </SelectItem>

                                    <SelectItem value="external">
                                        Intervenant externe
                                    </SelectItem>
                                </SelectContent>
                            </Select>

                            <p
                                id="type-error"
                                class="mt-1 text-xs text-destructive"
                                role="alert"
                            >
                                {{ form.errors.type }}
                            </p>
                        </div>

                        <div>
                            <Label
                                for="instructor-bio"
                                class="mb-1.5 block text-sm font-medium"
                            >
                                Biographie
                            </Label>

                            <textarea
                                id="instructor-bio"
                                v-model="form.bio"
                                class="flex min-h-40 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                rows="6"
                                required
                                maxlength="10000"
                                placeholder="Parcours, spécialités et approche pédagogique…"
                                :aria-invalid="!!form.errors.bio"
                                aria-describedby="bio-error bio-count"
                            ></textarea>

                            <div class="mt-1 flex justify-between gap-3">
                                <p
                                    id="bio-error"
                                    class="text-xs text-destructive"
                                    role="alert"
                                >
                                    {{ form.errors.bio }}
                                </p>

                                <p
                                    id="bio-count"
                                    class="shrink-0 text-xs tabular-nums text-muted-foreground"
                                >
                                    {{ form.bio.length }} / 10 000
                                </p>
                            </div>
                        </div>
                    </fieldset>

                    <DialogFooter class="gap-2 border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            @click="setEditorOpen(false)"
                        >
                            Annuler
                        </Button>

                        <Button type="submit" :disabled="form.processing">
                            {{
                                form.processing
                                    ? "Enregistrement…"
                                    : editingId !== null
                                      ? "Enregistrer"
                                      : "Créer la fiche"
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Modale de suppression -->
        <Dialog :open="deletionOpen" @update:open="setDeletionOpen">
            <DialogContent
                class="sm:max-w-lg"
                @escape-key-down="
                    deleteForm.processing && $event.preventDefault()
                "
                @interact-outside="
                    deleteForm.processing && $event.preventDefault()
                "
            >
                <form class="space-y-5" @submit.prevent="destroy">
                    <DialogHeader>
                        <DialogTitle> Supprimer cette fiche ? </DialogTitle>

                        <DialogDescription class="leading-relaxed">
                            La fiche de
                            <strong class="text-foreground">
                                {{
                                    selectedInstructor
                                        ? fullName(selectedInstructor)
                                        : ""
                                }}
                            </strong>
                            sera supprimée définitivement.
                        </DialogDescription>
                    </DialogHeader>

                    <div
                        class="flex items-start gap-3 rounded-lg border bg-muted/30 p-4"
                    >
                        <Checkbox
                            id="confirm-instructor-deletion"
                            :checked="deleteConfirmed"
                            :model-value="deleteConfirmed"
                            :disabled="deleteForm.processing"
                            @update:checked="setDeleteConfirmed"
                            @update:model-value="setDeleteConfirmed"
                        />

                        <Label
                            for="confirm-instructor-deletion"
                            class="cursor-pointer text-sm leading-relaxed"
                        >
                            Je confirme la suppression définitive de la fiche de
                            {{
                                selectedInstructor
                                    ? fullName(selectedInstructor)
                                    : ""
                            }}.
                        </Label>
                    </div>

                    <p
                        v-if="deleteForm.errors.instructor"
                        class="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive"
                        role="alert"
                    >
                        {{ deleteForm.errors.instructor }}
                    </p>

                    <DialogFooter class="gap-2 border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            autofocus
                            :disabled="deleteForm.processing"
                            @click="setDeletionOpen(false)"
                        >
                            Annuler
                        </Button>

                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="
                                !deleteConfirmed || deleteForm.processing
                            "
                        >
                            {{
                                deleteForm.processing
                                    ? "Suppression…"
                                    : "Supprimer la fiche"
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
