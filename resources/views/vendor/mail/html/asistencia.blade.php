@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ config('app.name') }}
@endcomponent
@endslot

@if($asistencia->estado == 0)
@component('mail::panel', ['style' => 'background-color: #f0f9ff; border-left: 4px solid #3b82f6;'])
# 📤 Asistencia Registrada

Hola {{ $asistencia->empleado->nombres }} {{ $asistencia->empleado->apellidos }},

La asistencia **{{ $asistencia->codigo }}** para la fecha **{{ $asistencia->fecha }}** ha sido **enviado** correctamente y está pendiente de revisión.
@endcomponent
@endif

@if($asistencia->estado == 1)
@component('mail::panel', ['style' => 'background-color: #f0fdf4; border-left: 4px solid #10b981;'])
# ✅ Asistencia Autorizada

Hola {{ $asistencia->empleado->nombres }} {{ $asistencia->empleado->apellidos }},

Tu asistencia **{{ $asistencia->codigo }} {{ $asistencia->semana }}** ha sido **autorizada** por el departamento de RRHH.
@endcomponent
@endif

@if($asistencia->estado == 2)
@component('mail::panel', ['style' => 'background-color: #fef2f2; border-left: 4px solid #ef4444;'])
# ❌ Asistencia Rechazada

Hola {{ $asistencia->empleado->nombres }} {{ $asistencia->empleado->apellidos }},

Lamentamos informarte que tu asistencia **{{ $asistencia->codigo }} {{ $asistencia->semana }}** ha sido **rechazada**.

**Motivo:**
{{ $asistencia->motivo ?? 'No se especificó un motivo' }}
@endcomponent
@endif

@component('mail::button', ['url' => route('asistencias.index'), 'color' => 'primary'])
Consultar mis asistencias
@endcomponent

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
@endcomponent
@endslot
@endcomponent
