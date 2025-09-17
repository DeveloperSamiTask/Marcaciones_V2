<!DOCTYPE html>
<html lang="es">

<head>
    <style>
        @page {
            size: landscape;
            margin-left: 0px;
            margin: 0;
        }

        * {
            font-family: "arial";
        }

        table {
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div style="display: flex; page-break-inside: avoid;">
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
                <strong>AMONESTACION ESCRITA POR INCUMPLIR LAS NORMAS DE CONTROL DE ASISTENSIA DE TRABAJO</strong>
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                ________________________________________________________________________________________</p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la ley, le
                comunicamos
                la decisión de la empresa de imponerle una sanción disciplinaria consistente en una
                <strong>AMONESTACION ESCRITA POR INCUMPLIR LAS NORMAS DE CONTROL DE ASISTENSIA DE TRABAJO</strong>
                en referencia a los hechos que describimos a continuación:
            </p>
            <li style="margin-left: 150px; margin-right: 230px;">
                "No realizar el número de marcaciones requeridas durante la jornada laboral"
            </li>
            </br>
            <br>
            <br>

            <div style="text-align: center; margin-left: 50px;margin-right: 50px;">
                <table style="text-align: center; width:100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid black;">APELLIDOS Y NOMBRES</th>
                            <th style="border: 1px solid black;">FECHA</th>
                            <th style="border: 1px solid black;">HI</th>
                            <th style="border: 1px solid black;">HS</th>
                            <th style="border: 1px solid black;">HIREF</th>
                            <th style="border: 1px solid black;">HTREF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ "{$marcacion->empleado->apellidos} {$marcacion->empleado->nombres}" }}
                            </td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ $marcacion->fecha->format('d/m/Y') }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ $marcacion->ingreso ? $marcacion->ingreso->format('H:i') : '' }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ $marcacion->salida ? $marcacion->salida->format('H:i') : '' }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ $marcacion->ingreso_refri ? $marcacion->ingreso_refri->format('H:i') : '' }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ $marcacion->salida_refri ? $marcacion->salida_refri->format('H:i') : '' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <br>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Estos hechos representan un incumplimiento al reglamento interno de trabajo en Capitulo IV Artículo 21º
                Son faltas sujetas a sanción,
                entre otras, las siguientes, inciso a “No marcar el ingreso o salida de su centro trabajo”.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es obligación
                inherente a su contrato de trabajo,
                constituyendo además su inobservancia una manifiesta contravención del Reglamento Interno de Trabajo de
                la empresa, cuyas disposiciones
                son obligatorias y plenamente conocidas por todo el personal que labora para la empresa.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se tomaran medidas
                pertinentes al respecto,
                siendo de su entera responsabilidad.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Atentamente</p>
            <br>
            <br>

            <table style="width:100%;">
                <tbody>
                    <tr>
                        <td style="text-align: center;"><img src="{{ asset($marcacion->empleado->empresa->firma) }}"
                                alt=""></td>
                        <td style="text-align: center;"><img src="{{ asset('storage/firmas/transparente.png') }}"
                                alt=""></td>
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
                ÁREA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $marcacion->empleado->area->nombre }}
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                FECHA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $marcacion->fecha->format('d/m/Y') }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">ASUNTO&nbsp;&nbsp;&nbsp;&nbsp;:
                <strong>AMONESTACION ESCRITA POR INCUMPLIR LAS NORMAS DE CONTROL DE ASISTENSIA DE TRABAJO</strong>
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                ________________________________________________________________________________________</p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la ley, le
                comunicamos
                la decisión de la empresa de imponerle una sanción disciplinaria consistente en una
                <strong>AMONESTACION ESCRITA POR INCUMPLIR LAS NORMAS DE CONTROL DE ASISTENSIA DE TRABAJO</strong>
                en referencia a los hechos que describimos a continuación:
            </p>
            <li style="margin-left: 150px; margin-right: 230px;">
                "No realizar el número de marcaciones requeridas durante la jornada laboral"
            </li>
            <br>
            <br>

            <div style="text-align: center; margin-left: 50px;margin-right: 50px;">
                <table style="text-align: center; width:100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid black;">APELLIDOS Y NOMBRES</th>
                            <th style="border: 1px solid black;">FECHA</th>
                            <th style="border: 1px solid black;">HI</th>
                            <th style="border: 1px solid black;">HS</th>
                            <th style="border: 1px solid black;">HIREF</th>
                            <th style="border: 1px solid black;">HTREF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ "{$marcacion->empleado->apellidos} {$marcacion->empleado->nombres}" }}
                            </td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ $marcacion->fecha->format('d/m/Y') }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ $marcacion->ingreso ? $marcacion->ingreso->format('H:i') : '' }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ $marcacion->salida ? $marcacion->salida->format('H:i') : '' }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ $marcacion->ingreso_refri ? $marcacion->ingreso_refri->format('H:i') : '' }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ $marcacion->salida_refri ? $marcacion->salida_refri->format('H:i') : '' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <br>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Estos hechos representan un incumplimiento al reglamento interno de trabajo en Capitulo IV Artículo 21º
                Son faltas sujetas a sanción,
                entre otras, las siguientes, inciso a “No marcar el ingreso o salida de su centro trabajo”.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es obligación
                inherente a su contrato de trabajo,
                constituyendo además su inobservancia una manifiesta contravención del Reglamento Interno de Trabajo de
                la empresa, cuyas disposiciones
                son obligatorias y plenamente conocidas por todo el personal que labora para la empresa.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se tomaran medidas
                pertinentes al respecto,
                siendo de su entera responsabilidad.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Atentamente</p>
            <br>
            <br>

            <table style="width:100%;">
                <tbody>
                    <tr>
                        <td style="text-align: center;"><img src="{{ asset($marcacion->empleado->empresa->firma) }}"
                                alt=""></td>
                        <td style="text-align: center;"><img src="{{ asset('storage/firmas/transparente.png') }}"
                                alt=""></td>
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
