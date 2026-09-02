<script setup>
import { ref, computed } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Button } from "@/Components/ui/button";
import { Separator } from "@/Components/ui/separator";
import {
    Menu,
    X,
    ChevronDown,
    User,
    Settings,
    LogOut,
    Sparkles,
} from "lucide-vue-next";
import CookieBanner from "@/Components/custom/CookieBanner.vue";

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const isAteliersActive = computed(
    () => page.url.split(/[?#]/)[0] === route("ateliers.index"),
);
const mobileMenuOpen = ref(false);

const logout = () => {
    router.post(route("logout"));
};
</script>

<template>
    <header
        class="bg-white border-b border-gray-100 sticky top-0 z-40 font-brand"
    >
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-8 h-20">
                <!-- 1. Logo / Marque -->
                <div v-if="$page.url !== '/'" class="flex items-center gap-3">
                    <Link :href="route('home.index')" class="block group">
                        <img
                            src="/images/assets/logo.png"
                            alt="Racines Tactiles - Atelier Céramique"
                            class="h-12 w-auto object-contain transition-transform group-hover:scale-105"
                        />
                    </Link>
                </div>

                <!-- 2. Liens de Navigation Desktop -->
                <nav
                    :class="[
                        'hidden md:flex items-center space-x-7 text-sm font-medium md:mr-auto',
                        $page.url === '/' ? 'md:ml-16' : '',
                    ]"
                >
                    <!-- Dropdown Ateliers -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                :class="[
                                    'group relative inline-flex items-center gap-1 transition-colors focus:outline-none cursor-pointer after:absolute after:-bottom-1 after:left-0 after:h-px after:w-full after:origin-left after:bg-current after:transition-transform after:duration-200',
                                    isAteliersActive
                                        ? 'text-gray-900 font-semibold after:scale-x-100'
                                        : 'text-gray-600 hover:text-gray-900 after:scale-x-0 hover:after:scale-x-100',
                                ]"
                            >
                                <span>Ateliers</span>
                                <ChevronDown
                                    class="w-3.5 h-3.5 text-gray-400"
                                />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="start"
                            class="w-56 bg-white border-gray-200 shadow-md font-brand"
                        >
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="route('ateliers.index')"
                                    class="cursor-pointer py-2 text-xs sm:text-sm"
                                >
                                    Tous les cours & ateliers
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="`${route('ateliers.index')}#collectifs`"
                                    class="cursor-pointer py-2 text-xs sm:text-sm"
                                >
                                    Cours collectifs
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="`${route('ateliers.index')}#initiation`"
                                    class="cursor-pointer py-2 text-xs sm:text-sm"
                                >
                                    Cours d'initiation
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="`${route('ateliers.index')}#prive`"
                                    class="cursor-pointer py-2 text-xs sm:text-sm"
                                >
                                    Cours privé
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Link
                        href="/stages"
                        :class="[
                            'group relative transition-colors duration-150 after:absolute after:-bottom-1 after:left-0 after:h-px after:w-full after:origin-left after:bg-current after:transition-transform after:duration-200',
                            $page.url.startsWith('/stages')
                                ? 'text-gray-900 font-semibold after:scale-x-100'
                                : 'text-gray-600 hover:text-gray-900 after:scale-x-0 hover:after:scale-x-100',
                        ]"
                    >
                        Stages
                    </Link>

                    <Link
                        :href="route('calendrier.index')"
                        :class="[
                            'group relative flex items-center gap-1.5 transition-colors duration-150 after:absolute after:-bottom-1 after:left-0 after:h-px after:w-full after:origin-left after:bg-current after:transition-transform after:duration-200',
                            $page.url.startsWith('/calendrier')
                                ? 'text-gray-900 font-semibold after:scale-x-100'
                                : 'text-gray-600 hover:text-gray-900 after:scale-x-0 hover:after:scale-x-100',
                        ]"
                    >
                        <span>Calendrier</span>
                    </Link>

                    <Link
                        :href="route('faq.index')"
                        :class="[
                            'group relative transition-colors duration-150 after:absolute after:-bottom-1 after:left-0 after:h-px after:w-full after:origin-left after:bg-current after:transition-transform after:duration-200',
                            $page.url.startsWith('/faq')
                                ? 'text-gray-900 font-semibold after:scale-x-100'
                                : 'text-gray-600 hover:text-gray-900 after:scale-x-0 hover:after:scale-x-100',
                        ]"
                    >
                        FAQ
                    </Link>

                    <Separator orientation="vertical" class="h-5 bg-gray-200" />

                    <Link
                        :href="route('contact.index')"
                        :class="[
                            'group relative transition-colors duration-150 after:absolute after:-bottom-1 after:left-0 after:h-px after:w-full after:origin-left after:bg-current after:transition-transform after:duration-200',
                            $page.url.startsWith('/contact')
                                ? 'text-earth-header font-semibold after:scale-x-100'
                                : 'text-earth hover:text-earth-header after:scale-x-0 hover:after:scale-x-100',
                        ]"
                    >
                        Contact
                    </Link>
                </nav>

                <!-- 3. Zone Utilisateur (Connecté vs Invité) -->
                <div class="hidden md:flex items-center gap-3">
                    <!-- Utilisateur Connecté -> Dropdown Profil -->
                    <template v-if="currentUser">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="flex items-center gap-2.5 p-1.5 pl-3 rounded-full border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition cursor-pointer"
                                >
                                    <User class="w-3.5 h-3.5 text-gray-400" />
                                    <span
                                        class="text-xs font-semibold text-gray-800"
                                    >
                                        {{
                                            currentUser.first_name ||
                                            currentUser.name
                                        }}
                                    </span>
                                    <ChevronDown
                                        class="w-3.5 h-3.5 text-gray-400 mr-1"
                                    />
                                </button>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent
                                align="end"
                                class="w-56 bg-white border-gray-200 shadow-md font-brand"
                            >
                                <DropdownMenuLabel
                                    class="text-xs text-gray-500 font-normal"
                                >
                                    Connecté en tant que <br />
                                    <strong
                                        class="text-gray-900 font-semibold"
                                        >{{ currentUser.email }}</strong
                                    >
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />

                                <!-- Lien Espace Membre -->
                                <DropdownMenuItem as-child>
                                    <Link
                                        :href="route('member.dashboard')"
                                        class="cursor-pointer flex items-center gap-2 py-2 text-xs sm:text-sm"
                                    >
                                        <User class="w-4 h-4 text-earth" />
                                        <span>Espace membre</span>
                                    </Link>
                                </DropdownMenuItem>

                                <!-- Lien Paramètres (Profil Jetstream) -->
                                <DropdownMenuItem as-child>
                                    <Link
                                        :href="route('profile.show')"
                                        class="cursor-pointer flex items-center gap-2 py-2 text-xs sm:text-sm"
                                    >
                                        <Settings
                                            class="w-4 h-4 text-gray-500"
                                        />
                                        <span>Paramètres du compte</span>
                                    </Link>
                                </DropdownMenuItem>

                                <DropdownMenuSeparator />

                                <!-- Déconnexion -->
                                <DropdownMenuItem
                                    class="cursor-pointer text-red-600 hover:bg-red-50 focus:bg-red-50 focus:text-red-700 flex items-center gap-2 py-2 text-xs sm:text-sm"
                                    @click="logout"
                                >
                                    <LogOut class="w-4 h-4 text-red-500" />
                                    <span>Déconnexion</span>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </template>

                    <!-- Invité (Non connecté) -->
                    <template v-else>
                        <Link :href="route('login')">
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-xs sm:text-sm text-gray-700 hover:text-gray-900"
                            >
                                Connexion
                            </Button>
                        </Link>
                        <Link :href="route('register')">
                            <Button
                                size="sm"
                                class="text-xs sm:text-sm bg-gray-900 hover:bg-gray-800 text-white font-medium h-9 shadow-xs"
                            >
                                Inscription
                            </Button>
                        </Link>
                    </template>
                </div>

                <!-- 4. Bouton Menu Hamburger Mobile -->
                <div class="ml-auto flex items-center md:hidden">
                    <button
                        type="button"
                        class="p-2 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        aria-label="Menu principal"
                    >
                        <component
                            :is="mobileMenuOpen ? X : Menu"
                            class="w-6 h-6"
                        />
                    </button>
                </div>
            </div>
        </div>

        <!-- 5. Menu Mobile Déroulant -->
        <div
            v-show="mobileMenuOpen"
            class="md:hidden border-t border-gray-100 bg-white px-4 pt-3 pb-6 space-y-3 shadow-lg"
        >
            <div class="space-y-1">
                <Link
                    :href="route('ateliers.index')"
                    class="block px-3 py-2 rounded-lg text-base font-medium text-gray-800 hover:bg-gray-50"
                    @click="mobileMenuOpen = false"
                >
                    Ateliers
                </Link>
                <Link
                    href="/stages"
                    class="block px-3 py-2 rounded-lg text-base font-medium text-gray-800 hover:bg-gray-50"
                    @click="mobileMenuOpen = false"
                >
                    Stages
                </Link>
                <Link
                    :href="route('calendrier.index')"
                    class="block px-3 py-2 rounded-lg text-base font-medium text-gray-800 hover:bg-gray-50 flex items-center justify-between"
                    @click="mobileMenuOpen = false"
                >
                    <span>Calendrier</span>
                </Link>
                <Link
                    href="/faq"
                    class="block px-3 py-2 rounded-lg text-base font-medium text-gray-800 hover:bg-gray-50"
                    @click="mobileMenuOpen = false"
                >
                    FAQ
                </Link>
                <Link
                    :href="route('contact.index')"
                    class="block px-3 py-2 rounded-lg text-base font-medium text-earth-header hover:bg-earth-light"
                    @click="mobileMenuOpen = false"
                >
                    Contact
                </Link>
            </div>

            <!-- Mobile Auth Section -->
            <div class="pt-4 border-t border-gray-100 space-y-2">
                <template v-if="currentUser">
                    <div class="px-3 py-1 text-xs text-gray-500">
                        Connecté en tant que
                        <strong>{{
                            currentUser.first_name || currentUser.name
                        }}</strong>
                    </div>
                    <Link
                        :href="route('member.dashboard')"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-base font-medium text-gray-900 bg-gray-50 hover:bg-gray-100"
                        @click="mobileMenuOpen = false"
                    >
                        <User class="w-4 h-4 text-earth" />
                        Espace membre
                    </Link>
                    <Link
                        :href="route('profile.show')"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50"
                        @click="mobileMenuOpen = false"
                    >
                        <Settings class="w-4 h-4 text-gray-500" />
                        Paramètres du compte
                    </Link>
                    <button
                        type="button"
                        class="w-full flex items-center gap-2 text-left px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50"
                        @click="logout"
                    >
                        <LogOut class="w-4 h-4" />
                        Déconnexion
                    </button>
                </template>

                <template v-else>
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <Link :href="route('login')" class="w-full">
                            <Button variant="outline" class="w-full text-sm"
                                >Connexion</Button
                            >
                        </Link>
                        <Link :href="route('register')" class="w-full">
                            <Button
                                class="w-full text-sm bg-gray-900 text-white"
                                >Inscription</Button
                            >
                        </Link>
                    </div>
                </template>
            </div>
        </div>
    </header>
    <CookieBanner />
</template>
