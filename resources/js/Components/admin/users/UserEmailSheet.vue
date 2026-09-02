<script setup>
import { ref, computed, watch } from "vue";
import axios from "axios";
import { toast } from "vue-sonner";
import { pluralize } from "@/Utils/formatters";

// Composants Shadcn UI
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
    SheetFooter,
} from "@/Components/ui/sheet";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/Components/ui/tabs";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Badge } from "@/Components/ui/badge";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import {
    Mail,
    Send,
    Loader2,
    Eye,
    Pencil,
    Sparkles,
    FileText,
    FlaskConical,
    Users,
} from "lucide-vue-next";
import RecipientInput from "./RecipientInput.vue";

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    recipients: {
        type: Array,
        default: () => [],
    },
    selectAllMatching: {
        type: Boolean,
        default: false,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    totalMatchingCount: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(["update:open", "sent"]);

// État local des destinataires et du formulaire
const localRecipients = ref([]);
const selectedTemplate = ref("custom");
const subject = ref("");
const body = ref("");
const activeTab = ref("write"); // 'write' | 'preview'

const isSendingBulk = ref(false);
const isSendingTest = ref(false);

// Bibliothèque des modèles d'e-mails
const templates = [
    {
        id: "custom",
        name: "Message libre (Vide)",
        subject: "",
        body: "",
    },
    {
        id: "pieces_ready",
        name: "🏺 Pièces prêtes pour l'émaillage",
        subject: "Vos pièces en céramique sont prêtes pour l'émaillage !",
        body: "Bonjour {prenom},\n\nNous avons le plaisir de vous informer que vos pièces issues de vos séances ont terminé leur première cuisson (biscuit).\n\nVous pouvez venir les émailler lors des créneaux en accès libre ou lors de votre prochaine séance.\n\nÀ très bientôt à l'atelier !\nL'équipe de l'Atelier de Céramique",
    },
    {
        id: "rentree",
        name: "✨ Rentrée & Reprise des cours",
        subject: "C'est la rentrée à l'atelier de céramique !",
        body: "Bonjour {prenom},\n\nToute l'équipe espère que vous avez passé un bel été.\n\nLes cours et ateliers reprennent cette semaine. Pensez à apporter votre tablier et vos outils si vous en possédez.\n\nAu plaisir de vous retrouver au tour et au modelage !\nL'équipe de l'Atelier de Céramique",
    },
    {
        id: "holiday_wishes",
        name: "🎄 Vœux & Fermeture temporaire",
        subject: "Joyeuses fêtes & Horaires de reprise de l'atelier",
        body: "Bonjour {prenom},\n\nToute l'équipe vous souhaite d'excellentes fêtes de fin d'année et un repos bien mérité.\n\nL'atelier fermera ses portes temporairement pendant les congés et réouvrira dès la rentrée.\n\nChaleureusement,\nL'équipe de l'Atelier de Céramique",
    },
    {
        id: "practical_reminder",
        name: "📋 Rappel & Informations pratiques",
        subject: "Informations importantes concernant vos cours de céramique",
        body: "Bonjour {prenom},\n\nNous vous rappelons quelques consignes pratiques pour le bon déroulement des ateliers :\n– Pensez à nettoyer vos tours et vos postes de modelage après chaque séance.\n– Vos pièces terminées sont conservées sur les étagères dédiées pendant un mois maximum après la cuisson finale.\n\nMerci pour votre précieuse collaboration !\nL'équipe de l'Atelier de Céramique",
    },
];

// Synchroniser les destinataires à l'ouverture
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            localRecipients.value = [...props.recipients];
            activeTab.value = "write";
        }
    },
    { immediate: true }
);

watch(
    () => props.recipients,
    (newVal) => {
        if (props.open) {
            localRecipients.value = [...newVal];
        }
    }
);

// Application du modèle choisi
const handleTemplateChange = (templateId) => {
    selectedTemplate.value = templateId;
    const found = templates.find((t) => t.id === templateId);
    if (found && templateId !== "custom") {
        subject.value = found.subject;
        body.value = found.body;
    }
};

// Nombre total effectif de destinataires
const effectiveRecipientsCount = computed(() => {
    if (props.selectAllMatching && props.totalMatchingCount > 0) {
        return props.totalMatchingCount;
    }
    return localRecipients.value.length;
});

// Destinataire type pour l'aperçu en direct
const sampleRecipient = computed(() => {
    if (localRecipients.value.length > 0) {
        return localRecipients.value[0];
    }
    return {
        first_name: "Sophie",
        last_name: "Martin",
        full_name: "Sophie Martin",
        email: "sophie.martin@example.com",
    };
});

// Remplacement réactif des variables pour l'aperçu
const previewRenderedBody = computed(() => {
    if (!body.value) return "Aucun contenu saisi pour le moment.";

    const r = sampleRecipient.value;
    const firstName = r.first_name || r.full_name?.split(" ")[0] || "Membre";
    const lastName = r.last_name || "";
    const email = r.email || "";

    return body.value
        .replaceAll("{prenom}", firstName)
        .replaceAll("{nom}", lastName)
        .replaceAll("{email}", email);
});

const close = () => {
    emit("update:open", false);
};

// Envoi d'un e-mail de test à l'administrateur connecté
const executeSendTestEmail = async () => {
    if (!subject.value || !body.value) {
        toast.error("Champs incomplets", {
            description: "Veuillez renseigner un objet et un message avant d'envoyer un test.",
        });
        return;
    }

    isSendingTest.value = true;
    try {
        const response = await axios.post(route("users.send-test-email"), {
            subject: subject.value,
            body: body.value,
        });

        toast.success("E-mail de test envoyé !", {
            description: response.data.message,
        });
    } catch (error) {
        console.error(error);
        toast.error("Erreur d'envoi du test", {
            description: error.response?.data?.message || "Impossible d'envoyer l'e-mail de test.",
        });
    } finally {
        isSendingTest.value = false;
    }
};

// Envoi groupé à tous les destinataires (membres et externes)
const executeSendBulkEmail = async () => {
    if (!subject.value || !body.value) {
        toast.error("Champs requis", {
            description: "Veuillez renseigner l'objet et le message du courriel.",
        });
        return;
    }

    if (effectiveRecipientsCount.value === 0) {
        toast.error("Aucun destinataire", {
            description: "Veuillez sélectionner ou ajouter au moins un destinataire.",
        });
        return;
    }

    isSendingBulk.value = true;
    try {
        const recipientIds = localRecipients.value
            .filter((r) => !r.is_custom && r.id)
            .map((r) => r.id);

        const customEmails = localRecipients.value
            .filter((r) => r.is_custom || !r.id)
            .map((r) => r.email);

        const response = await axios.post(route("users.send-bulk-email"), {
            subject: subject.value,
            body: body.value,
            recipient_ids: recipientIds,
            custom_emails: customEmails,
            select_all_matching: props.selectAllMatching,
            filters: props.filters,
        });

        toast.success("E-mails mis en file d'attente", {
            description: response.data.message,
        });

        close();
        emit("sent");
    } catch (error) {
        console.error(error);
        toast.error("Erreur lors de l'envoi", {
            description: error.response?.data?.message || "Une erreur est survenue.",
        });
    } finally {
        isSendingBulk.value = false;
    }
};
</script>

<template>
    <Sheet :open="open" @update:open="(val) => emit('update:open', val)">
        <SheetContent side="right" class="w-full sm:max-w-2xl p-0 flex flex-col h-full bg-background border-l shadow-2xl">
            <!-- En-tête -->
            <SheetHeader class="p-6 pb-4 border-b shrink-0 bg-muted/20 pr-12">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <SheetTitle class="text-lg font-bold">Rédiger un e-mail groupé</SheetTitle>
                        <Badge variant="outline" class="bg-primary/10 text-primary border-primary/20 text-xs font-semibold gap-1">
                            <Users class="h-3 w-3" />
                            {{ pluralize(effectiveRecipientsCount, 'destinataire') }}
                        </Badge>
                    </div>
                    <SheetDescription class="text-xs">
                        Communiquez avec vos membres sélectionnés en utilisant des modèles ou un message personnalisé.
                    </SheetDescription>
                </div>
            </SheetHeader>

            <!-- Corps scrollable du tiroir -->
            <div class="flex-1 overflow-y-auto p-6 space-y-5">
                <!-- 1. Destinataires & Tags Input -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <Label class="text-xs font-semibold text-foreground">
                            Destinataires ({{ effectiveRecipientsCount }})
                        </Label>
                        <span v-if="selectAllMatching" class="text-[11px] text-primary font-medium">
                            Mode global : tous les {{ totalMatchingCount }} membres filtrés
                        </span>
                    </div>

                    <RecipientInput v-model="localRecipients" />
                </div>

                <!-- 2. Sélecteur de Modèle -->
                <div class="space-y-1.5">
                    <Label class="text-xs font-semibold text-foreground flex items-center gap-1.5">
                        <FileText class="h-3.5 w-3.5 text-muted-foreground" />
                        Modèle prédéfini
                    </Label>
                    <Select :model-value="selectedTemplate" @update:model-value="handleTemplateChange">
                        <SelectTrigger class="h-9 text-xs bg-background cursor-pointer">
                            <SelectValue placeholder="Choisir un modèle de message" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="tpl in templates"
                                :key="tpl.id"
                                :value="tpl.id"
                                class="text-xs cursor-pointer"
                            >
                                {{ tpl.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- 3. Objet du mail -->
                <div class="space-y-1.5">
                    <Label for="email_subject" class="text-xs font-semibold text-foreground">
                        Objet du message <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="email_subject"
                        v-model="subject"
                        type="text"
                        placeholder="Ex: Informations sur vos cours de céramique"
                        class="h-9 text-xs bg-background"
                    />
                </div>

                <!-- 4. Onglets Rédaction / Aperçu Live -->
                <div class="space-y-2 pt-1">
                    <Tabs v-model="activeTab" class="w-full">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <Label class="text-xs font-semibold text-foreground">Corps du message</Label>
                            <TabsList class="h-8 bg-muted/60 p-0.5">
                                <TabsTrigger value="write" class="text-xs px-2.5 py-1 gap-1.5 cursor-pointer">
                                    <Pencil class="h-3 w-3" />
                                    <span>Rédiger</span>
                                </TabsTrigger>
                                <TabsTrigger value="preview" class="text-xs px-2.5 py-1 gap-1.5 cursor-pointer">
                                    <Eye class="h-3 w-3" />
                                    <span>Aperçu</span>
                                </TabsTrigger>
                            </TabsList>
                        </div>

                        <!-- Mode Rédiger -->
                        <TabsContent value="write" class="space-y-2 mt-0">
                            <textarea
                                v-model="body"
                                rows="9"
                                placeholder="Bonjour {prenom},&#10;&#10;Nous vous informons que..."
                                class="w-full rounded-lg border bg-background px-3 py-2.5 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary leading-relaxed font-sans"
                            />
                            <div class="p-2.5 rounded-lg bg-muted/40 border text-[11px] text-muted-foreground space-y-1">
                                <p class="font-medium text-foreground flex items-center gap-1">
                                    <Sparkles class="h-3.5 w-3.5 text-amber-600" />
                                    Variables de personnalisation automatique :
                                </p>
                                <div class="flex items-center gap-3 font-mono text-[10px] text-foreground flex-wrap">
                                    <span class="bg-background px-1.5 py-0.5 rounded border">{prenom} : Prénom du membre</span>
                                    <span class="bg-background px-1.5 py-0.5 rounded border">{nom} : Nom de famille</span>
                                    <span class="bg-background px-1.5 py-0.5 rounded border">{email} : Adresse e-mail</span>
                                </div>
                            </div>
                        </TabsContent>

                        <!-- Mode Aperçu (Simulateur Webmail) -->
                        <TabsContent value="preview" class="mt-0">
                            <div class="rounded-xl border bg-muted/30 p-4 space-y-3 shadow-2xs">
                                <div class="space-y-1 text-xs pb-3 border-b border-border/80 text-muted-foreground">
                                    <p><strong class="text-foreground">De :</strong> Atelier de Céramique &lt;contact@atelier.be&gt;</p>
                                    <p>
                                        <strong class="text-foreground">À :</strong>
                                        {{ sampleRecipient.full_name }} &lt;{{ sampleRecipient.email }}&gt;
                                    </p>
                                    <p><strong class="text-foreground">Objet :</strong> {{ subject || '(Sans objet)' }}</p>
                                </div>

                                <!-- Contenu stylisé -->
                                <div class="p-4 rounded-lg bg-background border text-xs text-foreground space-y-4 shadow-2xs">
                                    <div class="text-center pb-3 border-b">
                                        <span class="font-bold text-xs uppercase tracking-widest text-primary">
                                            Atelier de Céramique
                                        </span>
                                    </div>

                                    <div class="whitespace-pre-wrap leading-relaxed">
                                        {{ previewRenderedBody }}
                                    </div>

                                    <div class="pt-3 border-t text-[11px] text-muted-foreground text-center">
                                        Atelier de Céramique · Tous droits réservés
                                    </div>
                                </div>
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>

            <!-- Pied de page avec actions -->
            <SheetFooter class="p-4 border-t shrink-0 bg-muted/10 flex flex-row items-center justify-between w-full gap-3">
                <!-- En bas à gauche : E-mail de test -->
                <div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-8 text-xs gap-1.5 text-muted-foreground hover:text-foreground cursor-pointer"
                        :disabled="isSendingTest || isSendingBulk || !subject || !body"
                        @click="executeSendTestEmail"
                    >
                        <Loader2 v-if="isSendingTest" class="h-3.5 w-3.5 animate-spin" />
                        <FlaskConical v-else class="h-3.5 w-3.5 text-amber-600" />
                        <span>M'envoyer un test</span>
                    </Button>
                </div>

                <!-- En bas à droite : Annuler / Envoyer -->
                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-8 text-xs cursor-pointer"
                        @click="close"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        class="h-8 text-xs gap-1.5 font-semibold shadow-xs cursor-pointer"
                        :disabled="isSendingBulk || isSendingTest || effectiveRecipientsCount === 0 || !subject || !body"
                        @click="executeSendBulkEmail"
                    >
                        <Loader2 v-if="isSendingBulk" class="h-3.5 w-3.5 animate-spin" />
                        <Send v-else class="h-3.5 w-3.5" />
                        <span>Envoyer aux {{ effectiveRecipientsCount }} {{ pluralize(effectiveRecipientsCount, 'destinataire', null, false) }}</span>
                    </Button>
                </div>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
