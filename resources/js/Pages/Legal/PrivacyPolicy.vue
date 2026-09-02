<script setup>
import { computed } from "vue";

import LegalPageLayout from "@/Layouts/LegalPageLayout.vue";


const privacyEmail = computed(() =>
    ["info", "racines-tactiles.be"].join("@")
);

const privacyEmailHref = computed(
    () => `mailto:${privacyEmail.value}`
);
</script>

<template>
    <LegalPageLayout
        title="Politique de confidentialité"
        description="Informations relatives à la collecte et au traitement de vos données personnelles par Racines Tactiles SRL."
    >
        <template #icon>
        </template>

        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900">
                1. Responsable du traitement
            </h2>

            <p>
                Le responsable du traitement des données personnelles
                collectées via le site est :
            </p>

            <div
                class="p-5 bg-gray-50 border border-gray-200 rounded-xl space-y-1"
            >
                <p class="font-bold text-gray-900">
                    Racines Tactiles SRL
                </p>

                <p>
                    Route Provinciale 113/A, 1450 Chastre,
                    Belgique
                </p>

                <p>
                    BCE / TVA : BE 0538.838.463
                </p>

                <a
                    :href="privacyEmailHref"
                    class="text-gray-900 font-medium underline underline-offset-2"
                >
                    {{ privacyEmail }}
                </a>
            </div>
        </section>

        <section class="space-y-3">
            <h2
                class="text-xl font-bold text-gray-900 flex items-center gap-2"
            >

                2. Données collectées
            </h2>

            <p>
                Selon votre utilisation du site, les catégories de
                données suivantes peuvent notamment être traitées :
            </p>

            <ul class="list-disc pl-5 space-y-2">
                <li>
                    identité : prénom, nom et date de naissance ;
                </li>

                <li>
                    coordonnées : adresse, e-mail, téléphone et code
                    postal ;
                </li>

                <li>
                    données de compte et d'authentification ;
                </li>

                <li>
                    réservations, modules, séances, présences,
                    absences et rattrapages ;
                </li>

                <li>
                    informations relatives aux participants rattachés
                    au compte ;
                </li>

                <li>
                    informations professionnelles communiquées à des
                    fins de facturation ;
                </li>

                <li>
                    messages envoyés via le formulaire de contact ;
                </li>

                <li>
                    données techniques nécessaires à la sécurité et
                    au fonctionnement du site, telles que certaines
                    informations de session et adresses IP.
                </li>
            </ul>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900">
                3. Finalités
            </h2>

            <p>
                Les données sont notamment utilisées afin de :
            </p>

            <ul class="list-disc pl-5 space-y-2">
                <li>créer et administrer votre compte ;</li>
                <li>gérer les réservations et inscriptions ;</li>
                <li>
                    gérer les absences et crédits de rattrapage ;
                </li>
                <li>
                    permettre la réservation pour un participant
                    rattaché à votre compte ;
                </li>
                <li>
                    communiquer avec vous au sujet de vos cours ;
                </li>
                <li>
                    répondre aux demandes envoyées via le formulaire
                    de contact ;
                </li>
                <li>
                    assurer la sécurité et le bon fonctionnement du
                    site ;
                </li>
                <li>
                    assurer la gestion administrative et comptable
                    lorsqu'elle est nécessaire.
                </li>
            </ul>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900">
                4. Bases juridiques
            </h2>

            <p>
                Selon le traitement concerné, les données sont
                traitées lorsque cela est nécessaire à l'exécution
                d'une réservation ou d'un service demandé, au respect
                d'une obligation légale, à la poursuite d'un intérêt
                légitime lié à la gestion et à la sécurité du site ou,
                lorsque cela est requis, sur la base du consentement.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900">
                5. Données relatives aux mineurs
            </h2>

            <p>
                Un mineur ne dispose pas d'un compte autonome destiné
                à gérer directement ses réservations.
            </p>

            <p>
                Ses informations peuvent être introduites par son
                parent, son représentant légal ou l'adulte
                responsable qui l'ajoute comme participant rattaché
                à son propre compte.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900">
                6. Destinataires et sous-traitants
            </h2>

            <p>
                Les données sont accessibles uniquement aux personnes
                qui en ont besoin pour gérer les activités de
                Racines-Tactiles ainsi qu'aux prestataires techniques
                nécessaires au fonctionnement du service.
            </p>

            <p>
                Le site et les données associées sont notamment
                hébergés chez <strong>o2switch</strong>, dont les
                centres de données sont situés en France.
            </p>

            <p>
                L'envoi des e-mails transactionnels utilise
                actuellement l'infrastructure de messagerie liée à
                l'hébergement du site.
            </p>

            <p>
                Aucun outil publicitaire ou de profilage tel que Meta
                Pixel ou Google Analytics n'est actuellement utilisé.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900">
                7. Durées de conservation
            </h2>

            <p>
                Les données de compte, réservations et informations
                opérationnelles sont destinées à être conservées
                pendant la durée nécessaire au suivi de la relation
                avec le client et, en principe, jusqu'à
                <strong>12 mois après la dernière activité</strong>.
            </p>

            <p>
                Au-delà, les données qui ne sont plus nécessaires
                peuvent être supprimées ou anonymisées.
            </p>

            <p>
                Les messages issus du formulaire de contact peuvent
                être conservés jusqu'à 12 mois après le dernier
                échange, sauf nécessité particulière de les conserver
                plus longtemps.
            </p>

            <p>
                Les documents soumis à une obligation légale de
                conservation, notamment certaines factures et pièces
                comptables, sont conservés pendant la durée imposée
                par la législation applicable.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900">
                8. Sécurité
            </h2>

            <p>
                Des mesures techniques et organisationnelles
                raisonnables sont mises en œuvre afin de protéger les
                données contre les accès non autorisés, pertes,
                altérations ou divulgations.
            </p>

            <p>
                Les mots de passe ne sont pas stockés en clair et les
                mécanismes d'authentification du site intègrent des
                mesures destinées à sécuriser les comptes
                utilisateurs.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900">
                9. Vos droits
            </h2>

            <p>
                Dans les conditions prévues par le RGPD, vous pouvez
                notamment demander l'accès à vos données, leur
                rectification, leur effacement, la limitation de
                certains traitements, vous opposer à certains
                traitements ou demander la portabilité de données
                lorsqu'elle est applicable.
            </p>

            <p>
                Vous pouvez exercer ces droits en écrivant à :
            </p>

            <a
                :href="privacyEmailHref"
                class="inline-flex font-semibold text-gray-900 underline underline-offset-2"
            >
                {{ privacyEmail }}
            </a>

            <p>
                Vous disposez également du droit d'introduire une
                réclamation auprès de l'Autorité de protection des
                données belge.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900">
                10. Évolution de la politique
            </h2>

            <p>
                Cette politique pourra être adaptée notamment si de
                nouveaux services sont ajoutés, tels qu'un système de
                paiement en ligne, une newsletter ou des outils de
                mesure d'audience.
            </p>
        </section>
    </LegalPageLayout>
</template>
