<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";

import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

import { ArrowLeft, CheckCircle2, Loader2, Mail } from "lucide-vue-next";

defineProps({
    status: String,
});

const form = useForm({
    email: "",
});

const submit = () => {
    form.post(route("password.email"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Mot de passe oublié" />

    <Nav />

    <main
        class="min-h-[calc(100vh-8rem)] bg-gray-50/50 font-brand py-10 sm:py-16 px-4"
    >
        <div class="w-full max-w-md mx-auto">
            <!-- Intro -->
            <div class="mb-8 text-center">
                <h1
                    class="text-4xl sm:text-[2.65rem] font-bold text-gray-900 whitespace-nowrap"
                >
                    Mot de passe oublié ?
                </h1>

                <p class="mt-3 text-sm text-gray-500 leading-relaxed">
                    Indiquez l'adresse e-mail liée à votre compte. Nous vous
                    enverrons un lien pour choisir un nouveau mot de passe.
                </p>
            </div>

            <div
                class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 sm:p-8"
            >
                <!-- Confirmation envoi -->
                <div
                    v-if="status"
                    class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3"
                >
                    <CheckCircle2
                        class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"
                    />

                    <p class="text-sm text-emerald-800">
                        {{ status }}
                    </p>
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label
                            for="email"
                            class="text-sm font-semibold text-gray-800"
                        >
                            Adresse e-mail
                        </Label>

                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            autofocus
                            required
                            placeholder="vous@exemple.be"
                            :aria-invalid="Boolean(form.errors.email)"
                            :class="[
                                'h-11 bg-white',
                                form.errors.email
                                    ? 'border-red-400 focus-visible:ring-red-200'
                                    : 'border-gray-200',
                            ]"
                        />

                        <p
                            v-if="form.errors.email"
                            class="text-xs font-medium text-red-600"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <Button
                        type="submit"
                        class="w-full h-11 bg-blue-600 hover:bg-blue-500 text-white font-semibold"
                        :disabled="form.processing"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="w-4 h-4 mr-2 animate-spin"
                        />

                        <Mail v-else class="w-4 h-4 mr-2" />

                        {{
                            form.processing
                                ? "Envoi en cours..."
                                : "Envoyer le lien de réinitialisation"
                        }}
                    </Button>
                </form>

                <div class="mt-6 pt-5 border-t border-gray-100 text-center">
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        <ArrowLeft class="w-3.5 h-3.5" />
                        Retour à la connexion
                    </Link>
                </div>
            </div>
        </div>
    </main>

    <Footer />
</template>
