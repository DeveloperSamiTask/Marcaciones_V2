<!DOCTYPE html>
<html lang="es">

<head>
    <style>
        @page {

            margin: 0;
        }

        * {
            font-family: "arial";
        }

        table {
            font-size: 14px;
        }

        .firma-container {
            font-size: 12px;
            display: flex;
            flex-direction: column;
        }
        .firma-izquierda {
            align-items: flex-start;
        }
        .firma-derecha {
            align-items: flex-end;
        }
    </style>
</head>

<body>

        @php
           $tipo = [
			'tardanza' => 'AMONESTACION ESCRITA POR TARDANZA',
			'refrigerio' => 'AMONESTACION ESCRITA POR SOBRE TIEMPO DE REFRIGERIO',
			'falta injustificada' => 'AMONESTACION ESCRITA POR FALTA INJUSTIFICADA',
			'negligencia' => 'AMONESTACION ESCRITA POR NEGLIGENCIA',
			'incumplimiento' => 'AMONESTACION ESCRITA POR INCUMPLIMIENTO',
			'incompleto' => 'AMONESTACION ESCRITA POR MARCACION IMCOMPLETA',
		];
        @endphp


    <div style="display: flex; page-break-inside: avoid;">
        <div style="text-align: center;">
            <h2 style="font-size:16px; text-align:center; margin-left: 50px;">
                <u>&nbsp;MEMORANDUM {{ $fechaMemo }}-RRHH/GVS&nbsp;</u>
            </h2>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                DE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                {{ $suspension->empleado->empresa->razonsocial }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                A&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                {{ "{$suspension->empleado->apellidos} {$suspension->empleado->nombres}" }}
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                ÁREA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $suspension->empleado->area->nombre }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                FECHA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ now()->format('d/m/Y') }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">ASUNTO&nbsp;&nbsp;&nbsp;&nbsp;:
                <strong>SUSPENSION POR ACUMULACION DE AMONESTACIONES</strong>
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                ________________________________________________________________________________________</p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la ley,
                le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en
                <strong>SUSPENSION POR ACUMULACION DE AMONESTACIONES</strong>, en referencia a los hechos que
                describimos a continuación:
            </p>
            <li style="margin-left: 150px; margin-right: 230px;">Por acumulación de amonestaciones.</li>
            <br>
            <br>

            <div style="text-align: center; margin-left: 50px;margin-right: 50px;">
                <table style="text-align: center; width:100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid black;">MES</th>
                            <th style="border: 1px solid black;">FECHA</th>
                            <th style="border: 1px solid black;">DESCRIPCIÓN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($amonestaciones as $item)
                        <tr>
                            <td style="font-size:12px; text-align:center; border: 1px solid black; text-transform: uppercase;">
                                {{ $item->fecha->isoFormat('MMMM') }}
                            </td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ $item->fecha->format('d/m/Y') }}
                            </td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black; text-transform: uppercase;">
                                {{ $tipo[$item->tipo] ?? 'AMONESTACION ESCRITA' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <br>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                CAPITULO X artículo 42 {{ $articulo }}
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                obligación inherente a su contrato de trabajo, constituyendo además su inobservancia una manifiesta
                contravención del Reglamento Interno de Trabajo de la empresa, cuyas disposiciones son obligatorias
                y plenamente conocidas por todo el personal que labora para la empresa.
            </p>

            @if ($fecha == $fechaFin)
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Por esta razón, la empresa ha decidido proceder a sancionarlo con ({{ $diasSuspension }}) día de
                <strong>Suspensión sin goce de haber</strong>, fecha que será efectiva el día <strong>{{ $fecha }}</strong>.
                Finalmente, se le exhorta a que hechos como este no vuelvan a suceder; caso contrario, se
                tomarán medidas pertinentes al respecto, siendo de su entera responsabilidad.
            </p>
            @else
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Por esta razón, la empresa ha decidido proceder a sancionarlo con ({{ $diasSuspension }}) días de
                <strong>Suspensión sin goce de haber</strong>, que será efectiva desde el <strong>{{ $fecha }}</strong>
                hasta el <strong>{{ $fechaFin }}</strong>.
                Finalmente, se le exhorta a que hechos como este no vuelvan a suceder; caso contrario, se
                tomarán medidas pertinentes al respecto, siendo de su entera responsabilidad.
            </p>
            @endif

            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Atentamente</p>
            <br>
            <br>

            <table style="width:100%;">
                <tbody>
                    <tr>
                        <td style="text-align: center;"><img src="{{ asset($suspension->empleado->empresa->firma) }}" alt=""></td>
                        <td style="text-align: center;"><img src="{{ asset('storage/firmas/transparente.png') }}" alt=""></td>
                    </tr>
                </tbody>
            </table>

            <table style="width:100%;">
                <tr>
                    <td align="center" style="font-size:12px;">___________________________________________</td>
                    <td align="center" style="font-size:12px;">___________________________________________</td>
                </tr>
                <tbody>
                    <tr align="center">
                        <td>EL EMPLEADOR</td>
                        <td>EL TRABAJADOR</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="text-align: center;">
            <h2 style="font-size:16px; text-align:center; margin-left: 50px;">
                <u>&nbsp;MEMORANDUM {{ $fechaMemo }}-RRHH/GVS&nbsp;</u>
            </h2>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                DE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                {{ $suspension->empleado->empresa->razonsocial }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                A&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                {{ "{$suspension->empleado->apellidos} {$suspension->empleado->nombres}" }}
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                ÁREA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $suspension->empleado->area->nombre }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                FECHA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ now()->format('d/m/Y') }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">ASUNTO&nbsp;&nbsp;&nbsp;&nbsp;:
                <strong>SUSPENSION POR ACUMULACION DE AMONESTACIONES</strong>
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                ________________________________________________________________________________________</p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la ley,
                le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en
                <strong>SUSPENSION POR ACUMULACION DE AMONESTACIONES</strong>, en referencia a los hechos que
                describimos a continuación:
            </p>
            <li style="margin-left: 150px; margin-right: 230px;">Por acumulación de amonestaciones.</li>
            <br>
            <br>

            <div style="text-align: center; margin-left: 50px;margin-right: 50px;">
                <table style="text-align: center; width:100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid black;">MES</th>
                            <th style="border: 1px solid black;">FECHA</th>
                            <th style="border: 1px solid black;">DESCRIPCIÓN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($amonestaciones as $item)
                        <tr>
                            <td style="font-size:12px; text-align:center; border: 1px solid black; text-transform: uppercase;">
                                {{ $item->fecha->isoFormat('MMMM') }}
                            </td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ $item->fecha->format('d/m/Y') }}
                            </td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black; text-transform: uppercase;">
                                {{ $tipo[$suspension->tipo] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <br>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                CAPITULO X artículo 42 {{ $articulo }}
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                obligación inherente a su contrato de trabajo, constituyendo además su inobservancia una manifiesta
                contravención del Reglamento Interno de Trabajo de la empresa, cuyas disposiciones son obligatorias
                y plenamente conocidas por todo el personal que labora para la empresa.
            </p>

            @if ($fecha == $fechaFin)
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Por esta razón, la empresa ha decidido proceder a sancionarlo con ({{ $diasSuspension }}) día de
                <strong>Suspensión sin goce de haber</strong>, fecha que será efectiva el día <strong>{{ $fecha }}</strong>.
                Finalmente, se le exhorta a que hechos como este no vuelvan a suceder; caso contrario, se
                tomarán medidas pertinentes al respecto, siendo de su entera responsabilidad.
            </p>
            @else
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Por esta razón, la empresa ha decidido proceder a sancionarlo con ({{ $diasSuspension }}) días de
                <strong>Suspensión sin goce de haber</strong>, que será efectiva desde el <strong>{{ $fecha }}</strong>
                hasta el <strong>{{ $fechaFin }}</strong>.
                Finalmente, se le exhorta a que hechos como este no vuelvan a suceder; caso contrario, se
                tomarán medidas pertinentes al respecto, siendo de su entera responsabilidad.
            </p>
            @endif

            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Atentamente</p>
            <br>
            <br>

            <table style="width:100%;">
                <tbody>
                    <tr>
                        <td style="text-align: center;"><img src="{{ asset($suspension->empleado->empresa->firma) }}" alt=""></td>
                        <td style="text-align: center;"><img src="{{ asset('storage/firmas/transparente.png') }}" alt=""></td>
                    </tr>
                </tbody>
            </table>

            <table style="width:100%;">
                <tr>
                    <td align="center" style="font-size:12px;">___________________________________________</td>
                    <td align="center" style="font-size:12px;">___________________________________________</td>
                </tr>
                <tbody>
                    <tr align="center">
                        <td>EL EMPLEADOR</td>
                        <td>EL TRABAJADOR</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
