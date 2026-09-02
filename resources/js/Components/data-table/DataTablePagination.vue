<script setup lang="ts">
import { Button } from "@/Components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";

interface PaginationProps {
    currentPage: number;
    perPage: number;
    totalItems: number;
    lastPage: number;
    pageSizeOptions: number[];
}

const props = withDefaults(defineProps<PaginationProps>(), {
    pageSizeOptions: () => [5, 10, 20, 50],
});

const emit = defineEmits<{
    pageChange: [page: number];
    perPageChange: [perPage: number];
}>();
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <p class="text-sm text-muted-foreground">
                {{ totalItems }} éléments
            </p>
            <Select
                :model-value="perPage.toString()"
                @update:model-value="
                    (value) => emit('perPageChange', Number(value))
                "
            >
                <SelectTrigger class="h-8 w-[70px]">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent side="top">
                    <SelectItem
                        v-for="pageSize in pageSizeOptions"
                        :key="pageSize"
                        :value="pageSize.toString()"
                    >
                        {{ pageSize }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <p class="text-sm text-muted-foreground">par page</p>
        </div>

        <div class="flex items-center space-x-2">
            <div class="text-sm text-muted-foreground">
                Page {{ currentPage }} sur {{ lastPage }}
            </div>
            <Button
                variant="outline"
                size="icon"
                :disabled="currentPage <= 1"
                @click="emit('pageChange', currentPage - 1)"
            >
                <ChevronLeft class="h-4 w-4" />
                <span class="sr-only">Page précédente</span>
            </Button>
            <Button
                variant="outline"
                size="icon"
                :disabled="currentPage >= lastPage"
                @click="emit('pageChange', currentPage + 1)"
            >
                <ChevronRight class="h-4 w-4" />
                <span class="sr-only">Page suivante</span>
            </Button>
        </div>
    </div>
</template>
