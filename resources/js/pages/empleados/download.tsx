import { Button } from '@/components/ui/button';
import { Empleado } from '@/types/empleados';
import { usePage } from '@inertiajs/react';
import { DownloadIcon, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';




export default function DownloadEmpleado({ disabled, empleados, cesado }: {
    disabled: boolean;
    empleados: Empleado[];
    cesado: number;
}) {

    const { csrf_token } = usePage().props;
    const [processing, setProcessing] = useState(false);

    const download = async (e) => {
        e?.preventDefault();  // ← IMPORTANTE: Prevenir comportamiento por defecto

        try {
            setProcessing(true);
            const params = {
                empleados: JSON.stringify(empleados),
            };

            // ✅ DECIDIR RUTA SEGÚN CESADO
            const ruta = cesado === 1
                ? route('empleados.download-cesados')
                : route('empleados.download');

            console.log('🔗 RUTA COMPLETA:', ruta);

            const response = await fetch(ruta, {
                method: 'POST',
                headers: {
                    'Accept': 'application/vnd.ms-excel',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': `${csrf_token}`,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(params)
            });

            if (!response.ok) throw new Error('No se pudo completar la descarga');

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'empleados.xlsx';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
            setProcessing(false);

        } catch (error) {
            setProcessing(false);
            console.error('Error:', error);
            toast.error(`${error}`, {
                richColors: true,
                position: 'top-center',
                duration: 6000,
            });
        }
    };

    return (
        <Button
            variant="info"
            onClick={download}
            className="gap-2"
            disabled={disabled || processing}
        >
            {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <DownloadIcon className="h-4 w-4" />}
            {!processing ? (<span className="hidden sm:inline">Exportar a excel</span>) : (<span className="hidden sm:inline">Exportando ...</span>)}
        </Button>
    );
}
