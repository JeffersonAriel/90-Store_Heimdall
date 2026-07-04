# ⚡ Heimdall Back-Office + 90-Store E-commerce

Este repositório contém a fundação completa do ecossistema **Heimdall** (back-office de gestão administrativa e financeira) integrado à loja virtual **90-Store** (e-commerce esportivo responsivo mobile-first).

---

## 🏗️ Estrutura do Repositório

O projeto está organizado no formato de monorepo simplificado:

```
90-Store_Heimdall/
├── backend/            # API REST Laravel 11 + Painel Admin Inertia/Vue 3
│   ├── app/            # Regras de negócio, Models, Middlewares e Controllers
│   ├── database/       # Migrations e Seeders estruturados do MySQL/SQLite
│   ├── resources/      # Layouts, Views e assets do Painel Administrativo Heimdall
│   └── routes/         # Rotas divididas (web.php e api.php com autenticação Sanctum)
└── store/              # SPA Cliente em Vue 3 (Vite + Pinia + Vue Router)
    ├── src/            # Vitrine reativa, Carrinho, Checkout Pix e Minha Conta
    └── index.html      # Ponto de entrada HTML5 com SEO dinâmico
```

---

## 🛠️ Tecnologias Utilizadas

### Backend / Painel Administrativo (Heimdall)
- **Laravel 11 (PHP 8.3+)** com drivers de banco de dados locais/SQLite e MySQL compatível.
- **Inertia.js + Vue 3 (Composition API)** com build via Vite gerando assets estáticos publicáveis.
- **PragmaRX Google 2FA** integrado no fluxo de login de funcionários.
- **Spatie Activity Log** integrado para auditoria granular de alterações.
- **PhpSpreadsheet** para processamento, validação forense e importação/exportação de planilhas Excel (.xlsx) de Produtos e Fornecedores.

### Loja Virtual (90-Store)
- **Vue 3 SPA Pura (Vite)** para a melhor velocidade em dispositivos móveis.
- **Pinia Store** controlando reativamente o estado global (DRE do carrinho e sessões de clientes).
- **Sanctum Authentication** via cookies e tokens de longa duração.
- **@vueuse/head** para meta tags dinâmicas de SEO da vitrine e produtos.

---

## 📦 Deploy na Hospedagem Compartilhada (HostGator)

Como a HostGator não permite processos Node permanentes rodando em produção (VPS/Dedicado não disponível), este ecossistema foi projetado sob as seguintes premissas:

### 1. Preparação dos Assets
No ambiente de desenvolvimento local, compile os assets estáticos do painel administrativo e da loja virtual:

```bash
# No diretório /backend
npm run build

# No diretório /store
npm run build
```
Os arquivos gerados na pasta `/store/dist` devem ser movidos para a pasta pública do seu subdomínio correspondente à loja.

### 2. Configurações do `.htaccess` para SPA e Laravel
Utilize o seguinte arquivo `.htaccess` na raiz pública da loja para permitir que o Vue Router funcione sem quebras de rotas ao recarregar a página (F5):

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>
```

Para o backend na pasta `/public` do Laravel:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 3. Execução das Filas de Estoque e Comandos de Expiração
Como a HostGator não possui suporte a serviços persistentes como o Horizon/Supervisor, utilize a tabela do banco de dados para gerenciar as filas.

No **cPanel da HostGator**, configure as seguintes **Tarefas Cron (Cron Jobs)**:

```bash
# Executa o processamento de filas pendentes a cada minuto (reserva/baixa de estoque)
* * * * * php /home/seu_usuario/public_html/backend/artisan queue:work --stop-when-empty > /dev/null 2>&1

# Varre e libera estoques reservados expirados a cada 15 minutos
*/15 * * * * php /home/seu_usuario/public_html/backend/artisan stock:release-expired > /dev/null 2>&1
```

---

## 🔒 Segurança e LGPD

- **Dados Sensíveis Criptografados:** O campo de CPF do cliente é automaticamente armazenado e encriptado via cifra AES-256 no banco de dados usando a chave secreta de aplicação (`APP_KEY` do Laravel). A busca exata descriptografa sob demanda, atendendo aos princípios de privacidade e conformidade com a LGPD.
- **Segurança de APIs:** Chaves confidenciais e senhas de integração das APIs de CEP (ViaCEP, BrasilAPI, ApiCEP), frete (Melhor Envio) e gateways (Mercado Pago, PagSeguro, Stripe) são encriptadas de forma transparente no banco de dados antes da gravação e mascaradas em consultas do front-end.
- **Blindagem de IP:** O middleware `BlockBannedIps` faz validação de tentativas de invasões e bloqueios síncronos no servidor web.
