import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { LoaderCircle, Upload } from 'lucide-react';
import { toast } from 'sonner';

export default function ExcedenteHorario({ permisoId } : {permisoId : number}) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, reset, errors, clearErrors } = useForm<Required<{comprobante: File | null }>>({ comprobante: null });
    const [open, setOpen] = useState(false); // para cerrar el modal

    const uploadPermiso: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('permisos.upload', permisoId), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Comprobante subido exitosamente!', {
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

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] || null;
        setData('comprobante', file);
    };

    const closeModal = () => {
        clearErrors();
        reset();
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="secondary" className="hover-default" size="sm">
                    <Upload/>
                </Button>

            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Subir archivo</DialogTitle>
                <DialogDescription>
                    Este comprobante es un sustento para validar el permiso
                </DialogDescription>
                <form className="space-y-6" onSubmit={uploadPermiso}>
                    <div className="grid gap-2">
                        <Input
                            id="comprobante"
                            type="file"
                            name="comprobante"
                            ref={fileInputRef}
                            required
                            onChange={handleFileChange}
                            capture="environment"
                            accept="image/*,.pdf"
                        />

                        <InputError message={errors.comprobante} />
                    </div>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancelar
                            </Button>
                        </DialogClose>

                        <Button type='submit' disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Subir
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
