<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_adjudicacion', function (Blueprint $table) {
            $table->bigIncrements('id_documento');
            $table->string('nombre', 255);
            $table->text('leyenda');
            $table->unsignedInteger('orden')->default(1);
            $table->boolean('activo')->default(true);
            $table->boolean('obligatorio')->default(false);
            $table->timestamps();

            $table->index(['activo', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_adjudicacion');
    }
};
