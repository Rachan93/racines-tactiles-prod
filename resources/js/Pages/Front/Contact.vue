<script setup>
import { Head } from "@inertiajs/vue3";
import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";
import { Mail, Phone } from "lucide-vue-next";
</script>

<template>
    <Head title="Contact" />
    <Nav />

    <section id="contact" class="mb-16 font-brand">
        <div
            class="bg-white pt-16 pb-20 px-4 sm:px-6 lg:pt-12 lg:pb-28 lg:px-8"
        >
            <div class="relative max-w-lg mx-auto lg:max-w-6xl">
                <h1
                    class="text-5xl leading-9 text-gray-900 sm:text-6xl sm:leading-10 mb-12"
                >
                    Contact
                </h1>
                <div class="md:grid md:grid-cols-2 md:gap-8">
                    <div>
                        <h2
                            class="text-2xl leading-8 text-gray-900 sm:text-3xl sm:leading-9"
                        >
                            Informations
                        </h2>
                        <div class="mt-5 border-t-2 border-gray-100 pt-5">
                            <p class="text-lg leading-7 text-gray-500">
                                Besoin d'un renseignement ? Vous voulez vous
                                inscrire à un stage ? Contactez-nous!
                            </p>
                        </div>
                        <div class="mt-9">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <Phone class="h-6 w-6 text-earth" />
                                </div>
                                <div
                                    class="ml-3 text-base leading-6 text-gray-500"
                                >
                                    <p>
                                        <a
                                            class="underline hover:text-gray-700"
                                            href="tel:+32487328826"
                                            >+32 487/32.88.26</a
                                        >
                                    </p>
                                    <p class="mt-1">Thomas Flamant</p>
                                </div>
                            </div>
                            <div class="mt-6 flex">
                                <div class="flex-shrink-0">
                                    <Mail class="h-6 w-6 text-earth" />
                                </div>
                                <div
                                    class="ml-3 text-base leading-6 text-gray-500"
                                >
                                    <p>
                                        <a
                                            class="underline hover:text-gray-700"
                                            href="mailto:info@racines-tactiles.be"
                                            >info@racines-tactiles.be</a
                                        >
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-12 sm:mt-16 md:mt-0">
                        <h2
                            class="text-2xl leading-8 text-gray-900 sm:text-3xl sm:leading-9"
                        >
                            Adresse
                        </h2>
                        <div class="mt-5 border-t-2 border-gray-100 pt-5">
                            <p class="text-lg leading-7 text-gray-500">
                                rue Florimond Letroye 13
                                <br />
                                1300 Wavre
                            </p>
                        </div>
                        <div class="mt-9">
                            <div class="flex">
                                <iframe
                                    class="rounded-lg shadow-md"
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d360.02345019152585!2d4.612666293832646!3d50.715141868412516!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47c17f1858cffa29%3A0xae791f50b83bef23!2sRacines%20Tactiles%20-%20C%C3%A9ramique!5e1!3m2!1sfr!2sbe!4v1730051541127!5m2!1sfr!2sbe"
                                    width="600"
                                    height="450"
                                    style="border: 0"
                                    allowfullscreen=""
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                ></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <Footer />
</template>
