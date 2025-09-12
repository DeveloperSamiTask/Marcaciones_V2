@php
$estado = [
    'L' => ['label' => 'LABORAL'],
    'D' => ['label' => 'DESCANSO'],
    'C' => ['label' => 'COMPENSACION'],
    'CHE' => ['label' => 'COMPENSA HE'],
    'CA' => ['label' => 'COMP. ADELANTADA'],
    'F' => ['label' => 'FERIADO'],
	'FL' => ['label' => 'FERIADO LABORAL'],
	'SP' => ['label' => 'SIN PROGRAMACION'],
    'V' => ['label' => 'VACACIONES'],
    'M' => ['label' => 'D. MEDICO'],
    'S' => ['label' => 'SUSPENCION'],
    'FI' => ['label' => 'F. INJUSTIFICADA'],
    'FJ' => ['label' => 'F. JUSTIFICADA'],
    'LCG' => ['label' => 'L. CON GOCE'],
    'LSG' => ['label' => 'L. SIN GOCE'],
    'LP' => ['label' => 'L. PATERNIDAD'],
    'LM' => ['label' => 'L. MATERNIDAD'],
    'PE' => ['label' => 'PENDIENTE'],
    'HENA' => ['label' => 'H. EXTRA NO AUTORIZADO'],
    'ANTICIPADO' => ['label' => 'PENDIENTE'],
    '' => ['label' => 'NO REGISTRADO'],
];
@endphp

<table>
    <thead>
        <tr>
            <th></th>
        </tr>
        <tr>
            <th style="text-align: center;">{{ $empresa }}</th>
            <th style="text-align: center;"></th>
            <th></th>
            <th style="text-align: center;">Fecha de {{ $fechaInicio }} hasta {{ $fechaFin }}</th>
        </tr>
        <tr>
            <th>EMPLEADO</th>
            <th>DNI</th>
            <th>AREA</th>
            <th>JORNADA</th>
            <th>FECHA</th>
            <th>HORARIO</th>
            <th>HI</th>
            <th>HIP</th>
            <th>HS</th>
            <th>HSP</th>
            <th>HIREF</th>
            <th>HTREF</th>
            <th>TOTAL</th>
            <th>TARDANZA</th>
            <th>EXTRA</th>
			<th>ANTICIPADO</th>
            <th>NOCTURNO</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td> {{ $item->empleado->apellidos }} {{ $item->empleado->nombres }} </td>
                <td> {{ $item->empleado->dni }} </td>
                <td> {{ $item->empleado->area->nombre }} </td>
                <td> {{ $item->empleado->jornada->nombre }} </td>
                <td> {{ \Carbon\Carbon::parse($item->fecha)->format('d/m/Y') }} </td>
                <td> {{ $estado[$item->horario ? $item->horario->estado : '']['label'] }} </td>
                <td> {{ $item->marcacion && $item->marcacion->ingreso ? $item->marcacion->ingreso : '00:00' }} </td>
                <td> {{ $item->horario ? $item->horario->ingreso : '00:00' }} </td>
                <td> {{ $item->marcacion && $item->marcacion->salida ? $item->marcacion->salida : '00:00' }} </td>
                <td> {{ $item->horario ? $item->horario->salida : '00:00' }} </td>
                <td> {{ $item->marcacion && $item->marcacion->ingreso_refri ? $item->marcacion->ingreso_refri : '00:00' }} </td>
                <td> {{ $item->marcacion && $item->marcacion->salida_refri ? $item->marcacion->salida_refri : '00:00' }} </td>
                <td> {{ sprintf('%02d:%02d', floor($item->horas / 60), $item->horas % 60 ) }} </td>
                <td> {{ sprintf('%02d:%02d', floor($item->tardanza / 60), $item->tardanza % 60 ) }} </td>
                <td> {{ sprintf('%02d:%02d', floor($item->extra / 60), $item->extra % 60 ) }} </td>
                <td> {{ sprintf('%02d:%02d', floor($item->anticipado / 60), $item->anticipado % 60 ) }} </td>
                <td> {{ sprintf('%02d:%02d', floor($item->nocturno / 60), $item->nocturno % 60 ) }} </td>
            </tr>
        @endforeach
    </tbody>
</table>
