<script setup lang="ts">
import { DropdownMenuItem } from "@/Components/ui/dropdown-menu";
import DataTableRowActions from "@/Components/data-table/DataTableRowActions.vue";
import { User, Edit, Mail, Phone, Trash2, Scroll } from "lucide-vue-next";
import { User as UserType } from "./columns";
import { router } from "@inertiajs/vue3";

// Déclaration de la fonction route
declare function route(name: string, params?: Record<string, any>): string;

const props = defineProps<{
    user: UserType;
}>();

function viewUser(userId: string | number) {
    router.visit(route("admin.users.show", { id: userId }));
}

function editUser(userId: string | number) {
    router.visit(route("admin.users.edit", { id: userId }));
}

function copyEmail(email: string) {
    navigator.clipboard.writeText(email);
}

function copyPhone(phone: string) {
    navigator.clipboard.writeText(phone);
}

function viewModules(userId: string | number) {
    router.visit(route("admin.modules.index", { userId }));
}
</script>

<template>
    <DataTableRowActions>
        <!-- Utilisation du slot pour fournir les actions spécifiques -->
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

        <DropdownMenuItem class="text-destructive">
            <Trash2 class="mr-2 h-4 w-4" />
            <span>Supprimer</span>
        </DropdownMenuItem>
    </DataTableRowActions>
</template>
