import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { CircleX, LoaderCircle } from 'lucide-react';
import { toast } from 'sonner';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/input-error';

export default function DeleteAsistencia({ asistenciaId, text } : {asistenciaId : number, text?: string}) {
    const motivoInput = useRef<HTMLTextAreaElement>(null);
    const { data, setData, delete: destroy, processing, reset, errors, clearErrors } = useForm<Required<{motivo: string }>>({ motivo: '' });

    const deleteAsistencia: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('asistencias.destroy', asistenciaId), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Asistencia rechazada exitosamente!', {
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
                <Button variant="destructive" className="hover-destructive" size="sm">
                    <CircleX/>
                    {text ? (<span className="hidden sm:inline">{text}</span>) : ''}
                </Button>

            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Rechazar asistencia</DialogTitle>
                <DialogDescription>
                    ¿Estas seguro de realizar esta accion?
                </DialogDescription>
                <form className="space-y-6" onSubmit={deleteAsistencia}>
                    <div className="grid gap-2">
                        <Textarea
                            id="motivo"
                            className="mt-1 block w-full"
                            value={data.motivo}
                            tabIndex={1}
                            ref={motivoInput}
                            onChange={(e) => setData('motivo', e.target.value)}
                            required
                            autoComplete="motivo"
                            placeholder="Descripcion del motivo de su rechazo"
                        />

                        <InputError message={errors.motivo} />
                    </div>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancelar
                            </Button>
                        </DialogClose>

                        <Button type='submit' variant="destructive" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Rechazar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
