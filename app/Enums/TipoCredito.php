<?php

namespace App\Enums;

enum TipoCredito: string
{
    case PESSOAL = 'pessoal';
    case IMOBILIARIO = 'imobiliario';
    case AUTOMOTIVO = 'automotivo';
}
