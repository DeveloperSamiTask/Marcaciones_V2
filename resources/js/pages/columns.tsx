"use client"
import { ColumnDef } from "@tanstack/react-table"
import { Button } from "@/components/ui/button"
import { ArrowUpDown, BadgeCheckIcon, CircleX, Loader } from "lucide-react"
import { Empleado } from '@/types/empleados'
import { Badge } from "@/components/ui/badge"

const formatMinutes = (minutes: number | false): string => {
  if (typeof minutes !== 'number') return '-';

  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;

  return `${String(hours).padStart(2, '0')}:${String(remainingMinutes).padStart(2, '0')}`;
};

export const columns: ColumnDef<Empleado>[] = [
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
    cell: ({ row }) => row.original.empresa?.razonsocial
  },
  {
    accessorKey: "area.nombre",
    header: ({ column }) => {
      return (
        <Button
          variant="ghost"
          onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
        >
          AREA
          <ArrowUpDown className="ml-2 h-4 w-4" />
        </Button>
      )
    },
    cell: ({ row }) => row.original.area?.nombre
  },
  {
    accessorKey: "cargo",
    header: "CARGO",
  },
  {
    accessorKey: "horas",
    header: "HORAS",
    cell: ({ row }) => <span className="font-semibold text-green-500">{ formatMinutes(row.original.horas_trabajadas ?? 0) }</span>
  },
  {
    accessorKey: "porcentaje",
    header: "PORCENTAJE",
    cell: ({ row }) => {
        const horas = row.original.horas;
        const horas_trabajadas = (row.original.horas_trabajadas ?? 0) / 60;
        return ( <span className="font-semibold text-violet-500"> { horas > 0 ? ((horas_trabajadas / horas) * 100).toFixed(2) : 0 }% </span> )
    }
  },
  {
    accessorKey: "estado",
    header: "ESTADO",
    cell: ({ row }) => {
        const horas = row.original.horas;
        const horas_trabajadas = (row.original.horas_trabajadas ?? 0) / 60;
        return (
            horas_trabajadas < horas ?
            (
                <Badge variant="outline" >
                    <Loader />
                    En Progreso
                </Badge>
            ) : horas_trabajadas > horas ?
            (
                <Badge variant="destructive" >
                    <CircleX />
                    Exceso
                </Badge>
            ) :
            (
                <Badge variant="success" >
                    <BadgeCheckIcon />
                    Completado
                </Badge>
            )
        )
    },
  },
]
