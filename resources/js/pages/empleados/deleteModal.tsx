import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { LoaderCircle, Trash2, RotateCcw } from 'lucide-react';
import { toast } from 'sonner';

export default function ModalEmpleado({
    empleadoId,
    tipoMovimiento,
}: {
    empleadoId: number;
    tipoMovimiento: 'cese' | 'reactivacion';
}) {
    const fechaInput = useRef<HTMLInputElement>(null);
    const motivoInput = useRef<HTMLTextAreaElement>(null);

    const { data, setData, post, processing, reset, errors, clearErrors } = useForm({
        fecha_cambio: '',
        motivo: '',
        tipo_movimiento: tipoMovimiento,
        empleado_id: empleadoId,
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('movimiento.toggle'), {
            preserveScroll: true,
            onError: (errors) => {
                const messageError =
                    errors.message && errors.message !== ''
                        ? errors.message
                        : 'Ocurrió un error inesperado';
                fechaInput.current?.focus();
                toast.error(messageError, {
                    richColors: true,
                    position: 'top-center',
                    duration: 6000,
                });
            },
            onSuccess: () => {
                toast.success(
                    tipoMovimiento === 'cese'
                        ? 'Empleado cesado exitosamente.'
                        : 'Empleado reactivado exitosamente.',
                    {
                        richColors: true,
                        position: 'top-center',
                        duration: 5000,
                    }
                );
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
                <Button
                    variant={tipoMovimiento === 'cese' ? 'destructive' : 'default'}
                    className="hover:bg-muted"
                    size="sm"
                >
                    {tipoMovimiento === 'cese' ? <Trash2 /> : <RotateCcw />}
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogTitle>
                    {tipoMovimiento === 'cese' ? 'Cesar empleado' : 'Reactivar empleado'}
                </DialogTitle>
                <DialogDescription>
                    {tipoMovimiento === 'cese'
                        ? 'Ingresa la fecha de cese y el motivo para continuar.'
                        : 'Ingresa la fecha de reactivación y el motivo para continuar.'}
                </DialogDescription>

                <form className="space-y-6" onSubmit={handleSubmit}>
                    <div className="grid gap-2">
                        <Label htmlFor="fecha_cambio">
                            {tipoMovimiento === 'cese' ? 'Fecha de cese' : 'Fecha de reactivación'}
                        </Label>
                        <Input
                            id="fecha_cambio"
                            type="date"
                            name="fecha_cambio"
                            required
                            ref={fechaInput}
                            value={data.fecha_cambio}
                            onChange={(e) => setData('fecha_cambio', e.target.value)}
                        />
                        <InputError message={errors.fecha_cambio} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="motivo">Motivo</Label>
                        <textarea
                            id="motivo"
                            name="motivo"
                            rows={3}
                            required
                            className="w-full rounded-md border px-3 py-2 resize-vertical"
                            ref={motivoInput}
                            value={data.motivo}
                            onChange={(e) => setData('motivo', e.target.value)}
                        />
                        <InputError message={errors.motivo} />
                    </div>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancelar
                            </Button>
                        </DialogClose>

                        <Button type="submit" variant="destructive" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            {tipoMovimiento === 'cese' ? 'Cesar' : 'Reactivar'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
