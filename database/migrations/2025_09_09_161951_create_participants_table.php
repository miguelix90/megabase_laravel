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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_unico', 20)->unique();
            $table->integer('grupo')->nullable();
            $table->integer('sexo')->nullable();
            $table->boolean('adoptado')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->boolean('excluido')->nullable();
            $table->string('motivo_exclusion', 500)->nullable();
            $table->string('hash', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
