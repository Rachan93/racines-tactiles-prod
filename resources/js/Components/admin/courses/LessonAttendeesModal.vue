<script setup>
import { ref, watch, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { pluralize } from "@/Utils/formatters";

// Composants Shadcn UI
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { ScrollArea } from "@/Components/ui/scroll-area";
import {
    Users,
    Shell,
    Hand,
    Mail,
    Phone,
    User,
    UserCheck,
    UserX,
    Loader2,
    RotateCcw,
    ExternalLink,
    AlertCircle,
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
});

const emit = defineEmits(["update:open"]);

// Données chargées à la demande
const attendees = ref([]);
const isLoading = ref(false);
const hasError = ref(false);

// Récupération des participants via l'endpoint on-demand
const fetchAttendees = async () => {
    if (!props.lesson?.id) return;

    isLoading.value = true;
    hasError.value = false;

    try {
        const response = await fetch(
            route("lessons.attendees", { lesson: props.lesson.id }),
            {
                headers: {
                    Accept: "application/json",
                },
            }
        );

        if (!response.ok) {
            throw new Error("Erreur réseau lors de la récupération des participants");
        }

        attendees.value = await response.json();
    } catch (err) {
        console.error("Erreur chargement inscrits :", err);
        hasError.value = true;
    } finally {
        isLoading.value = false;
    }
};

// Déclenchement à chaque ouverture de la modale
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && props.lesson) {
            fetchAttendees();
        } else {
            attendees.value = [];
            isLoading.value = false;
            hasError.value = false;
        }
    },
    { immediate: true }
);

// Totaux et décomptes
const totalAttendees = computed(() => attendees.value.length);
const registeredCount = computed(() => attendees.value.filter((a) => a.status === "registered").length);
const absentCount = computed(() => attendees.value.filter((a) => a.status === "absent").length);

const close = () => {
    emit("update:open", false);
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-2xl max-h-[85vh] flex flex-col p-0 overflow-hidden">
            <!-- En-tête -->
            <DialogHeader class="p-6 pb-4 border-b shrink-0 bg-muted/20 pr-12">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <DialogTitle class="text-lg font-bold flex items-center gap-2">
                            <Users class="h-5 w-5 text-primary" />
                            <span>Participants inscrits</span>
                        </DialogTitle>
                        <Badge variant="outline" class="text-xs bg-background">
                            {{ pluralize(totalAttendees, 'participant') }}
                        </Badge>
                    </div>
                    <DialogDescription class="text-xs">
                        {{ course?.name }} • {{ lesson?.date_formatted }} ({{ lesson?.start_time }} - {{ lesson?.end_time }})
                    </DialogDescription>
                </div>

                <!-- Bandeau récapitulatif des effectifs et postes -->
                <div class="mt-3 p-3 rounded-lg border bg-background flex items-center justify-between gap-3 text-xs flex-wrap shadow-2xs">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-sky-50 text-sky-800 border border-sky-200 text-xs font-semibold dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800">
                            <Shell class="h-3.5 w-3.5 text-sky-600" />
                            {{ lesson?.spots?.wheel_booked }} / {{ lesson?.spots?.wheel_max }} Tours
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-orange-50 text-orange-800 border border-orange-200 text-xs font-semibold dark:bg-orange-950/40 dark:text-orange-300 dark:border-orange-800">
                            <Hand class="h-3.5 w-3.5 text-orange-600" />
                            {{ lesson?.spots?.handbuilding_booked }} / {{ lesson?.spots?.handbuilding_max }} Modelage
                        </span>
                    </div>

                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-emerald-700 dark:text-emerald-400 font-medium flex items-center gap-1">
                            <UserCheck class="h-3.5 w-3.5" /> {{ pluralize(registeredCount, 'présent') }}
                        </span>
                        <span v-if="absentCount > 0" class="text-destructive font-medium flex items-center gap-1">
                            <UserX class="h-3.5 w-3.5" /> {{ pluralize(absentCount, 'absent') }}
                        </span>
                    </div>
                </div>
            </DialogHeader>

            <!-- Corps du tableau des participants -->
            <div class="flex-1 overflow-hidden p-6 pt-4">
                <!-- État de chargement on-demand -->
                <div v-if="isLoading" class="py-16 text-center flex flex-col items-center justify-center space-y-3">
                    <Loader2 class="h-7 w-7 text-primary animate-spin" />
                    <p class="text-xs text-muted-foreground">Chargement des participants...</p>
                </div>

                <!-- État d'erreur -->
                <div v-else-if="hasError" class="py-12 px-4 rounded-xl border border-destructive/30 text-center flex flex-col items-center justify-center space-y-3 bg-destructive/5">
                    <AlertCircle class="h-8 w-8 text-destructive" />
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-destructive">Impossible de charger les participants</p>
                        <p class="text-[11px] text-muted-foreground">Une erreur s'est produite lors de la récupération des données.</p>
                    </div>
                    <Button type="button" variant="outline" size="sm" class="text-xs gap-1.5" @click="fetchAttendees">
                        <RotateCcw class="h-3.5 w-3.5" />
                        <span>Réessayer</span>
                    </Button>
                </div>

                <!-- Cas 1 : Aucun inscrit -->
                <div
                    v-else-if="attendees.length === 0"
                    class="py-12 px-4 rounded-xl border border-dashed text-center flex flex-col items-center justify-center space-y-2.5 bg-muted/10"
                >
                    <div class="h-10 w-10 rounded-full bg-muted text-muted-foreground flex items-center justify-center">
                        <Users class="h-5 w-5" />
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-xs font-bold text-foreground">Aucun participant inscrit</p>
                        <p class="text-[11px] text-muted-foreground max-w-xs">
                            Cette séance ne comporte actuellement aucune réservation d'élève ou de rattrapage.
                        </p>
                    </div>
                </div>

                <!-- Cas 2 : Liste des inscrits sous ScrollArea -->
                <ScrollArea v-else class="h-[22rem] w-full rounded-xl border bg-background overflow-hidden overscroll-contain">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead class="bg-muted/60 text-muted-foreground sticky top-0 z-10 border-b">
                            <tr>
                                <th class="py-2.5 px-3.5 font-semibold">Élève & Contact</th>
                                <th class="py-2.5 px-3.5 font-semibold">Poste</th>
                                <th class="py-2.5 px-3.5 font-semibold">Type de place</th>
                                <th class="py-2.5 px-3.5 font-semibold text-right pr-4">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="att in attendees"
                                :key="att.id"
                                class="hover:bg-muted/30 transition-colors"
                                :class="att.status === 'absent' ? 'bg-destructive/5' : ''"
                            >
                                <!-- Élève & Contact avec liens vers users.show -->
                                <td class="py-3 px-3.5">
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <!-- Si utilisateur régulier : lien direct vers sa fiche -->
                                            <Link
                                                v-if="!att.is_attendee && att.user_id"
                                                :href="route('users.show', { user: att.user_id })"
                                                class="font-bold text-foreground hover:text-primary hover:underline inline-flex items-center gap-1"
                                                title="Voir le profil membre"
                                            >
                                                <span>{{ att.name }}</span>
                                                <ExternalLink class="h-2.5 w-2.5 opacity-60" />
                                            </Link>

                                            <!-- Si invité : nom + badge bleu Invité -->
                                            <template v-else>
                                                <span class="font-bold text-foreground">{{ att.name }}</span>
                                                <Badge
                                                    variant="outline"
                                                    class="text-[9px] py-0 px-1.5 bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800 font-medium"
                                                >
                                                    Invité
                                                </Badge>
                                            </template>
                                        </div>

                                        <!-- Mention Parent cliquable si Invité -->
                                        <div v-if="att.is_attendee && att.parent_user_name" class="text-[10px] text-muted-foreground flex items-center gap-1">
                                            <User class="h-2.5 w-2.5 text-muted-foreground" />
                                            <span>Inscrit par :
                                                <Link
                                                    v-if="att.parent_user_id"
                                                    :href="route('users.show', { user: att.parent_user_id })"
                                                    class="font-semibold text-foreground hover:text-primary hover:underline inline-flex items-center gap-0.5"
                                                    title="Voir le compte du parent"
                                                >
                                                    {{ att.parent_user_name }}
                                                    <ExternalLink class="h-2 w-2 opacity-60" />
                                                </Link>
                                                <strong v-else class="text-foreground">{{ att.parent_user_name }}</strong>
                                            </span>
                                        </div>

                                        <!-- Email & Téléphone -->
                                        <div class="text-[11px] text-muted-foreground flex items-center gap-3 flex-wrap pt-0.5">
                                            <span v-if="att.email && att.email !== '-'" class="flex items-center gap-1">
                                                <Mail class="h-2.5 w-2.5 text-muted-foreground" /> {{ att.email }}
                                            </span>
                                            <span v-if="att.phone && att.phone !== '-'" class="flex items-center gap-1">
                                                <Phone class="h-2.5 w-2.5 text-muted-foreground" /> {{ att.phone }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Poste réservé (Tour / Modelage) -->
                                <td class="py-3 px-3.5">
                                    <Badge
                                        v-if="att.spot_type === 'wheel'"
                                        variant="outline"
                                        class="text-[11px] py-0.5 px-2 bg-sky-50 text-sky-800 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800 gap-1 font-medium"
                                    >
                                        <Shell class="h-3 w-3 text-sky-600" /> Tour
                                    </Badge>
                                    <Badge
                                        v-else
                                        variant="outline"
                                        class="text-[11px] py-0.5 px-2 bg-orange-50 text-orange-800 border-orange-200 dark:bg-orange-950/40 dark:text-orange-300 dark:border-orange-800 gap-1 font-medium"
                                    >
                                        <Hand class="h-3 w-3 text-orange-600" /> Modelage
                                    </Badge>
                                </td>

                                <!-- Type de réservation (Régulier, Rattrapage, Remplacement) -->
                                <td class="py-3 px-3.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <Badge
                                            v-if="att.enrollment_type === 'makeup'"
                                            variant="outline"
                                            class="text-[10px] py-0.5 px-1.5 bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 font-medium gap-1"
                                        >
                                            <RotateCcw class="h-2.5 w-2.5" /> Rattrapage
                                        </Badge>
                                        <Badge
                                            v-else
                                            variant="secondary"
                                            class="text-[10px] py-0.5 px-1.5 font-normal"
                                        >
                                            Régulier
                                        </Badge>

                                        <Badge
                                            v-if="att.is_substitute"
                                            variant="outline"
                                            class="text-[10px] py-0.5 px-1.5 bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300 font-medium"
                                        >
                                            Remplaçant
                                        </Badge>
                                    </div>
                                </td>

                                <!-- Statut de présence -->
                                <td class="py-3 px-3.5 text-right pr-4">
                                    <Badge
                                        v-if="att.status === 'registered'"
                                        variant="outline"
                                        class="text-[10px] py-0.5 px-2 bg-emerald-500/10 text-emerald-700 border-emerald-300 dark:text-emerald-400 font-medium inline-flex items-center gap-1"
                                    >
                                        <UserCheck class="h-3 w-3" /> Inscrit
                                    </Badge>
                                    <Badge
                                        v-else
                                        variant="destructive"
                                        class="text-[10px] py-0.5 px-2 font-medium inline-flex items-center gap-1"
                                    >
                                        <UserX class="h-3 w-3" /> Absent
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </ScrollArea>
            </div>

            <!-- Pied de page -->
            <DialogFooter class="p-4 border-t shrink-0 bg-muted/10 flex flex-row items-center justify-end">
                <Button type="button" variant="outline" size="sm" @click="close">
                    Fermer
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
