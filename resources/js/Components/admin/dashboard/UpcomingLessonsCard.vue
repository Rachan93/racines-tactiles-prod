<script setup>
import { Link } from "@inertiajs/vue3";
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from "@/Components/ui/card";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    CalendarDays,
    Clock,
    UserCheck,
    Shell,
    Hand,
    ArrowRight,
} from "lucide-vue-next";

defineProps({
    lessons: {
        type: Array,
        default: () => [],
    },
});

const getPercentage = (booked, max) => {
    if (!max || max === 0) return 0;
    return Math.min(100, Math.round((booked / max) * 100));
};
</script>

<template>
    <Card class="shadow-xs flex flex-col justify-between">
        <CardHeader class="pb-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <CardTitle class="text-base font-semibold">Prochaines séances</CardTitle>
                    <CardDescription>État des réservations et postes disponibles pour les prochains cours</CardDescription>
                </div>
                <Button variant="outline" size="sm" as-child class="self-start sm:self-auto gap-1 text-xs">
                    <Link :href="route('courses.index')">
                        <span>Voir les cours</span>
                        <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </Button>
            </div>
        </CardHeader>

        <CardContent>
            <div v-if="lessons.length > 0" class="divide-y divide-border">
                <div
                    v-for="lesson in lessons"
                    :key="lesson.id"
                    class="py-3.5 first:pt-0 last:pb-0 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                >
                    <!-- Zone 1 : Informations de la séance -->
                    <div class="flex items-start gap-3 min-w-0 md:w-5/12">
                        <div class="h-10 w-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0 mt-0.5">
                            <CalendarDays class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="font-medium text-sm text-foreground truncate">
                                    {{ lesson.course_name }}
                                </h4>
                                <Badge variant="secondary" class="text-[10px] py-0 px-1.5 font-normal">
                                    {{ lesson.type_name }}
                                </Badge>
                            </div>
                            <p class="text-xs text-muted-foreground flex items-center gap-1.5 mt-1">
                                <span class="font-medium text-foreground">{{ lesson.date_formatted }}</span>
                                <span>•</span>
                                <span class="flex items-center gap-1">
                                    <Clock class="h-3 w-3" />
                                    {{ lesson.start_time }} - {{ lesson.end_time }}
                                </span>
                            </p>
                            <p class="text-[11px] text-muted-foreground flex items-center gap-1 mt-0.5 truncate">
                                <UserCheck class="h-3 w-3 shrink-0" />
                                <span class="truncate">{{ lesson.instructor_name }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Zone 2 : Jauges de remplissage Tours vs Modelage -->
                    <div class="grid grid-cols-2 gap-3 w-full md:w-4/12">
                        <!-- Jauge Tours -->
                        <div class="p-2 rounded-lg bg-muted/40 space-y-1">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-muted-foreground flex items-center gap-1">
                                    <Shell class="h-3 w-3 text-sky-600 shrink-0" />
                                    <span>Tours</span>
                                </span>
                                <span class="font-bold text-foreground">
                                    {{ lesson.wheel.booked }}<span class="text-muted-foreground font-normal">/{{ lesson.wheel.max }}</span>
                                </span>
                            </div>
                            <div class="h-1.5 w-full bg-muted rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-sky-600 rounded-full transition-all duration-300"
                                    :style="{ width: `${getPercentage(lesson.wheel.booked, lesson.wheel.max)}%` }"
                                />
                            </div>
                        </div>

                        <!-- Jauge Modelage -->
                        <div class="p-2 rounded-lg bg-muted/40 space-y-1">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-muted-foreground flex items-center gap-1">
                                    <Hand class="h-3 w-3 text-orange-600 shrink-0" />
                                    <span>Modelage</span>
                                </span>
                                <span class="font-bold text-foreground">
                                    {{ lesson.handbuilding.booked }}<span class="text-muted-foreground font-normal">/{{ lesson.handbuilding.max }}</span>
                                </span>
                            </div>
                            <div class="h-1.5 w-full bg-muted rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-orange-600 rounded-full transition-all duration-300"
                                    :style="{ width: `${getPercentage(lesson.handbuilding.booked, lesson.handbuilding.max)}%` }"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Zone 3 : Statut global de la séance -->
                    <div class="flex items-center justify-between md:justify-end md:w-2/12 shrink-0">
                        <span class="text-xs text-muted-foreground md:hidden">Disponibilité :</span>
                        <Badge
                            :variant="lesson.total_booked >= lesson.total_max && lesson.total_max > 0 ? 'destructive' : 'outline'"
                            class="text-[11px] font-normal"
                        >
                            {{
                                lesson.total_booked >= lesson.total_max && lesson.total_max > 0
                                    ? 'Complet'
                                    : `${lesson.total_max - lesson.total_booked} place(s) dispo`
                            }}
                        </Badge>
                    </div>
                </div>
            </div>

            <!-- État vide -->
            <div v-else class="text-center py-8 text-sm text-muted-foreground">
                <CalendarDays class="h-8 w-8 mx-auto text-muted-foreground/50 mb-2" />
                <p>Aucune séance programmée pour les prochains jours.</p>
            </div>
        </CardContent>
    </Card>
</template>
