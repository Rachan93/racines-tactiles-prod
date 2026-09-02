<script setup>
//les monolithes contre-attaquent, bon je factoriserai plus tard...
import { computed, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";

import AdminLayout from "@/Layouts/AdminLayout.vue";

import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { ScrollArea } from "@/Components/ui/scroll-area";

import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/Components/ui/accordion";

import {
    ArrowLeft,
    Mail,
    Phone,
    Cake,
    Calendar,
    BookOpen,
    Users,
    User as UserIcon,
    Clock,
    Shell,
    Hand,
    CheckCircle2,
    AlertCircle,
    RotateCcw,
    Copy,
    Check,
} from "lucide-vue-next";

import {
    pluralize,
    formatPrice,
} from "@/Utils/formatters";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },

    stats: {
        type: Object,
        required: true,
    },

    attendees: {
        type: Array,
        default: () => [],
    },

    modules: {
        type: Array,
        default: () => [],
    },
});

/*
|--------------------------------------------------------------------------
| Avatar initiales
|--------------------------------------------------------------------------
*/

const initials = computed(() => {
    const first = props.user.first_name?.charAt(0) || "";
    const last = props.user.last_name?.charAt(0) || "";

    return `${first}${last}`.toUpperCase();
});

/*
|--------------------------------------------------------------------------
| Facturation
|--------------------------------------------------------------------------
*/

const hasBillingInformation = computed(() => {
    return Boolean(
        props.user.billing ||
            props.user.company_name ||
            props.user.company_address ||
            props.user.company_locality ||
            props.user.company_postal_code ||
            props.user.vat_number,
    );
});

/*
|--------------------------------------------------------------------------
| Copie rapide
|--------------------------------------------------------------------------
*/

const copiedField = ref(null);

const copyValue = async (value, field) => {
    if (!value) return;

    try {
        await navigator.clipboard.writeText(value);

        copiedField.value = field;

        window.setTimeout(() => {
            if (copiedField.value === field) {
                copiedField.value = null;
            }
        }, 1500);
    } catch (error) {
        console.error("Impossible de copier :", error);
    }
};

/*
|--------------------------------------------------------------------------
| Helpers modules
|--------------------------------------------------------------------------
*/

const getProgress = (module) => {
    if (!module.total_lessons) return 0;

    return Math.min(
        100,
        Math.round(
            (module.completed_lessons / module.total_lessons) *
                100,
        ),
    );
};

const getTypeBadgeClass = (typeName) => {
    const name = (typeName || "").toLowerCase();

    if (name.includes("collectif")) {
        return "bg-emerald-50 text-emerald-800 border-emerald-200";
    }

    if (name.includes("stage")) {
        return "bg-amber-50 text-amber-800 border-amber-200";
    }

    if (name.includes("privé") || name.includes("prive")) {
        return "bg-indigo-50 text-indigo-800 border-indigo-200";
    }

    return "bg-secondary text-secondary-foreground";
};

const getStatusLabel = (enrollment) => {
    if (enrollment.lesson?.is_cancelled) {
        return "Séance annulée";
    }

    if (enrollment.status === "cancelled") {
        return "Inscription annulée";
    }

    if (enrollment.status === "absent") {
        return enrollment.is_past
            ? "Absent"
            : "Absence signalée";
    }

    if (enrollment.is_past) {
        return "Passée";
    }

    return "Inscrit";
};

const getStatusClass = (enrollment) => {
    if (
        enrollment.lesson?.is_cancelled ||
        enrollment.status === "cancelled"
    ) {
        return "bg-muted text-muted-foreground border-border";
    }

    if (enrollment.status === "absent") {
        return "bg-amber-50 text-amber-800 border-amber-300";
    }

    if (enrollment.is_past) {
        return "bg-muted text-muted-foreground border-border";
    }

    return "bg-emerald-50 text-emerald-700 border-emerald-200";
};
</script>

<template>
    <AdminLayout :title="`Fiche membre — ${user.full_name}`">
        <Head :title="user.full_name" />

        <div class="space-y-6">
            <!-- ===================================================== -->
            <!-- BREADCRUMB                                           -->
            <!-- ===================================================== -->

            <nav
                class="flex items-center gap-1.5 text-xs text-muted-foreground"
            >
                <Link
                    :href="route('dashboard.index')"
                    class="hover:text-foreground transition-colors"
                >
                    Tableau de bord
                </Link>

                <span>/</span>

                <Link
                    :href="route('users.index')"
                    class="hover:text-foreground transition-colors"
                >
                    Répertoire des membres
                </Link>

                <span>/</span>

                <span class="font-semibold text-foreground">
                    {{ user.full_name }}
                </span>
            </nav>

            <!-- ===================================================== -->
            <!-- HEADER MEMBRE                                        -->
            <!-- ===================================================== -->

            <section
                class="rounded-xl border bg-card shadow-xs overflow-hidden"
            >
                <div
                    class="p-5 sm:p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-5"
                >
                    <div
                        class="flex items-start sm:items-center gap-4 min-w-0"
                    >
                        <!-- Avatar -->

                        <div
                            class="h-14 w-14 sm:h-16 sm:w-16 rounded-full bg-primary/10 border border-primary/20 text-primary flex items-center justify-center text-lg font-bold shrink-0"
                        >
                            {{ initials }}
                        </div>

                        <div class="min-w-0">
                            <div
                                class="flex items-center gap-2 flex-wrap"
                            >
                                <h1
                                    class="text-2xl sm:text-3xl font-bold tracking-tight text-foreground"
                                >
                                    {{ user.full_name }}
                                </h1>

                                <Badge
                                    variant="outline"
                                    class="text-xs"
                                    :class="
                                        user.email_verified
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                            : 'bg-amber-50 text-amber-800 border-amber-200'
                                    "
                                >
                                    <CheckCircle2
                                        v-if="user.email_verified"
                                        class="h-3 w-3 mr-1"
                                    />

                                    <AlertCircle
                                        v-else
                                        class="h-3 w-3 mr-1"
                                    />

                                    {{
                                        user.email_verified
                                            ? "E-mail vérifié"
                                            : "E-mail non vérifié"
                                    }}
                                </Badge>
                            </div>

                            <p
                                class="text-sm text-muted-foreground mt-1"
                            >
                                Membre depuis
                                {{ user.created_at || "—" }}
                            </p>

                            <div
                                class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 text-sm"
                            >
                                <a
                                    v-if="user.email"
                                    :href="`mailto:${user.email}`"
                                    class="inline-flex items-center gap-1.5 text-muted-foreground hover:text-foreground transition-colors"
                                >
                                    <Mail class="h-4 w-4" />
                                    {{ user.email }}
                                </a>

                                <a
                                    v-if="user.phone_number"
                                    :href="`tel:${user.phone_number}`"
                                    class="inline-flex items-center gap-1.5 text-muted-foreground hover:text-foreground transition-colors"
                                >
                                    <Phone class="h-4 w-4" />
                                    {{ user.phone_number }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <Button
                        variant="outline"
                        size="sm"
                        as-child
                        class="group shrink-0 self-start lg:self-auto"
                    >
                        <Link :href="route('users.index')">
                            <ArrowLeft
                                class="h-4 w-4 mr-1.5 transition-transform group-hover:-translate-x-0.5"
                            />

                            Retour au répertoire
                        </Link>
                    </Button>
                </div>
            </section>

            <!-- ===================================================== -->
            <!-- KPIs                                                  -->
            <!-- ===================================================== -->

            <section
                class="grid grid-cols-2 lg:grid-cols-4 gap-3"
            >
                <div
                    class="p-4 rounded-xl border bg-card shadow-xs"
                >
                    <div
                        class="flex items-center justify-between gap-2"
                    >
                        <span
                            class="text-xs font-medium text-muted-foreground"
                        >
                            Modules
                        </span>

                        <BookOpen
                            class="h-4 w-4 text-muted-foreground"
                        />
                    </div>

                    <p
                        class="text-2xl font-bold tracking-tight mt-2"
                    >
                        {{ stats.modules_count }}
                    </p>

                    <p
                        class="text-[11px] text-muted-foreground mt-1"
                    >
                        {{ stats.active_modules_count }} actif{{
                            stats.active_modules_count > 1 ? "s" : ""
                        }}
                    </p>
                </div>

                <div
                    class="p-4 rounded-xl border bg-card shadow-xs"
                >
                    <div
                        class="flex items-center justify-between gap-2"
                    >
                        <span
                            class="text-xs font-medium text-muted-foreground"
                        >
                            Séances à venir
                        </span>

                        <Calendar
                            class="h-4 w-4 text-muted-foreground"
                        />
                    </div>

                    <p
                        class="text-2xl font-bold tracking-tight mt-2"
                    >
                        {{ stats.upcoming_enrollments_count }}
                    </p>

                    <p
                        class="text-[11px] text-muted-foreground mt-1"
                    >
                        Toutes personnes du compte
                    </p>
                </div>

                <div
                    class="p-4 rounded-xl border bg-card shadow-xs"
                >
                    <div
                        class="flex items-center justify-between gap-2"
                    >
                        <span
                            class="text-xs font-medium text-muted-foreground"
                        >
                            Invités
                        </span>

                        <Users
                            class="h-4 w-4 text-muted-foreground"
                        />
                    </div>

                    <p
                        class="text-2xl font-bold tracking-tight mt-2"
                    >
                        {{ stats.attendees_count }}
                    </p>

                    <p
                        class="text-[11px] text-muted-foreground mt-1"
                    >
                        Rattaché{{
                            stats.attendees_count > 1 ? "s" : ""
                        }}
                        au compte
                    </p>
                </div>

                <div
                    class="p-4 rounded-xl border bg-card shadow-xs"
                >
                    <div
                        class="flex items-center justify-between gap-2"
                    >
                        <span
                            class="text-xs font-medium text-muted-foreground"
                        >
                            Statut
                        </span>

                        <UserIcon
                            class="h-4 w-4 text-muted-foreground"
                        />
                    </div>

                    <p
                        class="text-sm font-bold tracking-tight mt-2"
                    >
                        Compte membre
                    </p>

                    <p
                        class="text-[11px] text-muted-foreground mt-1"
                    >
                        ID #{{ user.id }}
                    </p>
                </div>
            </section>

            <!-- ===================================================== -->
            <!-- INFORMATIONS DU MEMBRE                               -->
            <!-- ===================================================== -->

            <div
                class="grid grid-cols-1 xl:grid-cols-3 gap-4"
            >
                <!-- Coordonnées -->

                <section
                    class="xl:col-span-2 rounded-xl border bg-card shadow-xs"
                >
                    <div class="px-5 py-4 border-b">
                        <h2 class="font-bold text-sm text-foreground">
                            Informations du membre
                        </h2>

                        <p
                            class="text-xs text-muted-foreground mt-0.5"
                        >
                            Coordonnées et informations personnelles.
                        </p>
                    </div>

                    <div
                        class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 text-sm"
                    >
                        <!-- Email -->

                        <div class="space-y-1">
                            <p
                                class="text-[11px] uppercase tracking-wider font-semibold text-muted-foreground"
                            >
                                E-mail
                            </p>

                            <div
                                class="flex items-center gap-2 min-w-0"
                            >
                                <Mail
                                    class="h-4 w-4 text-muted-foreground shrink-0"
                                />

                                <span class="truncate font-medium">
                                    {{ user.email || "—" }}
                                </span>

                                <button
                                    v-if="user.email"
                                    type="button"
                                    class="text-muted-foreground hover:text-primary transition-colors"
                                    @click="
                                        copyValue(
                                            user.email,
                                            'email',
                                        )
                                    "
                                >
                                    <Check
                                        v-if="
                                            copiedField === 'email'
                                        "
                                        class="h-3.5 w-3.5 text-emerald-600"
                                    />

                                    <Copy
                                        v-else
                                        class="h-3.5 w-3.5"
                                    />
                                </button>
                            </div>
                        </div>

                        <!-- Téléphone -->

                        <div class="space-y-1">
                            <p
                                class="text-[11px] uppercase tracking-wider font-semibold text-muted-foreground"
                            >
                                Téléphone
                            </p>

                            <div
                                class="flex items-center gap-2"
                            >
                                <Phone
                                    class="h-4 w-4 text-muted-foreground shrink-0"
                                />

                                <span class="font-medium">
                                    {{
                                        user.phone_number ||
                                        "Non renseigné"
                                    }}
                                </span>

                                <button
                                    v-if="user.phone_number"
                                    type="button"
                                    class="text-muted-foreground hover:text-primary transition-colors"
                                    @click="
                                        copyValue(
                                            user.phone_number,
                                            'phone',
                                        )
                                    "
                                >
                                    <Check
                                        v-if="
                                            copiedField === 'phone'
                                        "
                                        class="h-3.5 w-3.5 text-emerald-600"
                                    />

                                    <Copy
                                        v-else
                                        class="h-3.5 w-3.5"
                                    />
                                </button>
                            </div>
                        </div>

                        <!-- Naissance -->

                        <div class="space-y-1">
                            <p
                                class="text-[11px] uppercase tracking-wider font-semibold text-muted-foreground"
                            >
                                Date de naissance
                            </p>

                            <div
                                class="flex items-center gap-2"
                            >
                                <Cake
                                    class="h-4 w-4 text-muted-foreground"
                                />

                                <span class="font-medium">
                                    {{
                                        user.birthday ||
                                        "Non renseignée"
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Inscription -->

                        <div class="space-y-1">
                            <p
                                class="text-[11px] uppercase tracking-wider font-semibold text-muted-foreground"
                            >
                                Inscription
                            </p>

                            <div
                                class="flex items-center gap-2"
                            >
                                <Calendar
                                    class="h-4 w-4 text-muted-foreground"
                                />

                                <span class="font-medium">
                                    {{ user.created_at || "—" }}
                                </span>
                            </div>
                        </div>

                        <!-- Adresse -->

                        <div
                            class="space-y-1 sm:col-span-2"
                        >
                            <p
                                class="text-[11px] uppercase tracking-wider font-semibold text-muted-foreground"
                            >
                                Adresse
                            </p>

                            <div
                                class="p-3 rounded-lg bg-muted/40 border text-sm"
                            >
                                <template v-if="user.address">
                                    <p class="font-medium">
                                        {{ user.address }}
                                    </p>

                                    <p
                                        class="text-muted-foreground mt-0.5"
                                    >
                                        {{ user.postal_code }}
                                        {{ user.locality }}
                                    </p>
                                </template>

                                <span
                                    v-else
                                    class="text-muted-foreground"
                                >
                                    Aucune adresse renseignée.
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Facturation -->

                <section
                    class="rounded-xl border bg-card shadow-xs"
                >
                    <div class="px-5 py-4 border-b">
                        <h2 class="font-bold text-sm">
                            Facturation
                        </h2>

                        <p
                            class="text-xs text-muted-foreground mt-0.5"
                        >
                            Coordonnées professionnelles.
                        </p>
                    </div>

                    <div
                        v-if="hasBillingInformation"
                        class="p-5 space-y-4 text-sm"
                    >
                        <div>
                            <p
                                class="text-[11px] uppercase tracking-wider font-semibold text-muted-foreground"
                            >
                                Société
                            </p>

                            <p class="font-semibold mt-1">
                                {{
                                    user.company_name ||
                                    "Non renseignée"
                                }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-[11px] uppercase tracking-wider font-semibold text-muted-foreground"
                            >
                                Adresse
                            </p>

                            <p class="font-medium mt-1">
                                {{
                                    user.company_address ||
                                    "Non renseignée"
                                }}
                            </p>

                            <p
                                v-if="
                                    user.company_postal_code ||
                                    user.company_locality
                                "
                                class="text-muted-foreground"
                            >
                                {{ user.company_postal_code }}
                                {{ user.company_locality }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-[11px] uppercase tracking-wider font-semibold text-muted-foreground"
                            >
                                N° TVA
                            </p>

                            <div
                                class="flex items-center gap-2 mt-1"
                            >
                                <span class="font-medium">
                                    {{
                                        user.vat_number ||
                                        "Non renseigné"
                                    }}
                                </span>

                                <button
                                    v-if="user.vat_number"
                                    type="button"
                                    class="text-muted-foreground hover:text-primary"
                                    @click="
                                        copyValue(
                                            user.vat_number,
                                            'vat',
                                        )
                                    "
                                >
                                    <Check
                                        v-if="
                                            copiedField === 'vat'
                                        "
                                        class="h-3.5 w-3.5 text-emerald-600"
                                    />

                                    <Copy
                                        v-else
                                        class="h-3.5 w-3.5"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="p-8 text-center"
                    >
                        <p
                            class="text-xs text-muted-foreground"
                        >
                            Aucune information de facturation
                            professionnelle.
                        </p>
                    </div>
                </section>
            </div>

            <!-- ===================================================== -->
            <!-- INVITÉS                                               -->
            <!-- ===================================================== -->

            <section
                class="rounded-xl border bg-card shadow-xs overflow-hidden"
            >
                <div
                    class="px-5 py-4 border-b flex items-center justify-between gap-3"
                >
                    <div>
                        <h2
                            class="font-bold text-sm flex items-center gap-2"
                        >
                            <Users class="h-4 w-4" />

                            Invités rattachés
                        </h2>

                        <p
                            class="text-xs text-muted-foreground mt-0.5"
                        >
                            {{
                                pluralize(
                                    attendees.length,
                                    "personne rattachée",
                                )
                            }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="attendees.length > 0"
                    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 p-5"
                >
                    <div
                        v-for="attendee in attendees"
                        :key="attendee.id"
                        class="p-4 rounded-xl border bg-background hover:bg-muted/20 transition-colors"
                    >
                        <div
                            class="flex items-start gap-3"
                        >
                            <div
                                class="h-9 w-9 rounded-full bg-muted border flex items-center justify-center shrink-0"
                            >
                                <Users
                                    class="h-4 w-4 text-muted-foreground"
                                />
                            </div>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="font-semibold text-sm truncate"
                                >
                                    {{ attendee.full_name }}
                                </p>

                                <p
                                    class="text-xs text-muted-foreground mt-0.5"
                                >
                                    Invité #{{ attendee.id }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-4 pt-3 border-t space-y-2 text-xs"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-muted-foreground inline-flex items-center gap-1.5"
                                >
                                    <Cake class="h-3.5 w-3.5" />
                                    Naissance
                                </span>

                                <span class="font-medium">
                                    {{
                                        attendee.birthday ||
                                        "Non renseignée"
                                    }}
                                </span>
                            </div>

                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-muted-foreground inline-flex items-center gap-1.5"
                                >
                                    <BookOpen
                                        class="h-3.5 w-3.5"
                                    />
                                    Modules
                                </span>

                                <span class="font-semibold">
                                    {{ attendee.modules_count }}
                                </span>
                            </div>

                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-muted-foreground"
                                >
                                    Modules actifs
                                </span>

                                <Badge
                                    variant="secondary"
                                    class="text-[10px]"
                                >
                                    {{
                                        attendee.active_modules_count
                                    }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="py-10 text-center"
                >
                    <Users
                        class="h-8 w-8 text-muted-foreground/30 mx-auto"
                    />

                    <p
                        class="text-sm font-medium mt-2"
                    >
                        Aucun invité
                    </p>

                    <p
                        class="text-xs text-muted-foreground mt-1"
                    >
                        Aucun invité n'est rattaché à ce compte.
                    </p>
                </div>
            </section>

            <!-- ===================================================== -->
            <!-- MODULES                                               -->
            <!-- ===================================================== -->

            <section class="space-y-4">
                <div>
                    <h2
                        class="text-xl font-bold tracking-tight flex items-center gap-2"
                    >
                        <BookOpen class="h-5 w-5" />

                        Modules du compte
                    </h2>

                    <p
                        class="text-sm text-muted-foreground mt-1"
                    >
                        Modules du titulaire et de ses invités,
                        avec le détail de chaque séance.
                    </p>
                </div>

                <Accordion
                    v-if="modules.length > 0"
                    type="multiple"
                    class="space-y-3"
                >
                    <AccordionItem
                        v-for="module in modules"
                        :key="module.id"
                        :value="String(module.id)"
                        class="border rounded-xl bg-card shadow-xs overflow-hidden"
                    >
                        <!-- ========================================= -->
                        <!-- HEADER MODULE                             -->
                        <!-- ========================================= -->

                        <AccordionTrigger
                            class="px-5 py-4 hover:bg-muted/30 hover:no-underline"
                        >
                            <div
                                class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 w-full pr-4 text-left"
                            >
                                <div class="min-w-0 space-y-2">
                                    <div
                                        class="flex items-center flex-wrap gap-2"
                                    >
                                        <span
                                            class="font-bold text-base"
                                        >
                                            Module #{{ module.id }}
                                        </span>

                                        <Badge
                                            variant="outline"
                                            :class="
                                                getTypeBadgeClass(
                                                    module.type?.name,
                                                )
                                            "
                                        >
                                            {{ module.type?.name }}
                                        </Badge>

                                        <Badge
                                            variant="outline"
                                            :class="
                                                module.is_active
                                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                    : 'bg-muted text-muted-foreground border-border'
                                            "
                                        >
                                            {{
                                                module.is_active
                                                    ? "Actif"
                                                    : "Inactif"
                                            }}
                                        </Badge>

                                        <Badge
                                            variant="secondary"
                                            class="gap-1"
                                        >
                                            <UserIcon
                                                v-if="
                                                    module.participant
                                                        .type ===
                                                    'user'
                                                "
                                                class="h-3 w-3"
                                            />

                                            <Users
                                                v-else
                                                class="h-3 w-3"
                                            />

                                            {{
                                                module.participant
                                                    .name
                                            }}
                                        </Badge>
                                    </div>

                                    <div
                                        class="flex items-center flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground"
                                    >
                                        <span>
                                            {{
                                                pluralize(
                                                    module.total_lessons,
                                                    "séance",
                                                )
                                            }}
                                        </span>

                                        <span>•</span>

                                        <span>
                                            Acheté le
                                            {{ module.purchase_date }}
                                        </span>

                                        <template
                                            v-if="
                                                module.expiration_date
                                            "
                                        >
                                            <span>•</span>

                                            <span>
                                                Expire le
                                                {{
                                                    module.expiration_date
                                                }}
                                            </span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Progression mini -->

                                <div
                                    class="w-full lg:w-48 shrink-0 space-y-1.5"
                                >
                                    <div
                                        class="flex items-center justify-between text-[11px]"
                                    >
                                        <span
                                            class="text-muted-foreground"
                                        >
                                            Progression
                                        </span>

                                        <span class="font-bold">
                                            {{
                                                getProgress(
                                                    module,
                                                )
                                            }}%
                                        </span>
                                    </div>

                                    <div
                                        class="h-1.5 rounded-full bg-muted overflow-hidden"
                                    >
                                        <div
                                            class="h-full rounded-full bg-primary transition-all"
                                            :style="{
                                                width: `${getProgress(
                                                    module,
                                                )}%`,
                                            }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </AccordionTrigger>

                        <!-- ========================================= -->
                        <!-- CONTENU MODULE                            -->
                        <!-- ========================================= -->

                        <AccordionContent
                            class="border-t bg-muted/10"
                        >
                            <div class="p-5 space-y-5">
                                <!-- Infos module -->

                                <div
                                    class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2.5"
                                >
                                    <div
                                        class="p-3 rounded-lg bg-background border"
                                    >
                                        <p
                                            class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground"
                                        >
                                            Tarif payé
                                        </p>

                                        <p
                                            class="font-bold text-sm mt-1"
                                        >
                                            {{
                                                formatPrice(
                                                    module.paid_price,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        class="p-3 rounded-lg bg-background border"
                                    >
                                        <p
                                            class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground"
                                        >
                                            Progression
                                        </p>

                                        <p
                                            class="font-bold text-sm mt-1"
                                        >
                                            {{
                                                module.completed_lessons
                                            }}
                                            /
                                            {{ module.total_lessons }}
                                        </p>
                                    </div>

                                    <div
                                        class="p-3 rounded-lg bg-background border"
                                    >
                                        <p
                                            class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground"
                                        >
                                            À venir
                                        </p>

                                        <p
                                            class="font-bold text-sm mt-1"
                                        >
                                            {{
                                                module.upcoming_lessons
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        class="p-3 rounded-lg bg-background border"
                                    >
                                        <p
                                            class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground"
                                        >
                                            Rattrapages
                                        </p>

                                        <p
                                            class="font-bold text-sm mt-1"
                                        >
                                            {{
                                                module.makeups_used_count
                                            }}
                                            /
                                            {{
                                                module.max_makeups_allowed
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        class="p-3 rounded-lg bg-background border"
                                    >
                                        <p
                                            class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground"
                                        >
                                            Quota restant
                                        </p>

                                        <p
                                            class="font-bold text-sm mt-1"
                                        >
                                            {{
                                                module.remaining_makeups
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        class="p-3 rounded-lg bg-background border"
                                    >
                                        <p
                                            class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground"
                                        >
                                            Absences
                                        </p>

                                        <p
                                            class="font-bold text-sm mt-1"
                                        >
                                            {{
                                                module.absences_count
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Séances -->

                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <h3
                                            class="text-xs uppercase tracking-wider font-bold text-muted-foreground"
                                        >
                                            Séances du module
                                        </h3>

                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                module.enrollments
                                                    .length
                                            }}
                                            inscription{{
                                                module.enrollments
                                                    .length > 1
                                                    ? "s"
                                                    : ""
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        class="border rounded-lg bg-background overflow-hidden"
                                    >
                                        <ScrollArea
                                            class="h-80 w-full"
                                        >
                                            <table
                                                class="w-full text-xs text-left border-collapse"
                                            >
                                                <thead
                                                    class="sticky top-0 z-10 bg-muted/90 border-b"
                                                >
                                                    <tr>
                                                        <th
                                                            class="px-3 py-2.5 font-semibold w-16"
                                                        >
                                                            #
                                                        </th>

                                                        <th
                                                            class="px-3 py-2.5 font-semibold"
                                                        >
                                                            Séance
                                                        </th>

                                                        <th
                                                            class="px-3 py-2.5 font-semibold"
                                                        >
                                                            Horaire
                                                        </th>

                                                        <th
                                                            class="px-3 py-2.5 font-semibold"
                                                        >
                                                            Inscription
                                                        </th>

                                                        <th
                                                            class="px-3 py-2.5 font-semibold"
                                                        >
                                                            Poste
                                                        </th>

                                                        <th
                                                            class="px-3 py-2.5 font-semibold"
                                                        >
                                                            Statut
                                                        </th>
                                                    </tr>
                                                </thead>

                                                <tbody
                                                    class="divide-y divide-border"
                                                >
                                                    <tr
                                                        v-for="
                                                            enrollment in module.enrollments
                                                        "
                                                        :key="
                                                            enrollment.id
                                                        "
                                                        class="hover:bg-muted/30 transition-colors"
                                                        :class="{
                                                            'bg-amber-500/5':
                                                                enrollment.status ===
                                                                'absent',
                                                            'opacity-60':
                                                                enrollment.lesson
                                                                    ?.is_cancelled,
                                                        }"
                                                    >
                                                        <!-- Numéro -->

                                                        <td
                                                            class="px-3 py-3"
                                                        >
                                                            <span
                                                                v-if="
                                                                    enrollment.enrollment_type ===
                                                                    'regular'
                                                                "
                                                                class="font-bold text-muted-foreground"
                                                            >
                                                                #{{
                                                                    String(
                                                                        enrollment.sequence_number,
                                                                    ).padStart(
                                                                        2,
                                                                        "0",
                                                                    )
                                                                }}
                                                            </span>

                                                            <span
                                                                v-else
                                                                class="h-6 w-6 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center"
                                                            >
                                                                <RotateCcw
                                                                    class="h-3 w-3"
                                                                />
                                                            </span>
                                                        </td>

                                                        <!-- Cours -->

                                                        <td
                                                            class="px-3 py-3 min-w-48"
                                                        >
                                                            <p
                                                                class="font-semibold text-foreground"
                                                            >
                                                                {{
                                                                    enrollment
                                                                        .lesson
                                                                        .course_name
                                                                }}
                                                            </p>

                                                            <p
                                                                class="text-[11px] text-muted-foreground mt-0.5"
                                                            >
                                                                {{
                                                                    enrollment
                                                                        .lesson
                                                                        .date_formatted
                                                                }}
                                                            </p>

                                                            <div
                                                                v-if="
                                                                    enrollment.replaces
                                                                "
                                                                class="mt-1.5 text-[10px] text-primary inline-flex items-center gap-1"
                                                            >
                                                                <RotateCcw
                                                                    class="h-3 w-3"
                                                                />

                                                                Rattrape
                                                                {{
                                                                    enrollment
                                                                        .replaces
                                                                        .course_name
                                                                }}

                                                                <template
                                                                    v-if="
                                                                        enrollment
                                                                            .replaces
                                                                            .date
                                                                    "
                                                                >
                                                                    du
                                                                    {{
                                                                        enrollment
                                                                            .replaces
                                                                            .date
                                                                    }}
                                                                </template>
                                                            </div>
                                                        </td>

                                                        <!-- Horaire -->

                                                        <td
                                                            class="px-3 py-3 whitespace-nowrap"
                                                        >
                                                            <span
                                                                class="inline-flex items-center gap-1 text-muted-foreground"
                                                            >
                                                                <Clock
                                                                    class="h-3 w-3"
                                                                />

                                                                {{
                                                                    enrollment
                                                                        .lesson
                                                                        .start_time
                                                                }}
                                                                -
                                                                {{
                                                                    enrollment
                                                                        .lesson
                                                                        .end_time
                                                                }}
                                                            </span>
                                                        </td>

                                                        <!-- Type -->

                                                        <td
                                                            class="px-3 py-3"
                                                        >
                                                            <Badge
                                                                variant="outline"
                                                                class="text-[10px]"
                                                                :class="
                                                                    enrollment.enrollment_type ===
                                                                    'makeup'
                                                                        ? 'bg-primary/5 text-primary border-primary/20'
                                                                        : ''
                                                                "
                                                            >
                                                                <RotateCcw
                                                                    v-if="
                                                                        enrollment.enrollment_type ===
                                                                        'makeup'
                                                                    "
                                                                    class="h-3 w-3 mr-1"
                                                                />

                                                                {{
                                                                    enrollment.enrollment_type ===
                                                                    "makeup"
                                                                        ? "Rattrapage"
                                                                        : "Régulier"
                                                                }}
                                                            </Badge>
                                                        </td>

                                                        <!-- Poste -->

                                                        <td
                                                            class="px-3 py-3"
                                                        >
                                                            <span
                                                                class="inline-flex items-center gap-1.5 font-medium"
                                                            >
                                                                <Shell
                                                                    v-if="
                                                                        enrollment.spot_type ===
                                                                        'wheel'
                                                                    "
                                                                    class="h-3.5 w-3.5 text-muted-foreground"
                                                                />

                                                                <Hand
                                                                    v-else
                                                                    class="h-3.5 w-3.5 text-muted-foreground"
                                                                />

                                                                {{
                                                                    enrollment.spot_type ===
                                                                    "wheel"
                                                                        ? "Tour"
                                                                        : "Modelage"
                                                                }}
                                                            </span>
                                                        </td>

                                                        <!-- Statut -->

                                                        <td
                                                            class="px-3 py-3"
                                                        >
                                                            <Badge
                                                                variant="outline"
                                                                :class="
                                                                    getStatusClass(
                                                                        enrollment,
                                                                    )
                                                                "
                                                                class="text-[10px]"
                                                            >
                                                                <AlertCircle
                                                                    v-if="
                                                                        enrollment.status ===
                                                                        'absent'
                                                                    "
                                                                    class="h-3 w-3 mr-1"
                                                                />

                                                                <CheckCircle2
                                                                    v-else-if="
                                                                        enrollment.status ===
                                                                            'registered' &&
                                                                        !enrollment.is_past
                                                                    "
                                                                    class="h-3 w-3 mr-1"
                                                                />

                                                                {{
                                                                    getStatusLabel(
                                                                        enrollment,
                                                                    )
                                                                }}
                                                            </Badge>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </ScrollArea>
                                    </div>
                                </div>
                            </div>
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>

                <!-- Aucun module -->

                <div
                    v-else
                    class="rounded-xl border border-dashed bg-card/50 py-14 text-center"
                >
                    <BookOpen
                        class="h-9 w-9 mx-auto text-muted-foreground/30"
                    />

                    <h3 class="font-semibold mt-3">
                        Aucun module
                    </h3>

                    <p
                        class="text-xs text-muted-foreground mt-1"
                    >
                        Ni ce membre ni ses invités ne possèdent
                        encore de module.
                    </p>
                </div>
            </section>

            <!-- ===================================================== -->
            <!-- MÉTADONNÉES COMPTE                                   -->
            <!-- ===================================================== -->

            <section
                class="text-[11px] text-muted-foreground border-t pt-4 flex flex-wrap gap-x-4 gap-y-1"
            >
                <span>
                    Compte #{{ user.id }}
                </span>

                <span>
                    Créé le {{ user.created_at || "—" }}
                </span>

                <span>
                    Dernière modification :
                    {{ user.updated_at || "—" }}
                </span>
            </section>
        </div>
    </AdminLayout>
</template>
