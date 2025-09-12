import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { LoaderCircle, CircleCheckBig } from 'lucide-react';
import { toast } from 'sonner';

export default function EditAsistencia({ asistenciaId, text, isPendienteHorasExtra } : { asistenciaId : number; text?: string; isPendienteHorasExtra?: boolean }) {
    const { patch, processing, reset, clearErrors } = useForm();

    const editAsistencia: FormEventHandler = (e) => {
        e.preventDefault();

        if (isPendienteHorasExtra) {
            return toast.error('Hay horas extras pendientes de aprobación', {
                richColors: true,
                position: 'top-center',
                duration: 6000,
            });
        }

        patch(route('asistencias.update', asistenciaId), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Asistencia aprobada exitosamente!', {
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
                <Button variant="default" className="hover-default" size="sm">
                    <CircleCheckBig/>
                    {text ? (<span className="hidden sm:inline">{text}</span>) : ''}
                </Button>

            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Aprobar asistencia</DialogTitle>
                <DialogDescription>
                    ¿Estas seguro de realizar esta accion?
                </DialogDescription>
                <form className="space-y-6" onSubmit={editAsistencia}>
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
