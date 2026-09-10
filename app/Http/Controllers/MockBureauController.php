<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MockBureauController extends Controller
{
    /**
     * Consulta o score de crédito de um CPF (Mock).
     *
     * @param  string  $cpf
     * @return \Illuminate\Http\JsonResponse
     */
    public function consultar($cpf)
    {
        // Limpa caracteres especiais do CPF
        $cpfLimpo = preg_replace('/\D/', '', $cpf);
        
        if (empty($cpfLimpo)) {
            return response()->json(['error' => 'CPF inválido ou vazio.'], 400);
        }
        
        $ultimoDigito = substr($cpfLimpo, -1);

        switch ($ultimoDigito) {
            case '1':
                // Score baixo -> Reprovação automática
                return response()->json([
                    'cpf' => $cpfLimpo,
                    'score' => 150,
                    'situacao' => 'ativo',
                ]);

            case '2':
                // Score médio -> Taxa de 4.5%
                return response()->json([
                    'cpf' => $cpfLimpo,
                    'score' => 550,
                    'situacao' => 'ativo',
                ]);

            case '3':
                // Score alto -> Taxa de 2.9%
                return response()->json([
                    'cpf' => $cpfLimpo,
                    'score' => 850,
                    'situacao' => 'ativo',
                ]);

            case '4':
                // Erro interno do servidor (500)
                return response()->json([
                    'error' => 'Erro interno na comunicação com o provedor de score.',
                ], 500);

            case '5':
                // Simula Timeout / Latência alta
                sleep(5);
                return response()->json([
                    'cpf' => $cpfLimpo,
                    'score' => 720,
                    'situacao' => 'ativo',
                ]);

            case '6':
                // Resposta malformada (sem a chave 'score')
                return response()->json([
                    'cpf' => $cpfLimpo,
                    'status_bureau' => 'ok',
                ]);

            default:
                // Retorno genérico
                return response()->json([
                    'cpf' => $cpfLimpo,
                    'score' => 600,
                    'situacao' => 'ativo',
                ]);
        }
    }
}
