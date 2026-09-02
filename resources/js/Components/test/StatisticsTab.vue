<script setup>
import { Card, CardHeader, CardTitle, CardContent } from "@/Components/ui/card";

defineProps({
    stats: {
        type: Object,
        required: true,
    },
});

// Fonction pour formatter le libellé (transformer camelCase en texte lisible)
const formatLabel = (key) => {
    return key.replace(/([A-Z])/g, " $1").toLowerCase();
};
</script>

<template>
    <div class="space-y-6">
        <h3 class="text-lg font-bold">Statistiques générales</h3>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Card
                v-for="(value, key) in stats"
                :key="key"
                class="transition-all hover:shadow-md"
            >
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm text-muted-foreground capitalize">
                        {{ formatLabel(key) }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">
                        {{ value }}
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
