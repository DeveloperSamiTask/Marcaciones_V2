import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Eye, Clock } from 'lucide-react';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';
import { toast } from 'sonner';
import { useMemo } from 'react';  // ← Agrega esto

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

    // 1. NUEVO ESTADO: Para controlar qué filtro mostrar (todos, aprobados o pendientes)
    const [filtro, setFiltro] = useState<'todos' | 'aprobados' | 'pendientes'>('todos');

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
            setFiltro('todos');
        } catch (error: any) {
            console.error("Error completo:", error);
            toast.error("Error al cargar: " + error.message);
        } finally {
            setLoading(false);
        }
    };

    // 1. CORRECCIÓN LÓGICA: Usar el nombre exacto de la propiedad del backend
    const detalleFiltrado = useMemo(() => {
        if (!data?.detalle) return [];

        const filtrados = data.detalle.filter(dia => {
            // Importante: Verifica si en tu consola el backend manda 'estado_he' o 'estado_horas_extra'
            const estado = dia.estado_he;
            if (filtro === 'aprobados') return estado === 1;
            if (filtro === 'pendientes') return estado !== 1;
            return true;
        });

        console.log(`🎯 FILTRO ACTIVO: ${filtro}`, { total: filtrados.length, datos: filtrados });
        return filtrados;
    }, [data, filtro]);


    // 2. TOTALES
    const totalAprobado = data?.detalle
        ?.filter(d => d.estado_he === 1)
        .reduce((acc, curr) => acc + curr.minutos, 0) || 0;

    const totalPendiente = data?.detalle
        ?.filter(d => d.estado_he !== 1)
        .reduce((acc, curr) => acc + curr.minutos, 0) || 0;

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
                    <div className="flex gap-2 mt-4">
                        <Button
                            variant={filtro === 'todos' ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setFiltro('todos')}
                        >
                            Todos ({data?.detalle.length || 0})
                        </Button>
                        <Button
                            // Cambiamos 'success' por 'outline' + clase manual para evitar error de tipos
                            variant={filtro === 'aprobados' ? 'default' : 'outline'}
                            className={filtro === 'aprobados' ? 'bg-green-600 hover:bg-green-700' : ''}
                            size="sm"
                            onClick={() => setFiltro('aprobados')}
                        >
                            Aprobados
                        </Button>
                        <Button
                            variant={filtro === 'pendientes' ? 'default' : 'outline'}
                            className={filtro === 'pendientes' ? 'bg-orange-500 hover:bg-orange-600' : ''}
                            size="sm"
                            onClick={() => setFiltro('pendientes')}
                        >
                            Pendientes
                        </Button>
                    </div>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto py-4 space-y-3">
                    {loading ? (
                        <div className="flex justify-center p-10 italic">Cargando...</div>
                    ) : (
                        // 3. CAMBIO CLAVE: Mapear 'detalleFiltrado' en lugar de 'data.detalle'
                        detalleFiltrado.map((dia, index) => (
                            <div key={index} className="flex items-center justify-between p-3 border rounded-lg">
                                <div className="space-y-1">
                                    <p className="text-sm font-bold text-slate-700 uppercase">
                                        {format(parseISO(dia.fecha), "eeee dd MMM", { locale: es })}
                                    </p>
                                    <div className="flex gap-4 text-xs text-muted-foreground">
                                        <span>Prog: <b>{dia.programada}</b></span>
                                        <span>Real: <b>{dia.marcada}</b></span>
                                    </div>
                                </div>

                                <div className="text-right">
                                    <p className="font-mono font-bold text-red-500">+{formatMinutes(dia.minutos)}</p>
                                    <Badge variant={dia.estado_he === 1 ? "success" : "warning"}>
                                        {dia.estado_he === 1 ? 'APROBADO' : 'PENDIENTE'}
                                    </Badge>
                                </div>
                            </div>
                        ))
                    )}
                </div>

                {/* 5. FOOTER REFACTORIZADO: Muestra ambos totales de forma visual */}
                <DialogFooter className="bg-slate-50 p-4 border-t flex-row justify-between items-center">
                    <div className="flex gap-6">
                        <div>
                            <p className="text-[10px] text-orange-600 font-bold uppercase">Pendiente</p>
                            <p className="text-lg font-black">{formatMinutes(totalPendiente)}</p>
                        </div>
                        <div className="border-l pl-6">
                            <p className="text-[10px] text-green-600 font-bold uppercase">Aprobado</p>
                            <p className="text-lg font-black">{formatMinutes(totalAprobado)}</p>
                        </div>
                    </div>
                    <DialogClose asChild><Button variant="secondary">Cerrar</Button></DialogClose>
                </DialogFooter>

            </DialogContent>
        </Dialog>
    );
}
