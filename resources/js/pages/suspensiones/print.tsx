import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Suspension } from '@/types/suspensiones';
import { format } from 'date-fns';
import { LoaderCircle, Printer } from 'lucide-react';
import { useRef, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { DateRangeFilter } from '@/components/date-range';

export default function PrintSuspension({ suspension, isPrint }: { suspension: Suspension, isPrint: boolean }) {
    const fechaInput = useRef<HTMLInputElement>(null);
    const motivoInput = useRef<HTMLTextAreaElement>(null);
    const articuloInput = useRef<HTMLTextAreaElement>(null);

    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false); // para la carga
    const [dateRange, setDateRange] = useState<DateRange | undefined>(
        suspension.fecha_print
            ? { from: new Date(suspension.fecha_print), to: new Date(suspension.fecha_print) }
            : undefined
    );
    const [motivo, setMotivo] = useState(suspension.motivo ?? ''); // para enviar la fecha
    const [articulo, setArticulo] = useState(''); // para enviar la fecha

    {/* Imprimir en esta parte debe estar el calendario */ }
    const print = () => {
        setProcessing(true);
        const fechaInicio = dateRange?.from?.toISOString().split('T')[0] || '';
        const fechaFin = dateRange?.to?.toISOString().split('T')[0] || '';
        const printUrl = `${route('suspensiones.imprimir', suspension.id)}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&motivo=${motivo}&articulo=${articulo}`;
        const printWindow = window.open(printUrl, '_blank', 'width=800,height=600');



        if (printWindow) {

            // Esperamos a que cargue la página y disparamos print
            printWindow.onload = () => {
                printWindow.focus();
                printWindow.print();
            };
            setProcessing(false);
            setOpen(false);
        } else {
            setProcessing(false);
            alert('No se pudo abrir la ventana de impresión. Desactiva el bloqueador de ventanas emergentes.');
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant={isPrint ? 'destructive' : 'info'} className="hover-info" size="sm">
                    <Printer />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Imprimir</DialogTitle>
                <DialogDescription>
                    Indicar la fecha que se aplicara la suspension
                </DialogDescription>

                {/* Rango de suspensión - SOLO para suspensiones (S) que no han sido impresas */}
                {!suspension.fecha_print && suspension.codigo[0] == 'S' && (
                    <div className="grid gap-2">
                        <label className="text-sm font-medium">Rango de suspensión</label>
                        <DateRangeFilter
                            dateRange={dateRange}
                            setDateRange={setDateRange}
                            placeholder="SELECCIONAR RANGO DE SUSPENSIÓN"
                        />
                    </div>
                )}

                {/* Motivo - SOLO para amonestaciones (AM) de tipo negligencia o incumplimiento */}
                {suspension.codigo[0] == 'A' && (suspension.tipo == 'negligencia' || suspension.tipo == 'incumplimiento') && (
                    <div className="grid gap-2">
                        Motivo
                        <Textarea
                            id="motivo"
                            className="mt-1 block w-full"
                            value={motivo}
                            tabIndex={1}
                            ref={motivoInput}
                            onChange={(e) => setMotivo(e.target.value)}
                            autoComplete="motivo"
                            placeholder="Describe el motivo"
                        />
                    </div>
                )}

                {/* Artículo - AHORA SE MUESTRA PARA AMBAS (A y S) */}

                    <div className="grid gap-2">
                        <label className="text-sm font-medium">
                           Articulo {/*  /*{suspension.codigo[0] == 'S' ? '48' : '38'}*/}
                        </label>
                        <Textarea
                            id="articulo"
                            className="mt-1 block w-full"
                            required
                            ref={articuloInput}
                            value={articulo}
                            onChange={(e) => setArticulo(e.target.value)}
                            placeholder="Pega aquí el inciso correspondiente"
                        />
                    </div>


                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">
                            Cancelar
                        </Button>
                    </DialogClose>

                    <Button disabled={processing} onClick={print}>
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Imprimir
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
