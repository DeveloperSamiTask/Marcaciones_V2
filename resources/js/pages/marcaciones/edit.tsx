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

    const [modoEdicion, setModoEdicion] = useState<'libre' | 'compensar'>('compensar');

    const [open, setOpen] = useState(false);
    const [listaExtrasBack, setListaExtrasBack] = useState<Horario[]>([]);
    const [cargandoExtras, setCargandoExtras] = useState(false);
    const [horaActual, setHoraActual] = useState(marcacionHora);
    const [horaDescontada, setHoraDescontada] = useState("");
    const [horaOriginal, setHoraOriginal] = useState(marcacionHora);


    const { data, patch, processing, setData, reset, errors, clearErrors } = useForm<{
        empleado_id: number;
        hora_original: string;
        hora_nueva: string;
        tipo: string;
        motivo: string;
        extraSeleccionada?: string;
        hsp: string;
        tiempo_extra: string;
    }>({
        empleado_id: empleadoId,
        hora_original: marcacionHora,
        hora_nueva: marcacionHora,
        tipo: tipo,
        motivo: '',
        extraSeleccionada: '',
        hsp: hsp || '',
        tiempo_extra: '',
        modo: 'compensar'
    });


    useEffect(() => {
        if (open && empleadoId && modoEdicion === 'compensar') {
            setCargandoExtras(true);
            axios.get(route('marcaciones.extras', { empleado: empleadoId }), {
                params: { fechaInicio, fechaFin }
            })
                .then(res => setListaExtrasBack(res.data))
                .catch(err => console.error("Error cargando extras:", err))
                .finally(() => setCargandoExtras(false));
        }
    }, [open, modoEdicion]);

    // 5. LIMPIEZA: Al cerrar, reseteamos todo, incluyendo el modo a 'compensar'
    const closeModal = () => {
        setOpen(false);
        reset();
        setModoEdicion('compensar');
        setListaExtrasBack([]);
    };

    const extrasFiltradas = useMemo(() => {
        //console.log("📋 Lista a renderizar:", listaExtrasBack);
        return listaExtrasBack;
    }, [listaExtrasBack]);

    const tipoFormateado: Record<TipoMarcacion, string> = {
        ingreso: 'ingreso',
        salida: 'salida',
        ingreso_refri: 'ingreso de refrigerio',
        salida_refri: 'salida de refrigerio',
    };

    const updateMarcacion: FormEventHandler = (e) => {
        e.preventDefault();
        // 6. PATCH: Ahora mandamos el 'modo' dentro del data automáticamente
        patch(route('marcaciones.update', marcacionId), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                toast.success('¡Actualizado!');
            },
        });
    };

    useEffect(() => {
        if (!open) {
            clearErrors();
            reset();
            setData('motivo', '');
            setData('extraSeleccionada', '');
            setHoraOriginal(marcacionHora);
            setHoraActual(marcacionHora);
            setHoraDescontada("");
            setListaExtrasBack([]);
            setCargandoExtras(false);
        }
    }, [open]);


    const calcularDiferencia = (base: string, nueva: string) => {
        if (!base || !nueva) return "";

        const [h1, m1] = base.split(":").map(Number);
        const [h2, m2] = nueva.split(":").map(Number);

        const minutosBase = h1 * 60 + m1;
        const minutosNueva = h2 * 60 + m2;

        const diff = Math.abs(minutosNueva - minutosBase);
        const horas = Math.floor(diff / 60);
        const minutos = diff % 60;

        return `${horas.toString().padStart(2, "0")}:${minutos.toString().padStart(2, "0")}`;
    };

    const formatMinutes = (value: any): string => {
        const total = parseInt(value, 10);
        if (isNaN(total) || total <= 0) return '00:00';

        const h = Math.floor(total / 60);
        const m = total % 60;

        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="sm" disabled={disabled}>{marcacionHora}</Button>
            </DialogTrigger>
            <DialogContent className="max-w-md">
                <DialogTitle>Editar marcación</DialogTitle>

                {/* 7. SELECTOR DE MODO: El botón para que RRHH elija su veneno */}
                <div className="flex bg-slate-100 p-1 rounded-md mb-4">
                    <button
                        type="button"
                        className={`flex-1 py-1 text-xs rounded ${modoEdicion === 'compensar' ? 'bg-white shadow' : ''}`}
                        onClick={() => { setModoEdicion('compensar'); setData('modo', 'compensar'); }}
                    > 🔄 Compensar</button>
                    <button
                        type="button"
                        className={`flex-1 py-1 text-xs rounded ${modoEdicion === 'libre' ? 'bg-white shadow' : ''}`}
                        onClick={() => { setModoEdicion('libre'); setData('modo', 'libre'); }}
                    > ✏️ Libre</button>
                </div>

                <form className="space-y-4" onSubmit={updateMarcacion}>
                    <div className="grid gap-2">
                        <label className="text-sm font-medium">Hora de {tipo}</label>
                        <Input
                            type="time"
                            // 8. DINÁMICO: Si es compensar, es READONLY (se cambia vía select)
                            // Si es libre, RRHH puede escribir (antiguo)
                            readOnly={modoEdicion === 'compensar'}
                            value={horaActual}
                            onChange={(e) => setHoraActual(e.target.value)}
                            onBlur={(e) => {
                                const nueva = e.target.value;
                                setHoraActual(nueva);
                                setData('hora_nueva', nueva);
                                // Calculamos diferencia por si el back la necesita
                                const diff = calcularDiferencia(marcacionHora, nueva);
                                setHoraDescontada(diff);
                                setData('tiempo_extra', diff);
                            }}
                        />
                    </div>

                    {/* 9. VISTA COMPENSAR: Solo se muestra si el modo es 'compensar' */}
                    {modoEdicion === 'compensar' && (
                        <div className="space-y-4 animate-in fade-in duration-300">
                            {cargandoExtras ? (
                                <p className="text-xs text-blue-600">Buscando horas extras...</p>
                            ) : listaExtrasBack.length > 0 ? (
                                <div className="grid gap-2">
                                    <label className="text-sm font-semibold">Seleccionar Extra para descontar:</label>
                                    <select
                                        className="border rounded p-2 text-sm"
                                        value={data.extraSeleccionada}

                                        onChange={(e) => {
                                            const selectedId = e.target.value;
                                            // Buscamos el objeto completo para ver qué tiene
                                            const extraEncontrada = listaExtrasBack.find(x => String(x.id) === selectedId);
                                            console.log("🎯 SELECCIONADO:", {
                                                id_enviado: selectedId,
                                                datos_objeto: extraEncontrada
                                            });
                                            setData('extraSeleccionada', selectedId);
                                        }}
                                    >
                                        <option value="">-- Selecciona una bolsa --</option>
                                        {listaExtrasBack.map(ex => (
                                            <option key={ex.id} value={ex.id}>
                                                {formatMinutes(ex.extra)} disponible ({ex.fecha})
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            ) : (
                                <p className="text-xs text-red-500 italic">No hay horas extras este mes para compensar.</p>
                            )}
                        </div>
                    )}

                    {/* 10. MOTIVO: Siempre obligatorio para ambos casos */}
                    <div className="grid gap-2">
                        <label className="text-sm font-medium">Motivo del cambio</label>
                        <Textarea
                            required
                            value={data.motivo}
                            onChange={(e) => setData('motivo', e.target.value)}
                            placeholder="Ej: Se le perdonó la tardanza o compensó con extra..."
                        />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Guardando...' : 'Aplicar Cambios'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
