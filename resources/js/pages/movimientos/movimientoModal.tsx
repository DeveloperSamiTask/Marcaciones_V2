import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Movimiento } from "@/types/movimientos";
import { format } from "date-fns";

interface Props {
  movimiento: Movimiento;
  open: boolean;
  onClose: () => void;
}

export default function MovimientoModal({ movimiento, open, onClose }: Props) {
  return (
    <Dialog open={open} onOpenChange={onClose}>
      <DialogContent className="max-w-6xl p-4 md:p-8">
        <DialogHeader>
          <DialogTitle className="text-2xl sm:text-4xl font-bold tracking-tight">
            Detalle del Movimiento
          </DialogTitle>
        </DialogHeader>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
          <div>
            <Label>CÓDIGO</Label>
            <Input value={String(movimiento.id)} readOnly />
          </div>

          <div>
            <Label>NOMBRES</Label>
            <Input value={movimiento.empleado} readOnly />
          </div>

          <div>
            <Label>DNI</Label>
            <Input value={movimiento.dni} readOnly />
          </div>

          <div>
            <Label>TIPO DE MOVIMIENTO</Label>
            <Input value={movimiento.tipo_movimiento.toUpperCase()} readOnly />
          </div>

          <div>
            <Label>FECHA DE MOVIMIENTO</Label>
            <Input
              value={format(new Date(movimiento.fecha_movimiento), "dd/MM/yyyy")}
              readOnly
            />
          </div>

          <div>
            <Label>FECHA DE CESE ACTUAL</Label>
            <Input
              value={
                movimiento.fecha_cese_actual
                  ? format(new Date(movimiento.fecha_cese_actual), "dd/MM/yyyy")
                  : "—"
              }
              readOnly
            />
          </div>

          <div>
            <Label>FECHA DE ACTIVACIÓN ACTUAL</Label>
            <Input
              value={
                movimiento.fecha_activacion_actual
                  ? format(new Date(movimiento.fecha_activacion_actual), "dd/MM/yyyy")
                  : "—"
              }
              readOnly
            />
          </div>

          <div className="md:col-span-2">
            <Label>MOTIVO</Label>
            <textarea
              value={movimiento.motivo}
              readOnly
              className="w-full rounded-md border px-3 py-2 resize-none bg-background text-foreground"
            />
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
