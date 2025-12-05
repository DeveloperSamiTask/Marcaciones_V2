<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        body {
            font-family: "Arial", sans-serif;
            margin: 0;
            padding: 8mm;
            /* un poco menos que 10mm para ahorrar espacio */
            box-sizing: border-box;
            font-size: 12px;
            /* tamaño base cómodo */
        }

        .contenedor {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            /* similar a tu estilo pero más eficiente */
            max-width: 100%;
            min-width: 960px;
            /* para que no se estire demasiado */
            page-break-inside: avoid;
            box-sizing: border-box;
        }

        .memorandum {
            width: 50%;
            padding: 8px;
            /* un poco menos para compactar */
            box-sizing: border-box;
        }

        h2 {
            font-size: 14px;
            text-align: center;
            margin: 4px 0;
            text-decoration: underline;
        }

        p,
        li {
            font-size: 12px;
            text-align: justify;
            margin: 4px 0;
            line-height: 1.15;
            /* compacto pero legible */
        }

        table {
            width: 100%;
            font-size: 11px;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid black;
            text-align: center;
            padding: 3px 4px;
        }

        .firmas {
            margin-top: 12px;
            /* menos espacio sin perder estética */
        }

        .firmas img {
            height: 50px;
            /* baja de 60px → 50px */
        }

        .firmas td {
            text-align: center;
            font-size: 11px;
        }

        .lineas td {
            font-size: 10.5px;
            text-align: center;
            padding-top: 6px;
        }

        .firmas table,
        .firmas td,
        .firmas tr {
            border: none !important;
        }

        /* Quitar el colapso feo, que no se vean cajas */
        .firmas table {
            border-collapse: separate !important;
        }

        /* Evitar espacios raros entre las firmas */
        .firmas td {
            padding: 0 !important;
        }

        /* Para alinear mejor las líneas */
        .lineas td {
            border: none !important;
            padding-top: 6px;
        }
    </style>


</head>

<body>
    <div class="contenedor">
        @for ($i = 0; $i < 2; $i++)
            <div class="memorandum">
                <h2>MEMORANDUM {{ $fecha }}-RRHH</h2>

                <p><strong>DE:</strong> {{ $marcacion->empleado->empresa->razonsocial }}</p>
                <p><strong>A:</strong> {{ "{$marcacion->empleado->apellidos} {$marcacion->empleado->nombres}" }}</p>
                <p><strong>ÁREA:</strong> {{ $marcacion->empleado->area->nombre }}</p>
                <p><strong>FECHA:</strong> {{ $marcacion->fecha->format('d/m/Y') }}</p>
                <p><strong>ASUNTO:</strong> <strong>AMONESTACIÓN ESCRITA POR TARDANZA</strong></p>
                <hr>

                <p>Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la ley,
                    le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en
                    <strong>AMONESTACIÓN ESCRITA POR TARDANZA</strong>, en referencia a:
                </p>

                <ul>
                    <li>No llegar puntualmente al área de trabajo.</li>
                </ul>

                <table>
                    <thead>
                        <tr>
                            <th>APELLIDOS Y NOMBRES</th>
                            <th>FECHA</th>
                            <th>INGRESO</th>
                            <th>HORA</th>
                            <th>TARDANZA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ "{$marcacion->empleado->apellidos} {$marcacion->empleado->nombres}" }}</td>
                            <td>{{ $marcacion->fecha->format('d/m/Y') }}</td>
                            <td>{{ $horario ? $horario->ingreso->format('H:i') : '00:00' }}</td>
                            <td>{{ $marcacion->ingreso->format('H:i') }}</td>
                            <td>{{ sprintf('%02d:%02d', floor($marcacion->tardanza / 60), $marcacion->tardanza % 60) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p>Al respecto, le recordamos lo que dice en nuestro reglamento interno de trabajo Capítulo VIII
                    Artículo 38° “Son obligaciones de los trabajadores”: inciso a) “Cumplir las normas del presente
                    reglamento, así como toda directiva emanada de la gerencia a través de su personal jerárquico”.</p>

                <p>Sobre el particular, le recordamos que cumplir su jornada ordinaria de trabajo en forma completa es
                    una obligación inherente a su contrato de trabajo, constituyendo además su inobservancia una
                    manifiesta contravención del Reglamento Interno de Trabajo de la empresa, cuyas disposiciones son
                    obligatorias y plenamente conocidas por todo el personal que labora para la empresa.</p>

                <p>Finalmente, la empresa lo exhorta a que hechos como este no vuelvan a suceder, caso contrario, se
                    tomarán medidas más drásticas al respecto, procediendo a una suspensión, siendo de su entera
                    responsabilidad.</p>

                <p>Atentamente,</p>

                <div class="firmas">
                    <table>
                        <tr>
                            <td><img src="{{ asset($marcacion->empleado->empresa->firma) }}" alt="Firma Empleador"></td>
                            <td><img src="{{ asset('storage/firmas/transparente.png') }}" alt="Firma Trabajador"></td>
                        </tr>
                    </table>

                    <table class="lineas">
                        <tr>
                            <td>___________________________________________</td>
                            <td>___________________________________________</td>
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
