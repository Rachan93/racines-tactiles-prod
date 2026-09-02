<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import ResponsiveModal from "@/Components/custom/ResponsiveModal.vue";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

import {
    AlertTriangle,
    Loader2,
    Trash2,
} from "lucide-vue-next";

const confirmingUserDeletion = ref(false);

const form = useForm({
    password: "",
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
    form.clearErrors();
};

const deleteUser = () => {
    form.delete(route("current-user.destroy"), {
        preserveScroll: true,

        onSuccess: () => closeModal(),

        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <div class="p-6 sm:p-8">
        <div class="flex items-start gap-3">
            <div
                class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center shrink-0"
            >
                <AlertTriangle class="w-5 h-5 text-red-600" />
            </div>

            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900">
                    Supprimer mon compte
                </h3>

                <p class="mt-1 text-sm text-gray-500 max-w-2xl">
                    Cette action supprime définitivement votre compte.
                    Elle est irréversible.
                </p>
            </div>
        </div>

        <div class="mt-6">
            <Button
                type="button"
                variant="destructive"
                @click="confirmUserDeletion"
            >
                <Trash2 class="w-4 h-4 mr-2" />
                Supprimer mon compte
            </Button>
        </div>

        <ResponsiveModal
            v-model:open="confirmingUserDeletion"
            title="Supprimer définitivement votre compte ?"
            description="Cette action ne pourra pas être annulée."
        >
            <div class="space-y-5">
                <div
                    class="p-4 bg-red-50 border border-red-200 rounded-xl"
                >
                    <p class="text-sm text-red-800 leading-relaxed">
                        Vos données et ressources associées à ce compte
                        seront supprimées. Saisissez votre mot de passe
                        pour confirmer.
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="delete_password">
                        Mot de passe
                    </Label>

                    <Input
                        id="delete_password"
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        :aria-invalid="
                            Boolean(form.errors.password)
                        "
                        @keyup.enter="deleteUser"
                    />

                    <p
                        v-if="form.errors.password"
                        class="text-xs font-medium text-red-600"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>
            </div>

            <template #footer>
                <Button
                    type="button"
                    variant="outline"
                    @click="closeModal"
                >
                    Annuler
                </Button>

                <Button
                    type="button"
                    variant="destructive"
                    :disabled="form.processing"
                    @click="deleteUser"
                >
                    <Loader2
                        v-if="form.processing"
                        class="w-4 h-4 mr-2 animate-spin"
                    />

                    <Trash2
                        v-else
                        class="w-4 h-4 mr-2"
                    />

                    Supprimer définitivement
                </Button>
            </template>
        </ResponsiveModal>
    </div>
</template>
