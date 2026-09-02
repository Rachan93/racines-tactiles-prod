<script setup>
import { Head, Link } from "@inertiajs/vue3";

import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";

import { ArrowLeft } from "lucide-vue-next";

defineProps({
    title: {
        type: String,
        required: true,
    },

    description: {
        type: String,
        default: "",
    },
});
</script>

<template>
    <Head :title="title" />

    <Nav />

    <main class="min-h-screen bg-white font-brand py-10 sm:py-16">
        <div class="max-w-3xl mx-auto px-5 sm:px-6">
            <Link
                :href="route('home.index')"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 mb-8"
            >
                <ArrowLeft class="w-4 h-4" />
                Retour au site
            </Link>

            <header class="mb-10">
                <slot name="icon" />

                <h1 class="text-4xl sm:text-5xl font-bold text-gray-900">
                    {{ title }}
                </h1>

                <p
                    v-if="description"
                    class="mt-4 text-base text-gray-500 leading-relaxed"
                >
                    {{ description }}
                </p>
            </header>

            <article
                class="space-y-10 text-gray-600 leading-relaxed"
            >
                <slot />
            </article>

            <div
                class="mt-12 pt-6 border-t border-gray-200 text-sm text-gray-400"
            >
                Dernière mise à jour : septembre 2026.
            </div>
        </div>
    </main>

    <Footer />
</template>
