<script setup>
import { watch } from "vue";
import { useForm } from "laravel-precognition-vue-inertia";
import { toast } from "vue-sonner";
import { useGalleryImageUpload } from "@/Composables/useGalleryImageUpload";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";
import { Upload, Loader2 } from "lucide-vue-next";

const props = defineProps({
    open: Boolean,
    image: { type: Object, default: null },
});
const emit = defineEmits(["update:open"]);
const form = useForm(
    "post",
    () =>
        props.image
            ? route("gallery-images.update", props.image.id)
            : route("gallery-images.store"),
    {
        _method: "post",
        image: null,
        description: "",
    },
);
const {
    input,
    error,
    dragging,
    dragDepth,
    previewUrl,
    reset,
    onChange,
    onDrop,
} = useGalleryImageUpload(form, () => props.image?.src);

watch(
    () => props.open,
    (open) => {
        if (open) {
            reset();
            form.clearErrors();
            form._method = props.image ? "patch" : "post";
            form.description = props.image?.description || "";
        } else {
            reset();
        }
    },
    { immediate: true },
);

function setOpen(open) {
    if (!form.processing) emit("update:open", open);
}
function submit() {
    if (form.processing || error.value) return;
    const editing = Boolean(props.image);
    form.submit({
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            emit("update:open", false);
            toast.success(
                editing ? "Image modifiée" : "Image ajoutée à la galerie",
            );
        },
        onError: () =>
            toast.error("Enregistrement impossible", {
                description: "Vérifiez les champs du formulaire.",
            }),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="setOpen">
        <DialogContent
            class="max-h-[90dvh] min-w-0 overflow-y-auto sm:max-w-xl"
            @escape-key-down="form.processing && $event.preventDefault()"
            @interact-outside="form.processing && $event.preventDefault()"
        >
            <form class="min-w-0 space-y-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{
                        image ? "Modifier l’image" : "Ajouter une image"
                    }}</DialogTitle>
                    <DialogDescription>{{
                        image
                            ? "Conservez l’image actuelle ou choisissez un nouveau fichier."
                            : "L’image sera visible dans la galerie publique après enregistrement."
                    }}</DialogDescription>
                </DialogHeader>
                <div class="space-y-2">
                    <Label for="gallery-image-file">Image</Label>
                    <input
                        id="gallery-image-file"
                        ref="input"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="sr-only"
                        :disabled="form.processing"
                        aria-describedby="gallery-image-help gallery-image-error"
                        :aria-invalid="Boolean(error || form.errors.image)"
                        @change="onChange"
                    />
                    <div
                        class="rounded-lg border-2 border-dashed p-3 transition-colors focus-within:ring-2 focus-within:ring-ring"
                        :class="
                            dragging
                                ? 'border-primary bg-primary/5'
                                : 'border-border bg-muted/20'
                        "
                        @dragenter.prevent="!form.processing && dragDepth++"
                        @dragleave.prevent="
                            dragDepth = Math.max(0, dragDepth - 1)
                        "
                        @dragover.prevent
                        @drop.prevent="onDrop"
                    >
                        <button
                            type="button"
                            class="block w-full cursor-pointer rounded-md text-center disabled:cursor-wait"
                            :disabled="form.processing"
                            aria-label="Choisir une image"
                            @click="input?.click()"
                        >
                            <img
                                v-if="previewUrl"
                                :src="previewUrl"
                                :alt="
                                    form.description ||
                                    'Aperçu du fichier choisi'
                                "
                                class="mx-auto max-h-56 w-full rounded-md object-contain"
                            />
                            <Upload
                                v-else
                                class="mx-auto my-5 h-9 w-9 text-muted-foreground"
                            />
                            <span class="mt-3 block text-sm font-medium">{{
                                previewUrl
                                    ? "Déposez une image pour la remplacer, ou cliquez"
                                    : "Déposez une image ici, ou cliquez pour choisir"
                            }}</span>
                        </button>
                    </div>
                    <p
                        id="gallery-image-help"
                        class="text-xs text-muted-foreground"
                    >
                        JPG, PNG ou WebP · 5 Mo maximum · 6 000 × 6 000 pixels
                        maximum.
                    </p>
                    <Button
                        v-if="form.image"
                        type="button"
                        variant="ghost"
                        size="sm"
                        :disabled="form.processing"
                        @click="reset"
                    >
                        {{
                            image
                                ? "Conserver l’image actuelle"
                                : "Retirer le fichier choisi"
                        }}
                    </Button>
                    <p
                        v-if="error || form.errors.image"
                        id="gallery-image-error"
                        role="alert"
                        class="text-xs text-destructive"
                    >
                        {{ error || form.errors.image }}
                    </p>
                </div>
                <div class="space-y-2">
                    <Label for="gallery-image-description">Description</Label>
                    <textarea
                        id="gallery-image-description"
                        v-model="form.description"
                        rows="3"
                        maxlength="500"
                        required
                        class="flex w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50"
                        :disabled="form.processing"
                        :aria-invalid="Boolean(form.errors.description)"
                        aria-describedby="gallery-description-help gallery-description-error"
                        placeholder="Ex. : Vase en grès émaillé bleu, à col étroit."
                        @change="form.validate('description')"
                    />
                    <p
                        id="gallery-description-help"
                        class="text-xs text-muted-foreground"
                    >
                        Affichée sous l’image et utilisée comme texte alternatif
                        pour l’accessibilité.
                    </p>
                    <p
                        v-if="form.errors.description"
                        id="gallery-description-error"
                        role="alert"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.description }}
                    </p>
                </div>
                <div
                    v-if="form.processing && form.progress"
                    class="space-y-1"
                    aria-live="polite"
                >
                    <progress
                        class="h-2 w-full"
                        :value="form.progress.percentage"
                        max="100"
                        aria-label="Progression du transfert"
                    />
                    <p class="text-xs text-muted-foreground">
                        Transfert : {{ form.progress.percentage }} %
                    </p>
                </div>
                <DialogFooter class="gap-2 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="setOpen(false)"
                        >Annuler</Button
                    >
                    <Button
                        type="submit"
                        class="gap-2"
                        :disabled="form.processing || Boolean(error)"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        {{
                            form.processing ? "Enregistrement…" : "Enregistrer"
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
