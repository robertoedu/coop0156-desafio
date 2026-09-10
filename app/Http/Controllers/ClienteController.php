<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;

class ClienteController extends Controller
{
    /**
     * Lista todos os clientes cadastrados.
     *
     * GET /api/clientes
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $clientes = Cliente::orderBy('id')->paginate(15);

        return response()->json($clientes);
    }
    /**
     * Cadastra um novo cliente.
     *
     * POST /api/clientes
     *
     * Validações esperadas:
     *  - nome: obrigatório, string
     *  - cpf: obrigatório, 11 dígitos numéricos, único na tabela clientes
     *  - email: obrigatório, formato e-mail válido, único na tabela clientes
     *  - telefone: opcional, string
     *  - renda_mensal: obrigatório, numérico, mínimo de 0
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreClienteRequest $request)
    {
        $cliente = Cliente::create($request->validated());

        return response()->json($cliente, 201);
    }

    /**
     * Exibe os dados de um cliente específico.
     *
     * GET /api/clientes/{id}
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $cliente = Cliente::findOrFail($id);

        return response()->json($cliente);
    }

    /**
     * Atualiza os dados de um cliente existente.
     *
     * PUT /api/clientes/{id}
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateClienteRequest $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $cliente->update($request->validated());

        return response()->json($cliente);
    }

    /**
     * Remove um cliente do sistema.
     *
     * DELETE /api/clientes/{id}
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // TODO: Remover o cliente (retornar 404 se não encontrado, 204 No Content se removido).
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
