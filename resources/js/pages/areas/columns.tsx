"use client"
import { ColumnDef } from "@tanstack/react-table"
import { Button } from "@/components/ui/button"
import { ArrowUpDown, SquarePen } from "lucide-react"
import { Area } from '@/types/areas'
import DeleteArea from "./delete"
import { Link } from "@inertiajs/react"

export const columns: ColumnDef<Area>[] = [
  {
    accessorKey: "id",
    header: "CODIGO",
  },
  {
    accessorKey: "nombre",
    header: ({ column }) => {
        return (
          <Button
            variant="ghost"
            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          >
            NOMBRE
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        )
    },
  },
  {
    accessorKey: "empleado.dni",
    header: "DNI",
  },
  {
    accessorKey: "empleado.apellidos",
    header: ({ column }) => {
        return (
          <Button
            variant="ghost"
            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          >
            ENCARGADO
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        )
    },
    cell: ({ row }) => {
      const encargado = row.original.empleado
      return <span>{encargado.apellidos} {encargado.nombres}</span>
    },
  },
  {
    id: "actions",
    cell: ({ row }) => {
      const area = row.original

      return (
        <div className="flex items-center gap-2">
            <Button asChild key={`edit-area-${area.id}`} size="sm">
                <Link href={route('areas.edit', area.id)}>
                    <SquarePen/>
                </Link>
            </Button>
            <DeleteArea key={`delete-area-${area.id}`} areaId = {area.id} />
        </div>
      )
    },
  },
]
