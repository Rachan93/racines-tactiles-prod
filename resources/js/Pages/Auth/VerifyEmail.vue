<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";

import { Button } from "@/Components/ui/button";

import {
    CheckCircle2,
    Loader2,
    LogOut,
    MailCheck,
    RefreshCw,
} from "lucide-vue-next";

const props = defineProps({
    status: String,
});

const form = useForm({});

const verificationLinkSent = computed(
    () => props.status === "verification-link-sent"
);

const submit = () => {
    form.post(route("verification.send"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Vérification de votre e-mail" />

    <Nav />

    <main
        class="min-h-[calc(100vh-8rem)] bg-gray-50/50 font-brand py-10 sm:py-16 px-4"
    >
        <div class="w-full max-w-md mx-auto">
            <div class="mb-8 text-center">
                <div
                    class="w-12 h-12 mx-auto mb-4 rounded-xl bg-gray-100 flex items-center justify-center"
                >
                    <MailCheck class="w-5 h-5 text-gray-700" />
                </div>

                <h1 class="text-4xl sm:text-5xl font-bold text-gray-900">
                    Vérifiez votre e-mail
                </h1>

                <p class="mt-3 text-sm text-gray-500 leading-relaxed">
                    Nous vous avons envoyé un lien de vérification.
                    Cliquez dessus pour activer pleinement votre espace membre.
                </p>
            </div>

            <div
                class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 sm:p-8"
            >
                <div
                    v-if="verificationLinkSent"
                    class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3"
                >
                    <CheckCircle2
                        class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"
                    />

                    <p class="text-sm text-emerald-800">
                        Un nouveau lien de vérification vient de vous être envoyé.
                    </p>
                </div>

                <div class="space-y-5">
                    <div
                        class="p-4 rounded-xl bg-gray-50 border border-gray-100"
                    >
                        <p class="text-sm text-gray-600 leading-relaxed">
                            Vous n'avez rien reçu ? Vérifiez vos courriers
                            indésirables ou demandez un nouvel e-mail.
                        </p>
                    </div>

                    <form @submit.prevent="submit">
                        <Button
                            type="submit"
                            class="w-full h-11 bg-blue-600 hover:bg-blue-500 text-white font-semibold"
                            :disabled="form.processing"
                        >
                            <Loader2
                                v-if="form.processing"
                                class="w-4 h-4 mr-2 animate-spin"
                            />

                            <RefreshCw
                                v-else
                                class="w-4 h-4 mr-2"
                            />

                            {{
                                form.processing
                                    ? "Envoi en cours..."
                                    : "Renvoyer l'e-mail de vérification"
                            }}
                        </Button>
                    </form>

                    <div
                        class="pt-5 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-center gap-3"
                    >
                        <Link
                            :href="route('profile.show')"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900"
                        >
                            Modifier mon profil
                        </Link>

                        <span class="hidden sm:block text-gray-300">
                            ·
                        </span>

                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900"
                        >
                            <LogOut class="w-3.5 h-3.5" />
                            Se déconnecter
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <Footer />
</template>
