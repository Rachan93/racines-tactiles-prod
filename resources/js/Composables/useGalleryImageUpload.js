import { computed, onBeforeUnmount, ref } from "vue";

export function useGalleryImageUpload(form, existingUrl) {
    const input = ref(null);
    const localUrl = ref(null);
    const error = ref("");
    const dragDepth = ref(0);
    const previewUrl = computed(() => localUrl.value || existingUrl() || null);
    const dragging = computed(() => dragDepth.value > 0);

    function releaseUrl() {
        if (localUrl.value) URL.revokeObjectURL(localUrl.value);
        localUrl.value = null;
    }

    function reset() {
        releaseUrl();
        form.image = null;
        error.value = "";
        dragDepth.value = 0;
        form.clearErrors("image");
        if (input.value) input.value.value = "";
    }

    function selectFiles(files) {
        if (form.processing || !files?.length) return;
        error.value = "";
        form.clearErrors("image");
        if (files.length !== 1) {
            error.value = "Choisissez une seule image à la fois.";
            return;
        }
        const file = files[0];
        if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
            error.value = "Formats acceptés : JPG, PNG et WebP.";
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            error.value = "L’image ne doit pas dépasser 5 Mo.";
            return;
        }
        releaseUrl();
        form.image = file;
        localUrl.value = URL.createObjectURL(file);
    }

    function onChange(event) {
        selectFiles(event.target.files);
        event.target.value = "";
    }

    function onDrop(event) {
        dragDepth.value = 0;
        selectFiles(event.dataTransfer?.files);
    }

    onBeforeUnmount(releaseUrl);
    return {
        input,
        error,
        dragging,
        dragDepth,
        previewUrl,
        reset,
        onChange,
        onDrop,
    };
}
