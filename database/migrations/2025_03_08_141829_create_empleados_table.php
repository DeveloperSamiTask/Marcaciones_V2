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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jefe_id')->nullable();
            $table->foreignId('empresa_id');
            $table->foreignId('area_id');
            $table->foreignId('jornada_id');
            $table->char('dni', 10);
            $table->string('nombres');
            $table->string('apellidos');
            $table->char('sexo', 1);
            $table->date('fecha_nacimiento');
            $table->string('domicilio')->nullable();
            $table->string('peso')->nullable();
            $table->string('talla')->nullable();
            $table->string('cargo')->nullable();
            $table->smallInteger('horas');
            $table->date('fecha_ingreso');
            $table->date('fecha_cese')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
