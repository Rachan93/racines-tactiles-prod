<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";
import { Button } from "@/Components/ui/button";
import { ArrowRight } from "lucide-vue-next";

</script>

<template>
    <Head title="Accueil" />

    <div class="relative bg-white overflow-hidden font-brand">
        <div class="max-w-7xl mx-auto">
            <div
                class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32"
            >
                <Nav />
                <main
                    class="mt-10 mx-auto max-w-screen-xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:mx-20 xl:mt-28"
                >
                    <div class="text-justify">
                        <img
                            class="w-2/3 md:w-2/5 lg:w-2/3 mx-auto mb-24"
                            src="/images/assets/logo.png"
                            alt="Logo de l'atelier"
                        />
                        <p
                            class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0"
                        >
                            Un espace dédié à la découverte, à l'exploration de
                            la céramique à travers des techniques diverses et
                            variées.
                        </p>
                        <p
                            class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0"
                        >
                            Une porte ouverte sur un Univers de possibilités,
                            d'expérimentations et de réflexions qui pourront
                            s'étendre bien au delà de la pratique elle même.
                        </p>
                        <p
                            class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0"
                        >
                            Nombreuses sont les analogies qui nous permettent de
                            grandir au quotidien en ayant les mains dans la
                            terre.
                        </p>
                        <Link
                            :href="route('ateliers.index')"
                            class="mt-8 inline-flex"
                        >
                            <Button
                                class="group bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium h-10 px-4 shadow hover:scale-[1.01] transition duration-150"
                            >
                                Découvrir les ateliers
                                <ArrowRight
                                    class="w-4 h-4 ml-2 group-hover:translate-x-0.5 transition-transform"
                                />
                            </Button>
                        </Link>
                    </div>
                </main>
            </div>
        </div>

        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
            <img
                class="lg:[clip-path:polygon(58%_0%,100%_0%,100%_100%,40%_100%)] xl:[clip-path:polygon(18%_0%,100%_0%,100%_100%,6%_100%)] h-56 w-full object-cover sm:h-72 md:h-96 lg:w-full lg:h-full"
                src="/images/assets/hero.jpg"
                alt="Hero Image"
            />
        </div>
    </div>

    <!-- Section Qui suis-je -->
    <section
        id="about"
        class="bg-white px-4 py-20 font-brand sm:px-6 lg:px-8 lg:py-28"
    >
        <div class="mx-auto max-w-6xl">
            <div class="mb-12 max-w-3xl">
                <h1
                    class="mt-3 text-4xl leading-tight text-gray-900 sm:text-5xl"
                >
                    Qui suis-je ?
                </h1>
            </div>

            <div
                class="flex flex-col gap-12 text-justify lg:flex-row lg:gap-16"
            >
                <div class="lg:w-1/2">
                    <p class="mb-8 text-lg leading-8 text-gray-500">
                        Thomas est l'heureux fondateur de « Racines Tactiles »,
                        son atelier de céramique qu'il a ouvert en 2018 au beau
                        milieu du Brabant Wallon.
                    </p>
                    <p class="mb-8 text-lg leading-8 text-gray-500">
                        <span class="font-bold">Racines</span> car le travail de
                        la terre, qui est sous nos pieds, est une invitation au
                        centrage de nos émotions, à l'ancrage de notre position.
                        Les connotations sont multiples et peuvent directement
                        se rapporter au façonnage de l'argile et aux dimensions
                        méditatives, voire thérapeutiques, que cela amène.
                    </p>
                    <p class="text-lg leading-8 text-gray-500">
                        <span class="font-bold">Tactiles</span> car notre outil
                        principal, au-delà de nos mains, sont nos terminaisons
                        nerveuses qui nous amènent une certaine sensibilité qui
                        s'affûtera avec le temps. Cette acuité sensorielle est
                        impérative à la pratique de la céramique et constitue un
                        point essentiel au développement de son travail et de sa
                        pédagogie.
                    </p>
                </div>

                <div class="lg:w-1/2">
                    <p class="mb-8 text-lg leading-8 text-gray-500">
                        C'est à l'âge de douze ans qu'il découvre l'univers de
                        la céramique grâce à sa marraine qui l'initie au tour de
                        potier et aux différentes techniques de modelage.
                        Autodidacte, la majorité de son apprentissage s'est
                        faite en mettant la main à la pâte, complété par son
                        passage chez différents formateurs à travers la
                        Belgique.
                    </p>
                    <p class="text-lg leading-8 text-gray-500">
                        La créativité est pour lui une conviction, celle-ci
                        nourrit l'âme et favorise le développement personnel. La
                        curiosité représente le moteur de son univers qui prend
                        forme en ayant les mains dans la terre. Ses valeurs
                        s'orientent autour de l'écologie et du bien-être
                        collectif, du retour aux racines et d'un mode de vie
                        authentique.
                    </p>
                </div>
            </div>

            <div
                class="mt-16 flex flex-col items-start gap-6 border-t border-gray-200 pt-10 sm:flex-row sm:items-center"
            >
                <img
                    class="h-32 w-32 rounded-lg object-cover shadow-md"
                    src="/images/assets/about.jpg"
                    loading="lazy"
                    alt="Thomas Flamant dans son atelier"
                />
                <div>
                    <h2 class="text-lg font-medium text-gray-900">
                        Thomas Flamant
                    </h2>
                    <p class="mt-1 text-sm text-earth-header">
                        Professeur de poterie
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Component -->
    <Footer/>
</template>
