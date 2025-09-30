import { useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { LoaderCircle, Plus } from 'lucide-react';
import { toast } from 'sonner';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { Textarea } from '@/components/ui/textarea';
import { Horario } from '@/types/horarios';
import { format } from 'date-fns';

type TipoMarcacion = 'ingreso' | 'salida' | 'ingreso_refri' | 'salida_refri';

export default function EditMarcacion({ marcacionId, tipo, marcacionHora, disabled, horariosExtra }:
    { marcacionId: number, tipo: TipoMarcacion, marcacionHora: string, disabled: boolean, horariosExtra?: Horario[] }) {
    const horaInput = useRef<HTMLInputElement>(null);
    const motivoInput = useRef<HTMLTextAreaElement>(null);
    const [open, setOpen] = useState(false);

    // CORREGIDO: usa los mismos nombres que en los inputs
    const { data, patch, processing, setData, reset, errors, clearErrors } = useForm<{
        hora_original: string;
        hora_restada: string;
        tipo: string;
        motivo: string;
        extraSeleccionada?: string;
    }>({
        hora_original: marcacionHora,
        hora_restada: marcacionHora, // Inicialmente igual
        tipo: tipo,
        motivo: '',
        extraSeleccionada: ''
    });

    const tipoFormateado: Record<TipoMarcacion, string> = {
        ingreso: 'ingreso',
        salida: 'salida',
        ingreso_refri: 'ingreso de refrigerio',
        salida_refri: 'salida de refrigerio',
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
        }
    }, [open]);

    const [horaOriginal, setHoraOriginal] = useState(marcacionHora);
    const [horaActual, setHoraActual] = useState(marcacionHora);
    const [horaDescontada, setHoraDescontada] = useState("");

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

    const closeModal = () => {
        clearErrors();
        reset();
        setData('motivo', '');
        setData('extraSeleccionada', '');
        setHoraOriginal(marcacionHora);
        setHoraActual(marcacionHora);
        setHoraDescontada("");
        setOpen(false);
    };

    const updateMarcacion: FormEventHandler = (e) => {
        e.preventDefault();
        console.log("Datos que se van a guardar:", data);
        patch(route('marcaciones.update', marcacionId), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                toast.success('Marcación actualizada exitosamente!', {
                    richColors: true,
                    position: 'top-center',
                    duration: 4000,
                });
            },
            onError: (errors) => {
                const messageError = errors.message && errors.message != '' ? errors.message : 'Ocurrió un error inesperado';
                toast.error(messageError, {
                    richColors: true,
                    position: 'top-center',
                    duration: 6000,
                });
            },
            onFinish: () => reset(),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" className="hover-ghost" size="sm" disabled={disabled}>
                    {marcacionHora}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Editar marcación</DialogTitle>
                <DialogDescription>
                    Ingrese hora de {tipoFormateado[tipo]}
                </DialogDescription>

                <form className="space-y-6" onSubmit={updateMarcacion}>
                    <div className="grid gap-2">
                        <Input
                            id="hora_restada"
                            type="time"
                            name="hora_restada"
                            className="mt-1 block w-full"
                            tabIndex={1}
                            ref={horaInput}
                            value={horaActual}
                            onChange={(e) => {
                                setHoraActual(e.target.value);
                            }}
                            onBlur={(e) => {
                                const nuevaHora = e.target.value;
                                const resultado = calcularDiferencia(horaOriginal, nuevaHora);
                                setHoraDescontada(resultado);

                                // CORREGIDO: usa los nombres correctos
                                setData("hora_original", horaOriginal);
                                setData("hora_restada", nuevaHora);

                                console.log("Payload al backend:", {
                                    hora_original: horaOriginal,
                                    hora_restada: nuevaHora,
                                    motivo: data.motivo,
                                    tipo: data.tipo,
                                });
                            }}
                        />
                        <InputError message={errors.hora_restada} />
                    </div>

                    {horariosExtra && horariosExtra.length > 0 && horaDescontada && (
                        <div className="grid gap-2 mt-2">
                            <label htmlFor="horaDescontada" className="font-semibold">
                                Diferencia de horas
                            </label>
                            <Input
                                id="horaDescontada"
                                type="text"
                                value={horaDescontada}
                                readOnly
                                className="mt-1 block w-full"
                            />
                        </div>
                    )}


                    {horariosExtra && horariosExtra.length > 0 && (
                        <div className="grid gap-2">
                            <label htmlFor="extraSeleccionada" className="font-semibold">
                                Selecciona una hora extra disponible:
                            </label>
                            <select
                                name="extraSeleccionada"
                                id="extraSeleccionada"
                                className="border rounded px-3 py-2 text-black bg-white"
                                value={data.extraSeleccionada}>

                                <option value="">-- Selecciona una opción --</option>
                                {horariosExtra.map((extra) => (
                                    <option key={extra.id} value={extra.id}>
                                        {extra.extra} minutos (día {format(extra.fecha, 'dd/MM/yyyy')})
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.extraSeleccionada} />
                        </div>
                    )}





                    <div className="grid gap-2">
                        <Textarea
                            id="motivo"
                            name="motivo"
                            required
                            tabIndex={2}
                            className="mt-1 block w-full"
                            ref={motivoInput}
                            value={data.motivo}
                            onChange={(e) => setData('motivo', e.target.value)}
                            placeholder="Descripcion del motivo"
                        />

                        <InputError message={errors.motivo} />
                    </div>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancelar
                            </Button>
                        </DialogClose>

                        <Button type='submit' disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Editar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
