#!/bin/bash
#
# ─── HEIMDALL ERP + 90+ STORE — Deploy Script (HostGator) ───────────────────
# Uso: ./deploy.sh [branch]
# Padrão: main
#

set -e

BRANCH="${1:-main}"
APP_DIR="$(pwd)"
PHP="php"
COMPOSER="composer"

echo "════════════════════════════════════════"
echo " HEIMDALL ERP — Deploy para Produção"
echo " Branch: $BRANCH"
echo " Diretório: $APP_DIR"
echo "════════════════════════════════════════"

# 1. Ativar modo de manutenção
echo "→ [1/10] Ativando modo de manutenção..."
$PHP artisan down --secret="heimdall-bypass-2024" --render="errors::503"

# 2. Pull do git
echo "→ [2/10] Atualizando código..."
git fetch origin
git checkout $BRANCH
git pull origin $BRANCH

# 3. Instalar dependências PHP (sem dev)
echo "→ [3/10] Instalando dependências PHP..."
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --quiet

# 4. Limpar caches antigos
echo "→ [4/10] Limpando caches..."
$PHP artisan config:clear
$PHP artisan route:clear
$PHP artisan view:clear
$PHP artisan cache:clear
$PHP artisan event:clear

# 5. Executar migrations
echo "→ [5/10] Executando migrations..."
$PHP artisan migrate --force

# 6. Otimizar para produção
echo "→ [6/10] Otimizando para produção..."
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan event:cache
$PHP artisan icons:cache 2>/dev/null || true

# 7. Ajustar permissões
echo "→ [7/10] Ajustando permissões..."
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage/logs/*.log 2>/dev/null || true

# 8. Reiniciar queue workers
echo "→ [8/10] Reiniciando queue workers..."
$PHP artisan queue:restart

# 9. Warmup do storage
echo "→ [9/10] Criando links simbólicos..."
$PHP artisan storage:link 2>/dev/null || true

# 10. Desativar manutenção
echo "→ [10/10] Desativando modo de manutenção..."
$PHP artisan up

echo ""
echo "✅ Deploy concluído com sucesso!"
echo "   Versão: $(git describe --tags --always)"
echo "   Data: $(date '+%Y-%m-%d %H:%M:%S')"
echo "════════════════════════════════════════"
