<script setup>
import { computed, nextTick, ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/Components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import { Loader2 } from "lucide-vue-next";

const props = defineProps({
  open: { type: Boolean, default: false },
  closure: { type: Object, default: null },
});
const emit = defineEmits(["update:open"]);
const formElement = ref(null);
const editing = computed(() => props.closure !== null);
const form = useForm({
  name: "",
  type: "school_holiday",
  start_date: "",
  end_date: "",
  notes: "",
});

watch(
  () => props.open,
  (open) => {
    if (!open) return;
    form.reset();
    form.clearErrors();
    form.name = props.closure?.name ?? "";
    form.type = props.closure?.type ?? "school_holiday";
    form.start_date = props.closure?.start_date ?? "";
    form.end_date = props.closure?.end_date ?? "";
    form.notes = props.closure?.notes ?? "";
  },
  { immediate: true },
);

function changeOpen(open) {
  if (!form.processing) emit("update:open", open);
}
function submit() {
  if (form.processing) return;
  const name = form.name.trim();
  const wasEditing = editing.value;
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      emit("update:open", false);
      toast.success(wasEditing ? "Période modifiée" : "Période ajoutée", {
        description: `La période « ${name} » a été ${wasEditing ? "mise à jour" : "créée"}.`,
      });
    },
    onError: () =>
      nextTick(() => {
        formElement.value?.querySelector('[aria-invalid="true"]')?.focus();
      }),
  };
  if (wasEditing)
    form.patch(route("studio-closures.update", props.closure.id), options);
  else form.post(route("studio-closures.store"), options);
}
</script>

<template>
  <Dialog :open="open" @update:open="changeOpen">
    <DialogContent
      class="max-h-[90dvh] overflow-y-auto sm:max-w-xl"
      @escape-key-down="form.processing && $event.preventDefault()"
      @interact-outside="form.processing && $event.preventDefault()"
    >
      <form ref="formElement" class="space-y-5" @submit.prevent="submit">
        <DialogHeader>
          <DialogTitle>{{
            editing ? "Modifier la période" : "Ajouter une période"
          }}</DialogTitle>
          <DialogDescription
            >Renseignez le congé scolaire ou la fermeture de l’atelier. Les
            notes sont facultatives.</DialogDescription
          >
        </DialogHeader>
        <fieldset :disabled="form.processing" class="space-y-4">
          <div class="space-y-1.5">
            <Label for="closure-name">Nom</Label>
            <Input
              id="closure-name"
              v-model="form.name"
              required
              maxlength="255"
              placeholder="Ex. Vacances d’automne"
              :aria-invalid="!!form.errors.name"
              aria-describedby="closure-name-error"
            />
            <p
              id="closure-name-error"
              role="alert"
              class="text-xs text-destructive"
            >
              {{ form.errors.name }}
            </p>
          </div>
          <div class="space-y-1.5">
            <Label for="closure-type">Catégorie</Label>
            <Select v-model="form.type" :disabled="form.processing">
              <SelectTrigger
                id="closure-type"
                class="w-full"
                :aria-invalid="!!form.errors.type"
                aria-describedby="closure-type-error"
                ><SelectValue
              /></SelectTrigger>
              <SelectContent>
                <SelectItem value="school_holiday">Congé scolaire</SelectItem>
                <SelectItem value="studio_closure"
                  >Fermeture atelier</SelectItem
                >
              </SelectContent>
            </Select>
            <p
              id="closure-type-error"
              role="alert"
              class="text-xs text-destructive"
            >
              {{ form.errors.type }}
            </p>
          </div>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="space-y-1.5">
              <Label for="closure-start">Date de début</Label>
              <Input
                id="closure-start"
                v-model="form.start_date"
                type="date"
                required
                :aria-invalid="!!form.errors.start_date"
                aria-describedby="closure-start-error closure-date-help"
              />
              <p
                id="closure-start-error"
                role="alert"
                class="text-xs text-destructive"
              >
                {{ form.errors.start_date }}
              </p>
            </div>
            <div class="space-y-1.5">
              <Label for="closure-end">Date de fin</Label>
              <Input
                id="closure-end"
                v-model="form.end_date"
                type="date"
                required
                :min="form.start_date || undefined"
                :aria-invalid="!!form.errors.end_date"
                aria-describedby="closure-end-error closure-date-help"
              />
              <p
                id="closure-end-error"
                role="alert"
                class="text-xs text-destructive"
              >
                {{ form.errors.end_date }}
              </p>
            </div>
          </div>
          <p id="closure-date-help" class="text-xs text-muted-foreground">
            Les deux dates sont incluses. Pour une seule journée, indiquez la
            même date de début et de fin.
          </p>
          <div class="space-y-1.5">
            <Label for="closure-notes"
              >Notes
              <span class="font-normal text-muted-foreground"
                >(facultatives)</span
              ></Label
            >
            <textarea
              id="closure-notes"
              v-model="form.notes"
              rows="5"
              maxlength="10000"
              placeholder="Précisions sur cette période…"
              class="flex min-h-32 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
              :aria-invalid="!!form.errors.notes"
              aria-describedby="closure-notes-error closure-notes-count"
            ></textarea>
            <div class="flex justify-between gap-3">
              <p
                id="closure-notes-error"
                role="alert"
                class="text-xs text-destructive"
              >
                {{ form.errors.notes }}
              </p>
              <p
                id="closure-notes-count"
                class="shrink-0 text-xs tabular-nums text-muted-foreground"
              >
                {{ form.notes.length }} / 10 000
              </p>
            </div>
          </div>
        </fieldset>
        <DialogFooter class="gap-2 border-t pt-4">
          <Button
            type="button"
            variant="outline"
            :disabled="form.processing"
            @click="changeOpen(false)"
            >Annuler</Button
          >
          <Button type="submit" :disabled="form.processing" class="gap-2"
            ><Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />{{
              form.processing
                ? "Enregistrement…"
                : editing
                  ? "Enregistrer"
                  : "Créer la période"
            }}</Button
          >
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
