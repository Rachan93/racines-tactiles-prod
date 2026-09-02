<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm as usePrecognitionForm } from "laravel-precognition-vue-inertia";
import { useForm } from "@inertiajs/vue3";
import DialogModal from "@/Components/DialogModal.vue";
import { ref, computed, watch } from "vue";

const props = defineProps(["lesson"]);

function formatDate(dateString = null, format = "dd/mm/yyyy", offset = 0) {
    const date = dateString ? new Date(dateString) : new Date();
    date.setDate(date.getDate() + offset);
    const day = date.getDate().toString().padStart(2, "0");
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    const year = date.getFullYear().toString();

    if (format === "yyyy-mm-dd") {
        return `${year}-${month}-${day}`;
    }
    return `${day}/${month}/${year}`;
}

function formatTime(dateString) {
    const date = new Date(dateString);
    const hours = date.getHours().toString().padStart(2, "0");
    const minutes = date.getMinutes().toString().padStart(2, "0");
    return `${hours}:${minutes}`;
}

const timeStart = computed(() => formatTime(props.lesson.date_start));
const timeEnd = computed(() => formatTime(props.lesson.date_end));

const initialDuration = computed(() => {
    const startTime = new Date(`1970-01-01T${timeStart.value}:00`);
    const endTime = new Date(`1970-01-01T${timeEnd.value}:00`);
    const durationInMinutes = (endTime - startTime) / (1000 * 60);
    return durationInMinutes / 60;
});

const editForm = usePrecognitionForm(
    "patch",
    route("lessons.update", { lesson: props.lesson.id }),
    {
        date: formatDate(props.lesson.date_start, "yyyy-mm-dd"),
        time_start: timeStart.value,
        duration: initialDuration.value,
        time_end: timeEnd.value,
        spots_max: props.lesson.spots_max,
        price: props.lesson.price,
    }
);

editForm.setValidationTimeout(300);

const editing = ref(false);

const startEditing = () => {
    editing.value = true;
    editForm.date = formatDate(props.lesson.date_start, "yyyy-mm-dd");
    editForm.time_start = timeStart.value;
    editForm.duration = initialDuration.value;
    editForm.time_end = timeEnd.value;
    editForm.spots_max = props.lesson.spots_max;
    editForm.price = props.lesson.price;
};

const cancelEditing = () => {
    editing.value = false;
};

const submitEdit = () => {
    editForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            editForm.reset();
            editing.value = false;
        },
    });
};

const updateEndTime = () => {
    const startTime = new Date(`1970-01-01T${editForm.time_start}:00`);
    const durationInMinutes = editForm.duration * 60;
    startTime.setMinutes(startTime.getMinutes() + durationInMinutes);
    editForm.time_end = formatTime(startTime);
};

watch(
    () => editForm.time_start,
    () => {
        updateEndTime();
    }
);

watch(
    () => editForm.duration,
    () => {
        updateEndTime();
    }
);

const updateDuration = () => {
    const startTime = new Date(`1970-01-01T${editForm.time_start}:00`);
    const endTime = new Date(`1970-01-01T${editForm.time_end}:00`);
    let durationInMinutes = (endTime - startTime) / (1000 * 60);

    const durationHours = Math.floor(durationInMinutes / 60);
    const durationMinutes = (durationInMinutes % 60) / 60;
    editForm.duration = Math.max(
        1,
        Math.min(10, durationHours + durationMinutes)
    );
    updateEndTime();
};

const formattedDuration = computed(() => {
    const hours = Math.floor(editForm.duration);
    const minutes = Math.round((editForm.duration - hours) * 60);
    return `${hours}:${minutes.toString().padStart(2, "0")}`;
});

const confirmingLessonDeletion = ref(false);
const lessonIdToDelete = ref(null);
const formDeleteLesson = useForm("delete", {});
const isCheckboxChecked = ref(false);

const confirmLessonDeletion = (id) => {
    lessonIdToDelete.value = id;
    confirmingLessonDeletion.value = true;
    isCheckboxChecked.value = false;
};

var deleteLesson = () => {
    if (isCheckboxChecked.value) {
        formDeleteLesson.delete(
            route("lessons.delete", lessonIdToDelete.value),
            {
                preserveScroll: true,
                onSuccess: () => {
                    confirmingLessonDeletion.value = false;
                },
            }
        );
    }
};

var closeModal = () => {
    confirmingLessonDeletion.value = false;
};
</script>

<template>
    <AppLayout :title="formatDate(lesson.date_start)">
        <h1 class="text-center mb-8 mt-16 font-bold text-xl">
            Détails de la séance du
            <span class="text-blue-900">
                {{ formatDate(lesson.date_start) + " à " + timeStart }}
            </span>
            du module
            <span class="text-blue-900">{{ lesson.course.name }}</span>
        </h1>
        <div v-if="!editing">
            <ul
                class="bg-gray-300 p-4 mb-2 border border-gray-400 rounded-md shadow-lg w-1/4 m-auto"
            >
                <li class="font-bold">
                    {{ "Date : " + formatDate(lesson.date_start) }}
                </li>
                <li>{{ "Heure de début: " + timeStart }}</li>
                <li>{{ "Heure de fin: " + timeEnd }}</li>
                <li>
                    {{
                        "Places : " +
                        lesson.users_count +
                        "/" +
                        lesson.spots_max
                    }}
                </li>
                <li>{{ "Prix : " + lesson.price + " €" }}</li>
                <li class="mt-2">
                    {{ "Créée le : " + formatDate(lesson.created_at) }}
                </li>
                <li>
                    {{
                        "Dernière modification le : " +
                        formatDate(lesson.updated_at)
                    }}
                </li>
                <div class="flex justify-between">
                    <button
                        type="button"
                        @click="startEditing"
                        class="text-blue-600 mt-8 hover:text-white border border-blue-600 hover:bg-blue-600 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg p-2"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"
                            />
                            <path
                                d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"
                            />
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="confirmLessonDeletion(lesson.id)"
                        class="text-red-600 mt-8 hover:text-white border border-red-600 hover:bg-red-600 focus:ring-4 focus:outline-none focus:ring-red-300 rounded-lg p-2"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            ></path>
                        </svg>
                    </button>
                </div>
            </ul>
        </div>

        <form v-else @submit.prevent="submitEdit" class="max-w-lg mx-auto">
            <h2 class="text-center font-bold text-lg mb-6 mt-12">
                Modifier la séance
            </h2>

            <div class="mb-5 w-2/3 mx-auto">
                <label for="date" class="text-gray-700 mb-2 block font-medium"
                    >Date</label
                >
                <input
                    type="date"
                    id="date"
                    v-model="editForm.date"
                    @change="editForm.validate('date')"
                    :min="formatDate(null, 'yyyy-mm-dd', 1)"
                    class="bg-gray-200 focus:bg-gray-300 border border-gray-400 rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 w-full"
                />
                <div
                    v-if="editForm.invalid('date')"
                    class="text-sm text-red-600"
                >
                    {{ editForm.errors.date }}
                </div>
            </div>

            <div class="mb-5 w-2/3 mx-auto">
                <label
                    for="time_start"
                    class="text-gray-700 mb-2 block font-medium"
                    >Heure de début</label
                >
                <input
                    type="time"
                    id="time_start"
                    v-model="editForm.time_start"
                    class="bg-gray-200 focus:bg-gray-300 border border-gray-400 rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 w-full"
                    @change="editForm.validate('time_start')"
                />
                <div
                    v-if="editForm.invalid('time_start')"
                    class="text-sm text-red-600"
                >
                    {{ editForm.errors.time_start }}
                </div>
            </div>

            <div class="relative my-8 w-2/3 mx-auto">
                <label for="duration" class="block font-medium text-gray-700"
                    >Durée</label
                >
                <input
                    @change="
                        () => {
                            editForm.validate('duration');
                            editForm.validate('time_end');
                        }
                    "
                    type="range"
                    id="duration"
                    v-model="editForm.duration"
                    min="1"
                    max="10"
                    step="0.5"
                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer mt-1 p-2 border border-gray-300"
                />
                <span class="text-sm text-gray-500 absolute start-0 -bottom-6"
                    >1</span
                >
                <span class="text-sm text-gray-500 absolute end-0 -bottom-6"
                    >10</span
                >
                <span
                    class="text-sm text-gray-500 font-bold absolute start-1/2 transform -translate-x-1/2 -bottom-6"
                    >{{ formattedDuration }}</span
                >

                <span
                    v-if="editForm.errors.duration"
                    class="text-red-600 text-sm"
                    >{{ editForm.errors.duration }}</span
                >
            </div>

            <div class="mb-5 w-2/3 mx-auto">
                <label
                    for="time_end"
                    class="text-gray-700 mb-2 block font-medium"
                    >Heure de fin</label
                >
                <input
                    type="time"
                    id="time_end"
                    v-model="editForm.time_end"
                    class="bg-gray-200 focus:bg-gray-300 border border-gray-400 rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 w-full"
                    @change="
                        () => {
                            updateDuration();
                            editForm.validate('time_end');
                        }
                    "
                />
                <div
                    v-if="editForm.invalid('time_end')"
                    class="text-sm text-red-600"
                >
                    {{ editForm.errors.time_end }}
                </div>
            </div>

            <div class="mb-5 w-2/3 mx-auto">
                <label
                    for="spots_max"
                    class="text-gray-700 mb-2 block font-medium"
                    >Nombre maximum de places</label
                >
                <input
                    type="number"
                    id="spots_max"
                    v-model="editForm.spots_max"
                    @change="editForm.validate('spots_max')"
                    class="bg-gray-200 focus:bg-gray-300 border border-gray-400 rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 w-full"
                />
                <div
                    v-if="editForm.invalid('spots_max')"
                    class="text-sm text-red-600"
                >
                    {{ editForm.errors.spots_max }}
                </div>
            </div>

            <div class="mb-5 w-2/3 mx-auto">
                <label for="price" class="text-gray-700 mb-2 block font-medium"
                    >Prix</label
                >
                <input
                    type="number"
                    step="any"
                    id="price"
                    v-model="editForm.price"
                    @change="editForm.validate('price')"
                    class="bg-gray-200 focus:bg-gray-300 border border-gray-400 rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 w-full"
                />
                <div
                    v-if="editForm.invalid('price')"
                    class="text-sm text-red-600"
                >
                    {{ editForm.errors.price }}
                </div>
            </div>

            <div class="flex justify-center mt-8">
                <button
                    type="submit"
                    :disabled="editForm.processing"
                    class="focus:outline-none text-white bg-blue-900 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 block mr-4"
                >
                    Enregistrer
                </button>
                <button
                    @click="cancelEditing"
                    type="button"
                    class="focus:outline-none text-white bg-gray-700 hover:bg-gray-600 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 block"
                >
                    Annuler
                </button>
            </div>
        </form>

        <h2 class="text-center mb-8 mt-16 font-bold text-lg">
            Liste des utilisateurs inscrits à la séance
        </h2>

        <div
            class="w-2/3 mx-auto grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4 mb-12"
        >
            <a
                :href="route('users.show', { user })"
                v-for="(user, index) in lesson.users"
                :key="user.id"
            >
                <ul
                    class="bg-gray-300 p-4 mb-2 border border-gray-400 rounded-md shadow-lg"
                >
                    <li class="mb-2">
                        <p class="font-bold">
                            {{
                                index +
                                1 +
                                ".  " +
                                user.last_name +
                                " " +
                                user.first_name
                            }}
                        </p>
                        <p>{{ "ID :" + " " + user.id }}</p>
                    </li>
                </ul>
            </a>
        </div>

        <DialogModal :show="confirmingLessonDeletion" @close="closeModal">
            <template #title class="text-black"> Supprimer la séance </template>

            <template #content>
                <p class="text-black">
                    Êtes-vous sûr de vouloir supprimer cette séance ?
                    <span class="font-bold">
                        <template v-if="lesson.users_count === 1">
                            1 personne est inscrite à cette séance.
                        </template>
                        <template v-else-if="lesson.users_count > 1">
                            {{ lesson.users_count }} personnes sont inscrites à
                            cette séance.
                        </template>
                        <template v-else>
                            Aucune personne n'est inscrite à la séance.
                        </template></span
                    ><br />Cette action est irréversible.
                </p>
                <div class="flex items-center mt-4">
                    <input
                        id="confirmDeletionCheckbox"
                        type="checkbox"
                        v-model="isCheckboxChecked"
                        class="mr-2 leading-tight"
                    />
                    <label for="confirmDeletionCheckbox" class="text-black ml-1"
                        >Je comprends les conséquences de cette action.</label
                    >
                </div>
            </template>

            <template #footer>
                <div class="flex justify-between">
                    <button
                        class="focus:outline-none text-white bg-gray-700 hover:bg-gray-600 focus:ring-4 mr-2 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 block"
                        type="button"
                        @click="closeModal"
                    >
                        Annuler
                    </button>

                    <button
                        class="ms-3 focus:outline-none text-white bg-red-600 hover:bg-red-500 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 block"
                        :class="{ 'opacity-25': !isCheckboxChecked }"
                        :disabled="!isCheckboxChecked"
                        @click="deleteLesson"
                    >
                        Supprimer
                    </button>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>
