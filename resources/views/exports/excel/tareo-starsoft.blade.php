<table>
    <thead>
        <tr>
            <th>CODTRAB</th>
            <th>NOMBRES</th>
            <th>CCOSTO</th>
            @if ($jornada == '1')
                <th>COMPENS</th>
            @endif
            <th>DDESCMED</th>
            <th>DFALTAS</th>
            <th>DIASTRAB</th>
            <th>DLICCGO</th>
            <th>DLICSGO</th>
            <th>DVAC</th>
            <th>HE25</th>
            <th>HE35</th>
            @if ($jornada == '1')
                <th>NOCTURNOHE25</th>
                <th>NOCTURNOHE35</th>
            @endif
            @if ($jornada == '2')
                <th>HORPART</th>
            @endif
            <th>MTAR</th>
            <th>SUSP</th>
            <th>ZANT</th>
            <th></th>
            @if ($jornada == '2')
                <th>EXTRA TRABAJADO</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td> {{ $item->empleado->dni }} </td>
                <td> {{ $item->empleado->apellidos }} {{ $item->empleado->nombres }} </td>
                <td> </td>
                @if ($jornada == '1')
                    <td> {{ $item->compensa > 0 ? $item->compensa : '' }} </td>
                @endif
                <td> {{ $item->descanso_medico > 0 ? $item->descanso_medico : '' }} </td>
                <td> {{ ($item->falta_injustificada + $item->falta_justificada) > 0 ? ($item->falta_injustificada + $item->falta_justificada) : '' }} </td>
                <td> {{ $item->total_dias_trabajados }} </td>
                <td> {{ $item->licencia_con_goce > 0 ? $item->licencia_con_goce : '' }} </td>
                <td> {{ $item->licencia_sin_goce > 0 ? $item->licencia_sin_goce : '' }} </td>
                <td> {{ $item->vacaciones > 0 ? $item->vacaciones : '' }} </td>
                <td> {{ $item->extra_25 > 0 ? sprintf('%02d.%02d', floor($item->extra_25 / 60), $item->extra_25 % 60 ) : '' }} </td>
                <td> {{ $item->extra_35 > 0 ? sprintf('%02d.%02d', floor($item->extra_35 / 60), $item->extra_35 % 60 ) : '' }} </td>
                @if ($jornada == '1')
                    <td> {{ isset($item->nocturno_25) && $item->nocturno_25 > 0 ? sprintf('%02d.%02d', floor($item->nocturno_25 / 60), $item->nocturno_25 % 60 ) : '' }} </td>
                    <td> {{ isset($item->nocturno_35) && $item->nocturno_35 > 0 ? sprintf('%02d.%02d', floor($item->nocturno_35 / 60), $item->nocturno_35 % 60 ) : '' }} </td>
                @endif
                @if ($jornada == '2')
                    <td> {{ sprintf('%02d.%02d', floor($item->horas / 60), $item->horas % 60 ) }} </td>
                @endif
                <td> {{ $item->tardanza > 0 ? $item->tardanza : '' }} </td>
                <td> {{ $item->suspension > 0 ? $item->suspension : '' }} </td>
                <td> {{ $item->anticipado > 0 ? $item->anticipado : '' }} </td>
                <td> </td>
                @if ($jornada == '2')
                    <td> {{ isset($item->horasExtraPart) && $item->horasExtraPart > 0 ? sprintf('%02d.%02d', floor($item->horasExtraPart / 60), $item->horasExtraPart % 60 ) : '' }} </td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
