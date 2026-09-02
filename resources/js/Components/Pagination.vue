<!-- code repris de l'app de gestion d'évaluations, merci Aurélien ;) -->

<!-- tuto utilisé pour la pagination: https://codeplank.com/laravel-10-inertia-vue-3-crud-tutorial-example -->
<!-- dépôt git du tuto: https://github.com/Code-Plank/laravel-inertia-vue3/tree/main -->

<!-- code originel du component Pagination: https://github.com/Code-Plank/laravel-inertia-vue3/blob/main/resources/js/Components/Pagination.vue -->

<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    links: Array,
});
</script>
<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap -mb-1">
            <template v-for="(link, key) in links">
                <div
                    v-if="link.url === null"
                    :key="key"
                    class="mb-1 mr-1 px-4 py-3 text-gray-400 text-sm leading-4 border rounded"
                    v-html="link.label"
                />
                <Link
                    v-else
                    preserve-scroll
                    preserve-state
                    :key="`link-${key}`"
                    class="mb-1 mr-1 px-4 py-3 text-sm leading-4 hover:bg-blue-200 hover:border-blue-400 border focus:border-blue-500 rounded focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700"
                    :class="{
                        'bg-blue-300':
                            link.active /* , 'border-indigo-900': link.active */,
                    }"
                    :href="link.url"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
