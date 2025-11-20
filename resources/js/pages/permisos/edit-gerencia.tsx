'use client';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger, DialogClose } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { toast } from 'sonner';
import { LoaderCircle, SquareCheckBig } from 'lucide-react';

export default function AprobarSolicitudHE({ solicitudId }: { solicitudId: number }) {
    const { patch, processing, reset, errors, clearErrors } = useForm();

    const aprobarSolicitud: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('solicitudes-he-pt.aprobar', solicitudId), { // 🚨 RUTA NUEVA
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Solicitud HE PT aprobada exitosamente!');
            },
            onError: (errors) => {
                const messageError = errors.message || 'Ocurrió un error inesperado';
                toast.error(messageError);
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
                <DialogTitle>Aprobar Solicitud HE PT</DialogTitle>
                <DialogDescription>
                    ¿Estás seguro de aprobar esta solicitud de horas extras?
                </DialogDescription>
                <form className="space-y-6" onSubmit={aprobarSolicitud}>
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
