<script setup>
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import { Checkbox } from "@/Components/ui/checkbox";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";
import { Loader2 } from "lucide-vue-next";

const props = defineProps({
    open: Boolean,
    image: { type: Object, default: null },
});
const emit = defineEmits(["update:open"]);
const form = useForm({ confirmed: false });
watch(
    () => props.open,
    (open) => {
        if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);
function setOpen(open) {
    if (!form.processing) emit("update:open", open);
}
function confirm(value) {
    form.confirmed = value === true;
    form.clearErrors("confirmed");
}
function destroy() {
    if (!props.image || !form.confirmed || form.processing) return;
    form.delete(route("gallery-images.destroy", props.image.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit("update:open", false);
            toast.success("Image supprimée de la galerie");
        },
        onError: (errors) =>
            toast.error("Suppression impossible", {
                description: Object.values(errors)[0] || "Veuillez réessayer.",
            }),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="setOpen">
        <DialogContent
            class="max-h-[90dvh] overflow-y-auto sm:max-w-lg"
            @escape-key-down="form.processing && $event.preventDefault()"
            @interact-outside="form.processing && $event.preventDefault()"
        >
            <form class="space-y-5" @submit.prevent="destroy">
                <DialogHeader>
                    <DialogTitle>Supprimer cette image ?</DialogTitle>
                    <DialogDescription
                        >L’image sera retirée définitivement de la galerie
                        publique.</DialogDescription
                    >
                </DialogHeader>
                <figure
                    v-if="image"
                    class="min-w-0 rounded-lg border bg-muted/20 p-3"
                >
                    <img
                        :src="image.src"
                        :alt="image.alt"
                        class="mx-auto max-h-40 w-full rounded-md object-contain"
                    />
                    <figcaption
                        class="mt-2 text-sm text-muted-foreground [overflow-wrap:anywhere]"
                    >
                        {{ image.description }}
                    </figcaption>
                </figure>
                <div class="flex items-start gap-3">
                    <Checkbox
                        id="gallery-delete-confirm"
                        :checked="form.confirmed"
                        :model-value="form.confirmed"
                        :disabled="form.processing"
                        @update:checked="confirm"
                        @update:model-value="confirm"
                    />
                    <Label
                        for="gallery-delete-confirm"
                        class="cursor-pointer text-sm leading-relaxed"
                        >Je confirme la suppression définitive de cette
                        image.</Label
                    >
                </div>
                <p
                    v-if="form.errors.confirmed"
                    role="alert"
                    class="text-xs text-destructive"
                >
                    {{ form.errors.confirmed }}
                </p>
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
                        variant="destructive"
                        class="gap-2"
                        :disabled="!form.confirmed || form.processing"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        {{
                            form.processing
                                ? "Suppression…"
                                : "Supprimer l’image"
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
