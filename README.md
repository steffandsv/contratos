# ESPECIFICAÇÃO FINAL – SISTEMA DE GESTÃO DE CONTRATOS + PWA (PHP + MySQL)

## 1. Objetivo

Sistema web simples, em PHP + MySQL (index.php na raiz), com PWA, para:

- Centralizar contratos da prefeitura.
- Monitorar vencimentos e prorrogações.
- Notificar fiscais/gestores com antecedência.
- Oferecer um painel (“painel de carro”) com poucas informações, mas decisivas.

---

## 2. Escopo da V1

- Autenticação de usuários + perfis de acesso.
- Importação de contratos via CSV (como o modelo enviado).
- Cadastro e edição de contratos via CRUD.
- Dashboard para:
  - Ver ações urgentes (“minha fila”).
  - Ver indicadores básicos.
- Lista de contratos com filtros simples.
- Tela de detalhe do contrato com ações principais.
- Sistema de notificações:
  - Registro interno (tabela `notifications`).
  - Envio de e-mails (texto simples, responsivo).
- PWA básico:
  - `manifest.json`.
  - `service-worker.js`.
  - Aplicativo instalável no celular.

---

## 3. Estrutura de Pastas (Hospedagem Compartilhada)

Na pasta raiz do domínio:

- `/index.php` – front controller, roteia tudo.
- `/config/`
  - `config.php` – dados de conexão com MySQL, timezone, e-mail remetente etc.
- `/controllers/`
  - `AuthController.php`
  - `DashboardController.php`
  - `ContractController.php`
  - `UserController.php`
  - `ImportController.php`
  - `NotificationController.php`
- `/models/`
  - `User.php`
  - `Contract.php`
  - `Supplier.php`
  - `Notification.php`
  - `Setting.php`
  - `Log.php`
- `/views/`
  - `layout.php` (layout base)
  - `auth/login.php`
  - `dashboard/index.php`
  - `contracts/list.php`
  - `contracts/view.php`
  - `contracts/form.php`
  - `import/index.php`
  - `users/list.php`
  - `users/form.php`
- `/public/`
  - `css/` (CSS, pode usar Bootstrap)
  - `js/`
  - `img/`
  - `manifest.json`
  - `service-worker.js`
- `/cron/`
  - `cron_notifications.php` (executado via CRON do servidor)

---

## 4. Modelo de Dados (MySQL)

### 4.1. Tabela `users`

Perfis de acesso simples.

| Campo         | Tipo         | Observação                                         |
| ------------- | ------------ | -------------------------------------------------- |
| id            | INT PK AI    |                                                    |
| name          | VARCHAR(150) | Nome completo                                      |
| email         | VARCHAR(150) | Único                                              |
| password_hash | VARCHAR(255) | `password_hash()` PHP                              |
| role          | ENUM         | `'ADMIN','GESTOR_CENTRAL','FISCAL','VISUALIZADOR'` |
| is_active     | TINYINT(1)   | 1 = ativo, 0 = inativo                             |
| created_at    | DATETIME     |                                                    |
| updated_at    | DATETIME     |                                                    |

### 4.2. Tabela `suppliers`

Fornecedores do CSV.

| Campo      | Tipo         | Observação                       |
| ---------- | ------------ | -------------------------------- |
| id         | INT PK AI    |                                  |
| document   | VARCHAR(20)  | CPF/CNPJ limpo (somente números) |
| name       | VARCHAR(255) | Nome social/razão                |
| created_at | DATETIME     |                                  |
| updated_at | DATETIME     |                                  |

### 4.3. Tabela `contracts`

Tabela principal de contratos.

#### Campos principais

| Campo                | Tipo              | Observação                                                                                          |
| -------------------- | ----------------- | --------------------------------------------------------------------------------------------------- |
| id                   | INT PK AI         |                                                                                                     |
| number               | VARCHAR(50)       | `N° Contrato` (ex: `2012/25`)                                                                       |
| detailed_number      | VARCHAR(50)       | `Nº Detalhado do Contrato` (ex: `118`)                                                              |
| modality_code        | VARCHAR(50)       | `N° Modalidade` (ex: `0009/25`)                                                                     |
| modality_name        | VARCHAR(100)      | `Modalidade` (ex: `DISPENSA`)                                                                       |
| fiscal_name_raw      | VARCHAR(255)      | Nome do fiscal do CSV (`Fiscal`) – string simples                                                   |
| exercise             | INT               | `Exercício` (ex: 2025)                                                                              |
| legal_basis          | VARCHAR(255)      | `Fundamento Legal` (texto curto)                                                                    |
| procedure_number     | VARCHAR(100)      | `Proc. Licitatório`                                                                                 |
| supplier_id          | INT FK suppliers  | Ligação com a tabela `suppliers`                                                                    |
| value_total          | DECIMAL(15,2)     | `Valor` (converter vírgula para ponto)                                                              |
| date_start           | DATE              | `Vigência Inicial` (dd/mm/yyyy → DATE)                                                              |
| date_end_current     | DATE              | `Vencimento Atual`                                                                                  |
| description_short    | VARCHAR(255)      | resumo curto do objeto                                                                              |
| description_full     | TEXT              | `Objeto` completo                                                                                   |
| type_code            | VARCHAR(50)       | `Tipo` (ex: `99`, `02`) – manter código bruto                                                       |
| rateio_code          | VARCHAR(50)       | `Contrato de Rateio` (ex: `712310900`) – se não for usado para regra, manter apenas como informação |
| has_renewal          | TINYINT(1)        | 1 = admite prorrogação; 0 = não admite (definir regra: padrão 1, permitir edição manual)            |
| max_renewals         | INT NULL          | opcional                                                                                            |
| status_phase         | ENUM              | `'EM_ELABORACAO','VIGENTE','EM_PRORROGACAO','EM_ENCERRAMENTO','ENCERRADO','RESCINDIDO','ANULADO'`   |
| status_risk          | ENUM              | `'TRANQUILO','PLANEJAR','AGIR','CRITICO','IRREGULAR'`                                               |
| next_action_text     | VARCHAR(255)      | Frase curta: ex.: `Decidir prorrogação`                                                             |
| next_action_deadline | DATE NULL         | Data limite para próxima ação                                                                       |
| manager_user_id      | INT FK users NULL | Gestor central responsável (opcional)                                                               |
| created_by_user_id   | INT FK users      | Quem cadastrou                                                                                      |
| created_at           | DATETIME          |                                                                                                     |
| updated_at           | DATETIME          |                                                                                                     |

### 4.4. Tabela `contract_responsibles`

Vínculo com fiscais e gestores setoriais.

| Campo            | Tipo             | Observação                   |
| ---------------- | ---------------- | ---------------------------- |
| id               | INT PK AI        |                              |
| contract_id      | INT FK contracts |                              |
| user_id          | INT FK users     |                              |
| role_in_contract | ENUM             | `'FISCAL','GESTOR_SETORIAL'` |
| created_at       | DATETIME         |                              |

### 4.5. Tabela `notifications`

Notificações geradas pelo sistema.

| Campo         | Tipo             | Observação                               |
| ------------- | ---------------- | ---------------------------------------- |
| id            | INT PK AI        |                                          |
| contract_id   | INT FK contracts |                                          |
| user_id       | INT FK users     | Destinatário                             |
| type          | VARCHAR(50)      | Ex.: `PRAZO_VENCENDO`, `RISCO_IRREGULAR` |
| title         | VARCHAR(255)     | Título da notificação                    |
| message       | TEXT             | Texto do e-mail / painel                 |
| send_channel  | ENUM             | `'EMAIL','APP'`                          |
| scheduled_for | DATETIME         | Quando deveria ser enviada               |
| sent_at       | DATETIME NULL    | Preenchido após envio                    |
| status        | ENUM             | `'PENDENTE','ENVIADA','ERRO'`            |
| created_at    | DATETIME         |                                          |

### 4.6. Tabela `settings`

Parâmetros globais.

| Campo      | Tipo         | Observação                            |
| ---------- | ------------ | ------------------------------------- |
| id         | INT PK AI    |                                       |
| name       | VARCHAR(100) | Ex.: `notification_days_with_renewal` |
| value      | TEXT         | JSON ou texto simples                 |
| updated_at | DATETIME     |                                       |

Sugestão de valores iniciais:

- `notification_days_with_renewal` → `180,120,90,60,30,15,7,3`
- `notification_days_without_renewal` → `120,90,60,30,15,7,3`

### 4.7. Tabela `logs` (auditoria simples)

| Campo       | Tipo         | Observação                                |
| ----------- | ------------ | ----------------------------------------- |
| id          | INT PK AI    |                                           |
| user_id     | INT FK users | Quem fez a ação                           |
| action      | VARCHAR(100) | Ex.: `CREATE_CONTRACT`, `UPDATE_CONTRACT` |
| description | TEXT         |                                           |
| created_at  | DATETIME     |                                           |

---

## 5. Regras de Status e Risco

### 5.1. Fase administrativa (`status_phase`)

- `VIGENTE`: contrato em execução normal (padrão ao importar).
- `EM_PRORROGACAO`: após iniciar fluxo de prorrogação.
- `EM_ENCERRAMENTO`: após usuário sinalizar que entrou em fase de encerramento.
- `ENCERRADO`: após checklist de encerramento concluído.
- `RESCINDIDO`, `ANULADO`: selecionados manualmente quando aplicável.

### 5.2. Semáforo de risco (`status_risk`)

Baseado em:

- Dias para `date_end_current`.
- Se há decisão de prorrogar/encerrar em andamento.
- Se existe fiscal vinculado (`contract_responsibles` ou `fiscal_name_raw` vazio).

Regras iniciais:

1. Calcular `dias_restantes = date_end_current - hoje`.

2. Se `status_phase` em (`ENCERRADO`,`RESCINDIDO`,`ANULADO`) → `TRANQUILO`.

3. Se contrato vencido (`dias_restantes < 0`) e `status_phase = VIGENTE` → `IRREGULAR`.

4. Se não existe fiscal (nem user vinculado) → mínimo `AGIR`, nunca `TRANQUILO`.

5. Faixas de dias (se não estiver irregular):
   
   - `dias_restantes > 120` → `TRANQUILO`
   - `120 >= dias_restantes > 60` → `PLANEJAR`
   - `60 >= dias_restantes > 30` → `AGIR`
   - `30 >= dias_restantes >= 0` → `CRITICO`

### 5.3. Próxima ação (`next_action_text` e `next_action_deadline`)

Regras iniciais:

- Se `status_phase = VIGENTE` e `has_renewal = 1`:
  - `next_action_text = 'Decidir prorrogação ou encerramento'`
  - `next_action_deadline = date_end_current - X dias` (ex.: 45 dias; pode vir de `settings`)
- Se `status_phase = VIGENTE` e `has_renewal = 0`:
  - `next_action_text = 'Planejar encerramento do contrato'`
- Se `status_phase = EM_PRORROGACAO`:
  - `next_action_text = 'Concluir processo de prorrogação'`
- Se `status_phase = EM_ENCERRAMENTO`:
  - `next_action_text = 'Concluir checklist de encerramento'`

---

## 6. Importação de CSV

### 6.1. Arquivo esperado

- Separador: `;`
- Primeira linha: cabeçalho exatamente como informado.
- Codificação: tratar como UTF-8; se falhar, tentar ISO-8859-1.
- Formatos:
  - Datas: `dd/mm/yyyy`
  - Valores: com vírgula como separador decimal.

### 6.2. Tela de importação

URL sugerida: `/index.php?route=import`

Elementos:

- Campo de upload de arquivo `.csv`.
- Checkbox: “Atualizar contratos existentes com mesmo Nº Contrato + Exercício”.
- Botão **[Importar]**.
- Após processo, exibir:
  - Quantidade total de linhas lidas.
  - Quantidade importada.
  - Quantidade atualizada.
  - Lista de erros (linha + motivo).

### 6.3. Mapeamento de campos CSV → DB

| CSV                      | DB (`contracts` / outros)    |
| ------------------------ | ---------------------------- |
| N° Contrato              | `contracts.number`           |
| Nº Detalhado do Contrato | `contracts.detailed_number`  |
| N° Modalidade            | `contracts.modality_code`    |
| Modalidade               | `contracts.modality_name`    |
| Exercício                | `contracts.exercise`         |
| Fundamento Legal         | `contracts.legal_basis`      |
| Proc. Licitatório        | `contracts.procedure_number` |
| CPF/CNPJ Fornecedor      | `suppliers.document`         |
| Fornecedor               | `suppliers.name`             |
| Valor                    | `contracts.value_total`      |
| Vigência Inicial         | `contracts.date_start`       |
| Vencimento Atual         | `contracts.date_end_current` |
| Objeto                   | `contracts.description_full` |
| Tipo                     | `contracts.type_code`        |
| Contrato de Rateio       | `contracts.rateio_code`      |
| Fiscal                   | `contracts.fiscal_name_raw`  |

Regras:

- Limpar CPF/CNPJ (deixar só números) ao armazenar em `suppliers.document`.
- Se fornecedor já existir (mesmo `document`), reutilizar o `id`.
- `description_short`: gerar automaticamente com os primeiros ~180 caracteres de `Objeto`.
- `status_phase`: `VIGENTE` na importação.
- `has_renewal`: iniciar como `1` (prorrogável) e permitir edição posterior.
- Após salvar, recalcular `status_risk`, `next_action_text`, `next_action_deadline`.

### 6.4. Chave para identificação de contratos já existentes

- Combinação recomendada: `number` + `exercise`.
- Se já existe:
  - Se usuário marcou “Atualizar contratos existentes”:
    - Atualizar campos com valores do CSV.
  - Senão:
    - Pular linha e registrar como “duplicado”.

---

## 7. Telas Principais (UX)

### 7.1. Login

- Campos:
  - E-mail
  - Senha
- Botão: **[Entrar]**
- Sem tela de cadastro público (usuários criados pelo admin).

---

### 7.2. Dashboard (Home após login)

URL: `/index.php?route=dashboard`

#### Bloco 1 – Indicadores grandes (cards no topo)

4 cards:

1. **Ações de hoje**
   - Número de contratos com `next_action_deadline = hoje` ou vencidos.
2. **Próximos 30 dias**
   - Contratos com `next_action_deadline` até +30 dias.
3. **Meus contratos**
   - Contratos onde o usuário é `contract_responsibles.user_id`.
4. **Risco elevado**
   - Contratos com `status_risk` em (`AGIR`,`CRITICO`,`IRREGULAR`).

Cada card é clicável, levando para lista de contratos já filtrada.

#### Bloco 2 – Minha fila de ações

Tabela com:

- Prioridade (ícone colorido conforme `status_risk`)
- Nº Contrato
- Objeto (resumo curto)
- Próxima ação (`next_action_text`)
- Prazo (`next_action_deadline`)
- Botão **[Ver contrato]**

Ordenação padrão: por `status_risk` (CRITICO/IRREGULAR primeiro), depois por `next_action_deadline`.

#### Bloco 3 – Alertas de risco (lateral)

Lista simples:

- Contratos vencidos e ainda `VIGENTE`.
- Contratos sem fiscal vinculado.
- Contratos com data de fim em < 30 dias sem decisão de prorrogar/encerrar.

Cada item com link para o contrato.

---

### 7.3. Lista de contratos

URL: `/index.php?route=contracts`

Filtros no topo:

- Campo único de busca (texto): pesquisa em `number`, `detailed_number`, `description_short`, `suppliers.name`.
- Checkbox:
  - [ ] **Só meus contratos**
  - [ ] **Só com ação pendente** (`next_action_deadline` não nulo e ≥ hoje)
  - [ ] **Só em risco** (status_risk em AGIR/CRITICO/IRREGULAR)
- Select de fase:
  - Todos / Vigente / Em prorrogação / Em encerramento / Encerrado / Outros.

Tabela:

- Nº
- Objeto (resumo)
- Fornecedor
- Fase (`status_phase`)
- Semáforo (ícone/cor conforme `status_risk`)
- Próxima ação (texto curto)
- Prazo (data ou “—”)
- Botão **[Ver]**

Ordenação padrão: `date_end_current` ascendente.

---

### 7.4. Detalhe do contrato

URL: `/index.php?route=contract/view&id=X`

Estrutura:

1. **Cabeçalho**
   
   - Nº Contrato (grande)
   - Situação: `status_phase` + ícone de risco (`status_risk`)
   - Datas de vigência (início e fim atual)
   - Fornecedor

2. **Banner de ação (fixo abaixo do cabeçalho)**

Exemplo de textos, conforme regras:

- Vigente com prorrogação:
  
  - “Este contrato termina em 31/12/2025. Você precisa decidir se prorroga ou encerra até 15/11/2025.”
  - Botões grandes:
    - [Iniciar prorrogação]
    - [Iniciar encerramento]

- Crítico / irregular:
  
  - Texto em vermelho, claro, com prazo.
3. **Abas ou blocos**
- **Dados gerais**
  
  - Modalidade, exercício, fundamento legal, processo, tipo, rateio.

- **Objeto**
  
  - `description_full` (texto completo).

- **Responsáveis**
  
  - Gestor central
  - Fiscais (lista; com botão “Vincular usuário” se tiver permissão).

- **Histórico**
  
  - Logs relevantes (criação, alterações, mudanças de fase).
4. **Ações (botões)**

Dependendo da permissão:

- [Editar contrato]
- [Iniciar prorrogação]
- [Iniciar encerramento]
- [Encerrar contrato] (quando checklist ok)
- [Registrar rescisão/anulação]

Fluxo de “Iniciar prorrogação”:

- Abre formulário simples:
  - Nova data fim.
  - Justificativa (texto).
- Ao confirmar:
  - Atualizar `status_phase` para `EM_PRORROGACAO`.
  - Ajustar `next_action_text`/`next_action_deadline`.

---

### 7.5. Formulário de contrato (cadastro/edição)

Campos agrupados:

1. **Identificação**
   
   - Nº Contrato (obrigatório)
   - Nº detalhado
   - Exercício
   - Modalidade (texto)
   - Nº modalidade (texto)
   - Processo licitatório

2. **Fornecedor**
   
   - CPF/CNPJ (busca e/ou auto-preenchimento da tabela `suppliers`)
   - Nome do fornecedor (se novo)

3. **Vigência e valores**
   
   - Data início
   - Data fim atual
   - Valor total
   - Possui prorrogação? (sim/não)
   - Nº máximo de prorrogações (opcional)

4. **Responsáveis**
   
   - Campo de texto livre “Fiscal (nome)” – opção rápida
   - Seleção de usuários do sistema como Fiscais/Gestor (se já cadastrados)

5. **Objeto**
   
   - Texto completo.

Botões:

- [Salvar]
- [Cancelar]

---

### 7.6. Administração de usuários

URL: `/index.php?route=users` (somente ADMIN)

- Lista de usuários:
  - Nome, e-mail, perfil, status (ativo/inativo).
  - Botão [Editar].
- Formulário:
  - Nome
  - E-mail
  - Perfil (ADMIN, GESTOR_CENTRAL, FISCAL, VISUALIZADOR)
  - Ativo? (sim/não)
  - Redefinir senha (opcional).

---

### 7.7. Tela de notificações (no app)

Ícone de sino no topo:

- Ao clicar, abrir lista:
  - últimas notificações para o usuário (tabela `notifications` com `send_channel = 'APP'`).
- Mostrar:
  - Título
  - Trecho do texto
  - Data
  - Link para o contrato.

---

## 8. Notificações e CRON

### 8.1. Agendamento diário

Arquivo: `/cron/cron_notifications.php`

Executado 1x por dia (ex.: 06:00, horário do servidor).

Processo:

1. Carregar configurações de dias de aviso (settings).
2. Para cada contrato `VIGENTE` ou `EM_PRORROGACAO`:
   - Calcular `dias_restantes`.
   - Verificar lista de dias configurados (ex.: 180,120,90,60,30,15,7,3).
   - Se `dias_restantes` está na lista:
     - Gerar notificações para:
       - Gestor central (`manager_user_id` se houver).
       - Fiscais (`contract_responsibles`).
3. Casos especiais:
   - Contrato vencido e ainda `VIGENTE` → gerar notificação de risco `IRREGULAR` para:
     - Gestor central
     - Algum usuário com papel ADMIN (poder configurar e-mail fixo).
4. Gravar notificação em `notifications` com `status = 'PENDENTE'`.
5. Enviar e-mail:
   - Texto simples, com:
     - Número do contrato.
     - Objeto (curto).
     - Data de fim.
     - Frase de ação (usar `next_action_text`).
     - Link direto para o detalhe do contrato.
6. Atualizar `notifications.status` para `'ENVIADA'` e `sent_at`.

---

## 9. PWA – Requisitos mínimos

### 9.1. `manifest.json`

Na pasta `/public/manifest.json`:

- Nome do app (ex.: “Contratos – Jaborandi”).
- Ícones (512, 192, 64).
- `start_url`: `/index.php`.
- `display`: `standalone`.
- `theme_color` e `background_color`.

### 9.2. `service-worker.js`

Funções mínimas:

- Cache dos arquivos estáticos principais (CSS, JS, imagens).
- Cache da página inicial e da lista de contratos.
- Servir conteúdo do cache quando offline.

### 9.3. Registro do PWA

No layout principal (`views/layout.php`), incluir:

- `<link rel="manifest" href="/public/manifest.json">`
- Registro do service worker via JavaScript.

---

## 10. Segurança Básica

- Sessões com `session_regenerate_id()` após login.
- Time-out de sessão (ex.: 30 min de inatividade).
- Senhas com `password_hash()` e `password_verify()`.
- Proteção mínima de CSRF (token em formulários sensíveis).
- Filtrar/escapar todos os dados vindos de `$_POST`/`$_GET`.

---

## 11. Ordem de Implementação (para o programador)

1. Criar banco MySQL e tabelas (`users`, `suppliers`, `contracts`, `contract_responsibles`, `notifications`, `settings`, `logs`).
2. Implementar autenticação (login/logout, sessão, middleware de permissão).
3. Criar layout base (header com menu, link para dashboard, contratos, importação, usuários).
4. Implementar:
   - CRUD de contratos (sem importar CSV ainda).
   - CRUD de usuários (somente ADMIN).
5. Implementar cálculo de `status_risk` e `next_action_*` ao salvar contrato.
6. Construir Dashboard conforme especificação.
7. Implementar lista de contratos com filtros.
8. Implementar importação de CSV com mapeamento definido.
9. Implementar `cron_notifications.php` + envio de e-mails.
10. Implementar PWA (manifest, service worker, registro).
11. Refinar textos das notificações e pequenos ajustes visuais.


