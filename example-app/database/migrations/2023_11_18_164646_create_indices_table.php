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
        Schema::create('indices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livro_id')->constrained('livros');
            $table->unsignedBigInteger('indice_pai_id')->nullable();
            $table->string('titulo');
            $table->integer('pagina');
            $table->timestamps();

            // FK: indice_pai_id que referencia a própria tabela indices
            $table->foreign('indice_pai_id')->references('id')->on('indices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indices');
    }
};
