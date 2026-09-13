<?php

namespace App\Services;

use App\Enums\StatusAnalise;
use App\Models\AnaliseCredito;
use App\Models\Cliente;
use App\Exceptions\BureauIndisponivelException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use UnexpectedValueException;

use Illuminate\Support\Facades\DB;

class AnaliseCreditoService
{

    public function __construct(
        private readonly BureauService $bureau,
    ) {}

    public function solicitar(array $dados): AnaliseCredito
    {
        $analise = $this->criarPendente($dados);

        try {
            $score = $this->bureau->consultarScore($analise->cpf);
        } catch (ConnectionException $exception) {
            throw new BureauIndisponivelException(
                $analise->id,
                'Bureau de crédito indisponível no momento.',
                $exception,
            );
        } catch (RequestException $exception) {
            throw new BureauIndisponivelException(
                $analise->id,
                'Não foi possível consultar o Bureau de crédito.',
                $exception,
            );
        } catch (UnexpectedValueException $exception) {
            throw new BureauIndisponivelException(
                $analise->id,
                'O Bureau retornou uma resposta inválida.',
                $exception,
            );
        }

        $analise->update([
            'score' => $score,
        ]);

        if ($analise->renda_mensal < 1500) {
            $analise->update([
                'status' => StatusAnalise::REPROVADO,
                'motivo_rejeicao' => 'Renda mínima insuficiente',
            ]);

            return $analise;
        }

        return $analise;
    }
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
