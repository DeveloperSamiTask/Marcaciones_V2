import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { LoaderCircle, Trash2 } from 'lucide-react';
import { toast } from 'sonner';

export default function DeleteEmpresa({ empresaId } : { empresaId : number}) {
    const { delete: destroy, processing, reset } = useForm();

    const deleteEmpresa: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('empresas.destroy', empresaId), {
            preserveScroll: true,
            onSuccess: () => reset(),
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

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="destructive" className="hover-destructive" size="sm">
                    <Trash2/>
                </Button>

            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Eliminar empresa</DialogTitle>
                <DialogDescription>
                    ¿Estás seguro de realizar esta accion?
                </DialogDescription>
                <form className="space-y-6" onSubmit={deleteEmpresa}>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={() => reset()}>
                                Cancelar
                            </Button>
                        </DialogClose>

                        <Button type='submit' variant="destructive" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Eliminar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
