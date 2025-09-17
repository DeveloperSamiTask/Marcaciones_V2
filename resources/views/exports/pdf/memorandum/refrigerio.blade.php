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
            border-collapse: collapse;
        }
     </style>
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
             <p style="font-size:14px; text-align:left; margin-left: 50px; ">ASUNTO&nbsp;&nbsp;&nbsp;&nbsp;:
                <strong>AMONESTACION ESCRITA POR SOBRE TIEMPO DE REFRIGERIO</strong>
              </p>
             <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                 ________________________________________________________________________________________</p>
             <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
              Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la ley,
              le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en
              <strong>AMONESTACION ESCRITA POR SOBRE TIEMPO DE REFRIGERIO</strong>
              en referencia a los hechos que describimos a continuación:
            </p>
             <li style="margin-left: 150px; margin-right: 230px;">•	No retornar puntualmente al área de trabajo.</li> </br>
             <br>
             <br>

             <div style="text-align: center; margin-left: 50px;margin-right: 50px;">
                 <table style="text-align: center; width:100%; border-collapse: collapse;">
                     <thead style="border-top: 0px; border-right: 0px; border-left: 0px;">
                         <tr>
                             <th style="border: 1px solid black;">APELLIDOS Y NOMBRES</th>
                             <th style="border: 1px solid black;">FECHA</th>
                             <th style="border: 1px solid black;">INGRESO</th>
                             <th style="border: 1px solid black;">TERMINO</th>
                             <th style="border: 1px solid black;">TARDANZA</th>
                         </tr>
                     </thead>
                     <tbody>
                         <tr>
                             <td style="font-size:12px; text-align:center; border: 1px solid black;">
                                {{ "{$marcacion->empleado->apellidos} {$marcacion->empleado->nombres}" }}
                             </td>
                             <td style="font-size:12px; text-align:center; border: 1px solid black;">{{ $marcacion->fecha->format('d/m/Y') }}</td>
                             <td style="font-size:12px; text-align:center; border: 1px solid black;">{{ $marcacion->ingreso_refri->format('H:i') }}</td>
                             <td style="font-size:12px; text-align:center; border: 1px solid black;">{{ $marcacion->salida_refri->format('H:i') }}</td>
                             <td style="font-size:12px; text-align:center; border: 1px solid black;">{{ $marcacion->refrigerio }}</td>
                         </tr>
                     </tbody>
                 </table>
             </div>
             <br>
             <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
              Estos hechos representan un incumplimiento a las cláusulas del Reglamento interno de trabajo en el Capítulo IV,
              de las normas de control de asistencia al trabajo, Articulo 21 son faltas sujetas a sanción, entre otras
              las siguientes, inciso B” No ingresar en forma inmediata a su área de trabajo después de haber registrado
              su marcación”.
            </p>
             <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
              Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es obligación
              inherente a su contrato de trabajo, constituyendo además su inobservancia una manifiesta contravención del
              Reglamento Interno de Trabajo de la empresa, cuyas disposiciones son obligatorias y plenamente conocidas
              por todo el personal que labora para la empresa.
            </p>
             <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
              Finalmente, se le exhorta, que hechos como este no vuelva a suceder, caso contrario, se tomaran medidas pertinentes
              al respecto, siendo de su entera responsabilidad.
            </p>
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
                     <td align="center" style="font-size:12px;"><b>___________________________________________</b> </td>
                     <p>
                         <td align="center" style="font-size:12px;">___________________________________________</b></td>
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
            <p style="font-size:14px; text-align:left; margin-left: 50px; ">ASUNTO&nbsp;&nbsp;&nbsp;&nbsp;:
               <strong>AMONESTACION ESCRITA POR SOBRE TIEMPO DE REFRIGERIO</strong>
             </p>
            <p style="font-size:14px; text-align:left; margin-left: 50px; margin-right: 50px;">
                ________________________________________________________________________________________</p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
             Por la presente comunicación, y en ejercicio de las facultades sancionadoras que nos reconoce la ley,
             le comunicamos la decisión de la empresa de imponerle una sanción disciplinaria consistente en
             <strong>AMONESTACION ESCRITA POR SOBRE TIEMPO DE REFRIGERIO</strong>
             en referencia a los hechos que describimos a continuación:
           </p>
            <li style="margin-left: 150px; margin-right: 230px;">•	No retornar puntualmente al área de trabajo.</li> </br>
            <br>
            <br>

            <div style="text-align: center; margin-left: 50px;margin-right: 50px;">
                <table style="text-align: center; width:100%; border-collapse: collapse;">
                    <thead style="border-top: 0px; border-right: 0px; border-left: 0px;">
                        <tr>
                            <th style="border: 1px solid black;">APELLIDOS Y NOMBRES</th>
                            <th style="border: 1px solid black;">FECHA</th>
                            <th style="border: 1px solid black;">INGRESO</th>
                            <th style="border: 1px solid black;">TERMINO</th>
                            <th style="border: 1px solid black;">TARDANZA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">
                               {{ "{$marcacion->empleado->apellidos} {$marcacion->empleado->nombres}" }}
                            </td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">{{ $marcacion->fecha->format('d/m/Y') }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">{{ $marcacion->ingreso_refri->format('H:i') }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">{{ $marcacion->salida_refri->format('H:i') }}</td>
                            <td style="font-size:12px; text-align:center; border: 1px solid black;">{{ $marcacion->refrigerio }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <br>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
             Estos hechos representan un incumplimiento a las cláusulas del Reglamento interno de trabajo en el Capítulo IV,
             de las normas de control de asistencia al trabajo, Articulo 21 son faltas sujetas a sanción, entre otras
             las siguientes, inciso B” No ingresar en forma inmediata a su área de trabajo después de haber registrado
             su marcación”.
           </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
             Sobre el particular, le recordamos que cumplir con las normas establecidas por la empresa es obligación
             inherente a su contrato de trabajo, constituyendo además su inobservancia una manifiesta contravención del
             Reglamento Interno de Trabajo de la empresa, cuyas disposiciones son obligatorias y plenamente conocidas
             por todo el personal que labora para la empresa.
           </p>
            <p style="text-align:justify; margin-left: 50px; margin-right: 50px;">
             Finalmente, se le exhorta, que hechos como este no vuelva a suceder, caso contrario, se tomaran medidas pertinentes
             al respecto, siendo de su entera responsabilidad.
           </p>
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
                    <td align="center" style="font-size:12px;"><b>___________________________________________</b> </td>
                    <p>
                        <td align="center" style="font-size:12px;">___________________________________________</b></td>
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
