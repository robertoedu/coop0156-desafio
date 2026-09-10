<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $fillable = [
        'nome',
        'cpf',
        'email',
        'telefone',
        'renda_mensal',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'renda_mensal' => 'decimal:2',
        ];
    }

    /**
     * Um cliente pode ter muitas análises de crédito.
     */
    public function analises(): HasMany
    {
        return $this->hasMany(AnaliseCredito::class);
    }
}
