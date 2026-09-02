<script setup lang="ts">
import { DropdownMenuItem } from "@/Components/ui/dropdown-menu";
import DataTableRowActions from "@/Components/data-table/DataTableRowActions.vue";
import { User, Edit, Trash2, Scroll } from "lucide-vue-next";
import { Attendee } from "./attendeeColumns";
import { router } from "@inertiajs/vue3";

// Déclaration de la fonction route
declare function route(name: string, params?: Record<string, any>): string;

const props = defineProps<{
    attendee: Attendee;
}>();

function editAttendee(attendeeId: string | number) {
    router.visit(route("admin.attendees.edit", { attendee: attendeeId }));
}

function viewModules(attendeeId: string | number) {
    router.visit(route("admin.modules.index", { attendeeId }));
}

function viewParent(userId: string | number) {
    if (userId) {
        router.visit(route("users.show", { user: userId }));
    }
}

function deleteAttendee(attendeeId: string | number) {
    if (confirm("Êtes-vous sûr de vouloir supprimer cet accompagnant ?")) {
        router.delete(
            route("admin.attendees.destroy", { attendee: attendeeId }),
            {
                preserveScroll: true,
            }
        );
    }
}
</script>

<template>
    <DataTableRowActions>
        <DropdownMenuItem
            v-if="props.attendee.user"
            @click="viewParent(props.attendee.user.id)"
        >
            <User class="mr-2 h-4 w-4" />
            <span>Voir le profil du responsable</span>
        </DropdownMenuItem>

        <DropdownMenuItem @click="editAttendee(props.attendee.id)">
            <Edit class="mr-2 h-4 w-4" />
            <span>Modifier</span>
        </DropdownMenuItem>

        <DropdownMenuItem @click="viewModules(props.attendee.id)">
            <Scroll class="mr-2 h-4 w-4" />
            <span>Voir les modules</span>
        </DropdownMenuItem>

        <DropdownMenuItem
            @click="deleteAttendee(props.attendee.id)"
            class="text-destructive"
        >
            <Trash2 class="mr-2 h-4 w-4" />
            <span>Supprimer</span>
        </DropdownMenuItem>
    </DataTableRowActions>
</template>
