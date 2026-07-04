import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { LoaderCircle, SquareCheckBig } from 'lucide-react';
import { toast } from 'sonner';
import { Input } from '@/components/ui/input';

/*
    function calcularDiferencia(programada: string, real: string): number {
    const [ph, pm] = programada.split(':').map(Number);
    const [rh, rm] = real.split(':').map(Number);
    return Math.max(0, (rh * 60 + rm) - (ph * 60 + pm));
}
*/
type EditPermisoProps = {
    permisoId: number;
    salidaProgramada: string | null;
    salidaReal: string | null;
    permiso: any; // temporalmente para debug
};


function calcularDiferencia(programada: string, real: string): number {
    const [ph, pm] = programada.split(':').map(Number);
    const [rh, rm] = real.split(':').map(Number);

    let minutosProgramados = ph * 60 + pm;
    let minutosReales = rh * 60 + rm;

    // Si los minutos reales son menores, es porque pasó la medianoche
    if (minutosReales < minutosProgramados) {
        minutosReales += 24 * 60; // Le sumamos 1440 minutos (un día completo)
    }

    return Math.max(0, minutosReales - minutosProgramados);
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
    permiso,
}: EditPermisoProps) {

    // console.log("DEBUG MODAL CORRIENDO:", { salidaProgramada, salidaReal });
    // console.log("Permiso: " , permiso);

    // const { horario_dia, marcacion_dia } = permiso;
    const horario_dia = permiso?.horario_dia || { ingreso: '00:00', salida: '00:00' };
    const marcacion_dia = permiso?.marcacion_dia || { ingreso: '00:00', salida: '00:00' };
    const toMin = (t: string) => { const [h, m] = t.split(':').map(Number); return h * 60 + m; };
    // 1.Extra anticipado , extra salida
    //const realAnticipo = Math.max(0, toMin(horario_dia.ingreso) - toMin(marcacion_dia.ingreso));
    //const realSalida = Math.max(0, toMin(marcacion_dia.salida) - toMin(horario_dia.salida));

    const progIngreso = toMin(horario_dia.ingreso);
    const realIngreso = toMin(marcacion_dia.ingreso);
    const realAnticipo = Math.max(0, progIngreso - realIngreso);

    // --- LÓGICA CORREGIDA PARA SALIDA ---
    const progSalidaMin = toMin(horario_dia.salida);
    let realSalidaMin = toMin(marcacion_dia.salida);

    if (realSalidaMin < progSalidaMin && progSalidaMin >= 1320) {
        realSalidaMin += 1440;
    }

    const realSalida = Math.max(0, realSalidaMin - progSalidaMin);

    const esModoHE = permiso?.tipo_id === 20;

    // 2.
    const { patch, processing, reset, clearErrors, setData, data } = useForm({
        he_anticipada: realAnticipo, // Esto viene de tu cálculo de arriba
        he_salida: realSalida,       // Esto viene de tu cálculo de arriba
    });

    const esValido = esModoHE
        ? (
            Number(data.he_anticipada) <= realAnticipo &&
            Number(data.he_salida) <= realSalida &&
            (Number(data.he_anticipada) + Number(data.he_salida) > 0)
        )
        : true;

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
                <DialogTitle>
                    {esModoHE ? 'Aprobar Horas Extra' : 'Aprobar Permiso'}
                </DialogTitle>
                <DialogDescription>
                    {esModoHE
                        ? 'Revisa las horas trabajadas y define cuántas se aprueban.'
                        : '¿Estás seguro de aprobar este permiso?'}
                </DialogDescription>

                {esModoHE ? (
                    // Modal completo con HE — solo tipo 20
                    <div className="space-y-6 py-4">
                        {/* BLOQUE HE ANTICIPADO */}
                        <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <div className="flex justify-between items-center mb-3">
                                <span className="font-semibold text-slate-700">HE Anticipadas</span>
                                <span className="text-sm bg-blue-100 text-blue-700 px-2 py-1 rounded font-bold">
                                    Real: {minutosAHHMM(realAnticipo)}
                                </span>
                            </div>
                            <div className="flex items-center gap-4">
                                <p className="text-xs text-slate-500 w-24">Prog: {permiso.horario_dia.ingreso} <br /> Real: {permiso.marcacion_dia.ingreso}</p>
                                <Input
                                    className="h-12 text-lg"
                                    type="number"
                                    value={data.he_anticipada}
                                    onChange={(e) => setData('he_anticipada', Number(e.target.value))}
                                    placeholder="Minutos a aprobar"
                                />
                            </div>
                        </div>

                        {/* BLOQUE HE SALIDA */}
                        <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <div className="flex justify-between items-center mb-3">
                                <span className="font-semibold text-slate-700">HE Salida</span>
                                <span className="text-sm bg-orange-100 text-orange-700 px-2 py-1 rounded font-bold">
                                    Real: {minutosAHHMM(realSalida)}
                                </span>
                            </div>
                            <div className="flex items-center gap-4">
                                <p className="text-xs text-slate-500 w-24">Prog: {permiso.horario_dia.salida} <br /> Real: {permiso.marcacion_dia.salida}</p>
                                <Input
                                    className="h-12 text-lg"
                                    type="number"
                                    value={data.he_salida}
                                    onChange={(e) => setData('he_salida', Number(e.target.value))}
                                    placeholder="Minutos a aprobar"
                                />
                            </div>
                        </div>

                        {/* TOTAL GRANDE */}
                        <div className="border-t pt-4 text-center">
                            <p className="text-sm text-slate-500 uppercase tracking-wide">Total a aprobar</p>
                            <p className="text-4xl font-black text-slate-900">
                                {minutosAHHMM(Number(data.he_anticipada) + Number(data.he_salida))}
                            </p>
                        </div>
                    </div>
                ) : (
                    // Modal simple — tipo 2 y resto
                    <DialogDescription>¿Estás seguro de aprobar este permiso?</DialogDescription>
                )}

                <form onSubmit={handleSubmit}>
                    <DialogFooter>
                        <Button
                            className="w-full h-12 text-base"
                            type="submit"
                            disabled={processing || !esValido}
                        >
                            {processing ? "Procesando..." : "Aprobar"}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
