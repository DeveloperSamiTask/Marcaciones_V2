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

            $table->foreignId('marcacion_id')->constrained('marcacions')->onDelete('cascade');

            //$table->foreignId('permiso_id')->constrained('permisos')->onDelete('cascade');

            $table->foreignId('horario_id')->constrained('horarios')->onDelete('cascade');

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->time('hora_modificada');

            $table->time('total_horas_descontadas');

            $table->string('motivo')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descuento_extras');
    }
};
