<template>
  <AdminLayout title="Gestão de Pedidos">
    <template #breadcrumb>
      <span>Operações / Pedidos</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">📋 Gestão de Pedidos</h1>
        <p class="text-secondary mt-1">Gerencie os pedidos recebidos, avance os status sequencialmente e confirme pagamentos.</p>
      </div>
    </div>

    <!-- Filtros de Busca e Status -->
    <div class="card mb-6">
      <div class="card-body">
        <form @submit.prevent="handleSearch" class="flex gap-4 items-end flex-wrap">
          <div class="form-group mb-0 flex-1" style="min-width: 200px;">
            <label class="form-label">Buscar pedido</label>
            <input v-model="form.search" type="text" class="form-control" placeholder="ID do pedido ou nome do cliente..." />
          </div>

          <div class="form-group mb-0" style="min-width: 180px;">
            <label class="form-label">Filtrar por Status</label>
            <select v-model="form.status" class="form-control">
              <option value="">Todos</option>
              <option value="aguardando_pagamento">Aguardando Pagamento</option>
              <option value="em_separacao">Em Separação</option>
              <option value="em_envio">Em Envio</option>
              <option value="enviado">Enviado</option>
              <option value="entregue">Entregue</option>
              <option value="cancelado">Cancelado</option>
              <option value="devolvido">Devolvido</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary" style="height: 38px;">Filtrar</button>
          <button type="button" @click="resetFilters" class="btn btn-secondary" style="height: 38px;">Limpar</button>
        </form>
      </div>
    </div>

    <!-- Tabela -->
    <div class="card">
      <div class="card-body" style="padding:0;">
        <div v-if="orders.data.length === 0" class="alert alert-warning" style="margin: 1.5rem;">
          Nenhum pedido encontrado.
        </div>
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Gateway</th>
                <th>Status</th>
                <th style="width: 100px;">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ord in orders.data" :key="ord.id">
                <td class="font-mono font-bold">#{{ ord.id }}</td>
                <td style="font-size: 0.8125rem;">{{ formatDate(ord.created_at) }}</td>
                <td>
                  <div v-if="ord.cliente">
                    <strong>{{ ord.cliente.nome_completo }}</strong>
                    <div class="text-secondary" style="font-size: 0.75rem;">CPF: {{ ord.cliente.cpf }}</div>
                  </div>
                  <span v-else class="text-danger">Cliente excluído</span>
                </td>
                <td class="font-bold">
                  R$ {{ formatMoney(ord.total) }}
                </td>
                <td>
                  <span class="badge badge-secondary">{{ ord.gateway_pagamento || 'Pix Manual' }}</span>
                </td>
                <td>
                  <span class="badge" :class="getStatusBadgeClass(ord.status)">
                    {{ getStatusLabel(ord.status) }}
                  </span>
                </td>
                <td>
                  <Link :href="route('admin.orders.show', ord.id)" class="btn btn-secondary btn-sm" style="padding: 4px 8px;">
                    Detalhes
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginação -->
        <div v-if="orders.links && orders.links.length > 3" class="flex gap-2" style="padding: 1.5rem; justify-content: flex-end;">
          <Link v-for="(link, idx) in orders.links" :key="idx" 
                :href="link.url || '#'" 
                class="btn btn-secondary btn-sm" 
                :class="{ active: link.active, disabled: !link.url }"
                v-html="link.label">
          </Link>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  orders: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) }
})

const form = ref({
  search: props.filters.search || '',
  status: props.filters.status || ''
})

function handleSearch() {
  router.get(route('admin.orders.index'), form.value, { preserveState: true })
}

function resetFilters() {
  form.value = { search: '', status: '' }
  handleSearch()
}

function formatMoney(value) {
  if (value === null || value === undefined) return '0,00'
  return parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleString('pt-BR')
}

function getStatusLabel(status) {
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

function getStatusBadgeClass(status) {
  switch (status) {
    case 'aguardando_pagamento': return 'badge-warning'
    case 'em_separacao': return 'badge-secondary'
    case 'em_envio': return 'badge-secondary'
    case 'enviado': return 'badge-primary'
    case 'entregue': return 'badge-success'
    case 'cancelado': return 'badge-danger'
    default: return 'badge-secondary'
  }
}
</script>

<style scoped>
.disabled {
  pointer-events: none;
  opacity: 0.5;
}
.active {
  background: var(--color-brand);
  color: white;
  border-color: var(--color-brand);
}
</style>
