<?php

use App\Http\Controllers\AnaliseCreditoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\MockBureauController;
use Illuminate\Support\Facades\Route;

// --- CRUD de Clientes (o candidato implementa a lógica) ---
Route::apiResource('clientes', ClienteController::class);

// --- Análise de Crédito ---
Route::post('/analise-credito', [AnaliseCreditoController::class, 'solicitar']);
Route::post('/analise-credito/{id}/contratar', [AnaliseCreditoController::class, 'contratar']);

// --- Endpoint de Mock (Bureau de Crédito externo simulado — não alterar) ---
Route::get('/mock/bureau/{cpf}', [MockBureauController::class, 'consultar']);
