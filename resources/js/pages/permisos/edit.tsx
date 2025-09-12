import { useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { LoaderCircle, SquareCheckBig } from 'lucide-react';
import { toast } from 'sonner';

export default function EditPermiso({ permisoId } : {permisoId : number}) {
    const { patch, processing, reset, errors, clearErrors } = useForm();

    const editPermiso: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('permisos.update', permisoId), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Permiso autorizado exitosamente!', {
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
                <Button className="hover-default" size="sm">
                    <SquareCheckBig/>
                </Button>

            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Aprobar permiso</DialogTitle>
                <DialogDescription>
                    ¿Estas seguro de aprobar este permiso?
                </DialogDescription>
                <form className="space-y-6" onSubmit={editPermiso}>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancelar
                            </Button>
                        </DialogClose>

                        <Button type='submit' disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Aprobar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
