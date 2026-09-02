<script setup>
import { nextTick, ref } from "vue";
import { Head, useForm } from "@inertiajs/vue3";

import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

import {
    KeyRound,
    Loader2,
    RotateCcw,
    ShieldCheck,
} from "lucide-vue-next";

const recovery = ref(false);

const form = useForm({
    code: "",
    recovery_code: "",
});

const recoveryCodeInput = ref(null);
const codeInput = ref(null);

const toggleRecovery = async () => {
    recovery.value = !recovery.value;

    form.clearErrors();

    await nextTick();

    if (recovery.value) {
        form.code = "";
        recoveryCodeInput.value?.focus();
    } else {
        form.recovery_code = "";
        codeInput.value?.focus();
    }
};

const submit = () => {
    form.post(route("two-factor.login"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Double authentification" />

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
                    Double authentification
                </h1>

                <p
                    v-if="!recovery"
                    class="mt-3 text-sm text-gray-500 leading-relaxed"
                >
                    Entrez le code généré par votre application
                    d'authentification pour terminer votre connexion.
                </p>

                <p
                    v-else
                    class="mt-3 text-sm text-gray-500 leading-relaxed"
                >
                    Entrez l'un de vos codes de récupération pour accéder à
                    votre compte.
                </p>
            </div>

            <div
                class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 sm:p-8"
            >
                <form
                    class="space-y-5"
                    @submit.prevent="submit"
                >
                    <!-- Authenticator code -->
                    <div
                        v-if="!recovery"
                        class="space-y-2"
                    >
                        <Label
                            for="code"
                            class="text-sm font-semibold text-gray-800"
                        >
                            Code d'authentification
                        </Label>

                        <Input
                            id="code"
                            ref="codeInput"
                            v-model="form.code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            autofocus
                            placeholder="123456"
                            :aria-invalid="Boolean(form.errors.code)"
                            :class="[
                                'h-12 bg-white text-center text-lg tracking-[0.3em] font-semibold',
                                form.errors.code
                                    ? 'border-red-400 focus-visible:ring-red-200'
                                    : 'border-gray-200',
                            ]"
                        />

                        <p
                            v-if="form.errors.code"
                            class="text-xs font-medium text-red-600"
                        >
                            {{ form.errors.code }}
                        </p>
                    </div>

                    <!-- Recovery code -->
                    <div
                        v-else
                        class="space-y-2"
                    >
                        <Label
                            for="recovery_code"
                            class="text-sm font-semibold text-gray-800"
                        >
                            Code de récupération
                        </Label>

                        <Input
                            id="recovery_code"
                            ref="recoveryCodeInput"
                            v-model="form.recovery_code"
                            type="text"
                            autocomplete="one-time-code"
                            placeholder="Votre code de récupération"
                            :aria-invalid="
                                Boolean(form.errors.recovery_code)
                            "
                            :class="[
                                'h-11 bg-white',
                                form.errors.recovery_code
                                    ? 'border-red-400 focus-visible:ring-red-200'
                                    : 'border-gray-200',
                            ]"
                        />

                        <p
                            v-if="form.errors.recovery_code"
                            class="text-xs font-medium text-red-600"
                        >
                            {{ form.errors.recovery_code }}
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
                                ? "Vérification..."
                                : "Continuer"
                        }}
                    </Button>

                    <div class="pt-5 border-t border-gray-100 text-center">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900"
                            @click="toggleRecovery"
                        >
                            <RotateCcw class="w-3.5 h-3.5" />

                            <template v-if="!recovery">
                                Utiliser un code de récupération
                            </template>

                            <template v-else>
                                Utiliser l'application d'authentification
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <Footer />
</template>
