<template>
  <AdminLayout title="Controle de Estoque">
    <template #breadcrumb>
      <span>Operações / Estoque</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">📊 Controle de Estoque</h1>
        <p class="text-secondary mt-1">Gerencie os níveis de estoque próprio em tempo real e realize ajustes com auditoria completa.</p>
      </div>
    </div>

    <!-- Filtros de Alerta e Busca -->
    <div class="grid-4 gap-6 mb-6">
      <div class="card cursor-pointer" @click="setAlertaFilter('')" :style="!form.alerta ? 'border-color: var(--color-brand);' : ''">
        <div class="card-body">
          <div style="font-size: 1.5rem; margin-bottom: 0.25rem;">📦</div>
          <div class="text-secondary font-bold">Todos Próprios</div>
        </div>
      </div>
      <div class="card cursor-pointer" @click="setAlertaFilter('min')" :style="form.alerta === 'min' ? 'border-color: var(--color-warning);' : ''">
        <div class="card-body">
          <div style="font-size: 1.5rem; margin-bottom: 0.25rem;">⚠️</div>
          <div class="text-warning font-bold">Estoque Mínimo</div>
        </div>
      </div>
      <div class="card cursor-pointer" @click="setAlertaFilter('critico')" :style="form.alerta === 'critico' ? 'border-color: var(--color-danger);' : ''">
        <div class="card-body">
          <div style="font-size: 1.5rem; margin-bottom: 0.25rem;">🔴</div>
          <div class="text-danger font-bold">Estoque Crítico</div>
        </div>
      </div>
    </div>

    <!-- Barra de busca -->
    <div class="card mb-6">
      <div class="card-body">
        <form @submit.prevent="handleSearch" class="flex gap-4 items-end">
          <div class="form-group mb-0 flex-1">
            <label class="form-label">Buscar variação</label>
            <input v-model="form.search" type="text" class="form-control" placeholder="Buscar por produto ou SKU..." />
          </div>
          <button type="submit" class="btn btn-primary" style="height: 38px;">Buscar</button>
          <button type="button" @click="resetFilters" class="btn btn-secondary" style="height: 38px;">Limpar</button>
        </form>
      </div>
    </div>

    <!-- Lista de Estoque -->
    <div class="card">
      <div class="card-body" style="padding: 0;">
        <div v-if="stock.data.length === 0" class="alert alert-success" style="margin: 1.5rem;">
          Estoque em conformidade ou nenhum item próprio encontrado com esses filtros.
        </div>
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Produto / Variação</th>
                <th>SKU</th>
                <th>Tamanho / Cor</th>
                <th>Estoque Atual</th>
                <th>Reservado</th>
                <th>Mínimo / Crítico</th>
                <th>Status</th>
                <th style="width: 220px;">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in stock.data" :key="item.id">
                <td>
                  <strong>{{ item.produto_nome }}</strong>
                </td>
                <td class="font-mono text-secondary">{{ item.sku }}</td>
                <td>
                  <span class="badge badge-secondary">{{ item.tamanho || '—' }}</span>
                  <span v-if="item.cor" class="badge badge-secondary ml-1">{{ item.cor }}</span>
                </td>
                <td>
                  <span class="font-bold" :class="getStockClass(item)">{{ item.estoque_quantidade }}</span>
                </td>
                <td class="text-secondary">{{ item.estoque_reservado }}</td>
                <td class="text-secondary font-mono">
                  Min: {{ item.estoque_minimo || '—' }} / Crit: {{ item.estoque_critico || '—' }}
                </td>
                <td>
                  <span v-if="item.estoque_quantidade <= item.estoque_critico" class="badge badge-danger">🔴 Crítico</span>
                  <span v-else-if="item.estoque_quantidade <= item.estoque_minimo" class="badge badge-warning">⚠️ Mínimo</span>
                  <span v-else class="badge badge-success">OK</span>
                </td>
                <td>
                  <div class="flex gap-2">
                    <button @click="openAdjustModal(item)" class="btn btn-secondary btn-sm" style="padding: 4px 8px;">Ajustar</button>
                    <button @click="viewHistory(item)" class="btn btn-primary btn-sm" style="padding: 4px 8px;">Histórico</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginação -->
        <div v-if="stock.links && stock.links.length > 3" class="flex gap-2" style="padding: 1.5rem; justify-content: flex-end;">
          <Link v-for="(link, idx) in stock.links" :key="idx" 
                :href="link.url || '#'" 
                class="btn btn-secondary btn-sm" 
                :class="{ active: link.active, disabled: !link.url }"
                v-html="link.label">
          </Link>
        </div>
      </div>
    </div>

    <!-- Modal de Ajuste manual -->
    <div v-if="showAdjustModal" class="modal-backdrop" @click.self="showAdjustModal = false">
      <div class="modal-box">
        <h2 class="modal-title">Ajustar Estoque Físico</h2>
        <form @submit.prevent="saveAdjustment">
          <div class="info-box mb-4">
            <strong>SKU:</strong> {{ activeItem.sku }} <br/>
            <strong>Produto:</strong> {{ activeItem.produto_nome }} ({{ activeItem.tamanho }}/{{ activeItem.cor }})
          </div>

          <div class="form-group">
            <label class="form-label">Nova quantidade física em estoque</label>
            <input v-model.number="adjustForm.estoque_quantidade" type="number" class="form-control" min="0" required />
          </div>

          <div class="form-group">
            <label class="form-label">Justificativa do ajuste (obrigatório)</label>
            <textarea v-model="adjustForm.motivo" class="form-control" rows="3" placeholder="Ex: Inventário de rotina, quebra de produto, erro de entrada do fornecedor..." required></textarea>
          </div>

          <div class="flex gap-3 mt-6" style="justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" @click="showAdjustModal = false">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar Ajuste</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Histórico de Movimentações -->
    <div v-if="showHistoryModal" class="modal-backdrop" @click.self="showHistoryModal = false">
      <div class="modal-box" style="max-width: 700px;">
        <h2 class="modal-title">Auditoria de Movimentações — SKU {{ activeItem.sku }}</h2>
        
        <div class="table-wrapper" style="max-height: 400px; overflow-y: auto;">
          <table>
            <thead>
              <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Qtd</th>
                <th>Saldo</th>
                <th>Motivo / Resp.</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in historyList" :key="log.id">
                <td style="font-size: 0.75rem;">{{ formatDate(log.created_at) }}</td>
                <td>
                  <span class="badge" :class="getLogBadgeClass(log.tipo)">{{ log.tipo }}</span>
                </td>
                <td class="font-bold" :class="log.quantidade > 0 ? 'text-success' : 'text-danger'">
                  {{ log.quantidade > 0 ? '+' : '' }}{{ log.quantidade }}
                </td>
                <td class="font-mono text-secondary">{{ log.estoque_depois }}</td>
                <td>
                  <div style="font-size: 0.8125rem;">{{ log.motivo || '—' }}</div>
                  <div v-if="log.funcionario" class="text-muted" style="font-size: 0.75rem;">Por: {{ log.funcionario.nome }}</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex gap-3 mt-6" style="justify-content: flex-end;">
          <button type="button" class="btn btn-secondary" @click="showHistoryModal = false">Fechar</button>
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
  stock: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) }
})

const form = ref({
  search: props.filters.search || '',
  alerta: props.filters.alerta || ''
})

const showAdjustModal = ref(false)
const showHistoryModal = ref(false)
const activeItem = ref(null)
const historyList = ref([])

const adjustForm = ref({
  estoque_quantidade: 0,
  motivo: ''
})

function setAlertaFilter(value) {
  form.value.alerta = value
  handleSearch()
}

function handleSearch() {
  router.get(route('admin.stock.index'), form.value, { preserveState: true })
}

function resetFilters() {
  form.value = { search: '', alerta: '' }
  handleSearch()
}

function getStockClass(item) {
  if (item.estoque_quantidade <= item.estoque_critico) return 'text-danger font-bold'
  if (item.estoque_quantidade <= item.estoque_minimo) return 'text-warning font-bold'
  return 'text-success font-bold'
}

function openAdjustModal(item) {
  activeItem.value = item
  adjustForm.value = { estoque_quantidade: item.estoque_quantidade, motivo: '' }
  showAdjustModal.value = true
}

function saveAdjustment() {
  router.post(route('admin.stock.adjust', activeItem.value.id), adjustForm.value, {
    onSuccess: () => {
      showAdjustModal.value = false
    }
  })
}

async function viewHistory(item) {
  activeItem.value = item
  try {
    const response = await fetch(route('admin.stock.history', item.id))
    historyList.value = await response.json()
    showHistoryModal.value = true
  } catch (error) {
    alert('Erro ao carregar histórico de auditoria do estoque.')
  }
}

function getLogBadgeClass(tipo) {
  switch (tipo) {
    case 'entrada': return 'badge-success'
    case 'baixa_confirmada': return 'badge-primary'
    case 'reserva': return 'badge-secondary'
    case 'liberacao_reserva': return 'badge-warning'
    case 'ajuste_manual': return 'badge-danger'
    default: return 'badge-secondary'
  }
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleString('pt-BR')
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
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
}

.modal-box {
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: 2rem;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 24px 64px rgba(0,0,0,0.4);
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin-bottom: 1.5rem;
}
</style>
