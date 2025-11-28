<!DOCTYPE html>
<html lang="es">

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
            padding: 0;
            width: 210mm;
            height: 148mm;
        }

        @media print {
            body {
                margin: 0 !important;
                padding: 8mm !important;
            }

            .contenedor {
                width: 100% !important;
                margin: 0 !important;
            }
        }

        .contenedor {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            page-break-inside: avoid;
            max-width: 100%;
            padding: 8mm;
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
            margin-top: 2px;
        }

        .lineas td {
            font-size: 7px;
            text-align: center;
            padding-top: 2px;
            border: none;
        }
    </style>
</head>

<body>
    @php
        $tipo = [
            'tardanza' => 'AMONESTACION ESCRITA POR TARDANZA',
            'refrigerio' => 'AMONESTACION ESCRITA POR SOBRE TIEMPO DE REFRIGERIO',
        ];
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
                <div class="header-line"><strong>ASUNTO:</strong> <strong>SUSPENSION POR ACUMULACION DE
                        AMONESTACIONES</strong></div>

                <hr>

                <p>
                    Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la
                    ley, le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en
                    <strong>SUSPENSION POR ACUMULACION DE AMONESTACIONES</strong>
                    en referencia a los hechos que describimos a continuación:
                </p>

                <ul>
                    <li>Por acumulación de amonestaciones.</li>
                </ul>

                <table>
                    <thead>
                        <tr>
                            <th>MES</th>
                            <th>FECHA</th>
                            <th>DESCRIPCIÓN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($amonestaciones as $item)
                            <tr>
                                <td style="text-transform: uppercase;">{{ $item->fecha->isoFormat('MMMM') }}</td>
                                <td>{{ $item->fecha->format('d/m/Y') }}</td>
                                <td style="text-transform: uppercase;">{{ $tipo[$suspension->tipo] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p>
                    Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                    CAPITULO X artículo 42) "Medida disciplinaria o sanción es la acción correctiva tomando a través de
                    los niveles de supervisión que sanciona la falta o faltas cometidas por uno o más colaboradores en
                    contra de la disciplinaria".
                </p>

                <p>
                    Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                    obligación inherente a su contrato de trabajo, constituyendo además su inobservancia una manifiesta
                    contravención del Reglamento Interno de Trabajo de la empresa, cuyas disposiciones
                    son obligatorias y plenamente conocidas por todo el personal que labora para la empresa.
                </p>

                @if ($fecha == $fechaFin)
                    <p>
                        Por esta razón, la empresa ha decidido proceder a sancionarlo con ({{ $diasSuspension }}) día de
                        <strong>Suspensión sin goce de haber</strong>, fecha que será efectiva el día
                        <strong>{{ $fecha }}</strong>.
                        Finalmente, se le exhorta a que hechos como este no vuelvan a suceder; caso contrario, se
                        tomarán medidas pertinentes al respecto, siendo de su entera responsabilidad.
                    </p>
                @else
                    <p>
                        Por esta razón, la empresa ha decidido proceder a sancionarlo con ({{ $diasSuspension }}) días
                        de
                        <strong>Suspensión sin goce de haber</strong>, que será efectiva desde el
                        <strong>{{ $fecha }}</strong>
                        hasta el <strong>{{ $fechaFin }}</strong>.
                        Finalmente, se le exhorta a que hechos como este no vuelvan a suceder; caso contrario, se
                        tomarán medidas pertinentes al respecto, siendo de su entera responsabilidad.
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
