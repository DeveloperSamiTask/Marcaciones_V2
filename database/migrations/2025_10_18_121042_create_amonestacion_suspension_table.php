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
        Schema::create('amonestacion_suspension', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amonestacion_id')->constrained('suspensions');
            $table->foreignId('suspension_id')->constrained('suspensions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amonestacion_suspension');
    }
};
