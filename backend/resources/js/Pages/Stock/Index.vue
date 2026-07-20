<template>
  <AdminLayout title="Controle de Estoque">
    <template #breadcrumb>
      <span class="text-muted">Operações</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Estoque</span>
    </template>

    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">
          <span class="page-title-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            </svg>
          </span>
          Controle de Estoque
        </h1>
        <p class="page-subtitle">Gerencie os níveis de estoque e realize ajustes com auditoria completa.</p>
      </div>
    </div>

    <!-- KPI Filter Cards -->
    <div class="kpi-grid mb-6">
      <div class="kpi-card" style="cursor: pointer; grid-column: span 1;" @click="setAlertaFilter('')" :class="{ 'kpi-card--active': !form.alerta }">
        <div class="kpi-accent-bar kpi-accent-bar--brand"></div>
        <div class="kpi-card-header">
          <div class="kpi-icon kpi-icon--brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            </svg>
          </div>
          <span v-if="!form.alerta" class="kpi-trend kpi-trend--flat">Selecionado</span>
        </div>
        <div class="kpi-value">{{ counts.total }}</div>
        <div class="kpi-label">Todos Próprios</div>
      </div>

      <div class="kpi-card" style="cursor: pointer;" @click="setAlertaFilter('min')" :class="{ 'kpi-card--active': form.alerta === 'min' }">
        <div class="kpi-accent-bar kpi-accent-bar--warning"></div>
        <div class="kpi-card-header">
          <div class="kpi-icon kpi-icon--warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
              <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
          </div>
          <span v-if="form.alerta === 'min'" class="kpi-trend kpi-trend--flat">Selecionado</span>
        </div>
        <div class="kpi-value text-warning">{{ counts.min }}</div>
        <div class="kpi-label">Estoque Mínimo</div>
      </div>

      <div class="kpi-card" style="cursor: pointer;" @click="setAlertaFilter('critico')" :class="{ 'kpi-card--active': form.alerta === 'critico' }">
        <div class="kpi-accent-bar kpi-accent-bar--danger"></div>
        <div class="kpi-card-header">
          <div class="kpi-icon kpi-icon--danger">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <span v-if="form.alerta === 'critico'" class="kpi-trend kpi-trend--flat">Selecionado</span>
        </div>
        <div class="kpi-value text-danger">{{ counts.critico }}</div>
        <div class="kpi-label">Estoque Crítico</div>
      </div>

      <!-- Empty 4th slot for grid -->
      <div></div>
    </div>

    <!-- Search Filter -->
    <div class="card mb-6">
      <div class="card-body">
        <form @submit.prevent="handleSearch" class="flex gap-3 items-end flex-wrap">
          <div class="form-group flex-1" style="min-width: 220px; margin-bottom: 0;">
            <label class="form-label">Buscar variação</label>
            <div class="form-input-wrap">
              <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
              <input v-model="form.search" type="text" class="form-input" placeholder="Buscar por produto ou SKU..." />
            </div>
          </div>
          <div class="flex gap-2" style="flex-shrink: 0;">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <button type="button" @click="resetFilters" class="btn btn-secondary">Limpar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Table Card -->
    <div class="card">
      <div v-if="stock.data.length === 0" class="empty-state">
        <div class="empty-state-icon" style="background: var(--color-success-bg);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-success);">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
        <p class="empty-state-title">Estoque em conformidade</p>
        <p class="empty-state-desc">Nenhum item encontrado com os filtros atuais.</p>
      </div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Produto / Variação</th>
              <th>SKU</th>
              <th>Tam / Cor</th>
              <th style="text-align: center;">Atual</th>
              <th style="text-align: center;">Reservado</th>
              <th>Mín / Crít</th>
              <th>Status</th>
              <th style="width: 180px; text-align: right;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in stock.data" :key="item.id">
              <td data-label="Produto"><strong>{{ item.produto_nome }}</strong></td>
              <td data-label="SKU" class="font-mono text-secondary" style="font-size: 0.8125rem;">{{ item.sku }}</td>
              <td data-label="Tam/Cor">
                <span class="badge badge-secondary">{{ item.tamanho || '—' }}</span>
                <span v-if="item.cor" class="badge badge-secondary" style="margin-left: 0.25rem;">{{ item.cor }}</span>
              </td>
              <td data-label="Atual" style="text-align: center;">
                <span class="font-bold" :class="getStockClass(item)">{{ item.estoque_quantidade }}</span>
              </td>
              <td data-label="Reservado" style="text-align: center;" class="text-secondary">{{ item.estoque_reservado }}</td>
              <td data-label="Mín/Crít" class="text-muted font-mono" style="font-size: 0.8125rem;">
                {{ item.estoque_minimo || '—' }} / {{ item.estoque_critico || '—' }}
              </td>
              <td data-label="Status">
                <span v-if="item.estoque_quantidade <= item.estoque_critico" class="badge badge-stock-critical">
                  <span class="badge-dot"></span>Crítico
                </span>
                <span v-else-if="item.estoque_quantidade <= item.estoque_minimo" class="badge badge-stock-min">
                  <span class="badge-dot"></span>Mínimo
                </span>
                <span v-else class="badge badge-stock-ok">
                  <span class="badge-dot"></span>OK
                </span>
              </td>
              <td data-label="Ações" style="text-align: right;">
                <div class="flex gap-2 justify-end">
                  <button @click="openAdjustModal(item)" class="btn btn-secondary btn-sm">Ajustar</button>
                  <button @click="viewHistory(item)" class="btn btn-ghost btn-sm">Histórico</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="stock.links && stock.links.length > 3" class="pagination">
        <Link v-for="(link, idx) in stock.links" :key="idx" :href="link.url || '#'" class="page-btn" :class="{ active: link.active, disabled: !link.url }" v-html="link.label" />
      </div>
    </div>

    <!-- Modal Ajuste -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showAdjustModal" class="modal-overlay" @click.self="showAdjustModal = false">
          <div class="modal modal-sm">
            <div class="modal-header">
              <h3 class="modal-title">Ajustar Estoque Físico</h3>
              <button class="btn-icon" @click="showAdjustModal = false" aria-label="Fechar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="info-box mb-4">
                <div class="font-semibold" style="font-size: 0.8125rem;">{{ activeItem?.produto_nome }}</div>
                <div class="text-muted font-mono" style="font-size: 0.75rem;">SKU: {{ activeItem?.sku }} · {{ activeItem?.tamanho }}/{{ activeItem?.cor }}</div>
              </div>
              <form @submit.prevent="saveAdjustment" id="adjustForm" class="flex flex-col gap-4">
                <div class="form-group">
                  <label class="form-label form-label-required">Nova quantidade física</label>
                  <input v-model.number="adjustForm.estoque_quantidade" type="number" class="form-input" min="0" required />
                </div>
                <div class="form-group">
                  <label class="form-label form-label-required">Justificativa do ajuste</label>
                  <textarea v-model="adjustForm.motivo" class="form-textarea" rows="3" placeholder="Ex: Inventário de rotina, quebra de produto..." required></textarea>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showAdjustModal = false">Cancelar</button>
              <button type="submit" form="adjustForm" class="btn btn-primary">Salvar Ajuste</button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- Modal Histórico -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showHistoryModal" class="modal-overlay" @click.self="showHistoryModal = false">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h3 class="modal-title">
                Auditoria de Movimentações
                <span class="text-muted font-mono" style="font-weight: 400; font-size: 0.875rem;"> — {{ activeItem?.sku }}</span>
              </h3>
              <button class="btn-icon" @click="showHistoryModal = false" aria-label="Fechar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
            <div class="modal-body" style="padding: 0;">
              <div class="table-wrapper" style="max-height: 420px; overflow-y: auto;">
                <table>
                  <thead>
                    <tr>
                      <th>Data</th>
                      <th>Tipo</th>
                      <th style="text-align: center;">Qtd</th>
                      <th style="text-align: center;">Saldo</th>
                      <th>Motivo / Responsável</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="log in historyList" :key="log.id">
                      <td style="font-size: 0.75rem; white-space: nowrap;">{{ formatDate(log.created_at) }}</td>
                      <td><span class="badge" :class="getLogBadgeClass(log.tipo)">{{ log.tipo }}</span></td>
                      <td style="text-align: center;">
                        <span class="font-bold" :class="log.quantidade > 0 ? 'text-success' : 'text-danger'">
                          {{ log.quantidade > 0 ? '+' : '' }}{{ log.quantidade }}
                        </span>
                      </td>
                      <td style="text-align: center;" class="font-mono text-secondary">{{ log.estoque_depois }}</td>
                      <td>
                        <div style="font-size: 0.8125rem;">{{ log.motivo || '—' }}</div>
                        <div v-if="log.funcionario" class="text-muted" style="font-size: 0.75rem;">{{ log.funcionario.nome }}</div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-secondary" @click="showHistoryModal = false">Fechar</button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  stock:   { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  counts:  { type: Object, default: () => ({ total: 0, min: 0, critico: 0 }) }
})

const form = ref({ search: props.filters.search || '', alerta: props.filters.alerta || '' })

const showAdjustModal  = ref(false)
const showHistoryModal = ref(false)
const activeItem       = ref(null)
const historyList      = ref([])

const adjustForm = ref({ estoque_quantidade: 0, motivo: '' })

function setAlertaFilter(value) { form.value.alerta = value; handleSearch() }
function handleSearch() { router.get(route('admin.stock.index'), form.value, { preserveState: true }) }
function resetFilters() { form.value = { search: '', alerta: '' }; handleSearch() }

function getStockClass(item) {
  if (item.estoque_quantidade <= item.estoque_critico) return 'text-danger'
  if (item.estoque_quantidade <= item.estoque_minimo)  return 'text-warning'
  return 'text-success'
}

function openAdjustModal(item) {
  activeItem.value = item
  adjustForm.value = { estoque_quantidade: item.estoque_quantidade, motivo: '' }
  showAdjustModal.value = true
}

function saveAdjustment() {
  router.post(route('admin.stock.adjust', activeItem.value.id), adjustForm.value, { onSuccess: () => { showAdjustModal.value = false } })
}

async function viewHistory(item) {
  activeItem.value = item
  try {
    const response = await fetch(route('admin.stock.history', item.id))
    historyList.value = await response.json()
    showHistoryModal.value = true
  } catch { alert('Erro ao carregar histórico.') }
}

function getLogBadgeClass(tipo) {
  const map = { entrada: 'badge-success', baixa_confirmada: 'badge-primary', reserva: 'badge-secondary', liberacao_reserva: 'badge-warning', ajuste_manual: 'badge-danger' }
  return map[tipo] || 'badge-secondary'
}

function formatDate(d) { return new Date(d).toLocaleString('pt-BR') }
</script>

<style scoped>
.page-btn.disabled { pointer-events: none; opacity: 0.35; }
.kpi-card--active { border-color: var(--color-brand) !important; box-shadow: 0 0 0 2px var(--color-brand-glow); }
</style>
