<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import {
    Calendar,
    User,
    Users,
    Sparkles,
    CheckCircle2,
    Clock,
    ArrowRight,
    ChevronRight,
    CircleHelp,
} from "lucide-vue-next";
import { pluralize, formatDate } from "@/Utils/formatters";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";

const props = defineProps({
    module: {
        type: Object,
        required: true,
    },
    isOwner: {
        type: Boolean,
        default: false,
    },
    availableMakeupCredits: {
        type: Number,
        default: 0,
    },
});

const progressPercentage = computed(() => {
    if (!props.module.total_lessons || props.module.total_lessons <= 0)
        return 0;
    const pct = Math.round(
        (props.module.completed_lessons / props.module.total_lessons) * 100,
    );
    return Math.min(100, Math.max(0, pct));
});

const isCompleted = computed(() => {
    return props.module.completed_lessons >= props.module.total_lessons;
});

const isExpired = computed(() => {
    if (!props.module.expiration_date) return false;
    return new Date(props.module.expiration_date) < new Date();
});
</script>

<template>
    <Link
        :href="route('member.modules.show', module.id)"
        class="block group focus:outline-none"
    >
        <div
            :class="[
                'p-5 sm:p-6 rounded-xl border transition-all duration-200 flex flex-col justify-between gap-5 shadow',
                !module.is_active || isCompleted || isExpired
                    ? 'bg-gray-50/70 border-gray-200 opacity-80 group-hover:opacity-100 group-hover:border-gray-300'
                    : 'bg-gray-50 border-gray-300 group-hover:border-gray-400 hover:bg-[#f2f6f9] group-hover:shadow-md group-hover:-translate-y-0.5',
            ]"
        >
            <!-- 1. En-tête : Type, Statut & Participant -->
            <div class="space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center text-xs font-semibold uppercase tracking-wider px-2.5 py-0.5 rounded-md bg-gray-200 text-gray-700"
                            >
                                {{ module.type.name }}
                            </span>

                            <span
                                v-if="isCompleted"
                                class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200"
                            >
                                <CheckCircle2 class="w-3.5 h-3.5" /> Terminé
                            </span>
                            <span
                                v-else-if="!module.is_active || isExpired"
                                class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-500 border border-gray-200"
                            >
                                Inactif / Expiré
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200"
                            >
                                En cours
                            </span>
                        </div>

                        <h4
                            class="text-base sm:text-lg font-semibold text-gray-900 flex items-center gap-1.5"
                        >
                            Module de
                            {{ pluralize(module.total_lessons, "séance") }}
                            <ChevronRight
                                class="w-4 h-4 text-gray-900 group-hover:translate-x-0.5 transition-transform"
                            />
                        </h4>
                    </div>

                    <!-- Pastille Participant -->
                    <div
                        :class="[
                            'flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium shrink-0',
                            isOwner
                                ? 'bg-gray-900 text-white'
                                : 'bg-gray-200 text-gray-700 border border-gray-200',
                        ]"
                    >
                        <component
                            :is="isOwner ? User : Users"
                            class="w-3.5 h-3.5"
                        />
                        <span>{{
                            isOwner ? "Moi-même" : module.participant.name
                        }}</span>
                    </div>
                </div>

                <!-- 2. Jauge de progression des séances (Couleur Sauge) -->
                <div class="space-y-1.5">
                    <div
                        class="flex justify-between text-xs sm:text-sm font-medium text-gray-600"
                    >
                        <span>
                            {{ module.completed_lessons }} sur
                            {{ pluralize(module.total_lessons, "séance") }}
                            effectuée{{
                                module.completed_lessons > 1 ? "s" : ""
                            }}
                        </span>
                        <span class="font-bold text-gray-900"
                            >{{ progressPercentage }}%</span
                        >
                    </div>

                    <div
                        class="h-2 w-full bg-gray-300 rounded-full overflow-hidden"
                    >
                        <div
                            class="h-full bg-gray-900 rounded-full transition-all duration-500"
                            :style="{ width: `${progressPercentage}%` }"
                        />
                    </div>
                </div>
            </div>

            <!-- 3. Quota de rattrapage & Métadonnées -->
            <div class="space-y-3.5 pt-3 border-t border-gray-100 text-xs">
                <!-- Bloc Quota -->
                <div
                    class="p-3.5 bg-sage-light rounded-xl border border-sage-border space-y-2"
                >
                    <div
                        class="flex items-center justify-between text-xs sm:text-sm"
                    >
                        <span
                            class="font-semibold text-sage-dark flex items-center gap-1.5"
                        >
                            <Sparkles class="w-4 h-4 text-sage-dark" />
                            Rattrapages du module
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <span
                                            tabindex="0"
                                            aria-label="Informations sur les droits d'absence rattrapable"
                                            class="inline-flex cursor-help"
                                        >
                                            <CircleHelp
                                                class="w-3.5 h-3.5 text-gray-400"
                                            />
                                        </span>
                                    </TooltipTrigger>
                                    <TooltipContent class="max-w-xs">
                                        Si vous prévenez au moins 24 h à
                                        l'avance de vos absences, vous aurez
                                        droit à un crédit de rattrapage (jusqu'à
                                        {{ module.max_makeups_allowed }} fois
                                        pour ce module).
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </span>
                    </div>

                    <!-- Barres segmentées de rattrapage -->
                    <div class="flex items-center gap-1.5">
                        <div
                            v-for="index in module.max_makeups_allowed"
                            :key="index"
                            :class="[
                                'h-2 flex-1 rounded-full transition',
                                index <= module.makeups_used_count
                                    ? 'bg-emerald-200'
                                    : index <=
                                        module.makeups_used_count +
                                            module.remaining_makeups
                                      ? 'bg-sage'
                                      : 'bg-gray-100 border border-gray-200/50',
                            ]"
                        />
                    </div>

                    <div class="flex justify-between text-xs pt-0.5">
                        <div
                            class="flex items-center justify-between w-full gap-3"
                        >
                            <span class="text-gray-600">
                                {{ module.makeups_used_count }}
                                / {{ module.max_makeups_allowed }}
                                rattrapages utilisés
                            </span>

                            <span
                                v-if="availableMakeupCredits > 0"
                                class="font-semibold text-sage-dark"
                            >
                                {{
                                    pluralize(
                                        availableMakeupCredits,
                                        "crédit disponible",
                                        "crédits disponibles",
                                    )
                                }}
                            </span>

                            <span v-else class="text-gray-400">
                                Aucun crédit disponible
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 4. Action : Bouton Poser un rattrapage -->
                <div
                    v-if="
                        module.is_active &&
                        module.remaining_makeups > 0 &&
                        availableMakeupCredits > 0 &&
                        !isCompleted
                    "
                    class="pt-1"
                >
                    <Link
                        :href="
                            route('calendrier.index', {
                                mode: 'makeup',
                                module_id: module.id,
                            })
                        "
                        class="w-full block"
                        @click.stop
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            class="w-full group/btn text-xs sm:text-sm text-white border-sage-dark bg-sage-dark hover:bg-sage hover:text-white hover:border-sage justify-between h-9 px-3.5 font-medium transition-all duration-150 shadow-2xs"
                        >
                            <span class="flex items-center gap-2">
                                <Sparkles
                                    class="w-4 h-4 text-white transition-colors"
                                />
                                Poser un rattrapage
                            </span>
                            <ArrowRight
                                class="w-4 h-4 text-white group-hover/btn:translate-x-0.5 transition-all"
                            />
                        </Button>
                    </Link>
                </div>

                <!-- Dates d'achat et d'expiration -->
                <div
                    class="flex flex-wrap items-center justify-between text-xs text-gray-600 px-0.5"
                >
                    <span
                        v-if="module.purchase_date"
                        class="flex items-center justify-start gap-1.5"
                    >
                        <Calendar class="w-3.5 h-3.5 text-gray-500" />
                        Acheté le {{ formatDate(module.purchase_date) }}
                    </span>
                    <span
                        v-if="module.expiration_date"
                        class="flex items-center gap-1.5"
                    >
                        <Clock class="w-3.5 h-3.5 text-gray-400" />
                        Expire le {{ formatDate(module.expiration_date) }}
                    </span>
                </div>
            </div>
        </div>
    </Link>
</template>
