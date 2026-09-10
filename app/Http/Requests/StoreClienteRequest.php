<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
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
            'cpf' => ['required', 'string', 'digits:11', 'unique:clientes,cpf'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:clientes,email'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'renda_mensal' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
