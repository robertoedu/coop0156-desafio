<?php

namespace App\Services;

use App\Enums\StatusAnalise;
use App\Models\AnaliseCredito;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;

class AnaliseCreditoService
{
    public function criarPendente(array $dados): AnaliseCredito
    {
        return DB::transaction(function () use ($dados) {
            $cliente = Cliente::firstOrCreate(
                ['cpf' => $dados['cpf']],
                [
                    'nome' => $dados['nome'],
                    'renda_mensal' => $dados['renda_mensal'],
                ],
            );

            return $cliente->analises()->create([
                'cpf' => $dados['cpf'],
                'nome' => $dados['nome'],
                'renda_mensal' => $dados['renda_mensal'],
                'tipo_credito' => $dados['tipo_credito'],
                'valor_solicitado' => $dados['valor_solicitado'],
                'status' => StatusAnalise::PENDENTE,
            ]);
        });
    }
}
