<script setup>
import { ref, computed, watch } from "vue";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
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
import { Cake, X } from "lucide-vue-next";

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

const emit = defineEmits(["update:modelValue", "apply"]);

const isOpen = ref(false);

const operator = ref(props.modelValue?.operator || "");
const day = ref(props.modelValue?.day ? String(props.modelValue.day) : "all");
const month = ref(props.modelValue?.month ? String(props.modelValue.month) : "all");
const year = ref(props.modelValue?.year ? String(props.modelValue.year) : "all");

const endDay = ref(props.modelValue?.endDay ? String(props.modelValue.endDay) : "all");
const endMonth = ref(props.modelValue?.endMonth ? String(props.modelValue.endMonth) : "all");
const endYear = ref(props.modelValue?.endYear ? String(props.modelValue.endYear) : "all");

const months = [
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

const days = Array.from({ length: 31 }, (_, i) => String(i + 1));
const currentYear = new Date().getFullYear();
const years = Array.from({ length: 100 }, (_, i) => String(currentYear - i));

const operators = [
    { value: "equal", label: "Égale au" },
    { value: "before", label: "Avant le" },
    { value: "after", label: "Après le" },
    { value: "between", label: "Entre le ... et le ..." },
];

const isFilterActive = computed(() => {
    return Boolean(
        operator.value &&
        (day.value !== "all" || month.value !== "all" || year.value !== "all")
    );
});

const formatSingleDate = (d, m, y) => {
    const monthName = months.find((item) => item.value === m)?.label;
    if (d !== "all" && m !== "all") {
        return `${d} ${monthName}${y !== "all" ? " " + y : ""}`;
    }
    if (d === "all" && m !== "all") {
        return `en ${monthName}${y !== "all" ? " " + y : ""}`;
    }
    if (d !== "all" && m === "all") {
        return `le ${d} (tous mois)${y !== "all" ? " " + y : ""}`;
    }
    if (y !== "all") {
        return `en ${y}`;
    }
    return "";
};

const displayText = computed(() => {
    if (!isFilterActive.value) {
        return props.label;
    }

    const startText = formatSingleDate(day.value, month.value, year.value);

    if (operator.value === "between") {
        const endText = formatSingleDate(endDay.value, endMonth.value, endYear.value);
        return `Anniversaire entre ${startText} et ${endText}`;
    }

    const opLabel = operators.find((o) => o.value === operator.value)?.label.toLowerCase() || "";
    return `Anniversaire ${opLabel} ${startText}`;
});

const handleApply = () => {
    const payload = {
        operator: operator.value,
        day: day.value === "all" ? "" : day.value,
        month: month.value === "all" ? "" : month.value,
        year: year.value === "all" ? "" : year.value,
        endDay: operator.value === "between" && endDay.value !== "all" ? endDay.value : "",
        endMonth: operator.value === "between" && endMonth.value !== "all" ? endMonth.value : "",
        endYear: operator.value === "between" && endYear.value !== "all" ? endYear.value : "",
    };
    emit("update:modelValue", payload);
    emit("apply", payload);
    isOpen.value = false;
};

const handleReset = () => {
    operator.value = "";
    day.value = "all";
    month.value = "all";
    year.value = "all";
    endDay.value = "all";
    endMonth.value = "all";
    endYear.value = "all";
    const payload = { operator: "", day: "", month: "", year: "", endDay: "", endMonth: "", endYear: "" };
    emit("update:modelValue", payload);
    emit("apply", payload);
    isOpen.value = false;
};

watch(
    () => props.modelValue,
    (val) => {
        operator.value = val?.operator || "";
        day.value = val?.day ? String(val.day) : "all";
        month.value = val?.month ? String(val.month) : "all";
        year.value = val?.year ? String(val.year) : "all";
        endDay.value = val?.endDay ? String(val.endDay) : "all";
        endMonth.value = val?.endMonth ? String(val.endMonth) : "all";
        endYear.value = val?.endYear ? String(val.endYear) : "all";
    },
    { deep: true }
);
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                size="sm"
                class="h-9 justify-start text-left font-normal text-xs gap-2 min-w-[180px] cursor-pointer"
                :class="isFilterActive ? 'border-primary text-primary bg-primary/5 font-semibold' : 'text-muted-foreground'"
            >
                <Cake
                    class="h-3.5 w-3.5 shrink-0"
                    :class="isFilterActive ? 'text-primary' : 'text-muted-foreground'"
                />
                <span class="truncate flex-1">{{ displayText }}</span>
                <span
                    v-if="isFilterActive"
                    role="button"
                    class="ml-auto hover:bg-muted p-0.5 rounded-sm cursor-pointer"
                    @click.stop="handleReset"
                >
                    <X class="h-3 w-3 opacity-70 hover:opacity-100" />
                </span>
            </Button>
        </PopoverTrigger>

        <PopoverContent class="w-88 p-4 space-y-3" align="start">
            <div class="space-y-1">
                <h4 class="text-xs font-bold text-foreground">{{ props.label }}</h4>
                <p class="text-[11px] text-muted-foreground">Rechercher par date ou période d'anniversaire</p>
            </div>

            <!-- Opérateur -->
            <div class="space-y-1">
                <Label class="text-[11px] text-muted-foreground">Condition</Label>
                <Select v-model="operator">
                    <SelectTrigger class="h-8 text-xs bg-background cursor-pointer">
                        <SelectValue placeholder="Choisir un opérateur" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="op in operators"
                            :key="op.value"
                            :value="op.value"
                            class="text-xs cursor-pointer"
                        >
                            {{ op.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Date principale (Jour / Mois / Année) -->
            <div v-if="operator" class="space-y-1.5">
                <Label class="text-[11px] text-muted-foreground">
                    {{ operator === 'between' ? 'Date de début' : 'Date d\'anniversaire' }}
                </Label>
                <div class="grid grid-cols-3 gap-1.5">
                    <!-- Jour -->
                    <Select v-model="day">
                        <SelectTrigger class="h-8 text-xs bg-background cursor-pointer">
                            <SelectValue placeholder="Jour" />
                        </SelectTrigger>
                        <SelectContent class="max-h-52">
                            <SelectItem value="all" class="text-xs cursor-pointer">Tous les jours</SelectItem>
                            <SelectItem v-for="d in days" :key="d" :value="d" class="text-xs cursor-pointer">{{ d }}</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Mois -->
                    <Select v-model="month">
                        <SelectTrigger class="h-8 text-xs bg-background cursor-pointer">
                            <SelectValue placeholder="Mois" />
                        </SelectTrigger>
                        <SelectContent class="max-h-52">
                            <SelectItem value="all" class="text-xs cursor-pointer">Tous les mois</SelectItem>
                            <SelectItem v-for="m in months" :key="m.value" :value="m.value" class="text-xs cursor-pointer">{{ m.label }}</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Année -->
                    <Select v-model="year">
                        <SelectTrigger class="h-8 text-xs bg-background cursor-pointer">
                            <SelectValue placeholder="Année" />
                        </SelectTrigger>
                        <SelectContent class="max-h-52">
                            <SelectItem value="all" class="text-xs cursor-pointer">Toutes</SelectItem>
                            <SelectItem v-for="y in years" :key="y" :value="y" class="text-xs cursor-pointer">{{ y }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Date de fin si between -->
            <div v-if="operator === 'between'" class="space-y-1.5 pt-1">
                <Label class="text-[11px] text-muted-foreground">Date de fin</Label>
                <div class="grid grid-cols-3 gap-1.5">
                    <!-- Jour fin -->
                    <Select v-model="endDay">
                        <SelectTrigger class="h-8 text-xs bg-background cursor-pointer">
                            <SelectValue placeholder="Jour" />
                        </SelectTrigger>
                        <SelectContent class="max-h-52">
                            <SelectItem value="all" class="text-xs cursor-pointer">Tous les jours</SelectItem>
                            <SelectItem v-for="d in days" :key="d" :value="d" class="text-xs cursor-pointer">{{ d }}</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Mois fin -->
                    <Select v-model="endMonth">
                        <SelectTrigger class="h-8 text-xs bg-background cursor-pointer">
                            <SelectValue placeholder="Mois" />
                        </SelectTrigger>
                        <SelectContent class="max-h-52">
                            <SelectItem value="all" class="text-xs cursor-pointer">Tous les mois</SelectItem>
                            <SelectItem v-for="m in months" :key="m.value" :value="m.value" class="text-xs cursor-pointer">{{ m.label }}</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Année fin -->
                    <Select v-model="endYear">
                        <SelectTrigger class="h-8 text-xs bg-background cursor-pointer">
                            <SelectValue placeholder="Année" />
                        </SelectTrigger>
                        <SelectContent class="max-h-52">
                            <SelectItem value="all" class="text-xs cursor-pointer">Toutes</SelectItem>
                            <SelectItem v-for="y in years" :key="y" :value="y" class="text-xs cursor-pointer">{{ y }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-2 border-t">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs px-2 cursor-pointer"
                    @click="handleReset"
                >
                    Effacer
                </Button>
                <Button
                    type="button"
                    size="sm"
                    class="h-7 text-xs px-3 font-semibold cursor-pointer"
                    :disabled="!operator || (day === 'all' && month === 'all' && year === 'all')"
                    @click="handleApply"
                >
                    Appliquer
                </Button>
            </div>
        </PopoverContent>
    </Popover>
</template>
