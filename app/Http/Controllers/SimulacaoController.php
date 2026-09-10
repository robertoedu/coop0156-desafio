<?php

namespace App\Http\Controllers;

use App\Models\AnaliseCredito;
use App\Enums\StatusAnalise;
use Illuminate\Http\Request;

class SimulacaoController extends Controller
{
    /**
     * Exibe a tela de simulação/detalhes de uma análise aprovada.
     *
     * GET /simulacao/{id}
     *
     * @param  int  $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show($id)
    {
        $analise = AnaliseCredito::findOrFail($id);

        // Só exibe a simulação para análises aprovadas
        if ($analise->status !== StatusAnalise::APROVADO) {
            return redirect('/')->with('erro', 'Esta análise não está disponível para simulação.');
        }

        return view('simulacao', compact('analise'));
    }
}
