import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { LoaderCircle, Plus } from 'lucide-react';
import { toast } from 'sonner';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { Textarea } from '@/components/ui/textarea';
import { Horario } from '@/types/horarios';
import { format } from 'date-fns';
import axios from 'axios';

type TipoMarcacion = 'ingreso' | 'salida' | 'ingreso_refri' | 'salida_refri';

export default function CreateMarcacion({
    empleadoId, tipo, fecha, disabled, horariosExtra, hsp, hip
}: {
    empleadoId: number, tipo: TipoMarcacion, fecha: string, disabled: boolean,
    horariosExtra?: Horario[], hsp: string, hip: string
}) {
    // 1. Estados necesarios para la lógica de modos
    const [open, setOpen] = useState(false);
    const [modoEdicion, setModoEdicion] = useState<'libre' | 'compensarDia'>('libre');
    const [bolsaExtra, setBolsaExtra] = useState({ total_minutos: 0, label: "" });
    const [cargandoExtras, setCargandoExtras] = useState(false);

    // Lógica para calcular la jornada (copiada del Edit)
    const calcularJornada = (inicio: string, fin: string, descontarRefrigerio: boolean = true) => {
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
    const resultadoJornada = calcularJornada(hip, hsp);

    // 2. useForm con los campos adicionales para el backend
    const { data, post, processing, setData, reset, errors, clearErrors } = useForm({
        empleado_id: empleadoId,
        hora: '',
        tipo: tipo,
        fecha: fecha,
        motivo: '',
        modo: 'libre',
        total_he_disponibles: 0
    });

    // 3. Efecto para cargar bolsa (copiado del Edit)
    useEffect(() => {
        if (open && empleadoId && modoEdicion === 'compensarDia') {
            setCargandoExtras(true);
            axios.get(route('marcaciones.extras', { empleado: empleadoId }))
                .then(res => setBolsaExtra(res.data))
                .finally(() => setCargandoExtras(false));
        }
    }, [open, modoEdicion, empleadoId]);

    const createMarcacion: FormEventHandler = (e) => {
        e.preventDefault();

        // Sincronizar datos antes de enviar
        const payload = { ...data, modo: modoEdicion, total_he_disponibles: bolsaExtra.total_minutos };

        // Router dinámico: si es compensarDia, va a su ruta; si no, al store normal
        const url = modoEdicion === 'compensarDia'
        ? route('marcaciones.compensarDiaStore')
        : route('marcaciones.store');

        post(url, {
            data: payload,
            preserveScroll: true,
            onSuccess: () => { setOpen(false); reset(); toast.success('Operación exitosa'); },
            onError: (errs) => toast.error(errs.message || 'Error')
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="sm" disabled={disabled}><Plus /></Button>
            </DialogTrigger>
            <DialogContent className="max-w-md">
                <DialogTitle>Crear marcación</DialogTitle>

                {/* Selector de Modo */}
                <div className="flex bg-slate-100 p-1 rounded-md mb-4">
                    {['libre', 'compensarDia'].map((m) => (
                        <button key={m} type="button"
                            className={`flex-1 py-1 text-xs rounded ${modoEdicion === m ? 'bg-white shadow' : ''}`}
                            onClick={() => { setModoEdicion(m as any); setData('modo', m); }}>
                            {m.charAt(0).toUpperCase() + m.slice(1)}
                        </button>
                    ))}
                </div>

                <form className="space-y-4" onSubmit={createMarcacion}>
                    {/* Input Hora solo si no es compensarDia */}
                    {modoEdicion !== 'compensarDia' && (
                        <Input type="time" value={data.hora} onChange={e => setData('hora', e.target.value)} required />
                    )}

                    {modoEdicion === 'compensarDia' && (
                        <div className="p-3 bg-amber-50 text-xs text-amber-800 rounded">
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
                        </div>
                    )}

                    <Textarea value={data.motivo} onChange={e => setData('motivo', e.target.value)} placeholder="Motivo..." required />

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>Crear</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
