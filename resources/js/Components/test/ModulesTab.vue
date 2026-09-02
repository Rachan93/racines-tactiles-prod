<script setup>
import { useFormatting } from "@/Composables/useFormatting";
import { useParticipants } from "@/Composables/useParticipants";
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
const { formatDate, formatPrice } = useFormatting();
const { participantType } = useParticipants();

defineProps({
    modules: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div class="space-y-4">
        <h3 class="text-lg font-bold">Modules</h3>
        <ScrollArea class="h-[600px] rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-16">ID</TableHead>
                        <TableHead>Type de participant</TableHead>
                        <TableHead>Participant</TableHead>
                        <TableHead>Séances</TableHead>
                        <TableHead>Prix payé</TableHead>
                        <TableHead>État</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="module in modules" :key="module.id">
                        <TableCell class="font-medium">{{
                            module.id
                        }}</TableCell>
                        <TableCell>{{
                            participantType(module.participant_type)
                        }}</TableCell>
                        <TableCell>
                            {{ module.participant?.first_name }}
                            {{ module.participant?.last_name }}
                        </TableCell>
                        <TableCell>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline" class="bg-blue-50"
                                        >Total</Badge
                                    >
                                    <span class="text-sm">{{
                                        module.total_lessons
                                    }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline" class="bg-green-50"
                                        >Restantes</Badge
                                    >
                                    <span class="text-sm">{{
                                        module.remaining_lessons
                                    }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline" class="bg-amber-50"
                                        >Assistées</Badge
                                    >
                                    <span class="text-sm">{{
                                        module.attended_lessons
                                    }}</span>
                                </div>
                            </div>
                        </TableCell>
                        <TableCell>
                            {{ formatPrice(module.paid_price) }}
                        </TableCell>
                        <TableCell>
                            <div class="space-y-2">
                                <Badge
                                    :variant="
                                        module.is_active
                                            ? 'default'
                                            : 'destructive'
                                    "
                                >
                                    {{ module.is_active ? "Actif" : "Inactif" }}
                                </Badge>
                                <div class="flex items-center gap-2 mt-2">
                                    <Badge variant="outline" class="bg-blue-50"
                                        >Expiration</Badge
                                    >
                                    <span class="text-sm">{{
                                        formatDate(module.expiration_date)
                                    }}</span>
                                </div>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ScrollArea>
    </div>
</template>
