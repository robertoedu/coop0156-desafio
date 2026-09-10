<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Cliente;

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
}
