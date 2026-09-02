<script setup>
import { computed, watch, onMounted, onUnmounted } from "vue";
import { X, ChevronLeft, ChevronRight } from "lucide-vue-next";

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    images: {
        type: Array,
        required: true,
        // Accepte ['url1', 'url2'] ou [{ src: 'url1', alt: 'desc' }]
    },
    index: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(["update:open", "update:index", "close"]);

// Normalise les images sous le format standard { src, alt }
const normalizedImages = computed(() => {
    return props.images.map((item) => {
        if (typeof item === "string") {
            return { src: item, alt: "" };
        }
        return { src: item.src, alt: item.alt || "" };
    });
});

const currentImage = computed(() => {
    return normalizedImages.value[props.index] || null;
});

const hasMultipleImages = computed(() => normalizedImages.value.length > 1);

const close = () => {
    emit("update:open", false);
    emit("close");
};

const prev = () => {
    if (!hasMultipleImages.value) return;
    const newIndex =
        props.index === 0 ? normalizedImages.value.length - 1 : props.index - 1;
    emit("update:index", newIndex);
};

const next = () => {
    if (!hasMultipleImages.value) return;
    const newIndex =
        props.index === normalizedImages.value.length - 1 ? 0 : props.index + 1;
    emit("update:index", newIndex);
};

const handleKeyDown = (e) => {
    if (!props.open) return;

    if (e.key === "Escape") {
        close();
    } else if (e.key === "ArrowLeft") {
        prev();
    } else if (e.key === "ArrowRight") {
        next();
    }
};

// Gestion du scroll lock sur le body
watch(
    () => props.open,
    (isOpen) => {
        if (typeof document !== "undefined") {
            document.body.style.overflow = isOpen ? "hidden" : "";
        }
    }
);

onMounted(() => {
    window.addEventListener("keydown", handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeyDown);
    if (typeof document !== "undefined") {
        document.body.style.overflow = "";
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm select-none p-4 sm:p-8"
                role="dialog"
                aria-modal="true"
                @click.self="close"
            >
                <!-- Bouton Fermer -->
                <button
                    type="button"
                    class="absolute top-4 right-4 z-50 p-2 text-white/80 hover:text-white bg-black/40 hover:bg-black/70 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-white/50"
                    aria-label="Fermer la vue agrandie"
                    @click="close"
                >
                    <X class="w-6 h-6" />
                </button>

                <!-- Compteur (ex: 1 / 2) -->
                <div
                    v-if="hasMultipleImages"
                    class="absolute top-4 left-4 z-50 px-3 py-1 text-sm font-medium text-white/90 bg-black/40 rounded-full backdrop-blur-sm pointer-events-none"
                >
                    {{ index + 1 }} / {{ normalizedImages.length }}
                </div>

                <!-- Bouton Précédent -->
                <button
                    v-if="hasMultipleImages"
                    type="button"
                    class="absolute left-4 z-50 p-3 text-white/80 hover:text-white bg-black/40 hover:bg-black/70 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-white/50"
                    aria-label="Image précédente"
                    @click.stop="prev"
                >
                    <ChevronLeft class="w-7 h-7" />
                </button>

                <!-- Conteneur Image principale -->
                <div
                    class="relative max-w-5xl max-h-[85vh] w-full h-full flex items-center justify-center pointer-events-none"
                >
                    <Transition
                        mode="out-in"
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <img
                            v-if="currentImage"
                            :key="currentImage.src"
                            :src="currentImage.src"
                            :alt="currentImage.alt"
                            class="max-w-full max-h-[85vh] object-contain rounded-md shadow-2xl pointer-events-auto"
                            @click.stop
                        />
                    </Transition>
                </div>

                <!-- Bouton Suivant -->
                <button
                    v-if="hasMultipleImages"
                    type="button"
                    class="absolute right-4 z-50 p-3 text-white/80 hover:text-white bg-black/40 hover:bg-black/70 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-white/50"
                    aria-label="Image suivante"
                    @click.stop="next"
                >
                    <ChevronRight class="w-7 h-7" />
                </button>
            </div>
        </Transition>
    </Teleport>
</template>
