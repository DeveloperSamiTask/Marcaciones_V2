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
        Schema::create('marcacion_edicions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id');
            $table->foreignId('user_id');
            $table->time('hora');
            $table->date('fecha');
            $table->text('motivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marcacion_edicions');
    }
};
