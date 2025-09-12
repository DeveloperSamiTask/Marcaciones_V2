"use client"
import { ColumnDef } from "@tanstack/react-table"
import { Button } from "@/components/ui/button"
import { ArrowUpDown, SquarePen } from "lucide-react"
import { Badge } from "@/components/ui/badge"
import { Link } from "@inertiajs/react"
import { Empresa } from "@/types/empresas"
import DeleteEmpresa from "./delete"

export const columns: ColumnDef<Empresa>[] = [
  {
    accessorKey: "id",
    header: "CODIGO",
  },
  {
    accessorKey: "ruc",
    header: "RUC",
  },
  {
    accessorKey: "razonsocial",
    header: ({ column }) => {
        return (
          <Button
            variant="ghost"
            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          >
            RAZON SOCIAL
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        )
    },
  },
  {
    accessorKey: "direccion",
    header: "DIRECCION",
  },
  {
    accessorKey: "estado",
    header: "ESTADO",
    cell: ({ row }) => {
      return(row.original.estado ? <Badge>ACTIVO</Badge> : <Badge variant="destructive">INACTIVO</Badge>)
    },
  },
  {
    id: "actions",
    cell: ({ row }) => {
      const empresa = row.original

      return (
        <div className="flex items-center gap-2">
            <Button asChild key={`edit-empresa-${empresa.id}`} size="sm">
                <Link href={route('empresas.edit', empresa.id)}>
                    <SquarePen/>
                </Link>
            </Button>
            <DeleteEmpresa key={`delete-empresa-${empresa.id}`} empresaId = {empresa.id} />
        </div>
      )
    },
  },
]
