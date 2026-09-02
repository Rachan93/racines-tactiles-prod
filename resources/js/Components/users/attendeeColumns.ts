import { h, inject } from "vue";
import type { ColumnDef } from "@tanstack/vue-table";
import { Badge } from "@/Components/ui/badge";
import { Checkbox } from "@/Components/ui/checkbox";
import AttendeeRowActions from "./AttendeeRowActions.vue";
import DataTableColumnHeader from "@/Components/data-table/DataTableColumnHeader.vue";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";

// Interface accompagnant
export interface Attendee {
    id: number | string;
    first_name: string;
    last_name: string;
    birthday?: string | null;
    user?: {
        id: number | string;
        first_name: string;
        last_name: string;
        email: string;
    };
    created_at?: string;
}

// Colonnes pour les accompagnants
export const attendeeColumns: ColumnDef<Attendee>[] = [
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
        id: "user",
        header: ({ column }) => {
            const serverSort = inject("serverSideSort", null);
            return h(DataTableColumnHeader, {
                label: "Responsable",
                sorted: serverSort
                    ? serverSort.getIsSorted("user_name")
                    : column.getIsSorted(),
                onSort: (descending) => {
                    if (serverSort) {
                        serverSort.toggleSorting("user_name", descending);
                    } else {
                        column.toggleSorting(descending);
                    }
                },
            });
        },
        cell: ({ row }) => {
            const user = row.original.user;
            return user
                ? h("div", {}, `${user.last_name} ${user.first_name}`)
                : "";
        },
        enableHiding: true,
        meta: { label: "Responsable" },
    },
    {
        id: "actions",
        header: () => h("div", { class: "text-right" }, "Actions"),
        cell: ({ row }) => {
            const attendee = row.original;
            return h("div", { class: "text-right" }, [
                h(AttendeeRowActions, { attendee }),
            ]);
        },
        enableSorting: false,
        enableHiding: false,
        meta: { label: "Actions" },
    },
];
