import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import {  useEffect, useState } from 'react';
import { parseISO } from 'date-fns';
import { SelectFilter } from '@/components/select-filter';
import { DateRangeFilter } from '@/components/date-range';

// Importamos solo lo que SI tienes en la carpeta ui
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";

type RecalcularButtonProps = {
    empresa: number | null;
    fechaInicio?: string;
    fechaFin?: string;
    disabled?: boolean;
};

export function RecalcularButton({ empresa, fechaInicio, fechaFin, empresas, encargados, disabled }: any) {

    const [isLoading, setIsLoading] = useState(false);
    const [open, setOpen] = useState(false); // Para cerrar el modal manualmente

    const [localEmpresa, setLocalEmpresa] = useState<number | null>(empresa);
    const [localDateRange, setLocalDateRange] = useState<DateRange | undefined>({
        from: fechaInicio ? parseISO(fechaInicio) : undefined,
        to: fechaFin ? parseISO(fechaFin) : undefined,
    });

    // Sincronizar cuando las props cambien (si el usuario cambia el filtro afuera mientras el modal está cerrado)
    useEffect(() => {
        if (!open) {
            setLocalEmpresa(empresa);
            setLocalDateRange({
                from: fechaInicio ? parseISO(fechaInicio) : undefined,
                to: fechaFin ? parseISO(fechaFin) : undefined,
            });
        }
    }, [empresa, fechaInicio, fechaFin, open]);



    const handleConfirmar = () => {
        if (!localEmpresa || !localDateRange?.from || !localDateRange?.to) return;

        setIsLoading(true);
        router.post(route('marcaciones.recalcular-extras'), {
            empresa: localEmpresa,
            fechaInicio: localDateRange.from.toISOString().split('T')[0],
            fechaFin: localDateRange.to.toISOString().split('T')[0],
        }, {
            onFinish: () => {
                setIsLoading(false);
                setOpen(false);
            }
        });
    };

    const isDisabled = disabled || !empresa || !fechaInicio || !fechaFin || isLoading;

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="destructive" size="sm" disabled={disabled} className="gap-2">
                    <RefreshCw className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`} />
                    Recalcular HE
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Configuración de Recálculo de HE</DialogTitle>
                    <DialogDescription>
                        Confirma o ajusta los parámetros antes de procesar las horas extras.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-4 py-4">
                    {/* Reutilizamos tus componentes de filtro para que se vea IGUAL */}
                    <div className="space-y-2">
                        <label className="text-xs font-bold uppercase">Empresa objetivo:</label>
                        <SelectFilter
                            items={empresas}
                            selected={localEmpresa}
                            onSelect={setLocalEmpresa}
                            getValue={(e) => e.id}
                            displayValue={(e) => e.razonsocial}
                            placeholder="SELECCIONAR EMPRESA"
                        />
                    </div>

                    <div className="space-y-2">
                        <label className="text-xs font-bold uppercase">Rango de fechas a procesar:</label>
                        <DateRangeFilter
                            dateRange={localDateRange}
                            setDateRange={setLocalDateRange}
                            placeholder="SELECCIONAR RANGO"
                        />
                    </div>


                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => setOpen(false)} disabled={isLoading}>
                        Cancelar
                    </Button>
                    <Button onClick={handleConfirmar} disabled={isLoading || !localEmpresa || !localDateRange?.to}>
                        {isLoading ? 'Calculando...' : 'Iniciar Proceso de Cálculo'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
