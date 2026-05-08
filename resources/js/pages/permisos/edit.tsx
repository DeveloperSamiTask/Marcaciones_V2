import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { LoaderCircle, SquareCheckBig } from 'lucide-react';
import { toast } from 'sonner';
import { Input } from '@/components/ui/input';

function calcularDiferencia(programada: string, real: string): number {
    const [ph, pm] = programada.split(':').map(Number);
    const [rh, rm] = real.split(':').map(Number);
    return Math.max(0, (rh * 60 + rm) - (ph * 60 + pm));
}

function minutosAHHMM(min: number): string {
    const h = Math.floor(min / 60).toString().padStart(2, '0');
    const m = (min % 60).toString().padStart(2, '0');
    return `${h}:${m}`;
}

export default function EditPermiso({
    permisoId,
    salidaProgramada,
    salidaReal,
}: {
    permisoId: number;
    salidaProgramada: string | null;
    salidaReal: string | null;
}) {
    const maxMinutos = salidaProgramada && salidaReal
        ? calcularDiferencia(salidaProgramada, salidaReal)
        : 0;

    const { patch, processing, reset, clearErrors, setData, data } = useForm({
        he_aprobada: '',
    });

    const esValido = data.he_aprobada !== '' && data.he_aprobada <= minutosAHHMM(maxMinutos) && data.he_aprobada > '00:00';

    const handleSubmit: React.FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('permisos.update', permisoId), {
            preserveScroll: true,
            onSuccess: () => toast.success('Permiso autorizado exitosamente!', {
                richColors: true, position: 'top-center', duration: 4000,
            }),
            onError: (errors) => {
                const msg = errors.message || 'Ocurrió un error inesperado';
                toast.error(msg, { richColors: true, position: 'top-center', duration: 6000 });
            },
            onFinish: () => reset(),
        });
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button className="hover-default" size="sm">
                    <SquareCheckBig />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Aprobar Horas Extra</DialogTitle>
                <DialogDescription>
                    Revisa las horas trabajadas y define cuántas se aprueban.
                </DialogDescription>

                <div className="space-y-4 py-2">
                    <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p className="text-muted-foreground">Salida programada</p>
                            <p className="text-lg font-bold">{salidaProgramada ?? '—'}</p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">Salida real</p>
                            <p className="text-lg font-bold">{salidaReal ?? '—'}</p>
                        </div>
                    </div>

                    <div>
                        <p className="text-muted-foreground text-sm">HE trabajadas</p>
                        <p className="text-xl font-bold text-orange-500">
                            {minutosAHHMM(maxMinutos)}
                        </p>
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium">HE a aprobar:</label>
                        <Input
                            type="time"
                            value={data.he_aprobada}
                            onChange={(e) => setData('he_aprobada', e.target.value)}
                            min="00:01"
                            disabled={maxMinutos === 0}
                            step={60}
                        />
                        {data.he_aprobada > minutosAHHMM(maxMinutos) && (
                            <p className="text-destructive text-xs">
                                No puede superar las {minutosAHHMM(maxMinutos)} HE trabajadas
                            </p>
                        )}
                    </div>
                </div>

                <form onSubmit={handleSubmit}>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={() => { clearErrors(); reset(); }}>
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            disabled={processing || !esValido}
                        >
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Aprobar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
