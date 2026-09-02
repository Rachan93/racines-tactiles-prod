<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from "vue";
import axios from "axios";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
import {
    X,
    Mail,
    Phone,
    User as UserIcon,
    Loader2,
    HelpCircle,
} from "lucide-vue-next";

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: "Taper un email (espace/virgule/entrée) ou rechercher...",
    },
});

const emit = defineEmits(["update:modelValue"]);

// Regex d'extraction d'e-mails
const EMAIL_REGEX = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;

const inputRef = ref(null);
const containerRef = ref(null);
const searchQuery = ref("");
const searchResults = ref([]);
const isLoading = ref(false);
const isDropdownOpen = ref(false);
const highlightedIndex = ref(-1);
let searchTimeout = null;

// Filtrer les résultats pour exclure les destinataires déjà sélectionnés
const filteredResults = computed(() => {
    const selectedEmails = new Set(props.modelValue.map((r) => r.email?.toLowerCase()));
    const selectedIds = new Set(props.modelValue.map((r) => r.id).filter(Boolean));

    return searchResults.value.filter(
        (u) => !selectedEmails.has(u.email?.toLowerCase()) && !selectedIds.has(u.id)
    );
});

// Focus sur l'input au clic dans le conteneur
const focusInput = () => {
    inputRef.value?.focus();
};

// 1. TRAITEMENT UNIFIÉ DES E-MAILS (FRAPPE OU COLLAGE)
const processRawEmailString = async (rawString) => {
    if (!rawString) return;
    const matches = rawString.match(EMAIL_REGEX);
    if (!matches || matches.length === 0) return;

    isLoading.value = true;
    const currentEmails = new Set(props.modelValue.map((r) => r.email?.toLowerCase()));
    const cleanMatches = [...new Set(matches.map((m) => m.trim().toLowerCase()))].filter(
        (email) => !currentEmails.has(email)
    );

    if (cleanMatches.length === 0) {
        isLoading.value = false;
        searchQuery.value = "";
        return;
    }

    try {
        const response = await axios.post(route("users.check-recipients"), {
            emails: cleanMatches,
        });
        const foundUsers = response.data || [];
        const foundMap = new Map(foundUsers.map((u) => [u.email.toLowerCase(), u]));

        const newRecipients = cleanMatches.map((email) => {
            if (foundMap.has(email)) {
                const u = foundMap.get(email);
                return {
                    id: u.id,
                    full_name: u.full_name,
                    first_name: u.first_name,
                    last_name: u.last_name,
                    email: u.email,
                    phone_number: u.phone_number,
                    is_custom: false,
                };
            }
            return {
                id: null,
                full_name: email,
                first_name: email.split("@")[0],
                last_name: "",
                email: email,
                phone_number: null,
                is_custom: true,
            };
        });

        emit("update:modelValue", [...props.modelValue, ...newRecipients]);
    } catch (err) {
        console.error("Erreur vérification e-mails :", err);
        const fallbackRecipients = cleanMatches.map((email) => ({
            id: null,
            full_name: email,
            first_name: email.split("@")[0],
            last_name: "",
            email: email,
            phone_number: null,
            is_custom: true,
        }));
        emit("update:modelValue", [...props.modelValue, ...fallbackRecipients]);
    } finally {
        isLoading.value = false;
        searchQuery.value = "";
        searchResults.value = [];
        isDropdownOpen.value = false;
    }
};

// Collage automatique
const handlePaste = (event) => {
    const pastedText = event.clipboardData?.getData("text") || "";
    if (pastedText.match(EMAIL_REGEX)) {
        event.preventDefault();
        processRawEmailString(pastedText);
    }
};

// 2. RECHERCHE EN DIRECT (AUTOCOMPLETE)
const executeSearch = async () => {
    const query = searchQuery.value.trim();
    if (query.length < 2) {
        searchResults.value = [];
        isDropdownOpen.value = false;
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.get(route("users.search-recipients"), {
            params: { query },
        });
        searchResults.value = response.data || [];
        isDropdownOpen.value = searchResults.value.length > 0;
        highlightedIndex.value = searchResults.value.length > 0 ? 0 : -1;
    } catch (error) {
        console.error("Erreur recherche destinataires :", error);
        searchResults.value = [];
    } finally {
        isLoading.value = false;
    }
};

// Détection des séparateurs tapés en direct
watch(searchQuery, (newVal) => {
    if (newVal.includes(",") || newVal.includes(";") || (newVal.includes(" ") && newVal.trim().includes("@"))) {
        processRawEmailString(newVal);
        return;
    }

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(executeSearch, 200);
});

// 3. SÉLECTION D'UN MEMBRE
const selectUser = (user) => {
    const currentEmails = new Set(props.modelValue.map((r) => r.email?.toLowerCase()));
    if (!currentEmails.has(user.email.toLowerCase())) {
        emit("update:modelValue", [
            ...props.modelValue,
            {
                id: user.id,
                full_name: user.full_name || `${user.first_name} ${user.last_name}`,
                first_name: user.first_name,
                last_name: user.last_name,
                email: user.email,
                phone_number: user.phone_number,
                is_custom: false,
            },
        ]);
    }

    searchQuery.value = "";
    searchResults.value = [];
    isDropdownOpen.value = false;
    highlightedIndex.value = -1;
    nextTick(() => focusInput());
};

// 4. SUPPRESSION D'UN DESTINATAIRE
const removeRecipient = (index) => {
    const updated = [...props.modelValue];
    updated.splice(index, 1);
    emit("update:modelValue", updated);
};

// 5. NAVIGATION CLAVIER & ENTRÉE
const handleKeyDown = (event) => {
    if (event.key === "Backspace" && searchQuery.value === "" && props.modelValue.length > 0) {
        removeRecipient(props.modelValue.length - 1);
    } else if (event.key === "Enter" || event.key === "Tab") {
        if (isDropdownOpen.value && highlightedIndex.value >= 0 && filteredResults.value[highlightedIndex.value]) {
            event.preventDefault();
            selectUser(filteredResults.value[highlightedIndex.value]);
        } else if (searchQuery.value.trim().match(EMAIL_REGEX)) {
            event.preventDefault();
            processRawEmailString(searchQuery.value);
        }
    } else if (event.key === "ArrowDown") {
        event.preventDefault();
        if (filteredResults.value.length > 0) {
            highlightedIndex.value = (highlightedIndex.value + 1) % filteredResults.value.length;
        }
    } else if (event.key === "ArrowUp") {
        event.preventDefault();
        if (filteredResults.value.length > 0) {
            highlightedIndex.value =
                (highlightedIndex.value - 1 + filteredResults.value.length) % filteredResults.value.length;
        }
    } else if (event.key === "Escape") {
        isDropdownOpen.value = false;
    }
};

const handleBlur = () => {
    if (searchQuery.value.trim().match(EMAIL_REGEX)) {
        processRawEmailString(searchQuery.value);
    }
};

// Fermer le popover au clic extérieur
const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <!-- Zone de saisie avec tags/badges -->
        <div
            class="min-h-[44px] w-full rounded-lg border bg-background px-2.5 py-1.5 text-xs flex flex-wrap items-center gap-1.5 transition-colors focus-within:ring-1 focus-within:ring-primary focus-within:border-primary cursor-text"
            @click="focusInput"
        >
            <!-- Badges des destinataires avec Tooltips sur fond bleu et texte blanc -->
            <TooltipProvider :delay-duration="150">
                <template v-for="(recipient, idx) in modelValue" :key="recipient.id || recipient.email">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <span
                                class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 rounded-md text-xs font-medium transition-colors select-none"
                                :class="recipient.is_custom
                                    ? 'bg-muted text-muted-foreground border border-border/80'
                                    : 'bg-primary/10 text-primary border border-primary/20'"
                            >
                                <UserIcon v-if="!recipient.is_custom" class="h-3 w-3 shrink-0 opacity-80" />
                                <Mail v-else class="h-3 w-3 shrink-0 opacity-60" />

                                <span class="max-w-[180px] truncate">
                                    {{ recipient.full_name || recipient.email }}
                                </span>

                                <button
                                    type="button"
                                    class="h-4 w-4 rounded-sm flex items-center justify-center hover:bg-destructive/10 hover:text-destructive transition-colors ml-0.5 cursor-pointer"
                                    @click.stop="removeRecipient(idx)"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </span>
                        </TooltipTrigger>

                        <!-- Infobulle fond bleu & texte blanc pur -->
                        <TooltipContent side="top" class="p-3 space-y-1.5 text-xs max-w-xs shadow-xl bg-primary text-white border border-primary/20">
                            <template v-if="!recipient.is_custom">
                                <div class="flex items-center justify-between gap-2 pb-1.5 border-b border-white/20">
                                    <p class="font-bold text-white">{{ recipient.full_name }}</p>
                                    <span class="text-[10px] bg-white/20 text-white px-1.5 py-0.5 rounded font-semibold tracking-wide">
                                        Membre inscrit
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-white/90 pt-0.5">
                                    <Mail class="h-3 w-3 text-white shrink-0" />
                                    <span class="text-white">{{ recipient.email }}</span>
                                </div>
                                <div v-if="recipient.phone_number" class="flex items-center gap-1.5 text-[11px] text-white/90">
                                    <Phone class="h-3 w-3 text-white shrink-0" />
                                    <span class="text-white">{{ recipient.phone_number }}</span>
                                </div>
                            </template>
                            <template v-else>
                                <div class="space-y-1">
                                    <p class="font-bold text-white flex items-center gap-1.5">
                                        <HelpCircle class="h-3.5 w-3.5 text-white shrink-0" />
                                        <span>Destinataire externe / Inconnu</span>
                                    </p>
                                    <p class="text-[11px] text-white/90 font-mono">
                                        {{ recipient.email }}
                                    </p>
                                    <p class="text-[10px] text-white/80 italic">
                                        Cet e-mail n'est pas associé à un compte membre existant.
                                    </p>
                                </div>
                            </template>
                        </TooltipContent>
                    </Tooltip>
                </template>
            </TooltipProvider>

            <!-- Champ texte de saisie -->
            <div class="flex-1 min-w-[170px] flex items-center gap-1">
                <input
                    ref="inputRef"
                    v-model="searchQuery"
                    type="text"
                    :placeholder="modelValue.length === 0 ? props.placeholder : 'Ajouter un autre destinataire...'"
                    class="w-full bg-transparent border-0 p-0 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-0"
                    @paste="handlePaste"
                    @keydown="handleKeyDown"
                    @blur="handleBlur"
                    @focus="isDropdownOpen = searchResults.length > 0"
                />

                <Loader2 v-if="isLoading" class="h-3.5 w-3.5 animate-spin text-muted-foreground shrink-0" />
            </div>
        </div>

        <!-- Menu déroulant d'autocomplétion -->
        <div
            v-if="isDropdownOpen && filteredResults.length > 0"
            class="absolute left-0 right-0 top-full mt-1.5 z-50 rounded-lg border bg-popover text-popover-foreground shadow-lg overflow-hidden max-h-60 overflow-y-auto p-1 space-y-0.5 animate-in fade-in-50 zoom-in-95 duration-100"
        >
            <div
                v-for="(user, idx) in filteredResults"
                :key="user.id"
                class="flex items-center justify-between p-2 rounded-md cursor-pointer text-xs transition-colors"
                :class="idx === highlightedIndex ? 'bg-accent text-accent-foreground font-medium' : 'hover:bg-muted/50'"
                @click="selectUser(user)"
                @mouseenter="highlightedIndex = idx"
            >
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="h-7 w-7 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-[11px] shrink-0">
                        {{ (user.first_name?.[0] || '').toUpperCase() }}{{ (user.last_name?.[0] || '').toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-medium text-foreground truncate">{{ user.full_name }}</p>
                        <p class="text-[11px] text-muted-foreground truncate">{{ user.email }}</p>
                    </div>
                </div>

                <span v-if="user.phone_number" class="text-[10px] text-muted-foreground shrink-0 pl-2">
                    {{ user.phone_number }}
                </span>
            </div>
        </div>
    </div>
</template>
