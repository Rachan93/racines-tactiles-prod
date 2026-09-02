<script setup>
import { Head, Link } from "@inertiajs/vue3";

import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";

import UpdateProfileInformationForm from "@/Pages/Profile/Partials/UpdateProfileInformationForm.vue";
import UpdatePasswordForm from "@/Pages/Profile/Partials/UpdatePasswordForm.vue";
import TwoFactorAuthenticationForm from "@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue";
import LogoutOtherBrowserSessionsForm from "@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue";
import DeleteUserForm from "@/Pages/Profile/Partials/DeleteUserForm.vue";

import {
    ArrowLeft,
    LockKeyhole,
    ShieldCheck,
    UserRound,
} from "lucide-vue-next";

defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Mon profil" />

    <Nav />

    <main class="min-h-screen bg-gray-50/50 font-brand py-8 sm:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-0 space-y-8">
            <!-- Header -->
            <div
                class="flex flex-col sm:flex-row sm:items-end justify-between gap-5"
            >
                <div>
                    <Link
                        :href="route('member.dashboard')"
                        class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 mb-4"
                    >
                        <ArrowLeft class="w-4 h-4" />
                        Retour à mon espace
                    </Link>

                    <h1
                        class="text-4xl sm:text-5xl font-bold leading-none text-gray-900"
                    >
                        Mon profil
                    </h1>

                    <p class="mt-3 text-base text-gray-500 max-w-2xl">
                        Gérez vos informations personnelles, votre sécurité et
                        les paramètres de votre compte.
                    </p>
                </div>
            </div>

            <!-- Informations -->
            <section
                v-if="$page.props.jetstream.canUpdateProfileInformation"
                class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden"
            >
                <div class="p-6 sm:p-8 border-b border-gray-100">
                    <div class="flex items-start gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center shrink-0"
                        >
                            <UserRound class="w-5 h-5 text-gray-700" />
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-gray-900">
                                Informations personnelles
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Coordonnées utilisées pour votre espace membre
                                et vos réservations.
                            </p>
                        </div>
                    </div>
                </div>

                <UpdateProfileInformationForm
                    :user="$page.props.auth.user"
                />
            </section>

            <!-- Sécurité -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <ShieldCheck class="w-5 h-5 text-gray-700" />

                    <h2 class="text-lg font-bold text-gray-900">
                        Sécurité du compte
                    </h2>
                </div>

                <section
                    v-if="$page.props.jetstream.canUpdatePassword"
                    class="bg-white border border-gray-200 rounded-2xl shadow-sm"
                >
                    <UpdatePasswordForm />
                </section>

                <section
                    v-if="
                        $page.props.jetstream
                            .canManageTwoFactorAuthentication
                    "
                    class="bg-white border border-gray-200 rounded-2xl shadow-sm"
                >
                    <TwoFactorAuthenticationForm
                        :requires-confirmation="
                            confirmsTwoFactorAuthentication
                        "
                    />
                </section>

                <section
                    class="bg-white border border-gray-200 rounded-2xl shadow-sm"
                >
                    <LogoutOtherBrowserSessionsForm
                        :sessions="sessions"
                    />
                </section>
            </div>

            <!-- Danger -->
            <section
                v-if="$page.props.jetstream.hasAccountDeletionFeatures"
                class="bg-white border border-red-200 rounded-2xl shadow-sm"
            >
                <DeleteUserForm />
            </section>
        </div>
    </main>

    <Footer />
</template>
