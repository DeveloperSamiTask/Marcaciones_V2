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
        Schema::create('asistencia_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asistencia_id');
            $table->foreignId('empleado_id');
            $table->date('fecha');
            $table->time('ingreso')->nullable();
            $table->time('hora_ingreso')->nullable();
            $table->time('salida')->nullable();
            $table->time('hora_salida')->nullable();
            $table->time('ing_refri')->nullable();
            $table->time('sal_refri')->nullable();
            $table->smallInteger('total')->nullable();
            $table->char('estado', 5)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencia_detalles');
    }
};
