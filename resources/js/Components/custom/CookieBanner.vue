<script setup>
import { onMounted, ref } from "vue";
import { Link } from "@inertiajs/vue3";

import { Button } from "@/Components/ui/button";

import { Cookie, X } from "lucide-vue-next";

const STORAGE_KEY = "cookie_banner_acknowledged";

const visible = ref(false);

onMounted(() => {
    visible.value = localStorage.getItem(STORAGE_KEY) !== "true";
});

const dismiss = () => {
    localStorage.setItem(STORAGE_KEY, "true");

    visible.value = false;
};
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="visible"
            class="fixed bottom-4 inset-x-4 z-[100] font-brand pointer-events-none"
        >
            <div
                class="max-w-3xl mx-auto bg-gray-900 border border-gray-800 rounded-xl shadow-xl px-4 py-3.5 sm:px-5 pointer-events-auto"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4"
                >
                    <div
                        class="flex items-start sm:items-center gap-3 flex-1 min-w-0"
                    >
                        <div
                            class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center shrink-0"
                        >
                            <Cookie class="w-4 h-4 text-white" />
                        </div>

                        <p
                            class="text-xs sm:text-sm text-gray-200 leading-relaxed"
                        >
                            Ce site utilise uniquement les technologies
                            nécessaires à son fonctionnement et à la
                            sécurisation de votre espace membre.

                            <Link
                                :href="route('cookies.policy')"
                                class="font-semibold text-white underline underline-offset-2 hover:text-gray-200"
                            >
                                Politique des cookies
                            </Link>
                        </p>
                    </div>

                    <div
                        class="flex items-center gap-2 sm:shrink-0 ml-12 sm:ml-0"
                    >
                        <Button
                            type="button"
                            size="sm"
                            class="h-8 bg-white text-gray-900 hover:bg-gray-100 px-4 font-semibold"
                            @click="dismiss"
                        >
                            Compris
                        </Button>

                        <button
                            type="button"
                            aria-label="Fermer"
                            class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-white hover:bg-white/10 transition"
                            @click="dismiss"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
