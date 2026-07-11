<template>
  <div class="success-view container mt-8">
    <div class="success-card" v-if="order">
      <div class="success-icon">🎉</div>
      <h1 class="title-lg mt-4 text-green">PEDIDO CONFIRMADO!</h1>
      <p class="subtitle mt-2">Obrigado por comprar conosco. O status do seu pedido é atualizado automaticamente.</p>

      <div class="order-details-box mt-6">
        <h2 class="section-title">Resumo do Pedido</h2>
        
        <div class="detail-row">
          <span>Número do Pedido:</span>
          <strong>{{ order.order_nsu }}</strong>
        </div>
        
        <div class="detail-row">
          <span>Status do Pedido:</span>
          <span class="status-badge" :class="order.status">{{ formatStatus(order.status) }}</span>
        </div>

        <div class="detail-row">
          <span>Forma de Pagamento:</span>
          <strong>InfinitePay (Cartão / Pix)</strong>
        </div>

        <div class="detail-row" v-if="receiptUrl">
          <span>Comprovante:</span>
          <a :href="receiptUrl" target="_blank" class="btn btn-secondary btn-sm">Ver Comprovante</a>
        </div>
      </div>

      <div class="products-box mt-6">
        <h2 class="section-title">Produtos Adquiridos</h2>
        <div class="product-item" v-for="item in order.itens" :key="item.id">
          <div class="product-info">
            <strong>{{ item.nome_snapshot }}</strong>
            <span class="text-gray">Qtd: {{ item.quantidade }}</span>
          </div>
          <span class="price">{{ formatCurrency(item.preco_venda_snapshot * item.quantidade) }}</span>
        </div>
      </div>

      <div class="totals-box mt-6">
        <div class="total-row">
          <span>Subtotal:</span>
          <span>{{ formatCurrency(order.subtotal) }}</span>
        </div>
        <div class="total-row" v-if="order.desconto_cupom > 0">
          <span>Desconto Cupom:</span>
          <span class="text-green">- {{ formatCurrency(order.desconto_cupom) }}</span>
        </div>
        <div class="total-row" v-if="order.desconto_pontos > 0">
          <span>Desconto Pontos:</span>
          <span class="text-green">- {{ formatCurrency(order.desconto_pontos) }}</span>
        </div>
        <div class="total-row">
          <span>Frete:</span>
          <span>{{ formatCurrency(order.valor_frete) }}</span>
        </div>
        <div class="total-row grand-total mt-4">
          <span>Total Pago:</span>
          <span>{{ formatCurrency(order.total) }}</span>
        </div>
      </div>

      <div class="actions mt-8">
        <RouterLink to="/" class="btn btn-primary">Voltar para a Loja</RouterLink>
        <RouterLink to="/minha-conta?tab=pedidos" class="btn btn-secondary ml-4">Meus Pedidos</RouterLink>
      </div>
    </div>

    <div v-else-if="loading" class="loading-state text-center my-8">
      <span class="loading-spinner"></span>
      <p class="text-gray mt-4">Buscando informações do pedido...</p>
    </div>

    <div v-else class="error-state text-center my-8">
      <div class="error-icon">❌</div>
      <h2 class="title-md mt-4">Pedido não encontrado</h2>
      <p class="text-gray mt-2">Não foi possível carregar as informações do seu pedido.</p>
      <RouterLink to="/" class="btn btn-primary mt-6">Ir para Home</RouterLink>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const order = ref(null)
const loading = ref(true)

const receiptUrl = computed(() => {
  if (!order.value || !order.value.pagamentos) return null
  const pago = order.value.pagamentos.find(p => p.status === 'aprovado')
  if (pago && pago.payload_json) {
    try {
      const parsed = typeof pago.payload_json === 'string' ? jsonDecode(pago.payload_json) : pago.payload_json
      return parsed.receipt_url || null
    } catch (e) {
      return null
    }
  }
  return null
})

function jsonDecode(str) {
  try {
    return JSON.parse(str)
  } catch (e) {
    return null
  }
}

onMounted(async () => {
  const orderId = route.query.order_id
  if (!orderId) {
    loading.value = false
    return
  }

  try {
    const res = await axios.get(`/api/orders/public/${orderId}`, { params: route.query })
    if (res.data.success) {
      order.value = res.data.pedido
    }
  } catch (err) {
    console.error('Erro ao buscar dados do pedido', err)
  } finally {
    loading.value = false
  }
})

function formatStatus(status) {
  const map = {
    'aguardando_pagamento': 'Aguardando Pagamento',
    'em_separacao': 'Em Separação',
    'em_envio': 'Em Envio',
    'enviado': 'Enviado',
    'entregue': 'Entregue',
    'cancelado': 'Cancelado',
    'devolvido': 'Devolvido'
  }
  return map[status] || status
}

function formatCurrency(value) {
  return 'R$ ' + Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.success-view {
  max-width: 700px;
  margin: 0 auto;
  padding: 0 var(--spacing-4);
}

.success-card {
  background-color: var(--color-black-light);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  padding: var(--spacing-8);
  text-align: center;
}

.success-icon {
  font-size: 4rem;
}

.text-green {
  color: #10b981;
}

.order-details-box, .products-box, .totals-box {
  background-color: var(--color-black);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  padding: var(--spacing-6);
  text-align: left;
}

.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  border-bottom: 1px solid var(--color-black-lighter);
  padding-bottom: var(--spacing-2);
  margin-bottom: var(--spacing-4);
}

.detail-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: var(--spacing-3);
  font-size: 0.95rem;
}

.status-badge {
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.aguardando_pagamento {
  background-color: #f59e0b;
  color: #fff;
}

.status-badge.em_separacao, .status-badge.em_envio, .status-badge.enviado {
  background-color: #3b82f6;
  color: #fff;
}

.status-badge.entregue {
  background-color: #10b981;
  color: #fff;
}

.product-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--color-black-lighter);
  padding-bottom: var(--spacing-3);
  margin-bottom: var(--spacing-3);
}

.product-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
  margin-bottom: 0;
}

.product-info {
  display: flex;
  flex-direction: column;
}

.total-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: var(--spacing-2);
  font-size: 0.95rem;
}

.total-row.grand-total {
  border-top: 1px solid var(--color-black-lighter);
  padding-top: var(--spacing-4);
  font-size: 1.3rem;
  font-weight: 700;
}

.btn-secondary.btn-sm {
  padding: var(--spacing-1) var(--spacing-3);
  font-size: 0.85rem;
}

.error-icon {
  font-size: 4rem;
}

.ml-4 {
  margin-left: var(--spacing-4);
}
</style>
