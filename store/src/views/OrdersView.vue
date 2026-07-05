<template>
  <div class="orders-view container pt-24 pb-12">
    <div class="flex justify-between items-center mb-8">
      <h1 class="title-lg">MEUS PEDIDOS</h1>
      <RouterLink to="/" class="btn btn-outline">VOLTAR PARA A LOJA</RouterLink>
    </div>

    <div v-if="loading" class="text-center py-12">
      <span class="loading-spinner"></span>
      <p class="text-gray mt-4">Buscando seus pedidos...</p>
    </div>

    <div v-else-if="orders.length === 0" class="empty-state text-center py-12">
      <h2 class="text-xl font-bold">Nenhum pedido encontrado.</h2>
      <p class="text-gray mt-2 mb-6">Parece que você ainda não fez nenhuma compra.</p>
      <RouterLink to="/" class="btn btn-primary">IR PARA AS COMPRAS</RouterLink>
    </div>

    <div v-else class="orders-list">
      <div v-for="order in orders" :key="order.id" class="order-card mb-6">
        <div class="order-header flex justify-between items-center">
          <div>
            <h3 class="font-bold text-lg">Pedido #{{ order.id }}</h3>
            <span class="text-gray text-sm">{{ formatDate(order.created_at) }}</span>
          </div>
          <div class="order-status" :class="getStatusClass(order.status)">
            {{ formatStatus(order.status) }}
          </div>
        </div>

        <div class="order-body mt-4">
          <div class="order-items">
            <div v-for="item in order.itens" :key="item.id" class="order-item flex items-center mb-3 pb-3 border-b border-dark">
              <img :src="item.produto?.foto_capa || 'https://via.placeholder.com/60'" class="item-img" />
              <div class="item-details ml-4 flex-1">
                <p class="font-bold">{{ item.produto?.nome }}</p>
                <p class="text-gray text-sm">Tamanho: {{ item.variacao?.tamanho }} | Cor: {{ item.variacao?.cor }}</p>
              </div>
              <div class="item-price text-right">
                <p>{{ item.quantidade }}x</p>
                <p class="font-bold">{{ formatCurrency(item.preco_unitario) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="order-footer flex justify-between items-center mt-4">
          <div class="text-gray">
            Pagamento: <span class="text-white">{{ formatCurrency(order.total) }}</span>
          </div>
          <button class="btn btn-outline text-sm py-2 px-4">Ver Detalhes</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useHead } from '@vueuse/head'
import axios from 'axios'
import { useStore } from '@/store/main'
import { useRouter } from 'vue-router'

useHead({ title: 'Meus Pedidos | 90+ Store' })

const store = useStore()
const router = useRouter()
const orders = ref([])
const loading = ref(true)

onMounted(async () => {
  if (!store.isAuthenticated) {
    router.push('/login')
    return
  }

  try {
    const res = await axios.get('/api/orders', {
      headers: { Authorization: `Bearer ${store.token}` }
    })
    orders.value = res.data.pedidos
  } catch (err) {
    console.error("Erro ao buscar pedidos", err)
  } finally {
    loading.value = false
  }
})

function formatDate(dateStr) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(date)
}

function formatCurrency(value) {
  return 'R$ ' + Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatStatus(status) {
  const labels = {
    'aguardando_pagamento': 'Aguardando Pagamento',
    'pago': 'Pagamento Aprovado',
    'em_separacao': 'Em Separação',
    'enviado': 'Enviado',
    'entregue': 'Entregue',
    'cancelado': 'Cancelado'
  }
  return labels[status] || status
}

function getStatusClass(status) {
  return {
    'status-warning': status === 'aguardando_pagamento' || status === 'em_separacao',
    'status-success': status === 'pago' || status === 'enviado' || status === 'entregue',
    'status-danger': status === 'cancelado'
  }
}
</script>

<style scoped>
.order-card {
  background: var(--surface);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: var(--spacing-6);
}

.order-status {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 600;
}

.status-warning {
  background: rgba(234, 179, 8, 0.1);
  color: #eab308;
}

.status-success {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.status-danger {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.item-img {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: var(--radius-sm);
  background: #111;
}

.border-dark {
  border-color: #333;
}
</style>
