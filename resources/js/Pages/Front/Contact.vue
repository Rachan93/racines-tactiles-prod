<script setup>
import { Head } from "@inertiajs/vue3";
import { useForm } from "laravel-precognition-vue-inertia";
import { toast } from "vue-sonner";

import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

import { Mail, Phone, Send, Loader2, MapPin } from "lucide-vue-next";

const props = defineProps({
    contactDefaults: {
        type: Object,
        default: () => ({
            first_name: "",
            last_name: "",
            email: "",
        }),
    },
});

const form = useForm("post", route("contact.store"), {
    first_name: props.contactDefaults.first_name,
    last_name: props.contactDefaults.last_name,
    email: props.contactDefaults.email,
    subject: "",
    message: "",
});

form.setValidationTimeout(300);

const validate = (field) => {
    form.validate(field);
};

const fieldClass = (field) => [
    "h-11 bg-white border-gray-200 shadow-sm",
    form.invalid(field)
        ? "border-red-400 focus-visible:ring-red-100"
        : "focus-visible:ring-gray-100",
];
const submit = () => {
    form.submit({
        preserveScroll: true,

        onSuccess: () => {
            form.reset("subject", "message");

            toast.success("Message envoyé", {
                description:
                    "Votre message a bien été envoyé. Nous vous répondrons dès que possible.",
            });
        },
    });
};
</script>

<template>
    <Head title="Contact" />
    <Nav />

    <section id="contact" class="py-12 font-brand">
        <div class="bg-white pt-2 pb-20 px-4 sm:px-6 lg:pt-0 lg:pb-28 lg:px-8">
            <div class="relative max-w-lg mx-auto lg:max-w-6xl">
                <h1
                    class="text-5xl leading-9 text-gray-900 sm:text-6xl sm:leading-10 mb-12 mt-2"
                >
                    Contact
                </h1>

                <div
                    class="grid gap-12 border-t-2 border-gray-100 pt-10 lg:grid-cols-2 lg:gap-16"
                >
                    <!-- ========================================= -->
                    <!-- INFORMATIONS                              -->
                    <!-- ========================================= -->
                    <div>
                        <h2
                            class="text-3xl leading-9 text-gray-900 sm:text-4xl sm:leading-10"
                        >
                            Informations
                        </h2>

                        <p class="mt-6 text-xl leading-7 text-gray-500">
                            Une question sur les cours, un stage, un atelier ou
                            votre inscription ? Écrivez-nous directement ou
                            contactez l’atelier.
                        </p>

                        <div class="mt-10 space-y-7">
                            <div class="flex items-start">
                                <Phone
                                    class="h-5 w-5 shrink-0 text-earth mt-0.5"
                                />

                                <div class="ml-4">
                                    <p class="text-sm text-gray-500">
                                        Téléphone
                                    </p>

                                    <a
                                        href="tel:+32487328826"
                                        class="mt-1 block text-lg text-gray-900 underline underline-offset-4 hover:text-earth"
                                    >
                                        +32 487/32.88.26
                                    </a>

                                    <p class="mt-1 text-sm text-gray-500">
                                        Thomas Flamant
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <Mail
                                    class="h-5 w-5 shrink-0 text-earth mt-0.5"
                                />

                                <div class="ml-4">
                                    <p class="text-sm text-gray-500">E-mail</p>

                                    <a
                                        href="mailto:info@racines-tactiles.be"
                                        class="mt-1 block text-lg text-gray-900 underline underline-offset-4 hover:text-earth"
                                    >
                                        info@racines-tactiles.be
                                    </a>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <MapPin
                                    class="h-5 w-5 shrink-0 text-earth mt-0.5"
                                />

                                <div class="ml-4">
                                    <p class="text-sm text-gray-500">Atelier</p>

                                    <p class="mt-1 text-lg text-gray-900">
                                        rue Florimond Letroye 13
                                    </p>

                                    <p class="text-sm text-gray-500">
                                        1300 Wavre
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================= -->
                    <!-- FORMULAIRE                               -->
                    <!-- ========================================= -->
                    <div>
                        <h2
                            class="text-3xl leading-9 text-gray-900 sm:text-4xl sm:leading-10"
                        >
                            Envoyer un message
                        </h2>

                        <p class="mt-6 text-lg leading-7 text-gray-500">
                            Remplissez le formulaire et nous vous répondrons
                            directement par e-mail.
                        </p>

                        <form class="mt-8 space-y-6" @submit.prevent="submit">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <!-- Prénom -->
                                <div class="space-y-2">
                                    <Label
                                        for="first_name"
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        Prénom
                                    </Label>

                                    <Input
                                        id="first_name"
                                        v-model="form.first_name"
                                        autocomplete="given-name"
                                        required
                                        :aria-invalid="
                                            form.invalid('first_name')
                                        "
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
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        Nom
                                    </Label>

                                    <Input
                                        id="last_name"
                                        v-model="form.last_name"
                                        autocomplete="family-name"
                                        required
                                        :aria-invalid="
                                            form.invalid('last_name')
                                        "
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
                                <Label
                                    for="email"
                                    class="text-sm font-medium text-gray-900"
                                >
                                    Adresse e-mail
                                </Label>

                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    autocomplete="email"
                                    required
                                    :aria-invalid="form.invalid('email')"
                                    :class="fieldClass('email')"
                                    @change="validate('email')"
                                />

                                <p class="text-xs text-gray-500">
                                    Cette adresse sera utilisée pour vous
                                    répondre.
                                </p>

                                <p
                                    v-if="form.invalid('email')"
                                    class="text-xs font-medium text-red-600"
                                >
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <!-- Sujet -->
                            <div class="space-y-2">
                                <Label
                                    for="subject"
                                    class="text-sm font-medium text-gray-900"
                                >
                                    Objet
                                </Label>

                                <Input
                                    id="subject"
                                    v-model="form.subject"
                                    maxlength="150"
                                    required
                                    placeholder="Ex. Question concernant un atelier"
                                    :aria-invalid="form.invalid('subject')"
                                    :class="fieldClass('subject')"
                                    @change="validate('subject')"
                                />

                                <p
                                    v-if="form.invalid('subject')"
                                    class="text-xs font-medium text-red-600"
                                >
                                    {{ form.errors.subject }}
                                </p>
                            </div>

                            <!-- Message -->
                            <div class="space-y-2">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <Label
                                        for="message"
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        Message
                                    </Label>

                                    <span class="text-xs text-gray-400">
                                        {{ form.message.length }}/5000
                                    </span>
                                </div>

                                <textarea
                                    id="message"
                                    v-model="form.message"
                                    rows="8"
                                    maxlength="5000"
                                    required
                                    placeholder="Expliquez-nous votre demande..."
                                    :aria-invalid="form.invalid('message')"
                                    :class="[
                                        'w-full resize-y rounded-md border border-gray-200 bg-white px-3 py-3 text-sm leading-6 text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:ring-2 focus:ring-gray-100',
                                        form.invalid('message')
                                            ? 'border-red-400 focus:border-red-400 focus:ring-red-100'
                                            : '',
                                    ]"
                                    @change="validate('message')"
                                />

                                <p
                                    v-if="form.invalid('message')"
                                    class="text-xs font-medium text-red-600"
                                >
                                    {{ form.errors.message }}
                                </p>
                            </div>

                            <Button
                                type="submit"
                                class="group inline-flex h-10 px-4 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium shadow"
                                :disabled="form.processing"
                            >
                                <Loader2
                                    v-if="form.processing"
                                    class="w-4 h-4 mr-2 animate-spin"
                                />

                                <Send v-else class="w-4 h-4 mr-2" />

                                {{
                                    form.processing
                                        ? "Envoi du message..."
                                        : "Envoyer mon message"
                                }}
                            </Button>
                        </form>
                    </div>
                </div>

                <!-- ========================================= -->
                <!-- CARTE                                     -->
                <!-- ========================================= -->
                <div class="mt-16 border-t-2 border-gray-100 pt-10">
                    <h2
                        class="text-3xl leading-9 text-gray-900 sm:text-4xl sm:leading-10"
                    >
                        Nous trouver
                    </h2>

                    <div class="mt-8">
                        <iframe
                            class="h-[360px] w-full rounded-lg shadow-md sm:h-[450px]"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d360.02345019152585!2d4.612666293832646!3d50.715141868412516!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47c17f1858cffa29%3A0xae791f50b83bef23!2sRacines%20Tactiles%20-%20C%C3%A9ramique!5e1!3m2!1sfr!2sbe!4v1730051541127!5m2!1sfr!2sbe"
                            style="border: 0"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Localisation de Racines Tactiles"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <Footer />
</template>
