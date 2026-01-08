import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Eye, Clock } from 'lucide-react';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import { toast } from 'sonner';

// Reutilizamos tu formateador
const formatMinutes = (minutes: number): string => {
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    return `${String(hours).padStart(2, '0')}:${String(remainingMinutes).padStart(2, '0')}`;
};

interface DetalleExtraProps {
    empleadoId: number;
    fechaInicio?: string;
    fechaFin?: string;
}

export default function DetalleHorasExtraModal({ empleadoId, fechaInicio, fechaFin }: DetalleExtraProps) {
    const [loading, setLoading] = useState(false);
    const [data, setData] = useState<{ empleado: string, detalle: any[] } | null>(null);

    const fetchDetalle = async () => {
        if (!fechaInicio || !fechaFin) {
            toast.error("Seleccione un rango de fechas en el reporte");
            return;
        }

        setLoading(true);
        try {
            const url = route('reportes.extraDetalle', {
                empleado_id: empleadoId,
                fechaInicio,
                fechaFin
            });

            const response = await fetch(url);

            // Si el servidor responde pero con error (ej. 500), fetch no cae al catch solo
            if (!response.ok) {
                const errorText = await response.text();
                console.error("Error del servidor:", errorText);
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            console.log("Datos recibidos:", result); // <-- Mira esto en la consola
            setData(result);
        } catch (error: any) {
            console.error("Error completo:", error);
            toast.error("Error al cargar: " + error.message);
        } finally {
            setLoading(false);
        }
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="ghost" size="icon" onClick={fetchDetalle} className="hover:text-blue-600">
                    <Eye className="h-5 w-5" />
                </Button>
            </DialogTrigger>

            <DialogContent className="max-w-2xl max-h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold border-b pb-2">
                        Detalle Diario de Horas Extra
                    </DialogTitle>
                    {data && (
                        <p className="text-sm text-muted-foreground uppercase font-semibold">
                            {data?.empleado.nombres}
                        </p>
                    )}
                </DialogHeader>

                <div className="flex-1 overflow-y-auto py-4 space-y-3">
                    {loading ? (
                        <div className="flex justify-center p-10 italic text-muted-foreground">Cargando datos...</div>
                    ) : data?.detalle.map((dia, index) => (
                        <div key={index} className="flex items-center justify-between p-3 border rounded-lg hover:bg-slate-50 transition-colors">
                            <div className="space-y-1">
                                <p className="text-sm font-bold text-slate-700">
                                    {format(parseISO(dia.fecha), "eeee dd 'de' MMMM", { locale: es })}
                                </p>
                                <div className="flex gap-4 text-xs text-muted-foreground">
                                    <span>Prog: <b className="text-slate-600">{dia.programada}</b></span>
                                    <span>Real: <b className="text-slate-600">{dia.marcada}</b></span>
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                <div className="text-right">
                                    <p className={`font-mono font-bold ${dia.minutos > 0 ? 'text-red-500' : 'text-slate-400'}`}>
                                        +{formatMinutes(dia.minutos)}
                                    </p>
                                    <Badge
                                        variant={dia.estado_he === 1 ? "success" : "warning"}
                                        className="text-[10px] px-1 py-0"
                                    >
                                        {dia.estado_he === 1 ? 'APROBADO' : 'PENDIENTE'}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    ))}

                    {!loading && data?.detalle.length === 0 && (
                        <p className="text-center text-muted-foreground">No hay registros en este rango.</p>
                    )}
                </div>
                <DialogFooter className="bg-slate-50 p-4 border-t sm:justify-between items-center">
                    {/*<div className="text-left">
                        <p className="text-xs text-slate-500 uppercase">Total Aprobado en Rango:</p>
                        <p className="text-xl font-black text-blue-600">
                            {formatMinutes(data?.detalle.filter(d => d.estado_he === 1).reduce((acc, curr) => acc + curr.minutos, 0) || 0)}
                        </p>
                    </div>  */}

                    <DialogClose asChild>
                        <Button variant="secondary">Cerrar</Button>
                    </DialogClose>
                </DialogFooter>

            </DialogContent>
        </Dialog>
    );
}
