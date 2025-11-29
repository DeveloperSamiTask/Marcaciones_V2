<!DOCTYPE html>
<html lang="es">

<!--  respaldo
 <head>
    <meta charset="UTF-8">
    <style>
        @page {

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

        ul {
            margin: 4px 0;
            padding-left: 20px;
        }

        li {
            font-size: 9px;
            margin: 2px 0;
            line-height: 1.2;
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
-->

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: "Arial", sans-serif;
            margin: 0;
            /* Aumentar el padding del cuerpo para más espacio general */
            padding: 12mm;
            box-sizing: border-box;
        }

        .contenedor {
            display: flex;
            justify-content: space-between;
            /* Aumentar el espacio entre los dos memorándums */
            gap: 25px;
            page-break-inside: avoid;
            max-width: 100%;
        }

        .memorandum {
            width: 50%;
            box-sizing: border-box;
            /* Aumentar el padding interno del memorándum */
            padding: 12px;
        }

        h2 {
            /* Aumento de tamaño */
            font-size: 15px;
            text-align: center;
            text-decoration: underline;
            /* Aumento de margen */
            margin: 0 0 8px 0;
        }

        p {
            /* Aumento de tamaño (Base) */
            font-size: 12px;
            text-align: justify;
            /* Aumento de margen */
            margin: 5px 0;
            line-height: 1.4;
            /* Ligeramente más espaciado */
        }

        .header-line {
            /* Aumento de tamaño */
            font-size: 12px;
            text-align: left;
            /* Aumento de margen */
            margin: 4px 0;
            line-height: 1.3;
        }

        hr {
            border: none;
            border-top: 1px solid #000;
            /* Aumento de margen */
            margin: 8px 0;
        }

        ul {
            /* Aumento de margen */
            margin: 6px 0;
            /* Aumento de padding para indentación */
            padding-left: 25px;
        }

        li {
            /* Aumento de tamaño */
            font-size: 12px;
            /* Aumento de margen */
            margin: 3px 0;
            line-height: 1.3;
        }

        strong {
            font-weight: bold;
        }

        /* Estilos para Tablas */
        table {
            /* Tomado de tu referencia: 14px */
            font-size: 14px;
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }

        /* Estilos para Firmas */
        .firmas {
            /* Aumento de margen para separar del contenido */
            margin-top: 20px;
        }

        .firmas img {
            height: 50px;
            /* Tamaño de imagen aumentado */
            max-width: 100px;
        }

        .firmas table {
            width: 100%;
        }

        .firmas td {
            text-align: center;
            vertical-align: bottom;
        }

        .lineas {
            /* Aumento de margen */
            margin-top: 6px;
        }

        .lineas td {
            /* Aumento de tamaño */
            font-size: 11px;
            text-align: center;
            /* Aumento de padding */
            padding-top: 4px;
        }
    </style>
</head>

<body>
    <div class="contenedor">
        <!-- COLUMNA 1: CON AMONESTACIONES PREVIAS -->
        <div class="memorandum">
            <h2>MEMORANDUM {{ $fechaMemo }}-RRHH/GVS</h2>

            <div class="header-line"><strong>DE:</strong> {{ $suspension->empleado->empresa->razonsocial }}</div>
            <div class="header-line"><strong>A:</strong>
                {{ "{$suspension->empleado->apellidos} {$suspension->empleado->nombres}" }}</div>
            <div class="header-line"><strong>ÁREA:</strong> {{ $suspension->empleado->area->nombre }}</div>
            <div class="header-line"><strong>FECHA:</strong> {{ now()->format('d/m/Y') }}</div>
            <div class="header-line"><strong>ASUNTO:</strong> <strong>SUSPENSION POR FALTA INJUSTIFICADA</strong></div>

            <hr>

            <p>
                Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la ley,
                le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en
                <strong>SUSPENSION POR FALTA INJUSTIFICADA</strong> en referencia a los hechos que describimos a
                continuación:
            </p>

            @if (count($amonestaciones) > 0)

                {{-- CASO 1: Es una Suspensión por Acumulación (Muestra la tabla de amonestaciones previas) --}}

                <div style="text-align: center; margin-left: 50px; margin-right: 50px;">

                    {{-- Texto introductorio (Opcional, pero recomendado para la tabla) --}}


                    <table style="text-align: center; width:100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th
                                    style="border: 1px solid black; padding: 2px; font-weight: bold; background-color: #f0f0f0; font-size: 7px;">
                                    MES</th>
                                <th
                                    style="border: 1px solid black; padding: 2px; font-weight: bold; background-color: #f0f0f0; font-size: 7px;">
                                    FECHA</th>
                                <th
                                    style="border: 1px solid black; padding: 2px; font-weight: bold; background-color: #f0f0f0; font-size: 7px;">
                                    DESCRIPCIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($amonestaciones as $item)
                                <tr>
                                    <td
                                        style="font-size:7px; text-align:center; border: 1px solid black; padding: 2px; text-transform: uppercase;">
                                        {{ \Carbon\Carbon::parse($item->fecha)->isoFormat('MMMM') }}
                                    </td>
                                    <td
                                        style="font-size:7px; text-align:center; border: 1px solid black; padding: 2px;">
                                        {{ \Carbon\Carbon::parse($item->fecha)->format('d/m/Y') }}
                                    </td>
                                    <td
                                        style="font-size:7px; text-align:left; border: 1px solid black; padding: 2px; text-transform: uppercase;">
                                        Amonestación previa por falta injustificada
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <p>
                Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                CAPITULO X artículo 42) "Medida disciplinaria o sanción es la acción correctiva tomando a través de
                los niveles de supervisión que sanciona la falta o faltas cometidas por uno o más colaboradores en
                contra de la disciplinaria".
            </p>

            <p>
                Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                obligación inherente a su contrato de trabajo, constituyendo además su inobservancia una manifiesta
                contravención del Reglamento Interno de Trabajo de la empresa, cuyas disposiciones son obligatorias
                y plenamente conocidas por todo el personal que labora para la empresa.
            </p>

            @if ($fecha == $fechaFin)
                <p>
                    Por esta razón, la empresa ha decidido proceder a sancionarlo con ({{ $diasSuspension }}) día de
                    <strong>Suspensión sin goce de Haber</strong>, fecha que será efectiva el día
                    <strong>{{ $fecha }}</strong>.
                    Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se tomarán
                    medidas pertinentes al respecto, siendo de su entera responsabilidad.
                </p>
            @else
                <p>
                    Por esta razón, la empresa ha decidido proceder a sancionarlo con ({{ $diasSuspension }}) días de
                    <strong>Suspensión sin goce de Haber</strong>, que será efectiva desde el
                    <strong>{{ $fecha }}</strong>
                    hasta el <strong>{{ $fechaFin }}</strong>.
                    Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se tomarán
                    medidas pertinentes al respecto, siendo de su entera responsabilidad.
                </p>
            @endif

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

        <!-- COLUMNA 2: SIN AMONESTACIONES PREVIAS (SOLO FALTA ACTUAL) -->
        <div class="memorandum">
            <h2>MEMORANDUM {{ $fechaMemo }}-RRHH/GVS</h2>

            <div class="header-line"><strong>DE:</strong> {{ $suspension->empleado->empresa->razonsocial }}</div>
            <div class="header-line"><strong>A:</strong>
                {{ "{$suspension->empleado->apellidos} {$suspension->empleado->nombres}" }}</div>
            <div class="header-line"><strong>ÁREA:</strong> {{ $suspension->empleado->area->nombre }}</div>
            <div class="header-line"><strong>FECHA:</strong> {{ now()->format('d/m/Y') }}</div>
            <div class="header-line"><strong>ASUNTO:</strong> <strong>SUSPENSION POR FALTA INJUSTIFICADA</strong></div>

            <hr>

            <p>
                Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la ley,
                le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en
                <strong>SUSPENSION POR FALTA INJUSTIFICADA</strong> en referencia a los hechos que describimos a
                continuación:
            </p>

            @if (count($amonestaciones) > 0)

                {{-- CASO 1: Es una Suspensión por Acumulación (Muestra la tabla de amonestaciones previas) --}}

                <div style="text-align: center; margin-left: 50px; margin-right: 50px;">

                    {{-- Texto introductorio (Opcional, pero recomendado para la tabla) --}}
                    <table style="text-align: center; width:100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th
                                    style="border: 1px solid black; padding: 2px; font-weight: bold; background-color: #f0f0f0; font-size: 7px;">
                                    MES</th>
                                <th
                                    style="border: 1px solid black; padding: 2px; font-weight: bold; background-color: #f0f0f0; font-size: 7px;">
                                    FECHA</th>
                                <th
                                    style="border: 1px solid black; padding: 2px; font-weight: bold; background-color: #f0f0f0; font-size: 7px;">
                                    DESCRIPCIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($amonestaciones as $item)
                                <tr>
                                    <td
                                        style="font-size:7px; text-align:center; border: 1px solid black; padding: 2px; text-transform: uppercase;">
                                        {{ \Carbon\Carbon::parse($item->fecha)->isoFormat('MMMM') }}
                                    </td>
                                    <td
                                        style="font-size:7px; text-align:center; border: 1px solid black; padding: 2px;">
                                        {{ \Carbon\Carbon::parse($item->fecha)->format('d/m/Y') }}
                                    </td>
                                    <td
                                        style="font-size:7px; text-align:center; border: 1px solid black; padding: 2px; text-transform: uppercase;">
                                        Amonestación previa por falta injustificada
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif


            <p>
                Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                CAPITULO X artículo 42) "Medida disciplinaria o sanción es la acción correctiva tomando a través de
                los niveles de supervisión que sanciona la falta o faltas cometidas por uno o más colaboradores en
                contra de la disciplinaria".
            </p>

            <p>
                Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                obligación inherente a su contrato de trabajo, constituyendo además su inobservancia una manifiesta
                contravención del Reglamento Interno de Trabajo de la empresa, cuyas disposiciones son obligatorias
                y plenamente conocidas por todo el personal que labora para la empresa.
            </p>

            @if ($fecha == $fechaFin)
                <p>
                    Por esta razón, la empresa ha decidido proceder a sancionarlo con ({{ $diasSuspension }}) día de
                    <strong>Suspensión sin goce de Haber</strong>, fecha que será efectiva el día
                    <strong>{{ $fecha }}</strong>.
                    Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se tomarán
                    medidas pertinentes al respecto, siendo de su entera responsabilidad.
                </p>
            @else
                <p>
                    Por esta razón, la empresa ha decidido proceder a sancionarlo con ({{ $diasSuspension }}) días de
                    <strong>Suspensión sin goce de Haber</strong>, que será efectiva desde el
                    <strong>{{ $fecha }}</strong>
                    hasta el <strong>{{ $fechaFin }}</strong>.
                    Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se tomarán
                    medidas pertinentes al respecto, siendo de su entera responsabilidad.
                </p>
            @endif

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
    </div>
</body>

</html>
