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
        Schema::create('variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuestionario_id')->constrained('cuestionarios')->onDelete('cascade');
            $table->string('nombre', 100)->unique();
            $table->string('etiqueta', 100);
            $table->string('tipo', 20);
            $table->text('valores');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variables');
    }
};
