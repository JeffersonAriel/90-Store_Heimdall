# HEIMDALL ERP + 90+ STORE

> Sistema ERP empresarial integrado com e-commerce especializado em material esportivo.

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat&logo=php)
![Vue.js](https://img.shields.io/badge/Vue-3.x-4FC08D?style=flat&logo=vue.js)
![TypeScript](https://img.shields.io/badge/TypeScript-5.x-3178C6?style=flat&logo=typescript)

---

## 📦 Stack Tecnológico

| Camada | Tecnologia |
|--------|-----------|
| Backend | Laravel 12, PHP 8.4 |
| Frontend | Vue 3, Inertia.js, TypeScript |
| CSS | TailwindCSS 4 |
| Banco | MySQL 8+ |
| Cache/Queue | Redis |
| Container | Docker + Nginx |
| CI/CD | GitHub Actions |

## 🚀 Instalação Local (Docker)

```bash
# Clone o repositório
git clone https://github.com/seu-usuario/heimdall-erp.git
cd heimdall-erp

# Copie o .env
cp .env.example .env

# Suba os containers
docker-compose up -d

# Instale dependências dentro do container
docker exec heimdall_app composer install
docker exec heimdall_app php artisan key:generate

# Acesse o instalador web
open http://localhost/install
```

## 🛠️ Instalação Manual (HostGator/cPanel)

```bash
# 1. Clone no servidor
git clone ... && cd heimdall-erp

# 2. Instale dependências
composer install --no-dev --optimize-autoloader

# 3. Configure o .env
cp .env.example .env
# Edite o .env com seus dados MySQL

# 4. Acesse o instalador
# http://seusite.com.br/install
```

## 📁 Estrutura de Módulos

```
Modules/
├── Products/     # PIM + Seeders esportivos
├── Categories/
├── Brands/
├── Customers/
├── Orders/
├── Stock/
├── Purchases/
├── Financial/
├── CRM/
├── Marketing/
├── Reports/
├── Users/
├── Permissions/  # RBAC
├── Audit/
├── Settings/
├── Shipping/     # Plugável: Uber Moto, Metrô, Correios, Jadlog...
├── Payments/     # Plugável: Mercado Pago, Stripe, Pix...
├── Ecommerce/    # 90+ Store
├── AI/
├── BI/
└── API/          # RESTful pública
```

## 🔐 Roles e Permissões (RBAC)

| Role | Acesso |
|------|--------|
| `super-admin` | Acesso total |
| `admin` | Tudo exceto configurações de sistema |
| `gerente` | Produtos, pedidos, clientes, relatórios |
| `operador` | Vendas e atendimento |
| `financeiro` | Módulo financeiro e relatórios |
| `estoque` | Estoque e compras |
| `cliente` | Área do cliente na loja |

## 🚚 Integrações de Frete

- **Uber Moto** — até 50km de São Miguel Paulista (SP)
- **Entrega Metrô** — São Paulo Capital
- **Correios** — PAC, SEDEX
- **Jadlog** — .Package, .Com
- **SuperFrete** / **Melhor Envio**

## 💳 Gateways de Pagamento

- Mercado Pago (PIX, cartão, boleto)
- PagSeguro
- Stripe
- Asaas
- Pagar.me

## 🤖 IA — Cadastro Inteligente

Dado o nome, SKU ou EAN de um produto, a IA sugere:
- Categoria e subcategoria
- Descrição otimizada
- Atributos (tamanho, cor, material)
- NCM e tributação
- Tags e SEO

## 🌿 Git Flow

```
main          # Produção
develop       # Desenvolvimento
feature/*     # Novas funcionalidades
hotfix/*      # Correções urgentes
release/*     # Preparação de release
```

## 📋 Cron Jobs (HostGator)

```
* * * * * /usr/local/bin/php /home/usuario/public_html/artisan schedule:run >> /dev/null 2>&1
```

## 📄 Licença

Proprietário — 90+ Store / HEIMDALL ERP. Todos os direitos reservados.
