<script setup>
import { computed, ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";

import ConfirmsPassword from "@/Components/ConfirmsPassword.vue";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

import {
    AlertTriangle,
    CheckCircle2,
    Copy,
    KeyRound,
    Loader2,
    LockKeyhole,
    QrCode,
    RefreshCw,
    ShieldCheck,
    ShieldOff,
} from "lucide-vue-next";

const props = defineProps({
    requiresConfirmation: Boolean,
});

const page = usePage();

const enabling = ref(false);
const confirming = ref(false);
const disabling = ref(false);

const qrCode = ref(null);
const setupKey = ref(null);
const recoveryCodes = ref([]);

const copiedSetupKey = ref(false);
const copiedRecoveryCodes = ref(false);

const confirmationForm = useForm({
    code: "",
});

const twoFactorEnabled = computed(
    () =>
        !enabling.value &&
        page.props.auth.user?.two_factor_enabled
);

watch(twoFactorEnabled, () => {
    if (!twoFactorEnabled.value) {
        confirmationForm.reset();
        confirmationForm.clearErrors();
    }
});

const enableTwoFactorAuthentication = () => {
    enabling.value = true;

    router.post(
        route("two-factor.enable"),
        {},
        {
            preserveScroll: true,

            onSuccess: () =>
                Promise.all([
                    showQrCode(),
                    showSetupKey(),
                    showRecoveryCodes(),
                ]),

            onFinish: () => {
                enabling.value = false;
                confirming.value = props.requiresConfirmation;
            },
        }
    );
};

const showQrCode = () => {
    return axios
        .get(route("two-factor.qr-code"))
        .then((response) => {
            qrCode.value = response.data.svg;
        });
};

const showSetupKey = () => {
    return axios
        .get(route("two-factor.secret-key"))
        .then((response) => {
            setupKey.value = response.data.secretKey;
        });
};

const showRecoveryCodes = () => {
    return axios
        .get(route("two-factor.recovery-codes"))
        .then((response) => {
            recoveryCodes.value = response.data;
        });
};

const confirmTwoFactorAuthentication = () => {
    confirmationForm.post(route("two-factor.confirm"), {
        errorBag: "confirmTwoFactorAuthentication",
        preserveScroll: true,
        preserveState: true,

        onSuccess: () => {
            confirming.value = false;
            qrCode.value = null;
            setupKey.value = null;
        },
    });
};

const regenerateRecoveryCodes = () => {
    axios
        .post(route("two-factor.recovery-codes"))
        .then(() => showRecoveryCodes());
};

const disableTwoFactorAuthentication = () => {
    disabling.value = true;

    router.delete(route("two-factor.disable"), {
        preserveScroll: true,

        onSuccess: () => {
            disabling.value = false;
            confirming.value = false;
            qrCode.value = null;
            setupKey.value = null;
            recoveryCodes.value = [];
        },

        onError: () => {
            disabling.value = false;
        },
    });
};

const copySetupKey = async () => {
    if (!setupKey.value) return;

    await navigator.clipboard.writeText(setupKey.value);

    copiedSetupKey.value = true;

    setTimeout(() => {
        copiedSetupKey.value = false;
    }, 1800);
};

const copyRecoveryCodes = async () => {
    if (!recoveryCodes.value.length) return;

    await navigator.clipboard.writeText(
        recoveryCodes.value.join("\n")
    );

    copiedRecoveryCodes.value = true;

    setTimeout(() => {
        copiedRecoveryCodes.value = false;
    }, 1800);
};
</script>

<template>
    <div class="p-6 sm:p-8">
        <!-- Header -->
        <div
            class="flex flex-col sm:flex-row sm:items-start justify-between gap-4"
        >
            <div class="flex items-start gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center shrink-0"
                >
                    <ShieldCheck class="w-5 h-5 text-gray-700" />
                </div>

                <div>
                    <h3 class="text-lg font-bold text-gray-900">
                        Double authentification
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 max-w-xl">
                        Protégez votre compte avec un code temporaire généré
                        par une application d'authentification.
                    </p>
                </div>
            </div>

            <!-- Badge -->
            <div
                v-if="twoFactorEnabled && !confirming"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-700 shrink-0"
            >
                <CheckCircle2 class="w-3.5 h-3.5" />
                Activée
            </div>

            <div
                v-else-if="confirming"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-xs font-semibold text-amber-700 shrink-0"
            >
                <Loader2 class="w-3.5 h-3.5" />
                À confirmer
            </div>

            <div
                v-else
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-100 border border-gray-200 text-xs font-semibold text-gray-600 shrink-0"
            >
                <ShieldOff class="w-3.5 h-3.5" />
                Désactivée
            </div>
        </div>

        <!-- ====================================================== -->
        <!-- DÉSACTIVÉE -->
        <!-- ====================================================== -->

        <div
            v-if="!twoFactorEnabled && !confirming"
            class="mt-6"
        >
            <div
                class="p-5 rounded-xl bg-gray-50 border border-gray-200"
            >
                <div class="flex items-start gap-3">
                    <LockKeyhole
                        class="w-5 h-5 text-gray-600 shrink-0 mt-0.5"
                    />

                    <div>
                        <p class="text-sm font-semibold text-gray-900">
                            Renforcez la sécurité de votre compte
                        </p>

                        <p class="mt-1 text-sm text-gray-500 leading-relaxed">
                            Une fois activée, un code à usage unique vous sera
                            demandé en plus de votre mot de passe lors de la
                            connexion.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <ConfirmsPassword
                    @confirmed="enableTwoFactorAuthentication"
                >
                    <Button
                        type="button"
                        class="bg-blue-600 hover:bg-blue-500 text-white"
                        :disabled="enabling"
                    >
                        <Loader2
                            v-if="enabling"
                            class="w-4 h-4 mr-2 animate-spin"
                        />

                        <ShieldCheck
                            v-else
                            class="w-4 h-4 mr-2"
                        />

                        {{
                            enabling
                                ? "Activation..."
                                : "Activer la double authentification"
                        }}
                    </Button>
                </ConfirmsPassword>
            </div>
        </div>

        <!-- ====================================================== -->
        <!-- CONFIGURATION / CONFIRMATION -->
        <!-- ====================================================== -->

        <div
            v-if="confirming"
            class="mt-7 space-y-6"
        >
            <div
                class="p-4 rounded-xl bg-amber-50 border border-amber-200 flex items-start gap-3"
            >
                <AlertTriangle
                    class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"
                />

                <div>
                    <p class="text-sm font-semibold text-amber-900">
                        Terminez la configuration
                    </p>

                    <p class="mt-1 text-sm text-amber-800">
                        Scannez le QR code avec votre application
                        d'authentification puis saisissez le code généré.
                    </p>
                </div>
            </div>

            <div class="grid md:grid-cols-[auto_1fr] gap-6 items-start">
                <!-- QR -->
                <div
                    v-if="qrCode"
                    class="p-4 bg-white border border-gray-200 rounded-xl"
                >
                    <div
                        class="flex items-center gap-2 mb-3 text-sm font-semibold text-gray-800"
                    >
                        <QrCode class="w-4 h-4" />
                        QR code
                    </div>

                    <div
                        class="two-factor-qr flex justify-center"
                        v-html="qrCode"
                    />
                </div>

                <!-- Instructions -->
                <div class="space-y-5">
                    <!-- Setup key -->
                    <div
                        v-if="setupKey"
                        class="space-y-2"
                    >
                        <Label>
                            Clé de configuration
                        </Label>

                        <p class="text-xs text-gray-500">
                            Si vous ne pouvez pas scanner le QR code, entrez
                            cette clé manuellement dans votre application.
                        </p>

                        <div
                            class="flex items-center gap-2 p-3 rounded-lg bg-gray-50 border border-gray-200"
                        >
                            <code
                                class="flex-1 min-w-0 text-sm font-mono text-gray-800 break-all"
                            >
                                {{ setupKey }}
                            </code>

                            <button
                                type="button"
                                class="p-2 rounded-md text-gray-500 hover:text-gray-900 hover:bg-gray-100 shrink-0"
                                :title="
                                    copiedSetupKey
                                        ? 'Copié'
                                        : 'Copier la clé'
                                "
                                @click="copySetupKey"
                            >
                                <CheckCircle2
                                    v-if="copiedSetupKey"
                                    class="w-4 h-4 text-emerald-600"
                                />

                                <Copy
                                    v-else
                                    class="w-4 h-4"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- OTP -->
                    <div class="space-y-2">
                        <Label for="code">
                            Code d'authentification
                        </Label>

                        <Input
                            id="code"
                            v-model="confirmationForm.code"
                            type="text"
                            name="code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            autofocus
                            placeholder="123456"
                            :aria-invalid="
                                Boolean(
                                    confirmationForm.errors.code
                                )
                            "
                            :class="[
                                'h-12 text-center text-lg tracking-[0.3em] font-semibold',
                                confirmationForm.errors.code
                                    ? 'border-red-400 focus-visible:ring-red-200'
                                    : 'border-gray-200',
                            ]"
                            @keyup.enter="
                                confirmTwoFactorAuthentication
                            "
                        />

                        <p
                            v-if="confirmationForm.errors.code"
                            class="text-xs font-medium text-red-600"
                        >
                            {{
                                confirmationForm.errors.code
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="pt-5 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-3"
            >
                <ConfirmsPassword
                    @confirmed="disableTwoFactorAuthentication"
                >
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="disabling"
                    >
                        Annuler
                    </Button>
                </ConfirmsPassword>

                <ConfirmsPassword
                    @confirmed="confirmTwoFactorAuthentication"
                >
                    <Button
                        type="button"
                        class="bg-blue-600 hover:bg-blue-500 text-white"
                        :disabled="
                            confirmationForm.processing
                        "
                    >
                        <Loader2
                            v-if="
                                confirmationForm.processing
                            "
                            class="w-4 h-4 mr-2 animate-spin"
                        />

                        <CheckCircle2
                            v-else
                            class="w-4 h-4 mr-2"
                        />

                        Confirmer l'activation
                    </Button>
                </ConfirmsPassword>
            </div>
        </div>

        <!-- ====================================================== -->
        <!-- ACTIVÉE -->
        <!-- ====================================================== -->

        <div
            v-if="twoFactorEnabled && !confirming"
            class="mt-6 space-y-6"
        >
            <div
                class="p-5 rounded-xl bg-emerald-50 border border-emerald-200"
            >
                <div class="flex items-start gap-3">
                    <CheckCircle2
                        class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"
                    />

                    <div>
                        <p class="text-sm font-semibold text-emerald-900">
                            Votre compte est protégé
                        </p>

                        <p class="mt-1 text-sm text-emerald-800">
                            Un code généré par votre application
                            d'authentification sera demandé lors de vos
                            prochaines connexions.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Recovery codes -->
            <div
                v-if="recoveryCodes.length"
                class="space-y-4"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-end justify-between gap-3"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <KeyRound class="w-4 h-4 text-gray-700" />

                            <h4 class="text-sm font-bold text-gray-900">
                                Codes de récupération
                            </h4>
                        </div>

                        <p class="mt-1 text-xs text-gray-500 max-w-xl">
                            Conservez ces codes dans un endroit sécurisé.
                            Chacun ne peut être utilisé qu'une seule fois.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900"
                        @click="copyRecoveryCodes"
                    >
                        <CheckCircle2
                            v-if="copiedRecoveryCodes"
                            class="w-3.5 h-3.5 text-emerald-600"
                        />

                        <Copy
                            v-else
                            class="w-3.5 h-3.5"
                        />

                        {{
                            copiedRecoveryCodes
                                ? "Codes copiés"
                                : "Copier les codes"
                        }}
                    </button>
                </div>

                <div
                    class="grid sm:grid-cols-2 gap-2 p-4 bg-gray-950 rounded-xl"
                >
                    <code
                        v-for="code in recoveryCodes"
                        :key="code"
                        class="px-3 py-2 bg-white/5 rounded-lg text-sm font-mono text-gray-100"
                    >
                        {{ code }}
                    </code>
                </div>
            </div>

            <!-- Actions recovery -->
            <div class="flex flex-wrap gap-3">
                <ConfirmsPassword
                    v-if="recoveryCodes.length === 0"
                    @confirmed="showRecoveryCodes"
                >
                    <Button
                        type="button"
                        variant="outline"
                    >
                        <KeyRound class="w-4 h-4 mr-2" />
                        Afficher les codes de récupération
                    </Button>
                </ConfirmsPassword>

                <ConfirmsPassword
                    v-else
                    @confirmed="regenerateRecoveryCodes"
                >
                    <Button
                        type="button"
                        variant="outline"
                    >
                        <RefreshCw class="w-4 h-4 mr-2" />
                        Générer de nouveaux codes
                    </Button>
                </ConfirmsPassword>
            </div>

            <!-- Disable -->
            <div class="pt-6 border-t border-gray-100">
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                >
                    <div>
                        <p class="text-sm font-semibold text-gray-900">
                            Désactiver la double authentification
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Votre compte sera alors protégé uniquement par
                            votre mot de passe.
                        </p>
                    </div>

                    <ConfirmsPassword
                        @confirmed="disableTwoFactorAuthentication"
                    >
                        <Button
                            type="button"
                            variant="destructive"
                            :disabled="disabling"
                        >
                            <Loader2
                                v-if="disabling"
                                class="w-4 h-4 mr-2 animate-spin"
                            />

                            <ShieldOff
                                v-else
                                class="w-4 h-4 mr-2"
                            />

                            Désactiver
                        </Button>
                    </ConfirmsPassword>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.two-factor-qr :deep(svg) {
    width: 180px;
    height: 180px;
}
</style>
