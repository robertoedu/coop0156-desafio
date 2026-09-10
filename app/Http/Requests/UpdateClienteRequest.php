<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use Illuminate\Validation\Rule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteRequest extends FormRequest
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
        $cliente = Cliente::findOrFail($this->route('cliente'));

        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'cpf' => [
                'sometimes',
                'required',
                'string',
                'digits:11',
                Rule::unique('clientes', 'cpf')->ignore($cliente),
            ],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('clientes', 'email')->ignore($cliente),
            ],
            'telefone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'renda_mensal' => ['sometimes', 'required', 'numeric', 'gt:0'],
        ];
    }
}
