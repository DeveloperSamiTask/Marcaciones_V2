import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Permiso } from '@/types/permisos';
import { Suspension } from '@/types/suspensiones';
import { LoaderCircle, Printer } from 'lucide-react';
import { useRef, useState } from 'react';

export default function PrintPermiso({ permiso, isPrint }: { permiso: Permiso, isPrint: boolean }) {
    const fechaInput = useRef<HTMLInputElement>(null);
    const [processing, setProcessing] = useState(false); // para la carga

    const print = () => {
        setProcessing(true);
        const printUrl = `${route('permisos.imprimir', permiso.id)}`;
        const printWindow = window.open(printUrl, '_blank', 'width=800,height=600');
        if (printWindow) {
            // Esperamos a que cargue la página y disparamos print
            printWindow.onload = () => {
                printWindow.focus();
                printWindow.print();
            };
            setProcessing(false);
        } else {
            setProcessing(false);
            alert('No se pudo abrir la ventana de impresión. Desactiva el bloqueador de ventanas emergentes.');
        }
    };

    return (
        <Button variant={isPrint ? 'destructive' : 'info'} onClick={print} disabled={processing} className="hover-info" size="sm">
            {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <Printer />}
        </Button>
    );
}
