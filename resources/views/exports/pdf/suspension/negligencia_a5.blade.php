<!DOCTYPE html>
<html lang="es">
<!--

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A5 landscape;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Arial", sans-serif;
            margin: 0;
            padding: 8mm;
            box-sizing: border-box;
        }

        .contenedor {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            page-break-inside: avoid;
            max-width: 100%;
        }

        .memorandum {
            width: 50%;
            box-sizing: border-box;
            padding: 8px;
        }

        h2 {
            font-size: 11px;
            text-align: center;
            text-decoration: underline;
            margin: 0 0 5px 0;
        }

        p {
            font-size: 8px;
            text-align: justify;
            margin: 2px 0;
            line-height: 1.2;
        }

        .header-line {
            font-size: 8px;
            text-align: left;
            margin: 1px 0;
            line-height: 1.1;
        }

        hr {
            border: none;
            border-top: 1px solid #000;
            margin: 4px 0;
        }

        ul {
            margin: 3px 0;
            padding-left: 15px;
        }

        li {
            font-size: 8px;
            margin: 1px 0;
            line-height: 1.1;
        }

        strong {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }

        table th,
        table td {
            font-size: 7px;
            border: 1px solid black;
            padding: 2px;
            text-align: center;
        }

        table th {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .firmas {
            margin-top: 8px;
        }

        .firmas img {
            height: 35px;
            max-width: 70px;
        }

        .firmas table {
            width: 100%;
            border: none;
            margin: 0;
        }

        .firmas td {
            text-align: center;
            vertical-align: bottom;
            border: none;
            padding: 0;
        }

        .lineas {
            margin-top: 8px;  /* Lo aumentamos de 2px a 8px para bajar las líneas */
        }

        .lineas td {
            font-size: 7px;
            text-align: center;
            padding-top: 2px;
            border: none;
        }
    </style>
</head>

-->

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Arial", sans-serif;
        }

        body {
            /* **REDUCCIÓN DE MARGEN GENERAL:** De 12mm a 10mm */
            padding: 12mm;
            box-sizing: border-box;
        }

        .contenedor {
            display: flex;
            justify-content: space-between;
            /* **REDUCCIÓN DE ESPACIO:** De 25px a 18px */
            gap: 20px;
            page-break-inside: avoid;
            max-width: 100%;
        }

        .memorandum {
            width: 50%;
            box-sizing: border-box;
            /* **REDUCCIÓN DE RELLENO INTERNO:** De 12px a 10px */
            padding: 12px;
        }

        h2 {
            /* **REDUCCIÓN DE TAMAÑO:** De 15px a 13px */
            font-size: 15px;
            text-align: center;
            text-decoration: underline;
            /* **REDUCCIÓN DE MARGEN:** De 8px a 6px */
            margin: 0 0 6px 0;
        }

        p {
            /* **REDUCCIÓN DE TAMAÑO CLAVE (Motivo):** De 12px a 10px */
            font-size: 12px;
            text-align: justify;
            /* **REDUCCIÓN DE MARGEN:** De 5px a 3px */
            margin: 5px 0;
            /* **LÍNEA MÁS COMPACTA:** De 1.4 a 1.3 */
            line-height: 1.3;
        }

        .header-line {
            /* **REDUCCIÓN DE TAMAÑO:** De 12px a 10px */
            font-size: 12px;
            text-align: left;
            /* **REDUCCIÓN DE MARGEN:** De 4px a 3px */
            margin: 4px 0;
            line-height: 1.2;
        }

        hr {
            border: none;
            border-top: 1px solid #000;
            /* **REDUCCIÓN DE MARGEN:** De 8px a 6px */
            margin: 6px 0;
        }

        ul {
            /* **REDUCCIÓN DE MARGEN:** De 6px a 4px */
            margin: 4px 0;
            /* **REDUCCIÓN DE INDENTACIÓN:** De 25px a 20px */
            padding-left: 20px;
        }

        li {
            /* **REDUCCIÓN DE TAMAÑO:** De 12px a 10px */
            font-size: 10px;
            /* **REDUCCIÓN DE MARGEN:** De 3px a 2px */
            margin: 2px 0;
            line-height: 1.2;
        }

        strong {
            font-weight: bold;
        }

        table {
            /* **REDUCCIÓN DE TAMAÑO:** De 12px a 10px */
            font-size: 10px;
            width: 100%;
            border-collapse: collapse;
            /* **REDUCCIÓN DE MARGEN:** De 8px a 6px */
            margin: 6px 0;
        }

        table th,
        table td {
            /* **REDUCCIÓN DE TAMAÑO:** De 10px a 8px */
            font-size: 8px;
            border: 1px solid black;
            /* **REDUCCIÓN DE RELLENO:** De 4px a 3px */
            padding: 3px;
            text-align: center;
        }

        table th {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .firmas {
            /* **REDUCCIÓN DE MARGEN:** De 20px a 15px */
            margin-top: 15px;
        }

        .firmas img {
            /* **REDUCCIÓN DE TAMAÑO:** De 50px/100px a 40px/80px */
            height: 40px;
            max-width: 80px;
        }

        .firmas table {
            width: 100%;
            border: none;
            margin: 0;
        }

        .firmas td {
            text-align: center;
            vertical-align: bottom;
            border: none;
            padding: 0;
        }

        .lineas {
            /* **REDUCCIÓN DE MARGEN:** De 15px a 10px */
            margin-top: 10px;
        }

        .lineas td {
            /* **REDUCCIÓN DE TAMAÑO:** De 11px a 9px */
            font-size: 9px;
            text-align: center;
            /* **REDUCCIÓN DE RELLENO:** De 4px a 3px */
            padding-top: 3px;
            border: none;
        }
    </style>
</head>


<body>
    @php
        $codigo = [
            'A' => 'AMONESTACION ESCRITA POR NEGLIGENCIA DE FUNCIONES',
            'S' => 'SUSPENSION POR NEGLIGENCIA DE FUNCIONES',
        ];
        $tipo_sancion = $codigo[$suspension->codigo[0]];
    @endphp
    <div class="contenedor">
        @for ($i = 0; $i < 2; $i++)
            <div class="memorandum">
                <h2>MEMORANDUM {{ $fechaMemo }}-RRHH/GVS</h2>

                <div class="header-line"><strong>DE:</strong> {{ $suspension->empleado->empresa->razonsocial }}</div>
                <div class="header-line"><strong>A:</strong>
                    {{ "{$suspension->empleado->apellidos} {$suspension->empleado->nombres}" }}</div>
                <div class="header-line"><strong>ÁREA:</strong> {{ $suspension->empleado->area->nombre }}</div>
                <div class="header-line"><strong>FECHA:</strong> {{ now()->format('d/m/Y') }}</div>
                <div class="header-line"><strong>ASUNTO:</strong> <strong>{{ $tipo_sancion }}</strong></div>

                <hr>

                <p>
                    Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la
                    ley,
                    le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en una
                    <strong>{{ $tipo_sancion }}</strong> en referencia a los hechos
                    que describimos a continuación:
                </p>

                <p>{{ $suspension->motivo }}</p>

                @if ($suspension->codigo[0] == 'S' && isset($amonestaciones) && $amonestaciones->count() > 0)
                    {{-- Estilos inline aplicados a la tabla --}}
                    <table style="width: 100%; border-collapse: collapse; margin: 5px 0;">
                        <thead>
                            <tr>
                                {{-- Estilos inline aplicados a los encabezados (th) --}}
                                <th
                                    style="font-size: 7px; border: 1px solid black; padding: 2px; text-align: center; font-weight: bold; background-color: #f0f0f0;">
                                    MES</th>
                                <th
                                    style="font-size: 7px; border: 1px solid black; padding: 2px; text-align: center; font-weight: bold; background-color: #f0f0f0;">
                                    FECHA</th>
                                <th
                                    style="font-size: 7px; border: 1px solid black; padding: 2px; text-align: center; font-weight: bold; background-color: #f0f0f0;">
                                    DESCRIPCIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($amonestaciones as $item)
                                <tr>
                                    {{-- Estilos inline aplicados a las celdas (td) --}}
                                    <td
                                        style="text-transform: uppercase; font-size: 7px; border: 1px solid black; padding: 2px; text-align: center;">
                                        {{ $item->fecha->isoFormat('MMMM') }}
                                    </td>
                                    <td
                                        style="font-size: 7px; border: 1px solid black; padding: 2px; text-align: center;">
                                        {{ $item->fecha->format('d/m/Y') }}
                                    </td>
                                    <td
                                        style="text-transform: uppercase; font-size: 7px; border: 1px solid black; padding: 2px; text-align: center;">
                                        AMONESTACION ESCRITA POR
                                        {{ strtoupper($suspension->tipo) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <p>
                    Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                    CAPITULO X artículo 42) "Medida disciplinaria o sanción es la acción correctiva tomando a través de
                    los niveles de supervisión que sanciona la falta o faltas cometidas por uno o más colaboradores en
                    contra de la disciplinaria".
                </p>

                <p>
                    Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                    obligación inherente a su contrato de trabajo,
                    constituyendo además su inobservancia una manifiesta contravención del Reglamento Interno de Trabajo
                    de la empresa, cuyas disposiciones
                    son obligatorias y plenamente conocidas por todo el personal que labora para la empresa.
                </p>
                @if ($suspension->codigo[0] == 'S')
                    @if ($diasSuspension == 1)
                        <p>
                            Por esta razón, la empresa ha decidido proceder a sancionarlo con
                            ({{ $diasSuspension }}) día de <strong>Suspensión sin goce de Haber</strong>, fecha
                            que será efectiva el día <strong>{{ $fecha }}</strong>.
                            Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se
                            tomaran medidas pertinentes al respecto, siendo de su entera responsabilidad.
                        </p>
                    @else
                        <p>
                            Por esta razón, la empresa ha decidido proceder a sancionarlo con
                            ({{ $diasSuspension }}) días de <strong>Suspensión sin goce de Haber</strong>, que será
                            efectiva desde el
                            <strong>{{ $fecha }}</strong> hasta el <strong>{{ $fechaFin }}</strong>.
                            Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se
                            tomaran medidas pertinentes al respecto, siendo de su entera responsabilidad.
                        </p>
                    @endif
                @endif

                <p>Atentamente,</p>

                <div class="firmas">
                    <table>
                        <tr>
                            <td><img src="{{ asset($suspension->empleado->empresa->firma) }}" alt="Firma Empleador">
                            </td>
                            <td><img src="{{ asset('storage/firmas/transparente.png') }}" alt="Firma Trabajador"></td>
                        </tr>
                    </table>

                    <table class="lineas">
                        <tr>
                            <td class="firma-line">_______________________________</td>
                            <td class="firma-line">_______________________________</td>
                        </tr>
                        <tr>
                            <td>EL EMPLEADOR</td>
                            <td>EL TRABAJADOR</td>
                        </tr>
                    </table>
                </div>
            </div>
        @endfor
    </div>
</body>

</html>



<!--
    <!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A5 landscape;
            margin: 0;
        }

        body {
            font-family: "Arial", sans-serif;
            margin: 0;
            padding: 8mm;
            box-sizing: border-box;
        }

        .contenedor {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            page-break-inside: avoid;
            max-width: 100%;
        }

        .memorandum {
            width: 50%;
            box-sizing: border-box;
            padding: 8px;
        }

        h2 {
            font-size: 12px;
            text-align: center;
            text-decoration: underline;
            margin: 0 0 6px 0;
        }

        p {
            font-size: 9px;
            text-align: justify;
            margin: 3px 0;
            line-height: 1.3;
        }

        .header-line {
            font-size: 9px;
            text-align: left;
            margin: 2px 0;
            line-height: 1.2;
        }

        hr {
            border: none;
            border-top: 1px solid #000;
            margin: 5px 0;
        }

        strong {
            font-weight: bold;
        }

        .firmas {
            margin-top: 10px;
        }

        .firmas img {
            height: 40px;
            max-width: 80px;
        }

        .firmas table {
            width: 100%;
        }

        .firmas td {
            text-align: center;
            vertical-align: bottom;
        }

        .lineas {
            margin-top: 3px;
        }

        .lineas td {
            font-size: 8px;
            text-align: center;
            padding-top: 2px;
        }


    </style>
</head>

<body>
    <div class="contenedor">
        @for ($i = 0; $i < 2; $i++)
<div class="memorandum">
            <h2>MEMORANDUM {{ $fechaMemo }}-RRHH/GVS</h2>

            <div class="header-line"><strong>DE:</strong> {{ $suspension->empleado->empresa->razonsocial }}</div>
            <div class="header-line"><strong>A:</strong> {{ "{$suspension->empleado->apellidos} {$suspension->empleado->nombres}" }}</div>
            <div class="header-line"><strong>ÁREA:</strong> {{ $suspension->empleado->area->nombre }}</div>
            <div class="header-line"><strong>FECHA:</strong> {{ now()->format('d/m/Y') }}</div>
            <div class="header-line"><strong>ASUNTO:</strong> <strong>AMONESTACION ESCRITA POR INCUMPLIR LAS NORMAS DE TRABAJO</strong></div>

            <hr>

            <p>
                Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la ley,
                le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en una
                <strong>AMONESTACION ESCRITA POR INCUMPLIR LAS NORMAS DE TRABAJO</strong> en referencia a los hechos
                que describimos a continuación:
            </p>

            <p>{{ $suspension->motivo }}</p>

            <p>
                Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                artículo 38 inciso {{ $articulo }}.
            </p>

            <p>
                Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                obligación inherente a su contrato de trabajo,
                constituyendo además su inobservancia una manifiesta contravención del Reglamento Interno de Trabajo
                de la empresa, cuyas disposiciones
                son obligatorias y plenamente conocidas por todo el personal que labora para la empresa.
            </p>

            <p>
                Finalmente, se le exhorta a que hechos como este no vuelvan a suceder, caso contrario,
                se tomarán medidas pertinentes al respecto, siendo de su entera responsabilidad.
            </p>

            <p>Atentamente,</p>

            <div class="firmas">
                <table>
                    <tr>
                        <td><img src="{{ asset($suspension->empleado->empresa->firma) }}" alt="Firma Empleador"></td>
                        <td><img src="{{ asset('storage/firmas/transparente.png') }}" alt="Firma Trabajador"></td>
                    </tr>
                </table>

                <table class="lineas">
                    <tr>
                        <td class="firma-line">_______________________________</td>
                        <td class="firma-line">_______________________________</td>
                    </tr>
                    <tr>
                        <td>EL EMPLEADOR</td>
                        <td>EL TRABAJADOR</td>
                    </tr>
                </table>
            </div>
        </div>
@endfor
    </div>
</body>

</html>

-->
