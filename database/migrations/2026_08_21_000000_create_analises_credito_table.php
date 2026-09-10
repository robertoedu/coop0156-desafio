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
        Schema::create('analises_credito', function (Blueprint $table) {
            $table->id();
            $table->string('cpf');
            $table->string('nome');
            $table->decimal('renda_mensal', 15, 2);
            $table->string('tipo_credito');
            $table->decimal('valor_solicitado', 15, 2);
            $table->string('status')->default('pendente'); // pendente, aprovado, reprovado, processando_contratacao, contratado
            $table->integer('score')->nullable();
            $table->decimal('taxa_juros', 5, 2)->nullable();
            $table->decimal('valor_parcela', 15, 2)->nullable();
            $table->string('motivo_rejeicao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analises_credito');
    }
};
