<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solicitudes_horas_extras_pt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->string('empleado_area');

            // 🎯 INFORMACIÓN DE DETECCIÓN
            $table->date('fecha_deteccion'); // Cuando el sistema detectó las 93h
            $table->date('fecha_cumplimiento_93h'); // Fecha exacta en que llegó a 93h
            $table->decimal('horas_acumuladas', 8, 2); // 93.50 horas

            // 🎯 PERIODO DE HORAS EXTRAS
            $table->date('fecha_inicio_extras'); // Desde cuándo aplican las horas extras
            $table->date('fecha_fin_extras');    // Hasta cuándo aplican

            // 🎯 FLUJO DE APROBACIÓN
            $table->timestamp('fecha_limite_aprobacion'); // 48h después de detección
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');

            // 🎯 QUIÉN APROBÓ
            $table->string('aprobado_por')->nullable(); // 'GERENCIA' o 'SISTEMA'
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            // 🎯 ÍNDICES PARA BÚSQUEDAS RÁPIDAS
            $table->index(['empleado_id', 'estado']);
            $table->index('fecha_limite_aprobacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_horas_extras_pt');
    }
};
