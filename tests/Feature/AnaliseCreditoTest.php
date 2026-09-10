<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Enums\StatusAnalise;
use App\Models\Cliente;
use App\Services\AnaliseCreditoService;

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

    public function test_rejeita_solicitacao_sem_campos_obrigatorios(): void
    {
        $response = $this->postJson('/api/analise-credito', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nome',
                'cpf',
                'renda_mensal',
                'tipo_credito',
                'valor_solicitado',
            ]);

        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('analises_credito', 0);
    }

    public static function dadosInvalidos(): array
    {
        return [
            'cpf curto' => ['cpf', '1234567890'],
            'cpf longo' => ['cpf', '123456789012'],
            'cpf com letra' => ['cpf', '1234567890a'],
            'cpf com pontuacao' => ['cpf', '123.456.789-01'],
            'tipo de credito desconhecido' => ['tipo_credito', 'empresarial'],
            'renda negativa' => ['renda_mensal', -100],
            'renda nao numerica' => ['renda_mensal', 'abc'],
            'valor solicitado zero' => ['valor_solicitado', 0],
            'valor solicitado negativo' => ['valor_solicitado', -100],
            'valor solicitado nao numerico' => ['valor_solicitado', 'abc'],
        ];
    }

    #[DataProvider('dadosInvalidos')]
    public function test_rejeita_solicitacao_com_dado_invalido(
        string $campo,
        mixed $valor,
    ): void {
        $dados = [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678901',
            'renda_mensal' => 3000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
        ];

        $dados[$campo] = $valor;

        $response = $this->postJson('/api/analise-credito', $dados);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$campo]);

        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('analises_credito', 0);
    }

    public function test_cria_cliente_e_analise_pendente_para_cpf_novo(): void
    {
        $dados = [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678901',
            'renda_mensal' => 3000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
        ];

        $analise = app(AnaliseCreditoService::class)->criarPendente($dados);

        $this->assertSame(StatusAnalise::PENDENTE, $analise->status);
        $this->assertNotNull($analise->cliente_id);

        $this->assertDatabaseHas('clientes', [
            'id' => $analise->cliente_id,
            'nome' => $dados['nome'],
            'cpf' => $dados['cpf'],
            'renda_mensal' => $dados['renda_mensal'],
            'email' => null,
        ]);

        $this->assertDatabaseHas('analises_credito', [
            'id' => $analise->id,
            'cliente_id' => $analise->cliente_id,
            ...$dados,
            'status' => 'pendente',
            'score' => null,
            'taxa_juros' => null,
            'valor_parcela' => null,
            'motivo_rejeicao' => null,
        ]);

        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('analises_credito', 1);
    }

    public function test_reutiliza_cliente_existente_ao_criar_analise_pendente(): void
    {
        $cadastro = [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678901',
            'email' => 'roberto@example.com',
            'renda_mensal' => 3000,
        ];

        $cliente = Cliente::create($cadastro);

        $dados = [
            'nome' => 'Roberto Oliveira Silva',
            'cpf' => $cliente->cpf,
            'renda_mensal' => 4000,
            'tipo_credito' => 'automotivo',
            'valor_solicitado' => 5000,
        ];

        $analise = app(AnaliseCreditoService::class)->criarPendente($dados);

        $this->assertSame($cliente->id, $analise->cliente_id);
        $this->assertSame(StatusAnalise::PENDENTE, $analise->status);

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            ...$cadastro,
        ]);

        $this->assertDatabaseHas('analises_credito', [
            'id' => $analise->id,
            'cliente_id' => $cliente->id,
            ...$dados,
            'status' => 'pendente',
        ]);

        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('analises_credito', 1);
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
