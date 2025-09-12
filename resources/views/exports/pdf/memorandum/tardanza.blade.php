<!DOCTYPE html>
<html lang="en">

<head>
    <style>
        @page {
            margin-left: 0px;
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
            align-items: flex-start; /* Alinea el texto a la izquierda */
        }
        .firma-derecha {
            align-items: flex-end; /* Alinea el texto a la derecha */
        }
    </style>
</head>

<body>
    <div style="display: flex; page-break-inside: avoid;">
        <div style="text-align: center;">
            <h2 style="font-size:16px; text-align:center; margin-left: 50px;">
                <u>&nbsp;MEMORANDUM {{ $fecha }}-RRHH/ &nbsp;</u>
            </h2>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                DE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                {{ $marcacion->empleado->empresa->razonsocial }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                A&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                {{ "{$marcacion->empleado->apellidos} {$marcacion->empleado->nombres}" }}
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                ÁREA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $marcacion->empleado->area->nombre }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                FECHA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $marcacion->fecha->format('d/m/Y') }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">ASUNTO&nbsp;&nbsp;&nbsp;&nbsp;:
                <strong>AMONESTACIÓN ESCRITA POR TARDANZA</strong>
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                ________________________________________________________________________________________</p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Por la presente comunicación, y en
                ejercicio de las facultades sancionadoras que nos reconoce la ley, le comunicamos la decisión de la
                empresa de imponerle una sanción disciplinaria consistente en
                <strong>AMONESTACIÓN ESCRITA POR TARDANZA</strong>
                , en referencia a:
            </p>
            <li style="margin-left: 150px; margin-right: 230px;">No llegar puntualmente al área de trabajo.</li> </br>
            <br>
            <br>

            <div style="text-align: center; margin-left: 50px;margin-right: 50px;">
                <table style="text-align: center; width:100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid black;">APELLIDOS Y NOMBRES</th>
                            <th style="border: 1px solid black;">FECHA</th>
                            <th style="border: 1px solid black;">INGRESO</th>
                            <th style="border: 1px solid black;">HORA</th>
                            <th style="border: 1px solid black;">TARDANZA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ "{$marcacion->empleado->apellidos} {$marcacion->empleado->nombres}" }}
                            </td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;" >{{ $marcacion->fecha->format('d/m/Y') }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">{{ $horario ? $horario->ingreso->format('H:i') : '00:00' }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">{{ $marcacion->ingreso->format('H:i') }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ sprintf('%02d:%02d', floor($marcacion->tardanza / 60), $marcacion->tardanza % 60 ) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <br>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Al respecto, le recordamos lo que
                dice en nuestro reglamento
                interno de trabajo Capítulo VIII Artículo 38° “Son obligaciones de los trabajadores”: inciso a)
                “Cumplir las normas del
                presente reglamento, así como toda directiva emanada de la gerencia a través de su personal
                jerárquico”.</p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Sobre el particular, le recordamos
                que cumplir su jornada
                ordinaria de trabajo en forma completa es una obligación inherente a su contrato de trabajo,
                constituyendo
                además su inobservancia una manifiesta contravención del Reglamento Interno de Trabajo de la empresa,
                cuyas
                disposiciones son obligatorias y plenamente conocidas por todo el personal que labora para la empresa.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Finalmente, la empresa lo exhorta, a
                que hechos como
                este no vuelva a suceder, caso contrario, se tomaran medidas más drásticas al respecto procediendo a
                una suspensión,
                siendo de su entera responsabilidad.</p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Atentamente</p>
            <br>
            <br>

            <table style="width:100%;">
                <tbody>
                    <tr>
                        <td style="text-align: center;"><img src="{{ asset($marcacion->empleado->empresa->firma) }}" alt=""></td>
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
                <u>&nbsp;MEMORANDUM {{ $fecha }}-RRHH&nbsp;</u>
            </h2>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                DE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                {{ $marcacion->empleado->empresa->razonsocial }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                A&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                {{ "{$marcacion->empleado->apellidos} {$marcacion->empleado->nombres}" }}
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                ÁREA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $marcacion->empleado->area->nombre }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                FECHA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $marcacion->fecha->format('d/m/Y') }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">ASUNTO&nbsp;&nbsp;&nbsp;&nbsp;:
                <strong>AMONESTACIÓN ESCRITA POR TARDANZA</strong>
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                ________________________________________________________________________________________</p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Por la presente comunicación, y en
                ejercicio de las facultades sancionadoras que nos reconoce la ley, le comunicamos la decisión de la
                empresa de imponerle una sanción disciplinaria consistente en
                <strong>AMONESTACIÓN ESCRITA POR TARDANZA</strong>
                , en referencia a:
            </p>
            <li style="margin-left: 150px; margin-right: 230px;">No llegar puntualmente al área de trabajo.</li> </br>
            <br>
            <br>

			<div style="text-align: center; margin-left: 50px;margin-right: 50px;">
                <table style="text-align: center; width:100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid black;">APELLIDOS Y NOMBRES</th>
                            <th style="border: 1px solid black;">FECHA</th>
                            <th style="border: 1px solid black;">INGRESO</th>
                            <th style="border: 1px solid black;">HORA</th>
                            <th style="border: 1px solid black;">TARDANZA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ "{$marcacion->empleado->apellidos} {$marcacion->empleado->nombres}" }}
                            </td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;" >{{ $marcacion->fecha->format('d/m/Y') }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">{{ $horario ? $horario->ingreso->format('H:i') : '00:00' }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">{{ $marcacion->ingreso->format('H:i') }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ sprintf('%02d:%02d', floor($marcacion->tardanza / 60), $marcacion->tardanza % 60 ) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <br>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Al respecto, le recordamos lo que
                dice en nuestro reglamento
                interno de trabajo Capítulo VIII Artículo 38° “Son obligaciones de los trabajadores”: inciso a)
                “Cumplir las normas del
                presente reglamento, así como toda directiva emanada de la gerencia a través de su personal
                jerárquico”.</p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Sobre el particular, le recordamos
                que cumplir su jornada
                ordinaria de trabajo en forma completa es una obligación inherente a su contrato de trabajo,
                constituyendo
                además su inobservancia una manifiesta contravención del Reglamento Interno de Trabajo de la empresa,
                cuyas
                disposiciones son obligatorias y plenamente conocidas por todo el personal que labora para la empresa.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Finalmente, la empresa lo exhorta, a
                que hechos como
                este no vuelva a suceder, caso contrario, se tomaran medidas más drásticas al respecto procediendo a
                una suspensión,
                siendo de su entera responsabilidad.</p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Atentamente</p>
            <br>
            <br>

            <table style="width:100%;">
                <tbody>
                    <tr>
                        <td style="text-align: center;"><img src="{{ asset($marcacion->empleado->empresa->firma) }}" alt=""></td>
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
