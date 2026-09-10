<?php

namespace App\Models;

use App\Enums\StatusAnalise;
use App\Enums\TipoCredito;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnaliseCredito extends Model
{
    protected $table = 'analises_credito';

    protected $fillable = [
        'cliente_id',
        'cpf',
        'nome',
        'renda_mensal',
        'tipo_credito',
        'valor_solicitado',
        'status',
        'score',
        'taxa_juros',
        'valor_parcela',
        'motivo_rejeicao',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusAnalise::class,
            'tipo_credito' => TipoCredito::class,
            'renda_mensal' => 'decimal:2',
            'valor_solicitado' => 'decimal:2',
            'taxa_juros' => 'decimal:2',
            'valor_parcela' => 'decimal:2',
            'score' => 'integer',
        ];
    }

    /**
     * A análise pertence a um cliente.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}

