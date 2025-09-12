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
        Schema::create(' ', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nombres');
            //$table->string('apellidos');
            $table->char('dni', 10);
            $table->date('fecha_movimiento');
            $table->text('motivo')->nullable();
            $table->enum('tipo_movimiento', ['cese', 'reactivacion']);

            $table->unsignedBigInteger("empleados_id");
            $table->foreign('empleados_id')->references('id')->on('empleados')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
