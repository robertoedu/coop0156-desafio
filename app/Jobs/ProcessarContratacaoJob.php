<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * ⭐ DIFERENCIAL OPCIONAL — ProcessarContratacaoJob
 *
 * Este Job é um diferencial do desafio. Sua implementação NÃO é obrigatória,
 * mas demonstra conhecimento em processamento assíncrono com Laravel Queues.
 *
 * Para utilizá-lo:
 *  - No método `contratar` do AnaliseCreditoController, em vez de atualizar o
 *    status diretamente para 'contratado', atualize para 'processando_contratacao'
 *    e dispare este Job: ProcessarContratacaoJob::dispatch($analiseId);
 *  - Configure a fila no .env: QUEUE_CONNECTION=database
 *  - Execute o worker: php artisan queue:work
 */
class ProcessarContratacaoJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $analiseId)
    {
        //
    }

    /**
     * Execute the job.
     *
     * TODO (Diferencial): Buscar a AnaliseCredito pelo $analiseId,
     * atualizar o status para 'contratado' e registrar um log de sucesso.
     */
    public function handle(): void
    {
        //
    }
}
