<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm as usePrecognitionForm } from "laravel-precognition-vue-inertia";
import { useForm } from "@inertiajs/vue3";
import DialogModal from "@/Components/DialogModal.vue";
import { ref } from "vue";

const props = defineProps(["course", "types"]);

const editForm = usePrecognitionForm(
    "patch",
    route("courses.update", { course: props.course.id }),
    {
        name: props.course.name,
        type: props.course.type.id,
        spots_max: props.course.spots_max,
        price: props.course.price,
    }
);
editForm.setValidationTimeout(300);

const editing = ref(false);

const startEditing = () => {
    editing.value = true;
    editForm.name = props.course.name;
    editForm.type = props.course.type.id;
    editForm.spots_max = props.course.spots_max;
    editForm.price = props.course.price;
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

function formatDate(dateString) {
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, "0");
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    const year = date.getFullYear().toString();
    return `${day}/${month}/${year}`;
}

const confirmingCourseDeletion = ref(false);
const courseIdToDelete = ref(null);
const formDeleteCourse = useForm("delete", {});
const isCheckboxChecked = ref(false);

const confirmCourseDeletion = (id) => {
    courseIdToDelete.value = id;
    confirmingCourseDeletion.value = true;
    isCheckboxChecked.value = false;
};

var deleteCourse = () => {
    if (isCheckboxChecked.value) {
        formDeleteCourse.delete(
            route("courses.delete", courseIdToDelete.value),
            {
                preserveScroll: true,
                onSuccess: () => {
                    confirmingCourseDeletion.value = false;
                },
            }
        );
    }
};

var closeModal = () => {
    confirmingCourseDeletion.value = false;
};
</script>

<template>
    <AppLayout :title="course.name">
        <h1 class="text-center mb-8 mt-16 font-bold text-xl">
            Détails du module
            <span class="text-blue-900">{{ course.name }}</span>
        </h1>
        <div v-if="!editing">
            <ul
                class="bg-gray-300 p-4 mb-2 border border-gray-400 rounded-md shadow-lg w-1/4 m-auto"
            >
                <li class="font-bold">{{ course.id + ". " + course.name }}</li>
                <li>
                    {{
                        "Places : " +
                        course.users_count +
                        "/" +
                        course.spots_max
                    }}
                </li>
                <li>{{ "Type : " + course.type.name }}</li>
                <li>{{ "Prix : " + course.price + " €" }}</li>
                <li class="mt-2">
                    {{ "Créé le : " + formatDate(course.created_at) }}
                </li>
                <li>
                    {{
                        "Dernière modification le : " +
                        formatDate(course.updated_at)
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
                        @click="confirmCourseDeletion(course.id)"
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
                Modifier le module
            </h2>
            <div class="mb-5 w-2/3 mx-auto">
                <label for="name" class="text-gray-700 mb-2 block font-medium"
                    >Nom du cours</label
                >
                <input
                    type="text"
                    id="name"
                    v-model="editForm.name"
                    @change="editForm.validate('name')"
                    class="bg-gray-200 focus:bg-gray-300 border border-gray-400 rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 w-full"
                />
                <div
                    v-if="editForm.invalid('name')"
                    class="text-sm text-red-600"
                >
                    {{ editForm.errors.name }}
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
                <label for="type" class="text-gray-700 mb-2 block font-medium"
                    >Type de cours</label
                >
                <select
                    id="type"
                    v-model="editForm.type"
                    @change="editForm.validate('type')"
                    class="bg-gray-200 focus:bg-gray-300 border border-gray-400 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 w-full"
                >
                    <option
                        v-for="typo in types"
                        :key="typo.id"
                        :value="typo.id"
                    >
                        {{ typo.name }}
                    </option>
                </select>
                <div
                    v-if="editForm.invalid('type')"
                    class="text-sm text-red-600"
                >
                    {{ editForm.errors.type }}
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
            Liste des séances programmées pour le module
        </h2>

        <div
            class="w-2/3 mx-auto grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4 mb-12"
        >
            <a
                :href="route('lessons.show', { lesson })"
                v-for="(lesson, index) in course.lessons"
                :key="lesson.id"
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
                                formatDate(lesson.date_start)
                            }}
                        </p>
                        <p>
                            {{
                                "Places : " +
                                lesson.users_count +
                                "/" +
                                lesson.spots_max
                            }}
                        </p>
                        <p>{{ "Prix : " + lesson.price + " €" }}</p>
                        <p>{{ "ID :" + " " + lesson.id }}</p>
                    </li>
                </ul>
            </a>
        </div>

        <h2 class="text-center mb-8 mt-16 font-bold text-lg">
            Liste des utilisateurs inscrits au module
        </h2>

        <div
            class="w-2/3 mx-auto grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4 mb-12"
        >
            <a
                :href="route('users.show', { user })"
                v-for="(user, index) in course.users"
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

        <DialogModal :show="confirmingCourseDeletion" @close="closeModal">
            <template #title class="text-black"> Supprimer le cours </template>

            <template #content>
                <p class="text-black">
                    Êtes-vous sûr de vouloir supprimer ce cours ?
                    <span class="font-bold">
                        <template v-if="course.users_count === 1">
                            1 personne est inscrite à ce module.
                        </template>
                        <template v-else-if="course.users_count > 1">
                            {{ course.users_count }} personnes sont inscrites à
                            ce module.
                        </template>
                        <template v-else>
                            Aucune personne n'est inscrite au module.
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
                        @click="deleteCourse"
                    >
                        Supprimer
                    </button>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>
