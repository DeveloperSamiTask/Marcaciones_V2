@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
        {{ config('app.name') }}
        @endcomponent
    @endslot

    @component('mail::panel', ['style' => 'background-color: #f0f9ff; border-left: 4px solid #3b82f6;'])
        # Suspension por negligencia enviada
        Hola {{ $usuario->name }}
        La suspension **{{ $suspension->codigo }}** ha sido **enviada** por {{ $suspension->user->name }} el {{ $suspension->fecha->format('d/m/Y') }}.
    @endcomponent

    @component('mail::button', ['url' => route('suspensiones.index'), 'color' => 'primary'])
        Consultar suspensiones
    @endcomponent

@endcomponent
