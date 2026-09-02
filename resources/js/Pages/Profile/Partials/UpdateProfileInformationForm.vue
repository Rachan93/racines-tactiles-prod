<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { useForm } from "laravel-precognition-vue-inertia";

import Checkbox from "@/Components/Checkbox.vue";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

import {
    Building2,
    CheckCircle2,
    Loader2,
    Save,
} from "lucide-vue-next";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const verificationLinkSent = ref(false);

const form = useForm(
    "put",
    route("user-profile-information.update"),
    {
        first_name: props.user.first_name ?? "",
        last_name: props.user.last_name ?? "",
        email: props.user.email ?? "",

        birthday: props.user.birthday ?? "",
        phone_number: props.user.phone_number ?? "",

        address: props.user.address ?? "",
        locality: props.user.locality ?? "",
        postal_code: props.user.postal_code ?? "",

        billing: Boolean(props.user.billing),

        company_name: props.user.company_name ?? "",
        vat_number: props.user.vat_number ?? "",
        company_address: props.user.company_address ?? "",
        company_locality: props.user.company_locality ?? "",
        company_postal_code: props.user.company_postal_code ?? "",
    }
);

form.setValidationTimeout(300);

const fieldClass = (field) => [
    "h-11 bg-white",
    form.invalid(field)
        ? "border-red-400 focus-visible:ring-red-200"
        : "border-gray-200",
];

const updateProfileInformation = () => {
    form.submit({
        errorBag: "updateProfileInformation",
        preserveScroll: true,
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};
</script>

<template>
    <form
        class="p-6 sm:p-8 space-y-8"
        @submit.prevent="updateProfileInformation"
    >
        <!-- IDENTITÉ -->
        <div class="space-y-5">
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="first_name">Prénom</Label>

                    <Input
                        id="first_name"
                        v-model="form.first_name"
                        autocomplete="given-name"
                        required
                        :class="fieldClass('first_name')"
                        @change="form.validate('first_name')"
                    />

                    <p
                        v-if="form.errors.first_name"
                        class="text-xs font-medium text-red-600"
                    >
                        {{ form.errors.first_name }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="last_name">Nom</Label>

                    <Input
                        id="last_name"
                        v-model="form.last_name"
                        autocomplete="family-name"
                        required
                        :class="fieldClass('last_name')"
                        @change="form.validate('last_name')"
                    />

                    <p
                        v-if="form.errors.last_name"
                        class="text-xs font-medium text-red-600"
                    >
                        {{ form.errors.last_name }}
                    </p>
                </div>
            </div>

            <div class="space-y-2">
                <Label for="email">Adresse e-mail</Label>

                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    required
                    :class="fieldClass('email')"
                    @change="form.validate('email')"
                />

                <p
                    v-if="form.errors.email"
                    class="text-xs font-medium text-red-600"
                >
                    {{ form.errors.email }}
                </p>

                <div
                    v-if="
                        $page.props.jetstream.hasEmailVerification &&
                        user.email_verified_at === null
                    "
                    class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-900"
                >
                    <p>
                        Votre adresse e-mail n'est pas encore vérifiée.

                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="font-semibold underline underline-offset-2"
                            @click="sendEmailVerification"
                        >
                            Renvoyer le lien de vérification
                        </Link>
                    </p>

                    <p
                        v-if="verificationLinkSent"
                        class="mt-2 font-medium text-emerald-700"
                    >
                        Un nouveau lien vient de vous être envoyé.
                    </p>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="birthday">
                        Date de naissance
                    </Label>

                    <Input
                        id="birthday"
                        v-model="form.birthday"
                        type="date"
                        :class="fieldClass('birthday')"
                        @change="form.validate('birthday')"
                    />

                    <p
                        v-if="form.errors.birthday"
                        class="text-xs font-medium text-red-600"
                    >
                        {{ form.errors.birthday }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="phone_number">
                        Téléphone
                    </Label>

                    <Input
                        id="phone_number"
                        v-model="form.phone_number"
                        type="tel"
                        autocomplete="tel"
                        required
                        :class="fieldClass('phone_number')"
                        @change="form.validate('phone_number')"
                    />

                    <p
                        v-if="form.errors.phone_number"
                        class="text-xs font-medium text-red-600"
                    >
                        {{ form.errors.phone_number }}
                    </p>
                </div>
            </div>
        </div>

        <!-- ADRESSE -->
        <div class="pt-7 border-t border-gray-100 space-y-5">
            <div>
                <h3 class="font-bold text-gray-900">
                    Adresse
                </h3>

                <p class="mt-1 text-xs text-gray-500">
                    Adresse principale associée à votre compte.
                </p>
            </div>

            <div class="space-y-2">
                <Label for="address">
                    Rue et numéro
                </Label>

                <Input
                    id="address"
                    v-model="form.address"
                    autocomplete="street-address"
                    required
                    :class="fieldClass('address')"
                    @change="form.validate('address')"
                />

                <p
                    v-if="form.errors.address"
                    class="text-xs text-red-600"
                >
                    {{ form.errors.address }}
                </p>
            </div>

            <div class="grid sm:grid-cols-[1fr_160px] gap-4">
                <div class="space-y-2">
                    <Label for="locality">
                        Localité
                    </Label>

                    <Input
                        id="locality"
                        v-model="form.locality"
                        autocomplete="address-level2"
                        required
                        :class="fieldClass('locality')"
                        @change="form.validate('locality')"
                    />

                    <p
                        v-if="form.errors.locality"
                        class="text-xs text-red-600"
                    >
                        {{ form.errors.locality }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="postal_code">
                        Code postal
                    </Label>

                    <Input
                        id="postal_code"
                        v-model="form.postal_code"
                        autocomplete="postal-code"
                        required
                        :class="fieldClass('postal_code')"
                        @change="form.validate('postal_code')"
                    />

                    <p
                        v-if="form.errors.postal_code"
                        class="text-xs text-red-600"
                    >
                        {{ form.errors.postal_code }}
                    </p>
                </div>
            </div>
        </div>

        <!-- FACTURATION -->
        <div class="pt-7 border-t border-gray-100 space-y-5">
            <label
                class="flex items-start gap-3 cursor-pointer"
            >
                <Checkbox
                    v-model:checked="form.billing"
                    name="billing"
                    @change="form.validate('billing')"
                />

                <div>
                    <div
                        class="flex items-center gap-2 text-sm font-bold text-gray-900"
                    >
                        <Building2 class="w-4 h-4" />
                        Facturation professionnelle
                    </div>

                    <p class="mt-1 text-xs text-gray-500">
                        Utiliser des coordonnées professionnelles pour la
                        facturation.
                    </p>
                </div>
            </label>

            <div
                v-if="form.billing"
                class="grid sm:grid-cols-2 gap-4 p-5 bg-gray-50 border border-gray-200 rounded-xl"
            >
                <div class="space-y-2">
                    <Label for="company_name">
                        Société
                    </Label>

                    <Input
                        id="company_name"
                        v-model="form.company_name"
                        :class="fieldClass('company_name')"
                        @change="form.validate('company_name')"
                    />

                    <p
                        v-if="form.errors.company_name"
                        class="text-xs text-red-600"
                    >
                        {{ form.errors.company_name }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="vat_number">
                        Numéro de TVA
                    </Label>

                    <Input
                        id="vat_number"
                        v-model="form.vat_number"
                        placeholder="BE..."
                        :class="fieldClass('vat_number')"
                        @change="form.validate('vat_number')"
                    />

                    <p
                        v-if="form.errors.vat_number"
                        class="text-xs text-red-600"
                    >
                        {{ form.errors.vat_number }}
                    </p>
                </div>

                <div class="sm:col-span-2 space-y-2">
                    <Label for="company_address">
                        Adresse
                    </Label>

                    <Input
                        id="company_address"
                        v-model="form.company_address"
                        :class="fieldClass('company_address')"
                        @change="form.validate('company_address')"
                    />

                    <p
                        v-if="form.errors.company_address"
                        class="text-xs text-red-600"
                    >
                        {{ form.errors.company_address }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="company_locality">
                        Localité
                    </Label>

                    <Input
                        id="company_locality"
                        v-model="form.company_locality"
                        :class="fieldClass('company_locality')"
                        @change="form.validate('company_locality')"
                    />

                    <p
                        v-if="form.errors.company_locality"
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
                        :class="
                            fieldClass('company_postal_code')
                        "
                        @change="form.validate('company_postal_code')"
                    />

                    <p
                        v-if="form.errors.company_postal_code"
                        class="text-xs text-red-600"
                    >
                        {{ form.errors.company_postal_code }}
                    </p>
                </div>
            </div>
        </div>

        <!-- ACTION -->
        <div
            class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-end gap-3"
        >
            <div
                v-if="form.recentlySuccessful"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700"
            >
                <CheckCircle2 class="w-4 h-4" />
                Modifications enregistrées
            </div>

            <Button
                type="submit"
                class="bg-blue-600 hover:bg-blue-500 text-white h-10"
                :disabled="form.processing"
            >
                <Loader2
                    v-if="form.processing"
                    class="w-4 h-4 mr-2 animate-spin"
                />

                <Save
                    v-else
                    class="w-4 h-4 mr-2"
                />

                Enregistrer
            </Button>
        </div>
    </form>
</template>
