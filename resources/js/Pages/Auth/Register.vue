<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import { useForm } from "laravel-precognition-vue-inertia";

import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";

import Checkbox from "@/Components/Checkbox.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

import {
    ArrowLeft,
    Building2,
    Eye,
    EyeOff,
    Loader2,
    UserPlus,
} from "lucide-vue-next";

const showPassword = ref(false);
const showConfirmation = ref(false);

const form = useForm("post", route("register"), {
    last_name: "",
    first_name: "",
    email: "",
    password: "",
    password_confirmation: "",
    birthday: "",
    phone_number: "",
    address: "",
    locality: "",
    postal_code: "",

    company_name: "",
    vat_number: "",
    company_address: "",
    company_locality: "",
    company_postal_code: "",

    billing: false,
    terms: false,
});

form.setValidationTimeout(300);

const validate = (field) => {
    form.validate(field);
};

const fieldClass = (field) => [
    "h-11 bg-white border-gray-200",
    form.invalid(field) ? "border-red-400 focus-visible:ring-red-200" : "",
];

const submit = () => {
    form.submit({
        preserveScroll: true,

        onSuccess: () => {
            form.reset("password", "password_confirmation");
        },
    });
};
</script>

<template>
    <Head title="Créer un compte" />

    <Nav />

    <main class="bg-gray-50/50 font-brand py-10 sm:py-16 px-4">
        <div class="max-w-3xl mx-auto">
            <!-- Intro -->
            <div class="mb-8 text-center">
                <h1 class="text-4xl sm:text-5xl font-bold text-gray-900">
                    Créer un compte
                </h1>

                <p class="mt-3 text-sm text-gray-500 max-w-xl mx-auto">
                    Inscrivez-vous pour réserver vos cours, gérer vos présences
                    et suivre vos modules.
                </p>
            </div>

            <form
                class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden"
                @submit.prevent="submit"
            >
                <!-- ========================================= -->
                <!-- IDENTITÉ                                  -->
                <!-- ========================================= -->
                <section class="p-6 sm:p-8 space-y-5">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">
                            Vos informations
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Informations utilisées pour votre compte membre.
                        </p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <!-- Prénom -->
                        <div class="space-y-2">
                            <Label
                                for="first_name"
                                class="text-sm font-semibold"
                            >
                                Prénom
                            </Label>

                            <Input
                                id="first_name"
                                v-model="form.first_name"
                                autocomplete="given-name"
                                autofocus
                                required
                                :aria-invalid="form.invalid('first_name')"
                                :class="fieldClass('first_name')"
                                @change="validate('first_name')"
                            />

                            <p
                                v-if="form.invalid('first_name')"
                                class="text-xs font-medium text-red-600"
                            >
                                {{ form.errors.first_name }}
                            </p>
                        </div>

                        <!-- Nom -->
                        <div class="space-y-2">
                            <Label
                                for="last_name"
                                class="text-sm font-semibold"
                            >
                                Nom
                            </Label>

                            <Input
                                id="last_name"
                                v-model="form.last_name"
                                autocomplete="family-name"
                                required
                                :aria-invalid="form.invalid('last_name')"
                                :class="fieldClass('last_name')"
                                @change="validate('last_name')"
                            />

                            <p
                                v-if="form.invalid('last_name')"
                                class="text-xs font-medium text-red-600"
                            >
                                {{ form.errors.last_name }}
                            </p>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="space-y-2">
                        <Label for="email" class="text-sm font-semibold">
                            Adresse e-mail
                        </Label>

                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            @change="
                                () => {
                                    console.log('CHANGE EMAIL', form.email);
                                    form.validate('email');
                                }
                            "
                        />

                        <p
                            v-if="form.invalid('email')"
                            class="text-xs font-medium text-red-600"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <!-- Birthday -->
                        <div class="space-y-2">
                            <Label for="birthday" class="text-sm font-semibold">
                                Date de naissance
                            </Label>

                            <Input
                                id="birthday"
                                v-model="form.birthday"
                                type="date"
                                :aria-invalid="form.invalid('birthday')"
                                :class="fieldClass('birthday')"
                                @change="validate('birthday')"
                            />

                            <p
                                v-if="form.invalid('birthday')"
                                class="text-xs font-medium text-red-600"
                            >
                                {{ form.errors.birthday }}
                            </p>
                        </div>

                        <!-- Phone -->
                        <div class="space-y-2">
                            <Label
                                for="phone_number"
                                class="text-sm font-semibold"
                            >
                                Téléphone
                            </Label>

                            <Input
                                id="phone_number"
                                v-model="form.phone_number"
                                type="tel"
                                autocomplete="tel"
                                required
                                placeholder="+32 ..."
                                :aria-invalid="form.invalid('phone_number')"
                                :class="fieldClass('phone_number')"
                                @change="validate('phone_number')"
                            />

                            <p
                                v-if="form.invalid('phone_number')"
                                class="text-xs font-medium text-red-600"
                            >
                                {{ form.errors.phone_number }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ========================================= -->
                <!-- ADRESSE                                   -->
                <!-- ========================================= -->
                <section class="p-6 sm:p-8 space-y-5 border-t border-gray-100">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Adresse</h2>
                    </div>

                    <div class="space-y-2">
                        <Label for="address"> Rue et numéro </Label>

                        <Input
                            id="address"
                            v-model="form.address"
                            autocomplete="street-address"
                            required
                            :aria-invalid="form.invalid('address')"
                            :class="fieldClass('address')"
                            @change="validate('address')"
                        />

                        <p
                            v-if="form.invalid('address')"
                            class="text-xs font-medium text-red-600"
                        >
                            {{ form.errors.address }}
                        </p>
                    </div>

                    <div class="grid sm:grid-cols-[1fr_160px] gap-4">
                        <div class="space-y-2">
                            <Label for="locality"> Localité </Label>

                            <Input
                                id="locality"
                                v-model="form.locality"
                                autocomplete="address-level2"
                                required
                                :aria-invalid="form.invalid('locality')"
                                :class="fieldClass('locality')"
                                @change="validate('locality')"
                            />

                            <p
                                v-if="form.invalid('locality')"
                                class="text-xs font-medium text-red-600"
                            >
                                {{ form.errors.locality }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="postal_code"> Code postal </Label>

                            <Input
                                id="postal_code"
                                v-model="form.postal_code"
                                autocomplete="postal-code"
                                required
                                :aria-invalid="form.invalid('postal_code')"
                                :class="fieldClass('postal_code')"
                                @change="validate('postal_code')"
                            />

                            <p
                                v-if="form.invalid('postal_code')"
                                class="text-xs font-medium text-red-600"
                            >
                                {{ form.errors.postal_code }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ========================================= -->
                <!-- PASSWORD                                  -->
                <!-- ========================================= -->
                <section class="p-6 sm:p-8 space-y-5 border-t border-gray-100">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">
                            Sécurité
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            Minimum 10 caractères, avec une majuscule, une
                            minuscule et un chiffre.
                        </p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <!-- Password -->
                        <div class="space-y-2">
                            <Label for="password"> Mot de passe </Label>

                            <div class="relative">
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    required
                                    :aria-invalid="form.invalid('password')"
                                    :class="[
                                        ...fieldClass('password'),
                                        'pr-10',
                                    ]"
                                    @change="
                                        form.password_confirmation
                                            ? validate('password')
                                            : null
                                    "
                                />

                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-700"
                                    @click="showPassword = !showPassword"
                                >
                                    <EyeOff
                                        v-if="showPassword"
                                        class="w-4 h-4"
                                    />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>

                            <p
                                v-if="form.invalid('password')"
                                class="text-xs font-medium text-red-600"
                            >
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Confirmation -->
                        <div class="space-y-2">
                            <Label for="password_confirmation">
                                Confirmation
                            </Label>

                            <div class="relative">
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="
                                        showConfirmation ? 'text' : 'password'
                                    "
                                    autocomplete="new-password"
                                    required
                                    :aria-invalid="
                                        form.invalid('password_confirmation')
                                    "
                                    :class="[
                                        ...fieldClass('password_confirmation'),
                                        'pr-10',
                                    ]"
                                    @change="validate('password')"
                                />

                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-700"
                                    @click="
                                        showConfirmation = !showConfirmation
                                    "
                                >
                                    <EyeOff
                                        v-if="showConfirmation"
                                        class="w-4 h-4"
                                    />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>

                            <p
                                v-if="form.invalid('password_confirmation')"
                                class="text-xs font-medium text-red-600"
                            >
                                {{ form.errors.password_confirmation }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ========================================= -->
                <!-- FACTURATION                               -->
                <!-- ========================================= -->
                <section class="p-6 sm:p-8 space-y-5 border-t border-gray-100">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <Checkbox
                            v-model:checked="form.billing"
                            name="billing"
                            @change="validate('billing')"
                        />

                        <div>
                            <div
                                class="font-semibold text-sm text-gray-900 flex items-center gap-1.5"
                            >
                                <Building2 class="w-4 h-4" />
                                J'ai besoin d'une facturation professionnelle
                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                Ajoutez les coordonnées de votre entreprise.
                            </p>
                        </div>
                    </label>

                    <div
                        v-if="form.billing"
                        class="grid sm:grid-cols-2 gap-4 p-4 sm:p-5 rounded-xl border border-gray-200 bg-gray-50"
                    >
                        <div class="space-y-2">
                            <Label for="company_name"> Société </Label>

                            <Input
                                id="company_name"
                                v-model="form.company_name"
                                :class="fieldClass('company_name')"
                                @change="validate('company_name')"
                            />

                            <p
                                v-if="form.invalid('company_name')"
                                class="text-xs text-red-600"
                            >
                                {{ form.errors.company_name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="vat_number"> Numéro de TVA </Label>

                            <Input
                                id="vat_number"
                                v-model="form.vat_number"
                                placeholder="BE..."
                                :class="fieldClass('vat_number')"
                                @change="validate('vat_number')"
                            />

                            <p
                                v-if="form.invalid('vat_number')"
                                class="text-xs text-red-600"
                            >
                                {{ form.errors.vat_number }}
                            </p>
                        </div>

                        <div class="sm:col-span-2 space-y-2">
                            <Label for="company_address"> Adresse </Label>

                            <Input
                                id="company_address"
                                v-model="form.company_address"
                                :class="fieldClass('company_address')"
                                @change="validate('company_address')"
                            />

                            <p
                                v-if="form.invalid('company_address')"
                                class="text-xs text-red-600"
                            >
                                {{ form.errors.company_address }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="company_locality"> Localité </Label>

                            <Input
                                id="company_locality"
                                v-model="form.company_locality"
                                :class="fieldClass('company_locality')"
                                @change="validate('company_locality')"
                            />

                            <p
                                v-if="form.invalid('company_locality')"
                                class="text-xs text-red-600"
                            >
                                {{ form.errors.company_locality }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="company_postal_code">
                                Code postal
                            </Label>

                            <Input
                                id="company_postal_code"
                                v-model="form.company_postal_code"
                                :class="fieldClass('company_postal_code')"
                                @change="validate('company_postal_code')"
                            />

                            <p
                                v-if="form.invalid('company_postal_code')"
                                class="text-xs text-red-600"
                            >
                                {{ form.errors.company_postal_code }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ========================================= -->
                <!-- TERMS + SUBMIT                            -->
                <!-- ========================================= -->
                <section
                    class="p-6 sm:p-8 space-y-5 border-t border-gray-100 bg-gray-50/50"
                >
                    <div
                        v-if="
                            $page.props.jetstream
                                .hasTermsAndPrivacyPolicyFeature
                        "
                    >
                        <label class="flex items-start gap-3 cursor-pointer">
                            <Checkbox
                                v-model:checked="form.terms"
                                name="terms"
                                @change="validate('terms')"
                            />

                            <span class="text-sm leading-relaxed text-gray-600">
                                J'accepte les
                                <a
                                    target="_blank"
                                    :href="route('terms.show')"
                                    class="font-medium text-gray-900 underline underline-offset-2"
                                >
                                    conditions d'utilisation
                                </a>
                                et la
                                <a
                                    target="_blank"
                                    :href="route('policy.show')"
                                    class="font-medium text-gray-900 underline underline-offset-2"
                                >
                                    politique de confidentialité </a
                                >.
                            </span>
                        </label>

                        <p
                            v-if="form.invalid('terms')"
                            class="mt-2 text-xs font-medium text-red-600"
                        >
                            {{ form.errors.terms }}
                        </p>
                    </div>

                    <Button
                        type="submit"
                        class="w-full h-11 bg-blue-600 hover:bg-blue-500 text-white font-semibold"
                        :disabled="form.processing || form.validating"
                    >
                        <Loader2
                            v-if="form.processing || form.validating"
                            class="w-4 h-4 mr-2 animate-spin"
                        />

                        <UserPlus v-else class="w-4 h-4 mr-2" />

                        {{
                            form.processing
                                ? "Création du compte..."
                                : "Créer mon compte"
                        }}
                    </Button>

                    <div class="text-center">
                        <Link
                            :href="route('login')"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeft class="w-3.5 h-3.5" />
                            J'ai déjà un compte
                        </Link>
                    </div>
                </section>
            </form>
        </div>
    </main>

    <Footer />
</template>
