<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { VueDraggable } from "vue-draggable-plus";
import { toast } from "vue-sonner";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ImageLightbox from "@/Components/custom/ImageLightbox.vue";
import GalleryImageFormModal from "@/Components/admin/gallery/GalleryImageFormModal.vue";
import GalleryImageDeleteModal from "@/Components/admin/gallery/GalleryImageDeleteModal.vue";
import { Button } from "@/Components/ui/button";
import {
    Breadcrumb,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbSeparator,
    BreadcrumbPage,
} from "@/Components/ui/breadcrumb";
import {
    Plus,
    Images,
    Eye,
    Pencil,
    Trash2,
    GripVertical,
    ArrowLeft,
    ArrowRight,
    Save,
    RotateCcw,
    Loader2,
    ExternalLink,
} from "lucide-vue-next";

const props = defineProps({ images: { type: Array, required: true } });
const items = ref([]);
const originalIds = ref([]);
const orderForm = useForm({ ids: [], original_ids: [] });
const reloading = ref(false);
const dragging = ref(false);
const orderConflict = ref(false);
const editorOpen = ref(false);
const deleteOpen = ref(false);
const selectedImage = ref(null);
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);
const dirty = computed(
    () =>
        items.value.some(
            (item, index) => item.id !== originalIds.value[index],
        ) || items.value.length !== originalIds.value.length,
);
const busy = computed(() => orderForm.processing || reloading.value);
const dragDisabled = computed(
    () =>
        busy.value ||
        editorOpen.value ||
        deleteOpen.value ||
        lightboxOpen.value ||
        orderConflict.value,
);

function sync() {
    items.value = props.images.map((image) => ({ ...image }));
    originalIds.value = props.images.map((image) => image.id);
}
watch(() => props.images, sync, { immediate: true });
function resetOrder() {
    if (busy.value || dragging.value) return;
    sync();
    orderForm.clearErrors();
    // Un conflit nécessite une nouvelle lecture du serveur.
}
function reloadImages() {
    if (busy.value) return;
    if (
        dirty.value &&
        !window.confirm(
            "Abandonner le classement non enregistré et recharger la galerie ?",
        )
    )
        return;
    reloading.value = true;
    router.reload({
        only: ["images"],
        onSuccess: () => {
            orderConflict.value = false;
            orderForm.clearErrors();
        },
        onFinish: () => {
            reloading.value = false;
        },
    });
}
function move(index, offset) {
    if (dragDisabled.value || dragging.value) return;
    const destination = index + offset;
    if (destination < 0 || destination >= items.value.length) return;
    const copy = [...items.value];
    const [item] = copy.splice(index, 1);
    copy.splice(destination, 0, item);
    items.value = copy;
}
function saveOrder() {
    if (!dirty.value || busy.value || dragging.value || orderConflict.value)
        return;
    orderForm.ids = items.value.map((image) => image.id);
    orderForm.original_ids = [...originalIds.value];
    orderForm.patch(route("gallery-images.reorder"), {
        preserveScroll: true,
        onSuccess: () => {
            orderConflict.value = false;
            toast.success("Ordre de la galerie enregistré");
        },
        onError: (errors) => {
            orderConflict.value = true;
            toast.error("Classement non enregistré", {
                description:
                    Object.values(errors)[0] ||
                    "Veuillez recharger la galerie.",
            });
        },
    });
}
function edit(image = null) {
    if (busy.value || dirty.value || dragging.value || orderConflict.value)
        return;
    selectedImage.value = image;
    editorOpen.value = true;
}
function remove(image) {
    if (busy.value || dirty.value || dragging.value || orderConflict.value)
        return;
    selectedImage.value = image;
    deleteOpen.value = true;
}
function preview(index) {
    if (dragging.value) return;
    lightboxIndex.value = index;
    lightboxOpen.value = true;
}
function beforeUnload(event) {
    if (!dirty.value) return;
    event.preventDefault();
    event.returnValue = "";
}
let removeNavigationGuard;
onMounted(() => {
    window.addEventListener("beforeunload", beforeUnload);
    removeNavigationGuard = router.on("before", (event) => {
        if (
            event.detail.visit.method !== "get" ||
            !dirty.value ||
            reloading.value
        )
            return;
        if (!window.confirm("Quitter la page sans enregistrer le classement ?"))
            event.preventDefault();
    });
});
onBeforeUnmount(() => {
    window.removeEventListener("beforeunload", beforeUnload);
    removeNavigationGuard?.();
});
</script>

<template>
    <AdminLayout title="Galerie">
        <div class="space-y-6">
            <Breadcrumb>
                <BreadcrumbList class="gap-1.5 text-xs">
                    <BreadcrumbItem
                        ><BreadcrumbLink as-child
                            ><Link :href="route('dashboard.index')"
                                >Tableau de bord</Link
                            ></BreadcrumbLink
                        ></BreadcrumbItem
                    >
                    <BreadcrumbSeparator />
                    <BreadcrumbItem
                        ><BreadcrumbPage class="font-semibold"
                            >Galerie</BreadcrumbPage
                        ></BreadcrumbItem
                    >
                </BreadcrumbList>
            </Breadcrumb>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="text-xl font-semibold tracking-tight">
                        Gestion de la galerie
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Ajoutez vos pièces et choisissez leur ordre d’affichage
                        sur le site.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button as-child variant="outline" class="gap-2"
                        ><a
                            :href="route('gallery.index')"
                            target="_blank"
                            rel="noopener noreferrer"
                            ><ExternalLink class="h-4 w-4" />Voir la galerie</a
                        ></Button
                    >
                    <Button
                        class="gap-2"
                        :disabled="busy || dirty || dragging || orderConflict"
                        @click="edit()"
                        ><Plus class="h-4 w-4" />Ajouter une image</Button
                    >
                </div>
            </div>
            <div
                class="flex flex-col gap-3 rounded-xl border bg-card p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="min-w-0">
                    <p class="text-sm font-medium">
                        {{ items.length }}
                        {{ items.length > 1 ? "images" : "image" }}
                    </p>
                    <p
                        id="gallery-sort-help"
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        Déplacez les cartes avec leur poignée ou utilisez les
                        flèches pour changer leur ordre.
                    </p>
                    <p
                        v-if="dirty"
                        class="mt-2 text-xs font-medium text-primary"
                        role="status"
                    >
                        Classement non enregistré. Enregistrez ou annulez avant
                        d’ajouter, modifier ou supprimer une image.
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <Button
                        v-if="dirty"
                        variant="outline"
                        size="sm"
                        :disabled="busy || dragging"
                        class="gap-2"
                        @click="resetOrder"
                        ><RotateCcw class="h-4 w-4" />Annuler</Button
                    >
                    <Button
                        v-if="dirty"
                        size="sm"
                        :disabled="busy || dragging || orderConflict"
                        class="gap-2"
                        @click="saveOrder"
                    >
                        <Loader2
                            v-if="orderForm.processing"
                            class="h-4 w-4 animate-spin"
                        /><Save v-else class="h-4 w-4" />Enregistrer l’ordre
                    </Button>
                </div>
            </div>
            <div
                v-if="orderConflict"
                role="alert"
                class="space-y-3 rounded-lg border border-destructive/20 bg-destructive/5 p-4"
            >
                <p class="text-sm text-destructive">
                    {{
                        Object.values(orderForm.errors)[0] ||
                        "Rechargez les images avant de poursuivre."
                    }}
                </p>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="busy"
                    @click="reloadImages"
                    >Recharger les images</Button
                >
            </div>
            <VueDraggable
                v-model="items"
                :animation="180"
                handle=".gallery-drag-handle"
                :disabled="dragDisabled"
                :delay="150"
                :delay-on-touch-only="true"
                :touch-start-threshold="5"
                ghost-class="opacity-30"
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
                aria-describedby="gallery-sort-help"
                @start="dragging = true"
                @end="dragging = false"
            >
                <article
                    v-for="(image, index) in items"
                    :key="image.id"
                    class="min-w-0 overflow-hidden rounded-xl border bg-card shadow-sm"
                >
                    <button
                        type="button"
                        class="group block aspect-[10/7] w-full overflow-hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring"
                        :aria-label="`Agrandir : ${image.description}`"
                        @click="preview(index)"
                    >
                        <img
                            :src="image.src"
                            :alt="image.alt"
                            loading="lazy"
                            decoding="async"
                            draggable="false"
                            class="h-full w-full object-cover transition-transform duration-300 motion-safe:group-hover:scale-105"
                        />
                    </button>
                    <div class="space-y-3 p-4">
                        <p
                            class="line-clamp-3 min-h-[3.75rem] whitespace-pre-line text-sm text-muted-foreground [overflow-wrap:anywhere]"
                        >
                            {{ image.description }}
                        </p>
                        <div
                            class="flex flex-wrap items-center justify-between gap-2 border-t pt-3"
                        >
                            <div class="flex items-center gap-1">
                                <span
                                    class="gallery-drag-handle flex h-8 w-6 items-center justify-center touch-none text-muted-foreground"
                                    :class="
                                        dragDisabled
                                            ? 'opacity-40'
                                            : 'cursor-grab active:cursor-grabbing'
                                    "
                                    title="Glisser pour déplacer"
                                    aria-hidden="true"
                                    ><GripVertical class="h-4 w-4"
                                /></span>
                                <span
                                    class="mr-1 text-xs tabular-nums text-muted-foreground"
                                    >{{ index + 1 }}</span
                                >
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8"
                                    :disabled="
                                        dragDisabled || dragging || index === 0
                                    "
                                    :aria-label="`Déplacer l’image ${index + 1} avant`"
                                    title="Déplacer avant"
                                    @click="move(index, -1)"
                                    ><ArrowLeft class="h-3.5 w-3.5"
                                /></Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8"
                                    :disabled="
                                        dragDisabled ||
                                        dragging ||
                                        index === items.length - 1
                                    "
                                    :aria-label="`Déplacer l’image ${index + 1} après`"
                                    title="Déplacer après"
                                    @click="move(index, 1)"
                                    ><ArrowRight class="h-3.5 w-3.5"
                                /></Button>
                            </div>
                            <div class="flex items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8"
                                    :aria-label="`Voir l’image ${index + 1}`"
                                    title="Agrandir"
                                    @click="preview(index)"
                                    ><Eye class="h-3.5 w-3.5"
                                /></Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8"
                                    :disabled="
                                        busy ||
                                        dirty ||
                                        dragging ||
                                        orderConflict
                                    "
                                    :aria-label="`Modifier l’image ${index + 1}`"
                                    title="Modifier"
                                    @click="edit(image)"
                                    ><Pencil class="h-3.5 w-3.5"
                                /></Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive focus-visible:bg-destructive/10"
                                    :disabled="
                                        busy ||
                                        dirty ||
                                        dragging ||
                                        orderConflict
                                    "
                                    :aria-label="`Supprimer l’image ${index + 1}`"
                                    title="Supprimer"
                                    @click="remove(image)"
                                    ><Trash2 class="h-3.5 w-3.5"
                                /></Button>
                            </div>
                        </div>
                    </div>
                </article>
            </VueDraggable>
            <div
                v-if="!items.length"
                class="rounded-xl border border-dashed py-16 text-center"
            >
                <Images class="mx-auto mb-3 h-9 w-9 text-muted-foreground/50" />
                <p class="font-medium">
                    La galerie attend ses premières pièces
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Ajoutez une image et sa description pour commencer.
                </p>
            </div>
        </div>
        <GalleryImageFormModal
            v-model:open="editorOpen"
            :image="selectedImage"
        />
        <GalleryImageDeleteModal
            v-model:open="deleteOpen"
            :image="selectedImage"
        />
        <ImageLightbox
            v-model:open="lightboxOpen"
            v-model:index="lightboxIndex"
            :images="items"
        />
    </AdminLayout>
</template>
