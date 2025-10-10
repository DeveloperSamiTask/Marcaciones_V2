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
        Schema::create('suspensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id');
            $table->string('codigo')->nullable();
            $table->string('codigo_asociado')->nullable();
            $table->string('tipo');
            $table->date('fecha')->nullable(); // fecha aplicada
            $table->date('fecha_fin')->nullable();
            $table->time('hora')->nullable();
            $table->boolean('estado')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspensions');
    }
};
