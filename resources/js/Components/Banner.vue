<script setup>
import { ref, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const show = ref(true);
const style = ref('success');
const message = ref('');

watchEffect(async () => {
    style.value = page.props.jetstream.flash?.bannerStyle || 'success';
    message.value = page.props.jetstream.flash?.banner || '';
    show.value = true;
});
</script>

<template>
    <div>
        <div v-if="show && message"
            :class="{ 'bg-indigo-500': style == 'success', 'bg-red-700': style == 'danger', 'bg-[#414142]': style == 'hev' }">
            <div class="max-w-screen-xl mx-auto py-2 px-3 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between flex-wrap">
                    <div class="w-0 flex-1 flex items-center min-w-0">
                        <span class="flex" :class="{
                            'rounded-lg p-2 bg-indigo-600': style == 'success', ' rounded-lg p-2 bg-red-600': style == 'danger', 'bg-[#CE9804] rounded-full ': style == 'hev'
                        }">

                            <svg v-if="style == 'success'" class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>

                            <svg v-if="style == 'danger'" class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>

                            <svg v-if="style == 'hev'" class="h-10 w-10 text-white" id="Layer_1"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 364.707 364.707"
                                stroke-width="1.5" stroke="currentColor">
                                <path class="h-10 w-10" fill="#414142" stroke-linecap="round" stroke-linejoin="round" d="M223.864,272.729l-38.608-97.848l-56.603,89.184H93.166l79.052-127.654l-8.875-25.229h-30.781V81.12h52.691
	                            l60.521,153.899l26.608-8.668l8.867,29.813L223.864,272.729z" />

                            </svg>

                        </span>

                        <p class="ms-3 font-medium text-sm text-white truncate">
                            {{ message }}
                        </p>
                    </div>

                    <div class="shrink-0 sm:ms-3">
                        <button type="button" class="-me-1 flex p-2 rounded-md focus:outline-none sm:-me-2 transition"
                            :class="{ 'hover:bg-indigo-600 focus:bg-indigo-600': style == 'success', 'hover:bg-red-600 focus:bg-red-600': style == 'danger', 'hover:bg-[#CE9804] focus:bg-[#EEAF1F]': style == 'hev', }"
                            aria-label="Dismiss" @click.prevent="show = false">
                            <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
