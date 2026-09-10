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
}
