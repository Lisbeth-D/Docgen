<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_generados', function (Blueprint $table) {
            $table->id();

            /*
             * Usuario que generó el documento.
             */
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
             * Ejemplos:
             * Acta de Apertura
             * Acta de Cierre
             * Dictamen de Fallo
             */
            $table->string('tipo_documento', 150);

            /*
             * Número del procedimiento relacionado.
             */
            $table->string(
                'numero_procedimiento',
                255
            )->nullable();

            /*
             * Nombre con el que se descargará.
             */
            $table->string(
                'nombre_archivo',
                500
            );

            /*
             * Ruta relativa dentro del disco configurado.
             *
             * Ejemplo:
             * documentos/historial/1/archivo.docx
             */
            $table->string(
                'ruta_archivo',
                1000
            );

            /*
             * Disco de Laravel utilizado.
             * Por ahora será local.
             */
            $table->string(
                'disco',
                100
            )->default('local');

            /*
             * Información complementaria del archivo.
             */
            $table->string(
                'tipo_mime',
                255
            )->nullable();

            $table->unsignedBigInteger(
                'tamano_archivo'
            )->nullable();

            /*
             * Fecha en la que deberá eliminarse.
             */
            $table->timestamp(
                'fecha_expiracion'
            );

            $table->timestamps();

            /*
             * Índices para acelerar el historial
             * y la limpieza automática.
             */
            $table->index([
                'user_id',
                'created_at',
            ]);

            $table->index(
                'fecha_expiracion'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'documentos_generados'
        );
    }
};