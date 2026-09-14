# Coop0156 — Análise de Crédito Cooperativo

Implementação do desafio técnico em Laravel: cadastro de clientes pela API, análise de crédito com consulta a um Bureau simulado, apresentação das condições e contratação de análises aprovadas.

O enunciado original foi preservado em [DESAFIO.md](DESAFIO.md).

## Implementado

- CRUD de clientes com paginação, Form Requests, validação, unicidade de CPF/e-mail, atualização parcial e exclusão.
- Cadastro automático ou reutilização do cliente pelo CPF durante a solicitação de crédito.
- Registro da análise pendente vinculada ao cliente antes de consultar o Bureau.
- Consulta via HTTP com URL e timeout configuráveis e tratamento de falhas.
- Regras de renda mínima, score, juros simples e comprometimento de renda.
- JavaScript da tela inicial: envio, resultado, mensagens de erro, máscara de CPF e acesso à simulação.
- Simulação com taxa, total, parcelas e confirmação de contratação.
- Contratação síncrona: somente análises aprovadas passam para `contratado`.
- Testes automatizados das APIs, integração e respostas das páginas de simulação.

O HTML/CSS, as rotas, models, enums e a estrutura inicial de banco vieram do scaffold. A implementação foi feita sobre essa base. O mock do Bureau foi mantido.

## Ambiente utilizado

Windows, PowerShell, PHP 8.5.10, Composer e SQLite, sem Docker.

Optei por usar PHP local devido às limitações de recursos da minha máquina pessoal, que também utilizo para executar outros projetos. Essa abordagem é permitida pelo enunciado e possibilitou desenvolver e testar a aplicação sem o consumo adicional de recursos do Docker. Para este desafio, utilizei uma instalação separada do PHP, selecionada pelo PATH de cada terminal, preservando as instalações utilizadas pelos demais projetos.

Embora o `composer.json` declare PHP `^8.3`, as dependências registradas no `composer.lock` exigem **PHP 8.4.1 ou superior**. PHP 8.2 e 8.3 não atendem ao lock atual. A versão efetivamente validada foi PHP 8.5.10.

Use `composer install` para instalar as versões do lock. Docker/Sail não foi validado.

### Configuração do PHP

Confira o PHP utilizado pelo terminal e pelo Composer:

```powershell
php --version
where.exe php
composer --version
php --ini
php -m
```

Se houver outra versão no PATH, coloque a pasta do PHP escolhido no início dele em cada terminal. Exemplo para uma instalação em `C:\tools\php85`:

```powershell
$env:Path = 'C:\tools\php85;' + $env:Path
```

Substitua o caminho pela sua instalação. Essa mudança vale somente para o terminal atual e é perdida ao fechá-lo.

Na instalação ZIP do PHP para Windows, se não houver `php.ini`, copie `php.ini-development` para `php.ini` na pasta do PHP. Configure `extension_dir` e habilite estas extensões, removendo o `;` das linhas correspondentes quando necessário:

```ini
extension_dir = "C:\tools\php85\ext"
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_sqlite
extension=zip
```

Adapte o caminho de `extension_dir`. Extensões básicas como PDO, DOM, XML e tokenizer também devem estar disponíveis. A verificação do Composer abaixo confere os requisitos das dependências. O driver `pdo_sqlite` permite ao Laravel acessar o SQLite sem instalar um servidor MySQL.

## Instalação local

Execute os comandos na raiz do projeto, pelo PowerShell, com PHP e Composer no PATH.

### 1. Dependências

```powershell
composer check-platform-reqs --lock
composer install
```

Se a verificação falhar, ajuste a versão do PHP ou as extensões. Não é necessário ignorar os requisitos de plataforma.

### 2. Arquivo de ambiente e chave

```powershell
if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
}
php artisan key:generate
```

Gere a chave na instalação inicial; não é necessário gerar outra a cada execução.

O `.env.example` já contém:

```dotenv
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
SCORE_BUREAU_API_URL=http://127.0.0.1:8001/api/mock/bureau
SCORE_BUREAU_TIMEOUT=3
```

Sem definir `DB_DATABASE`, o Laravel utiliza `database/database.sqlite`. Para seguir esse padrão, não configure `DB_URL` nem outro caminho em `DB_DATABASE`.

### 3. Banco SQLite

```powershell
if (-not (Test-Path -LiteralPath 'database/database.sqlite')) {
    New-Item -ItemType File -Path 'database/database.sqlite' | Out-Null
}
php artisan config:clear
php artisan migrate
```

O arquivo SQLite guarda os dados locais. As migrations criam clientes, análises e tabelas auxiliares. Sessões e cache também usam banco na configuração fornecida. Não são necessários usuário, senha ou serviço separado de banco.

### 4. Dois servidores

Abra dois terminais na raiz do mesmo projeto, usando o PHP correto em ambos.

**Terminal da aplicação:**

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal do Bureau simulado:**

```powershell
php artisan serve --host=127.0.0.1 --port=8001
```

Acesse a aplicação em **http://127.0.0.1:8000**. A porta **8001** fica destinada às consultas ao mock.

São usados dois processos porque o servidor PHP local no Windows atende uma requisição por vez. Consultar o Bureau no mesmo servidor ocupado pode causar timeout. Abra o formulário na porta 8000, mesmo que ele também esteja acessível na porta 8001.

As views usam Tailwind e fontes por CDN. O fluxo atual não exige `npm install` nem build do Vite; esses recursos visuais dependem de acesso à internet.

Use `Ctrl+C` em cada terminal para encerrar.

## Teste manual

| Campo | Exemplo |
|---|---|
| Nome | Cliente Teste |
| CPF | `01234567893` |
| Renda mensal | `5000` |
| Tipo de crédito | Pessoal |
| Valor solicitado | `5000` |

O mock retorna score 850 para esse CPF. A análise deve ser aprovada com taxa de 2,9% ao mês, total de R$ 6.740,00 e parcela de R$ 561,67. Abra **Ver condições da simulação** e clique em **Confirmar Contratação**. A resposta deve conter `status: contratado`, e a página deve mostrar o modal de sucesso.

O mock determina a resposta pelo último dígito do CPF:

| Final | Resposta |
|---|---|
| 1 | Score 150 |
| 2 | Score 550 |
| 3 | Score 850 |
| 4 | HTTP 500 |
| 5 | Espera de 5 segundos, excedendo o timeout local de 3 segundos |
| 6 | JSON sem score |
| Demais | Score 600 |

Após testar timeout, aguarde a requisição atrasada terminar antes de tentar novamente. Uma nova solicitação cria outra análise; não retoma a anterior.

## Endpoints

| Método | Rota | Comportamento |
|---|---|---|
| GET | `/api/clientes` | Paginação de 15 registros |
| POST | `/api/clientes` | Cadastro, sucesso `201` |
| GET | `/api/clientes/{id}` | Consulta, `404` se inexistente |
| PUT/PATCH | `/api/clientes/{id}` | Atualização dos campos enviados |
| DELETE | `/api/clientes/{id}` | Exclusão, `204` sem corpo |
| POST | `/api/analise-credito` | Análise, sucesso `201`, inclusive quando reprovada pelas regras |
| POST | `/api/analise-credito/{id}/contratar` | Contratação, sucesso `200` |
| GET | `/simulacao/{id}` | Condições da análise aprovada |

Nas chamadas à API, use `Accept: application/json` e, ao enviar JSON, `Content-Type: application/json`. Dados inválidos retornam `422`. A contratação retorna `404` para análise inexistente e `422` para status diferente de aprovado.

O cadastro recebe `nome`, `cpf`, `email`, `telefone` opcional e `renda_mensal`. A análise recebe `nome`, `cpf`, `renda_mensal`, `tipo_credito` e `valor_solicitado`. Os tipos aceitos são `pessoal`, `imobiliario` e `automotivo`.

## Regras e decisões

### Crédito e arredondamento

- Renda inferior a R$ 1.500: `Renda mínima insuficiente`.
- Score inferior a 400: `Score de crédito muito baixo`.
- Score de 400 a 699: taxa mensal de 4,5%.
- Score a partir de 700: taxa mensal de 2,9%.
- Parcela superior a 30% da renda: `Comprometimento de renda superior a 30%`.

```text
total = valor solicitado × (1 + taxa mensal / 100 × 12)
parcela = arredondar(total / 12, 2)
```

A comparação com 30% utiliza a parcela arredondada para centavos; igualdade permite aprovação. O total exibido utiliza a fórmula antes de arredondar as parcelas, podendo diferir em centavos da soma das 12 parcelas arredondadas.

O CRUD exige renda positiva. A solicitação de análise aceita renda zero na validação e a reprova pela regra de renda mínima.

### E-mail e cadastro automático

O scaffold exigia e-mail no banco, mas o cadastro automático não recebe esse campo. Após esclarecimento com o responsável pelo desafio, que autorizou decidir a abordagem, uma nova migration tornou o e-mail nullable, preservando sua unicidade quando informado.

A API de cadastro de clientes continua exigindo e-mail válido e único. O cadastro automático utiliza `null`, sem inventar um endereço.

Clientes existentes são reutilizados pelo CPF sem sobrescrever o cadastro. A análise guarda o nome e a renda recebidos naquela solicitação. O CRUD permite alterar CPF, respeitando formato e unicidade. A máscara é visual: a API recebe somente dígitos como string, preservando zeros iniciais. Não há validação de dígitos verificadores do CPF, pois o requisito é de formato e unicidade.

### Exclusão e histórico

A criação da análise sempre associa um cliente válido. Foi mantida a migration original: ao excluir o cliente, suas análises permanecem e `cliente_id` fica nulo por `nullOnDelete`. A exclusão retorna `204`; não foi acrescentado bloqueio para clientes com análises.

### Falhas do Bureau

Cliente e análise são criados em uma transação; a consulta HTTP ocorre depois dela. Se não for possível obter o score, a análise permanece pendente.

| Falha | Resposta da aplicação |
|---|---|
| Erro HTTP do Bureau | `502`, mensagem controlada |
| Timeout ou falha de conexão | `503`, mensagem de indisponibilidade |
| Resposta sem score inteiro válido | `502`, mensagem de resposta inválida |

As respostas incluem `analise_id`. O status pendente e os códigos `502`/`503` foram decisões de implementação; o enunciado exige tratamento de falhas, mas não determina esses detalhes. Não há retentativa nem reprocessamento automático.

## Organização e melhorias adicionais

| Componente | Responsabilidade |
|---|---|
| Form Requests | Validação de entrada |
| Controllers | Receber chamadas e produzir respostas |
| `AnaliseCreditoService` | Criação, regras de análise e contratação |
| `BureauService` | Consulta HTTP e verificação do score |
| `CalculoCreditoService` | Fórmula compartilhada do total |
| Models e migrations | Persistência, relacionamentos e estrutura do banco |
| Views e JavaScript | Formulário, resultado, simulação e confirmação |

Os serviços são escolhas de organização para separar responsabilidades. A máscara de CPF, os testes adicionais dos limites e os testes da página de simulação complementam os itens explicitamente listados no desafio. A mensagem de redirecionamento de uma simulação indisponível é exibida na tela inicial.

## Testes automatizados

```powershell
php artisan config:clear
php artisan test
```

Os servidores 8000 e 8001 não precisam estar ligados para os testes: as consultas externas são simuladas com `Http::fake()`.

O `phpunit.xml` configura SQLite em memória (`DB_DATABASE=:memory:`), sem `DB_URL`, sessões e cache em memória e fila síncrona. Os testes com persistência usam `RefreshDatabase`, isolando os cenários do arquivo SQLite usado manualmente.

| Arquivo | Cobertura |
|---|---|
| `ClienteTest` | CRUD, validação, unicidade, paginação e e-mail nullable |
| `AnaliseCreditoTest` | Cadastro automático, regras, limites, falhas do Bureau e contratação |
| `BureauServiceTest` | Consulta HTTP e exceções da integração |
| `SimulacaoTest` | Conteúdo da página, redirecionamento com mensagem e análise inexistente |

`SimulacaoTest` verifica respostas HTML do Laravel; não executa JavaScript no navegador. O fluxo visual até a contratação também foi verificado manualmente.

## O que ficou de fora

- O diferencial de fila ainda não foi implementado. `ProcessarContratacaoJob` permanece como estrutura inicial, sem processamento. Apesar de `QUEUE_CONNECTION=database` no exemplo de ambiente, a contratação não dispara Jobs nem precisa de worker.
- Não há tela separada de CRUD de clientes; as operações são oferecidas pela API, conforme permitido no desafio.
- Não há envio de notificações. O texto original do modal menciona confirmação posterior, mas não foi implementado envio de e-mail ou mensagem.
- Docker/Sail não foi validado. As instruções acima descrevem o ambiente local utilizado.
