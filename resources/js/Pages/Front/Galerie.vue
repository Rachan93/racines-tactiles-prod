<script setup>
import { ref } from "vue";
import { Head } from "@inertiajs/vue3";
import Nav from "@/Components/custom/Nav.vue";
import Footer from "@/Components/custom/Footer.vue";
import ImageLightbox from "@/Components/custom/ImageLightbox.vue";

defineProps({ images: { type: Array, required: true } });
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);
function openImage(index) {
    lightboxIndex.value = index;
    lightboxOpen.value = true;
}
</script>

<template>
    <Head title="Galerie" />
    <Nav />
    <section id="galerie" class="py-12 font-brand">
        <div class="bg-white px-4 pb-20 pt-2 sm:px-6 lg:px-8 lg:pb-28 lg:pt-0">
            <div class="relative mx-auto max-w-lg lg:max-w-6xl">
                <h1
                    class="mb-0 text-5xl leading-tight text-gray-900 sm:text-6xl"
                >
                    Galerie
                </h1>
                <p class="mt-6 text-xl text-gray-500 sm:mt-8">
                    Les pièces visibles dans la galerie sont disponibles à la
                    vente dans notre atelier.
                </p>
                <div
                    v-if="images.length"
                    class="grid grid-cols-2 gap-6 pt-10 md:mt-4 lg:grid-cols-3 lg:gap-8"
                >
                    <figure
                        v-for="(image, index) in images"
                        :key="image.id"
                        class="min-w-0"
                    >
                        <button
                            type="button"
                            class="group block aspect-[10/7] w-full cursor-pointer overflow-hidden rounded-lg shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7faba7] focus-visible:ring-offset-2"
                            :aria-label="`Agrandir : ${image.description}`"
                            @click="openImage(index)"
                        >
                            <img
                                :src="image.src"
                                :alt="image.alt"
                                loading="lazy"
                                decoding="async"
                                class="h-full w-full object-cover transition-transform duration-300 motion-safe:group-hover:scale-105"
                            />
                        </button>
                        <figcaption
                            class="mt-2 whitespace-pre-line text-center text-sm text-gray-500 [overflow-wrap:anywhere]"
                        >
                            {{ image.description }}
                        </figcaption>
                    </figure>
                </div>
                <p v-else class="py-16 text-center text-gray-500">
                    Pas encore d’images dans la galerie.
                </p>
            </div>
        </div>
    </section>
    <ImageLightbox
        v-model:open="lightboxOpen"
        v-model:index="lightboxIndex"
        :images="images"
    />
    <Footer />
</template>
