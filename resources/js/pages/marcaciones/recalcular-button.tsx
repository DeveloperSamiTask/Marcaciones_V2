import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { useState } from 'react';
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

export function RecalcularButton({ empresa, fechaInicio, fechaFin, disabled }: RecalcularButtonProps) {
    const [isLoading, setIsLoading] = useState(false);
    const [open, setOpen] = useState(false); // Para cerrar el modal manualmente

    const handleRecalcular = () => {
        if (!empresa || !fechaInicio || !fechaFin) return;

        setIsLoading(true);

        router.post(
            route('marcaciones.recalcular-extras'),
            { empresa, fechaInicio, fechaFin },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => {
                    setIsLoading(false);
                    setOpen(false); // Cerramos el modal
                    router.reload({ only: ['marcaciones'] });
                },
            }
        );
    };

    const isDisabled = disabled || !empresa || !fechaInicio || !fechaFin || isLoading;

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="destructive"
                    size="sm"
                    disabled={isDisabled}
                    className="gap-2"
                >
                    <RefreshCw className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`} />
                    Recalcular HE
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>⚠️ Recalcular Horas Extras</DialogTitle>
                    <div className="text-sm text-muted-foreground space-y-2 pt-4">
                        <p>Esto va a:</p>
                        <ul className="list-disc list-inside space-y-1">
                            <li>Resetear los candados de "Cálculo Manual"</li>
                            <li>Recalcular las HE desde cero</li>
                            <li>Sobrescribir valores existentes</li>
                        </ul>
                        <p className="font-semibold text-destructive mt-4">
                            ¿Estás seguro de continuar con esta limpieza?
                        </p>
                    </div>
                </DialogHeader>
                <DialogFooter className="gap-2 sm:gap-0">
                    {/* Botón para cerrar manualmente */}
                    <Button variant="outline" onClick={() => setOpen(false)} disabled={isLoading}>
                        Cancelar
                    </Button>
                    <Button
                        onClick={handleRecalcular}
                        className="bg-destructive hover:bg-destructive/90"
                        disabled={isLoading}
                    >
                        {isLoading ? 'Procesando...' : 'Sí, recalcular'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
