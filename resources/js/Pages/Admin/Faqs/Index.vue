<script setup>
import { ref } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";

import AdminLayout from "@/Layouts/AdminLayout.vue";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Checkbox } from "@/Components/ui/checkbox";
import { Badge } from "@/Components/ui/badge";

import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";

import {
    CircleHelp,
    Plus,
    Pencil,
    Trash2,
    Eye,
    EyeOff,
    ChevronRight,
    Loader2,
    ExternalLink,
    Save,
} from "lucide-vue-next";

const props = defineProps({
    faqs: {
        type: Array,
        default: () => [],
    },

    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            inactive: 0,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Création / édition
|--------------------------------------------------------------------------
*/

const isFormOpen = ref(false);
const editingFaq = ref(null);

const form = useForm({
    question: "",
    answer: "",
    position: 0,
    is_active: true,
});

const openCreate = () => {
    editingFaq.value = null;

    form.reset();
    form.clearErrors();

    form.position =
        props.faqs.length > 0
            ? Math.max(...props.faqs.map((faq) => Number(faq.position) || 0)) +
              1
            : 1;

    form.is_active = true;

    isFormOpen.value = true;
};

const openEdit = (faq) => {
    editingFaq.value = faq;

    form.clearErrors();

    form.question = faq.question;
    form.answer = faq.answer;
    form.position = Number(faq.position);
    form.is_active = Boolean(faq.is_active);

    isFormOpen.value = true;
};

const closeForm = () => {
    if (form.processing) return;

    isFormOpen.value = false;
    editingFaq.value = null;

    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    const options = {
        preserveScroll: true,

        onSuccess: () => {
            closeForm();
        },
    };

    if (editingFaq.value) {
        form.patch(
            route("faqs.update", {
                faq: editingFaq.value.id,
            }),
            options,
        );

        return;
    }

    form.post(route("faqs.store"), options);
};

/*
|--------------------------------------------------------------------------
| Suppression
|--------------------------------------------------------------------------
*/

const faqToDelete = ref(null);
const isDeleteOpen = ref(false);
const isDeleting = ref(false);

const openDelete = (faq) => {
    faqToDelete.value = faq;
    isDeleteOpen.value = true;
};

const closeDelete = () => {
    if (isDeleting.value) return;

    faqToDelete.value = null;
    isDeleteOpen.value = false;
};

const confirmDelete = () => {
    if (!faqToDelete.value) return;

    isDeleting.value = true;

    router.delete(
        route("faqs.destroy", {
            faq: faqToDelete.value.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                isDeleteOpen.value = false;
                faqToDelete.value = null;
            },

            onFinish: () => {
                isDeleting.value = false;
            },
        },
    );
};
</script>

<template>
    <AdminLayout title="Gestion de la FAQ">
        <Head title="Gestion de la FAQ" />

        <div class="space-y-6">
            <!-- ===================================================== -->
            <!-- BREADCRUMB                                           -->
            <!-- ===================================================== -->

            <nav
                class="flex items-center gap-1.5 text-xs text-muted-foreground"
            >
                <Link
                    :href="route('dashboard.index')"
                    class="hover:text-foreground transition-colors"
                >
                    Tableau de bord
                </Link>

                <ChevronRight class="h-3.5 w-3.5 text-muted-foreground/60" />

                <span class="font-semibold text-foreground"> FAQ </span>
            </nav>

            <!-- ===================================================== -->
            <!-- HEADER                                                -->
            <!-- ===================================================== -->

            <div
                class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <div
                            class="h-9 w-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center"
                        >
                            <CircleHelp class="h-5 w-5" />
                        </div>

                        <div>
                            <h1
                                class="text-2xl font-bold tracking-tight text-foreground"
                            >
                                Questions fréquentes
                            </h1>

                            <p class="text-sm text-muted-foreground mt-0.5">
                                Gérez les questions affichées sur la FAQ
                                publique.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" as-child class="group">
                        <a
                            :href="route('faq.index')"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <ExternalLink class="h-4 w-4 mr-1.5" />

                            Voir la page
                        </a>
                    </Button>

                    <Button
                        size="sm"
                        class="gap-1.5 shadow-sm"
                        @click="openCreate"
                    >
                        <Plus class="h-4 w-4" />

                        Nouvelle question
                    </Button>
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- STATS                                                 -->
            <!-- ===================================================== -->

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-xl border bg-card p-4 shadow-xs">
                    <p class="text-xs font-medium text-muted-foreground">
                        Total
                    </p>

                    <p class="text-2xl font-bold tracking-tight mt-1">
                        {{ stats.total }}
                    </p>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-xs">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium text-muted-foreground">
                            Publiées
                        </p>

                        <Eye class="h-4 w-4 text-emerald-600" />
                    </div>

                    <p class="text-2xl font-bold tracking-tight mt-1">
                        {{ stats.active }}
                    </p>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-xs">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium text-muted-foreground">
                            Masquées
                        </p>

                        <EyeOff class="h-4 w-4 text-muted-foreground" />
                    </div>

                    <p class="text-2xl font-bold tracking-tight mt-1">
                        {{ stats.inactive }}
                    </p>
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- LISTE                                                 -->
            <!-- ===================================================== -->

            <div v-if="faqs.length > 0" class="space-y-3">
                <article
                    v-for="faq in faqs"
                    :key="faq.id"
                    class="rounded-xl border bg-card shadow-xs overflow-hidden transition-colors hover:border-primary/20"
                >
                    <div
                        class="p-4 sm:p-5 flex flex-col md:flex-row md:items-start justify-between gap-4"
                    >
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            <!-- Position -->

                            <div
                                class="h-9 min-w-9 px-2 rounded-lg bg-muted flex items-center justify-center text-xs font-bold text-muted-foreground shrink-0"
                            >
                                {{ faq.position }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2
                                        class="font-semibold text-sm sm:text-base text-foreground"
                                    >
                                        {{ faq.question }}
                                    </h2>

                                    <Badge
                                        variant="outline"
                                        :class="
                                            faq.is_active
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                : 'bg-muted text-muted-foreground border-border'
                                        "
                                        class="text-[10px]"
                                    >
                                        <Eye
                                            v-if="faq.is_active"
                                            class="h-3 w-3 mr-1"
                                        />

                                        <EyeOff v-else class="h-3 w-3 mr-1" />

                                        {{
                                            faq.is_active
                                                ? "Publiée"
                                                : "Masquée"
                                        }}
                                    </Badge>
                                </div>

                                <p
                                    class="mt-2 text-sm text-muted-foreground whitespace-pre-line leading-relaxed"
                                >
                                    {{ faq.answer }}
                                </p>

                                <p
                                    class="mt-3 text-[10px] text-muted-foreground/70"
                                >
                                    Dernière modification :
                                    {{ faq.updated_at || "—" }}
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->

                        <div
                            class="flex items-center gap-1 shrink-0 self-end md:self-start"
                        >
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-8 gap-1.5 text-xs"
                                @click="openEdit(faq)"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                                Modifier
                            </Button>

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                                title="Supprimer"
                                @click="openDelete(faq)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Vide -->

            <div
                v-else
                class="rounded-xl border border-dashed bg-card/50 py-16 px-4 flex flex-col items-center text-center"
            >
                <div
                    class="h-12 w-12 rounded-full bg-primary/10 text-primary flex items-center justify-center"
                >
                    <CircleHelp class="h-6 w-6" />
                </div>

                <h3 class="font-bold text-base mt-3">Aucune question</h3>

                <p class="text-xs text-muted-foreground max-w-sm mt-1">
                    Créez votre première question pour commencer à remplir la
                    FAQ publique.
                </p>

                <Button size="sm" class="mt-4 gap-1.5" @click="openCreate">
                    <Plus class="h-4 w-4" />

                    Ajouter une question
                </Button>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- DIALOG CRÉATION / ÉDITION                                 -->
        <!-- ========================================================= -->

        <Dialog
            :open="isFormOpen"
            @update:open="
                (value) => {
                    if (!value) closeForm();
                }
            "
        >
            <DialogContent class="sm:max-w-2xl bg-background">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <CircleHelp class="h-5 w-5 text-primary" />

                        {{
                            editingFaq
                                ? "Modifier la question"
                                : "Nouvelle question"
                        }}
                    </DialogTitle>

                    <DialogDescription>
                        {{
                            editingFaq
                                ? "Modifiez le contenu ou la visibilité de cette question."
                                : "Ajoutez une question et sa réponse à la FAQ du site."
                        }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-5 pt-2" @submit.prevent="submitForm">
                    <!-- Question -->

                    <div class="space-y-2">
                        <Label for="faq_question"> Question </Label>

                        <Input
                            id="faq_question"
                            v-model="form.question"
                            type="text"
                            maxlength="500"
                            placeholder="Ex. Dois-je apporter mon propre matériel ?"
                            class="bg-background"
                        />

                        <div class="flex items-center justify-between gap-3">
                            <p
                                v-if="form.errors.question"
                                class="text-xs text-destructive font-medium"
                            >
                                {{ form.errors.question }}
                            </p>

                            <span
                                class="text-[10px] text-muted-foreground ml-auto"
                            >
                                {{ form.question.length }}/500
                            </span>
                        </div>
                    </div>

                    <!-- Réponse -->

                    <div class="space-y-2">
                        <Label for="faq_answer"> Réponse </Label>

                        <textarea
                            id="faq_answer"
                            v-model="form.answer"
                            rows="8"
                            maxlength="20000"
                            placeholder="Rédigez ici la réponse qui sera affichée aux visiteurs..."
                            class="flex min-h-[180px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring resize-y"
                        />

                        <p
                            v-if="form.errors.answer"
                            class="text-xs text-destructive font-medium"
                        >
                            {{ form.errors.answer }}
                        </p>
                    </div>

                    <!-- Options -->

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="faq_position">
                                Ordre d'affichage
                            </Label>

                            <Input
                                id="faq_position"
                                v-model.number="form.position"
                                type="number"
                                min="0"
                                max="9999"
                            />

                            <p class="text-[11px] text-muted-foreground">
                                Les nombres les plus petits apparaissent en
                                premier.
                            </p>

                            <p
                                v-if="form.errors.position"
                                class="text-xs text-destructive font-medium"
                            >
                                {{ form.errors.position }}
                            </p>
                        </div>

                        <div class="rounded-xl border bg-muted/20 p-4">
                            <div class="flex items-start gap-3">
                                <Checkbox
                                    id="faq_active"
                                    v-model="form.is_active"
                                />

                                <Label
                                    for="faq_active"
                                    class="cursor-pointer space-y-1"
                                >
                                    <span class="block font-semibold">
                                        Publier cette question
                                    </span>

                                    <span
                                        class="block text-xs text-muted-foreground font-normal leading-relaxed"
                                    >
                                        Si désactivée, elle reste enregistrée
                                        dans l'admin mais disparaît du site.
                                    </span>
                                </Label>
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="gap-2 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            @click="closeForm"
                        >
                            Annuler
                        </Button>

                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="gap-1.5"
                        >
                            <Loader2
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin"
                            />

                            <Save v-else class="h-4 w-4" />

                            {{
                                form.processing
                                    ? "Enregistrement..."
                                    : editingFaq
                                      ? "Enregistrer"
                                      : "Créer la question"
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ========================================================= -->
        <!-- DIALOG SUPPRESSION                                        -->
        <!-- ========================================================= -->

        <Dialog
            :open="isDeleteOpen"
            @update:open="
                (value) => {
                    if (!value) closeDelete();
                }
            "
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle
                        class="flex items-center gap-2 text-destructive"
                    >
                        <Trash2 class="h-5 w-5" />

                        Supprimer cette question ?
                    </DialogTitle>

                    <DialogDescription>
                        Cette suppression est définitive.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="faqToDelete"
                    class="rounded-lg border bg-muted/30 p-3 text-sm"
                >
                    <p class="font-semibold">
                        {{ faqToDelete.question }}
                    </p>
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        variant="outline"
                        :disabled="isDeleting"
                        @click="closeDelete"
                    >
                        Annuler
                    </Button>

                    <Button
                        variant="destructive"
                        :disabled="isDeleting"
                        class="gap-1.5"
                        @click="confirmDelete"
                    >
                        <Loader2
                            v-if="isDeleting"
                            class="h-4 w-4 animate-spin"
                        />

                        <Trash2 v-else class="h-4 w-4" />

                        {{ isDeleting ? "Suppression..." : "Supprimer" }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
