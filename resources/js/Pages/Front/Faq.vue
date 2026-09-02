<script setup>
import { Head, Link } from "@inertiajs/vue3";

import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";

import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/Components/ui/accordion";

import { Button } from "@/Components/ui/button";

import {
    CircleHelp,
    Mail,
    ArrowRight,
} from "lucide-vue-next";

defineProps({
    faqs: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="FAQ" />

    <Nav />

    <main class="min-h-screen bg-white font-brand">
        <!-- ========================================================= -->
        <!-- HERO                                                     -->
        <!-- ========================================================= -->

        <section
            class="border-b border-gray-100 bg-gray-50/50"
        >
            <div
                class="max-w-4xl mx-auto px-6 lg:px-0 py-16 sm:py-20 text-center"
            >
                <div
                    class="h-12 w-12 mx-auto rounded-full bg-gray-900 text-white flex items-center justify-center"
                >
                    <CircleHelp class="h-6 w-6" />
                </div>

                <h1
                    class="text-4xl sm:text-5xl font-medium tracking-tight text-gray-900 mt-5"
                >
                    Questions fréquentes
                </h1>

                <p
                    class="text-base sm:text-lg text-gray-500 max-w-2xl mx-auto mt-4 leading-relaxed"
                >
                    Retrouvez les réponses aux questions les plus
                    fréquentes concernant les cours, les réservations
                    et le fonctionnement de Racines-Tactiles.
                </p>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- FAQ                                                      -->
        <!-- ========================================================= -->

        <section
            class="max-w-4xl mx-auto px-6 lg:px-0 py-12 sm:py-16"
        >
            <Accordion
                v-if="faqs.length > 0"
                type="single"
                collapsible
                class="space-y-3"
            >
                <AccordionItem
                    v-for="faq in faqs"
                    :key="faq.id"
                    :value="String(faq.id)"
                    class="border border-gray-200 rounded-xl px-5 sm:px-6 bg-white shadow-xs overflow-hidden data-[state=open]:border-gray-300 data-[state=open]:shadow transition"
                >
                    <AccordionTrigger
                        class="text-left hover:no-underline py-5 text-sm sm:text-base font-semibold text-gray-900"
                    >
                        <div
                            class="flex items-start gap-3 pr-3"
                        >
                            <div
                                class="h-7 w-7 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center shrink-0 mt-[-3px]"
                            >
                                <CircleHelp
                                    class="h-3.5 w-3.5"
                                />
                            </div>

                            <span>
                                {{ faq.question }}
                            </span>
                        </div>
                    </AccordionTrigger>

                    <AccordionContent
                        class="pb-5 pl-10 pr-2"
                    >
                        <div
                            class="text-sm sm:text-base text-gray-600 leading-7 whitespace-pre-line"
                        >
                            {{ faq.answer }}
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>

            <!-- Aucune FAQ -->

            <div
                v-else
                class="py-16 text-center border border-dashed border-gray-200 rounded-2xl bg-gray-50/50"
            >
                <CircleHelp
                    class="h-8 w-8 text-gray-300 mx-auto"
                />

                <h2
                    class="font-semibold text-gray-900 mt-3"
                >
                    La FAQ arrive bientôt
                </h2>

                <p
                    class="text-sm text-gray-500 mt-1"
                >
                    Nous préparons actuellement les réponses aux
                    questions les plus fréquentes.
                </p>
            </div>

            <!-- ===================================================== -->
            <!-- CONTACT                                               -->
            <!-- ===================================================== -->

            <div
                class="mt-12 rounded-2xl bg-gray-900 text-white p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-5"
            >
                <div>
                    <div
                        class="flex items-center gap-2"
                    >
                        <Mail
                            class="h-4 w-4 text-gray-300"
                        />

                        <h2
                            class="font-semibold text-lg"
                        >
                            Vous n'avez pas trouvé votre réponse ?
                        </h2>
                    </div>

                    <p
                        class="text-sm text-gray-300 mt-2 max-w-xl"
                    >
                        Contactez-nous et nous vous répondrons
                        directement.
                    </p>
                </div>

                <Button
                    as-child
                    size="sm"
                    class="group bg-white text-gray-900 hover:bg-gray-100 shrink-0"
                >
                    <Link :href="route('contact.index')">
                        Nous contacter

                        <ArrowRight
                            class="h-4 w-4 ml-1.5 transition-transform group-hover:translate-x-1"
                        />
                    </Link>
                </Button>
            </div>
        </section>
    </main>

    <Footer />
</template>
