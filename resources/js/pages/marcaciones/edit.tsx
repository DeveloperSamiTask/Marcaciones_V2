import { useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { LoaderCircle } from 'lucide-react';
import { toast } from 'sonner';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { Textarea } from '@/components/ui/textarea';
import { Horario } from '@/types/horarios';
import { format } from 'date-fns';
import { useMemo } from 'react';
import axios from 'axios';

type TipoMarcacion = 'ingreso' | 'salida' | 'ingreso_refri' | 'salida_refri';

export default function EditMarcacion({
    marcacionId,
    tipo,
    marcacionHora,
    disabled,
    hsp,
    empleadoId,
    fechaInicio,
    fechaFin
}: {
    marcacionId: number,
    tipo: TipoMarcacion,
    marcacionHora: string,
    disabled: boolean,
    hsp: string,
    empleadoId: number,
    fechaInicio?: string,
    fechaFin?: string
}) {
    const horaInput = useRef<HTMLInputElement>(null);
    const motivoInput = useRef<HTMLTextAreaElement>(null);
    const [open, setOpen] = useState(false);

    const { data, patch, processing, setData, reset, errors, clearErrors } = useForm<{
        empleado_id: number;
        hora_original: string;
        hora_nueva: string;
        tipo: string;
        motivo: string;
        extraSeleccionada?: string;
        hsp: string;
        tiempo_extra: string;

    }>({
        empleado_id: empleadoId,
        hora_original: marcacionHora,
        hora_nueva: marcacionHora,
        tipo: tipo,
        motivo: '',
        extraSeleccionada: '',
        hsp: hsp || '',
        tiempo_extra: ''
    });

    const [listaExtrasBack, setListaExtrasBack] = useState<Horario[]>([]);
    const [cargandoExtras, setCargandoExtras] = useState(false);

    useEffect(() => {
        if (open && empleadoId) {
            setListaExtrasBack([]);
            setCargandoExtras(true);

            axios.get(route('marcaciones.extras', { empleado: empleadoId }), {
                params: {
                    fechaInicio: fechaInicio,
                    fechaFin: fechaFin
                }
            })
                .then(res => {
                    console.log("✅ DATOS DEL SERVIDOR:", res.data);
                    setListaExtrasBack(res.data);
                })
                .catch(err => {
                    console.error("❌ ERROR:", err);
                })
                .finally(() => {
                    setCargandoExtras(false);
                });
        }
    }, [open, empleadoId, fechaInicio, fechaFin]);

    const extrasFiltradas = useMemo(() => {
        console.log("📋 Lista a renderizar:", listaExtrasBack);
        return listaExtrasBack;
    }, [listaExtrasBack]);

    const tipoFormateado: Record<TipoMarcacion, string> = {
        ingreso: 'ingreso',
        salida: 'salida',
        ingreso_refri: 'ingreso de refrigerio',
        salida_refri: 'salida de refrigerio',
    };

    useEffect(() => {
        if (!open) {
            clearErrors();
            reset();
            setData('motivo', '');
            setData('extraSeleccionada', '');
            setHoraOriginal(marcacionHora);
            setHoraActual(marcacionHora);
            setHoraDescontada("");
            setListaExtrasBack([]);
            setCargandoExtras(false);
        }
    }, [open]);

    const [horaOriginal, setHoraOriginal] = useState(marcacionHora);
    const [horaActual, setHoraActual] = useState(marcacionHora);
    const [horaDescontada, setHoraDescontada] = useState("");

    const calcularDiferencia = (base: string, nueva: string) => {
        if (!base || !nueva) return "";

        const [h1, m1] = base.split(":").map(Number);
        const [h2, m2] = nueva.split(":").map(Number);

        const minutosBase = h1 * 60 + m1;
        const minutosNueva = h2 * 60 + m2;

        const diff = Math.abs(minutosNueva - minutosBase);
        const horas = Math.floor(diff / 60);
        const minutos = diff % 60;

        return `${horas.toString().padStart(2, "0")}:${minutos.toString().padStart(2, "0")}`;
    };

    const closeModal = () => {
        clearErrors();
        reset();
        setData('motivo', '');
        setData('extraSeleccionada', '');
        setHoraOriginal(marcacionHora);
        setHoraActual(marcacionHora);
        setHoraDescontada("");
        setListaExtrasBack([]);
        setCargandoExtras(false);
        setOpen(false);
    };

    const updateMarcacion: FormEventHandler = (e) => {
        e.preventDefault();

        // console.log("=== ENVIANDO AL BACKEND ===");
        // console.log("hora_original:", data.hora_original);
        // console.log("hora_nueva:", data.hora_nueva);
        // console.log("tiempo_extra:", data.tiempo_extra);
        // console.log("extraSeleccionada:", data.extraSeleccionada);
        // console.log("hsp:", hsp);
        // console.log("tipo:", data.tipo);

        console.log("🚀 ENVIANDO AL BACKEND:", {
            extraId: data.extraSeleccionada,
            tiempoARestar: data.tiempo_extra,
            nuevaHora: data.hora_nueva
        });


        patch(route('marcaciones.update', marcacionId), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                toast.success('Marcación actualizada exitosamente!', {
                    richColors: true,
                    position: 'top-center',
                    duration: 4000,
                });
            },
            onError: (errors) => {
                const messageError = errors.message && errors.message != '' ? errors.message : 'Ocurrió un error inesperado';
                toast.error(messageError, {
                    richColors: true,
                    position: 'top-center',
                    duration: 6000,
                });
            },
            onFinish: () => reset(),
        });
    };

    const formatMinutes = (value: any): string => {
        const total = parseInt(value, 10);
        if (isNaN(total) || total <= 0) return '00:00';

        const h = Math.floor(total / 60);
        const m = total % 60;

        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
    };



    console.log("🎨 RENDERIZANDO - extrasFiltradas.length:", extrasFiltradas.length);

    // DEBUG: Inspeccionar TODO lo que entra al componente
    console.log("=== PROPS RECIBIDAS ===");
    console.log("marcacionId:", marcacionId);
    console.log("empleadoId:", empleadoId);
    console.log("fechaInicio:", fechaInicio);
    console.log("fechaFin:", fechaFin);

    // DEBUG: Inspeccionar el DOM real
    if (open) {
        setTimeout(() => {
            const selectElement = document.getElementById('extraSeleccionada') as HTMLSelectElement;
            if (selectElement) {
                console.log("=== SELECT EN EL DOM ===");
                console.log("Cantidad de opciones:", selectElement.options.length);
                Array.from(selectElement.options).forEach((option, index) => {
                    if (index > 0) { // Saltar el "-- Selecciona --"
                        console.log(`Opción ${index}:`, {
                            value: option.value,
                            text: option.textContent
                        });
                    }
                });
            }
        }, 500);
    }

    // console.log("🎨 RENDERIZANDO - extrasFiltradas.length:", extrasFiltradas.length);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" className="hover-ghost" size="sm" disabled={disabled}>
                    {marcacionHora}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Editar marcación</DialogTitle>
                <DialogDescription>
                    Ingrese hora de {tipoFormateado[tipo]}
                </DialogDescription>

                <form className="space-y-6" onSubmit={updateMarcacion}>
                    <div className="grid gap-2">
                        <Input
                            readOnly
                            id="hora_nueva"
                            type="time"
                            name="hora_nueva"
                            className="mt-1 block w-full"
                            tabIndex={1}
                            ref={horaInput}
                            value={horaActual}
                            onChange={(e) => {
                                setHoraActual(e.target.value);
                            }}
                            onBlur={(e) => {
                                const nuevaHora = e.target.value;
                                const resultado = calcularDiferencia(horaOriginal, nuevaHora);
                                setHoraDescontada(resultado);

                                setData("hora_original", horaOriginal);
                                setData("hora_nueva", nuevaHora);
                                setData("tiempo_extra", resultado);
                                setData("hsp", hsp);
                            }}
                        />
                        <InputError message={errors.hora_nueva} />
                    </div>


                    {!cargandoExtras && listaExtrasBack && listaExtrasBack.length > 0 && (
                        <div className="grid gap-2">
                            <label htmlFor="extraSeleccionada" className="font-semibold">
                                Selecciona una hora extra disponible:
                            </label>
                            <select
                                name="extraSeleccionada"
                                id="extraSeleccionada"
                                className="border rounded px-3 py-2 text-black bg-white"
                                value={data.extraSeleccionada}
                                onChange={(e) => {
                                    console.log("🔍 SELECCIONANDO EXTRA ID:", e.target.value);
                                    console.log("🔍 EXTRA COMPLETA:", extrasFiltradas.find(x => x.id == e.target.value));
                                    setData('extraSeleccionada', e.target.value);
                                }}
                            >
                                <option value="">-- Selecciona una opción --</option>
                                {extrasFiltradas.map((extra) => {
                                    console.log("🟢 RENDERIZANDO OPTION:", extra);
                                    return (
                                        <option key={extra.id} value={extra.id}>
                                            {formatMinutes(extra.extra)} hs (Día {extra.fecha.split('-').reverse().join('/')})

                                        </option>
                                    );
                                })}
                            </select>
                            <InputError message={errors.extraSeleccionada} />
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Textarea
                            id="motivo"
                            name="motivo"
                            required
                            tabIndex={2}
                            className="mt-1 block w-full"
                            ref={motivoInput}
                            value={data.motivo}
                            onChange={(e) => setData('motivo', e.target.value)}
                            placeholder="Descripcion del motivo"
                        />
                        <InputError message={errors.motivo} />
                    </div>

                    {cargandoExtras && (
                        <p className="text-sm text-gray-500">Cargando horas extras disponibles...</p>
                    )}

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancelar
                            </Button>
                        </DialogClose>

                        <Button type='submit' disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Editar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
