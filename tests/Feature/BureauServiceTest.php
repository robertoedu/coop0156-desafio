<?php

namespace Tests\Feature;


use App\Services\BureauService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use UnexpectedValueException;

class BureauServiceTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_retorna_score_do_bureau(): void
    {
        config([
            'services.score_bureau.url' => 'https://bureau.example/api/',
            'services.score_bureau.timeout' => 3,
        ]);

        Http::preventStrayRequests();

        Http::fake([
            'https://bureau.example/api/12345678903' => Http::response([
                'cpf' => '12345678903',
                'score' => 850,
                'situacao' => 'ativo',
            ], 200),
        ]);

        $score = app(BureauService::class)
            ->consultarScore('12345678903');

        $this->assertSame(850, $score);

        Http::assertSent(function (Request $request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://bureau.example/api/12345678903'
                && $request->hasHeader('Accept', 'application/json');
        });

        Http::assertSentCount(1);
    }

    public function test_lanca_excecao_quando_bureau_retorna_500(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            '*' => Http::response([
                'error' => 'Falha no provedor de score.',
            ], 500),
        ]);

        $this->expectException(RequestException::class);

        app(BureauService::class)->consultarScore('12345678904');
    }

    public function test_lanca_excecao_quando_conexao_com_bureau_falha(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            '*' => Http::failedConnection(),
        ]);

        $this->expectException(ConnectionException::class);

        app(BureauService::class)->consultarScore('12345678905');
    }

    public function test_lanca_excecao_quando_resposta_nao_possui_score(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            '*' => Http::response([
                'cpf' => '12345678906',
                'status_bureau' => 'ok',
            ], 200),
        ]);

        $this->expectException(UnexpectedValueException::class);

        app(BureauService::class)->consultarScore('12345678906');
    }
}
