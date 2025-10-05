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
export default function ModalUsuarios({
    usuarioId,
    tipoMovimiento,
}: {
    usuarioId: number;
    tipoMovimiento: 'archivado' | 'reactivacion';
}) {
    const fechaInput = useRef<HTMLInputElement>(null);
    const motivoInput = useRef<HTMLTextAreaElement>(null);

    const { data, setData, post, processing, reset, errors, clearErrors } = useForm({
        fecha_cambio: '',
        motivo: '',
        tipo_movimiento: tipoMovimiento,
        usuario_id: usuarioId,
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('movimiento.toggle_usuarios'), {
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
                    tipoMovimiento === 'archivado'
                        ? 'Usuario cesado exitosamente.'
                        : 'Usuario reactivado exitosamente.',
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
                    variant={tipoMovimiento === 'archivado' ? 'destructive' : 'default'}
                    className="hover:bg-muted"
                    size="sm"
                >
                    {tipoMovimiento === 'archivado' ? <Trash2 /> : <RotateCcw />}
                </Button>
            </DialogTrigger>

            <DialogContent>
                <DialogTitle>
                    {tipoMovimiento === 'archivado' ? 'Cesar Usuario' : 'Reactivar Usuario'}
                </DialogTitle>
                <DialogDescription>
                    {tipoMovimiento === 'archivado'
                        ? 'Ingresa la fecha de cese y el motivo para continuar.'
                        : 'Ingresa la fecha de reactivación y el motivo para continuar.'}
                </DialogDescription>

                <form className="space-y-6" onSubmit={handleSubmit}>
                    <div className="grid gap-2">
                        <Label htmlFor="fecha_cambio">
                            {tipoMovimiento === 'archivado' ? 'Fecha de cese' : 'Fecha de reactivación'}
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
                        <InputError message={errors.message} />
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

                        <Button type="submit" variant={tipoMovimiento === 'archivado' ? 'destructive' : 'default'} disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            {tipoMovimiento === 'archivado' ? 'Cesar Usuario' : 'Reactivar Usuario'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
