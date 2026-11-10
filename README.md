# DigitalCard — Loja Universitária com Cartão Virtual e Cashback

> **Aviso Acadêmico:** Este é um **projeto universitário — ambiente de demonstração**. Nenhum pagamento PIX, cartão virtual, saldo financeiro ou entrega logística é real.

## Visão Geral e Arquitetura
O **DigitalCard** é uma aplicação web desenvolvida em **Laravel 13 (`^13.17`)** que simula um ecossistema de e-commerce integrado a uma carteira digital e cartão virtual próprio:
- **Design System Editorial**: Interface tipográfica com *Instrument Sans*, *Instrument Serif* e *Geist Mono*, paleta neutra aquecida (`paper`, `surface`, `ink`, `line`, `accent`, `money`, `danger`) e ícones vetoriais *Lucide* locais (`resources/svg/icons/`).
- **Catálogo de Produtos**: 45 produtos distribuídos em 5 departamentos principais e 14 subcategorias, com busca segura, filtros por preço, cashback e avaliação real (`withAvg`/`withCount`), ordenação e SEO (`sitemap.xml`, `robots.txt`, Open Graph e JSON-LD `Product` / `BreadcrumbList`).
- **Carrinho e Cupons (`App\Services\CartPricing`)**: Suporte a visitantes (sessão com merge automático no login) e persistência em banco (`cart_items` e `wishlists`). Cálculo unificado em centavos inteiros (`int`), validação de cupons em banco (`coupons` e `coupon_redemptions`) e cashback calculado proporcionalmente sobre o valor líquido pago.
- **Frete e Endereços (`App\Services\ShippingCalculator`)**: Consulta de CEP via ViaCEP (com cache e fallback offline), persistência em `addresses` e frete grátis em compras acima de R$ 299,00.
- **Cartão Virtual Fictício (`9900`) e PIX Simulado (`App\Services\Payment\SimulatedPixGateway`)**:
  - Número do cartão e CVV protegidos em repouso (`encrypted` cast) e revelados sob demanda mediante validação do PIN de 4 dígitos (protegido por `RateLimiter` de 5 tentativas / 15 min).
  - Geração de cobrança PIX dinâmica e botão de simulação de pagamento (`POST /webhooks/pix/simular`), além do fluxo secundário de envio de comprovante privado (`storage/app/private/receipts`) avaliado por administrador.
- **Ciclo de Vida do Pedido (`App\Enums\OrderStatus`)**: Máquina de estados (`Produto solicitado` → `Em separação` → `Enviado` → `Produto entregue`, além de `Pedido cancelado` e `Devolvido (Arrependimento)` em até 7 dias corridos), histórico em `order_status_histories` e notificações por e-mail.

## Instalação e Execução Local

1. Instale as dependências PHP e Node.js:
   ```bash
   composer install
   npm ci
   ```
2. Configure o arquivo `.env` e gere a chave da aplicação:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Prepare o banco SQLite, execute as migrations e rode o seeder (em `APP_ENV=local`):
   ```bash
   touch database/database.sqlite
   php artisan migrate:fresh --seed
   ```
   - **Credenciais de demonstração**: Se `ADMIN_INITIAL_PASSWORD` e `DEMO_CLIENT_PASSWORD` não estiverem definidas no `.env`, o `DatabaseSeeder` gera senhas aleatórias de 16 caracteres para `admin@digitalcard.com` e `cliente@digitalcard.com` e as exibe no console ao rodar `migrate:fresh --seed`.
   - **Verificação de e-mail**: Em desenvolvimento (`MAIL_MAILER=log`), os links de verificação de e-mail e notificações de status de pedido são gravados em `storage/logs/laravel.log`.
4. Compile os assets de frontend:
   ```bash
   npm run build
   ```

## Imagens de Produtos e Capturas de Tela
- **Guia de Fotos Reais**: Consulte [`docs/imagens.md`](./docs/imagens.md) para a lista completa dos 45 produtos e a convenção de arquivos `public/images/products/{slug}-400.webp` (400×500 px) e `public/images/products/{slug}.webp` (800×1000 px). Enquanto um arquivo não está presente, o componente `<x-product-image>` renderiza automaticamente um placeholder editorial com o ícone da categoria.
- **Capturas de Tela Responsivas**: As capturas de tela das rotas públicas, área do cliente e painel administrativo nas larguras `390px`, `768px`, `1280px` e `1440px` encontram-se em [`docs/screenshots/`](./docs/screenshots/).

## Executando os Testes e Ferramentas de Qualidade

```bash
php artisan test --compact
CACHE_STORE=database php artisan test --compact
php vendor/bin/pint
```

## Boas Práticas de Segurança
Consulte o arquivo [`SECURITY.md`](./SECURITY.md) para detalhes sobre os controles implementados.
- **Jamais versione ou envie** o arquivo `.env`, `.phpunit.result.cache`, `storage/framework/views/*.php`, comprovantes em `storage/app/private/receipts/*` ou bancos de dados locais (`database.sqlite`).
