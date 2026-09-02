<script setup>
import { nextTick, reactive, ref } from "vue";

import ResponsiveModal from "@/Components/custom/ResponsiveModal.vue";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

import {
    Eye,
    EyeOff,
    Loader2,
    LockKeyhole,
} from "lucide-vue-next";

const emit = defineEmits(["confirmed"]);

defineProps({
    title: {
        type: String,
        default: "Confirmez votre mot de passe",
    },

    content: {
        type: String,
        default:
            "Pour votre sécurité, confirmez votre mot de passe avant de poursuivre.",
    },

    button: {
        type: String,
        default: "Confirmer",
    },
});

const confirmingPassword = ref(false);
const showPassword = ref(false);

const form = reactive({
    password: "",
    error: "",
    processing: false,
});

const focusPassword = async () => {
    await nextTick();

    document
        .getElementById("confirmed-password-input")
        ?.focus();
};

const startConfirmingPassword = () => {
    axios
        .get(route("password.confirmation"))
        .then((response) => {
            if (response.data.confirmed) {
                emit("confirmed");

                return;
            }

            confirmingPassword.value = true;

            focusPassword();
        });
};

const confirmPassword = () => {
    if (form.processing) return;

    form.processing = true;
    form.error = "";

    axios
        .post(route("password.confirm"), {
            password: form.password,
        })
        .then(() => {
            form.processing = false;

            closeModal();

            nextTick(() => {
                emit("confirmed");
            });
        })
        .catch((error) => {
            form.processing = false;

            form.error =
                error.response?.data?.errors?.password?.[0] ??
                "Impossible de confirmer le mot de passe.";

            focusPassword();
        });
};

const closeModal = () => {
    confirmingPassword.value = false;

    form.password = "";
    form.error = "";
    form.processing = false;
    showPassword.value = false;
};
</script>

<template>
    <span>
        <!--
            On garde exactement le fonctionnement Jetstream :
            le bouton réel est fourni via le slot.
        -->
        <span @click="startConfirmingPassword">
            <slot />
        </span>

        <ResponsiveModal
            v-model:open="confirmingPassword"
            :title="title"
            :description="content"
        >
            <div class="space-y-5">
                <div
                    class="p-4 rounded-xl bg-gray-50 border border-gray-200 flex items-start gap-3"
                >
                    <LockKeyhole
                        class="w-5 h-5 text-gray-600 shrink-0 mt-0.5"
                    />

                    <p class="text-sm text-gray-600 leading-relaxed">
                        Cette vérification permet de protéger les actions
                        sensibles liées à votre compte.
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="confirmed-password-input">
                        Mot de passe
                    </Label>

                    <div class="relative">
                        <Input
                            id="confirmed-password-input"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="current-password"
                            placeholder="Votre mot de passe"
                            :aria-invalid="Boolean(form.error)"
                            :class="[
                                'h-11 pr-11',
                                form.error
                                    ? 'border-red-400 focus-visible:ring-red-200'
                                    : 'border-gray-200',
                            ]"
                            @input="form.error = ''"
                            @keyup.enter="confirmPassword"
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
                        v-if="form.error"
                        class="text-xs font-medium text-red-600"
                    >
                        {{ form.error }}
                    </p>
                </div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="form.processing"
                    @click="closeModal"
                >
                    Annuler
                </Button>

                <Button
                    type="button"
                    class="bg-blue-600 hover:bg-blue-500 text-white"
                    :disabled="
                        form.processing || !form.password
                    "
                    @click="confirmPassword"
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
                            : button
                    }}
                </Button>
            </template>
        </ResponsiveModal>
    </span>
</template>
