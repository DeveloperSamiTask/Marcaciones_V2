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
    <div style="display: flex; page-break-inside: avoid;">
        <div style="text-align: center;">
            <h2 style="font-size:16px; text-align:center; margin-left: 50px;">
                <u>&nbsp;MEMORANDUM {{ $fecha }}-RRHH&nbsp;</u>
            </h2>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                DE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                {{ $permiso->empleado->empresa->razonsocial }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                A&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                {{ "{$permiso->empleado->apellidos} {$permiso->empleado->nombres}" }}
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                ÁREA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $permiso->empleado->area->nombre }}
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                FECHA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $permiso->fecha->format('d/m/Y') }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">ASUNTO&nbsp;&nbsp;&nbsp;&nbsp;:
                <strong>AMONESTACION POR FALTA INJUSTIFICADA</strong>
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                ________________________________________________________________________________________</p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la ley,
                le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en
                <strong>AMONESTACION POR FALTA INJUSTIFICADA en referencia</strong>
                a los hechos que describimos a continuación:
            </p>
            <li style="margin-left: 150px; margin-right: 230px;">
                -	"El dia {{ $permiso->fecha->format('d/m/Y') }} usted falto injustificadamente a su centro de labores"
            </li>
            </br>
            <br>
            <br>

            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Estos hechos representan un incumplimiento a las clausulas del reglamento interno de trabajo en el capítulo IV Artículo 21 inciso
                e) “Faltar injustificadamente al trabajo”.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es obligación inherente a su contrato de trabajo,
                constituyendo además su inobservancia una manifiesta contravención del Reglamento Interno de Trabajo de la empresa, cuyas disposiciones
                son obligatorias y plenamente conocidas por todo el personal que labora para la empresa.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Finalmente, se le exhorta, que hechos como este no vuelva a suceder, caso contrario, se tomaran medidas pertinentes al respecto, siendo de su entera responsabilidad.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Atentamente</p>
            <br>
            <br>

            <table style="width:100%;">
                <tbody>
                    <tr align="center">
                        <td><img src="{{ asset($permiso->empleado->empresa->firma) }}" alt=""></td>
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
                <u>&nbsp;MEMORANDUM {{ $fecha }}-RRHH&nbsp;</u>
            </h2>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                DE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                {{ $permiso->empleado->empresa->razonsocial }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                A&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                {{ "{$permiso->empleado->apellidos} {$permiso->empleado->nombres}" }}
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                ÁREA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $permiso->empleado->area->nombre }}
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">
                FECHA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $permiso->fecha->format('d/m/Y') }}</p>
            <p style="font-size:14px; text-align:left; margin-left: 50px;">ASUNTO&nbsp;&nbsp;&nbsp;&nbsp;:
                <strong>AMONESTACION POR FALTA INJUSTIFICADA</strong>
            </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                ________________________________________________________________________________________</p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la ley,
                le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en
                <strong>AMONESTACION POR FALTA INJUSTIFICADA en referencia</strong>
                a los hechos que describimos a continuación:
            </p>
            <li style="margin-left: 150px; margin-right: 230px;">
                -	"El dia {{ $permiso->fecha->format('d/m/Y') }} usted falto injustificadamente a su centro de labores"
            </li>
            </br>
            <br>
            <br>

            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Estos hechos representan un incumplimiento a las clausulas del reglamento interno de trabajo en el capítulo IV Artículo 21 inciso
                e) “Faltar injustificadamente al trabajo”.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es obligación inherente a su contrato de trabajo,
                constituyendo además su inobservancia una manifiesta contravención del Reglamento Interno de Trabajo de la empresa, cuyas disposiciones
                son obligatorias y plenamente conocidas por todo el personal que labora para la empresa.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
                Finalmente, se le exhorta, que hechos como este no vuelva a suceder, caso contrario, se tomaran medidas pertinentes al respecto, siendo de su entera responsabilidad.
            </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">Atentamente</p>
            <br>
            <br>

            <table style="width:100%;">
                <tbody>
                    <tr align="center">
                        <td><img src="{{ asset($permiso->empleado->empresa->firma) }}" alt=""></td>
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
