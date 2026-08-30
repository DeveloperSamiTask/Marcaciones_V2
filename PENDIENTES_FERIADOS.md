# Pendientes: compensación con feriados

Estado al 13/08/2026: implementación realizada, todavía pendiente de validación funcional completa y entrega.

## 1. Probar compensación de día completo

Probar los dos caminos por separado:

- Con marcación existente: debe actualizar ingreso y salida con el horario programado.
- Sin marcación existente: debe crear la marcación con ingreso y salida programados.

En ambos casos verificar:

- Rechaza la operación cuando no existe horario programado.
- Rechaza la operación cuando el saldo de feriados es insuficiente.
- Puede consumir uno o varios feriados, comenzando por el más antiguo.
- Descuenta correctamente `horarios.feriado`.
- Acumula correctamente `horarios.feriado_consumido`.
- Conserva `calculo_manual_feriado = 1`.
- Guarda la descripción con la fecha del feriado utilizado y la fecha compensada.
- Genera el registro consolidado de edición.
- Si ocurre un error, la transacción no deja consumos parciales.

## 2. Verificar el recálculo de feriados en producción

Problema observado:

- El cálculo obtiene el saldo esperado, pero anteriormente `Horario::update()` devolvía éxito y las columnas seguían en cero.
- Ejemplo observado: horario `263937`, saldo calculado `08:30:00`, pero la lectura posterior devolvía `00:00:00` y flag `0`.

Cambio aplicado:

- El recálculo escribe mediante `DB::table('horarios')->update()` para no depender de `$fillable`.
- La verificación posterior usa `useWritePdo()`.
- Si la base revierte el valor, el proceso debe lanzar un error y advertir sobre posibles triggers.

Validación pendiente en producción:

- Ejecutar el recálculo para una empresa y rango conocidos.
- Confirmar en el log `filas_afectadas`, `feriado_despues` y `flag_despues`.
- Confirmar directamente en MySQL que los valores persisten después de terminar la petición.
- Si siguen regresando a cero, revisar triggers con `SHOW TRIGGERS LIKE 'horarios'`.
- Confirmar que producción tiene actualizado `app/Models/Horario.php`.
- Limpiar caché con `php artisan optimize:clear` y reiniciar PHP/Apache si OPcache conserva código antiguo.

## 3. Pruebas mínimas antes de entregar

- Feriado con turno diurno y descuento de refrigerio.
- Feriado con turno nocturno que cruza medianoche.
- Saldo exacto para cubrir un día.
- Saldo distribuido entre dos o más feriados.
- Saldo insuficiente.
- Horario inexistente.
- Marcación existente.
- Marcación inexistente.
- Segundo intento sobre un feriado parcialmente consumido.
- Verificar que el horario destino no se convierta accidentalmente en estado `F`.

## 4. Git y despliegue

- Revisar el diff completo y separar cambios ajenos o no relacionados.
- Ejecutar sintaxis PHP, build del frontend y las pruebas disponibles.
- Crear un commit con los cambios confirmados.
- Subir la rama al repositorio.
- Desplegar los archivos y assets compilados necesarios.
- Limpiar cachés en producción.
- Hacer una prueba controlada y verificar tanto el log como la BD.

## 5. Documentación pendiente

Documentar como mínimo:

- Significado de `feriado`, `feriado_consumido` y `calculo_manual_feriado`.
- Cómo se calcula la jornada y cuándo se descuenta refrigerio.
- Orden utilizado para consumir feriados.
- Diferencia entre compensar tardanza/salida anticipada y compensar el día completo.
- Flujo con marcación existente y sin marcación.
- Uso de `reporte_he_consumidas` y cómo la descripción identifica un consumo de feriado.
- Procedimiento de recálculo y diagnóstico en producción.
- Archivos principales involucrados:
  - `app/Http/Controllers/MarcacionController.php`
  - `app/Models/Horario.php`
  - `resources/js/pages/marcaciones/create.tsx`
  - `resources/js/pages/marcaciones/edit.tsx`

## Orden recomendado al retomar

1. Verificar recálculo en producción con un registro controlado.
2. Probar día completo con marcación existente.
3. Probar día completo sin marcación existente.
4. Corregir cualquier resultado encontrado.
5. Documentar el flujo definitivo.
6. Revisar diff, crear commit, subir y desplegar.
