import { Button } from '@/components/ui/button';
import { Marcacion } from '@/types/marcaciones';
import { usePage } from '@inertiajs/react';
import { DownloadIcon, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface Filters {
    empresa: number | null;
    encargado: number | null;
    dateRange?: {
        from: Date;
        to: Date;
    };
}

export default function DownloadMarcacion({ disabled, marcaciones, filters }: { disabled: boolean; marcaciones: Marcacion[]; filters: Filters }) {
    const { empresa, encargado, dateRange } = filters;
    const { csrf_token } = usePage().props;
    const [processing, setProcessing] = useState(false); // para la carga

    const download = async () => {
        try {
            setProcessing(true);
            const params = {
                marcaciones: JSON.stringify(marcaciones),
                empresa,
                encargado,
                fechaInicio: dateRange?.from?.toISOString().split('T')[0] ?? null,
                fechaFin: dateRange?.to?.toISOString().split('T')[0] ?? null,
            };

            const response = await fetch(route('marcaciones.download'), {
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
            link.download = 'marcaciones.xlsx';
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
