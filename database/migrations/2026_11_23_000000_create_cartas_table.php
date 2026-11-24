<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cartas', function (Blueprint $table) {
            $table->id();
            $table->string('remitente', 60);
            $table->unsignedTinyInteger('edad');
            $table->text('ciudad');
            $table->json('regalos');
            $table->string('estado')->index();
            $table->string('comportamiento');
            $table->date('fechaRecepcion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartas');
    }
};
