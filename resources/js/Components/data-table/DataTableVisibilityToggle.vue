<script setup lang="ts">
import { Button } from "@/Components/ui/button";
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Settings } from "lucide-vue-next";

interface Column {
    id: string;
    label: string;
    isVisible: boolean;
    canHide: boolean;
}

interface VisibilityToggleProps {
    columns: Column[];
}

defineProps<VisibilityToggleProps>();

const emit = defineEmits<{
    toggleVisibility: [columnId: string, isVisible: boolean];
}>();

function handleToggle(columnId: string, isVisible: boolean) {
    emit("toggleVisibility", columnId, isVisible);
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger asChild>
            <Button variant="outline" size="sm" class="ml-auto">
                <Settings class="h-4 w-4 mr-2" />
                Colonnes
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-48">
            <DropdownMenuLabel>Affichage des colonnes</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuCheckboxItem
                v-for="column in columns.filter((col) => col.canHide)"
                :key="column.id"
                :modelValue="column.isVisible"
                @update:modelValue="(value) => handleToggle(column.id, !!value)"
            >
                {{ column.label }}
            </DropdownMenuCheckboxItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
