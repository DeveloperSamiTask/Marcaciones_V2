import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Marcacion } from '@/types/marcaciones';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Send } from 'lucide-react';
import { FormEventHandler, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

interface Filters {
    empresa: number | null;
    encargado: number | null;
    dateRange?: {
        from: Date;
        to: Date;
    };
}

interface SendMarcacionProps {
    marcaciones: Marcacion[];
    filters: Filters;
    getSelectedData?: () => Marcacion[]; // Función para obtener seleccionados
}

export default function SendMarcacion({ marcaciones, filters, getSelectedData }: SendMarcacionProps) {
    const { empresa, encargado, dateRange } = filters;
    const [marcacionesToSend, setMarcacionesToSend] = useState<Marcacion[]>([]);
    const conceptoInput = useRef<HTMLTextAreaElement>(null);
    const [open, setOpen] = useState(false);

    const handleSend = () => {
        const selectedData = getSelectedData ? getSelectedData() : [];
        const newDataToSend = selectedData.length > 0 ? selectedData : marcaciones;
        setMarcacionesToSend(newDataToSend);
    };

    const { data, post, processing, setData, reset, errors, clearErrors } = useForm({
        marcaciones: JSON.stringify(marcacionesToSend),
        empresa,
        encargado,
        fechaInicio: dateRange?.from?.toISOString?.() ?? null,
        fechaFin: dateRange?.to?.toISOString?.() ?? null,
        concepto: '',
    });

    useEffect(() => {
        setData({
            marcaciones: JSON.stringify(marcacionesToSend),
            empresa,
            encargado,
            fechaInicio: dateRange?.from?.toISOString() ?? null,
            fechaFin: dateRange?.to?.toISOString() ?? null,
            concepto: data.concepto, // Mantenemos el concepto existente
        });
    }, [marcacionesToSend, empresa, encargado, dateRange]);

    const sendMarcacion: FormEventHandler = (e) => {
        e.preventDefault();

        const invalidMarcaciones = marcacionesToSend.filter((m) => !m.horario || m.horario.estado === 'PE');

        if(!encargado){
            return toast.error('Se debe seleccionar un encargado', {
                richColors: true,
                position: 'top-center',
                duration: 6000,
            });
        }

        if (invalidMarcaciones.length > 0) {
            return toast.error('Existen marcaciones sin horario válido o en estado pendientes', {
                richColors: true,
                position: 'top-center',
                duration: 6000,
            });
        }

        post(route('asistencias.store'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Marcaciones enviadas exitosamente!', {
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
        reset('concepto');
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button onClick={handleSend} disabled={marcaciones.length == 0}>
                    <Send />
                    <span className="hidden sm:inline">Enviar</span>
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Enviar asistencias {getSelectedData?.()?.length ? 'seleccionadas' : ''}</DialogTitle>
                <DialogDescription>¿Estas seguro de enviar estas asistencias?</DialogDescription>

                <form className="space-y-6" onSubmit={sendMarcacion}>

                    <div className="grid gap-2">
                        <Textarea
                            id="concepto"
                            className="mt-1 block w-full"
                            value={data.concepto}
                            tabIndex={1}
                            ref={conceptoInput}
                            onChange={(e) => setData('concepto', e.target.value)}
                            autoComplete="concepto"
                            placeholder="Describe el concepto de envio"
                        />

                        <InputError message={errors.concepto} />
                    </div>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancelar
                            </Button>
                        </DialogClose>

                        <Button type="submit" disabled={processing || marcaciones.length == 0}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            {getSelectedData?.()?.length ? 'Enviar seleccionados' : 'Enviar'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
