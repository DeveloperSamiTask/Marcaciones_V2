import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Printer, SquareCheckBig } from 'lucide-react';
import { FormEventHandler, useState } from 'react';
import { toast } from 'sonner';

export default function PrintMemorandum({ memorandumId, tipo, isSuspension }: { memorandumId: number; tipo: string; isSuspension: boolean }) {
    const [open, setOpen] = useState(false);
    const { data, post, setData, processing, reset, errors, clearErrors } = useForm<Required<{ marcacion_id: number, tipo: string }>>
        ({ marcacion_id: memorandumId, tipo: tipo });

    const createSuspension: FormEventHandler = (e) => {
        e.preventDefault();

        if(isSuspension){
            print();
        }else{
            post(route('suspensiones.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Amonestacion creada exitosamente!', {
                        richColors: true,
                        position: 'top-center',
                        duration: 4000,
                    });
                    print();
                },
                onError: (errors) => {
                    const messageError = errors.message && errors.message != '' ? errors.message : 'Ocurrio un error inesperado';
                    toast.error(messageError, {
                        richColors: true,
                        position: 'top-center',
                        duration: 6000,
                    });
                },
                onFinish: () => reset()
            });
        }
    };

    const closeModal = () => {
        clearErrors();
        reset();
        setOpen(false);
    };

    const print = () => {
        const printUrl = `${route('memorandums.imprimir', memorandumId)}?tipo=${tipo}`;
        const printWindow = window.open(printUrl, '_blank', 'width=800,height=600');
        if (printWindow) {
            // Esperamos a que cargue la página y disparamos print
            closeModal();
            printWindow.onload = () => {
                printWindow.focus();
                printWindow.print();
            };
        } else {
            alert("No se pudo abrir la ventana de impresión. Desactiva el bloqueador de ventanas emergentes.");
        }

    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant={isSuspension ? 'destructive' : 'info'} className="hover-info" size="sm">
                    <Printer />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Imprimir memorandum</DialogTitle>
                <DialogDescription>
                    ¿Estas seguro de autorizar este permiso?
                    <br />
                    Se realizara una amonestacion cuando imprima el memorandum
                </DialogDescription>
                <form className="space-y-6" onSubmit={createSuspension}>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancelar
                            </Button>
                        </DialogClose>

                        <Button type='submit' disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Imprimir
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
