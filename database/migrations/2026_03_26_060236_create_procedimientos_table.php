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
        Schema::create('procedimientos', function (Blueprint $table) {
            $table->id('id_procedimiento');

            // Relación con tipo_procedimiento
            $table->foreignId('id_tipo_procedimiento')
                ->nullable()
                ->constrained('tipo_procedimiento', 'id_tipo_procedimiento')
                ->nullOnDelete();

            // Relación con personas (YA LA TIENES)
            $table->foreignId('id_persona')
                ->nullable()
                ->constrained('personas')
                ->nullOnDelete();

            // Usuario que crea (opcional pero recomendado)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('num_procedimiento')->nullable();
            $table->string('nombre_procedimiento')->nullable();

            $table->date('fecha_publicacion')->nullable();
            $table->date('fecha_vm')->nullable();
            $table->time('hora_vm')->nullable();

            $table->date('fecha_ac')->nullable();
            $table->time('hora_ac')->nullable();

            $table->date('fecha_apertura')->nullable();
            $table->time('hora_apertura')->nullable();

            $table->date('fecha_fallo')->nullable();
            $table->time('hora_fallo')->nullable();

            $table->date('vigencia_contrato')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedimientos');
    }
};
