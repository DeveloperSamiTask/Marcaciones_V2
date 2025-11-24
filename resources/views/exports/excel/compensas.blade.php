@php
    $estado = [
        'C' => ['label' => 'COMPENSACION'],
        'CA' => ['label' => 'COMP. ADELANTADA'],
        '' => ['label' => 'NO REGISTRADO'],
        0 => ['label' => 'PENDIENTE'],
        1 => ['label' => 'AUTORIZADO'],
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
            @if ($encargado)
                <th style="text-align: center;">Encargado: {{ $encargado }}
                <th>
            @endif
        </tr>
        @if ($tipo == 'pendientes')
            <tr>
                <th>CODIGO</th>
                <th>DNI</th>
                <th>INGRESO</th>
                <th>EMPLEADO</th>
                <th>AREA</th>
                <th>JORNADA</th>
                <th>TOTAL</th>
                <th>FERIADOS</th>
                <th>TDS</th>
            </tr>
        @else
            <tr>
                <th>CODIGO</th>
                <th>DNI</th>
                <th>EMPLEADO</th>
                <th>AREA</th>
                <th>JORNADA</th>
                <th>TIPO</th>
                <th>FECHA</th>
                <th>MOTIVO</th>
                <th>MOTIVO RECHAZO</th>
                <th>ESTADO</th>
            </tr>
        @endif
    </thead>
    <tbody>
        @if ($tipo === 'pendientes')
            @foreach ($items as $item)
                <tr>
                    <td> {{ $item['id'] }} </td>
                    <td> {{ $item['dni'] }} </td>
                    <td> {{ $item['fecha_ingreso'] }} </td>
                    <td> {{ $item['empleado'] }} </td>
                    <td> {{ $item['area'] }} </td>
                    <td> {{ $item['jornada'] }} </td>
                    <td> {{ count($item['feriados']) }} </td>
                    <td>
                        @if (!empty($item['feriados']))
                            @foreach ($item['feriados'] as $feriado)
                                {{ $feriado['nombre'] }}
                                ({{ \Carbon\Carbon::parse($feriado['fecha'])->format('d/m/Y') }})
                                <br>
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                    <!-- change background color td -->
                    <td style="{{ !empty($item['permisos_td']) ? 'background:#91FF80;' : '' }}">
                        @if (!empty($item['permisos_td']))
                            @foreach ($item['permisos_td'] as $td)
                                ({{ \Carbon\Carbon::parse($td['fecha'])->format('d/m/Y') }})
                                <br>
                            @endforeach
                        @endif
                    </td>



                </tr>
            @endforeach
        @else
            @foreach ($items as $item)
                <tr>
                    <td> {{ $item['empleado']['id'] }} </td>
                    <td> {{ $item['empleado']['dni'] }} </td>
                    <td> {{ $item['empleado']['apellidos'] }} {{ $item['empleado']['nombres'] }} </td>
                    <td> {{ $item['empleado']['area']['nombre'] }} </td>
                    <td> {{ $item['empleado']['jornada']['nombre'] }} </td>
                    <td> {{ $estado[$item['tipo']['codigo']]['label'] }} </td>
                    <td> {{ \Carbon\Carbon::parse($item['fecha'])->format('d/m/Y') }} </td>
                    <td> {{ $item['motivo'] }} </td>
                    <td> {{ $item['motivo_rechazo'] }} </td>
                    <td> {{ $estado[$item['estado']]['label'] }} </td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
