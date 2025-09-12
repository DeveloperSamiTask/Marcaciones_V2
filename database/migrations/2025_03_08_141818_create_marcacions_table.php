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
        Schema::create('marcacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id');
            $table->date('fecha');
            $table->time('ingreso')->nullable();
            $table->time('salida')->nullable();
            $table->time('ingreso_refri')->nullable();
            $table->time('salida_refri')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marcacions');
    }
};
