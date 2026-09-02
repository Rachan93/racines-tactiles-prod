<script setup>
import { Tabs, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Badge } from "@/Components/ui/badge";

defineProps({
    activeTab: {
        type: String,
        required: true,
    },
    tabs: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(["update:activeTab"]);

const handleTabChange = (value) => {
    emit("update:activeTab", value);
};
</script>

<template>
    <Tabs
        :model-value="activeTab"
        @update:model-value="handleTabChange"
        class="mb-6"
    >
        <TabsList class="w-full flex flex-wrap gap-2 bg-transparent p-0">
            <TabsTrigger
                v-for="tab in tabs"
                :key="tab.id"
                :value="tab.id"
                class="data-[state=active]:bg-blue-500 data-[state=active]:text-white bg-gray-200 text-sm"
            >
                {{ tab.label }}
                <Badge
                    v-if="tab.count !== undefined"
                    class="ml-2 bg-gray-100 text-gray-700 hover:bg-gray-100"
                    variant="outline"
                >
                    {{ tab.count }}
                </Badge>
            </TabsTrigger>
        </TabsList>
    </Tabs>
</template>
