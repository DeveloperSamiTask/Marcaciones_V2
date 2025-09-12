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
            <th>HORAS TRABAJADAS</th>
            <th>EXCEDENTE</th>
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
            <th>DESCUENTO</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td> {{ $item->empleado->dni }} </td>
                <td> {{ \Carbon\Carbon::parse($item->empleado->fecha_ingreso)->format('d/m/Y') }} </td>
                <td> {{ $item->empleado->area->nombre }} </td>
                <td> {{ $item->empleado->apellidos }} {{ $item->empleado->nombres }} </td>
                <td> {{ sprintf('%02d:%02d', floor($item->horas / 60), $item->horas % 60 ) }} </td>
                <td> {{ $item->horasExcedente > 0 ? sprintf('%02d:%02d', floor($item->horasExcedente / 60), $item->horasExcedente % 60 ) : '00:00' }} </td>
                <td> {{ sprintf('%02d:%02d', floor($item->tardanza / 60), $item->tardanza % 60 ) }} </td>
                <td> {{ sprintf('%02d:%02d', floor($item->anticipado / 60), $item->anticipado % 60 ) }} </td>
                <td> {{ sprintf('%02d:%02d', floor($item->nocturno / 60), $item->nocturno % 60 ) }} </td>
                <td> {{ sprintf('%02d:%02d', floor($item->extra_25 / 60), $item->extra_25 % 60 ) }} </td>
                <td> {{ sprintf('%02d:%02d', floor($item->extra_35 / 60), $item->extra_35 % 60 ) }} </td>
                <td> {{ $item->compensa_pendiente }} </td>
                <td> {{ $item->falta_injustificada }} </td>
                <td> {{ $item->falta_justificada }} </td>
                <td> {{ $item->feriado }} </td>
                <td> {{ $item->feriado_laboral }} </td>
                <td> {{ $item->descanso_medico }} </td>
                <td> {{ $item->vacaciones }} </td>
                <td> {{ $item->compensa }} </td>
                <td> {{ $item->licencia_con_goce }} </td>
                <td> {{ $item->licencia_sin_goce }} </td>
                <td> {{ $item->licencia_paternidad }} </td>
                <td> {{ $item->licencia_maternidad }} </td>
                <td> {{ $item->licencia_fallecimiento }} </td>
                <td> {{ $item->sin_programacion }} </td>
                <td> {{ $item->suspension }} </td>
                <td> {{ $item->descanso }} </td>
                <td> {{ $item->asistencia }} </td>
                <td> {{ $item->total_pago }} </td>
                <td> {{ $item->total_100 }} </td>
                <td> {{ $item->descuento }} </td>
            </tr>
        @endforeach

        <tr><td></td></tr>
        <tr><td></td></tr>
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
