<script setup lang="ts">
import { Button } from "@/Components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { MoreHorizontal } from "lucide-vue-next";

defineProps<{
    label?: string;
}>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" class="h-8 w-8 p-0">
                <span class="sr-only">Ouvrir le menu</span>
                <MoreHorizontal class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuLabel>{{ label || "Actions" }}</DropdownMenuLabel>
            <DropdownMenuSeparator />

            <!-- Contenu via slot -->
            <slot></slot>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
