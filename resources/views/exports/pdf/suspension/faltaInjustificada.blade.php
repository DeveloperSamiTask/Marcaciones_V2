    <!DOCTYPE html>
    <html lang="es">

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
                align-items: flex-start;
                /* Alinea el texto a la izquierda */
            }

            .firma-derecha {
                align-items: flex-end;
                /* Alinea el texto a la derecha */
            }
        </style>
    </head>

    <body>
        <div style="page-break-inside: avoid;">
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
                    ÁREA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                    {{ $suspension->empleado->area->nombre }}
                </p>
                <p style="font-size:14px; text-align:left; margin-left: 50px;">
                    FECHA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ now()->format('d/m/Y') }}</p>
                <p style="font-size:14px; text-align:left; margin-left: 50px;">ASUNTO&nbsp;&nbsp;&nbsp;&nbsp;:
                    <strong>SUSPENSION POR FALTA INJUSTIFICADA</strong>
                </p>
                <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                    ________________________________________________________________________________________</p>
                <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                    Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la
                    ley, le comunicamos la
                    decisión de la empresa de imponerle una sanción disciplinaria consistente en <strong>SUSPENSION POR
                        FALTA INJUSTIFICADA</strong> en referencia a los hechos que describimos a continuación:
                </p>
                <li style="margin-left: 150px; margin-right: 230px;">
                    Por faltar injustificadamente a su centro de labores el dia {{ $suspension->fecha->format('d/m/Y') }}
                </li>

                <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                    Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                    CAPITULO X artículo 42) “Medida disciplinaria o sanción es la acción correctiva tomando a través de
                    los niveles de supervisión que sanciona la falta o faltas cometidas por uno o más colaboradores en
                    contra de la disciplinaria”. </p>
                <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                    Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                    obligación inherente a su contrato de trabajo, constituyendo además su inobservancia una manifiesta
                    contravención del Reglamento Interno de Trabajo de la empresa, cuyas disposiciones son obligatorias
                    y plenamente conocidas por todo el personal que labora para la empresa. </p>
                <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                    Por esta razón, la empresa ha decidido proceder s sancionarlo con (1) días de <strong>Suspensión sin
                        goce de
                        Haber</strong>, fecha que será efectiva el dia <strong>{{ $fecha }}</strong>
                    Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se tomaran
                    medidas pertinentes al respecto, siendo de su entera responsabilidad.

                </p>
                <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Atentamente</p>

                <table style="width:100%;">
                    <tbody>
                        <tr align="center">
                            <td><img src="{{ asset($suspension->empleado->empresa->firma) }}" alt=""></td>
                            <td><img src="{{ asset('storage/firmas/transparente.png') }}" alt=""></td>
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
        <div style="page-break-inside: avoid;">
            <br>
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
                    ÁREA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                    {{ $suspension->empleado->area->nombre }}
                </p>
                <p style="font-size:14px; text-align:left; margin-left: 50px;">
                    FECHA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ now()->format('d/m/Y') }}</p>
                <p style="font-size:14px; text-align:left; margin-left: 50px;">ASUNTO&nbsp;&nbsp;&nbsp;&nbsp;:
                    <strong>SUSPENSION POR FALTA INJUSTIFICADA</strong>
                </p>
                <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                    ________________________________________________________________________________________</p>
                <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                    Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la
                    ley, le comunicamos la
                    decisión de la empresa de imponerle una sanción disciplinaria consistente en <strong>SUSPENSION POR
                        FALTA INJUSTIFICADA</strong> en referencia a los hechos que describimos a continuación:
                </p>
                <li style="margin-left: 150px; margin-right: 230px;">
                    Por faltar injustificadamente a su centro de labores el dia {{ $suspension->fecha->format('d/m/Y') }}
                </li>


                <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                    Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                    CAPITULO X artículo 42) “Medida disciplinaria o sanción es la acción correctiva tomando a través de
                    los niveles de supervisión que sanciona la falta o faltas cometidas por uno o más colaboradores en
                    contra de la disciplinaria”. </p>
                <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                    Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                    obligación inherente a su contrato de trabajo, constituyendo además su inobservancia una manifiesta
                    contravención del Reglamento Interno de Trabajo de la empresa, cuyas disposiciones son obligatorias
                    y plenamente conocidas por todo el personal que labora para la empresa. </p>
                <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                    Por esta razón, la empresa ha decidido proceder s sancionarlo con (1) días de <strong>Suspensión sin
                        goce de
                        Haber</strong>, fecha que será efectiva el dia <strong>{{ $fecha }}</strong>
                    Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se tomaran
                    medidas pertinentes al respecto, siendo de su entera responsabilidad.

                </p>
                <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Atentamente</p>

                <table style="width:100%;">
                    <tbody>
                        <tr align="center">
                            <td><img src="{{ asset($suspension->empleado->empresa->firma) }}" alt=""></td>
                            <td><img src="{{ asset('storage/firmas/transparente.png') }}" alt=""></td>
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
