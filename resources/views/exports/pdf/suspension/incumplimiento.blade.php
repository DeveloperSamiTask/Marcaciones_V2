<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
            /* Sin margen en el papel */
        }

        body {
            font-family: "Arial", sans-serif;
            margin: 0;
            padding: 10mm;
            /* Simula margen visual */
            box-sizing: border-box;
        }

        .contenedor {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            page-break-inside: avoid;
            max-width: 100%;
            min-width: 980px;
            /* Puedes ajustar el padding si quieres más espacio */
            /* padding: 10mm;  // opcional si quieres espacio extra dentro */
            box-sizing: border-box;
        }


        .memorandum {
            width: 50%;
            box-sizing: border-box;
            padding: 10px;
        }

        h2 {
            font-size: 16px;
            text-align: center;
            text-decoration: underline;
        }

        p,
        li {
            font-size: 14px;
            text-align: justify;
            margin: 5px 0;
        }

        .firmas {
            margin-top: 20px;
        }

        .firmas img {
            height: 60px;
        }

        .firmas td {
            text-align: center;
        }

        .lineas td {
            font-size: 12px;
            text-align: center;
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

    <div class="contenedor">
        @for ($i = 0; $i < 2; $i++)
            <div class="memorandum">
                <h2>MEMORANDUM {{ $fechaMemo }}-RRHH/GVS</h2>

                <p><strong>DE:</strong> {{ $suspension->empleado->empresa->razonsocial }}</p>
                <p><strong>A:</strong> {{ "{$suspension->empleado->apellidos} {$suspension->empleado->nombres}" }}</p>
                <p><strong>ÁREA:</strong> {{ $suspension->empleado->area->nombre }}</p>
                <p><strong>FECHA:</strong> {{ now()->format('d/m/Y') }}</p>
                <p><strong>ASUNTO:</strong> <strong>AMONESTACION ESCRITA POR INCUMPLIR LAS NORMAS DE TRABAJO</strong>
                </p>
                <hr>

                <p>
                    Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la
                    ley,
                    le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en una
                    <strong>AMONESTACION ESCRITA POR INCUMPLIR LAS NORMAS DE TRABAJO</strong> en referencia a los hechos
                    que describimos a continuación:
                </p>

                <p>{{ $suspension->motivo }}</p>

                <p>
                    Estos hechos representan un incumplimiento a las cláusulas del Reglamento Interno de Trabajo
                    artículo 38 inciso {{ $articulo }}.
                </p>

                <p>
                    Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es
                    obligación inherente a su contrato de trabajo,
                    constituyendo además su inobservancia una manifiesta contravención del Reglamento Interno de Trabajo
                    de la empresa, cuyas disposiciones
                    son obligatorias y plenamente conocidas por todo el personal que labora para la empresa.
                </p>

                <p>
                    Finalmente, se le exhorta a que hechos como este no vuelvan a suceder, caso contrario,
                    se tomarán medidas pertinentes al respecto, siendo de su entera responsabilidad.
                </p>

                <p>Atentamente,</p>

                <div class="firmas">
                    <table style="width: 100%;">
                        <tr>
                            <td><img src="{{ asset($suspension->empleado->empresa->firma) }}" alt="Firma Empleador"></td>
                            <td><img src="{{ asset('storage/firmas/transparente.png') }}" alt="Firma Trabajador"></td>
                        </tr>
                    </table>

                    <table class="lineas" style="width: 100%;">
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
