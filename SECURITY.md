# Política de Segurança e Arquitetura de Proteção — DigitalCard

> **Aviso de Escopo Acadêmico:** Este repositório contém um **projeto universitário de demonstração**. Nenhum pagamento PIX, cartão virtual, saldo ou entrega física é real.

## Controles de Segurança Implementados

1. **Armazenamento Privado e Prevenção de IDOR em Comprovantes (S1)**
   - Uploads de comprovantes são gravados exclusivamente no disco privado `local` (`storage/app/private/receipts`).
   - A visualização ocorre por rota autenticada (`GET /recargas/{recharge}/comprovante`) protegida por `RechargePolicy@viewReceipt` (restrita ao titular do cartão ou administrador), com cabeçalhos `X-Content-Type-Options: nosniff` e `Cache-Control: private, no-store, max-age=0`.

2. **Segregação de Funções e Auditoria Financeira (S2, S3, F4, F5)**
   - Administradores são bloqueados (`RechargePolicy@review` → `403 Forbidden`) de aprovar ou recusar solicitações de recarga do próprio cartão virtual.
   - O crédito de recarga utiliza `approved_amount` confirmado pelo administrador revisor (`reviewed_by`), registrando estado anterior e novo na tabela `audit_logs`.
   - Pedidos seguem máquina de estados estrita (`App\Enums\OrderStatus`): pedidos cancelados jamais podem transicionar para entregues ou gerar cashback (`cashback_credited_at` garante crédito único e idempotente).

3. **Criptografia em Repouso e Revelação de CVV sob Demanda (S8)**
   - As colunas `card_number` e `cvv` de `virtual_cards` utilizam cast `encrypted` (AES-256-CBC via `APP_KEY`), mantendo apenas `card_last_four` e `card_hash` (SHA-256) para indexação.
   - O prefixo do cartão (`9900`) é claramente fictício e o CVV nunca é renderizado no HTML por padrão, exigindo confirmação do PIN de 4 dígitos via `POST /meu-cartao/revelar-cvv`.

4. **Proteção contra Força Bruta e Escalação de Privilégio (S5, S11)**
   - `RateLimiter` aplicado nas verificações de PIN do cartão (5 tentativas → bloqueio de 15 minutos por usuário) e no login (por conta `email|ip` e globalmente por IP `login-ip:{ip}`).
   - Coluna `is_admin` removida de `$fillable` no model `User`.
   - Login administrativo separado em `/admin/login` com mensagens de erro genéricas (`auth.failed`), evitando enumeração de contas ou confirmação de senha.
   - Middleware `SecurityHeadersMiddleware` ativo em todas as respostas web (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` e `Content-Security-Policy-Report-Only`).

5. **Integridade Monetária e Idempotência (S9, S10, F1)**
   - Cálculos de subtotal, desconto de cupons, frete e cashback centralizados em `App\Services\CartPricing` operando em **centavos inteiros (`int`)**, calculando o cashback estritamente sobre o valor líquido pago após descontos.
   - Checkout protegido por `idempotency_key` única por formulário e webhook PIX simulado (`POST /webhooks/pix/simular`) idempotente por `txid`.

## Limitações Conhecidas do Ambiente Acadêmico
- **SQLite e Concorrência (`lockForUpdate`)**: O SQLite realiza travamento em nível de arquivo/tabela na escrita e ignora cláusulas `FOR UPDATE` em leituras `SELECT`. Para testes de estresse de concorrência real em produção, utilize **MySQL 8.0+** ou **PostgreSQL 15+** (ex.: via Laravel Sail).
- **Sessão em Produção**: Ao publicar sob HTTPS, configure `SESSION_SECURE_COOKIE=true` e `APP_DEBUG=false` no arquivo `.env`.
