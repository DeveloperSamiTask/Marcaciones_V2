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
        Schema::table('movimientos', function (Blueprint $table) {
            //
            $table->renameColumn('fecha_cese', 'ultima_fecha_cese');
            $table->renameColumn('fecha_activacion', 'ultima_fecha_activacion');

            $table->date('fecha_cese_actual')->nullable();
            $table->date('fecha_activacion_actual')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            //
            $table->renameColumn('ultima_fecha_cese', 'fecha_cese');
            $table->renameColumn('ultima_fecha_activacion', 'fecha_activacion');

            $table->dropColumn(['fecha_cese_actual', 'fecha_activacion_actual']);
        });
    }
};
