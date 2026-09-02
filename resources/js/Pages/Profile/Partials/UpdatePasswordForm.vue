<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

import {
    CheckCircle2,
    Eye,
    EyeOff,
    KeyRound,
    Loader2,
} from "lucide-vue-next";

const currentPasswordInput = ref(null);
const passwordInput = ref(null);

const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showConfirmation = ref(false);

const form = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const updatePassword = () => {
    form.put(route("user-password.update"), {
        errorBag: "updatePassword",
        preserveScroll: true,

        onSuccess: () => {
            form.reset();
        },

        onError: () => {
            if (form.errors.password) {
                form.reset(
                    "password",
                    "password_confirmation"
                );

                passwordInput.value?.focus();

                return;
            }

            if (form.errors.current_password) {
                form.reset("current_password");

                currentPasswordInput.value?.focus();
            }
        },
    });
};

const fieldClass = (field) => [
    "h-11 pr-11 bg-white",
    form.errors[field]
        ? "border-red-400 focus-visible:ring-red-200"
        : "border-gray-200",
];
</script>

<template>
    <div class="p-6 sm:p-8">
        <div class="mb-6">
            <div class="flex items-center gap-2">
                <KeyRound class="w-5 h-5 text-gray-700" />

                <h3 class="text-lg font-bold text-gray-900">
                    Mot de passe
                </h3>
            </div>

            <p class="mt-2 text-sm text-gray-500">
                Utilisez un mot de passe unique et suffisamment robuste.
            </p>
        </div>

        <form
            class="space-y-5"
            @submit.prevent="updatePassword"
        >
            <div class="space-y-2">
                <Label for="current_password">
                    Mot de passe actuel
                </Label>

                <div class="relative">
                    <Input
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        :type="
                            showCurrentPassword
                                ? 'text'
                                : 'password'
                        "
                        autocomplete="current-password"
                        :class="
                            fieldClass('current_password')
                        "
                    />

                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-700"
                        @click="
                            showCurrentPassword =
                                !showCurrentPassword
                        "
                    >
                        <EyeOff
                            v-if="showCurrentPassword"
                            class="w-4 h-4"
                        />
                        <Eye
                            v-else
                            class="w-4 h-4"
                        />
                    </button>
                </div>

                <p
                    v-if="form.errors.current_password"
                    class="text-xs font-medium text-red-600"
                >
                    {{ form.errors.current_password }}
                </p>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="password">
                        Nouveau mot de passe
                    </Label>

                    <div class="relative">
                        <Input
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            :type="
                                showPassword
                                    ? 'text'
                                    : 'password'
                            "
                            autocomplete="new-password"
                            :class="fieldClass('password')"
                        />

                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-700"
                            @click="
                                showPassword = !showPassword
                            "
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

                <div class="space-y-2">
                    <Label for="password_confirmation">
                        Confirmation
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
                            class="h-11 pr-11"
                        />

                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-700"
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
                </div>
            </div>

            <p class="text-xs text-gray-500">
                Minimum 10 caractères, avec une majuscule,
                une minuscule et un chiffre.
            </p>

            <div
                class="flex flex-col sm:flex-row sm:items-center justify-end gap-3 pt-2"
            >
                <div
                    v-if="form.recentlySuccessful"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700"
                >
                    <CheckCircle2 class="w-4 h-4" />
                    Mot de passe modifié
                </div>

                <Button
                    type="submit"
                    variant="outline"
                    :disabled="form.processing"
                >
                    <Loader2
                        v-if="form.processing"
                        class="w-4 h-4 mr-2 animate-spin"
                    />

                    Modifier le mot de passe
                </Button>
            </div>
        </form>
    </div>
</template>
