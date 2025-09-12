import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';
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

export default function CreateMarcacion({ empleadoId, tipo, fecha, disabled, horariosExtra } :
    { empleadoId: number, tipo: TipoMarcacion, fecha: string, disabled: boolean, horariosExtra?: Horario[] }) {
    const horaInput = useRef<HTMLInputElement>(null);
    const motivoInput = useRef<HTMLTextAreaElement>(null);
    const { data, post, processing, setData, reset, errors, clearErrors } = useForm<Required<{ empleado_id: number, hora: string, tipo: string, fecha: string, motivo: string }>>
        ({ empleado_id: empleadoId, hora: '', tipo: tipo, fecha: fecha, motivo: '' })

    const tipoFormateado: Record<TipoMarcacion, string> = {
        ingreso: 'ingreso',
        salida: 'salida',
        ingreso_refri: 'ingreso de refrigerio',
        salida_refri: 'salida de refrigerio',
    };

    const createMarcacion: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('marcaciones.store'), {
            preserveScroll: true,
            onSuccess: () => {
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
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="ghost" className="hover-info" size="sm" disabled={disabled}>
                    <Plus/>
                </Button>

            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Crear marcacion</DialogTitle>
                    <DialogDescription>
                        Ingrese hora de { tipoFormateado[tipo] }
                    </DialogDescription>

                <form className="space-y-6" onSubmit={createMarcacion}>

                    <div className="grid gap-2">
                        <Input
                            id="hora"
                            type="time"
                            name="hora"
                            tabIndex={1}
                            required
                            ref={horaInput}
                            value={data.hora}
                            onChange={(e) => setData('hora', e.target.value)}
                        />

                        <InputError message={errors.hora} />
                    </div>
                    {horariosExtra && (<div className="grid gap-2">
                        {horariosExtra.map((extra) => {
                            return (
                                <span key={extra.id} className='text-teal-400'>
                                    Tienes {extra.extra} extra, el dia: {format(extra.fecha, 'dd/MM/yyyy')}
                                </span>
                            );
                        })}
                    </div>)}
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

                        <Button type='submit' disabled={processing} tabIndex={3}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Crear
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
