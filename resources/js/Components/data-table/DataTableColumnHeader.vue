<script setup lang="ts">
import { h } from "vue";
import { ArrowUpDown } from "lucide-vue-next";

interface SortableHeader {
    label: string;
    sorted: false | "asc" | "desc";
    onSort: (descending: boolean) => void;
}

const props = defineProps<SortableHeader>();
</script>

<template>
    <button
        class="flex items-center gap-1 hover:bg-accent hover:text-accent-foreground py-1 px-2 rounded"
        @click="onSort(sorted === 'asc')"
    >
        {{ label }}
        <span v-if="sorted === 'asc'" class="ml-1"> ▲ </span>
        <span v-else-if="sorted === 'desc'" class="ml-1"> ▼ </span>
        <span v-else class="ml-1">
            <ArrowUpDown class="h-4 w-4" />
        </span>
    </button>
</template>
