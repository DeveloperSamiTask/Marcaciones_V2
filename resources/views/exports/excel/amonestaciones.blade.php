@php
$estado = [
    'FALTA INJUSTIFICADA' => ['label' => 'FALTA INJUSTIFICADA'],
    'TARDANZA' => ['label' => 'TARDANZA'],
    'INCOMPLETO' => ['label' => 'MARCACION INCOMPLETA'],
    'REFRIGERIO' => ['label' => 'TARDANZA REFRIGERIO'],
    'NEGLIGENCIA' => ['label' => 'NEGLIGENCIA'],
    '' => ['label' => 'NO REGISTRADO'],
    0 => ['label' => 'PENDIENTE'],
    1 => ['label' => 'APLICADO'],
    2 => ['label' => 'RECHAZADO'],
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
            @if ($area)
                <th style="text-align: center;">Area: {{ $area }}<th>
            @endif
        </tr>
        <tr>
            <th>CODIGO</th>
            <th>NOMBRE</th>ss
            <th>EMPLEADO</th>
            <th>AREA</th>
            <th>FECHA</th>
            <th>TIPO</th>
            <th>ESTADO</th>
            <th>HORA</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td> {{ $item['codigo'] }} </td>
                <td> {{ $item['codigo'][0] == 'S' ? 'SUSPENSION' : 'AMONESTACION' }} </td>
                <td> {{ $item['empleado']['apellidos'] }} {{ $item['empleado']['nombres'] }} </td>
                <td> {{ $item['empleado']['area']['nombre'] }} </td>
                <td> {{ \Carbon\Carbon::parse($item['fecha'])->format('d/m/Y') }} </td>
                <td> {{ $estado[strtoupper($item['tipo'])]['label'] }} </td>
                <td> {{ $estado[$item['estado']]['label'] }} </td>
                <td> {{ $item['hora'] }} </td>
            </tr>
        @endforeach
    </tbody>
</table>
