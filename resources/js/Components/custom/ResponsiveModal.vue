<script setup>
import { useMediaQuery } from "@vueuse/core";

import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";

import {
    Drawer,
    DrawerContent,
    DrawerHeader,
    DrawerTitle,
    DrawerDescription,
    DrawerFooter,
} from "@/Components/ui/drawer";

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },

    title: {
        type: String,
        default: "",
    },

    description: {
        type: String,
        default: "",
    },

    maxWidth: {
        type: String,
        default: "sm:max-w-lg",
    },
});

const emit = defineEmits(["update:open"]);

const isDesktop = useMediaQuery("(min-width: 640px)");
</script>

<template>
    <!-- ====================================================== -->
    <!-- DESKTOP : VRAI DIALOG                                  -->
    <!-- ====================================================== -->
    <Dialog
        v-if="isDesktop"
        :open="open"
        @update:open="(value) => emit('update:open', value)"
    >
        <DialogContent
            :class="[
                'font-brand bg-white border-gray-200 p-0 flex flex-col overflow-hidden',
                'max-h-[85vh] rounded-2xl shadow-xl',
                maxWidth,
            ]"
        >
            <DialogHeader
                v-if="
                    title || description || $slots.title || $slots.description
                "
                class="px-6 pt-5 pb-4 border-b border-gray-100 shrink-0 text-left space-y-1"
            >
                <DialogTitle
                    v-if="title || $slots.title"
                    class="text-xl font-bold text-gray-900 pr-6"
                >
                    <slot name="title">
                        {{ title }}
                    </slot>
                </DialogTitle>

                <DialogDescription
                    v-if="description || $slots.description"
                    class="text-sm text-gray-500"
                >
                    <slot name="description">
                        {{ description }}
                    </slot>
                </DialogDescription>
            </DialogHeader>

            <div
                class="flex-1 min-h-0 overflow-y-auto px-6 py-4 text-sm text-gray-700 space-y-4"
            >
                <slot />
            </div>

            <DialogFooter
                v-if="$slots.footer"
                class="p-4 border-t border-gray-100 shrink-0 bg-gray-50/70 flex-row gap-2"
            >
                <slot name="footer" />
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- ====================================================== -->
    <!-- MOBILE : VRAI DRAWER / BOTTOM SHEET                    -->
    <!-- ====================================================== -->
    <Drawer
        v-else
        :open="open"
        @update:open="(value) => emit('update:open', value)"
    >
        <DrawerContent class="font-brand bg-white max-h-[90dvh] flex flex-col">
            <DrawerHeader
                v-if="
                    title || description || $slots.title || $slots.description
                "
                class="px-6 pt-3 pb-4 border-b border-gray-100 shrink-0 text-left"
            >
                <DrawerTitle
                    v-if="title || $slots.title"
                    class="text-lg font-bold text-gray-900 pr-4"
                >
                    <slot name="title">
                        {{ title }}
                    </slot>
                </DrawerTitle>

                <DrawerDescription
                    v-if="description || $slots.description"
                    class="text-xs text-gray-500 mt-1"
                >
                    <slot name="description">
                        {{ description }}
                    </slot>
                </DrawerDescription>
            </DrawerHeader>

            <div
                class="flex-1 min-h-0 overflow-y-auto px-6 py-4 text-sm text-gray-700 space-y-4"
            >
                <slot />
            </div>

            <DrawerFooter
                v-if="$slots.footer"
                class="p-4 border-t border-gray-100 shrink-0 bg-gray-50/70 gap-2"
            >
                <slot name="footer" />
            </DrawerFooter>
        </DrawerContent>
    </Drawer>
</template>
