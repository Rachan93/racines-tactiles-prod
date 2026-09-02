<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { Button } from "@/Components/ui/button";
import { Calendar } from "@/Components/ui/calendar";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import {
    DateFormatter,
    type DateValue,
    getLocalTimeZone,
    parseDate,
} from "@internationalized/date";
import { CalendarIcon, FilterIcon, XIcon } from "lucide-vue-next";
import { cn } from "@/lib/utils";
import { useDateFormatting } from "@/Composables/users/useDateFormatting";

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({
            operator: "",
            date: "",
            dateEnd: "",
        }),
    },
    label: {
        type: String,
        default: "Date d'inscription",
    },
});

const emit = defineEmits(["update:modelValue", "filter"]);

// État local du filtre
const operator = ref(props.modelValue.operator || "");

// Correction: Vérifier le type de données avant de le parser
const date = ref<DateValue | undefined>(
    props.modelValue.date
        ? typeof props.modelValue.date === "string"
            ? parseDate(props.modelValue.date)
            : (props.modelValue.date as DateValue)
        : undefined
);

const dateEnd = ref<DateValue | undefined>(
    props.modelValue.dateEnd
        ? typeof props.modelValue.dateEnd === "string"
            ? parseDate(props.modelValue.dateEnd)
            : (props.modelValue.dateEnd as DateValue)
        : undefined
);

const isPopoverOpen = ref(false);
const isDatePopoverOpen = ref(false);
const isDateEndPopoverOpen = ref(false);

// Importer les fonctions de formatage de date
const { formatDateForBackend } = useDateFormatting();

// Formateur de date en français - correction pour TypeScript
const df = new DateFormatter("fr-FR", {
    // @ts-ignore - dateStyle est supporté par l'API mais pas par la définition de type TypeScript
    dateStyle: "long",
});

// Options d'opérateurs disponibles
const operatorOptions = [
    { value: "before", label: "Avant le" },
    { value: "after", label: "Après le" },
    { value: "equal", label: "Égale au" },
    { value: "before_equal", label: "Avant ou égale au" },
    { value: "after_equal", label: "Après ou égale au" },
    { value: "between", label: "Entre le" },
];

// Label de l'opérateur sélectionné
const operatorLabel = computed(() => {
    // Correction pour l'erreur find()
    const selectedOperator = operatorOptions.filter(
        (opt) => opt.value === operator.value
    )[0];
    return selectedOperator ? selectedOperator.label : "";
});

// Texte à afficher dans le bouton
const displayText = computed(() => {
    if (!operator.value || !date.value) {
        return props.label;
    }

    const formattedDate = date.value
        ? df.format(date.value.toDate(getLocalTimeZone()))
        : "";
    const formattedEndDate = dateEnd.value
        ? df.format(dateEnd.value.toDate(getLocalTimeZone()))
        : "";

    if (operator.value === "between" && dateEnd.value) {
        return `${operatorLabel.value} ${formattedDate} et le ${formattedEndDate}`;
    } else {
        return `${operatorLabel.value} ${formattedDate}`;
    }
});

// Libellés pour les boutons de sélection de date
const startDateButtonText = computed(() => {
    if (date.value) {
        return df.format(date.value.toDate(getLocalTimeZone()));
    }
    return "Choisir une date";
});

const endDateButtonText = computed(() => {
    if (dateEnd.value) {
        return df.format(dateEnd.value.toDate(getLocalTimeZone()));
    }
    return "Choisir une date";
});

// Déterminer si le filtre est actif et valide
const isFilterValid = computed(() => {
    return (
        !!operator.value &&
        !!date.value &&
        (operator.value !== "between" || !!dateEnd.value)
    );
});

// Déterminer si le filtre est actuellement appliqué
const isFilterActive = computed(() => {
    return (
        !!props.modelValue.operator &&
        !!props.modelValue.date &&
        (props.modelValue.operator !== "between" || !!props.modelValue.dateEnd)
    );
});

// Mettre à jour le modèle quand l'état local change
function updateLocalModel() {
    const stringDate = date.value ? date.value.toString() : "";
    const stringDateEnd = dateEnd.value ? dateEnd.value.toString() : "";

    emit("update:modelValue", {
        operator: operator.value,
        date: stringDate,
        dateEnd: stringDateEnd,
    });
}

// Fonction de mise à jour de la date avec fermeture du popover
function updateDate(newDate: DateValue | undefined) {
    date.value = newDate;
    updateLocalModel();
    isDatePopoverOpen.value = false;
}

// Fonction de mise à jour de la date de fin avec fermeture du popover
function updateDateEnd(newDate: DateValue | undefined) {
    dateEnd.value = newDate;
    updateLocalModel();
    isDateEndPopoverOpen.value = false;
}

// Appliquer le filtre
function handleSearch() {
    if (isFilterValid.value) {
        // Utiliser la fonction formatDateForBackend du composable
        const formattedDate = date.value
            ? formatDateForBackend(date.value)
            : "";
        const formattedDateEnd = dateEnd.value
            ? formatDateForBackend(dateEnd.value)
            : "";

        emit("filter", {
            operator: operator.value,
            date: formattedDate,
            dateEnd: formattedDateEnd,
        });
        isPopoverOpen.value = false;
    }
}

// Réinitialiser le filtre
function resetFilter() {
    operator.value = "";
    date.value = undefined;
    dateEnd.value = undefined;
    updateLocalModel();
    emit("filter", { operator: "", date: "", dateEnd: "" });
}

// Surveiller les changements de propriétés entrantes
watch(
    () => props.modelValue,
    (newValue) => {
        operator.value = newValue.operator || "";

        // Correction: Vérification et parsing sécurisés ici aussi
        if (newValue.date) {
            date.value =
                typeof newValue.date === "string"
                    ? parseDate(newValue.date)
                    : (newValue.date as DateValue);
        } else {
            date.value = undefined;
        }

        if (newValue.dateEnd) {
            dateEnd.value =
                typeof newValue.dateEnd === "string"
                    ? parseDate(newValue.dateEnd)
                    : (newValue.dateEnd as DateValue);
        } else {
            dateEnd.value = undefined;
        }
    },
    { deep: true }
);
</script>

<template>
    <Popover v-model:open="isPopoverOpen">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                :class="
                    cn(
                        'w-full justify-start text-left font-normal',
                        isFilterActive
                            ? 'border-primary text-primary'
                            : 'text-muted-foreground'
                    )
                "
            >
                <div class="flex items-center gap-2 w-full">
                    <FilterIcon
                        v-if="isFilterActive"
                        class="h-4 w-4 flex-shrink-0"
                    />
                    <CalendarIcon v-else class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate">{{ displayText }}</span>
                    <XIcon
                        v-if="isFilterActive"
                        class="ml-auto h-4 w-4 opacity-70 hover:opacity-100"
                        @click.stop="resetFilter"
                    />
                </div>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto min-w-[320px] p-4">
            <div class="space-y-4">
                <div>
                    <h4 class="font-medium mb-2">{{ props.label }}</h4>
                    <Select
                        v-model="operator"
                        @update:model-value="updateLocalModel"
                    >
                        <SelectTrigger>
                            <SelectValue
                                :placeholder="`Choisir un opérateur`"
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in operatorOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div v-if="operator" class="space-y-4">
                    <!-- Date de début avec Popover -->
                    <div>
                        <Popover v-model:open="isDatePopoverOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    :class="
                                        cn(
                                            'w-full justify-start text-left font-normal',
                                            !date && 'text-muted-foreground'
                                        )
                                    "
                                >
                                    <CalendarIcon class="mr-2 h-4 w-4" />
                                    {{ startDateButtonText }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0">
                                <Calendar
                                    v-model="date"
                                    :weekday-format="'narrow'"
                                    initial-focus
                                    class="border-0"
                                    @update:model-value="updateDate"
                                />
                            </PopoverContent>
                        </Popover>
                    </div>

                    <!-- Date de fin avec Popover (seulement si opérateur est 'between') -->
                    <div v-if="operator === 'between'">
                        <p class="text-sm mb-2 ml-4 font-bold">Et le :</p>
                        <Popover v-model:open="isDateEndPopoverOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    :class="
                                        cn(
                                            'w-full justify-start text-left font-normal',
                                            !dateEnd && 'text-muted-foreground'
                                        )
                                    "
                                >
                                    <CalendarIcon class="mr-2 h-4 w-4" />
                                    {{ endDateButtonText }}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0">
                                <Calendar
                                    v-model="dateEnd"
                                    :weekday-format="'narrow'"
                                    initial-focus
                                    class="border-0"
                                    @update:model-value="updateDateEnd"
                                />
                            </PopoverContent>
                        </Popover>
                    </div>
                </div>

                <div class="flex justify-between pt-4">
                    <Button variant="outline" size="sm" @click="resetFilter">
                        Réinitialiser
                    </Button>
                    <Button
                        size="sm"
                        @click="handleSearch"
                        :disabled="!isFilterValid"
                    >
                        Appliquer
                    </Button>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
