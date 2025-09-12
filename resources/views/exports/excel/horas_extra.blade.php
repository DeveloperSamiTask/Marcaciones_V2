@php
    $estado = [
        'pendientes' => ['label' => 'PENDIENTE'],
        'aprobados' => ['label' => 'APROBADOS'],
        'revision' => ['label' => 'REVISION'],
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
            @if ($encargado)
                <th style="text-align: center;">Encargado: {{ $encargado }}
                <th>
            @endif
        </tr>
        <tr>
            <th>CODIGO</th>
            <th>DNI</th>
            <th>EMPLEADO</th>
            <th>AREA</th>
            <th>JORNADA</th>
            <th>ESTADO</th>
            <th>EXTRA</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td> {{ $item['empleado']['id'] }} </td>
                <td> {{ $item['empleado']['dni'] }} </td>
                <td> {{ $item['empleado']['apellidos'] }} {{ $item['empleado']['nombres'] }} </td>
                <td> {{ $item['empleado']['area']['nombre'] }} </td>
                <td> {{ $item['empleado']['jornada']['nombre'] }} </td>
                <td> {{ $estado[$item['estado']]['label'] }} </td>
                <td> {{ $item['extra'] }} </td>
            </tr>
        @endforeach
    </tbody>
</table>
