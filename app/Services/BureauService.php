<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class BureauService
{
    public function consultarScore(string $cpf): int
    {
        $url = rtrim(config('services.score_bureau.url'), '/');

        $response = Http::acceptJson()
            ->timeout(config('services.score_bureau.timeout'))
            ->get("{$url}/{$cpf}");

        $response->throw();

        $score = $response->json('score');

        if (! is_int($score)) {
            throw new UnexpectedValueException(
                'O Bureau retornou uma resposta sem score inteiro válido.'
            );
        }

        return $score;
    }
}
