'use client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Encargado } from '@/types/encargados';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, SquarePen } from 'lucide-react';
import ModalUsuarios from './deleteModal';
import { format } from "date-fns";

export const columns = (estado: number): ColumnDef<Encargado>[] => [
    {
        accessorKey: 'id',
        header: 'CODIGO',
    },
    {
        accessorKey: 'empleado.dni',
        header: 'DNI',
    },
    {
        accessorKey: 'empleado.apellidos',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    ENCARGADO
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            const encargado = row.original.empleado;
            return (
                <span>
                    {encargado.apellidos} {encargado.nombres}
                </span>
            );
        },
    },
    {
        accessorKey: 'name',
        header: 'NOMBRE',
    },
    {
        accessorKey: 'rol.nombre',
        header: 'ROL',
    },
    {
        accessorKey: 'email',
        header: 'CORREO',
    },
    {
        accessorKey: 'estado',
        header: 'ESTADO',
        cell: ({ row }) => row.original.estado ? (<Badge variant="default"> ACTIVO </Badge>) : (<Badge variant="destructive"> INACTIVO </Badge>)
    },

    // COLUMNA CESE - SOLO CUANDO cesado = 1
    ...(estado === 0 ? [{
        accessorKey: "empleado.fecha_cese",
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                >
                    CESE
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            const fechaCese = row.original.empleado?.fecha_cese;
            if (!fechaCese) {
                return null;
            }
            return format(fechaCese, 'dd/MM/yyyy');
        }
    }] : []),

    {
        id: 'actions',
        cell: ({ row }) => {
            const usuario = row.original;

            return (
                <div className="flex items-center gap-2">
                    <Button asChild key={`edit-usuario-${usuario.id}`} size="sm">
                        <Link href={route('usuarios.edit', usuario.id)}>
                            <SquarePen />
                        </Link>
                    </Button>

                    {/*  0 es cesado , 1 activado*/}
                    <ModalUsuarios
                        key={`modal-usuario-${usuario.id}`}
                        usuarioId={usuario.id}
                        tipoMovimiento={estado === 1 ? "archivado" : "reactivacion"}
                    />
                </div>
            );
        },
    },
];
