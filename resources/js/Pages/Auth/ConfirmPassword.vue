<script setup>
import { ref } from "vue";
import { Head, useForm } from "@inertiajs/vue3";

import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

import {
    Eye,
    EyeOff,
    Loader2,
    LockKeyhole,
    ShieldCheck,
} from "lucide-vue-next";

const form = useForm({
    password: "",
});

const passwordInput = ref(null);
const showPassword = ref(false);

const submit = () => {
    form.post(route("password.confirm"), {
        preserveScroll: true,

        onFinish: () => {
            form.reset("password");

            passwordInput.value?.focus();
        },
    });
};
</script>

<template>
    <Head title="Confirmer votre mot de passe" />

    <Nav />

    <main
        class="min-h-[calc(100vh-8rem)] bg-gray-50/50 font-brand py-10 sm:py-16 px-4"
    >
        <div class="w-full max-w-md mx-auto">
            <div class="mb-8 text-center">
                <div
                    class="w-12 h-12 mx-auto mb-4 rounded-xl bg-gray-100 flex items-center justify-center"
                >
                    <ShieldCheck class="w-5 h-5 text-gray-700" />
                </div>

                <h1 class="text-4xl sm:text-5xl font-bold text-gray-900">
                    Confirmation requise
                </h1>

                <p class="mt-3 text-sm text-gray-500 leading-relaxed">
                    Pour votre sécurité, confirmez votre mot de passe avant de
                    poursuivre cette action.
                </p>
            </div>

            <div
                class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 sm:p-8"
            >
                <form
                    class="space-y-5"
                    @submit.prevent="submit"
                >
                    <div class="space-y-2">
                        <Label
                            for="password"
                            class="text-sm font-semibold text-gray-800"
                        >
                            Mot de passe
                        </Label>

                        <div class="relative">
                            <Input
                                id="password"
                                ref="passwordInput"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                autofocus
                                required
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

                        <LockKeyhole
                            v-else
                            class="w-4 h-4 mr-2"
                        />

                        {{
                            form.processing
                                ? "Vérification..."
                                : "Confirmer et continuer"
                        }}
                    </Button>
                </form>
            </div>
        </div>
    </main>

    <Footer />
</template>
