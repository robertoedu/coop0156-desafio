<?php

namespace App\Http\Requests;

use App\Enums\TipoCredito;
use Illuminate\Validation\Rule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SolicitarAnaliseCreditoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'digits:11'],
            'renda_mensal' => ['required', 'numeric', 'min:0'],
            'tipo_credito' => [
                'required',
                'string',
                Rule::enum(TipoCredito::class),
            ],
            'valor_solicitado' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
