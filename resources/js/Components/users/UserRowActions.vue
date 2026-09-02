<script setup lang="ts">
import { DropdownMenuItem } from "@/Components/ui/dropdown-menu";
import DataTableRowActions from "@/Components/data-table/DataTableRowActions.vue";
import {
    User,
    Edit,
    Mail,
    Phone,
    Trash2,
    Scroll,
    UserPlus,
} from "lucide-vue-next";
import { User as UserType } from "./userColumns";
import { router } from "@inertiajs/vue3";

// Déclaration de la fonction route
declare function route(name: string, params?: Record<string, any>): string;

const props = defineProps<{
    user: UserType;
}>();

function viewUser(userId: string | number) {
    router.visit(route("users.show", { user: userId }));
}

function editUser(userId: string | number) {
    router.visit(route("users.edit", { user: userId }));
}

function copyEmail(email: string) {
    navigator.clipboard.writeText(email);
}

function copyPhone(phone: string) {
    navigator.clipboard.writeText(phone);
}

function viewModules(userId: string | number) {
    router.visit(route("modules.index", { userId }));
}

function addAttendee(userId: string | number) {
    router.visit(route("admin.attendees.create", { userId }));
}

function deleteUser(userId: string | number) {
    if (confirm("Êtes-vous sûr de vouloir supprimer cet utilisateur ?")) {
        router.delete(route("users.destroy", { user: userId }), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <DataTableRowActions>
        <DropdownMenuItem @click="viewUser(props.user.id)">
            <User class="mr-2 h-4 w-4" />
            <span>Voir le profil</span>
        </DropdownMenuItem>

        <DropdownMenuItem @click="editUser(props.user.id)">
            <Edit class="mr-2 h-4 w-4" />
            <span>Modifier</span>
        </DropdownMenuItem>

        <DropdownMenuItem @click="copyEmail(props.user.email)">
            <Mail class="mr-2 h-4 w-4" />
            <span>Copier l'email</span>
        </DropdownMenuItem>

        <DropdownMenuItem
            v-if="props.user.phone_number"
            @click="copyPhone(props.user.phone_number)"
        >
            <Phone class="mr-2 h-4 w-4" />
            <span>Copier le téléphone</span>
        </DropdownMenuItem>

        <DropdownMenuItem @click="viewModules(props.user.id)">
            <Scroll class="mr-2 h-4 w-4" />
            <span>Voir les modules</span>
        </DropdownMenuItem>

        <DropdownMenuItem @click="addAttendee(props.user.id)">
            <UserPlus class="mr-2 h-4 w-4" />
            <span>Ajouter un accompagnant</span>
        </DropdownMenuItem>

        <DropdownMenuItem
            @click="deleteUser(props.user.id)"
            class="text-destructive"
        >
            <Trash2 class="mr-2 h-4 w-4" />
            <span>Supprimer</span>
        </DropdownMenuItem>
    </DataTableRowActions>
</template>
