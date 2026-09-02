import { h, inject } from "vue";
import type { ColumnDef } from "@tanstack/vue-table";
import { Badge } from "@/Components/ui/badge";
import { Checkbox } from "@/Components/ui/checkbox";
import UserRowActions from "./UserRowActions.vue";
import DataTableColumnHeader from "@/Components/data-table/DataTableColumnHeader.vue";

export interface User {
    id: number | string;
    first_name: string;
    last_name: string;
    email: string;
    phone_number: string;
    modules?: Array<{
        id: number | string;
        remaining_lessons: number;
        total_lessons: number;
    }>;
    attendees?: Array<{
        id: number | string;
        first_name: string;
        last_name: string;
    }>;
}

export const columns: ColumnDef<User>[] = [
    {
        id: "select",
        header: ({ table }) => {
            return h(
                "div",
                { class: "px-1" },
                h(Checkbox, {
                    modelValue: table.getIsAllPageRowsSelected(),
                    "onUpdate:modelValue": (value) =>
                        table.toggleAllPageRowsSelected(!!value),
                    ariaLabel: "Tout sélectionner",
                    class: "translate-y-[2px]",
                })
            );
        },
        cell: ({ row }) => {
            return h(
                "div",
                { class: "px-1" },
                h(Checkbox, {
                    modelValue: row.getIsSelected(),
                    "onUpdate:modelValue": (value) =>
                        row.toggleSelected(!!value),
                    ariaLabel: "Sélectionner la ligne",
                    class: "translate-y-[2px]",
                })
            );
        },
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: "id",
        header: ({ column }) => {
            const serverSort = inject("serverSideSort", null);

            return h(DataTableColumnHeader, {
                label: "ID",
                sorted: serverSort
                    ? serverSort.getIsSorted("id")
                    : column.getIsSorted(),
                onSort: (descending) => {
                    if (serverSort) {
                        serverSort.toggleSorting("id", descending);
                    } else {
                        column.toggleSorting(descending);
                    }
                },
            });
        },
        enableHiding: true,
    },
    {
        accessorKey: "first_name",
        header: ({ column }) => {
            const serverSort = inject("serverSideSort", null);

            return h(DataTableColumnHeader, {
                label: "Prénom",
                sorted: serverSort
                    ? serverSort.getIsSorted("first_name")
                    : column.getIsSorted(),
                onSort: (descending) => {
                    if (serverSort) {
                        serverSort.toggleSorting("first_name", descending);
                    } else {
                        column.toggleSorting(descending);
                    }
                },
            });
        },
        enableHiding: true,
    },
    {
        accessorKey: "last_name",
        header: ({ column }) => {
            const serverSort = inject("serverSideSort", null);

            return h(DataTableColumnHeader, {
                label: "Nom",
                sorted: serverSort
                    ? serverSort.getIsSorted("last_name")
                    : column.getIsSorted(),
                onSort: (descending) => {
                    if (serverSort) {
                        serverSort.toggleSorting("last_name", descending);
                    } else {
                        column.toggleSorting(descending);
                    }
                },
            });
        },
        enableHiding: true,
    },
    {
        accessorKey: "email",
        header: ({ column }) => {
            const serverSort = inject("serverSideSort", null);

            return h(DataTableColumnHeader, {
                label: "Email",
                sorted: serverSort
                    ? serverSort.getIsSorted("email")
                    : column.getIsSorted(),
                onSort: (descending) => {
                    if (serverSort) {
                        serverSort.toggleSorting("email", descending);
                    } else {
                        column.toggleSorting(descending);
                    }
                },
            });
        },
        enableHiding: true,
    },
    {
        accessorKey: "phone_number",
        header: ({ column }) => {
            const serverSort = inject("serverSideSort", null);

            return h(DataTableColumnHeader, {
                label: "Téléphone",
                sorted: serverSort
                    ? serverSort.getIsSorted("phone_number")
                    : column.getIsSorted(),
                onSort: (descending) => {
                    if (serverSort) {
                        serverSort.toggleSorting("phone_number", descending);
                    } else {
                        column.toggleSorting(descending);
                    }
                },
            });
        },
        enableHiding: true,
    },
    {
        id: "modules",
        header: ({ column }) => {
            const serverSort = inject("serverSideSort", null);

            return h(DataTableColumnHeader, {
                label: "Modules",
                sorted: serverSort
                    ? serverSort.getIsSorted("modules_count")
                    : column.getIsSorted(),
                onSort: (descending) => {
                    if (serverSort) {
                        serverSort.toggleSorting("modules_count", descending);
                    } else {
                        column.toggleSorting(descending);
                    }
                },
            });
        },
        accessorFn: (row) => row.modules?.length || 0,
        cell: ({ row }) => {
            const mods = row.original.modules;
            return mods && mods.length
                ? mods.map((module) =>
                      h(
                          "div",
                          { key: module.id, class: "flex items-center gap-1" },
                          [
                              h(
                                  Badge,
                                  { variant: "outline", class: "bg-blue-50" },
                                  () => `Module #${module.id}` // Transformer le contenu en fonction de rendu
                              ),
                              ` ${module.remaining_lessons}/${module.total_lessons} séances`,
                          ]
                      )
                  )
                : "Aucun module";
        },
        enableHiding: true,
    },
    {
        id: "attendees",
        header: ({ column }) => {
            const serverSort = inject("serverSideSort", null);

            return h(DataTableColumnHeader, {
                label: "Invités",
                sorted: serverSort
                    ? serverSort.getIsSorted("attendees_count")
                    : column.getIsSorted(),
                onSort: (descending) => {
                    if (serverSort) {
                        serverSort.toggleSorting("attendees_count", descending);
                    } else {
                        column.toggleSorting(descending);
                    }
                },
            });
        },
        accessorFn: (row) => row.attendees?.length || 0,
        cell: ({ row }) => {
            const atts = row.original.attendees;
            return atts && atts.length
                ? atts.map((attendee) =>
                      h(
                          Badge,
                          { key: attendee.id, variant: "secondary" },
                          () => `${attendee.first_name} ${attendee.last_name}` // Transformer le contenu en fonction de rendu
                      )
                  )
                : "Aucun invité";
        },
        enableHiding: true,
    },
    {
        id: "actions",
        header: () => h("div", { class: "text-right" }, "Actions"),
        cell: ({ row }) => {
            const user = row.original;
            return h("div", { class: "text-right" }, [
                h(UserRowActions, { user }),
            ]);
        },
        enableSorting: false,
        enableHiding: false,
    },
];
