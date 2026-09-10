<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulação de Crédito — Coop0156</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: {
                        darkBg: '#0b0f19',
                        panelBg: '#131c2e',
                        panelBorder: '#1e2d4a',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #0b0f19;
            background-image:
                radial-gradient(at 20% 20%, hsla(210, 70%, 15%, 0.2) 0px, transparent 50%),
                radial-gradient(at 80% 80%, hsla(142, 70%, 12%, 0.15) 0px, transparent 50%);
        }
        .glass-panel {
            background: rgba(19, 28, 46, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(30, 45, 74, 0.6);
        }
    </style>
</head>
<body class="text-slate-200 min-h-screen flex flex-col font-sans">

    <!-- Header -->
    <header class="border-b border-panelBorder/50 py-5 glass-panel sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 flex justify-between items-center">
            <a href="/" class="flex items-center gap-3 group">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-green-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-green-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight bg-gradient-to-r from-emerald-400 to-green-300 bg-clip-text text-transparent">Coop0156</h1>
                    <p class="text-xs text-slate-400">Desafio Análise de Crédito</p>
                </div>
            </a>
            <a href="/" class="text-sm text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Nova Análise
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow max-w-4xl mx-auto px-4 py-12 w-full">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-slate-500 mb-8">
            <a href="/" class="hover:text-slate-300 transition-colors">Análise</a>
            <span>/</span>
            <span class="text-slate-300">Simulação #{{ $analise->id }}</span>
        </nav>

        <!-- Cabeçalho da Simulação -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-bold text-white">Simulação de Crédito</h2>
                <p class="text-slate-400 mt-1">Revise as condições antes de confirmar a contratação.</p>
            </div>
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Pré-aprovado
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Dados do Proponente -->
            <div class="glass-panel rounded-2xl p-6">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-4">Proponente</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-slate-500">Nome</p>
                        <p class="font-semibold text-slate-100">{{ $analise->nome }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">CPF</p>
                        <p class="font-medium text-slate-200 font-mono">{{ $analise->cpf }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Renda Mensal</p>
                        <p class="font-medium text-slate-200">R$ {{ number_format($analise->renda_mensal, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Tipo de Crédito</p>
                        <p class="font-medium text-slate-200 capitalize">{{ $analise->tipo_credito->value }}</p>
                    </div>
                </div>
            </div>

            <!-- Score e Aprovação -->
            <div class="glass-panel rounded-2xl p-6">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-4">Score de Crédito</h3>
                <div class="flex flex-col items-center justify-center h-32">
                    <p class="text-6xl font-bold bg-gradient-to-b from-emerald-300 to-emerald-500 bg-clip-text text-transparent">
                        {{ $analise->score }}
                    </p>
                    <p class="text-slate-400 text-sm mt-2">Pontuação Obtida</p>
                </div>
                <div class="mt-4 pt-4 border-t border-panelBorder">
                    <p class="text-xs text-slate-500">Taxa de Juros Aplicada</p>
                    <p class="text-xl font-bold text-emerald-400 mt-1">{{ number_format($analise->taxa_juros, 1, ',', '.') }}% a.m.</p>
                </div>
            </div>

            <!-- Condições Financeiras -->
            <div class="glass-panel rounded-2xl p-6">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-4">Condições</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-slate-500">Valor Solicitado</p>
                        <p class="font-semibold text-slate-100 text-lg">R$ {{ number_format($analise->valor_solicitado, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Parcelas</p>
                        <p class="font-medium text-slate-200">12x fixas</p>
                    </div>
                    <div class="pt-3 border-t border-panelBorder">
                        <p class="text-xs text-slate-500">Valor Estimado da Parcela</p>
                        <p class="text-2xl font-bold text-white mt-1">
                            R$ {{ number_format($analise->valor_parcela, 2, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aviso de Comprometimento de Renda -->
        @php
            $comprometimento = ($analise->valor_parcela / $analise->renda_mensal) * 100;
        @endphp
        <div class="glass-panel rounded-2xl p-5 mt-6 flex items-center gap-4">
            <div class="h-10 w-10 rounded-xl bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-slate-200">Comprometimento de renda</p>
                <p class="text-xs text-slate-400 mt-0.5">
                    A parcela representa aproximadamente <span class="text-blue-400 font-semibold">{{ number_format($comprometimento, 1, ',', '.') }}%</span>
                    da sua renda mensal declarada (R$ {{ number_format($analise->renda_mensal, 2, ',', '.') }}).
                </p>
            </div>
        </div>

        <!-- Botão de Contratação -->
        <div class="mt-8 glass-panel rounded-2xl p-8 text-center">

            @if(session('erro'))
                <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6 text-red-400 text-sm">
                    {{ session('erro') }}
                </div>
            @endif

            <h3 class="text-xl font-semibold text-white mb-2">Confirmar Contratação</h3>
            <p class="text-slate-400 text-sm mb-8 max-w-md mx-auto">
                Ao confirmar, você está simulando a solicitação formal de contratação deste crédito. Esta ação não pode ser desfeita.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/" class="px-8 py-3.5 rounded-xl border border-panelBorder text-slate-400 hover:text-slate-200 hover:border-slate-500 transition-all font-medium text-sm">
                    Cancelar
                </a>
                <button id="btn-confirmar"
                    class="px-10 py-3.5 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-indigo-500/20 flex items-center gap-2 justify-center">
                    <span id="txt-confirmar">Confirmar Contratação</span>
                    <svg id="spinner-confirmar" class="animate-spin h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>

    </main>

    <!-- Sucesso Modal -->
    <div id="modal-sucesso" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="glass-panel rounded-3xl p-10 max-w-md w-full mx-4 text-center">
            <div class="h-20 w-20 bg-emerald-500/10 text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">Contratação Realizada!</h3>
            <p class="text-slate-400 text-sm mb-6">O crédito foi contratado com sucesso. Você receberá uma confirmação em breve.</p>
            <div class="bg-emerald-500/5 border border-emerald-500/10 rounded-xl p-3 mb-6 text-xs text-emerald-400 font-mono">
                Status: CONTRATADO
            </div>
            <a href="/" class="inline-block px-8 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 hover:bg-emerald-500/20 rounded-xl text-sm font-medium transition-all">
                Iniciar Nova Simulação
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-panelBorder/40 py-6 text-center text-xs text-slate-600">
        <p>&copy; 2026 Coop0156. Desafio Técnico Laravel.</p>
    </footer>

    <!--
      -- =========================================================================
      -- INSTRUÇÕES (CANDIDATO): Implemente o JavaScript abaixo.
      -- =========================================================================
      -- Ao clicar em "Confirmar Contratação", o candidato deve:
      --   1. Mostrar o spinner e desabilitar o botão para evitar clique duplo.
      --   2. Fazer requisição POST para '/api/analise-credito/{{ $analise->id }}/contratar'.
      --   3. Em caso de sucesso (HTTP 200), exibir o modal de sucesso (#modal-sucesso).
      --   4. Em caso de erro, exibir uma mensagem de feedback adequada para o usuário.
      -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnConfirmar = document.getElementById('btn-confirmar');

            // TODO: Implementar o clique do botão de confirmação.
        });
    </script>

</body>
</html>
