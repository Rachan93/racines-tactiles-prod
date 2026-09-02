<script setup>
import { useFormatting } from "@/Composables/useFormatting";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { ScrollArea } from "@/Components/ui/scroll-area";
import { Badge } from "@/Components/ui/badge";

// Utilisation des composables
const { formatDate } = useFormatting();

defineProps({
    upcomingLessons: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div class="space-y-4">
        <h3 class="text-lg font-bold">Séances à venir</h3>
        <ScrollArea class="h-[600px] rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-16">ID</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Cours ID</TableHead>
                        <TableHead>Instructeur</TableHead>
                        <TableHead>Places</TableHead>
                        <TableHead>Statut</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="lesson in upcomingLessons"
                        :key="lesson.id"
                    >
                        <TableCell class="font-medium">{{
                            lesson.id
                        }}</TableCell>
                        <TableCell>
                            <Badge variant="outline" class="bg-blue-50">
                                {{ formatDate(lesson.date) }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <Badge variant="secondary">
                                {{ lesson.course_id }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <div>
                                <Badge
                                    v-if="lesson.override_instructor_id"
                                    variant="outline"
                                    class="bg-amber-50"
                                >
                                    Override:
                                    {{ lesson.override_instructor_id }}
                                </Badge>
                                <span
                                    v-else
                                    class="text-sm text-muted-foreground"
                                    >Par défaut</span
                                >
                            </div>
                        </TableCell>
                        <TableCell>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline" class="bg-blue-50"
                                        >Modelage</Badge
                                    >
                                    <span class="text-sm">
                                        {{ lesson.spots_taken_handbuilding }}/{{
                                            lesson.override_spots_max_handbuilding ||
                                            "?"
                                        }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline" class="bg-amber-50"
                                        >Tour</Badge
                                    >
                                    <span class="text-sm">
                                        {{ lesson.spots_taken_wheel }}/{{
                                            lesson.override_spots_max_wheel ||
                                            "?"
                                        }}
                                    </span>
                                </div>
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    lesson.is_cancelled
                                        ? 'destructive'
                                        : 'default'
                                "
                                class="whitespace-nowrap"
                            >
                                {{
                                    lesson.is_cancelled
                                        ? "Annulée"
                                        : "Confirmée"
                                }}
                            </Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ScrollArea>
    </div>
</template>
