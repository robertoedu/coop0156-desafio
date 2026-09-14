<?php

namespace Tests\Feature;

use App\Models\AnaliseCredito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_exibe_condicoes_e_total_da_analise_aprovada(): void
    {
        $analise = $this->criarAnalise('aprovado');

        $this->get("/simulacao/{$analise->id}")
            ->assertOk()
            ->assertViewIs('simulacao')
            ->assertSee('6.740,00')
            ->assertSee('561,67')
            ->assertSee('Confirmar Contratação');
    }

    public function test_exibe_motivo_do_redirecionamento_na_tela_inicial(): void
    {
        $analise = $this->criarAnalise('reprovado');
        $mensagem = 'Esta análise não está disponível para simulação.';

        $this->get("/simulacao/{$analise->id}")
            ->assertRedirect('/')
            ->assertSessionHas('erro', $mensagem);

        $this->get('/')
            ->assertOk()
            ->assertSee($mensagem);
    }

    public function test_retorna_404_para_simulacao_inexistente(): void
    {
        $this->get('/simulacao/999')->assertNotFound();
    }

    private function criarAnalise(string $status): AnaliseCredito
    {
        return AnaliseCredito::create([
            'nome' => 'Cliente Teste',
            'cpf' => '01234567893',
            'renda_mensal' => 5000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
            'status' => $status,
            'score' => 850,
            'taxa_juros' => 2.9,
            'valor_parcela' => 561.67,
        ]);
    }
}
