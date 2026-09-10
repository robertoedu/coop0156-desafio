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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cpf', 11)->unique();
            $table->string('email')->unique();
            $table->string('telefone', 20)->nullable();
            $table->decimal('renda_mensal', 15, 2);
            $table->timestamps();
        });

        // Adiciona chave estrangeira na tabela de análises
        Schema::table('analises_credito', function (Blueprint $table) {
            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('clientes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analises_credito', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Cliente::class);
        });

        Schema::dropIfExists('clientes');
    }
};
