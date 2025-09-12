import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Suspension } from '@/types/suspensiones';
import { format } from 'date-fns';
import { LoaderCircle, Printer } from 'lucide-react';
import { useRef, useState } from 'react';

export default function PrintSuspension({ suspension, isPrint }: { suspension: Suspension, isPrint: boolean }) {
    const fechaInput = useRef<HTMLInputElement>(null);
    const motivoInput = useRef<HTMLTextAreaElement>(null);
    const articuloInput = useRef<HTMLTextAreaElement>(null);

    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false); // para la carga
    const [fecha, setFecha] = useState(suspension.fecha_print ? format(suspension.fecha_print, 'yyyy-MM-dd') : ''); // para enviar la fecha
    const [motivo, setMotivo] = useState(suspension.motivo ?? ''); // para enviar la fecha
    const [articulo, setArticulo] = useState(''); // para enviar la fecha

    const print = () => {
        setProcessing(true);
        const printUrl = `${route('suspensiones.imprimir', suspension.id)}?fecha=${fecha}&motivo=${motivo}&articulo=${articulo}`;
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

                    {!suspension.fecha_print && suspension.codigo[0] == 'S' &&(
                        <div className="grid gap-2">
                            <Input
                                id="fecha_print"
                                type="date"
                                name="fecha_print"
                                required
                                ref={fechaInput}
                                value={fecha}
                                onChange={(e) => setFecha(e.target.value)}
                            />
                        </div>
                    )}

                    {(suspension.tipo == 'negligencia' || suspension.tipo == 'incumplimiento') && (
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

                    {(suspension.tipo == 'negligencia' || suspension.tipo == 'incumplimiento') && (
                        <div className="grid gap-2">
                            Articulo {suspension.codigo[0] == 'S' ? '48' : '38'}
                            <Textarea
                                id="articulo"
                                className="mt-1 block w-full"
                                required
                                ref={articuloInput}
                                value={articulo}
                                onChange={(e) => setArticulo(e.target.value)}
                                autoComplete="articulo"
                                placeholder="Describe el numero del articulo"
                            />

                        </div>
                    )}

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
