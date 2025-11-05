import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { Empresa } from '@/types/empresas';
import { Marcacion } from '@/types/marcaciones';
import { useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { CalendarIcon, LoaderCircle, RefreshCcw } from 'lucide-react';
import { FormEventHandler, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';


export default function PullMarcacion({ empresaId }: { empresaId: number | null }) {

    const fechaInput = useRef<HTMLInputElement>(null);
    const [open, setOpen] = useState(false);

    const { data, post, processing, setData, reset, errors, clearErrors } = useForm({ empresa: empresaId, fecha: '' });

    useEffect(() => {
        setData({
            empresa: empresaId,
            fecha: '',
        });
    }, [empresaId]);

    const pullMarcacion: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('marcaciones.pull'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Asistencias actualizadas exitosamente!', {
                    richColors: true,
                    position: 'top-center',
                    duration: 4000,
                });
                setOpen(false);
                closeModal();
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
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant='warning' disabled={!empresaId}>
                    <RefreshCcw />
                    <span className="hidden sm:inline">Actualizar</span>
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Actualizar asistencias</DialogTitle>
                <DialogDescription>Selecciona la fecha inicial para la actualizacion</DialogDescription>

                <form className="space-y-6" onSubmit={pullMarcacion}>

                    <div className="grid gap-2">
                        <Input
                            id="fecha"
                            type='date'
                            className="mt-1 block w-full"
                            value={data.fecha}
                            tabIndex={1}
                            ref={fechaInput}
                            onChange={(e) => setData('fecha', e.target.value)}
                            required
                            autoComplete="fecha"
                        />

                        <InputError message={errors.fecha} />
                    </div>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancelar
                            </Button>
                        </DialogClose>

                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Actualizar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
