<script setup>
import { ref, computed, watch } from "vue";
import { formatDate } from "@/Utils/formatters";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
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
import { Calendar as CalendarIcon, X } from "lucide-vue-next";

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
    prefixText: {
        type: String,
        default: "Inscrit",
    },
});

const emit = defineEmits(["update:modelValue", "apply"]);

const isOpen = ref(false);
const operator = ref(props.modelValue?.operator || "");
const date = ref(props.modelValue?.date || "");
const dateEnd = ref(props.modelValue?.dateEnd || "");

const operators = [
    { value: "equal", label: "Égale au" },
    { value: "before", label: "Avant le" },
    { value: "after", label: "Après le" },
    { value: "before_equal", label: "Avant ou égale au" },
    { value: "after_equal", label: "Après ou égale au" },
    { value: "between", label: "Entre le ... et le ..." },
];

const operatorLabel = computed(() => {
    const found = operators.find((o) => o.value === operator.value);
    return found ? found.label.toLowerCase() : "";
});

const isFilterActive = computed(() => {
    return Boolean(operator.value && date.value);
});

const displayText = computed(() => {
    if (!operator.value || !date.value) {
        return props.label;
    }

    const formattedStart = formatDate(date.value);
    if (operator.value === "between" && dateEnd.value) {
        return `${props.prefixText} entre ${formattedStart} et ${formatDate(dateEnd.value)}`;
    }

    return `${props.prefixText} ${operatorLabel.value} ${formattedStart}`;
});

const handleApply = () => {
    const payload = {
        operator: operator.value,
        date: date.value,
        dateEnd: operator.value === "between" ? dateEnd.value : "",
    };
    emit("update:modelValue", payload);
    emit("apply", payload);
    isOpen.value = false;
};

const handleReset = () => {
    operator.value = "";
    date.value = "";
    dateEnd.value = "";
    const payload = { operator: "", date: "", dateEnd: "" };
    emit("update:modelValue", payload);
    emit("apply", payload);
    isOpen.value = false;
};

watch(
    () => props.modelValue,
    (val) => {
        operator.value = val?.operator || "";
        date.value = val?.date || "";
        dateEnd.value = val?.dateEnd || "";
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
                class="h-9 justify-start text-left font-normal text-xs gap-2 min-w-[170px] cursor-pointer"
                :class="isFilterActive ? 'border-primary text-primary bg-primary/5 font-semibold' : 'text-muted-foreground'"
            >
                <CalendarIcon
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

        <PopoverContent class="w-80 p-4 space-y-3" align="start">
            <div class="space-y-1">
                <h4 class="text-xs font-bold text-foreground">{{ props.label }}</h4>
                <p class="text-[11px] text-muted-foreground">Filtrer par date ou période</p>
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

            <!-- Date de début -->
            <div v-if="operator" class="space-y-1">
                <Label class="text-[11px] text-muted-foreground">
                    {{ operator === 'between' ? 'Date de début' : 'Date' }}
                </Label>
                <Input
                    type="date"
                    v-model="date"
                    class="h-8 text-xs bg-background cursor-pointer"
                />
            </div>

            <!-- Date de fin (entre le) -->
            <div v-if="operator === 'between'" class="space-y-1">
                <Label class="text-[11px] text-muted-foreground">Date de fin</Label>
                <Input
                    type="date"
                    v-model="dateEnd"
                    class="h-8 text-xs bg-background cursor-pointer"
                />
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
                    :disabled="!operator || !date || (operator === 'between' && !dateEnd)"
                    @click="handleApply"
                >
                    Appliquer
                </Button>
            </div>
        </PopoverContent>
    </Popover>
</template>
