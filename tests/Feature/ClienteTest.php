<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Cliente;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Database\QueryException;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_cliente_com_dados_validos(): void
    {
        $dados = [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678901',
            'email' => 'roberto@example.com',
            'telefone' => '51999999999',
            'renda_mensal' => 3000,
        ];

        $response = $this->postJson('/api/clientes', $dados);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'nome' => $dados['nome'],
                'cpf' => $dados['cpf'],
                'email' => $dados['email'],
                'telefone' => $dados['telefone'],
                'renda_mensal' => '3000.00',
            ])
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('clientes', [
            'id' => $response->json('id'),
            ...$dados,
        ]);

        $this->assertDatabaseCount('clientes', 1);
    }
    public function test_rejeita_cadastro_sem_campos_obrigatorios(): void
    {
        $response = $this->postJson('/api/clientes', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nome',
                'cpf',
                'email',
                'renda_mensal',
            ])
            ->assertJsonMissingValidationErrors(['telefone']);

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_rejeita_cadastro_com_cpf_duplicado(): void
    {
        Cliente::create([
            'nome' => 'Cliente Existente',
            'cpf' => '12345678901',
            'email' => 'existente@example.com',
            'renda_mensal' => 3000,
        ]);

        $response = $this->postJson('/api/clientes', [
            'nome' => 'Novo Cliente',
            'cpf' => '12345678901',
            'email' => 'novo@example.com',
            'renda_mensal' => 4000,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cpf'])
            ->assertJsonMissingValidationErrors(['email']);

        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_rejeita_cadastro_com_email_duplicado(): void
    {
        Cliente::create([
            'nome' => 'Cliente Existente',
            'cpf' => '12345678901',
            'email' => 'existente@example.com',
            'renda_mensal' => 3000,
        ]);

        $response = $this->postJson('/api/clientes', [
            'nome' => 'Novo Cliente',
            'cpf' => '12345678902',
            'email' => 'existente@example.com',
            'renda_mensal' => 4000,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonMissingValidationErrors(['cpf']);

        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_lista_clientes_com_paginacao(): void
    {
        $ids = [];

        for ($i = 1; $i <= 16; $i++) {
            $cliente = Cliente::create([
                'nome' => "Cliente {$i}",
                'cpf' => str_pad((string) $i, 11, '0', STR_PAD_LEFT),
                'email' => "cliente{$i}@example.com",
                'renda_mensal' => 3000,
            ]);

            $ids[] = $cliente->id;
        }

        $primeiraPagina = $this->getJson('/api/clientes');

        $primeiraPagina
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 15)
            ->assertJsonPath('total', 16)
            ->assertJsonPath('last_page', 2);

        $this->assertSame(
            array_slice($ids, 0, 15),
            array_column($primeiraPagina->json('data'), 'id'),
        );

        $segundaPagina = $this->getJson('/api/clientes?page=2');

        $segundaPagina
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('data.0.id', $ids[15]);

        $this->assertDatabaseCount('clientes', 16);
    }

    public function test_exibe_cliente_por_id(): void
    {
        $cliente = Cliente::create([
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678901',
            'email' => 'roberto@example.com',
            'telefone' => '51999999999',
            'renda_mensal' => 3000,
        ]);

        $response = $this->getJson("/api/clientes/{$cliente->id}");

        $response
            ->assertOk()
            ->assertJsonPath('id', $cliente->id)
            ->assertJsonPath('nome', 'Roberto Oliveira')
            ->assertJsonPath('cpf', '12345678901')
            ->assertJsonPath('email', 'roberto@example.com')
            ->assertJsonPath('telefone', '51999999999')
            ->assertJsonPath('renda_mensal', '3000.00');
    }

    public function test_retorna_404_ao_buscar_cliente_inexistente(): void
    {
        $response = $this->getJson('/api/clientes/999');

        $response
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_atualiza_cliente_parcialmente(): void
    {
        $dados = [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678901',
            'email' => 'roberto@example.com',
            'telefone' => '51999999999',
            'renda_mensal' => 3000,
        ];

        $cliente = Cliente::create($dados);

        $response = $this->putJson("/api/clientes/{$cliente->id}", [
            'nome' => 'Roberto Oliveira Silva',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('id', $cliente->id)
            ->assertJsonPath('nome', 'Roberto Oliveira Silva')
            ->assertJsonPath('cpf', $dados['cpf'])
            ->assertJsonPath('email', $dados['email'])
            ->assertJsonPath('telefone', $dados['telefone'])
            ->assertJsonPath('renda_mensal', '3000.00');

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            ...$dados,
            'nome' => 'Roberto Oliveira Silva',
        ]);

        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_permite_manter_cpf_e_email_do_proprio_cliente(): void
    {
        $dados = [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678901',
            'email' => 'roberto@example.com',
            'renda_mensal' => 3000,
        ];

        $cliente = Cliente::create($dados);

        $response = $this->putJson("/api/clientes/{$cliente->id}", [
            'cpf' => $dados['cpf'],
            'email' => $dados['email'],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('id', $cliente->id)
            ->assertJsonPath('cpf', $dados['cpf'])
            ->assertJsonPath('email', $dados['email']);

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            ...$dados,
        ]);

        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_rejeita_atualizacao_com_cpf_de_outro_cliente(): void
    {
        $outroCliente = Cliente::create([
            'nome' => 'Outro Cliente',
            'cpf' => '12345678901',
            'email' => 'outro@example.com',
            'renda_mensal' => 3000,
        ]);

        $cliente = Cliente::create([
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678902',
            'email' => 'roberto@example.com',
            'renda_mensal' => 4000,
        ]);

        $response = $this->putJson("/api/clientes/{$cliente->id}", [
            'cpf' => $outroCliente->cpf,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cpf']);

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'cpf' => '12345678902',
        ]);

        $this->assertDatabaseHas('clientes', [
            'id' => $outroCliente->id,
            'cpf' => '12345678901',
        ]);

        $this->assertDatabaseCount('clientes', 2);
    }

    public function test_rejeita_atualizacao_com_email_de_outro_cliente(): void
    {
        $outroCliente = Cliente::create([
            'nome' => 'Outro Cliente',
            'cpf' => '12345678901',
            'email' => 'outro@example.com',
            'renda_mensal' => 3000,
        ]);

        $cliente = Cliente::create([
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678902',
            'email' => 'roberto@example.com',
            'renda_mensal' => 4000,
        ]);

        $response = $this->putJson("/api/clientes/{$cliente->id}", [
            'email' => $outroCliente->email,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'email' => 'roberto@example.com',
        ]);

        $this->assertDatabaseHas('clientes', [
            'id' => $outroCliente->id,
            'email' => 'outro@example.com',
        ]);

        $this->assertDatabaseCount('clientes', 2);
    }

    public function test_retorna_404_ao_atualizar_cliente_inexistente(): void
    {
        $response = $this->putJson('/api/clientes/999', [
            'nome' => 'Nome Atualizado',
        ]);

        $response
            ->assertNotFound()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_remove_cliente_existente(): void
    {
        $cliente = Cliente::create([
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678901',
            'email' => 'roberto@example.com',
            'renda_mensal' => 3000,
        ]);

        $response = $this->deleteJson("/api/clientes/{$cliente->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('clientes', [
            'id' => $cliente->id,
        ]);

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_retorna_404_ao_remover_cliente_inexistente(): void
    {
        $response = $this->deleteJson('/api/clientes/999');

        $response
            ->assertNotFound()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseCount('clientes', 0);
    }

    public static function dadosInvalidos(): array
    {
        return [
            'cpf curto' => ['cpf', '1234567890'],
            'cpf longo' => ['cpf', '123456789012'],
            'cpf com letra' => ['cpf', '1234567890a'],
            'cpf com pontuacao' => ['cpf', '123.456.789-01'],
            'email invalido' => ['email', 'email-invalido'],
            'renda zero' => ['renda_mensal', 0],
            'renda negativa' => ['renda_mensal', -100],
            'renda nao numerica' => ['renda_mensal', 'abc'],
        ];
    }

    #[DataProvider('dadosInvalidos')]
    public function test_rejeita_cadastro_com_dado_invalido(
        string $campo,
        mixed $valor,
    ): void {
        $dados = [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678901',
            'email' => 'roberto@example.com',
            'renda_mensal' => 3000,
        ];

        $dados[$campo] = $valor;

        $response = $this->postJson('/api/clientes', $dados);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$campo]);

        $this->assertDatabaseCount('clientes', 0);
    }

    #[DataProvider('dadosInvalidos')]
    public function test_rejeita_atualizacao_com_dado_invalido(
        string $campo,
        mixed $valor,
    ): void {
        $dados = [
            'nome' => 'Roberto Oliveira',
            'cpf' => '12345678901',
            'email' => 'roberto@example.com',
            'renda_mensal' => 3000,
        ];

        $cliente = Cliente::create($dados);

        $response = $this->putJson("/api/clientes/{$cliente->id}", [
            $campo => $valor,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$campo]);

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            ...$dados,
        ]);

        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_permite_clientes_sem_email_no_banco(): void
    {
        $primeiro = Cliente::create([
            'nome' => 'Primeiro Cliente',
            'cpf' => '12345678901',
            'renda_mensal' => 3000,
        ]);

        $segundo = Cliente::create([
            'nome' => 'Segundo Cliente',
            'cpf' => '12345678902',
            'renda_mensal' => 4000,
        ]);

        $this->assertDatabaseHas('clientes', [
            'id' => $primeiro->id,
            'email' => null,
        ]);

        $this->assertDatabaseHas('clientes', [
            'id' => $segundo->id,
            'email' => null,
        ]);

        $this->assertDatabaseCount('clientes', 2);
    }

    public function test_banco_rejeita_email_duplicado(): void
    {
        Cliente::create([
            'nome' => 'Primeiro Cliente',
            'cpf' => '12345678901',
            'email' => 'cliente@example.com',
            'renda_mensal' => 3000,
        ]);

        $this->expectException(QueryException::class);

        Cliente::create([
            'nome' => 'Segundo Cliente',
            'cpf' => '12345678902',
            'email' => 'cliente@example.com',
            'renda_mensal' => 4000,
        ]);
    }
}
