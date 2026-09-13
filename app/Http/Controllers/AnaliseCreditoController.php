<?php

namespace App\Http\Controllers;

use App\Exceptions\BureauIndisponivelException;
use App\Services\AnaliseCreditoService;
use Illuminate\Http\Client\ConnectionException;

use App\Http\Requests\SolicitarAnaliseCreditoRequest;

class AnaliseCreditoController extends Controller
{
    /**
     * Solicita uma nova análise de crédito.
     *
     * POST /api/analise-credito
     *
     * Campos esperados no body (JSON):
     *  - nome: string, obrigatório
     *  - cpf: string, obrigatório (11 dígitos)
     *  - renda_mensal: numeric, obrigatório
     *  - tipo_credito: string, obrigatório (pessoal | imobiliario | automotivo)
     *  - valor_solicitado: numeric, obrigatório
     *
     * Fluxo esperado:
     *  1. Validar os dados de entrada.
     *  2. Persistir a análise no banco com status 'pendente'.
     *  3. Consultar a API do Bureau de Crédito (GET /api/mock/bureau/{cpf}) via Http::.
     *  4. Tratar falhas de comunicação com o Bureau (timeout, HTTP 500, resposta malformada).
     *  5. Aplicar as regras de negócio (renda mínima, faixas de score, comprometimento de renda).
     *  6. Atualizar e retornar a análise persistida com o resultado final.
     *
     * @param  \App\Http\Requests\SolicitarAnaliseCreditoRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function solicitar(
        SolicitarAnaliseCreditoRequest $request,
        AnaliseCreditoService $service,
    ) {
        try {
            $analise = $service->solicitar($request->validated());
        } catch (BureauIndisponivelException $exception) {
            $statusHttp = $exception->getPrevious() instanceof ConnectionException
                ? 503
                : 502;

            return response()->json([
                'message' => $exception->getMessage(),
                'analise_id' => $exception->analiseId,
            ], $statusHttp);
        }

        return response()->json($analise, 201);
    }

    /**
     * Confirma a contratação de uma análise de crédito aprovada.
     *
     * POST /api/analise-credito/{id}/contratar
     *
     * Fluxo esperado:
     *  1. Buscar a análise pelo ID (retornar 404 se não encontrada).
     *  2. Verificar se o status é 'aprovado' (retornar 422 se não for).
     *  3. Atualizar o status para 'contratado'.
     *  4. Retornar confirmação de sucesso.
     *
     * ⭐ DIFERENCIAL OPCIONAL: Em vez de atualizar diretamente para 'contratado',
     *    atualize para 'processando_contratacao' e dispare o Job ProcessarContratacaoJob
     *    para a fila. O Job ficará responsável por finalizar e atualizar para 'contratado'.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function contratar($id)
    {
        // TODO: Implementar validação da análise e confirmação da contratação.
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
