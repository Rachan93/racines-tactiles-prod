<script setup>
import { ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import Checkbox from "@/Components/Checkbox.vue";
import {
    Eye,
    EyeOff,
    Loader2,
    LogIn,
    ArrowRight,
} from "lucide-vue-next";

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const showPassword = ref(false);

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: data.remember ? "on" : "",
    })).post(route("login"), {
        preserveScroll: true,
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Connexion" />

    <Nav />

    <main
        class="min-h-[calc(100vh-8rem)] bg-gray-50/50 font-brand py-10 sm:py-16 px-4"
    >
        <div class="w-full max-w-md mx-auto">
            <!-- Intro -->
            <div class="mb-8 text-center">
                <h1 class="text-4xl sm:text-5xl font-bold text-gray-900">
                    Connexion
                </h1>

                <p class="mt-3 text-sm text-gray-500">
                    Accédez à votre espace membre et gérez vos cours.
                </p>
            </div>

            <!-- Card -->
            <div
                class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 sm:p-8"
            >
                <div
                    v-if="status"
                    class="mb-5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-sm text-emerald-800"
                >
                    {{ status }}
                </div>

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
                            autocomplete="username"
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

                    <!-- Password -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-4">
                            <Label
                                for="password"
                                class="text-sm font-semibold text-gray-800"
                            >
                                Mot de passe
                            </Label>

                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline"
                            >
                                Mot de passe oublié ?
                            </Link>
                        </div>

                        <div class="relative">
                            <Input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
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

                    <!-- Remember -->
                    <label
                        class="flex items-center gap-2.5 cursor-pointer select-none"
                    >
                        <Checkbox
                            v-model:checked="form.remember"
                            name="remember"
                        />

                        <span class="text-sm text-gray-600">
                            Se souvenir de moi
                        </span>
                    </label>

                    <!-- Submit -->
                    <Button
                        type="submit"
                        class="w-full h-11 bg-blue-600 hover:bg-blue-500 text-white font-semibold"
                        :disabled="form.processing"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="w-4 h-4 mr-2 animate-spin"
                        />

                        <LogIn
                            v-else
                            class="w-4 h-4 mr-2"
                        />

                        {{
                            form.processing
                                ? "Connexion..."
                                : "Se connecter"
                        }}
                    </Button>
                </form>

                <div class="mt-6 pt-5 border-t border-gray-100 text-center">
                    <p class="text-sm text-gray-500">
                        Pas encore de compte ?
                    </p>

                    <Link
                        :href="route('register')"
                        class="mt-1 inline-flex items-center gap-1 text-sm font-semibold text-gray-900 hover:text-blue-600"
                    >
                        Créer un compte
                        <ArrowRight class="w-3.5 h-3.5" />
                    </Link>
                </div>
            </div>
        </div>
    </main>

    <Footer />
</template>
