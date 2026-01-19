'use client';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Marcacion } from '@/types/marcaciones';
import {
    ColumnDef,
    ColumnFiltersState,
    FilterFn,
    SortingState,
    flexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';
import { ChevronLeftIcon, ChevronRightIcon, ChevronsLeftIcon, ChevronsRightIcon } from 'lucide-react';
import { useState, forwardRef, useImperativeHandle, useEffect, useMemo } from 'react';

interface DataTableProps<TValue> {
    columns: ColumnDef<Marcacion, TValue>[];
    data: Marcacion[];
    filters?: any;
}

export interface DataTableRef {
    getSelectedData: () => Marcacion[];
}

export const DataTable = forwardRef<DataTableRef, DataTableProps<TValue>>(({ columns, data,filters }, ref) => {

    const [rowSelection, setRowSelection] = useState({});
    const [sorting, setSorting] = useState<SortingState>([]);
    const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
    const [globalFilter, setGlobalFilter] = useState('');

    const fuzzyFilter: FilterFn<any> = (row, columnId, value) => {
        // busqueda avanzada en la tabla, busca cualquier campo de la tabla
        const search = value.toLowerCase();
        return Object.values(row.original).join(' ').toLowerCase().includes(search);
    };

    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        onSortingChange: setSorting,
        getSortedRowModel: getSortedRowModel(),
        onColumnFiltersChange: setColumnFilters,
        getFilteredRowModel: getFilteredRowModel(),
        onGlobalFilterChange: setGlobalFilter,
        onRowSelectionChange: setRowSelection,
        filterFns: {
            fuzzy: fuzzyFilter,
        },
        // 3. AQUÍ ES DONDE SUCEDE LA MAGIA: El casillero META
        meta: {
            filters: filters,
        },
        state: {
            rowSelection,
            sorting,
            columnFilters,
            globalFilter,
        },
        initialState: {
            pagination: {
                pageSize: 10, // Items por página por defecto
            },
        },
    });

    useImperativeHandle(ref, () => ({
        getSelectedData: () => {
            return table.getSelectedRowModel().flatRows.map(row => row.original);
        }
    }));

    return (
        <div>
            <div className="flex items-center justify-between gap-3 py-4">
                <Input placeholder="Buscar" value={globalFilter} onChange={(e) => setGlobalFilter(e.target.value)} className="max-w-sm" />
                <div className="flex items-center gap-2">
                    <Select
                        value={`${table.getState().pagination.pageSize}`}
                        onValueChange={(value) => {
                            table.setPageSize(Number(value));
                        }}
                    >
                        <SelectTrigger className="h-9 w-[80px] rounded-xl">
                            <SelectValue placeholder={table.getState().pagination.pageSize} />
                        </SelectTrigger>
                        <SelectContent>
                            {[10, 20, 30, 40, 50].map((pageSize) => (
                                <SelectItem key={pageSize} value={`${pageSize}`}>
                                    {pageSize}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div className="rounded-xl border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <TableHead className="bg-card" key={header.id}>
                                        {flexRender(header.column.columnDef.header, header.getContext())}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows?.length ? (
                            table.getRowModel().rows.map((row) => {
                                //const anticipado = row.original.anticipado > 0;
                                const anticipado = !!row.original.anticipado && row.original.anticipado !== '00:00' && row.original.anticipado !== 0;

                                const horarioEstado = row.original.horario?.estado ?? '';
                                const esDescanso = ['D', 'C', 'CA', 'V'].includes(horarioEstado);
                                const esFeriado = ['F', 'FL', 'DM', 'PE'].includes(horarioEstado);
                                const esSuspension = ['S', 'FI', 'FJ', ''].includes(horarioEstado);

                                const getRowClass = () => {
                                    if (anticipado) return "bg-violet-500/30 hover:bg-violet-500/40 dark:bg-violet-600/20 dark:hover:bg-violet-600/30";
                                    if (esDescanso) return "bg-blue-500/30 hover:bg-blue-500/40 dark:bg-blue-500/20 dark:hover:bg-blue-500/30";
                                    if (esFeriado) return "bg-yellow-400/20 hover:bg-yellow-400/30 dark:bg-yellow-400/10 dark:hover:bg-yellow-400/20";
                                    if (esSuspension) return "bg-red-500/30 hover:bg-red-500/40 dark:bg-red-500/10 dark:hover:bg-red-500/20";
                                    if (horarioEstado == 'L' && !row.original.marcacion) return "bg-red-500/30 hover:bg-red-500/40 dark:bg-red-500/10 dark:hover:bg-red-500/20";
                                    return "hover:bg-gray-200 dark:hover:bg-neutral-800";
                                };

                                return (
                                    <TableRow
                                        key={row.id}
                                        data-state={row.getIsSelected() && 'selected'}
                                        className={getRowClass()}
                                    >
                                        {row.getVisibleCells().map((cell) => (
                                            <TableCell key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</TableCell>
                                        ))}
                                    </TableRow>
                                )
                            })
                        ) : (
                            <TableRow>
                                <TableCell colSpan={columns.length} className="h-24 text-center">
                                    No hay resultados.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Controles de paginación */}
            <div className="flex flex-col items-center justify-between gap-3 px-2 py-4 sm:flex-row">
                <div className="text-muted-foreground text-sm">
                    Mostrando {table.getRowModel().rows.length} de {table.getFilteredRowModel().rows.length} registros.
                </div>

                <div className="ml-auto flex items-center gap-2 lg:ml-0">
                    <Button
                        variant="outline"
                        className="hidden h-8 w-8 p-0 lg:flex"
                        onClick={() => table.setPageIndex(0)}
                        disabled={!table.getCanPreviousPage()}
                    >
                        <span className="sr-only">Go to first page</span>
                        <ChevronsLeftIcon />
                    </Button>
                    <Button
                        variant="outline"
                        className="size-8"
                        size="icon"
                        onClick={() => table.previousPage()}
                        disabled={!table.getCanPreviousPage()}
                    >
                        <span className="sr-only">Go to previous page</span>
                        <ChevronLeftIcon />
                    </Button>
                    <Button variant="outline" className="size-8" size="icon" onClick={() => table.nextPage()} disabled={!table.getCanNextPage()}>
                        <span className="sr-only">Go to next page</span>
                        <ChevronRightIcon />
                    </Button>
                    <Button
                        variant="outline"
                        className="hidden size-8 lg:flex"
                        size="icon"
                        onClick={() => table.setPageIndex(table.getPageCount() - 1)}
                        disabled={!table.getCanNextPage()}
                    >
                        <span className="sr-only">Go to last page</span>
                        <ChevronsRightIcon />
                    </Button>
                </div>
            </div>
        </div>
    );
}
);
