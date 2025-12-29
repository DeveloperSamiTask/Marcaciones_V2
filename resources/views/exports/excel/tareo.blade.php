<table>
    <thead>
        <tr>
            <th></th>
        </tr>
        <tr>
            <th style="text-align: center;">{{ $empresa }}</th>
            <th style="text-align: center;">{{ $jornada }}</th>
            <th></th>
            <th style="text-align: center;">Fecha de {{ $fechaInicio }} hasta {{ $fechaFin }}</th>
        </tr>
        <tr>
            <th>DNI</th>
            <th>FECHA DE INGRESO</th>
            <th>AREA</th>
            <th>EMPLEADO</th>
            <th>HORAS LABORADAS</th>
            <th>HORAS TRABAJADAS</th>
            <th>EXCEDENTE</th>  <!-- ESTA COLUMNA -->
            <th>TARDANZA</th>
            <th>ANTICIPADO</th>
            <th>NOCTURNO</th>
            <th>25%</th>
            <th>35%</th>
            <th>CP</th>
            <th>FI</th>
            <th>FJ</th>
            <th>F</th>
            <th>FL</th>
            <th>DM</th>
            <th>VAC</th>
            <th>C</th>
            <th>C/HR</th>
            <th>LCG</th>
            <th>LSG</th>
            <th>LP</th>
            <th>LM</th>
            <th>LF</th>
            <th>SP</th>
            <th>S</th>
            <th>D</th>
            <th>A</th>
            <th>TOTAL PAGO</th>
            <th>TOTAL 100%</th>
            <th>HE/PT</th>
            <th>DESCUENTO</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td>{{ $item->empleado->dni }}</td>
                <td>{{ \Carbon\Carbon::parse($item->empleado->fecha_ingreso)->format('d/m/Y') }}</td>
                <td>{{ $item->empleado->area->nombre }}</td>
                <td>{{ $item->empleado->apellidos }} {{ $item->empleado->nombres }}</td>

                @php
                    $empleado = $item->empleado;
                    $marcaciones = $empleado->marcaciones ?? collect();

                    // 1. ELIMINAR DUPLICADOS
                    $marcacionesUnicas = [];
                    foreach ($marcaciones as $m) {
                        $fecha = \Carbon\Carbon::parse($m->fecha)->format('Y-m-d');
                        $marcacionesUnicas[$fecha] = $m;
                    }

                    $totalTrabajadoMinutos = 0;

                    // 2. CALCULAR HORAS TRABAJADAS REALES
                    foreach ($marcacionesUnicas as $m) {
                        $ingreso = trim($m->ingreso);
                        $salida = trim($m->salida);

                        if (
                            $ingreso &&
                            $salida &&
                            !in_array($ingreso, ['00:00', '00:00:00']) &&
                            !in_array($salida, ['00:00', '00:00:00'])
                        ) {
                            $start = \Carbon\Carbon::parse($ingreso);
                            $end = \Carbon\Carbon::parse($salida);
                            $dur = $start->diffInMinutes($end, false);

                            if ($dur < 0) {
                                $dur += 1440;
                            }

                            if ($dur > 0 && $dur < 1440) {
                                // 3. REGLA DE REFRIGERIO: 60 min si trabajó 6h o más
                                if ($dur >= 360) {
                                    $dur -= 60;
                                }
                                $totalTrabajadoMinutos += $dur;
                            }
                        }
                    }

                    // 4. CALCULO DE COMPENSAS (Solo para Laboradas)
                    $minutosCompensa = 0;
                    $esPartTime = $empleado->jornada_id == 2;
                    $horarios = $empleado->horarios ?? [];

                    if ($esPartTime) {
                        foreach ($horarios as $horario) {
                            if (($horario->estado ?? '') === 'C') {
                                $ingStr = $horario->ingreso ?? $horario->entryTime;
                                $salStr = $horario->salida ?? $horario->exitTime;

                                if ($ingStr && $salStr) {
                                    $cStart = \Carbon\Carbon::parse($ingStr);
                                    $cEnd = \Carbon\Carbon::parse($salStr);
                                    $cDur = $cStart->diffInMinutes($cEnd, false);
                                    if ($cDur < 0) {
                                        $cDur += 1440;
                                    }
                                    if ($cDur >= 360) {
                                        $cDur -= 60;
                                    }
                                    $minutosCompensa += $cDur;
                                }
                            }
                        }
                    }

                    // Totales finales
                    $totalLaboradas = $totalTrabajadoMinutos + $minutosCompensa;
                    $totalTrabajadas = $totalTrabajadoMinutos;
                @endphp

                <td>{{ sprintf('%02d:%02d', floor($totalLaboradas / 60), $totalLaboradas % 60) }}</td>
                <td>{{ sprintf('%02d:%02d', floor($totalTrabajadas / 60), $totalTrabajadas % 60) }}</td>

                <!-- AQUÍ FALTA LA COLUMNA EXCEDENTE -->
                <td>
                    @if(isset($item->excedente))
                        {{ sprintf('%02d:%02d', floor($item->excedente / 60), $item->excedente % 60) }}
                    @else
                        00:00
                    @endif
                </td>
                <!-- FIN DE COLUMNA FALTANTE -->

                <td>{{ sprintf('%02d:%02d', floor($item->tardanza / 60), $item->tardanza % 60) }}</td>
                <td>{{ sprintf('%02d:%02d', floor($item->anticipado / 60), $item->anticipado % 60) }}</td>
                <td>{{ sprintf('%02d:%02d', floor($item->nocturno / 60), $item->nocturno % 60) }}</td>
                <td>{{ sprintf('%02d:%02d', floor($item->extra_25 / 60), $item->extra_25 % 60) }}</td>
                <td>{{ sprintf('%02d:%02d', floor($item->extra_35 / 60), $item->extra_35 % 60) }}</td>
                <td>{{ $item->compensa_pendiente }}</td>
                <td>{{ $item->falta_injustificada }}</td>
                <td>{{ $item->falta_justificada }}</td>
                <td>{{ $item->feriado }}</td>
                <td>{{ $item->feriado_laboral }}</td>
                <td>{{ $item->descanso_medico }}</td>
                <td>{{ $item->vacaciones }}</td>
                <td>{{ $item->compensa }}</td>
                <td>
                    @php
                        $totalMinutos = 0;
                        $empleado = $item->empleado;
                        $esPartTime = $empleado->jornada_id == 2;
                        $diasCompensa = $item->compensa_dias_total ?? 0;
                        $horarios = $empleado->horarios ?? [];

                        if (!$esPartTime || $diasCompensa == 0) {
                            echo '<span class="text-gray-400">—</span>';
                        } else {
                            $parseTimeToMinutes = function ($time) {
                                if (!$time) return null;
                                $parts = explode(':', $time);
                                if (count($parts) < 2) return null;
                                return intval($parts[0]) * 60 + intval($parts[1]);
                            };

                            $diffMinutes = function ($startStr, $endStr) use ($parseTimeToMinutes) {
                                $start = $parseTimeToMinutes($startStr);
                                $end = $parseTimeToMinutes($endStr);
                                if ($start === null || $end === null) return null;
                                return $end < $start ? $end + 1440 - $start : $end - $start;
                            };

                            $horariosCompensa = collect($horarios)->filter(fn($h) => $h->estado === 'C');

                            foreach ($horariosCompensa as $horario) {
                                $ingreso = $horario->ingreso ?? ($horario->entryTime ?? null);
                                $salida = $horario->salida ?? ($horario->exitTime ?? null);

                                if ($ingreso && $salida) {
                                    $duracion = $diffMinutes($ingreso, $salida);
                                    if ($duracion !== null) {
                                        $minutosDia = $duracion;
                                        if ($empleado->jornada_id == 2 && $minutosDia >= 360) {
                                            $minutosDia -= 60;
                                        }
                                        $totalMinutos += $minutosDia;
                                    }
                                }
                            }
                            $horas = floor($totalMinutos / 60);
                            $min = $totalMinutos % 60;
                            echo sprintf('%02d:%02d', $horas, $min);
                        }
                    @endphp
                </td>
                <td>{{ $item->licencia_con_goce }}</td>
                <td>{{ $item->licencia_sin_goce }}</td>
                <td>{{ $item->licencia_paternidad }}</td>
                <td>{{ $item->licencia_maternidad }}</td>
                <td>{{ $item->licencia_fallecimiento }}</td>
                <td>{{ $item->sin_programacion }}</td>
                <td>{{ $item->suspension }}</td>
                <td>{{ $item->descanso }}</td>
                <td>{{ $item->asistencia }}</td>
                <td>{{ $item->total_pago }}</td>
                <td>{{ $item->total_100 }}</td>
                <td>
                    @if (isset($item->hept_horas) && $item->hept_horas > 0)
                        {{ sprintf('%02d:%02d', floor($item->hept_horas / 60), $item->hept_horas % 60) }}
                    @else
                        00:00
                    @endif
                </td>
                <td>{{ $item->descuento }}</td>
            </tr>
        @endforeach

        <tr>
            <td></td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">LYENDA</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">FI</td>
            <td style="text-align: center; text-lg">FALTA INJUSTIFICADA</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">FJ</td>
            <td style="text-align: center; text-lg">FALTA JUSTIFICADA</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">D</td>
            <td style="text-align: center; text-lg">DESCANSO</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">F</td>
            <td style="text-align: center; text-lg">FERIADO</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">FL</td>
            <td style="text-align: center; text-lg">FERIADO LABORAL</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">DM</td>
            <td style="text-align: center; text-lg">DESCANSO MEDICO</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">VAC</td>
            <td style="text-align: center; text-lg">VACACIONES</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">C</td>
            <td style="text-align: center; text-lg">COMPENSACION</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">CP</td>
            <td style="text-align: center; text-lg">COMPENSA PENDIENTE</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">LCG</td>
            <td style="text-align: center; text-lg">LICENCIA CON GOCE DE HABER</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">LSG</td>
            <td style="text-align: center; text-lg">LICENCIA SIN GOCE DE HABER</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">LP</td>
            <td style="text-align: center; text-lg">LICENCIA PATERNIDAD</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">LM</td>
            <td style="text-align: center; text-lg">LICENCIA MATERNIDAD</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">LF</td>
            <td style="text-align: center; text-lg">LICENCIA FALLECIMIENTO</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">SP</td>
            <td style="text-align: center; text-lg">SIN PROGRAMACION</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">S</td>
            <td style="text-align: center; text-lg">SUSPENSION</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">A</td>
            <td style="text-align: center; text-lg">ASISTENCIA</td>
        </tr>
        <tr>
            <td style="text-align: center; text-lg">TD</td>
            <td style="text-align: center; text-lg">TRABAJO DIA DE DESCANSO</td>
        </tr>
    </tbody>
</table>
