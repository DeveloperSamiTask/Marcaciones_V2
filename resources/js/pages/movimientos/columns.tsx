import { ColumnDef } from "@tanstack/react-table";
import { format } from "date-fns";
import { ArrowUpDown } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Movimiento } from "@/types/movimientos"; // ajusta si tienes otro tipo
import { useState } from "react";
import MovimientoModal from "./movimientoModal";

export const columnsMovimiento: ColumnDef<Movimiento>[] = [
    {
        accessorKey: "id",
        header: "CÓDIGO",
        cell: ({ row }) => row.original.id,
    },
    {
        accessorKey: "empleado",
        header: "EMPLEADO",
        cell: ({ row }) => row.original.empleado.toUpperCase(),
    },
    {
        accessorKey: "dni",
        header: "DNI",
    },
    {
        accessorKey: "fecha_movimiento",
        header: ({ column }) => (
            <Button
                variant="ghost"
                onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                className="uppercase"
            >
                FECHA MOVIMIENTO
                <ArrowUpDown className="ml-2 h-4 w-4" />
            </Button>
        ),
        cell: ({ row }) =>
            format(new Date(row.original.fecha_movimiento), "dd/MM/yyyy"),
    },
    {
        accessorKey: "motivo",
        header: "MOTIVO",
        cell: ({ row }) => (
            <div className="max-w-[200px] truncate text-sm text-muted-foreground text-white" >
                {row.original.motivo}
            </div>
        ),
    },

    {
        accessorKey: "tipo_movimiento",
        header: "TIPO",
        cell: ({ row }) => {
            const tipo = row.original.tipo_movimiento;
            return (
                <Badge variant={tipo === "cese" ? "destructive" : "default"}>
                    {tipo.toUpperCase()}
                </Badge>
            );
        },
    },
    {
        accessorKey: "fecha_cese_actual",
        header: "F. CESE ACTUAL",
        cell: ({ row }) =>
            row.original.fecha_cese_actual
                ? format(new Date(row.original.fecha_cese_actual), "dd/MM/yyyy")
                : "—",
    },
    {
        accessorKey: "fecha_activacion_actual",
        header: "F. ACTIVACIÓN ACTUAL",
        cell: ({ row }) =>
            row.original.fecha_activacion_actual
                ? format(new Date(row.original.fecha_activacion_actual), "dd/MM/yyyy")
                : "—",
    },
    {
        id: "actions",
        header: "ACCIONES",
        cell: ({ row }) => <MovimientoActions movimiento={row.original} />,
    },
];


type MovimientoActionsProps = {
    movimiento: Movimiento;
};

function MovimientoActions({ movimiento }: MovimientoActionsProps) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <Button size="sm" onClick={() => setOpen(true)}>
                Ver detalle
            </Button>
            <MovimientoModal
                movimiento={movimiento}
                open={open}
                onClose={() => setOpen(false)}
            />
        </>
    );
}
