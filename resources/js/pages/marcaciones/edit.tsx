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
    empleadoId,
    fechaInicio,
    fechaFin
}: {
    marcacionId: number,
    tipo: TipoMarcacion,
    marcacionHora: string,
    disabled: boolean,
    hsp: string,
    empleadoId: number,
    fechaInicio?: string,
    fechaFin?: string
}) {

    // const [modoEdicion, setModoEdicion] = useState<'libre' | 'compensar'>('compensar');

    // const [open, setOpen] = useState(false);
    const [listaExtrasBack, setListaExtrasBack] = useState<Horario[]>([]);
    // const [cargandoExtras, setCargandoExtras] = useState(false);
    // const [horaActual, setHoraActual] = useState(marcacionHora);
    const [horaDescontada, setHoraDescontada] = useState("");
    const [horaOriginal, setHoraOriginal] = useState(marcacionHora);

    const [modoEdicion, setModoEdicion] = useState<'libre' | 'compensar'>('compensar');
    const [open, setOpen] = useState(false);

    // CAMBIO CLAVE: Ahora es un objeto con el total, no un array
    const [bolsaExtra, setBolsaExtra] = useState({ total_minutos: 0, label: "" });
    const [cargandoExtras, setCargandoExtras] = useState(false);
    const [horaActual, setHoraActual] = useState(marcacionHora);


    // const { data, patch, processing, setData, reset, errors, clearErrors } = useForm<{
    //     empleado_id: number;
    //     hora_original: string;
    //     hora_nueva: string;
    //     tipo: string;
    //     motivo: string;
    //     extraSeleccionada?: string;
    //     hsp: string;
    //     tiempo_extra: string;
    // }>({
    //     empleado_id: empleadoId,
    //     hora_original: marcacionHora,
    //     hora_nueva: marcacionHora,
    //     tipo: tipo,
    //     motivo: '',
    //     extraSeleccionada: '',
    //     hsp: hsp || '',
    //     tiempo_extra: '',
    //     modo: 'compensar'
    // });

    const { data, patch, processing, setData, reset } = useForm({
        empleado_id: empleadoId,
        hora_original: marcacionHora,
        hora_nueva: marcacionHora,
        tipo: tipo,
        motivo: '',
        modo: 'compensar' // Enviamos el modo al back
    });




    // 5. LIMPIEZA: Al cerrar, reseteamos todo, incluyendo el modo a 'compensar'


    // const extrasFiltradas = useMemo(() => {
    //     //console.log("📋 Lista a renderizar:", listaExtrasBack);
    //     return listaExtrasBack;
    // }, [listaExtrasBack]);

    // const tipoFormateado: Record<TipoMarcacion, string> = {
    //     ingreso: 'ingreso',
    //     salida: 'salida',
    //     ingreso_refri: 'ingreso de refrigerio',
    //     salida_refri: 'salida de refrigerio',
    // };

    useEffect(() => {
        if (open && empleadoId && modoEdicion === 'compensar') {
            setCargandoExtras(true);
            axios.get(route('marcaciones.extras', { empleado: empleadoId }))
                .then(res => setBolsaExtra(res.data))
                .catch(err => console.error("Error:", err))
                .finally(() => setCargandoExtras(false));
        }
    }, [open, modoEdicion]);

    const updateMarcacion = (e) => {
        e.preventDefault();
        patch(route('marcaciones.update', marcacionId), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };

    // useEffect(() => {
    //     if (!open) {
    //         clearErrors();
    //         reset();
    //         setData('motivo', '');
    //         setData('extraSeleccionada', '');
    //         setHoraOriginal(marcacionHora);
    //         setHoraActual(marcacionHora);
    //         setHoraDescontada("");
    //         setListaExtrasBack([]);
    //         setCargandoExtras(false);
    //     }
    // }, [open]);


    // const calcularDiferencia = (base: string, nueva: string) => {
    //     if (!base || !nueva) return "";

    //     const [h1, m1] = base.split(":").map(Number);
    //     const [h2, m2] = nueva.split(":").map(Number);

    //     const minutosBase = h1 * 60 + m1;
    //     const minutosNueva = h2 * 60 + m2;

    //     const diff = Math.abs(minutosNueva - minutosBase);
    //     const horas = Math.floor(diff / 60);
    //     const minutos = diff % 60;

    //     return `${horas.toString().padStart(2, "0")}:${minutos.toString().padStart(2, "0")}`;
    // };

    // const formatMinutes = (value: any): string => {
    //     const total = parseInt(value, 10);
    //     if (isNaN(total) || total <= 0) return '00:00';

    //     const h = Math.floor(total / 60);
    //     const m = total % 60;

    //     return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
    // };

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
                    > 🔄 Compensar</button>
                    <button type="button"
                        className={`flex-1 py-1 text-xs rounded ${modoEdicion === 'libre' ? 'bg-white shadow' : ''}`}
                        onClick={() => { setModoEdicion('libre'); setData('modo', 'libre'); }}
                    > ✏️ Libre</button>
                </div>

                <form className="space-y-4" onSubmit={updateMarcacion}>
                    {/* Input de Hora */}
                    <div className="grid gap-2">
                        <label className="text-sm font-medium text-muted-foreground">Hora actual de {tipo}</label>
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
