<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnaliseCreditoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Teste inicial guiado: Verifica que a rota de solicitação de análise
     * de crédito retorna o status HTTP 501 (Not Implemented) por padrão.
     *
     * O candidato deve adaptar ou reescrever este teste para validar
     * o fluxo correto após implementar a solução.
     */
    public function test_rota_solicitar_analise_retorna_stub_nao_implementado(): void
    {
        $response = $this->postJson('/api/analise-credito', [
            'cpf' => '12345678901',
            'nome' => 'João da Silva',
            'renda_mensal' => 3000.00,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000.00,
        ]);

        $response->assertStatus(501);
    }

    /**
     * DICA PARA O CANDIDATO:
     * Crie aqui testes adicionais para cobrir os fluxos de sucesso e erro:
     *
     * 1. Testar análise de crédito aprovada com score alto (juros de 2.9%).
     * 2. Testar análise de crédito aprovada com score médio (juros de 4.5%).
     * 3. Testar reprovação por renda mensal insuficiente (abaixo de R$ 1.500,00).
     * 4. Testar reprovação por score muito baixo (abaixo de 400).
     * 5. Testar reprovação por comprometimento de renda (parcela > 30% da renda).
     * 6. Testar resiliência caso a API externa do Bureau retorne erro 500.
     * 7. Testar se a rota de contratação dispara o Job `ProcessarContratacaoJob` para a fila.
     *
     * Lembre-se de utilizar \Illuminate\Support\Facades\Http::fake() para simular as chamadas à API do Bureau.
     */
}
