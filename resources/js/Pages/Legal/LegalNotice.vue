<script setup>
import { computed } from "vue";

import LegalPageLayout from "@/Layouts/LegalPageLayout.vue";

import {
    AtSign,
    Globe2,
    Mail,
    MapPin,
    Phone,
    CodeXml,
    Copyright,
    UserCheck
} from "lucide-vue-next";

const email = computed(() =>
    ["info", "racines-tactiles.be"].join("@")
);

const emailHref = computed(() => `mailto:${email.value}`);
</script>

<template>
    <LegalPageLayout
        title="Mentions légales"
        description="Informations relatives à l'éditeur, à l'exploitation et à l'hébergement du site Racines-Tactiles."
    >


        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900">
                Éditeur du site
            </h2>

            <p>
                Le présent site est édité et exploité par :
            </p>

            <div
                class="p-5 bg-gray-50 border border-gray-200 rounded-xl space-y-2"
            >
                <p class="font-bold text-gray-900">
                    Racines Tactiles SRL
                </p>

                <p>
                    Siège social : Route Provinciale 113/A,
                    1450 Chastre, Belgique
                </p>

                <p>
                    Numéro d'entreprise BCE :
                    <strong>0538.838.463</strong>
                </p>

                <p>
                    Numéro de TVA :
                    <strong>BE 0538.838.463</strong>
                </p>
            </div>
        </section>

        <section class="space-y-3">
            <h2
                class="text-xl font-bold text-gray-900 flex items-center gap-2"
            >
                <MapPin class="w-5 h-5" />
                Atelier
            </h2>

            <p>
                Les activités de Racines-Tactiles sont notamment
                proposées à l'atelier situé :
            </p>

            <p class="font-medium text-gray-800">
                Rue Florimond Letroye 13, Wavre, Belgique
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <AtSign class="w-5 h-5" />
                Contact
            </h2>

            <div class="space-y-2">
                <a
                    :href="emailHref"
                    class="flex items-center gap-2 text-gray-800 hover:text-blue-600"
                >
                    <Mail class="w-4 h-4" />
                    {{ email }}
                </a>

                <a
                    href="tel:+32487328826"
                    class="flex items-center gap-2 text-gray-800 hover:text-blue-600"
                >
                    <Phone class="w-4 h-4" />
                    0487 32 88 26
                </a>
            </div>
        </section>

        <section class="space-y-3">
            <h2
                class="text-xl font-bold text-gray-900 flex items-center gap-2"
            >
                <Globe2 class="w-5 h-5" />
                Hébergement
            </h2>

            <p>
                Le site est hébergé par o2switch, société française
                disposant de ses infrastructures et centres de données
                à Clermont-Ferrand, en France.
            </p>

            <p>
                o2switch — Chemin des Pardiaux,
                63000 Clermont-Ferrand, France.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <CodeXml class="w-5 h-5" />
                Conception et développement
            </h2>

            <p>
                Conception et développement du site :
                <strong class="text-gray-800">
                    Anas Rachafi El Hausi
                </strong>.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <Copyright class="w-5 h-5" />
                Propriété intellectuelle
            </h2>

            <p>
                Les textes, photographies, illustrations, éléments
                graphiques, logos et autres contenus présents sur le
                site sont protégés par les droits de propriété
                intellectuelle applicables.
            </p>

            <p>
                Toute reproduction, diffusion ou exploitation non
                autorisée de ces contenus est interdite, sauf accord
                préalable de Racines Tactiles SRL ou du titulaire des
                droits concernés.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <UserCheck class="w-5 h-5" />
                Responsabilité
            </h2>

            <p>
                Racines Tactiles SRL veille à fournir des informations
                aussi exactes et à jour que possible. Des erreurs,
                omissions ou interruptions temporaires du site peuvent
                toutefois survenir.
            </p>

            <p>
                Les informations publiées sur le site ne peuvent être
                considérées comme une garantie de disponibilité d'un
                cours ou d'une séance tant que la réservation
                correspondante n'a pas été validée.
            </p>
        </section>
    </LegalPageLayout>
</template>
