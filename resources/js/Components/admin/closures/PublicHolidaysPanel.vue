<script setup>
import { computed, ref, watch } from "vue";
import { formatDate, pluralize } from "@/Utils/formatters";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Label } from "@/Components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import {
  Table,
  TableHeader,
  TableBody,
  TableHead,
  TableRow,
  TableCell,
} from "@/Components/ui/table";
import { CalendarDays, ChevronDown, ChevronUp } from "lucide-vue-next";

const props = defineProps({ holidays: { type: Object, required: true } });
const open = ref(false);
const selectedYear = ref(String(props.holidays.years?.[0] ?? ""));
watch(
  () => props.holidays.years,
  (years) => {
    if (!years?.some((year) => String(year) === selectedYear.value)) {
      selectedYear.value = String(years?.[0] ?? "");
    }
  },
);
const dates = computed(
  () => props.holidays.by_year?.[selectedYear.value] ?? [],
);
const displayDate = (date) =>
  formatDate(`${date}T12:00:00`, {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  });
</script>

<template>
  <section
    class="rounded-xl border bg-card shadow-xs"
    aria-labelledby="public-holidays-title"
  >
    <div
      class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="min-w-0">
        <h3
          id="public-holidays-title"
          class="flex flex-wrap items-center gap-2 text-sm font-semibold"
        >
          <CalendarDays
            class="h-4 w-4 shrink-0 text-primary"
            aria-hidden="true"
          />
          Jours fériés belges
          <Badge variant="outline" class="text-[10px] font-normal"
            >Lecture seule</Badge
          >
        </h3>
        <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
          Calendrier indicatif des jours fériés, distinct des congés scolaires
          et des fermetures enregistrées.
        </p>
      </div>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        class="shrink-0 gap-1.5 self-start text-xs sm:self-auto"
        :aria-expanded="open"
        aria-controls="public-holidays-content"
        @click="open = !open"
      >
        {{ open ? "Masquer les jours fériés" : "Voir les jours fériés" }}
        <ChevronUp v-if="open" class="h-3.5 w-3.5" aria-hidden="true" />
        <ChevronDown v-else class="h-3.5 w-3.5" aria-hidden="true" />
      </Button>
    </div>
    <div
      v-show="open"
      id="public-holidays-content"
      class="space-y-3 border-t p-4"
    >
      <p
        v-if="holidays.error"
        role="alert"
        class="rounded-lg border border-destructive/20 bg-destructive/5 p-3 text-xs text-destructive"
      >
        {{ holidays.error }}
      </p>
      <template v-else>
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <Label for="public-holidays-year" class="text-xs">Année</Label>
            <Select v-model="selectedYear">
              <SelectTrigger
                id="public-holidays-year"
                class="h-8 w-24 bg-background text-xs"
                ><SelectValue
              /></SelectTrigger>
              <SelectContent
                ><SelectItem
                  v-for="year in holidays.years"
                  :key="year"
                  :value="String(year)"
                  class="text-xs"
                  >{{ year }}</SelectItem
                ></SelectContent
              >
            </Select>
          </div>
          <span class="text-xs text-muted-foreground" aria-live="polite">{{
            pluralize(dates.length, "jour férié", "jours fériés")
          }}</span>
        </div>
        <div class="overflow-hidden rounded-lg border bg-background">
          <Table class="text-xs">
            <TableHeader class="bg-muted/40"
              ><TableRow
                ><TableHead class="font-semibold">Date</TableHead
                ><TableHead class="font-semibold"
                  >Jour férié</TableHead
                ></TableRow
              ></TableHeader
            >
            <TableBody>
              <TableRow v-for="holiday in dates" :key="holiday.date"
                ><TableCell class="whitespace-nowrap text-muted-foreground">{{
                  displayDate(holiday.date)
                }}</TableCell
                ><TableCell class="font-medium">{{
                  holiday.name
                }}</TableCell></TableRow
              >
              <TableRow v-if="dates.length === 0"
                ><TableCell
                  :colspan="2"
                  class="py-6 text-center text-muted-foreground"
                  >Aucun jour férié disponible pour cette année.</TableCell
                ></TableRow
              >
            </TableBody>
          </Table>
        </div>
      </template>
    </div>
  </section>
</template>
