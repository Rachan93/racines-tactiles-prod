<script setup>
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from "@/Components/ui/card";
import { Badge } from "@/Components/ui/badge";
import { Avatar, AvatarFallback } from "@/Components/ui/avatar";
import { Layers } from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";

defineProps({
    modules: {
        type: Array,
        default: () => [],
    },
});

const getInitials = (name) => {
    if (!name) return "??";
    const parts = name.trim().split(" ");
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};
</script>

<template>
    <Card class="shadow-xs flex flex-col justify-between">
        <CardHeader class="pb-3">
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="text-base font-semibold"
                        >Dernières réservations</CardTitle
                    >
                    <CardDescription
                        >Les modules récemment souscrits</CardDescription
                    >
                </div>
                <div
                    class="h-8 w-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0"
                >
                    <Layers class="h-4 w-4" />
                </div>
            </div>
        </CardHeader>

        <CardContent>
            <div v-if="modules.length > 0" class="space-y-3">
                <Link
                    v-for="item in modules"
                    :key="item.id"
                    :href="
                        route('users.show', {
                            user: item.owner_user_id,
                        })
                    "
                    class="flex items-center justify-between gap-3 p-2 rounded-lg hover:bg-muted/60 transition-colors group cursor-pointer"
                >
                    <!-- Élève et Formule -->
                    <div class="flex items-center gap-3 min-w-0">
                        <Avatar class="h-9 w-9 border shrink-0">
                            <AvatarFallback
                                class="text-xs font-semibold bg-primary/10 text-primary"
                            >
                                {{ getInitials(item.participant_name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <p
                                    class="font-medium text-sm text-foreground truncate group-hover:text-primary transition-colors"
                                >
                                    {{ item.participant_name }}
                                </p>
                                <Badge
                                    v-if="item.participant_type === 'Invité'"
                                    variant="secondary"
                                    class="text-[9px] py-0 px-1 font-normal"
                                >
                                    Invité
                                </Badge>
                            </div>
                            <p
                                class="text-xs text-muted-foreground truncate mt-0.5"
                            >
                                Formule {{ item.type_name }} •
                                {{ item.total_lessons }} séances
                            </p>
                        </div>
                    </div>

                    <!-- Date relative et Statut -->
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <Badge
                            :variant="item.is_active ? 'default' : 'outline'"
                            class="text-[10px] font-normal py-0 px-1.5"
                        >
                            {{ item.is_active ? "Actif" : "Terminé" }}
                        </Badge>
                        <span class="text-[10px] text-muted-foreground">{{
                            item.created_at
                        }}</span>
                    </div>
                </Link>
            </div>

            <!-- État vide -->
            <div v-else class="text-center py-8 text-sm text-muted-foreground">
                <Layers class="h-8 w-8 mx-auto text-muted-foreground/50 mb-2" />
                <p>Aucun module réservé pour le moment.</p>
            </div>
        </CardContent>
    </Card>
</template>
