<table>
    <thead>
        <tr>
            <th></th>
        </tr>
        <tr>
            <th>CODIGO</th>
            <th>DNI</th>
            <th>EMPLEADO</th>
            <th>EMPRESA</th>
            <th>AREA</th>
            <th>JORNADA</th>
            <th>JEFE</th>
            <th>CARGO</th>
            <th>EMAIL</th>
            <th>SEXO</th>
            <th>DOMICILIO</th>
            <th>PESO</th>
            <th>TALLA</th>
            <th>HORAS</th>
            <th>INGRESO</th>
            <th>CESE</th>
            <th>ESTADO</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td> {{ $item->id }} </td>
                <td> {{ $item->dni }} </td>
                <td> {{ $item->apellidos }} {{ $item->nombres }} </td>
                <td> {{ $item->empresa->razonsocial }} </td>
                <td> {{ $item->area->nombre }} </td>
                <td> {{ $item->jornada->nombre }} </td>
                <td> {{ $item->jefe_id ? $item->jefe->apellidos : '' }} {{ $item->jefe_id ? $item->jefe->nombres : '' }} </td>
                <td> {{ $item->cargo }} </td>
                <td> {{ $item->email }} </td>
                <td> {{ $item->sexo }} </td>
                <td> {{ $item->domicilio }} </td>
                <td> {{ $item->peso }} </td>
                <td> {{ $item->talla }} </td>
                <td> {{ $item->horas }} </td>
                <td> {{ \Carbon\Carbon::parse($item->fecha_ingreso)->format('d/m/Y') }} </td>
                <td> {{ \Carbon\Carbon::parse($item->fecha_cese)->format('d/m/Y') }} </td>
                <td> {{ !$item->fecha_cese ? 'ACTIVO' : 'INACTIVO' }} </td>
            </tr>
        @endforeach
    </tbody>
</table>
