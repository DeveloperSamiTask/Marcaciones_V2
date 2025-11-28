<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A5 landscape;
            margin: 0;
        }

        body {
            font-family: "Arial", sans-serif;
            margin: 0;
            padding: 8mm;
            box-sizing: border-box;
        }

        .contenedor {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            page-break-inside: avoid;
            max-width: 100%;
        }

        .memorandum {
            width: 50%;
            box-sizing: border-box;
            padding: 8px;
        }

        h2 {
            font-size: 12px;
            text-align: center;
            text-decoration: underline;
            margin: 0 0 6px 0;
        }

        p {
            font-size: 9px;
            text-align: justify;
            margin: 3px 0;
            line-height: 1.3;
        }

        .header-line {
            font-size: 9px;
            text-align: left;
            margin: 2px 0;
            line-height: 1.2;
        }

        hr {
            border: none;
            border-top: 1px solid #000;
            margin: 5px 0;
        }

        strong {
            font-weight: bold;
        }

        .firmas {
            margin-top: 10px;
        }

        .firmas img {
            height: 40px;
            max-width: 80px;
        }

        .firmas table {
            width: 100%;
        }

        .firmas td {
            text-align: center;
            vertical-align: bottom;
        }

        .lineas {
            margin-top: 3px;
        }

        .lineas td {
            font-size: 8px;
            text-align: center;
            padding-top: 2px;
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
                <div class="header-line"><strong>ASUNTO:</strong> <strong>AMONESTACION ESCRITA POR INCUMPLIR LAS NORMAS
                        DE TRABAJO</strong></div>

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
