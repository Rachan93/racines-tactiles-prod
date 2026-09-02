<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { useForm as usePrecognitionForm } from "laravel-precognition-vue-inertia";
import { useForm } from "@inertiajs/vue3";
import DialogModal from "@/Components/DialogModal.vue";
import Pagination from "@/Components/Pagination.vue";
import { ref, watch } from "vue";

const props = defineProps(["users", "courses", "filters", "allUserData"]);

//console.log("allUserData", props.allUserData);
console.log("filters", props.filters);

const searchForm = usePrecognitionForm("get", route("users.indexOld"), {
    search: props.filters.search || "",
    created_at_operator: props.filters.created_at_operator || "",
    created_at_date: props.filters.created_at_date || "",
    birthday_operator: props.filters.birthday_operator || "",
    birthday_date: props.filters.birthday_date || "",
    next_lesson_operator: props.filters.next_lesson_operator || "",
    next_lesson_date: props.filters.next_lesson_date || "",
    role: props.filters.role || "",
    billing: props.filters.billing || "",
    course: props.filters.course || "",
    company_search: props.filters.company_search || "",
    sorting: props.filters.sorting || "last_name",
});
console.log(searchForm.sorting);
searchForm.setValidationTimeout(300);

const submitSearch = () => {
    selectedUsers.value = [];
    searchForm.submit({
        preserveScroll: true,
        preserveState: true,
        onSuccess: (response) => {
            allUserData.value = response.props.allUserData;
        },
    });
};

const resetSearchForm = () => {
    searchForm.search = "";
    searchForm.created_at_operator = "";
    searchForm.created_at_date = "";
    searchForm.birthday_operator = "";
    searchForm.birthday_date = "";
    searchForm.next_lesson_operator = "";
    searchForm.next_lesson_date = "";
    searchForm.role = "";
    searchForm.billing = "";
    searchForm.course = "";
    searchForm.company_search = "";
    searchForm.sorting = "last_name";
};

const allUserData = ref(props.allUserData);

const selectedUsers = ref([]);

const selectAllUsers = () => {
    selectedUsers.value = allUserData.value.map((user) => user.id);
};

const unselectAllUsers = () => {
    selectedUsers.value = [];
};

watch(selectedUsers, (newValue) => {
    console.log("Selected users:", newValue);
});

const action = ref("add_recipients");

const performAction = () => {
    if (selectedUsers.value.length > 0) {
        if (action.value === "add_recipients") {
            addSelectedUsersToRecipients();
        } else if (action.value === "delete_users") {
            confirmUsersDeletion();
        }
        } else {
            alert(
                "Vous devez sélectionner au moins un membre pour effectuer une action."
            );

        }

};
const confirmingUsersDeletion = ref(false);
const usersIdToDelete = ref(null);
const formDeleteUsers = useForm("delete", {});
const isCheckboxChecked = ref(false);

const confirmUsersDeletion = () => {
    usersIdToDelete.value = selectedUsers.value;
    console.log(usersIdToDelete.value);
    confirmingUsersDeletion.value = true;
    isCheckboxChecked.value = false;
};

const deleteUsers = () => {
    if (isCheckboxChecked.value) {
        formDeleteUsers.delete(
            route("users.delete", { id: usersIdToDelete.value }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    closeModal();
                    selectedUsers.value = [];
                },
            }
        );
    }
};

var closeModal = () => {
    confirmingUsersDeletion.value = false;
};

const emailForm = usePrecognitionForm("post", route("users.mail"), {
    recipients: [],
    recipients_names: "",
    template: "custom",
    subject: "",
    message: "",
    channels: ["mail", "notification"],
});
emailForm.setValidationTimeout(300);

const showMailBody = ref(emailForm.template === "custom");

const toggleMailBody = () => {
    showMailBody.value = emailForm.template === "custom";
};

const addSelectedUsersToRecipients = () => {
    selectedUsers.value.forEach((userId) => {
        if (!emailForm.recipients.includes(userId)) {
            const user = allUserData.value.find((u) => u.id === userId);
            emailForm.recipients.push(userId);
            emailForm.recipients_names += `${user.last_name} ${user.first_name}; `;
        }
    });
};

const submitEmail = () => {
    if (emailForm.template !== "custom") {
        emailForm.subject = "";
        emailForm.message = "";
    }

    emailForm.submit({
        preserveScroll: true,
        onSuccess: () => {
            emailForm.recipients = [];
            emailForm.recipients_names = "";
            emailForm.template = "custom";
            emailForm.subject = "";
            emailForm.message = "";
        },
    });
};
</script>

<template>
    <AppLayout title="Membres">
        <h2 class="text-center mb-8 mt-16 font-bold text-xl">
            Liste des membres
        </h2>

        <div class="w-2/3 mx-auto mb-8">
            <form
                @submit.prevent="submitSearch"
                class="flex items-center space-x-4"
            >
                <input
                    v-model="searchForm.search"
                    type="text"
                    placeholder="Rechercher par nom, prénom, email, téléphone, commune ou code postal"
                    class="w-full p-2 border rounded min-w-32"
                />

                <div class="flex flex-col space-y-2">
                    <label for="created_at_operator" class="font-bold"
                        >Date d'inscription</label
                    >
                    <select
                        id="created_at_operator"
                        name="created_at_operator"
                        v-model="searchForm.created_at_operator"
                        class="p-2 border rounded"
                    >
                        <option value="">Sélectionner un opérateur</option>
                        <option value="<">avant le</option>
                        <option value=">">après le</option>
                        <option value="=">égale au</option>
                        <option value="<=">avant ou égale au</option>
                        <option value=">=">après ou égale au</option>
                    </select>
                    <input
                        v-model="searchForm.created_at_date"
                        type="date"
                        class="p-2 border rounded"
                    />
                </div>

                <div class="flex flex-col space-y-2">
                    <label for="birthday_operator" class="font-bold"
                        >Date d'anniversaire</label
                    >
                    <select
                        id="birthday_operator"
                        name="birthday_operator"
                        v-model="searchForm.birthday_operator"
                        class="p-2 border rounded"
                    >
                        <option value="">Sélectionner un opérateur</option>
                        <option value="<">avant le</option>
                        <option value=">">après le</option>
                        <option value="=">égale au</option>
                        <option value="<=">avant ou égale au</option>
                        <option value=">=">après ou égale au</option>
                    </select>
                    <input
                        v-model="searchForm.birthday_date"
                        type="date"
                        class="p-2 border rounded"
                    />
                </div>

                <div class="flex flex-col space-y-2">
                    <label for="next_lesson_operator" class="font-bold"
                        >Date de séance prévue</label
                    >
                    <select
                        id="next_lesson_operator"
                        name="next_lesson_operator"
                        v-model="searchForm.next_lesson_operator"
                        class="p-2 border rounded"
                    >
                        <option value="">Sélectionner un opérateur</option>
                        <option value="<">avant le</option>
                        <option value=">">après le</option>
                        <option value="=">égale au</option>
                        <option value="<=">avant ou égale au</option>
                        <option value=">=">après ou égale au</option>
                    </select>
                    <input
                        v-model="searchForm.next_lesson_date"
                        type="date"
                        class="p-2 border rounded"
                    />
                </div>

                <div class="flex flex-col space-y-2">
                    <label for="role" class="font-bold">Rôle</label>
                    <select
                        id="role"
                        name="role"
                        v-model="searchForm.role"
                        class="p-2 border rounded"
                    >
                        <option value="">Tous</option>
                        <option value="1">Administrateur</option>
                        <option value="2">Modérateur</option>
                        <option value="2">Membre</option>
                    </select>
                </div>

                <div class="flex flex-col space-y-2">
                    <label for="billing" class="font-bold">Facturation</label>
                    <select
                        id="billing"
                        name="billing"
                        v-model="searchForm.billing"
                        class="p-2 border rounded"
                    >
                        <option value="">Tous</option>
                        <option value="1">Oui</option>
                        <option value="0">Non</option>
                    </select>
                </div>

                <input
                    v-model="searchForm.company_search"
                    type="text"
                    placeholder="Rechercher par nom, numéro de TVA, commune ou code postal de l'entreprise"
                    class="w-full p-2 border rounded min-w-32"
                />
                <div class="flex flex-col space-y-2">
                    <label for="course" class="font-bold"
                        >Inscrit au module</label
                    >
                    <select
                        v-model="searchForm.course"
                        id="course"
                        class="p-2 border rounded"
                    >
                        <option value="">Tous</option>
                        <option
                            v-for="course in courses"
                            :key="course.id"
                            :value="course.id"
                        >
                            {{ course.name }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col space-y-2">
                    <label for="sorting" class="font-bold">Trier par</label>
                    <select
                        v-model="searchForm.sorting"
                        id="sorting"
                        class="p-2 border rounded"
                        @change="
                            {
                                {
                                    console.log(searchForm.sorting);
                                }
                            }
                        "
                    >
                        <option value="last_name">ordre alphabetique</option>
                        <option value="birth_date">date de naissance</option>
                        <option value="created_at">date d'inscription</option>
                    </select>
                </div>
                <button
                    type="button"
                    @click="resetSearchForm"
                    class="p-2 bg-red-500 text-white rounded"
                >
                    Réinitialiser les filtres
                </button>
                <button
                    type="submit"
                    class="p-2 bg-blue-500 text-white rounded"
                >
                    Rechercher
                </button>
            </form>
        </div>

        <div class="text-center mb-4">
            <button
                @click="selectAllUsers"
                class="p-2 bg-green-500 text-white rounded"
            >
                Tout Sélectionner
            </button>
            <button
                @click="unselectAllUsers"
                class="p-2 bg-red-500 text-white rounded"
            >
                Tout Désélectionner
            </button>
        </div>

        <div class="text-center mb-4">
            <p>{{ selectedUsers.length }} membres sélectionnés</p>
        </div>

        <div class="flex items-center mb-4">
            <select
                id="action"
                name="action"
                v-model="action"
                class="p-2 border rounded mr-2"
            >
                <option value="add_recipients">
                    Ajouter aux destinataires
                </option>
                <option value="delete_users">Supprimer les membres</option>
            </select>
            <button
                type="button"
                @click="performAction"
                class="p-2 bg-green-500 text-white rounded"
            >
                Effectuer l'action
            </button>
        </div>
        <div
            v-if="users.total > 0"
            class="w-2/3 mx-auto grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4 mb-12"
        >
            {{ users.total }} membres correspondent à la recherche.
            <div
                v-for="user in users.data"
                :key="user.id"
                class="flex items-center space-x-2"
            >
                <input
                    type="checkbox"
                    v-model="selectedUsers"
                    :value="user.id"
                />
                <a :href="route('users.show', { user })">
                    <ul
                        class="bg-gray-300 p-4 mb-2 border border-gray-400 rounded-md shadow-lg"
                    >
                        <li class="mb-2">
                            <p class="font-bold">
                                {{
                                    user.id +
                                    ". " +
                                    user.last_name +
                                    " " +
                                    user.first_name
                                }}
                            </p>
                            <p>{{ "Rôle : " + user.role.name }}</p>
                            <p>{{ "Facturation : " + user.billing }}</p>
                        </li>
                    </ul>
                </a>
            </div>
        </div>
        <div v-else class="text-center">
            <p>Aucun membre ne correspond à votre recherche.</p>
        </div>
        <pagination
            :links="props.users.links"
            class="max-w-4xl mx-auto mb-4 flex justify-center"
        />

        <div
            class="w-2/3 mx-auto mt-8 p-6 bg-white border border-gray-300 rounded-md shadow-lg"
        >
            <h3 class="text-center font-bold text-lg mb-4">
                Envoyer un message
            </h3>
            <form @submit.prevent="submitEmail">
                <div class="mb-4">
                    <label>
                        <input
                            type="checkbox"
                            v-model="emailForm.channels"
                            value="mail"
                        />
                        Envoyer par mail
                    </label>
                    <label>
                        <input
                            type="checkbox"
                            v-model="emailForm.channels"
                            value="notification"
                        />
                        Envoyer comme notification
                    </label>
                    <label for="recipients_names" class="block font-bold mb-2"
                        >Destinataires</label
                    >

                    <input
                        v-model="emailForm.recipients_names"
                        id="recipients_names"
                        type="text"
                        placeholder="Destinataires"
                        class="w-full p-2 border rounded"
                        readonly
                    />
                </div>

                <input
                    v-model="emailForm.recipients"
                    id="recipients"
                    type="hidden"
                />

                <div class="mb-4">
                    <label for="template" class="block font-bold mb-2"
                        >Modèle</label
                    >
                    <select
                        id="template"
                        name="template"
                        @change="toggleMailBody"
                        v-model="emailForm.template"
                        class="p-2 border rounded"
                    >
                        <option value="custom">Personnalisé</option>
                        <option value="test1">Test 1</option>
                        <option value="test2">Test 2</option>
                    </select>
                </div>
                <template v-if="showMailBody">
                    <div class="mb-4">
                        <label for="subject" class="block font-bold mb-2"
                            >Objet</label
                        >
                        <input
                            v-model="emailForm.subject"
                            id="subject"
                            type="text"
                            placeholder="Objet du message"
                            class="w-full p-2 border rounded"
                            required
                        />
                    </div>
                    <div class="mb-4">
                        <label for="message" class="block font-bold mb-2"
                            >Message</label
                        >
                        <textarea
                            v-model="emailForm.message"
                            id="message"
                            placeholder="Votre message"
                            class="w-full p-2 border rounded"
                            rows="5"
                            required
                        ></textarea>
                    </div>
                </template>
                <div class="text-center">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-500 text-white rounded"
                    >
                        Envoyer
                    </button>
                </div>
            </form>
        </div>
        <DialogModal :show="confirmingUsersDeletion" @close="closeModal">
            <template
                v-if="selectedUsers.length === 1"
                #title
                class="text-black"
            >
                Supprimer le membre
            </template>
            <template v-else #title class="text-black">
                Supprimer les membres
            </template>

            <template #content>
                <p class="text-black">
                    <template v-if="selectedUsers.length === 1">
                        Êtes-vous sûr de vouloir supprimer ce membre ?
                    </template>
                    <template v-else>
                        Êtes-vous sûr de vouloir supprimer ces
                        <span class="font-bold">{{
                            selectedUsers.length
                        }}</span>
                        membres ?
                    </template>

                    <br />Cette action est irréversible.
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
                        @click="deleteUsers"
                    >
                        Supprimer
                    </button>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>
