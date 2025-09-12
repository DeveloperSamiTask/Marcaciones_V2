import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { CircleAlert, LoaderCircle } from 'lucide-react';
import { toast } from 'sonner';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

export default function ModalAsitencia({ detalleId, extra } : {detalleId : number; extra?: string}) {
    const { post, processing, reset, clearErrors } = useForm<Required<{ detalle_id: number, extra: string }>>({ detalle_id: detalleId, extra: extra || '' });

    const sendHorasExtra: FormEventHandler = (e) => {
        e.preventDefault();
        console.log(extra);

        post(route('asistencias.horasExtra', detalleId), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Enviado para su aprobación!', {
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
            <DialogTrigger>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <CircleAlert className='w-4 text-yellow-600 cursor-pointer'/>
                    </TooltipTrigger>
                    <TooltipContent color='red'>
                        <p>Horas extra no aprobado</p>
                    </TooltipContent>
                </Tooltip>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Aprobar horas extra</DialogTitle>
                <DialogDescription>
                    Se enviará al area respectiva para que se apruebe las horas extra
                </DialogDescription>
                <form className="space-y-6" onSubmit={sendHorasExtra}>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancelar
                            </Button>
                        </DialogClose>

                        <Button type='submit' disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Enviar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
