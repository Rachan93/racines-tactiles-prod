<script setup>
import LegalPageLayout from "@/Layouts/LegalPageLayout.vue";

import {
    Cookie,
    LockKeyhole,
    ShieldCheck,
    ChartNoAxesCombined,
    Globe


} from "lucide-vue-next";
</script>

<template>
    <LegalPageLayout
        title="Politique des cookies"
        description="Cette page explique les technologies utilisées par le site afin d'assurer son bon fonctionnement et la sécurité de votre navigation."
    >

        <!-- 1 -->
        <section class="space-y-3">
            <h2
                class="text-xl font-bold text-gray-900 flex items-center gap-2"
            >
                <ShieldCheck class="w-5 h-5" />
                Cookies strictement nécessaires
            </h2>

            <p>
                Le site utilise uniquement les cookies et technologies
                nécessaires à son fonctionnement.
            </p>

            <p>
                Ils permettent notamment de maintenir votre session,
                de sécuriser les formulaires et de permettre
                l'utilisation de votre espace membre.
            </p>

            <p>
                Ces éléments sont indispensables au fonctionnement du
                service et ne sont pas utilisés à des fins
                publicitaires.
            </p>
        </section>

        <!-- 2 -->
        <section class="space-y-3">
            <h2
                class="text-xl font-bold text-gray-900 flex items-center gap-2"
            >
                <LockKeyhole class="w-5 h-5" />
                Authentification et sécurité
            </h2>

            <p>
                Lorsque vous vous connectez à votre compte, le site
                utilise des cookies techniques permettant notamment
                de conserver votre session et de protéger les
                requêtes contre les tentatives de falsification.
            </p>

            <p>
                Ces mécanismes sont nécessaires à la sécurité de
                l'authentification et au fonctionnement de votre
                espace membre.
            </p>
        </section>

        <!-- 3 -->
        <section class="space-y-3">

             <h2
                class="text-xl font-bold text-gray-900 flex items-center gap-2"
            >
                <ChartNoAxesCombined class="w-5 h-5" />
                Mesure d'audiance et publicité
            </h2>


            <p>
                Le site n'utilise actuellement aucun cookie de mesure
                d'audience, de profilage ou de publicité nécessitant
                votre consentement.
            </p>

            <p>
                Aucun outil publicitaire ou de suivi tel que Google
                Analytics ou Meta Pixel n'est actuellement activé.
            </p>

            <p>
                Si de tels outils devaient être ajoutés à l'avenir,
                cette politique serait mise à jour et votre
                consentement serait demandé avant leur activation
                lorsque celui-ci est requis.
            </p>
        </section>

        <!-- 4 -->
        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <Cookie class="w-5 h-5" />
                Bannière d'information
            </h2>

            <p>
                Lors de votre première visite, une bannière vous informe
                de l'utilisation des technologies nécessaires au
                fonctionnement du site.
            </p>

            <p>
                Lorsque vous fermez cette bannière, cette préférence est
                enregistrée localement dans votre navigateur afin de
                ne pas l'afficher à chaque visite.
            </p>

            <p>
                Cette préférence utilise le stockage local de votre
                navigateur et n'est pas utilisée à des fins de suivi
                ou de profilage.
            </p>
        </section>

        <!-- 5 -->
        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <Globe class="w-5 h-5" />
                Gestion des données du navigateur
            </h2>

            <p>
                Vous pouvez supprimer à tout moment les cookies et
                données locales enregistrés par le site depuis les
                paramètres de votre navigateur.
            </p>

            <p>
                Leur suppression peut toutefois avoir certaines
                conséquences fonctionnelles, notamment vous
                déconnecter de votre espace membre ou réinitialiser
                certaines préférences enregistrées localement.
            </p>
        </section>

        <!-- 6 -->
        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-900">
                Évolution de cette politique
            </h2>

            <p>
                Cette politique pourra être mise à jour si les
                technologies utilisées par le site évoluent,
                notamment en cas d'ajout futur d'outils statistiques,
                publicitaires ou de services tiers utilisant des
                cookies.
            </p>
        </section>
    </LegalPageLayout>
</template>
