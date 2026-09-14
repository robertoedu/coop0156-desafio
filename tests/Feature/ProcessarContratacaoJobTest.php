<?php

namespace Tests\Feature;

use App\Jobs\ProcessarContratacaoJob;
use App\Models\AnaliseCredito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ProcessarContratacaoJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_finaliza_contratacao_e_registra_log(): void
    {
        Log::spy();

        $analise = AnaliseCredito::create([
            'nome' => 'Cliente Teste',
            'cpf' => '01234567893',
            'renda_mensal' => 5000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
            'status' => 'processando_contratacao',
            'score' => 850,
            'taxa_juros' => 2.9,
            'valor_parcela' => 561.67,
        ]);

        $job = new ProcessarContratacaoJob($analise->id);

        $job->handle();

        $this->assertDatabaseHas('analises_credito', [
            'id' => $analise->id,
            'status' => 'contratado',
        ]);

        $this->assertDatabaseCount('analises_credito', 1);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Contratação de crédito concluída.', [
                'analise_id' => $analise->id,
            ]);
    }

    #[DataProvider('statusQueNaoDevemSerProcessados')]
    public function test_preserva_analise_fora_do_status_de_processamento(
        string $status,
    ): void {
        Log::spy();

        $analise = AnaliseCredito::create([
            'nome' => 'Cliente Teste',
            'cpf' => '01234567893',
            'renda_mensal' => 5000,
            'tipo_credito' => 'pessoal',
            'valor_solicitado' => 5000,
            'status' => $status,
        ]);

        $job = new ProcessarContratacaoJob($analise->id);

        $job->handle();

        $this->assertDatabaseHas('analises_credito', [
            'id' => $analise->id,
            'status' => $status,
        ]);

        Log::shouldNotHaveReceived('info');
    }

    public static function statusQueNaoDevemSerProcessados(): array
    {
        return [
            'pendente' => ['pendente'],
            'reprovado' => ['reprovado'],
            'aprovado sem envio para processamento' => ['aprovado'],
            'ja contratado' => ['contratado'],
        ];
    }
}
