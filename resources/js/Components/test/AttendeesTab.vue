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
    attendees: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div class="space-y-4">
        <h3 class="text-lg font-bold">Invités</h3>
        <ScrollArea class="h-[600px] rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-16">ID</TableHead>
                        <TableHead>Prénom</TableHead>
                        <TableHead>Nom</TableHead>
                        <TableHead>Date de naissance</TableHead>
                        <TableHead>Utilisateur parent</TableHead>
                        <TableHead>Modules</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="attendee in attendees" :key="attendee.id">
                        <TableCell class="font-medium">{{
                            attendee.id
                        }}</TableCell>
                        <TableCell>{{ attendee.first_name }}</TableCell>
                        <TableCell>{{ attendee.last_name }}</TableCell>
                        <TableCell>
                            <Badge variant="outline" class="bg-blue-50">
                                {{ formatDate(attendee.birthday) }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <Badge v-if="attendee.user" variant="secondary">
                                {{ attendee.user.first_name }}
                                {{ attendee.user.last_name }}
                            </Badge>
                            <span v-else class="text-sm text-muted-foreground"
                                >Non lié</span
                            >
                        </TableCell>
                        <TableCell>
                            <div
                                v-if="attendee.modules?.length"
                                class="space-y-2"
                            >
                                <div
                                    v-for="module in attendee.modules"
                                    :key="module.id"
                                    class="flex items-center gap-2"
                                >
                                    <Badge
                                        variant="outline"
                                        class="bg-green-50"
                                    >
                                        Module #{{ module.id }}
                                    </Badge>
                                    <span class="text-sm">
                                        {{ module.remaining_lessons }}/{{
                                            module.total_lessons
                                        }}
                                        séances
                                    </span>
                                </div>
                            </div>
                            <div v-else class="text-sm text-muted-foreground">
                                Aucun module
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ScrollArea>
    </div>
</template>
