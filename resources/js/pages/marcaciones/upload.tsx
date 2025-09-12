import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { LoaderCircle, Upload } from 'lucide-react';
import { toast } from 'sonner';

export default function UploadMarcacion({ marcacionId, disabled } : {marcacionId : number, disabled: boolean}) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, reset, errors, clearErrors } = useForm<Required<{sustento: File | null }>>({ sustento: null });


    const uploadMarcacion: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('marcaciones.upload', marcacionId), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Sustento subido exitosamente!', {
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
        setData('sustento', file);
    };

    const closeModal = () => {
        clearErrors();
        reset();
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="secondary" className="hover-default" size="sm" disabled={disabled}>
                    <Upload/>
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Subir archivo</DialogTitle>
                <DialogDescription>
                    Este comprobante es un sustento para validar la asistencia
                </DialogDescription>
                <form className="space-y-6" onSubmit={uploadMarcacion}>
                    <div className="grid gap-2">
                        <Input
                            id="sustento"
                            type="file"
                            name="sustento"
                            ref={fileInputRef}
                            required
                            onChange={handleFileChange}
                            accept=".pdf,.jpg,.jpeg,.png"
                        />

                        <InputError message={errors.sustento} />
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
