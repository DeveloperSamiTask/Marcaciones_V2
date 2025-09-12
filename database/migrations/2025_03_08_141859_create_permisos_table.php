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
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id');
            $table->foreignId('tipo_id');
            $table->date('fecha');
            $table->time('salida');
            $table->time('llegada');
            $table->time('total');
            $table->text('motivo');
            $table->text('motivo_rechazo')->nullable();
            $table->string('comprobante')->nullable();
            $table->boolean('estado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
