# Desafio Técnico: Sistema de Análise de Crédito Cooperativo (Coop0156)

Seja bem-vindo ao desafio técnico para a vaga de desenvolvedor PHP/Laravel. Este desafio foi estruturado para avaliar sua capacidade de lidar com integração de APIs, regras de negócio, organização de código e testes automatizados de forma prática e realista.

---

## 📌 Contexto do Domínio

Você está trabalhando no desenvolvimento da **Coop0156**, uma plataforma interna de uma cooperativa para cadastro de clientes, simulação e contratação de crédito.

O fluxo completo do sistema consiste em:

1. **Cadastrar** um cliente na plataforma (CRUD completo).
2. **Solicitar uma análise de crédito** para um cliente, consultando um Bureau de Crédito externo para obter o Score.
3. **Aplicar regras de elegibilidade** (renda mínima, faixas de score com taxa de juros, comprometimento de renda).
4. **Visualizar a simulação** das condições (parcelas, taxa, valor total) em uma tela dedicada.
5. **Confirmar a contratação** do crédito aprovado.

---

## 🛠️ O Que Foi Entregue (Scaffold)

Para otimizar o seu tempo, as estruturas básicas já estão prontas:

1. **Interface (Frontend):** View Blade pré-estilizada em `resources/views/analise.blade.php` (tela inicial) e `resources/views/simulacao.blade.php` (tela de simulação). O HTML/CSS está pronto — **o candidato implementa o JavaScript**.
2. **Rotas:**
   - `routes/api.php` com as rotas do CRUD de clientes (`apiResource`) e as rotas de análise de crédito.
   - `routes/web.php` com as rotas Web da interface visual (`/` e `/simulacao/{id}`).
3. **Configuração do Bureau:** URL e timeout da API externa configurados em `config/services.php` via variáveis de ambiente no `.env`.
4. **Migrations:**
   - `create_analises_credito_table` — estrutura da tabela de análises.
   - `create_clientes_table` — estrutura da tabela de clientes, já com a chave estrangeira vinculando as análises.
5. **Models:** `Cliente` (com relacionamento `hasMany` de análises) e `AnaliseCredito` (com `belongsTo` de cliente), com Enums `StatusAnalise` e `TipoCredito` mapeados.
6. **Controllers Stub:** `ClienteController` (CRUD completo a implementar) e `AnaliseCreditoController` (análise e contratação a implementar).
7. **Bureau API Mock:** Rota interna `/api/mock/bureau/{cpf}` que simula o Bureau externo — **não alterar**.

---

## 🚀 O Que Você Precisa Implementar

O desafio está dividido em 4 etapas obrigatórias + 1 diferencial:

---

### 1. CRUD de Clientes

Implemente o `ClienteController` com as 5 operações do CRUD de forma completa e com boas práticas:

- **`GET /api/clientes`** — Lista paginada de clientes.
- **`POST /api/clientes`** — Cria um novo cliente com validação dos campos:
  - `nome`: obrigatório
  - `cpf`: obrigatório, exatamente 11 dígitos numéricos, único
  - `email`: obrigatório, formato válido, único
  - `telefone`: opcional
  - `renda_mensal`: obrigatório, numérico positivo
- **`GET /api/clientes/{id}`** — Exibe um cliente (404 se não encontrado).
- **`PUT /api/clientes/{id}`** — Atualiza um cliente com validação.
- **`DELETE /api/clientes/{id}`** — Remove um cliente (204 em sucesso).

**Boas práticas esperadas:** validações com Form Request, retorno de erros claros, código limpo e organizado.

---

### 2. Integração com o Bureau e Regras de Negócio

Implemente o método `solicitar` do `AnaliseCreditoController`:

**Fluxo esperado:**
1. Validar os dados de entrada.
2. **Localizar ou cadastrar o cliente:** buscar pelo CPF informado. Se o cliente não existir, criá-lo automaticamente com os dados recebidos (`nome`, `cpf`, `renda_mensal`). Use `firstOrCreate` ou estratégia equivalente. A análise deve sempre estar vinculada a um `cliente_id` válido.
3. Persistir a análise com status `pendente`, associada ao cliente.
4. Consultar a API do Bureau via `Http::` do Laravel: `GET /api/mock/bureau/{cpf}`.
5. Tratar os possíveis cenários de falha do Bureau (veja a seção de testes abaixo).
6. Aplicar as regras de crédito e atualizar a análise no banco.

> **Por que esse fluxo?** A interface não possui uma tela separada de cadastro de clientes — o foco do desafio está nas boas práticas de API REST e nas regras de negócio. O CRUD de clientes (`ClienteController`) deve ser implementado e testado via testes automatizados, mas o cadastro em si é automatizado durante a solicitação de crédito.

**Regras de crédito a implementar:**

| Condição | Resultado |
|---|---|
| Renda mensal < R$ 1.500,00 | Reprovado — `"Renda mínima insuficiente"` |
| Score < 400 | Reprovado — `"Score de crédito muito baixo"` |
| Score entre 400 e 699 | Aprovado — taxa de **4,5% ao mês** |
| Score ≥ 700 | Aprovado — taxa de **2,9% ao mês** |
| Parcela > 30% da renda mensal | Reprovado — `"Comprometimento de renda superior a 30%"` |

**Cálculo da parcela:**
O crédito é dividido em **12 parcelas fixas**, com juros simples aplicados sobre o valor solicitado.

**Exemplo:** para um valor solicitado de R$ 10.000,00 com taxa de 2,9% ao mês:
- Juros totais: `10.000 × 2,9% × 12 = R$ 3.480,00`
- Valor total a pagar: `10.000 + 3.480 = R$ 13.480,00`
- Parcela: `13.480 / 12 = R$ 1.123,33`

A parcela não pode ultrapassar 30% da renda mensal informada.

> 💡 **Nota:** o cálculo financeiro em si não é o foco de avaliação deste desafio — é apenas a regra de negócio de exemplo do domínio. Pequenas variações de arredondamento ou de fórmula não serão penalizadas, desde que a aplicação das faixas de score, renda mínima e comprometimento de renda esteja correta.

---

### 3. Tela de Simulação e Contratação

A tela de simulação (`/simulacao/{id}`) já está pronta visualmente. O candidato precisa:

- **No frontend (`analise.blade.php`):** ao receber uma resposta **aprovada**, exibir o resultado e um link/botão que direcione o usuário para `/simulacao/{id}`.
- **Na tela de simulação (`simulacao.blade.php`):** implementar o JavaScript do botão **"Confirmar Contratação"**, que deve disparar `POST /api/analise-credito/{id}/contratar`.
- **No backend (`contratar`):** validar que a análise existe e está com status `aprovado`, atualizar para `contratado` e retornar sucesso.

---

### 4. Testes Automatizados

Os testes estão divididos em dois arquivos:

#### `tests/Feature/AnaliseCreditoTest.php`

Complete este arquivo com testes cobrindo:

- Aprovação com score alto (taxa de 2,9%).
- Aprovação com score médio (taxa de 4,5%).
- Reprovação por renda insuficiente.
- Reprovação por score baixo.
- Reprovação por comprometimento de renda.
- Falha da API do Bureau (HTTP 500): a aplicação deve retornar resposta limpa, sem crash.
- Confirmação de contratação (`contratar`) com análise aprovada.
- Criação automática do cliente ao solicitar análise com CPF novo.

Use `Http::fake()` para simular as respostas do Bureau sem chamadas reais de rede.

#### `tests/Feature/ClienteTest.php` _(criar este arquivo)_

Crie e complete este arquivo cobrindo os endpoints do CRUD de clientes:

- Criação de cliente com dados válidos (201).
- Falha de validação ao criar cliente sem campos obrigatórios (422).
- Falha ao criar cliente com CPF duplicado (422).
- Falha ao criar cliente com e-mail duplicado (422).
- Listagem paginada de clientes (200).
- Exibição de cliente existente por ID (200).
- Retorno 404 ao buscar cliente inexistente.
- Atualização parcial de cliente existente (200).
- Remoção de cliente existente (204 sem body).
- Retorno 404 ao tentar remover cliente inexistente.

---

### ⭐ Diferencial Opcional — Filas (Laravel Queues)

Se quiser ir além, ao invés de atualizar o status para `contratado` diretamente no método `contratar`, implemente:

1. Atualize o status para `processando_contratacao` e dispare o `ProcessarContratacaoJob` para a fila.
2. No Job, finalize a contratação: atualize para `contratado` e registre um log de sucesso.
3. Configure `QUEUE_CONNECTION=database` no `.env` e execute `php artisan queue:work` em um terminal separado.

---

### ⭐ Diferencial Opcional — Vá Além

Se sobrar tempo e você quiser mostrar mais do seu repertório, sinta-se à vontade para agregar valor ao projeto além do solicitado — por exemplo, uma tela de cadastro/listagem de clientes, melhorias de UX nas telas existentes, validações extras no frontend, etc. Não é obrigatório e não substitui nenhum dos itens obrigatórios acima, mas é visto como diferencial positivo.

---

## 🧪 Comportamento da API Mock do Bureau

A rota `/api/mock/bureau/{cpf}` responde baseada no **último dígito do CPF** (apenas números):

| Último dígito do CPF | Retorno |
|---|---|
| `1` | Score **150** — útil para testar reprovação por score baixo |
| `2` | Score **550** — útil para testar aprovação com taxa de 4,5% |
| `3` | Score **850** — útil para testar aprovação com taxa de 2,9% |
| `4` | **HTTP 500** — útil para testar resiliência a falha do Bureau |
| `5` | **Delay de 5s** — útil para testar tratamento de timeout |
| `6` | JSON **sem a chave `score`** — útil para testar resposta malformada |
| Qualquer outro | Score **600** padrão |

---

## 🚀 Como Executar o Projeto

Você pode escolher entre duas abordagens abaixo. **A Opção A (Sail) é a recomendada** — é o ambiente usado na avaliação — mas a Opção B também é válida se preferir não usar Docker.

---

### Opção A — Laravel Sail (Docker) — recomendada

> Requisitos: Docker Desktop (ou Docker + Docker Compose) instalado e em execução.
> No Windows, recomenda-se usar o WSL 2 com Docker integrado.
>
> Como o `vendor/` não está versionado no repositório, é necessário instalar as dependências via Docker antes de subir o Sail (que também é instalado via Composer).

```bash
# 1. Instalar dependências via Docker (não requer PHP instalado localmente)
docker run --rm -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs

# 2. Configurar o ambiente
cp .env.example .env

# 3. Subir os containers em background
./vendor/bin/sail up -d

# 4. Gerar a chave da aplicação e rodar as migrations
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate

# Acesse: http://localhost

# 5. Rodar os testes
./vendor/bin/sail artisan test

# ⭐ Opcional: Worker da fila (apenas se implementar o diferencial)
./vendor/bin/sail artisan queue:work

# Para encerrar os containers
./vendor/bin/sail down
```

---

### Opção B — PHP local (sem Docker)

> Requisitos: PHP 8.2+, Composer.
> O projeto já vem configurado com **SQLite** no `.env.example` — nenhuma instalação de banco de dados é necessária.

```bash
# 1. Instalar dependências
composer install

# 2. Configurar o ambiente
cp .env.example .env
php artisan key:generate

# 3. Criar o arquivo de banco SQLite e rodar as migrations
touch database/database.sqlite
php artisan migrate

# 4. Iniciar o servidor
php artisan serve
# Acesse: http://localhost:8000

# 5. Rodar os testes
php artisan test

# ⭐ Opcional: Worker da fila (apenas se implementar o diferencial)
php artisan queue:work
```

---

## 📤 Como Entregar

1. Crie um **repositório público** (ou privado, dando acesso ao avaliador) no seu GitHub/GitLab pessoal com o código do desafio.
2. Faça commits ao longo do desenvolvimento (evite um único commit gigante no final — o histórico de commits também é avaliado).
3. Inclua no repositório um **README próprio** descrevendo o que foi realizado — quais etapas você implementou, o que ficou de fora (se houver) — e, se quiser, suas decisões técnicas e considerações sobre o desenvolvimento.
4. Ao finalizar, envie o **link do repositório** por e-mail para **jonathan_peixoto@sicredi.com.br**, dentro do prazo combinado de uma semana.

## 📬 Dúvidas

Surgiu alguma dúvida durante o desenvolvimento? Entre em contato pelo e-mail **jonathan_peixoto@sicredi.com.br**. Fique à vontade para perguntar sobre qualquer ponto do enunciado que não tenha ficado claro.

---

## 🏆 Critérios de Avaliação

1. **Boas práticas de API REST:** Validações, Form Requests, retorno de erros adequados, uso correto de HTTP status codes.
2. **Organização do código:** Separação de responsabilidades — a lógica de negócio não deve ficar no Controller. Criação de Services ou Actions é recomendada.
3. **Resiliência na integração HTTP:** Tratamento correto de timeouts e erros do Bureau — a aplicação não pode travar ou retornar erro 500 inesperado.
4. **Qualidade dos testes:** Cobertura dos cenários relevantes, uso de `Http::fake()`, edge cases contemplados.
5. **Consistência e clareza:** Nomenclatura consistente, uso consciente de recursos do framework, código limpo, commits descritivos e versionamento.

Boa sorte! Mostre-nos o seu melhor código. 🚀
