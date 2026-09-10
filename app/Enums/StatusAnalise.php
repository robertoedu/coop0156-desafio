<?php

namespace App\Enums;

enum StatusAnalise: string
{
    case PENDENTE = 'pendente';
    case APROVADO = 'aprovado';
    case REPROVADO = 'reprovado';
    case PROCESSANDO_CONTRATACAO = 'processando_contratacao';
    case CONTRATADO = 'contratado';
}
