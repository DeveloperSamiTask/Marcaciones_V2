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
        @php
            $codigo = [
                'S' => 'SUSPENSION POR NEGLIGENCIA DE FUNCIONES',
                'A' => 'AMONESTACION ESCRITA POR NEGLIGENCIA DE FUNCIONES',
            ];
        @endphp
        @if ($suspension->codigo[0] == 'S')
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
                        <strong>{{ $codigo[$suspension->codigo[0]] }}</strong>
                    </p>
                    <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                        ________________________________________________________________________________________</p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la
                        ley, le comunicamos la decisión
                        de la empresa de imponerle una sanción disciplinaria consistente en
                        <strong>{{ $codigo[$suspension->codigo[0]] }}</strong>
                        en referencia a los hechos que describimos a continuación:
                    </p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        {{ $suspension->motivo }}
                    </p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                        artículo 48 inciso
                        {{ $articulo }}
                    </p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                        obligación inherente a su
                        contrato de trabajo, constituyendo además su inobservancia una manifiesta contravención del
                        Reglamento Interno de Trabajo de la empresa,
                        cuyas disposiciones son obligatorias y plenamente conocidas por todo el personal que labora para
                        la empresa.
                    </p>
                    @if ($suspension->codigo[0] == 'S')
                        @if ($diasSuspension == 1)
                            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                                Por esta razón, la empresa ha decidido proceder a sancionarlo con
                                ({{ $diasSuspension }}) día de <strong>Suspensión sin goce de Haber</strong>, fecha
                                que será efectiva el día <strong>{{ $fecha }}</strong>.
                                Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se
                                tomaran medidas pertinentes al respecto, siendo de su entera responsabilidad.
                            </p>
                        @else
                            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                                Por esta razón, la empresa ha decidido proceder a sancionarlo con
                                ({{ $diasSuspension }}) días de <strong>Suspensión sin goce de Haber</strong>, que será
                                efectiva desde el
                                <strong>{{ $fecha }}</strong> hasta el <strong>{{ $fechaFin }}</strong>.
                                Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se
                                tomaran medidas pertinentes al respecto, siendo de su entera responsabilidad.
                            </p>
                        @endif
                    @endif
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
                <div style="text-align: center;">
                    <br>
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
                        <strong>{{ $codigo[$suspension->codigo[0]] }}</strong>
                    </p>
                    <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                        ________________________________________________________________________________________</p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la
                        ley, le comunicamos la decisión
                        de la empresa de imponerle una sanción disciplinaria consistente en
                        <strong>{{ $codigo[$suspension->codigo[0]] }}</strong>
                        en referencia a los hechos que describimos a continuación:
                    </p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        {{ $suspension->motivo }}
                    </p>



                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                        artículo 48 inciso
                        {{ $articulo }}
                    </p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                        obligación inherente a su
                        contrato de trabajo, constituyendo además su inobservancia una manifiesta contravención del
                        Reglamento Interno de Trabajo de la empresa,
                        cuyas disposiciones son obligatorias y plenamente conocidas por todo el personal que labora para
                        la empresa.
                    </p>
                    @if ($suspension->codigo[0] == 'S')
                        @if ($diasSuspension == 1)
                            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                                Por esta razón, la empresa ha decidido proceder a sancionarlo con
                                ({{ $diasSuspension }}) día de <strong>Suspensión sin goce de Haber</strong>, fecha
                                que será efectiva el día <strong>{{ $fecha }}</strong>.
                                Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se
                                tomaran medidas pertinentes al respecto, siendo de su entera responsabilidad.
                            </p>
                        @else
                            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                                Por esta razón, la empresa ha decidido proceder a sancionarlo con
                                ({{ $diasSuspension }}) días de <strong>Suspensión sin goce de Haber</strong>, que será
                                efectiva desde el
                                <strong>{{ $fecha }}</strong> hasta el <strong>{{ $fechaFin }}</strong>.
                                Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se
                                tomaran medidas pertinentes al respecto, siendo de su entera responsabilidad.
                            </p>
                        @endif
                    @endif
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
        @else
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
                        ÁREA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                        {{ $suspension->empleado->area->nombre }}
                    </p>
                    <p style="font-size:14px; text-align:left; margin-left: 50px;">
                        FECHA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ now()->format('d/m/Y') }}</p>
                    <p style="font-size:14px; text-align:left; margin-left: 50px;">ASUNTO&nbsp;&nbsp;&nbsp;&nbsp;:
                        <strong>{{ $codigo[$suspension->codigo[0]] }}</strong>
                    </p>
                    <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                        ________________________________________________________________________________________</p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la
                        ley, le comunicamos la decisión
                        de la empresa de imponerle una sanción disciplinaria consistente en
                        <strong>{{ $codigo[$suspension->codigo[0]] }}</strong>
                        en referencia a los hechos que describimos a continuación:
                    </p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        {{ $suspension->motivo }}
                    </p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                        artículo 38 inciso
                        {{ $articulo }}
                    </p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                        obligación inherente a su
                        contrato de trabajo, constituyendo además su inobservancia una manifiesta contravención del
                        Reglamento Interno de Trabajo de la empresa,
                        cuyas disposiciones son obligatorias y plenamente conocidas por todo el personal que labora para
                        la empresa.
                    </p>
                    @if ($suspension->codigo[0] == 'S')
                        @if ($diasSuspension == 1)
                            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                                Por esta razón, la empresa ha decidido proceder a sancionarlo con
                                ({{ $diasSuspension }}) día de <strong>Suspensión sin goce de Haber</strong>, fecha
                                que será efectiva el día <strong>{{ $fecha }}</strong>.
                                Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se
                                tomaran medidas pertinentes al respecto, siendo de su entera responsabilidad.
                            </p>
                        @else
                            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                                Por esta razón, la empresa ha decidido proceder a sancionarlo con
                                ({{ $diasSuspension }}) días de <strong>Suspensión sin goce de Haber</strong>, que será
                                efectiva desde el
                                <strong>{{ $fecha }}</strong> hasta el <strong>{{ $fechaFin }}</strong>.
                                Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se
                                tomaran medidas pertinentes al respecto, siendo de su entera responsabilidad.
                            </p>
                        @endif
                    @endif
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
                        <strong>{{ $codigo[$suspension->codigo[0]] }}</strong>
                    </p>
                    <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                        ________________________________________________________________________________________</p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la
                        ley, le comunicamos la decisión
                        de la empresa de imponerle una sanción disciplinaria consistente en
                        <strong>{{ $codigo[$suspension->codigo[0]] }}</strong>
                        en referencia a los hechos que describimos a continuación:
                    </p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        {{ $suspension->motivo }}
                    </p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                        artículo 38 inciso
                        {{ $articulo }}
                    </p>
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                        Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                        obligación inherente a su
                        contrato de trabajo, constituyendo además su inobservancia una manifiesta contravención del
                        Reglamento Interno de Trabajo de la empresa,
                        cuyas disposiciones son obligatorias y plenamente conocidas por todo el personal que labora para
                        la empresa.
                    </p>
                    @if ($suspension->codigo[0] == 'S')
                        @if ($diasSuspension == 1)
                            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                                Por esta razón, la empresa ha decidido proceder a sancionarlo con
                                ({{ $diasSuspension }}) día de <strong>Suspensión sin goce de Haber</strong>, fecha
                                que será efectiva el día <strong>{{ $fecha }}</strong>.
                                Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se
                                tomaran medidas pertinentes al respecto, siendo de su entera responsabilidad.
                            </p>
                        @else
                            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                                Por esta razón, la empresa ha decidido proceder a sancionarlo con
                                ({{ $diasSuspension }}) días de <strong>Suspensión sin goce de Haber</strong>, que será
                                efectiva desde el
                                <strong>{{ $fecha }}</strong> hasta el <strong>{{ $fechaFin }}</strong>.
                                Finalmente, se le exhorta, que hechos como este no vuelvan a suceder, caso contrario, se
                                tomaran medidas pertinentes al respecto, siendo de su entera responsabilidad.
                            </p>
                        @endif
                    @endif
                    <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Atentamente</p>

                    <table style="width:100%;">
                        <tbody>
                            <tr align="center">
                                <td><img src="{{ asset($suspension->empleado->empresa->firma) }}" alt="">
                                </td>
                                <td><img src="{{ asset('storage/firmas/transparente.png') }}" alt=""></td>
                            </tr>
                        </tbody>
                    </table>

                    <table style="width:100%;">
                        <tr>
                            <td align="center" style="font-size:12px;">___________________________________________
                            </td>
                            <td align="center" style="font-size:12px;">___________________________________________
                            </td>
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
        @endif

    </body>

    </html>
