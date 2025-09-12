import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { LoaderCircle, SquareX } from 'lucide-react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Textarea } from '@/components/ui/textarea';

export default function DeletePermiso({ permisoId } : {permisoId : number}) {
    const motivoRechazoInput = useRef<HTMLTextAreaElement>(null);
    const { data, delete: destroy, setData, processing, reset, errors, clearErrors } = useForm<Required<{motivo_rechazo: string }>>({ motivo_rechazo: '' });

    const deletePermiso: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('permisos.destroy', permisoId), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Permiso rechazado exitosamente!', {
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
                <Button variant="destructive" className="hover-default" size="sm">
                    <SquareX/>
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Rechazar permiso</DialogTitle>
                <DialogDescription>
                    Ingresa el motivo de rechazo.
                </DialogDescription>
                <form className="space-y-6" onSubmit={deletePermiso}>
                    <div className="grid gap-2">
                        <Textarea
                            id="motivo_rechazo"
                            className="mt-1 block w-full"
                            value={data.motivo_rechazo}
                            tabIndex={1}
                            ref={motivoRechazoInput}
                            onChange={(e) => setData('motivo_rechazo', e.target.value)}
                            required
                            autoComplete="motivo_rechazo"
                            placeholder="Descripcion del motivo"
                        />

                        <InputError message={errors.motivo_rechazo} />
                    </div>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancelar
                            </Button>
                        </DialogClose>

                        <Button variant="destructive" type='submit' disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Rechazar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
