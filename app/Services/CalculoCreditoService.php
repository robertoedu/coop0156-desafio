<?php

namespace App\Services;

class CalculoCreditoService
{
    public function calcularTotal(float $valorSolicitado, float $taxaMensal): float
    {
        return $valorSolicitado * (1 + $taxaMensal / 100 * 12);
    }
}
