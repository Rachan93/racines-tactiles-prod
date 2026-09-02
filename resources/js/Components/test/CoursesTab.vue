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
    activeCourses: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div class="space-y-4">
        <h3 class="text-lg font-bold">Cours actifs</h3>
        <ScrollArea class="h-[600px] rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-16">ID</TableHead>
                        <TableHead>Titre</TableHead>
                        <TableHead>Dates</TableHead>
                        <TableHead>Instructeur</TableHead>
                        <TableHead>Séances</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="course in activeCourses" :key="course.id">
                        <TableCell class="font-medium">{{
                            course.id
                        }}</TableCell>
                        <TableCell>{{ course.title }}</TableCell>
                        <TableCell>
                            <div class="flex flex-col gap-1">
                                <Badge
                                    variant="outline"
                                    class="bg-blue-50 inline-flex w-fit"
                                >
                                    Début: {{ formatDate(course.start_date) }}
                                </Badge>
                                <Badge
                                    variant="outline"
                                    class="bg-amber-50 inline-flex w-fit"
                                >
                                    Fin: {{ formatDate(course.end_date) }}
                                </Badge>
                            </div>
                        </TableCell>
                        <TableCell>
                            {{
                                course.instructor?.first_name +
                                    " " +
                                    course.instructor?.last_name ||
                                "Non assigné"
                            }}
                        </TableCell>
                        <TableCell>
                            <div
                                v-if="course.lessons?.length"
                                class="space-y-2"
                            >
                                <div
                                    v-for="lesson in course.lessons"
                                    :key="lesson.id"
                                    class="flex items-center"
                                >
                                    <Badge variant="secondary" class="mr-2">
                                        #{{ lesson.id }}
                                    </Badge>
                                    <span class="text-sm">{{
                                        formatDate(lesson.date)
                                    }}</span>
                                </div>
                            </div>
                            <div v-else class="text-sm text-muted-foreground">
                                Aucune séance
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ScrollArea>
    </div>
</template>
