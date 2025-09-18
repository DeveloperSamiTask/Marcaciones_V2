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
    const { data, patch, processing, setData, reset, errors, clearErrors } = useForm<Required<{ hora: string, tipo: string, motivo: string }>>
        ({ hora: marcacionHora, tipo: tipo, motivo: '' })

    const tipoFormateado: Record<TipoMarcacion, string> = {
        ingreso: 'ingreso',
        salida: 'salida',
        ingreso_refri: 'ingreso de refrigerio',
        salida_refri: 'salida de refrigerio',
    };

    useEffect(() => {
        setData('hora', marcacionHora);
    }, [marcacionHora]);

    const updateMarcacion: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('marcaciones.update', marcacionId), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                toast.success('Marcacion creada exitosamente!', {
                    richColors: true,
                    position: 'top-center',
                    duration: 4000,
                });
            },
            onError: (errors) => {
                const messageError = errors.message && errors.message != '' ? errors.message : 'Ocurrio un error inesperado';
                toast.error(messageError, {
                    richColors: true,
                    position: 'top-center',
                    duration: 6000,
                });
            },
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        clearErrors();
        reset();
        setData('motivo', '');
        setOpen(false);
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" className="hover-ghost" size="sm" disabled={disabled}>
                    {marcacionHora}
                </Button>

            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Editar marcacion</DialogTitle>
                <DialogDescription>
                    Ingrese hora de {tipoFormateado[tipo]}
                </DialogDescription>

                <form className="space-y-6" onSubmit={updateMarcacion}>

                    <div className="grid gap-2">
                        <Input
                            id="hora"
                            type="time"
                            name="hora"
                            className="mt-1 block w-full"
                            tabIndex={1}
                            ref={horaInput}
                            value={data.hora}
                            onChange={(e) => setData('hora', e.target.value)}
                        />

                        <InputError message={errors.hora} />
                    </div>

                    {horariosExtra && horariosExtra.length > 0 && (
                        <div className="grid gap-2">
                            <label htmlFor="extraSeleccionada" className="font-semibold">
                                Selecciona una hora extra disponible:
                            </label>
                            <select
                                name="extraSeleccionada"
                                id="extraSeleccionada"
                                className="border rounded px-3 py-2 text-black bg-white"
                                value={data.extraSeleccionada}
                                onChange={(e) => setData('extraSeleccionada', e.target.value)}
                            >
                                <option value="">-- Selecciona una opción --</option>
                                {horariosExtra.map((extra) => (
                                    <option key={extra.id} value={extra.extra}>
                                        {extra.extra} minutos (día {format(extra.fecha, 'dd/MM/yyyy')})
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.extraSeleccionada} />
                        </div>
                    )}

                    {horariosExtra && horariosExtra.length > 0 && (
                        <div className="grid gap-2 mt-4">
                            <label htmlFor="tiempoDescontar" className="font-semibold">Tiempo a descontar:</label>
                            <select
                                name="tiempoDescontar"
                                id="tiempoDescontar"
                                className="border rounded px-3 py-2 text-black bg-white"
                                value={data.tiempoDescontar}
                                onChange={(e) => setData('tiempoDescontar', e.target.value)}
                            >
                                <option value="">-- Selecciona un tiempo --</option>
                                <option value="30">30 minutos</option>
                                <option value="40">40 minutos</option>
                            </select>
                            <InputError message={errors.tiempoDescontar} />
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
