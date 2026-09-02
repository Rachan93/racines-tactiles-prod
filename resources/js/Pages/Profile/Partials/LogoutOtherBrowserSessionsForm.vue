<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import ResponsiveModal from "@/Components/custom/ResponsiveModal.vue";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

import {
    CheckCircle2,
    Laptop,
    Loader2,
    LogOut,
    MonitorSmartphone,
    Smartphone,
} from "lucide-vue-next";

defineProps({
    sessions: {
        type: Array,
        default: () => [],
    },
});

const confirmingLogout = ref(false);

const form = useForm({
    password: "",
});

const confirmLogout = () => {
    confirmingLogout.value = true;
};

const closeModal = () => {
    confirmingLogout.value = false;

    form.reset();
    form.clearErrors();
};

const logoutOtherBrowserSessions = () => {
    form.delete(route("other-browser-sessions.destroy"), {
        preserveScroll: true,

        onSuccess: () => closeModal(),

        onFinish: () => {
            form.reset("password");
        },
    });
};
</script>

<template>
    <div class="p-6 sm:p-8">
        <div class="flex items-start gap-3">
            <div
                class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center shrink-0"
            >
                <MonitorSmartphone class="w-5 h-5 text-gray-700" />
            </div>

            <div>
                <h3 class="text-lg font-bold text-gray-900">
                    Sessions ouvertes
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Consultez les appareils récemment connectés à votre compte.
                </p>
            </div>
        </div>

        <div
            v-if="sessions.length"
            class="mt-6 divide-y divide-gray-100 border-y border-gray-100"
        >
            <div
                v-for="(session, index) in sessions"
                :key="index"
                class="py-4 flex items-center gap-3"
            >
                <div
                    class="w-10 h-10 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-center shrink-0"
                >
                    <Laptop
                        v-if="session.agent?.is_desktop"
                        class="w-5 h-5 text-gray-600"
                    />

                    <Smartphone
                        v-else
                        class="w-5 h-5 text-gray-600"
                    />
                </div>

                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800">
                        {{
                            session.agent?.platform ||
                            "Appareil inconnu"
                        }}
                        ·
                        {{
                            session.agent?.browser ||
                            "Navigateur inconnu"
                        }}
                    </p>

                    <p class="mt-0.5 text-xs text-gray-500">
                        {{ session.ip_address }}

                        <template v-if="session.is_current_device">
                            ·
                            <span class="font-semibold text-emerald-600">
                                Cet appareil
                            </span>
                        </template>

                        <template v-else>
                            · Dernière activité
                            {{ session.last_active }}
                        </template>
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <Button
                type="button"
                variant="outline"
                @click="confirmLogout"
            >
                <LogOut class="w-4 h-4 mr-2" />
                Déconnecter les autres appareils
            </Button>

            <span
                v-if="form.recentlySuccessful"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700"
            >
                <CheckCircle2 class="w-4 h-4" />
                Sessions fermées
            </span>
        </div>

        <ResponsiveModal
            v-model:open="confirmingLogout"
            title="Déconnecter les autres appareils"
            description="Confirmez cette action avec votre mot de passe."
        >
            <div class="space-y-2">
                <Label for="logout_password">
                    Mot de passe
                </Label>

                <Input
                    id="logout_password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    :aria-invalid="
                        Boolean(form.errors.password)
                    "
                    @keyup.enter="
                        logoutOtherBrowserSessions
                    "
                />

                <p
                    v-if="form.errors.password"
                    class="text-xs font-medium text-red-600"
                >
                    {{ form.errors.password }}
                </p>
            </div>

            <template #footer>
                <Button
                    variant="outline"
                    type="button"
                    @click="closeModal"
                >
                    Annuler
                </Button>

                <Button
                    type="button"
                    class="bg-gray-900 hover:bg-gray-800"
                    :disabled="form.processing"
                    @click="logoutOtherBrowserSessions"
                >
                    <Loader2
                        v-if="form.processing"
                        class="w-4 h-4 mr-2 animate-spin"
                    />

                    Déconnecter
                </Button>
            </template>
        </ResponsiveModal>
    </div>
</template>
