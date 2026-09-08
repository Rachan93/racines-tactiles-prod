<script setup>
import { computed, onUnmounted, ref, watch } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { pluralize, formatDate } from "@/Utils/formatters";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import StudioClosureFormModal from "@/Components/admin/closures/StudioClosureFormModal.vue";
import PublicHolidaysPanel from "@/Components/admin/closures/PublicHolidaysPanel.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Badge } from "@/Components/ui/badge";
import { Checkbox } from "@/Components/ui/checkbox";
import {
  Table,
  TableHeader,
  TableBody,
  TableHead,
  TableRow,
  TableCell,
} from "@/Components/ui/table";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/Components/ui/dialog";
import {
  Breadcrumb,
  BreadcrumbList,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbSeparator,
  BreadcrumbPage,
} from "@/Components/ui/breadcrumb";
import { ScrollArea } from "@/Components/ui/scroll-area";
import {
  CalendarX,
  Plus,
  Search,
  X,
  RotateCcw,
  Eye,
  Pencil,
  Trash2,
  ChevronLeft,
  ChevronRight,
  Loader2,
} from "lucide-vue-next";

const props = defineProps({
  closures: { type: Object, required: true },
  filters: { type: Object, required: true },
  publicHolidays: { type: Object, required: true },
});

const search = ref(props.filters.search || "");
const category = ref(props.filters.type || "all");
const period = ref(props.filters.period || "upcoming");
const perPage = ref(Number(props.closures.per_page) || 25);
const loading = ref(false);
const waitingForSearch = ref(false);
const busy = computed(() => loading.value || waitingForSearch.value);
const hasFilters = computed(
  () =>
    search.value !== "" ||
    category.value !== "all" ||
    period.value !== "upcoming",
);
let timer = null;
let cancelToken = null;
let revision = 0;

function syncFilters() {
  search.value = props.filters.search || "";
  category.value = props.filters.type || "all";
  period.value = props.filters.period || "upcoming";
  perPage.value = Number(props.closures.per_page) || 25;
}
watch(
  () => props.filters,
  () => {
    if (!busy.value) syncFilters();
  },
);
function stopSearch() {
  clearTimeout(timer);
  timer = null;
  waitingForSearch.value = false;
  revision += 1;
  cancelToken?.cancel();
  cancelToken = null;
  loading.value = false;
}
function loadPage(page = 1) {
  stopSearch();
  const requestRevision = revision;
  router.get(
    route("studio-closures.index"),
    {
      search: search.value.trim() || undefined,
      type: category.value === "all" ? undefined : category.value,
      period: period.value,
      per_page: perPage.value,
      page,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      only: ["closures", "filters"],
      onCancelToken: (token) => {
        cancelToken = token;
      },
      onStart: () => {
        loading.value = true;
      },
      onSuccess: () => {
        if (requestRevision === revision) syncFilters();
      },
      onError: (errors) => {
        if (requestRevision !== revision) return;
        toast.error("Recherche impossible", {
          description: Object.values(errors)[0] || "Veuillez réessayer.",
        });
      },
      onFinish: () => {
        if (requestRevision !== revision) return;
        loading.value = false;
        cancelToken = null;
      },
    },
  );
}
function handleSearch(value) {
  search.value = String(value ?? "");
  stopSearch();
  waitingForSearch.value = true;
  timer = setTimeout(() => loadPage(), 300);
}
function clearSearch() {
  search.value = "";
  loadPage();
}
function changeCategory(value) {
  category.value = value;
  loadPage();
}
function changePeriod(value) {
  period.value = value;
  loadPage();
}
function resetFilters() {
  search.value = "";
  category.value = "all";
  period.value = "upcoming";
  loadPage();
}
function changePerPage(value) {
  perPage.value = Number(value);
  loadPage();
}
function changePage(page) {
  if (busy.value || page < 1 || page > props.closures.last_page) return;
  loadPage(page);
}
onUnmounted(stopSearch);

const categoryLabel = (type) =>
  type === "school_holiday" ? "Congé scolaire" : "Fermeture atelier";
const statusLabel = (status) =>
  ({ upcoming: "À venir", ongoing: "En cours", past: "Terminé" })[status] ||
  status;
const statusClass = (status) =>
  ({
    upcoming:
      "border-blue-500/20 bg-blue-500/10 text-blue-700 dark:text-blue-300",
    ongoing:
      "border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300",
    past: "border-border bg-muted text-muted-foreground",
  })[status];
// Les dates du serveur sont des dates civiles : midi local évite la conversion UTC de YYYY-MM-DD.
const displayDate = (date) =>
  date
    ? formatDate(`${date}T12:00:00`, {
        day: "2-digit",
        month: "short",
        year: "numeric",
      })
    : "—";
const rangeLabel = (item) =>
  item.start_date === item.end_date
    ? `Le ${displayDate(item.start_date)}`
    : `Du ${displayDate(item.start_date)} au ${displayDate(item.end_date)}`;

const editorOpen = ref(false);
const editingClosure = ref(null);
const detailOpen = ref(false);
const detailClosure = ref(null);
const deleteOpen = ref(false);
const deletingClosure = ref(null);
const deleteForm = useForm({ confirmed: false });

function openEditor(item = null) {
  stopSearch();
  syncFilters();
  editingClosure.value = item;
  editorOpen.value = true;
}
function openDetail(item) {
  stopSearch();
  syncFilters();
  detailClosure.value = item;
  detailOpen.value = true;
}
function openDelete(item) {
  stopSearch();
  syncFilters();
  deleteForm.reset();
  deleteForm.clearErrors();
  deletingClosure.value = item;
  deleteOpen.value = true;
}
function changeDeleteOpen(open) {
  if (deleteForm.processing) return;
  deleteOpen.value = open;
  if (!open) deleteForm.reset();
}
function confirmDelete(value) {
  deleteForm.confirmed = value === true;
  deleteForm.clearErrors("confirmed");
}
function destroy() {
  if (!deletingClosure.value || !deleteForm.confirmed || deleteForm.processing)
    return;
  const name = deletingClosure.value.name;
  deleteForm.delete(
    route("studio-closures.destroy", deletingClosure.value.id),
    {
      preserveScroll: true,
      onSuccess: () => {
        deleteOpen.value = false;
        deleteForm.reset();
        toast.success("Période supprimée", {
          description: `La période « ${name} » a été supprimée.`,
        });
      },
      onError: (errors) =>
        toast.error("Suppression impossible", {
          description:
            Object.values(errors)[0] ||
            `La période « ${name} » n’a pas pu être supprimée.`,
        }),
    },
  );
}
</script>

<template>
  <AdminLayout title="Congés & Fermetures">
    <div class="space-y-6">
      <Breadcrumb>
        <BreadcrumbList class="gap-1.5 text-xs">
          <BreadcrumbItem
            ><BreadcrumbLink as-child
              ><Link :href="route('dashboard.index')"
                >Tableau de bord</Link
              ></BreadcrumbLink
            ></BreadcrumbItem
          >
          <BreadcrumbSeparator />
          <BreadcrumbItem
            ><BreadcrumbPage class="font-semibold"
              >Congés & Fermetures</BreadcrumbPage
            ></BreadcrumbItem
          >
        </BreadcrumbList>
      </Breadcrumb>

      <div
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
      >
        <div>
          <h2 class="text-xl font-semibold tracking-tight">
           Gestion des congés et fermetures
          </h2>
          <p class="mt-1 text-sm text-muted-foreground">
            Ajoutez, modifiez ou supprimez les périodes de congés scolaires et de
            fermetures de l’atelier.
          </p>
        </div>
        <Button class="shrink-0 gap-2" @click="openEditor()"
          ><Plus class="h-4 w-4" />Ajouter une période</Button
        >
      </div>

      <div
        class="flex flex-col justify-between gap-3.5 rounded-xl border bg-card p-4 shadow-xs lg:flex-row lg:items-center"
      >
        <div class="relative min-w-0 flex-1">
          <Search
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
          />
          <Input
            :model-value="search"
            maxlength="255"
            placeholder="Rechercher une période…"
            aria-label="Rechercher une période par son nom"
            class="h-9 bg-background pl-9 pr-9 text-xs"
            @update:model-value="handleSearch"
          />
          <Button
            v-if="search"
            type="button"
            variant="ghost"
            size="icon"
            class="absolute right-1 top-1/2 h-7 w-7 -translate-y-1/2 text-muted-foreground"
            aria-label="Effacer la recherche"
            @click="clearSearch"
            ><X class="h-3.5 w-3.5"
          /></Button>
        </div>
        <div class="flex flex-wrap items-center gap-2 sm:flex-nowrap">
          <Select :model-value="category" @update:model-value="changeCategory">
            <SelectTrigger
              class="h-9 w-[175px] bg-background text-xs"
              aria-label="Filtrer par catégorie"
              ><SelectValue
            /></SelectTrigger>
            <SelectContent align="end">
              <SelectItem value="all" class="text-xs"
                >Toutes catégories</SelectItem
              >
              <SelectItem value="school_holiday" class="text-xs"
                >Congés scolaires</SelectItem
              >
              <SelectItem value="studio_closure" class="text-xs"
                >Fermetures atelier</SelectItem
              >
            </SelectContent>
          </Select>
          <Select :model-value="period" @update:model-value="changePeriod">
            <SelectTrigger
              class="h-9 w-[180px] bg-background text-xs"
              aria-label="Filtrer par période"
              ><SelectValue
            /></SelectTrigger>
            <SelectContent align="end">
              <SelectItem value="upcoming" class="text-xs"
                >En cours et à venir</SelectItem
              >
              <SelectItem value="past" class="text-xs">Passées</SelectItem>
              <SelectItem value="all" class="text-xs"
                >Toutes les périodes</SelectItem
              >
            </SelectContent>
          </Select>
          <Button
            v-if="hasFilters"
            type="button"
            variant="ghost"
            size="sm"
            class="h-9 gap-1 px-2 text-xs text-muted-foreground"
            @click="resetFilters"
            ><RotateCcw class="h-3.5 w-3.5" />Effacer</Button
          >
        </div>
      </div>

      <div
        class="flex items-center justify-between gap-3 text-xs text-muted-foreground"
        aria-live="polite"
      >
        <span>{{ pluralize(closures.total, "résultat") }}</span>
        <span v-if="busy" class="flex items-center gap-1.5"
          ><Loader2 class="h-3.5 w-3.5 animate-spin" />Recherche…</span
        >
        <span v-else>{{
          period === "past"
            ? "Du plus récent au plus ancien"
            : "Tri par date de début"
        }}</span>
      </div>

      <div
        class="overflow-hidden rounded-xl border bg-card shadow-2xs"
        :aria-busy="busy"
      >
        <Table class="text-xs">
          <TableHeader class="border-b bg-muted/50">
            <TableRow class="hover:bg-transparent">
              <TableHead class="min-w-48 pl-4 font-semibold text-foreground"
                >Nom</TableHead
              >
              <TableHead class="font-semibold text-foreground"
                >Catégorie</TableHead
              >
              <TableHead class="whitespace-nowrap font-semibold text-foreground"
                >Début</TableHead
              >
              <TableHead class="whitespace-nowrap font-semibold text-foreground"
                >Fin</TableHead
              >
              <TableHead class="font-semibold text-foreground"
                >Statut</TableHead
              >
              <TableHead class="pr-4 text-right font-semibold text-foreground"
                >Actions</TableHead
              >
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="item in closures.data"
              :key="item.id"
              class="hover:bg-muted/40"
            >
              <TableCell class="max-w-xs py-3 pl-4">
                <button
                  type="button"
                  class="text-left font-semibold text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                  :disabled="busy"
                  @click="openDetail(item)"
                >
                  {{ item.name }}
                </button>
                <p
                  v-if="item.notes"
                  class="mt-1 line-clamp-1 break-words text-[11px] text-muted-foreground"
                >
                  {{ item.notes }}
                </p>
              </TableCell>
              <TableCell
                ><Badge
                  variant="outline"
                  class="whitespace-nowrap bg-background text-[10px] font-normal"
                  >{{ categoryLabel(item.type) }}</Badge
                ></TableCell
              >
              <TableCell class="whitespace-nowrap text-muted-foreground">{{
                displayDate(item.start_date)
              }}</TableCell>
              <TableCell class="whitespace-nowrap text-muted-foreground">{{
                displayDate(item.end_date)
              }}</TableCell>
              <TableCell
                ><Badge
                  variant="outline"
                  class="whitespace-nowrap text-[10px] font-medium"
                  :class="statusClass(item.status)"
                  >{{ statusLabel(item.status) }}</Badge
                ></TableCell
              >
              <TableCell class="pr-4">
                <div class="flex justify-end gap-1">
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8"
                    :disabled="busy"
                    :aria-label="`Voir la période ${item.name}`"
                    title="Voir le détail"
                    @click="openDetail(item)"
                    ><Eye class="h-3.5 w-3.5"
                  /></Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8"
                    :disabled="busy"
                    :aria-label="`Modifier la période ${item.name}`"
                    title="Modifier"
                    @click="openEditor(item)"
                    ><Pencil class="h-3.5 w-3.5"
                  /></Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive focus-visible:bg-destructive/10"
                    :disabled="busy"
                    :aria-label="`Supprimer la période ${item.name}`"
                    title="Supprimer"
                    @click="openDelete(item)"
                    ><Trash2 class="h-3.5 w-3.5"
                  /></Button>
                </div>
              </TableCell>
            </TableRow>
            <TableRow v-if="closures.data.length === 0">
              <TableCell :colspan="6" class="h-40 text-center">
                <CalendarX
                  class="mx-auto mb-3 h-7 w-7 text-muted-foreground/50"
                />
                <p class="font-medium">Aucune période à afficher</p>
                <p class="mt-1 text-muted-foreground">
                  Ajoutez une période ou ajustez les filtres pour consulter
                  l’historique.
                </p>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <nav
        class="flex flex-col items-center justify-between gap-3 pt-1 text-xs text-muted-foreground sm:flex-row"
        aria-label="Pagination des périodes"
      >
        <div class="flex items-center gap-2">
          <Label for="closures-per-page" class="text-xs font-normal"
            >Afficher</Label
          >
          <Select
            :model-value="String(perPage)"
            :disabled="busy"
            @update:model-value="changePerPage"
          >
            <SelectTrigger
              id="closures-per-page"
              class="h-8 w-16 cursor-pointer bg-background text-xs"
              ><SelectValue
            /></SelectTrigger>
            <SelectContent
              ><SelectItem
                v-for="size in [10, 25, 50, 100]"
                :key="size"
                :value="String(size)"
                class="cursor-pointer text-xs"
                >{{ size }}</SelectItem
              ></SelectContent
            >
          </Select>
          <span
            >par page · <strong>{{ closures.total }}</strong>
            {{ pluralize(closures.total, "résultat", null, false) }}</span
          >
        </div>
        <div class="flex items-center gap-1.5">
          <span
            >Page <strong>{{ closures.current_page }}</strong> sur
            <strong>{{ closures.last_page || 1 }}</strong></span
          >
          <div class="ml-2 flex items-center gap-1">
            <Button
              variant="outline"
              size="icon"
              class="h-8 w-8 cursor-pointer"
              aria-label="Page précédente"
              :disabled="busy || closures.current_page <= 1"
              @click="changePage(closures.current_page - 1)"
              ><ChevronLeft class="h-3.5 w-3.5"
            /></Button>
            <Button
              variant="outline"
              size="icon"
              class="h-8 w-8 cursor-pointer"
              aria-label="Page suivante"
              :disabled="busy || closures.current_page >= closures.last_page"
              @click="changePage(closures.current_page + 1)"
              ><ChevronRight class="h-3.5 w-3.5"
            /></Button>
          </div>
        </div>
      </nav>
      <PublicHolidaysPanel :holidays="publicHolidays" />
    </div>

    <StudioClosureFormModal
      v-model:open="editorOpen"
      :closure="editingClosure"
    />

    <Dialog v-model:open="detailOpen">
      <DialogContent
        class="min-w-0 max-h-[90dvh] overflow-x-hidden overflow-y-auto sm:max-w-xl"
      >
        <DialogHeader>
          <DialogTitle class="min-w-0 pr-5 [overflow-wrap:anywhere]">{{
            detailClosure?.name
          }}</DialogTitle>
          <DialogDescription
            >{{ detailClosure ? rangeLabel(detailClosure) : "" }} · Dates
            incluses</DialogDescription
          >
        </DialogHeader>
        <template v-if="detailClosure">
          <div class="flex flex-wrap gap-2">
            <Badge variant="outline">{{
              categoryLabel(detailClosure.type)
            }}</Badge>
            <Badge
              variant="outline"
              :class="statusClass(detailClosure.status)"
              >{{ statusLabel(detailClosure.status) }}</Badge
            >
          </div>
          <div class="space-y-2">
            <h3 class="text-sm font-medium">Notes</h3>
            <ScrollArea
              v-if="detailClosure.notes"
              class="h-56 w-full min-w-0 overflow-hidden rounded-lg border bg-muted/20 [&_[data-reka-scroll-area-viewport]>div]:!block [&_[data-radix-scroll-area-viewport]>div]:!block"
            >
              <p
                class="w-full min-w-0 whitespace-pre-wrap p-4 pr-5 text-sm leading-relaxed text-muted-foreground [overflow-wrap:anywhere]"
              >
                {{ detailClosure.notes }}
              </p>
            </ScrollArea>
            <p v-else class="text-sm text-muted-foreground">
              Aucune note pour cette période.
            </p>
          </div>
        </template>
        <DialogFooter
          ><Button type="button" variant="outline" @click="detailOpen = false"
            >Fermer</Button
          ></DialogFooter
        >
      </DialogContent>
    </Dialog>

    <Dialog :open="deleteOpen" @update:open="changeDeleteOpen">
      <DialogContent
        class="sm:max-w-lg"
        @escape-key-down="deleteForm.processing && $event.preventDefault()"
        @interact-outside="deleteForm.processing && $event.preventDefault()"
      >
        <form class="space-y-5" @submit.prevent="destroy">
          <DialogHeader>
            <DialogTitle>Supprimer cette période ?</DialogTitle>
            <DialogDescription class="leading-relaxed"
              >La période « {{ deletingClosure?.name }} » sera supprimée
              définitivement.</DialogDescription
            >
          </DialogHeader>
          <p
            v-if="deletingClosure"
            class="rounded-lg border bg-muted/30 p-3 text-sm"
          >
            {{ rangeLabel(deletingClosure) }}
          </p>
          <div class="flex items-start gap-3">
            <Checkbox
              id="confirm-closure-delete"
              :checked="deleteForm.confirmed"
              :model-value="deleteForm.confirmed"
              :disabled="deleteForm.processing"
              aria-describedby="closure-delete-error"
              @update:checked="confirmDelete"
              @update:model-value="confirmDelete"
            />
            <Label
              for="confirm-closure-delete"
              class="cursor-pointer text-sm leading-relaxed"
              >Je confirme la suppression définitive de cette période.</Label
            >
          </div>
          <p
            id="closure-delete-error"
            role="alert"
            class="text-xs text-destructive"
          >
            {{ deleteForm.errors.confirmed }}
          </p>
          <DialogFooter class="gap-2 border-t pt-4">
            <Button
              type="button"
              variant="outline"
              :disabled="deleteForm.processing"
              @click="changeDeleteOpen(false)"
              >Annuler</Button
            >
            <Button
              type="submit"
              variant="destructive"
              class="gap-2"
              :disabled="!deleteForm.confirmed || deleteForm.processing"
              ><Loader2
                v-if="deleteForm.processing"
                class="h-4 w-4 animate-spin"
              />{{
                deleteForm.processing ? "Suppression…" : "Supprimer la période"
              }}</Button
            >
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </AdminLayout>
</template>
