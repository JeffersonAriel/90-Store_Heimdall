<template>
  <AdminLayout title="Gestão de Pedidos">
    <template #breadcrumb>
      <span class="text-muted">Operações</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Pedidos</span>
    </template>

    <!-- Page Header -->
    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">
          <span class="page-title-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
              <line x1="3" y1="6" x2="21" y2="6"/>
              <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
          </span>
          Gestão de Pedidos
        </h1>
        <p class="page-subtitle">Gerencie pedidos, avance status e confirme pagamentos.</p>
      </div>
      <div class="page-actions">
        <Link :href="route('admin.orders.create')" class="btn btn-primary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Novo Pedido
        </Link>
      </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-6">
      <div class="card-body">
        <form @submit.prevent="handleSearch" class="flex gap-3 items-end flex-wrap">
          <div class="form-group flex-1" style="min-width: 220px; margin-bottom: 0;">
            <label class="form-label">Buscar pedido</label>
            <div class="form-input-wrap">
              <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
              <input
                v-model="form.search"
                type="text"
                class="form-input"
                placeholder="ID do pedido ou nome do cliente..."
              />
            </div>
          </div>

          <div class="form-group" style="min-width: 200px; margin-bottom: 0;">
            <label class="form-label">Status</label>
            <select v-model="form.status" class="form-select">
              <option value="">Todos os status</option>
              <option value="aguardando_pagamento">Aguardando Pagamento</option>
              <option value="em_separacao">Em Separação</option>
              <option value="em_envio">Em Envio</option>
              <option value="enviado">Enviado</option>
              <option value="entregue">Entregue</option>
              <option value="cancelado">Cancelado</option>
              <option value="devolvido">Devolvido</option>
            </select>
          </div>

          <div class="flex gap-2" style="flex-shrink: 0;">
            <button type="submit" class="btn btn-primary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
              Filtrar
            </button>
            <button type="button" @click="resetFilters" class="btn btn-secondary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"/>
                <path d="M3.51 15a9 9 0 1 0 .49-3.96"/>
              </svg>
              Limpar
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Table Card -->
    <div class="card">
      <!-- Empty State -->
      <div v-if="orders.data.length === 0" class="empty-state">
        <div class="empty-state-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
        </div>
        <p class="empty-state-title">Nenhum pedido encontrado</p>
        <p class="empty-state-desc">Tente ajustar os filtros ou aguarde novos pedidos.</p>
      </div>

      <!-- Table -->
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Pedido</th>
              <th>Data</th>
              <th>Cliente</th>
              <th>Total</th>
              <th>Gateway</th>
              <th>Status</th>
              <th style="width: 90px; text-align: right;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ord in orders.data" :key="ord.id">
              <td data-label="Pedido">
                <span class="font-mono font-bold" style="font-size: 0.8125rem;">#{{ ord.id }}</span>
              </td>
              <td data-label="Data" class="text-secondary" style="font-size: 0.8125rem; white-space: nowrap;">
                {{ formatDate(ord.created_at) }}
              </td>
              <td data-label="Cliente">
                <div v-if="ord.cliente">
                  <div class="font-semibold" style="font-size: 0.875rem;">{{ ord.cliente.nome_completo }}</div>
                  <div class="text-muted font-mono" style="font-size: 0.75rem;">{{ ord.cliente.cpf }}</div>
                </div>
                <span v-else class="badge badge-danger">Cliente excluído</span>
              </td>
              <td data-label="Total">
                <span class="font-bold">R$ {{ formatMoney(ord.total) }}</span>
              </td>
              <td data-label="Gateway">
                <span class="badge badge-secondary">{{ ord.gateway_pagamento || 'Pix Manual' }}</span>
              </td>
              <td data-label="Status">
                <span class="badge" :class="getStatusBadgeClass(ord.status)">
                  <span class="badge-dot"></span>
                  {{ getStatusLabel(ord.status) }}
                </span>
              </td>
              <td data-label="Ações" style="text-align: right;">
                <Link :href="route('admin.orders.show', ord.id)" class="btn btn-secondary btn-sm">
                  Detalhes
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="orders.links && orders.links.length > 3" class="pagination">
        <Link
          v-for="(link, idx) in orders.links"
          :key="idx"
          :href="link.url || '#'"
          class="page-btn"
          :class="{ active: link.active, disabled: !link.url }"
          v-html="link.label"
        />
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  orders:  { type: Object, required: true },
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
  return new Date(dateStr).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

function getStatusLabel(status) {
  const map = {
    aguardando_pagamento: 'Aguardando Pagamento',
    em_separacao:         'Em Separação',
    em_envio:             'Em Envio',
    enviado:              'Enviado',
    entregue:             'Entregue',
    cancelado:            'Cancelado',
    devolvido:            'Devolvido'
  }
  return map[status] || status
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'aguardando_pagamento': return 'badge-warning'
    case 'em_separacao':         return 'badge-secondary'
    case 'em_envio':             return 'badge-info'
    case 'enviado':              return 'badge-primary'
    case 'entregue':             return 'badge-success'
    case 'cancelado':            return 'badge-danger'
    case 'devolvido':            return 'badge-danger'
    default:                     return 'badge-secondary'
  }
}
</script>

<style scoped>
.page-btn.disabled { pointer-events: none; opacity: 0.35; }
</style>
