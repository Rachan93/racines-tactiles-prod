<script setup>
import { ref, computed } from "vue";
import { pluralize, formatPrice } from "@/Utils/formatters";

// Composants Shadcn UI
import {
    AccordionItem,
    AccordionTrigger,
    AccordionContent,
} from "@/Components/ui/accordion";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { ScrollArea } from "@/Components/ui/scroll-area";
import {
    Shell,
    Hand,
    Clock,
    User,
    AlertTriangle,
    XCircle,
    Pencil,
    Trash2,
    Users,
    Settings2,
    Sparkles,
    EyeOff,
    Eye,
} from "lucide-vue-next";

const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["edit-lesson", "edit-course", "delete-course", "view-attendees"]);

// Filtre local interne des séances
const selectedFilter = ref("all"); // 'all' | 'upcoming' | 'past' | 'overridden' | 'cancelled'

// Style spécifique par formule de cours (Collectif, Stage, Privé)
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

// Compteurs des onglets
const lessonsCount = computed(() => props.course.lessons?.length || 0);

const upcomingLessonsCount = computed(() => {
    return (props.course.lessons || []).filter((l) => !l.is_past && !l.is_cancelled).length;
});

const pastLessonsCount = computed(() => {
    return (props.course.lessons || []).filter((l) => l.is_past).length;
});

const overriddenLessonsCount = computed(() => {
    return (props.course.lessons || []).filter((l) => l.is_overridden).length;
});

const cancelledLessonsCount = computed(() => {
    return (props.course.lessons || []).filter((l) => l.is_cancelled).length;
});

// Filtrage réactif des séances
const filteredLessons = computed(() => {
    const list = props.course.lessons || [];
    if (selectedFilter.value === "upcoming") {
        return list.filter((l) => !l.is_past && !l.is_cancelled);
    }
    if (selectedFilter.value === "past") {
        return list.filter((l) => l.is_past);
    }
    if (selectedFilter.value === "overridden") {
        return list.filter((l) => l.is_overridden);
    }
    if (selectedFilter.value === "cancelled") {
        return list.filter((l) => l.is_cancelled);
    }
    return list;
});
</script>

<template>
    <AccordionItem
        :value="String(course.id)"
        class="border rounded-xl shadow-xs overflow-hidden transition-all duration-200"
        :class="course.is_past ? 'bg-card/60 opacity-85 hover:opacity-100 border-dashed border-border/80' : 'bg-card border-border'"
    >
        <!-- En-tête de l'Accordéon (Dépliable) -->
        <AccordionTrigger class="px-5 py-4 hover:bg-muted/30 hover:no-underline select-none">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full pr-4 text-left">
                <!-- Titre & Formule & Statuts -->
                <div class="space-y-1.5 min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-bold text-base text-foreground tracking-tight truncate">
                            {{ course.name }}
                        </h3>

                        <!-- Badge Couleur Spécifique au Type -->
                        <Badge variant="outline" :class="getTypeBadgeClass(course.type?.name)" class="text-xs font-semibold">
                            {{ course.type?.name }}
                        </Badge>

                        <!-- Badge Stage Mis en avant -->
                        <Badge v-if="course.is_featured" variant="outline" class="text-xs bg-amber-500/10 text-amber-700 border-amber-300 dark:text-amber-400 gap-1">
                            <Sparkles class="h-3 w-3" /> Mis en avant
                        </Badge>

                        <!-- Badge Cours Terminé -->
                        <Badge v-if="course.is_past" variant="outline" class="text-xs bg-muted text-muted-foreground border-border">
                            Terminé
                        </Badge>

                        <!-- Badge Cours Non publié -->
                        <Badge v-if="!course.is_active" variant="outline" class="text-xs bg-destructive/10 text-destructive border-destructive/30 gap-1">
                            <EyeOff class="h-3 w-3" /> Non publié
                        </Badge>

                        <Badge variant="outline" class="text-xs font-normal bg-background">
                            {{ pluralize(course.stats.total_lessons, 'séance') }}
                        </Badge>
                    </div>

                    <!-- Métadonnées principales -->
                    <div class="flex items-center gap-2 text-xs text-muted-foreground flex-wrap pt-0.5">
                        <span class="flex items-center gap-1 font-medium text-foreground">
                            <User class="h-3 w-3 text-muted-foreground" />
                            {{ course.instructor.name }}
                        </span>
                        <span>•</span>
                        <span class="flex items-center gap-1">
                            <Clock class="h-3 w-3" />
                            {{ course.default_start_time }} à {{ course.default_end_time }}
                        </span>
                        <span>•</span>
                        <span class="flex items-center gap-1">
                            <Users class="h-3 w-3 text-primary" />
                            Remplissage moyen : {{ course.stats.avg_registered }} / {{ course.default_spots_total }} élèves
                        </span>
                    </div>

                    <!-- Alertes épurées : Personnalisées & Annulées -->
                    <div
                        v-if="course.stats.overridden_lessons > 0 || course.stats.cancelled_lessons > 0"
                        class="flex items-center gap-3 text-[11px] pt-1"
                    >
                        <span
                            v-if="course.stats.overridden_lessons > 0"
                            class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 font-medium"
                        >
                            <AlertTriangle class="h-3 w-3" />
                            {{ pluralize(course.stats.overridden_lessons, 'séance personnalisée') }}
                        </span>
                        <span
                            v-if="course.stats.cancelled_lessons > 0"
                            class="inline-flex items-center gap-1 text-destructive font-medium"
                        >
                            <XCircle class="h-3 w-3" />
                            {{ pluralize(course.stats.cancelled_lessons, 'séance annulée') }}
                        </span>
                    </div>
                </div>
            </div>
        </AccordionTrigger>

        <!-- Contenu Déplié -->
        <AccordionContent class="px-5 pb-5 pt-2 border-t bg-muted/10 space-y-4">
            <!-- 1. Paramètres par défaut du cours parent -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-3 rounded-lg bg-background border text-xs">
                <div class="space-y-0.5">
                    <span class="text-muted-foreground text-[11px]">Période globale</span>
                    <p class="font-medium text-foreground">
                        Du {{ course.first_lesson_date_formatted }} au {{ course.end_date_formatted }}
                    </p>
                </div>
                <div class="space-y-0.5">
                    <span class="text-muted-foreground text-[11px]">Capacités par défaut</span>
                    <p class="font-medium text-foreground flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 text-sky-700 dark:text-sky-400">
                            <Shell class="h-3 w-3" /> {{ course.default_spots_max_wheel }}
                        </span>
                        <span class="inline-flex items-center gap-1 text-orange-700 dark:text-orange-400">
                            <Hand class="h-3 w-3" /> {{ course.default_spots_max_handbuilding }}
                        </span>
                    </p>
                </div>
                <div class="space-y-0.5">
                    <span class="text-muted-foreground text-[11px]">Tarif de base</span>
                    <p class="font-medium text-foreground">
                        {{ formatPrice(course.default_price) }}
                    </p>
                </div>
                <div class="space-y-0.5">
                    <span class="text-muted-foreground text-[11px]">Fréquence</span>
                    <p class="font-medium text-foreground">
                        {{ course.frequency === 7 ? 'Hebdomadaire (7j)' : `Tous les ${course.frequency} jours` }}
                    </p>
                </div>
            </div>

            <!-- 2. Filtres locaux des séances -->
            <div class="flex items-center justify-between gap-2 flex-wrap pt-1">
                <div class="flex items-center gap-1 bg-muted/60 p-1 rounded-lg border text-xs flex-wrap">
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md transition-colors font-medium"
                        :class="selectedFilter === 'all' ? 'bg-background shadow-xs text-foreground' : 'text-muted-foreground hover:text-foreground'"
                        @click="selectedFilter = 'all'"
                    >
                        Toutes ({{ lessonsCount }})
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md transition-colors font-medium"
                        :class="selectedFilter === 'upcoming' ? 'bg-background shadow-xs text-foreground' : 'text-muted-foreground hover:text-foreground'"
                        @click="selectedFilter = 'upcoming'"
                    >
                        À venir ({{ upcomingLessonsCount }})
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md transition-colors font-medium"
                        :class="selectedFilter === 'past' ? 'bg-background shadow-xs text-foreground' : 'text-muted-foreground hover:text-foreground'"
                        @click="selectedFilter = 'past'"
                    >
                        Passées ({{ pastLessonsCount }})
                    </button>
                    <button
                        v-if="overriddenLessonsCount > 0"
                        type="button"
                        class="px-2.5 py-1 rounded-md transition-colors font-medium flex items-center gap-1"
                        :class="selectedFilter === 'overridden' ? 'bg-background shadow-xs text-amber-600' : 'text-amber-600/80 hover:text-amber-600'"
                        @click="selectedFilter = 'overridden'"
                    >
                        <AlertTriangle class="h-3 w-3" />
                        Personnalisées ({{ overriddenLessonsCount }})
                    </button>
                    <button
                        v-if="cancelledLessonsCount > 0"
                        type="button"
                        class="px-2.5 py-1 rounded-md transition-colors font-medium flex items-center gap-1"
                        :class="selectedFilter === 'cancelled' ? 'bg-background shadow-xs text-destructive' : 'text-destructive/80 hover:text-destructive'"
                        @click="selectedFilter = 'cancelled'"
                    >
                        <XCircle class="h-3 w-3" />
                        Annulées ({{ cancelledLessonsCount }})
                    </button>
                </div>
            </div>

            <!-- 3. Tableau des séances sous ScrollArea h-[32rem] (h-128) -->
            <div class="border rounded-lg bg-background overflow-hidden shadow-xs">
                <ScrollArea class="h-[32rem] w-full overscroll-contain [&_[data-radix-scroll-area-viewport]]:overscroll-contain">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead class="bg-muted/60 text-muted-foreground sticky top-0 z-10 border-b">
                            <tr>
                                <th class="py-2.5 px-3 font-semibold w-10">#</th>
                                <th class="py-2.5 px-3 font-semibold">Date & Horaires</th>
                                <th class="py-2.5 px-3 font-semibold">Professeur</th>
                                <th class="py-2.5 px-3 font-semibold">Inscrits & Postes</th>
                                <th class="py-2.5 px-3 font-semibold">Statut</th>
                                <th class="py-2.5 px-3 font-semibold text-right pr-4">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="(lesson, idx) in filteredLessons"
                                :key="lesson.id"
                                class="hover:bg-muted/30 transition-colors"
                                :class="{
                                    'bg-destructive/5': lesson.is_cancelled,
                                    'bg-amber-500/5': lesson.is_overridden && !lesson.is_cancelled,
                                }"
                            >
                                <!-- Numéro séance -->
                                <td class="py-2.5 px-3 font-medium text-muted-foreground">
                                    #{{ String(idx + 1).padStart(2, '0') }}
                                </td>

                                <!-- Date & Horaires (Horaires en ambre si modifiés) -->
                                <td class="py-2.5 px-3">
                                    <div class="font-medium text-foreground">
                                        {{ lesson.date_formatted }}
                                    </div>
                                    <div
                                        class="text-[11px] flex items-center gap-1"
                                        :class="lesson.diffs?.time ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-muted-foreground'"
                                    >
                                        <Clock class="h-2.5 w-2.5" />
                                        {{ lesson.start_time }} - {{ lesson.end_time }}
                                    </div>
                                </td>

                                <!-- Professeur (Nom en ambre si remplacé/modifié) -->
                                <td class="py-2.5 px-3">
                                    <div
                                        class="font-medium"
                                        :class="lesson.diffs?.instructor ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-foreground'"
                                    >
                                        {{ lesson.instructor.name }}
                                    </div>
                                </td>

                                <!-- Colonne fusionnée : Inscrits + Tours + Modelage sur UNE SEULE LIGNE + Bouton 👁️ -->
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 text-xs flex-wrap font-medium">
                                            <!-- Total inscrits / max -->
                                            <span :class="lesson.spots.total_booked >= lesson.spots.total_max && lesson.spots.total_max > 0 ? 'text-destructive font-bold' : 'text-foreground'">
                                                {{ lesson.spots.total_booked }}/{{ lesson.spots.total_max }}
                                            </span>

                                            <span class="text-muted-foreground text-[10px]">•</span>

                                            <!-- Tours max (ambre uniquement si diffs.wheel est vrai) -->
                                            <span
                                                class="inline-flex items-center gap-0.5 text-[11px]"
                                                :class="lesson.diffs?.wheel ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-sky-700 dark:text-sky-300'"
                                            >
                                                <Shell class="h-3 w-3" /> {{ lesson.spots.wheel_booked }}/{{ lesson.spots.wheel_max }}
                                            </span>

                                            <span class="text-muted-foreground text-[10px]">•</span>

                                            <!-- Modelage max (ambre uniquement si diffs.handbuilding est vrai) -->
                                            <span
                                                class="inline-flex items-center gap-0.5 text-[11px]"
                                                :class="lesson.diffs?.handbuilding ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-orange-700 dark:text-orange-300'"
                                            >
                                                <Hand class="h-3 w-3" /> {{ lesson.spots.handbuilding_booked }}/{{ lesson.spots.handbuilding_max }}
                                            </span>
                                        </div>

                                        <!-- Bouton œil pour voir les participants inscrits -->
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 w-7 p-0 text-muted-foreground hover:text-foreground hover:bg-muted shrink-0"
                                            title="Voir les participants inscrits"
                                            @click.stop="emit('view-attendees', { lesson, course })"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </td>

                                <!-- Statut : Cumul Annulée / Personnalisée + Tooltip direct sur badge -->
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <!-- Badge Annulée avec Tooltip direct -->
                                        <TooltipProvider v-if="lesson.is_cancelled">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Badge variant="destructive" class="text-[10px] py-0 px-1.5 font-semibold cursor-help">
                                                        Annulée
                                                    </Badge>
                                                </TooltipTrigger>
                                                <TooltipContent side="top" class="max-w-xs text-xs p-2.5">
                                                    <p class="font-bold mb-1 text-destructive">Motif d'annulation :</p>
                                                    <p class="text-foreground leading-relaxed">{{ lesson.cancellation_reason || 'Aucun motif communiqué.' }}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>

                                        <!-- Badge Personnalisée -->
                                        <Badge
                                            v-if="lesson.is_overridden"
                                            variant="outline"
                                            class="text-[10px] py-0 px-1.5 bg-amber-500/10 text-amber-700 border-amber-300 dark:text-amber-400 font-medium"
                                        >
                                            Personnalisée
                                        </Badge>

                                        <!-- Badge Actif si ni annulé ni personnalisé -->
                                        <Badge
                                            v-if="!lesson.is_cancelled && !lesson.is_overridden"
                                            variant="outline"
                                            class="text-[10px] py-0 px-1.5 bg-emerald-500/10 text-emerald-700 border-emerald-300 dark:text-emerald-400 font-medium"
                                        >
                                            Actif
                                        </Badge>
                                    </div>
                                </td>

                                <!-- Action Éditer Séance -->
                                <td class="py-2.5 px-3 text-right pr-4">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="h-7 text-xs gap-1 px-2 shadow-2xs hover:bg-primary hover:text-primary-foreground"
                                        @click="emit('edit-lesson', { lesson, course })"
                                    >
                                        <Pencil class="h-3 w-3" />
                                        <span>Éditer</span>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </ScrollArea>
            </div>

            <!-- 4. Actions d'administration du cours avec Verrou de Sécurité -->
            <div class="flex items-center justify-between pt-2 border-t">
                <!-- Supprimer le cours (Désactivé si inscrits futurs) -->
                <TooltipProvider v-if="course.future_registered_count > 0">
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
                                    <span>Supprimer ce cours</span>
                                </Button>
                            </span>
                        </TooltipTrigger>
                        <TooltipContent side="top">
                            <p class="text-xs max-w-xs">
                                Impossible de supprimer : {{ pluralize(course.future_registered_count, 'élève est inscrit', 'élèves sont inscrits') }} sur des séances futures.
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
                    @click="emit('delete-course', course)"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    <span>Supprimer ce cours</span>
                </Button>

                <!-- Modifier le cours -->
                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 text-xs gap-1.5 shadow-2xs"
                    @click="emit('edit-course', course)"
                >
                    <Settings2 class="h-3.5 w-3.5" />
                    <span>Modifier le cours</span>
                </Button>
            </div>
        </AccordionContent>
    </AccordionItem>
</template>
