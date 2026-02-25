<!DOCTYPE html>
<html lang="es">

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
            padding: 14mm;
            box-sizing: border-box;
        }

        .contenedor {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            page-break-inside: avoid;
            max-width: 100%;
        }

        .memorandum {
            width: 50%;
            box-sizing: border-box;
            padding: 12px;
        }

        h2 {
            font-size: 15px;
            text-align: center;
            text-decoration: underline;
            margin: 0 0 6px 0;
        }

        p {
            font-size: 13px;
            text-align: justify;
            margin: 5px 0;
            line-height: 1.3;
        }

        .header-line {
            font-size: 13px;
            text-align: left;
            margin: 4px 0;
            line-height: 1.2;
        }

        hr {
            border: none;
            border-top: 1px solid #000;
            margin: 6px 0;
        }

        ul {
            margin: 4px 0;
            padding-left: 20px;
        }

        li {
            font-size: 12px;
            margin: 2px 0;
            line-height: 1.2;
        }

        strong {
            font-weight: bold;
        }

        table {
            font-size: 9px;
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }

        table th,
        table td {
            font-size: 9px;
            border: 1px solid black;
            padding: 3px;
            text-align: center;
        }

        table th {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .firmas {
            margin-top: 70px;
        }

        .firmas img {
          height: 100px;
            max-width: 160px;
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
            margin-top: 10px;
        }

        .lineas td {
            font-size: 10px;
            text-align: center;
            padding-top: 3px;
            border: none;
        }
    </style>
</head>

<body>
    <div class="contenedor">
        @for ($i = 0; $i < 2; $i++)
            <div class="memorandum">
                <h2>MEMORANDUM {{ $fechaMemo }}-RRHH/GVS</h2>

                <div class="header-line"><strong>DE:</strong> {{ $suspension->empleado->empresa->razonsocial }}</div>
                <div class="header-line"><strong>A:</strong>
                    {{ "{$suspension->empleado->apellidos} {$suspension->empleado->nombres}" }}</div>
                <div class="header-line"><strong>ÁREA:</strong> {{ $suspension->empleado->area->nombre }}</div>
                <div class="header-line"><strong>FECHA:</strong> {{ now()->format('d/m/Y') }}</div>
                <div class="header-line"><strong>ASUNTO:</strong> <strong>SUSPENSION POR FALTA INJUSTIFICADA</strong>
                </div>

                <hr>

                <p>
                    Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la
                    ley,
                    le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en
                    <strong>SUSPENSION POR FALTA INJUSTIFICADA</strong> en referencia a los hechos que describimos a
                    continuación:
                </p>

                @if (count($amonestaciones) > 1)
                    {{-- Tabla cuando hay más de una amonestación --}}
                    <table style="width: 100%; border-collapse: collapse; margin: 5px 0;">
                        <thead>
                            <tr>
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
                            @foreach ($amonestaciones as $amonestacion)
                                <tr>
                                    <td
                                        style="text-transform: uppercase; font-size: 7px; border: 1px solid black; padding: 2px; text-align: center;">
                                        {{ \Carbon\Carbon::parse($amonestacion->fecha)->isoFormat('MMMM') }}
                                    </td>
                                    <td
                                        style="font-size: 7px; border: 1px solid black; padding: 2px; text-align: center;">
                                        {{ \Carbon\Carbon::parse($amonestacion->fecha)->format('d/m/Y') }}
                                    </td>
                                    <td
                                        style="text-transform: uppercase; font-size: 7px; border: 1px solid black; padding: 2px; text-align: center;">
                                        AMONESTACION ESCRITA POR FALTA INJUSTIFICADA
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @elseif (count($amonestaciones) == 1)
                    {{-- Lista cuando hay exactamente una amonestación --}}
                    <ul>
                        @foreach ($amonestaciones as $amonestacion)
                            <li>Amonestación previa por falta injustificada el día
                                {{ \Carbon\Carbon::parse($amonestacion->fecha)->format('d/m/Y') }}.</li>
                        @endforeach
                    </ul>
                @else
                    {{-- Lista cuando no hay amonestaciones (solo la falta actual) --}}
                    <ul>
                        <li>Por faltar injustificadamente a su centro de labores el día
                            {{ $suspension->fecha->format('d/m/Y') }}.</li>
                    </ul>
                @endif

                <p>
                    Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                    CAPITULO X artículo 42 {{ $articulo }}
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
                        Por esta razón, la empresa ha decidido proceder a sancionarlo con ({{ $diasSuspension }}) días
                        de
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
