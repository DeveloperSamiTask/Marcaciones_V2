'use client';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger, DialogClose } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';
import { toast } from 'sonner';
import { LoaderCircle, SquareX } from 'lucide-react';
import { Textarea } from '@/components/ui/textarea';

//import { InputError } from '@/components/ui/input-error';
export default function RechazarSolicitudHE({ solicitudId }: { solicitudId: number }) {
    const motivoRechazoInput = useRef<HTMLTextAreaElement>(null);
    const { data, delete: destroy, setData, processing, reset, errors, clearErrors } = useForm<{observaciones: string }>({ observaciones: '' });

    const rechazarSolicitud: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('solicitudes-he-pt.rechazar', solicitudId), { // 🚨 RUTA DELETE
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Solicitud HE PT rechazada exitosamente!');
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
                <Button variant="destructive" className="hover-default" size="sm">
                    <SquareX/>
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Rechazar Solicitud HE PT</DialogTitle>
                <DialogDescription>
                    Ingresa el motivo de rechazo.
                </DialogDescription>
                <form className="space-y-6" onSubmit={rechazarSolicitud}>
                    <div className="grid gap-2">
                        <Textarea
                            id="observaciones"
                            className="mt-1 block w-full"
                            value={data.observaciones}
                            ref={motivoRechazoInput}
                            onChange={(e) => setData('observaciones', e.target.value)}
                            required
                            placeholder="Descripción del motivo de rechazo"
                        />
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
