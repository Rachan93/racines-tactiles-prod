<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import Banner from "@/Components/Banner.vue";
import { Button } from "@/Components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/Components/ui/sheet";
import "vue-sonner/style.css";
import Toaster from "@/Components/ui/sonner/Sonner.vue";
import { Avatar, AvatarFallback } from "@/Components/ui/avatar";
import { Badge } from "@/Components/ui/badge";
import {
    LayoutDashboard,
    GraduationCap,
    Users,
    Flame,
    ShoppingBag,
    Coins,
    Menu,
    LogOut,
    ChevronDown,
    ShieldUser,
    ExternalLink,
    PanelLeftClose,
    PanelLeftOpen,
    CircleHelp,
} from "lucide-vue-next";

defineProps({
    title: {
        type: String,
        default: "",
    },
});

const page = usePage();
const isMobileOpen = ref(false);
const isCollapsed = ref(false);

// Charger l'état de la sidebar depuis le localStorage au montage
onMounted(() => {
    const savedState = localStorage.getItem("admin_sidebar_collapsed");
    if (savedState !== null) {
        isCollapsed.value = savedState === "true";
    }
});

const toggleSidebar = () => {
    isCollapsed.value = !isCollapsed.value;
    localStorage.setItem(
        "admin_sidebar_collapsed",
        isCollapsed.value.toString(),
    );
};

const user = computed(() => page.props.auth?.user);

const userInitials = computed(() => {
    if (!user.value) return "AD";
    const first = user.value.first_name?.[0] || "";
    const last = user.value.last_name?.[0] || "";
    return (first + last).toUpperCase() || "A";
});

const navigationGroups = computed(() => [
    {
        group: "Gestion Atelier",
        items: [
            {
                label: "Tableau de bord",
                route: "dashboard.index",
                icon: LayoutDashboard,
                active: route().current("dashboard.index"),
            },
            {
                label: "Cours & Séances",
                route: "courses.index",
                icon: GraduationCap,
                active:
                    route().current("courses.*") ||
                    route().current("lessons.*"),
            },
            {
                label: "Membres & Invités",
                route: "users.index",
                icon: Users,
                active: route().current("users.*"),
            },
        ],
    },
    {
        group: "Contenu du site",
        items: [
            {
                label: "FAQ",
                route: "faqs.index",
                icon: CircleHelp,
                active: route().current("faqs.*"),
            },
        ],
    },
    {
        group: "Atelier & Boutique",
        items: [
            {
                label: "Terres & Cuissons",
                icon: Flame,
                active: false,
                isUpcoming: true,
                badge: "Bientôt",
            },
            {
                label: "Boutique / Ventes",
                icon: ShoppingBag,
                active: false,
                isUpcoming: true,
                badge: "Bientôt",
            },
            {
                label: "Soldes & Fidélité",
                icon: Coins,
                active: false,
                isUpcoming: true,
                badge: "Bientôt",
            },
        ],
    },
]);

const logout = () => {
    router.post(route("logout"));
};
</script>

<template>
    <!-- Conteneur pleine hauteur bloqué (évite le scroll sur la page globale) -->
    <div class="h-screen w-screen overflow-hidden flex bg-muted/40 antialiased">
        <Head
            :title="title ? `${title} - Admin Studio` : 'Administration Studio'"
        />

        <Banner />

        <!-- ========================================================= -->
        <!-- 1. SIDEBAR DESKTOP (FIXE & COLLAPSIBLE)                   -->
        <!-- ========================================================= -->
        <aside
            class="hidden md:flex flex-col h-screen border-r bg-background shrink-0 transition-all duration-300 ease-in-out select-none overflow-hidden"
            :class="isCollapsed ? 'w-[72px]' : 'w-64 lg:w-72'"
        >
            <!-- Header Sidebar avec Logo et Bouton Collapse -->
            <div
                class="h-16 flex items-center border-b shrink-0 transition-all duration-300"
                :class="
                    isCollapsed ? 'justify-center px-2' : 'justify-between px-4'
                "
            >
                <!-- Marque uniquement quand la sidebar est ouverte -->
                <div
                    v-if="!isCollapsed"
                    class="flex items-center gap-3 min-w-0 overflow-hidden"
                >
                    <div
                        class="h-9 w-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0"
                    >
                        <ShieldUser class="h-5 w-5" />
                    </div>

                    <div class="flex flex-col min-w-0">
                        <span
                            class="font-semibold text-sm tracking-tight truncate"
                        >
                            Racines-Tactiles
                        </span>

                        <span class="text-xs text-muted-foreground truncate">
                            Panneau de gestion
                        </span>
                    </div>
                </div>

                <!-- En mode réduit, seul le bouton reste visible -->
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 text-muted-foreground hover:text-foreground shrink-0"
                    :title="isCollapsed ? 'Déplier le menu' : 'Replier le menu'"
                    @click="toggleSidebar"
                >
                    <PanelLeftOpen v-if="isCollapsed" class="h-4 w-4" />

                    <PanelLeftClose v-else class="h-4 w-4" />
                </Button>
            </div>

            <!-- Navigation Scrollable indépendamment -->
            <div class="flex-1 overflow-y-auto py-5 px-3 space-y-6">
                <div
                    v-for="(group, gIdx) in navigationGroups"
                    :key="gIdx"
                    class="space-y-1.5"
                >
                    <h4
                        v-if="!isCollapsed"
                        class="px-3 text-[11px] font-semibold text-muted-foreground uppercase tracking-wider transition-opacity duration-200"
                    >
                        {{ group.group }}
                    </h4>
                    <div v-else class="h-px bg-border my-2 mx-1" />

                    <nav class="space-y-1">
                        <template v-for="item in group.items" :key="item.label">
                            <!-- Lien actif -->
                            <Link
                                v-if="!item.isUpcoming && item.route"
                                :href="route(item.route)"
                                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors group relative"
                                :class="[
                                    item.active
                                        ? 'bg-primary text-primary-foreground shadow-sm'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                    isCollapsed ? 'justify-center px-0' : '',
                                ]"
                                :title="isCollapsed ? item.label : ''"
                            >
                                <component
                                    :is="item.icon"
                                    class="h-4 w-4 shrink-0"
                                />
                                <span v-if="!isCollapsed" class="truncate">{{
                                    item.label
                                }}</span>
                            </Link>

                            <!-- Lien futur (désactivé) -->
                            <div
                                v-else
                                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-muted-foreground/50 cursor-not-allowed select-none"
                                :class="
                                    isCollapsed
                                        ? 'justify-center px-0'
                                        : 'justify-between'
                                "
                                :title="
                                    isCollapsed ? `${item.label} (Bientôt)` : ''
                                "
                            >
                                <div class="flex items-center gap-3 truncate">
                                    <component
                                        :is="item.icon"
                                        class="h-4 w-4 shrink-0 opacity-50"
                                    />
                                    <span
                                        v-if="!isCollapsed"
                                        class="truncate"
                                        >{{ item.label }}</span
                                    >
                                </div>
                                <Badge
                                    v-if="!isCollapsed && item.badge"
                                    variant="outline"
                                    class="text-[10px] py-0 px-1.5 font-normal"
                                >
                                    {{ item.badge }}
                                </Badge>
                            </div>
                        </template>
                    </nav>
                </div>
            </div>

            <!-- Footer Sidebar -->
            <div class="p-3 border-t shrink-0">
                <Link
                    :href="route('home.index')"
                    target="_blank"
                    class="flex items-center justify-between w-full px-3 py-2 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground rounded-md transition-colors"
                    :class="isCollapsed ? 'justify-center px-0' : ''"
                    :title="isCollapsed ? 'Voir le site public' : ''"
                >
                    <span v-if="!isCollapsed" class="truncate"
                        >Voir le site public</span
                    >
                    <ExternalLink class="h-3.5 w-3.5 shrink-0" />
                </Link>
            </div>
        </aside>

        <!-- ========================================================= -->
        <!-- 2. ZONE DE CONTENU PRINCIPALE (SCROLL INTERNE)           -->
        <!-- ========================================================= -->
        <div class="flex flex-col flex-1 h-screen overflow-hidden min-w-0">
            <!-- Header fixe en haut -->
            <header
                class="sticky top-0 z-30 h-16 border-b bg-background/95 backdrop-blur px-4 sm:px-6 flex items-center justify-between gap-4 shrink-0"
            >
                <div class="flex items-center gap-3">
                    <!-- Hamburger Mobile -->
                    <Sheet v-model:open="isMobileOpen">
                        <SheetTrigger as-child>
                            <Button
                                variant="outline"
                                size="icon"
                                class="md:hidden"
                            >
                                <Menu class="h-5 w-5" />
                                <span class="sr-only">Ouvrir le menu</span>
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="left"
                            class="w-72 p-0 flex flex-col"
                        >
                            <SheetHeader
                                class="h-20 flex items-center justify-start px-6 pt-3 border-b"
                            >
                                <SheetTitle
                                    class="flex items-center gap-3 text-sm font-semibold"
                                >
                                    <div
                                        class="h-8 w-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0"
                                    >
                                        <ShieldUser class="h-4 w-4" />
                                    </div>

                                    <span>Racines-Tactiles</span>
                                </SheetTitle>
                            </SheetHeader>
                            <div class="flex-1 overflow-y-auto p-4 space-y-6">
                                <div
                                    v-for="(group, gIdx) in navigationGroups"
                                    :key="gIdx"
                                    class="space-y-2"
                                >
                                    <h4
                                        class="px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wider"
                                    >
                                        {{ group.group }}
                                    </h4>
                                    <nav class="space-y-1">
                                        <template
                                            v-for="item in group.items"
                                            :key="item.label"
                                        >
                                            <Link
                                                v-if="
                                                    !item.isUpcoming &&
                                                    item.route
                                                "
                                                :href="route(item.route)"
                                                @click="isMobileOpen = false"
                                                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg"
                                                :class="[
                                                    item.active
                                                        ? 'bg-primary text-primary-foreground'
                                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                                ]"
                                            >
                                                <component
                                                    :is="item.icon"
                                                    class="h-4 w-4 shrink-0"
                                                />
                                                <span>{{ item.label }}</span>
                                            </Link>
                                            <div
                                                v-else
                                                class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg text-muted-foreground/60"
                                            >
                                                <div
                                                    class="flex items-center gap-3"
                                                >
                                                    <component
                                                        :is="item.icon"
                                                        class="h-4 w-4 shrink-0"
                                                    />
                                                    <span>{{
                                                        item.label
                                                    }}</span>
                                                </div>
                                                <Badge
                                                    v-if="item.badge"
                                                    variant="outline"
                                                    class="text-[10px] py-0 px-1.5"
                                                >
                                                    {{ item.badge }}
                                                </Badge>
                                            </div>
                                        </template>
                                    </nav>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>

                    <h1
                        v-if="title"
                        class="font-semibold text-lg sm:text-xl text-foreground tracking-tight"
                    >
                        {{ title }}
                    </h1>
                </div>

                <!-- Menu Utilisateur -->
                <div class="flex items-center gap-3">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                class="flex items-center gap-2 pl-2 pr-3 py-1.5 h-auto rounded-full hover:bg-muted"
                            >
                                <Avatar
                                    class="h-8 w-8 border border-primary/20"
                                >
                                    <AvatarFallback
                                        class="text-xs font-bold bg-primary/10 text-primary"
                                    >
                                        {{ userInitials }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="hidden sm:flex flex-col text-left">
                                    <span
                                        class="text-xs font-medium leading-none"
                                        >{{ user?.first_name }}
                                        {{ user?.last_name }}</span
                                    >
                                    <span
                                        class="text-[11px] text-muted-foreground mt-0.5"
                                        >{{ user?.email }}</span
                                    >
                                </div>
                                <ChevronDown
                                    class="h-3.5 w-3.5 text-muted-foreground"
                                />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <DropdownMenuLabel>
                                <div class="flex flex-col space-y-1">
                                    <p class="text-xs font-semibold">
                                        {{ user?.first_name }}
                                        {{ user?.last_name }}
                                    </p>

                                    <p
                                        class="text-[11px] font-normal text-muted-foreground truncate"
                                    >
                                        {{ user?.email }}
                                    </p>
                                </div>
                            </DropdownMenuLabel>

                            <DropdownMenuSeparator />

                            <!-- Retour site public -->
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="route('home.index')"
                                    class="cursor-pointer flex items-center gap-2"
                                >
                                    <ExternalLink class="h-4 w-4" />

                                    <span>Retour au site</span>
                                </Link>
                            </DropdownMenuItem>

                            <DropdownMenuSeparator />

                            <!-- Déconnexion -->
                            <DropdownMenuItem
                                class="cursor-pointer flex items-center gap-2 text-destructive focus:text-destructive"
                                @click="logout"
                            >
                                <LogOut class="h-4 w-4" />

                                <span>Déconnexion</span>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <!-- Seule cette zone défile -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="max-w-7xl mx-auto w-full">
                    <slot />
                    <Toaster richColors position="top-right" />
                </div>
            </main>
        </div>
    </div>
</template>
