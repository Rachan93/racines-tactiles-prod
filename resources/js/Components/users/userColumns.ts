import { h, inject } from "vue";
import type { ColumnDef } from "@tanstack/vue-table";
import { Badge } from "@/Components/ui/badge";
import { Checkbox } from "@/Components/ui/checkbox";
import UserRowActions from "./UserRowActions.vue";
import DataTableColumnHeader from "@/Components/data-table/DataTableColumnHeader.vue";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";

// Interface utilisateur
export interface User {
    id: number | string;
    first_name: string;
    last_name: string;
    email: string;
    phone_number: string;
    birthday?: string | null;
    attendees?: Array<{
        id: number | string;
        first_name: string;
        last_name: string;
    }>;
    created_at?: string;
}

// Colonnes pour les utilisateurs
export const userColumns: ColumnDef<User>[] = [
    {
        id: "select",
        header: ({ table }) => {
            return h("div", { class: "px-1" }, [
                h(
                    TooltipProvider,
                    {},
                    {
                        default: () => [
                            h(
                                Tooltip,
                                {},
                                {
                                    default: () => [
                                        h(
                                            TooltipTrigger,
                                            { asChild: true },
                                            {
                                                default: () => [
                                                    h(Checkbox, {
                                                        modelValue:
                                                            table.getIsAllPageRowsSelected(),
                                                        "onUpdate:modelValue": (
                                                            value
                                                        ) =>
                                                            table.toggleAllPageRowsSelected(
                                                                !!value
                                                            ),
                                                        ariaLabel:
                                                            "Tout sélectionner",
                                                        class: "translate-y-[2px]",
                                                    }),
                                                ],
                                            }
                                        ),
                                        h(
                                            TooltipContent,
                                            { class: "max-w-xs" },
                                            {
                                                default: () =>
                                                    "Sélectionner tous les éléments de cette page",
                                            }
                                        ),
                                    ],
                                }
                            ),
                        ],
                    }
                ),
            ]);
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
        meta: { label: "Sélection" },
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
        meta: { label: "Nom" },
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
        meta: { label: "Prénom" },
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
        meta: { label: "Email" },
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
        meta: { label: "Téléphone" },
    },
    {
        id: "birthday",
        header: ({ column }) => {
            const serverSort = inject("serverSideSort", null);
            return h(DataTableColumnHeader, {
                label: "Date de naissance",
                sorted: serverSort
                    ? serverSort.getIsSorted("birthday")
                    : column.getIsSorted(),
                onSort: (descending) => {
                    if (serverSort) {
                        serverSort.toggleSorting("birthday", descending);
                    } else {
                        column.toggleSorting(descending);
                    }
                },
            });
        },
        cell: ({ row }) => {
            const birthday = row.original.birthday;
            if (!birthday) {
                return h("span", {}, "Non spécifié");
            }
            // Formater la date de naissance
            const date = new Date(birthday);
            return h("span", {}, date.toLocaleDateString("fr-FR"));
        },
        enableHiding: true,
        meta: { label: "Date de naissance" },
    },
    {
        id: "attendees",
        header: ({ column }) => {
            const serverSort = inject("serverSideSort", null);
            return h(DataTableColumnHeader, {
                label: "Accompagnants",
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
                          () => `${attendee.last_name} ${attendee.first_name}`
                      )
                  )
                : h("span", {}, "Aucun accompagnant");
        },
        enableHiding: true,
        meta: { label: "Accompagnants" },
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
        meta: { label: "Actions" },
    },
];
