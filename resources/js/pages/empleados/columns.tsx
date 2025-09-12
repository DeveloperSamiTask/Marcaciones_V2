"use client"
import { ColumnDef } from "@tanstack/react-table"
import { Button } from "@/components/ui/button"
import { ArrowUpDown, SquarePen } from "lucide-react"
import { Empleado } from '@/types/empleados'
import { Link } from "@inertiajs/react"
import ModalEmpleado from "./deleteModal"
import { Badge } from "@/components/ui/badge"
import { format } from "date-fns"

export const columns = (cesado: number): ColumnDef<Empleado>[] => [
    {
        accessorKey: "id",
        header: "CODIGO",
    },
    {
        accessorKey: "dni",
        header: "DNI",
    },
    {
        accessorKey: "apellidos",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                >
                    EMPLEADO
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            )
        },
        cell: ({ row }) => `${row.original.apellidos} ${row.original.nombres}`
    },
    {
        accessorKey: "empresa.razonsocial",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                >
                    EMPRESA
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            )
        },
        cell: ({ row }) => row.original.empresa.razonsocial
    },
    {
        accessorKey: "area.nombre",
        header: "AREA",
        cell: ({ row }) => row.original.area?.nombre
    },
    {
        accessorKey: "jornada",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                >
                    JORNADA
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            )
        },
        cell: ({ row }) => row.original.jornada.nombre
    },
    {
        accessorKey: "cargo",
        header: "CARGO",
    },
    {
        accessorKey: "fecha_ingreso",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                >
                    INGRESO
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            )
        },
        cell: ({ row }) => format(row.original.fecha_ingreso, 'dd/MM/yyyy')
    },
    {
        accessorKey: "jefe",
        header: "JEFE",
        cell: ({ row }) => row.original.jefe ? `${row.original.jefe.apellidos} ${row.original.jefe.nombres}` : 'Sin jefe'
    },
    {
        accessorKey: "fecha_cese",
        header: "ESTADO",
        cell: ({ row }) => {
            const estado = row.original.fecha_cese
            return (!estado ? <Badge>ACTIVO</Badge> : <Badge variant="destructive">INACTIVO</Badge>)
        },
    },
    {
        id: "actions",
        cell: ({ row }) => {
            const empleado = row.original;

            return (
                <div className="flex items-center gap-2">
                    <Button asChild key={`edit-empleado-${empleado.id}`} size="sm">
                        <Link href={route("empleados.edit", empleado.id)}>
                            <SquarePen />
                        </Link>
                    </Button>

                    <ModalEmpleado
                        key={`modal-empleado-${empleado.id}`}
                        empleadoId={empleado.id}
                        tipoMovimiento={cesado === 1 ? "reactivacion" : "cese"}
                    />
                </div>
            );
        },
    },
];
