<script setup>
import { ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

import {
    ArrowLeft,
    Eye,
    EyeOff,
    KeyRound,
    Loader2,
} from "lucide-vue-next";

const props = defineProps({
    email: String,
    token: String,
});

const showPassword = ref(false);
const showConfirmation = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("password.update"), {
        preserveScroll: true,

        onFinish: () => {
            form.reset(
                "password",
                "password_confirmation"
            );
        },
    });
};
</script>

<template>
    <Head title="Réinitialiser le mot de passe" />

    <Nav />

    <main
        class="min-h-[calc(100vh-8rem)] bg-gray-50/50 font-brand py-10 sm:py-16 px-4"
    >
        <div class="w-full max-w-md mx-auto">
            <!-- Intro -->
            <div class="mb-8 text-center">
                <div
                    class="w-12 h-12 mx-auto mb-4 rounded-xl bg-gray-100 flex items-center justify-center"
                >
                    <KeyRound class="w-5 h-5 text-gray-700" />
                </div>

                <h1 class="text-4xl sm:text-5xl font-bold text-gray-900">
                    Nouveau mot de passe
                </h1>

                <p class="mt-3 text-sm text-gray-500">
                    Choisissez un nouveau mot de passe pour votre compte.
                </p>
            </div>

            <div
                class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 sm:p-8"
            >
                <form
                    class="space-y-5"
                    @submit.prevent="submit"
                >
                    <!-- Email -->
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
                            required
                            readonly
                            :aria-invalid="Boolean(form.errors.email)"
                            :class="[
                                'h-11 bg-gray-50 text-gray-600',
                                form.errors.email
                                    ? 'border-red-400'
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

                    <!-- Password -->
                    <div class="space-y-2">
                        <Label
                            for="password"
                            class="text-sm font-semibold text-gray-800"
                        >
                            Nouveau mot de passe
                        </Label>

                        <div class="relative">
                            <Input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                required
                                autofocus
                                :aria-invalid="Boolean(form.errors.password)"
                                :class="[
                                    'h-11 pr-11 bg-white',
                                    form.errors.password
                                        ? 'border-red-400 focus-visible:ring-red-200'
                                        : 'border-gray-200',
                                ]"
                            />

                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-700"
                                :aria-label="
                                    showPassword
                                        ? 'Masquer le mot de passe'
                                        : 'Afficher le mot de passe'
                                "
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff
                                    v-if="showPassword"
                                    class="w-4 h-4"
                                />

                                <Eye
                                    v-else
                                    class="w-4 h-4"
                                />
                            </button>
                        </div>

                        <p
                            v-if="form.errors.password"
                            class="text-xs font-medium text-red-600"
                        >
                            {{ form.errors.password }}
                        </p>

                        <p
                            v-else
                            class="text-xs leading-relaxed text-gray-500"
                        >
                            Minimum 10 caractères, avec une majuscule,
                            une minuscule et un chiffre.
                        </p>
                    </div>

                    <!-- Confirmation -->
                    <div class="space-y-2">
                        <Label
                            for="password_confirmation"
                            class="text-sm font-semibold text-gray-800"
                        >
                            Confirmer le mot de passe
                        </Label>

                        <div class="relative">
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="
                                    showConfirmation
                                        ? 'text'
                                        : 'password'
                                "
                                autocomplete="new-password"
                                required
                                :aria-invalid="
                                    Boolean(
                                        form.errors.password_confirmation
                                    )
                                "
                                :class="[
                                    'h-11 pr-11 bg-white',
                                    form.errors.password_confirmation
                                        ? 'border-red-400 focus-visible:ring-red-200'
                                        : 'border-gray-200',
                                ]"
                            />

                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-700"
                                :aria-label="
                                    showConfirmation
                                        ? 'Masquer la confirmation'
                                        : 'Afficher la confirmation'
                                "
                                @click="
                                    showConfirmation =
                                        !showConfirmation
                                "
                            >
                                <EyeOff
                                    v-if="showConfirmation"
                                    class="w-4 h-4"
                                />

                                <Eye
                                    v-else
                                    class="w-4 h-4"
                                />
                            </button>
                        </div>

                        <p
                            v-if="
                                form.errors.password_confirmation
                            "
                            class="text-xs font-medium text-red-600"
                        >
                            {{
                                form.errors.password_confirmation
                            }}
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

                        <KeyRound
                            v-else
                            class="w-4 h-4 mr-2"
                        />

                        {{
                            form.processing
                                ? "Réinitialisation..."
                                : "Réinitialiser mon mot de passe"
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
