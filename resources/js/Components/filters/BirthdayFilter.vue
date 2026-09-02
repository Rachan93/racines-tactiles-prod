<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { Button } from "@/Components/ui/button";
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
import { FilterIcon, CakeIcon, XIcon } from "lucide-vue-next";
import { cn } from "@/lib/utils";

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({
            operator: "",
            day: "",
            month: "",
            year: "",
            endDay: "",
            endMonth: "",
            endYear: "",
        }),
    },
    label: {
        type: String,
        default: "Date d'anniversaire",
    },
});

const emit = defineEmits(["update:modelValue", "filter"]);

// État local du filtre
const operator = ref(props.modelValue.operator || "");
const day = ref(props.modelValue.day || "");
const month = ref(props.modelValue.month || "");
const year = ref(props.modelValue.year || "all");
const endDay = ref(props.modelValue.endDay || "");
const endMonth = ref(props.modelValue.endMonth || "");
const endYear = ref(props.modelValue.endYear || "all");
const isPopoverOpen = ref(false);

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
    // Utilisation de filter()[0] au lieu de find() pour la compatibilité
    const selectedOperator = operatorOptions.filter(
        (opt) => opt.value === operator.value
    )[0];
    return selectedOperator ? selectedOperator.label : "";
});

// Options pour les jours (1-31) - Version compatible ES5
const dayOptions = [];
for (let i = 0; i < 31; i++) {
    dayOptions.push({
        value: (i + 1).toString(),
        label: (i + 1).toString(),
    });
}

// Options pour les mois (1-12) avec les noms en français
const monthOptions = [
    { value: "1", label: "Janvier" },
    { value: "2", label: "Février" },
    { value: "3", label: "Mars" },
    { value: "4", label: "Avril" },
    { value: "5", label: "Mai" },
    { value: "6", label: "Juin" },
    { value: "7", label: "Juillet" },
    { value: "8", label: "Août" },
    { value: "9", label: "Septembre" },
    { value: "10", label: "Octobre" },
    { value: "11", label: "Novembre" },
    { value: "12", label: "Décembre" },
];

// Options pour les années (100 ans en arrière à partir de l'année courante) - Version compatible ES5
const currentYear = new Date().getFullYear();
const yearOptions = [];
for (let i = 0; i < 100; i++) {
    yearOptions.push({
        value: (currentYear - i).toString(),
        label: (currentYear - i).toString(),
    });
}

// Texte à afficher dans le bouton
const displayText = computed(() => {
    if (!operator.value) {
        return props.label;
    }

    // Formatage de la première date en texte lisible
    function formatDateText(dayVal, monthVal, yearVal) {

        // Obtenir le nom du mois
        const matchingMonth = monthOptions.filter(
            (m) => m.value === monthVal
        )[0];
        const monthName = matchingMonth
            ? matchingMonth.label.toLowerCase()
            : monthVal;

        // Construire la chaîne de date
        let dateText = `${dayVal} ${monthName}`;
        if (yearVal && yearVal !== "all") {
            dateText += ` ${yearVal}`;
        }

        return dateText;
    }

    // Texte pour la première date
    const firstDateText = formatDateText(day.value, month.value, year.value);

    // Pour l'opérateur "between", ajouter la seconde date
    if (operator.value === "between") {
        const secondDateText = formatDateText(
            endDay.value,
            endMonth.value,
            endYear.value
        );
        return `${operatorLabel.value} ${firstDateText} et le ${secondDateText}`;
    } else {
        return `${operatorLabel.value} ${firstDateText}`;
    }
});

// Déterminer si le filtre est valide
const isFilterValid = computed(() => {
    // Il doit y avoir un opérateur sélectionné
    if (!operator.value) return false;

    // Le jour ET le mois sont obligatoires pour la première date
    const hasRequiredFirstDate = !!day.value && !!month.value;

    // Si l'année est spécifiée pour la première date
    const firstYearIsSpecified = !!year.value && year.value !== "all";
    const firstYear = firstYearIsSpecified ? parseInt(year.value) : null;

    // Si l'opérateur est "between", le jour ET le mois sont également obligatoires pour la seconde date
    if (operator.value === "between") {
        const hasRequiredSecondDate = !!endDay.value && !!endMonth.value;

        // Vérifier si l'année de fin est spécifiée
        const secondYearIsSpecified =
            !!endYear.value && endYear.value !== "all";
        const secondYear = secondYearIsSpecified
            ? parseInt(endYear.value)
            : null;

        // Si les deux années sont spécifiées, vérifier que l'année de fin n'est pas antérieure à l'année de début
        if (firstYearIsSpecified && secondYearIsSpecified) {
            if (secondYear < firstYear) {
                return false; // La date de fin est antérieure à la date de début
            }

            // Si les années sont identiques, vérifier les mois
            if (secondYear === firstYear) {
                const firstMonth = parseInt(month.value);
                const secondMonth = parseInt(endMonth.value);

                if (secondMonth < firstMonth) {
                    return false; // Le mois de fin est antérieur au mois de début dans la même année
                }

                // Si les mois sont identiques, vérifier les jours
                if (secondMonth === firstMonth) {
                    const firstDay = parseInt(day.value);
                    const secondDay = parseInt(endDay.value);

                    if (secondDay < firstDay) {
                        return false; // Le jour de fin est antérieur au jour de début dans le même mois
                    }
                }
            }
        }

        // Si l'année est spécifiée pour la première date, elle doit aussi l'être pour la seconde
        if (firstYearIsSpecified && !secondYearIsSpecified) {
            return false;
        }

        return hasRequiredFirstDate && hasRequiredSecondDate;
    }

    return hasRequiredFirstDate;
});

// Calcul des erreurs de validation spécifiques pour afficher des messages d'erreur
const validationErrors = computed(() => {
    const errors = [];

    if (operator.value === "between") {
        const firstYearIsSpecified = !!year.value && year.value !== "all";
        const firstYear = firstYearIsSpecified ? parseInt(year.value) : null;

        const secondYearIsSpecified =
            !!endYear.value && endYear.value !== "all";
        const secondYear = secondYearIsSpecified
            ? parseInt(endYear.value)
            : null;

        // Vérifier si la date de fin est antérieure à la date de début
        if (
            firstYearIsSpecified &&
            secondYearIsSpecified &&
            secondYear < firstYear
        ) {
            errors.push(
                "La date de fin ne peut pas être antérieure à la date de début."
            );
        } else if (
            firstYearIsSpecified &&
            secondYearIsSpecified &&
            secondYear === firstYear
        ) {
            const firstMonth = parseInt(month.value);
            const secondMonth = parseInt(endMonth.value);

            if (secondMonth < firstMonth) {
                errors.push(
                    "Le mois de fin ne peut pas être antérieur au mois de début pour la même année."
                );
            } else if (secondMonth === firstMonth) {
                const firstDay = parseInt(day.value);
                const secondDay = parseInt(endDay.value);

                if (secondDay < firstDay) {
                    errors.push(
                        "Le jour de fin ne peut pas être antérieur au jour de début pour le même mois."
                    );
                }
            }
        }

        // Erreur si la première année est spécifiée mais pas la seconde
        if (firstYearIsSpecified && !secondYearIsSpecified) {
            errors.push(
                "Si vous spécifiez une année pour la première date, vous devez aussi en spécifier une pour la seconde date."
            );
        }
    }

    return errors;
});

/**
 * Vérifie si une date est valide et corrige automatiquement le jour si nécessaire
 * @returns Le jour corrigé si nécessaire, ou le jour original sinon
 */
function validateAndCorrectDate(
    day: string,
    month: string,
    yearInput?: string
): string {
    if (!day || !month) {
        return day; // On ne corrige pas si jour ou mois non sélectionnés
    }

    const dayNum = parseInt(day);
    const monthNum = parseInt(month);

    // Si l'année n'est pas spécifiée, on ne fait aucune correction (comme demandé)
    if (yearInput === "all" || !yearInput) {
        return day;
    }

    const yearNum = parseInt(yearInput);

    // Vérifier le nombre maximal de jours dans le mois pour cette année
    const lastDayOfMonth = new Date(yearNum, monthNum, 0).getDate();

    // Si le jour entré est supérieur au nombre de jours dans le mois pour cette année
    if (dayNum > lastDayOfMonth) {
        return lastDayOfMonth.toString(); // Retourner le dernier jour valide du mois
    }

    return day; // La date est valide, retourner le jour original
}

// Surveiller les changements de jour ou mois ou année pour correction automatique
watch([day, month, year], ([newDay, newMonth, newYear]) => {
    if (newDay && newMonth) {
        const correctedDay = validateAndCorrectDate(newDay, newMonth, newYear);
        if (correctedDay !== newDay) {
            day.value = correctedDay;
            updateLocalModel();
        }
    }
});

// Surveiller les changements pour la deuxième date (pour l'opérateur between)
watch([endDay, endMonth, endYear], ([newEndDay, newEndMonth, newEndYear]) => {
    if (newEndDay && newEndMonth) {
        const correctedEndDay = validateAndCorrectDate(
            newEndDay,
            newEndMonth,
            newEndYear
        );
        if (correctedEndDay !== newEndDay) {
            endDay.value = correctedEndDay;
            updateLocalModel();
        }
    }
});

// Mettre à jour le modèle quand l'état local change
function updateLocalModel() {
    // Les champs jour et mois n'ont plus de valeur "all", ils peuvent être vides
    emit("update:modelValue", {
        operator: operator.value,
        day: day.value,
        month: month.value,
        year: year.value === "all" ? "" : year.value,
        endDay: endDay.value,
        endMonth: endMonth.value,
        endYear: endYear.value === "all" ? "" : endYear.value,
    });
}

// Appliquer le filtre
function handleSearch() {
    if (isFilterValid.value) {
        emit("filter", {
            operator: operator.value,
            day: day.value,
            month: month.value,
            year: year.value === "all" ? "" : year.value,
            endDay: endDay.value,
            endMonth: endMonth.value,
            endYear: endYear.value === "all" ? "" : endYear.value,
        });
        isPopoverOpen.value = false;
    }
}

// Réinitialiser le filtre
function resetFilter() {
    operator.value = "";
    day.value = "";
    month.value = "";
    year.value = "all";
    endDay.value = "";
    endMonth.value = "";
    endYear.value = "all";
    updateLocalModel();
    emit("filter", {
        operator: "",
        day: "",
        month: "",
        year: "",
        endDay: "",
        endMonth: "",
        endYear: "",
    });
}

// Surveiller les changements de propriétés entrantes
watch(
    () => props.modelValue,
    (newValue) => {
        // Conversion des chaînes vides appropriée pour chaque champ
        operator.value = newValue.operator || "";
        day.value = newValue.day || "";
        month.value = newValue.month || "";
        year.value = newValue.year || "all";
        endDay.value = newValue.endDay || "";
        endMonth.value = newValue.endMonth || "";
        endYear.value = newValue.endYear || "all";

        // Corriger le jour si nécessaire
        if (day.value && month.value && year.value !== "all") {
            day.value = validateAndCorrectDate(
                day.value,
                month.value,
                year.value
            );
        }

        // Répéter le même processus pour la date de fin
        if (endDay.value && endMonth.value && endYear.value !== "all") {
            endDay.value = validateAndCorrectDate(
                endDay.value,
                endMonth.value,
                endYear.value
            );
        }

        // Ajuster les jours si nécessaire après le changement des valeurs
        adjustDayValueIfNeeded();
        adjustEndDayValueIfNeeded();
    },
    { deep: true, immediate: true }
);

/**
 * Calcule le nombre de jours dans un mois pour une année donnée
 */
function getDaysInMonth(month: string, year?: string): number {
    if (month === "all") return 31; // Par défaut, montrer tous les jours possibles

    const monthNum = parseInt(month);

    // Si le mois est février (2)
    if (monthNum === 2) {
        // Si l'année est spécifiée, vérifier si c'est une année bissextile
        if (year && year !== "all") {
            const yearNum = parseInt(year);
            // Année bissextile si divisible par 4, sauf si divisible par 100 mais pas par 400
            const isLeapYear =
                (yearNum % 4 === 0 && yearNum % 100 !== 0) ||
                yearNum % 400 === 0;
            return isLeapYear ? 29 : 28;
        }
        // Sans année spécifiée, on renvoie 29 (pour inclure le 29 février)
        return 29;
    }

    // Pour les autres mois:
    // - Avril (4), Juin (6), Septembre (9), Novembre (11) ont 30 jours
    // - Les autres ont 31 jours
    return [4, 6, 9, 11].includes(monthNum) ? 30 : 31;
}

/**
 * Filtre les options de jours pour n'afficher que les jours valides selon le mois et l'année
 */
const filteredDayOptions = computed(() => {
    const maxDays = getDaysInMonth(month.value, year.value);
    return dayOptions.filter((option) => parseInt(option.value) <= maxDays);
});

/**
 * Filtre les options de jours pour la date de fin
 */
const filteredEndDayOptions = computed(() => {
    const maxDays = getDaysInMonth(endMonth.value, endYear.value);
    return dayOptions.filter((option) => parseInt(option.value) <= maxDays);
});

/**
 * Ajuster la valeur du jour si elle dépasse le maximum pour le mois/année sélectionnés
 */
function adjustDayValueIfNeeded() {
    if (day.value && month.value) {
        const maxDays = getDaysInMonth(month.value, year.value);
        if (parseInt(day.value) > maxDays) {
            day.value = maxDays.toString();
            updateLocalModel();
        }
    }
}

/**
 * Ajuster la valeur du jour de fin si elle dépasse le maximum pour le mois/année sélectionnés
 */
function adjustEndDayValueIfNeeded() {
    if (endDay.value && endMonth.value) {
        const maxDays = getDaysInMonth(endMonth.value, endYear.value);
        if (parseInt(endDay.value) > maxDays) {
            endDay.value = maxDays.toString();
            updateLocalModel();
        }
    }
}

// Surveiller les changements de mois ou d'année pour ajuster le jour si nécessaire
watch([month, year], () => {
    adjustDayValueIfNeeded();
});

// Surveiller les changements pour la seconde date
watch([endMonth, endYear], () => {
    adjustEndDayValueIfNeeded();
});

// Déterminer si le filtre est actif
const isFilterActive = computed((): boolean => {
    return !!operator.value && isFilterValid.value;
});
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
                    <CakeIcon v-else class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate">{{ displayText }}</span>
                    <XIcon
                        v-if="isFilterActive"
                        class="ml-auto h-4 w-4 opacity-70 hover:opacity-100"
                        @click.stop="resetFilter"
                    />
                </div>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto min-w-[460px] p-4">
            <div class="space-y-4">
                <div>
                    <h4 class="font-medium mb-2">{{ props.label }}</h4>

                    <!-- Sélection de l'opérateur -->
                    <Select
                        v-model="operator"
                        @update:model-value="updateLocalModel"
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Choisir un opérateur" />
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

                <!-- Première sélection de date (visible si un opérateur est sélectionné) -->
                <div v-if="operator" class="space-y-3">
                    <h5 class="text-sm font-medium">
                        {{ operator === "between" ? "Date de début" : "Date" }}
                    </h5>

                    <!-- Sélecteurs en ligne (jour/mois/année) -->
                    <div class="grid grid-cols-3 gap-2">
                        <!-- Sélection du jour -->
                        <div>
                            <label class="text-sm font-medium mb-1 block">
                                Jour <span class="text-red-500">*</span>
                            </label>
                            <Select
                                v-model="day"
                                @update:model-value="updateLocalModel"
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Choisir un jour"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in filteredDayOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Sélection du mois -->
                        <div>
                            <label class="text-sm font-medium mb-1 block">
                                Mois <span class="text-red-500">*</span>
                            </label>
                            <Select
                                v-model="month"
                                @update:model-value="updateLocalModel"
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Choisir un mois"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in monthOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Sélection de l'année -->
                        <div>
                            <label class="text-sm font-medium mb-1 block">
                                Année
                            </label>
                            <Select
                                v-model="year"
                                @update:model-value="updateLocalModel"
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Toutes" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Toutes</SelectItem>
                                    <SelectItem
                                        v-for="option in yearOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <!-- Seconde sélection de date (visible uniquement si l'opérateur est "between") -->
                <div v-if="operator === 'between'" class="space-y-3">
                    <h5 class="text-sm font-medium mt-2">Date de fin</h5>

                    <!-- Sélecteurs en ligne (jour/mois/année) pour la date de fin -->
                    <div class="grid grid-cols-3 gap-2">
                        <!-- Sélection du jour de fin -->
                        <div>
                            <label class="text-sm font-medium mb-1 block">
                                Jour <span class="text-red-500">*</span>
                            </label>
                            <Select
                                v-model="endDay"
                                @update:model-value="updateLocalModel"
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Choisir un jour"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in filteredEndDayOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Sélection du mois de fin -->
                        <div>
                            <label class="text-sm font-medium mb-1 block">
                                Mois <span class="text-red-500">*</span>
                            </label>
                            <Select
                                v-model="endMonth"
                                @update:model-value="updateLocalModel"
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Choisir un mois"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in monthOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Sélection de l'année de fin -->
                        <div>
                            <label class="text-sm font-medium mb-1 block">
                                Année
                                <span
                                    v-if="year && year !== 'all'"
                                    class="text-red-500"
                                    >*</span
                                >
                            </label>
                            <Select
                                v-model="endYear"
                                @update:model-value="updateLocalModel"
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Toutes" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Toutes</SelectItem>
                                    <SelectItem
                                        v-for="option in yearOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
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
