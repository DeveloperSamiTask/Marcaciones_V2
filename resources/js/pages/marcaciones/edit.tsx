import { useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { LoaderCircle } from 'lucide-react';
import { toast } from 'sonner';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { Textarea } from '@/components/ui/textarea';
import { Horario } from '@/types/horarios';
import { format } from 'date-fns';
import { useMemo } from 'react';
import axios from 'axios';

type TipoMarcacion = 'ingreso' | 'salida' | 'ingreso_refri' | 'salida_refri';

export default function EditMarcacion({

    marcacionId,
    tipo,
    marcacionHora,
    disabled,
    hsp,
    hip,
    empleadoId,
    fechaInicio,
    fechaFin
}: {
    marcacionId: number,
    tipo: TipoMarcacion,
    marcacionHora: string,
    disabled: boolean,
    hsp: string,
    hip: string,
    empleadoId: number,
    fechaInicio?: string,
    fechaFin?: string
}) {

    const calcularTotalHoras = (inicio: string, fin: string, descontarRefrigerio: boolean = true) => {
        if (!inicio || !fin) return { totalMinutos: 0, label: "0h 0m" };

        const [h1, m1] = inicio.split(':').map(Number);
        const [h2, m2] = fin.split(':').map(Number);

        let totalMinutos = (h2 * 60 + m2) - (h1 * 60 + m1);

        if (totalMinutos < 0) totalMinutos += 24 * 60;

        // Si hay que descontar refrigerio (60 min)
        if (descontarRefrigerio && totalMinutos >= 360) { // Si trabaja más de 6h, suele haber refrigerio
            totalMinutos -= 60;
        }

        const horas = Math.floor(totalMinutos / 60);
        const minutos = totalMinutos % 60;

        return {
            totalMinutos,
            label: `${horas}h ${minutos}m`
        };
    };

    const resultadoJornada = calcularTotalHoras(hip, hsp, true); // <--- Cambia a 'false' si no quieres descontar


    const [listaExtrasBack, setListaExtrasBack] = useState<Horario[]>([]);
    const [horaDescontada, setHoraDescontada] = useState("");
    const [horaOriginal, setHoraOriginal] = useState(marcacionHora);
    const [modoEdicion, setModoEdicion] = useState<'libre' | 'compensar' | 'compensarDia'>('compensar');
    const [open, setOpen] = useState(false);

    // CAMBIO CLAVE: Ahora es un objeto con el total, no un array
    const [bolsaExtra, setBolsaExtra] = useState({ total_minutos: 0, label: "" });
    const [cargandoExtras, setCargandoExtras] = useState(false);
    const [horaActual, setHoraActual] = useState(marcacionHora);
    const { data, patch, processing, setData, reset } = useForm({
        empleado_id: empleadoId,
        hora_original: marcacionHora,
        hora_nueva: marcacionHora,

        tipo: tipo,
        motivo: '',
        modo: 'compensar' , // Enviamos el modo al back

        marcacion_id: marcacionId,
        total_he_disponibles: bolsaExtra.total_minutos,
    });

    useEffect(() => {
        // AÑADIMOS 'compensarDia' a la condición
        if (open && empleadoId && (modoEdicion === 'compensar' || modoEdicion === 'compensarDia')) {
            setCargandoExtras(true);
            axios.get(route('marcaciones.extras', { empleado: empleadoId }))
                .then(res => setBolsaExtra(res.data))
                .catch(err => console.error("Error:", err))
                .finally(() => setCargandoExtras(false));
        }
    }, [open, modoEdicion, empleadoId]);

    const updateMarcacion = (e) => {
        e.preventDefault();

        // Sincronizamos el modo actual del modal con el formulario
        setData('modo', modoEdicion);

        patch(route('marcaciones.update', marcacionId), {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); reset(); },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="sm" disabled={disabled}>{marcacionHora}</Button>
            </DialogTrigger>
            <DialogContent className="max-w-md">
                <DialogTitle>Editar marcación</DialogTitle>

                {/* Selector de Modo */}
                <div className="flex bg-slate-100 p-1 rounded-md mb-4">
                    <button type="button"
                        className={`flex-1 py-1 text-xs rounded ${modoEdicion === 'compensar' ? 'bg-white shadow' : ''}`}
                        onClick={() => { setModoEdicion('compensar'); setData('modo', 'compensar'); }}
                    >Compensar</button>
                    <button type="button"
                        className={`flex-1 py-1 text-xs rounded ${modoEdicion === 'libre' ? 'bg-white shadow' : ''}`}
                        onClick={() => { setModoEdicion('libre'); setData('modo', 'libre'); }}
                    >Libre</button>

                    <button type="button"
                        className={`flex-1 py-1 text-xs rounded ${modoEdicion === 'compensarDia' ? 'bg-white shadow' : ''}`}
                        onClick={() => { setModoEdicion('compensarDia'); setData('modo', 'compensarDia'); }}
                    >Compensar Dia</button>
                </div>

                <form className="space-y-4" onSubmit={updateMarcacion}>
                    {/* Input de Hora */}

                    {modoEdicion !== 'compensarDia' && (
                        <div className="grid gap-2">
                            <label className="text-sm font-medium text-muted-foreground">
                                Hora actual de {tipo}
                            </label>
                            <Input
                                type="time"
                                readOnly={modoEdicion === 'compensar'}
                                value={horaActual}
                                onChange={(e) => {
                                    setHoraActual(e.target.value);
                                    setData('hora_nueva', e.target.value);
                                }}
                            />
                        </div>
                    )}

                    {/* Vista de Compensación Automática */}
                    {modoEdicion === 'compensar' && (
                        <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg animate-in fade-in">
                            {cargandoExtras ? (
                                <p className="text-xs text-blue-600 animate-pulse">Consultando bolsa de horas...</p>
                            ) : bolsaExtra.total_minutos > 0 ? (
                                <div className="space-y-1">
                                    <p className="text-sm font-bold text-blue-900">Disponible: {bolsaExtra.label}</p>
                                    <p className="text-[10px] text-blue-700 italic">
                                        * El sistema descontará automáticamente el tiempo necesario de las horas más antiguas.
                                    </p>
                                </div>
                            ) : (
                                <p className="text-xs text-red-500 font-medium">⚠️ No tiene horas extras disponibles para compensar.</p>
                            )}
                        </div>
                    )}

                    {modoEdicion === 'compensarDia' && (
                        <div className="p-4 bg-amber-50 border border-amber-200 rounded-lg animate-in fade-in">
                            {cargandoExtras ? (
                                <p className="text-xs text-amber-600 animate-pulse">Calculando jornada y consultando bolsa...</p>
                            ) : bolsaExtra.total_minutos >= resultadoJornada.totalMinutos ? (
                                <div className="space-y-1">
                                    <p className="text-sm font-bold text-amber-900">
                                        Jornada a compensar: {resultadoJornada.label}
                                    </p>
                                    <p className="text-sm text-amber-800">
                                        Disponible: {bolsaExtra.label}
                                    </p>
                                    <p className="text-[10px] text-amber-700 italic">
                                        * Se descontará el total de la jornada de tu bolsa de HE.
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-1">
                                    <p className="text-xs text-red-600 font-bold">⚠️ Saldo insuficiente</p>
                                    <p className="text-xs text-red-500">
                                        Necesitas {resultadoJornada.label} pero solo tienes {bolsaExtra.label}.
                                    </p>
                                </div>
                            )}
                        </div>
                    )}


                    <div className="grid gap-2">
                        <label className="text-sm font-medium">Motivo</label>
                        <Textarea
                            required
                            value={data.motivo}
                            onChange={(e) => setData('motivo', e.target.value)}
                            placeholder="Describa el motivo del ajuste..."
                        />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing || (modoEdicion === 'compensar' && bolsaExtra.total_minutos <= 0)}>
                            {processing ? 'Procesando...' : 'Aplicar Ajuste'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
