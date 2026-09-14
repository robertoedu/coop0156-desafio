<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Enums\StatusAnalise;
use App\Models\Cliente;
use App\Services\AnaliseCreditoService;
use App\Exceptions\BureauIndisponivelException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use App\Models\AnaliseCredito;
use App\Jobs\ProcessarContratacaoJob;
use Illuminate\Support\Facades\Queue;
use UnexpectedValueException;

class AnaliseCreditoTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_preserva_analise_pendente_quando_bureau_retorna_500(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            '*' => Http::response([
                'error' => 'Falha no provedor de score.',
            ], 500),
        ]);

        $dados = [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678904',
            'renda_mensal' => 3000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
        ];

        try {
            app(AnaliseCreditoService::class)->solicitar($dados);

            $this->fail('Era esperada uma falha na consulta ao Bureau.');
        } catch (BureauIndisponivelException $exception) {
            $this->assertInstanceOf(
                RequestException::class,
                $exception->getPrevious(),
            );

            $cliente = Cliente::where('cpf', $dados['cpf'])->firstOrFail();

            $this->assertDatabaseHas('analises_credito', [
                'id' => $exception->analiseId,
                'cliente_id' => $cliente->id,
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

        Http::assertSentCount(1);
    }

    public function test_persiste_score_recebido_do_bureau(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            '*' => Http::response([
                'cpf' => '12345678903',
                'score' => 850,
            ], 200),
        ]);

        $dados = [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678903',
            'renda_mensal' => 5000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
        ];

        $analise = app(AnaliseCreditoService::class)->solicitar($dados);

        $this->assertSame(850, $analise->score);

        $this->assertDatabaseHas('clientes', [
            'id' => $analise->cliente_id,
            'cpf' => $dados['cpf'],
            'email' => null,
        ]);

        $this->assertDatabaseHas('analises_credito', [
            'id' => $analise->id,
            'cliente_id' => $analise->cliente_id,
            ...$dados,
            'score' => 850,
        ]);

        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('analises_credito', 1);

        Http::assertSentCount(1);
    }

    public static function falhasDoBureau(): array
    {
        return [
            'falha de conexao' => [
                'conexao',
                ConnectionException::class,
            ],
            'resposta sem score' => [
                'sem_score',
                UnexpectedValueException::class,
            ],
        ];
    }

    #[DataProvider('falhasDoBureau')]
    public function test_preserva_analise_pendente_nas_demais_falhas_do_bureau(
        string $cenario,
        string $excecaoEsperada,
    ): void {
        Http::preventStrayRequests();

        if ($cenario === 'conexao') {
            Http::fake([
                '*' => Http::failedConnection(),
            ]);
        } else {
            Http::fake([
                '*' => Http::response([
                    'status_bureau' => 'ok',
                ], 200),
            ]);
        }

        $dados = [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678905',
            'renda_mensal' => 3000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
        ];

        try {
            app(AnaliseCreditoService::class)->solicitar($dados);

            $this->fail('Era esperada uma falha na consulta ao Bureau.');
        } catch (BureauIndisponivelException $exception) {
            $this->assertInstanceOf(
                $excecaoEsperada,
                $exception->getPrevious(),
            );

            $cliente = Cliente::where('cpf', $dados['cpf'])->firstOrFail();

            $this->assertDatabaseHas('analises_credito', [
                'id' => $exception->analiseId,
                'cliente_id' => $cliente->id,
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
    }

    public function test_solicita_analise_pela_api(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            '*' => Http::response([
                'score' => 850,
            ], 200),
        ]);

        $response = $this->postJson('/api/analise-credito', [
            'cpf' => '12345678903',
            'nome' => 'João da Silva',
            'renda_mensal' => 5000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('score', 850)
            ->assertJsonStructure(['id', 'cliente_id']);

        $cliente = Cliente::where('cpf', '12345678903')->firstOrFail();

        $response->assertJsonPath('cliente_id', $cliente->id);

        $this->assertDatabaseHas('analises_credito', [
            'id' => $response->json('id'),
            'cliente_id' => $cliente->id,
            'score' => 850,
        ]);

        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('analises_credito', 1);

        Http::assertSentCount(1);
    }

    public static function falhasDoBureauNaApi(): array
    {
        return [
            'erro HTTP' => [
                'http',
                502,
                'Não foi possível consultar o Bureau de crédito.',
            ],
            'falha de conexao' => [
                'conexao',
                503,
                'Bureau de crédito indisponível no momento.',
            ],
            'resposta sem score' => [
                'sem_score',
                502,
                'O Bureau retornou uma resposta inválida.',
            ],
        ];
    }

    #[DataProvider('falhasDoBureauNaApi')]
    public function test_retorna_erro_controlado_quando_bureau_falha(
        string $cenario,
        int $statusEsperado,
        string $mensagemEsperada,
    ): void {
        Http::preventStrayRequests();

        $respostaBureau = match ($cenario) {
            'http' => Http::response(['error' => 'Falha interna'], 500),
            'conexao' => Http::failedConnection(),
            'sem_score' => Http::response(['status_bureau' => 'ok'], 200),
        };

        Http::fake([
            '*' => $respostaBureau,
        ]);

        $dados = [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678904',
            'renda_mensal' => 3000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
        ];

        $response = $this->postJson('/api/analise-credito', $dados);

        $response
            ->assertStatus($statusEsperado)
            ->assertJsonPath('message', $mensagemEsperada)
            ->assertJsonStructure(['analise_id']);

        $this->assertIsInt($response->json('analise_id'));

        $cliente = Cliente::where('cpf', $dados['cpf'])->firstOrFail();

        $this->assertDatabaseHas('analises_credito', [
            'id' => $response->json('analise_id'),
            'cliente_id' => $cliente->id,
            ...$dados,
            'status' => 'pendente',
            'score' => null,
            'taxa_juros' => null,
            'valor_parcela' => null,
            'motivo_rejeicao' => null,
        ]);

        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('analises_credito', 1);

        Http::assertSentCount(1);
    }

    public function test_reprova_analise_por_renda_insuficiente(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            '*' => Http::response(['score' => 850], 200),
        ]);

        $response = $this->postJson('/api/analise-credito', [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678903',
            'renda_mensal' => 1499.99,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 1000,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'reprovado')
            ->assertJsonPath('motivo_rejeicao', 'Renda mínima insuficiente');

        $this->assertDatabaseHas('analises_credito', [
            'id' => $response->json('id'),
            'status' => 'reprovado',
            'score' => 850,
            'motivo_rejeicao' => 'Renda mínima insuficiente',
            'taxa_juros' => null,
            'valor_parcela' => null,
        ]);

        Http::assertSentCount(1);
    }

    public function test_reprova_analise_por_score_baixo(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            '*' => Http::response(['score' => 399], 200),
        ]);

        $response = $this->postJson('/api/analise-credito', [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678901',
            'renda_mensal' => 5000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 1000,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'reprovado')
            ->assertJsonPath('score', 399)
            ->assertJsonPath(
                'motivo_rejeicao',
                'Score de crédito muito baixo'
            );

        $this->assertDatabaseHas('analises_credito', [
            'id' => $response->json('id'),
            'status' => 'reprovado',
            'score' => 399,
            'motivo_rejeicao' => 'Score de crédito muito baixo',
            'taxa_juros' => null,
            'valor_parcela' => null,
        ]);

        Http::assertSentCount(1);
    }

    public static function faixasDeScore(): array
    {
        return [
            'inicio da faixa media' => [400, '4.50', '641.67'],
            'fim da faixa media' => [699, '4.50', '641.67'],
            'inicio da faixa alta' => [700, '2.90', '561.67'],
            'score alto' => [850, '2.90', '561.67'],
        ];
    }

    #[DataProvider('faixasDeScore')]
    public function test_calcula_taxa_e_parcela_por_score(
        int $score,
        string $taxaEsperada,
        string $parcelaEsperada,
    ): void {
        Http::preventStrayRequests();

        Http::fake([
            '*' => Http::response(['score' => $score], 200),
        ]);

        $response = $this->postJson('/api/analise-credito', [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678903',
            'renda_mensal' => 5000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'aprovado')
            ->assertJsonPath('motivo_rejeicao', null)
            ->assertJsonPath('taxa_juros', $taxaEsperada)
            ->assertJsonPath('valor_parcela', $parcelaEsperada);

        $this->assertDatabaseHas('analises_credito', [
            'id' => $response->json('id'),
            'score' => $score,
            'taxa_juros' => $taxaEsperada,
            'valor_parcela' => $parcelaEsperada,
            'status' => 'aprovado',
            'motivo_rejeicao' => null,
        ]);
    }

    public static function limitesDeComprometimento(): array
    {
        return [
            'abaixo do limite' => [
                4675.20,
                '599.98',
                'aprovado',
                null,
            ],
            'no limite' => [
                4675.32,
                '600.00',
                'aprovado',
                null,
            ],
            'acima do limite' => [
                4675.44,
                '600.01',
                'reprovado',
                'Comprometimento de renda superior a 30%',
            ],
        ];
    }

    #[DataProvider('limitesDeComprometimento')]
    public function test_decide_analise_pelo_comprometimento_de_renda(
        float $valorSolicitado,
        string $parcelaEsperada,
        string $statusEsperado,
        ?string $motivoEsperado,
    ): void {
        Http::preventStrayRequests();

        Http::fake([
            '*' => Http::response(['score' => 550], 200),
        ]);

        $response = $this->postJson('/api/analise-credito', [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678902',
            'renda_mensal' => 2000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => $valorSolicitado,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('status', $statusEsperado)
            ->assertJsonPath('valor_parcela', $parcelaEsperada)
            ->assertJsonPath('motivo_rejeicao', $motivoEsperado);

        $this->assertDatabaseHas('analises_credito', [
            'id' => $response->json('id'),
            'status' => $statusEsperado,
            'taxa_juros' => '4.50',
            'valor_parcela' => $parcelaEsperada,
            'motivo_rejeicao' => $motivoEsperado,
        ]);
    }

    public function test_envia_contratacao_aprovada_para_fila(): void
    {
        Queue::fake();

        Http::fake([
            '*' => Http::response(['score' => 850], 200),
        ]);

        $solicitacao = $this->postJson('/api/analise-credito', [
            'nome' => 'Cliente Teste',
            'cpf' => '01234567893',
            'renda_mensal' => 5000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
        ]);

        $solicitacao
            ->assertCreated()
            ->assertJsonPath('status', 'aprovado');

        $id = $solicitacao->json('id');

        $response = $this->postJson(
            "/api/analise-credito/{$id}/contratar"
        );

        $response
            ->assertStatus(202)
            ->assertJsonPath('analise.id', $id)
            ->assertJsonPath(
                'analise.status',
                'processando_contratacao'
            );

        $this->assertDatabaseHas('analises_credito', [
            'id' => $id,
            'status' => 'processando_contratacao',
        ]);

        Queue::assertPushed(
            ProcessarContratacaoJob::class,
            fn(ProcessarContratacaoJob $job) => $job->analiseId === $id
        );

        Queue::assertPushed(ProcessarContratacaoJob::class, 1);
    }

    public function test_retorna_404_ao_contratar_analise_inexistente(): void
    {
        $response = $this->postJson(
            '/api/analise-credito/999/contratar'
        );

        $response->assertNotFound();

        $this->assertDatabaseCount('analises_credito', 0);
    }

    #[DataProvider('statusQueNaoPermitemContratacao')]
    public function test_rejeita_contratacao_de_analise_nao_aprovada(
        string $status,
    ): void {
        $analise = AnaliseCredito::create([
            'nome' => 'Cliente Teste',
            'cpf' => '01234567893',
            'renda_mensal' => 5000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
            'status' => $status,
        ]);

        $response = $this->postJson(
            "/api/analise-credito/{$analise->id}/contratar"
        );

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Somente análises aprovadas podem ser contratadas.'
            );

        $this->assertDatabaseHas('analises_credito', [
            'id' => $analise->id,
            'status' => $status,
        ]);

        $this->assertDatabaseCount('analises_credito', 1);
    }

    public static function statusQueNaoPermitemContratacao(): array
    {
        return [
            'pendente' => ['pendente'],
            'reprovado' => ['reprovado'],
            'processando contratacao' => ['processando_contratacao'],
            'ja contratado' => ['contratado'],
        ];
    }

    public function test_nao_envia_outro_job_ao_repetir_contratacao(): void
    {
        Queue::fake();

        $analise = AnaliseCredito::create([
            'nome' => 'Cliente Teste',
            'cpf' => '01234567893',
            'renda_mensal' => 5000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
            'status' => 'aprovado',
            'score' => 850,
            'taxa_juros' => 2.9,
            'valor_parcela' => 561.67,
        ]);

        $url = "/api/analise-credito/{$analise->id}/contratar";

        $this->postJson($url)->assertStatus(202);

        $this->postJson($url)->assertUnprocessable();

        $this->assertDatabaseHas('analises_credito', [
            'id' => $analise->id,
            'status' => 'processando_contratacao',
        ]);

        Queue::assertPushed(ProcessarContratacaoJob::class, 1);
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
