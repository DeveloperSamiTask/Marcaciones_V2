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
        Schema::create('descuento_extras', function (Blueprint $table) {
            // Relaciones
            $table->id();
           // $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->foreignId('permiso_id')->constrained('permisos')->onDelete('cascade');
            $table->foreignId('marcacion_id')->constrained('marcacions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Datos del descuento
            $table->integer('total_horas_descontadas'); // De 30 en 30
            //$table->integer('total_horas_extras'); // en minutos
            $table->string('motivo')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descuento_extras');
    }
};
