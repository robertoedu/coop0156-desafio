# Coop0156 — Análise de Crédito Cooperativo

Implementação do desafio técnico em Laravel para cadastro de clientes, análise de crédito com consulta a um Bureau simulado e contratação de propostas aprovadas.

A solução utiliza o scaffold fornecido. O enunciado original está em [DESAFIO.md](DESAFIO.md).

## Implementado

- CRUD de clientes com paginação, validação, unicidade de CPF/e-mail e atualização parcial.
- Cadastro ou reutilização automática de clientes pelo CPF durante a análise.
- Integração HTTP com o Bureau e tratamento de erro HTTP, timeout, falha de conexão e resposta inválida.
- Regras de renda mínima, score, juros simples e comprometimento de renda.
- Formulário com máscara de CPF, resultado da análise e mensagens de erro.
- Simulação com taxa, total e parcelas, usando as views Blade fornecidas.
- **Diferencial de fila:** contratação assíncrona com banco de dados, Job e log de conclusão.
- Testes automatizados do CRUD, análise, integração, contratação e simulação.

## Decisões técnicas

### Organização

Form Requests concentram as validações; controllers recebem as chamadas e produzem respostas. `AnaliseCreditoService` coordena a análise e a contratação, `BureauService` concentra a integração HTTP e `CalculoCreditoService` compartilha a fórmula entre análise e simulação. O Job finaliza a contratação fora da requisição web.

### Persistência e Bureau

Cliente e análise pendente são criados em uma transação. A consulta ao Bureau ocorre depois, via `Http::`, com URL e timeout definidos em `config/services.php`. O mock fornecido não foi alterado.

Quando o Bureau falha, a análise permanece pendente. A resposta inclui `analise_id` e utiliza `502` para erro HTTP ou score inválido, e `503` para timeout/conexão. Esses códigos e a manutenção do status pendente são decisões de implementação.

Clientes existentes são reutilizados sem sobrescrever seu cadastro. Nome e renda recebidos ficam registrados na análise como dados daquela solicitação.

### E-mail e exclusão de clientes

O scaffold exigia e-mail no banco, mas o cadastro automático solicitado não recebe esse campo. Após esclarecimento com o responsável pelo desafio, uma nova migration tornou o e-mail nullable, mantendo a unicidade quando informado. O cadastro pela API de clientes continua exigindo e-mail válido e único.

Foi preservado o `nullOnDelete` original: excluir um cliente retorna `204`, mantém suas análises e deixa `cliente_id` nulo. Toda análise é vinculada a um cliente no momento da criação.

O CRUD permite alterar CPF, respeitando formato e unicidade. A validação exige 11 dígitos, sem validar dígitos verificadores. A máscara é visual; o envio mantém o CPF como string, preservando zeros iniciais.

### Contratação por fila

O fluxo é `aprovado` → `processando_contratacao` → `contratado`.

A API valida o status, registra o processamento em transação e envia `ProcessarContratacaoJob` com `afterCommit()`. Retorna `202`, indicando que a solicitação foi aceita para processamento.

O Job finaliza somente análises em `processando_contratacao` e registra um log com o ID. Uma segunda solicitação sequencial de contratação é recusada com `422`, sem enviar outra tarefa.

## Como executar

Ambiente validado: **PHP 8.5.10 local, Composer e SQLite**, no Windows, sem Docker.O PHP deve ter suporte a PDO SQLite e atender aos requisitos do Composer.

Execute os comandos na raiz do projeto. O exemplo de preparação abaixo usa PowerShell e preserva arquivos de ambiente e banco já existentes.

### Instalação

```powershell
composer check-platform-reqs --lock
composer install

if (-not (Test-Path '.env')) {
    Copy-Item .env.example .env
}
php artisan key:generate

if (-not (Test-Path 'database/database.sqlite')) {
    New-Item -ItemType File database/database.sqlite | Out-Null
}
php artisan config:clear
php artisan migrate
```

Gere a chave apenas na instalação inicial. Utilize as configurações locais já presentes no `.env.example`:

```dotenv
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
SCORE_BUREAU_API_URL=http://127.0.0.1:8001/api/mock/bureau
SCORE_BUREAU_TIMEOUT=3
```

Sem definir `DB_DATABASE` ou `DB_URL`, o projeto usa `database/database.sqlite`. As migrations criam também as tabelas da fila, sessões e cache; não é necessário instalar um servidor de banco.

### Iniciar a aplicação

Mantenha três terminais abertos na raiz do projeto, usando a mesma versão do PHP e configuração de ambiente.

**Terminal 1 — aplicação:**

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2 — Bureau simulado:**

```powershell
php artisan serve --host=127.0.0.1 --port=8001
```

> **Abra o frontend somente em http://127.0.0.1:8000.** Ambos os processos servem o mesmo projeto e exibem a página, mas a porta 8001 é reservada ao Bureau. Usar o formulário nela faz a aplicação consultar o próprio servidor ocupado e pode causar `503` por timeout. Os dois processos evitam essa espera no PHP local do Windows.

**Terminal 3 — worker:**

```powershell
php artisan queue:work --tries=3 --timeout=30
```

Sem worker, a contratação permanece aguardando processamento. Se alterar o código do Job, reinicie o worker. Use `Ctrl+C` para encerrar os processos.

As views utilizam Tailwind e fontes por CDN: não é necessário build do Vite para este fluxo, mas os recursos visuais dependem de internet.

## Testes

```powershell
php artisan config:clear
php artisan test
```

Os testes usam SQLite em memória e `RefreshDatabase`, sem modificar o banco utilizado manualmente. Não precisam dos servidores nem do worker ligados.

A cobertura inclui:

- CRUD, paginação, validações, unicidade e atualização parcial.
- Cadastro automático, regras financeiras e limites de score/comprometimento.
- Falhas do Bureau, simuladas com `Http::fake()`.
- Envio para fila com `Queue::fake()`, recusa de repetição e execução do Job com verificação de status/log.
- Respostas HTML da simulação, redirecionamento e análise inexistente.

Os testes de páginas não executam JavaScript. O fluxo visual e a execução real do worker também foram exercitados manualmente.

## Fluxo de teste manual

Em **http://127.0.0.1:8000**, informe:

| Campo            | Valor         |
| ---------------- | ------------- |
| Nome             | Cliente Teste |
| CPF              | `01234567893` |
| Renda mensal     | `5000`        |
| Tipo de crédito  | Pessoal       |
| Valor solicitado | `5000`        |

Esse exemplo deve resultar em aprovação com score 850, taxa de 2,9%, total de R$ 6.740,00 e parcela de R$ 561,67.

Abra **Ver condições da simulação** e confirme a contratação. A API retorna `202` e a tela informa processamento. Com o worker ativo, o Job muda o status no banco para `contratado` e registra a conclusão no log. O modal não atualiza automaticamente.

Os demais cenários do mock, incluindo falhas, estão descritos no [enunciado](DESAFIO.md).

## Endpoints

| Método    | Rota                                  | Resultado esperado                      |
| --------- | ------------------------------------- | --------------------------------------- |
| GET       | `/api/clientes`                       | Lista paginada, 15 registros por página |
| POST      | `/api/clientes`                       | Cadastro, `201`                         |
| GET       | `/api/clientes/{id}`                  | Consulta, `200` ou `404`                |
| PUT/PATCH | `/api/clientes/{id}`                  | Atualização parcial, `200`              |
| DELETE    | `/api/clientes/{id}`                  | Exclusão, `204` sem corpo               |
| POST      | `/api/analise-credito`                | Análise aprovada/reprovada, `201`       |
| POST      | `/api/analise-credito/{id}/contratar` | Envio para processamento, `202`         |
| GET       | `/simulacao/{id}`                     | Condições da análise aprovada           |

Use `Accept: application/json` e, para enviar JSON, `Content-Type: application/json`. Validações retornam `422`; registros inexistentes retornam `404`. A contratação exige status aprovado.

O cadastro recebe nome, CPF, e-mail, renda mensal e telefone opcional. A análise recebe nome, CPF, renda mensal, tipo de crédito e valor solicitado. Os tipos aceitos são `pessoal`, `imobiliario` e `automotivo`.

## Regras de crédito

| Condição                        | Resultado                                             |
| ------------------------------- | ----------------------------------------------------- |
| Renda inferior a R$ 1.500       | Reprovação: `Renda mínima insuficiente`               |
| Score inferior a 400            | Reprovação: `Score de crédito muito baixo`            |
| Score de 400 a 699              | Taxa de 4,5% ao mês                                   |
| Score a partir de 700           | Taxa de 2,9% ao mês                                   |
| Parcela superior a 30% da renda | Reprovação: `Comprometimento de renda superior a 30%` |

São 12 parcelas com juros simples:

```text
total = valor solicitado × (1 + taxa mensal / 100 × 12)
parcela = arredondar(total / 12, 2)
```

O limite de 30% é comparado com a parcela arredondada; igualdade permite aprovação. O total exibido é calculado antes do arredondamento das parcelas, podendo diferir em centavos da soma delas.

O CRUD exige renda positiva. Na análise, renda zero passa pela validação de entrada e é reprovada pela regra de renda mínima.

## O que ficou de fora

Nenhuma das etapas obrigatórias de implementação ficou de fora. O diferencial de contratação por fila também foi implementado.

A tela separada de cadastro/listagem de clientes, citada no enunciado como exemplo de melhoria opcional, não foi implementada. O CRUD está disponível pela API, conforme solicitado.
