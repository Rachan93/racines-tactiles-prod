<script setup>
import { Link } from "@inertiajs/vue3";
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import { Avatar, AvatarFallback } from "@/Components/ui/avatar";
import { Users, ArrowRight } from "lucide-vue-next";

defineProps({
    users: {
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
                        >Derniers membres inscrits</CardTitle
                    >
                    <CardDescription
                        >Nouveaux comptes créés sur le site</CardDescription
                    >
                </div>
                <!-- Bouton CTA vers le répertoire d'utilisateurs -->
                <Button
                    variant="outline"
                    size="sm"
                    as-child
                    class="gap-1 text-xs"
                >
                    <Link :href="route('users.index')">
                        <span>Répertoire</span>
                        <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </Button>
            </div>
        </CardHeader>

        <CardContent>
            <div v-if="users.length > 0" class="space-y-2">
                <Link
                    v-for="user in users"
                    :key="user.id"
                    :href="route('users.show', { user: user.id })"
                    class="flex items-center justify-between gap-3 p-2 rounded-lg hover:bg-muted/60 transition-colors group cursor-pointer"
                >
                    <!-- Avatar & Nom/Email -->
                    <div class="flex items-center gap-3 min-w-0">
                        <Avatar
                            class="h-9 w-9 border border-primary/15 shrink-0"
                        >
                            <AvatarFallback
                                class="text-xs font-bold bg-primary/10 text-primary group-hover:bg-primary group-hover:text-primary-foreground transition-colors"
                            >
                                {{ getInitials(user.full_name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="min-w-0 flex-1">
                            <p
                                class="font-medium text-sm text-foreground truncate group-hover:text-primary transition-colors"
                            >
                                {{ user.full_name }}
                            </p>
                            <p class="text-xs text-muted-foreground truncate">
                                {{ user.email }}
                            </p>
                        </div>
                    </div>

                    <!-- Date d'inscription -->
                    <div class="text-right shrink-0">
                        <span
                            class="text-[11px] text-muted-foreground font-medium"
                        >
                            {{ user.created_at }}
                        </span>
                    </div>
                </Link>
            </div>

            <!-- État vide -->
            <div v-else class="text-center py-8 text-sm text-muted-foreground">
                <Users class="h-8 w-8 mx-auto text-muted-foreground/50 mb-2" />
                <p>Aucun utilisateur inscrit récemment.</p>
            </div>
        </CardContent>
    </Card>
</template>
