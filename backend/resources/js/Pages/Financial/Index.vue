<template>
  <AdminLayout title="Controle Financeiro">
    <template #breadcrumb>
      <span class="text-muted">Operações</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Financeiro</span>
    </template>

    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">
          <span class="page-title-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="1" x2="12" y2="23"/>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
          </span>
          Controle Financeiro
        </h1>
        <p class="page-subtitle">Fluxo de caixa, contas bancárias e controle de contas a pagar/receber.</p>
      </div>
      <div class="page-actions">
        <button class="btn btn-primary" @click="openCreateModal">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Novo Lançamento
        </button>
        <a :href="route('admin.financial.export-bi')" target="_blank" class="btn btn-secondary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
          </svg>
          Exportar BI
        </a>
      </div>
    </div>

    <!-- KPI Métricas -->
    <div class="kpi-grid mb-6">
      <div class="kpi-card">
        <div class="kpi-accent-bar" :class="metrics.lucro_liquido >= 0 ? 'kpi-accent-bar--success' : 'kpi-accent-bar--danger'"></div>
        <div class="kpi-card-header">
          <div class="kpi-icon" :class="metrics.lucro_liquido >= 0 ? 'kpi-icon--success' : 'kpi-icon--danger'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
          </div>
          <span class="kpi-trend" :class="metrics.lucro_liquido >= 0 ? 'kpi-trend--up' : 'kpi-trend--down'">
            {{ metrics.lucro_liquido >= 0 ? 'Positivo' : 'Negativo' }}
          </span>
        </div>
        <div class="kpi-value font-mono" :class="metrics.lucro_liquido >= 0 ? 'text-success' : 'text-danger'">
          R$ {{ formatMoney(metrics.lucro_liquido) }}
        </div>
        <div class="kpi-label">Saldo Líquido Realizado</div>
        <div class="kpi-hint">Receitas confirmadas menos despesas pagas</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-accent-bar kpi-accent-bar--brand"></div>
        <div class="kpi-card-header">
          <div class="kpi-icon kpi-icon--brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
            </svg>
          </div>
        </div>
        <div class="kpi-value font-mono">R$ {{ formatMoney(metrics.contas_receber) }}</div>
        <div class="kpi-label">Previsão de Entradas</div>
        <div class="kpi-hint">Aguardando Pix/Boleto</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-accent-bar kpi-accent-bar--danger"></div>
        <div class="kpi-card-header">
          <div class="kpi-icon kpi-icon--danger">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/>
            </svg>
          </div>
        </div>
        <div class="kpi-value font-mono text-danger">R$ {{ formatMoney(metrics.contas_pagar) }}</div>
        <div class="kpi-label">Previsão de Saídas</div>
        <div class="kpi-hint">Aguardando liquidação</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-accent-bar kpi-accent-bar--warning"></div>
        <div class="kpi-card-header">
          <div class="kpi-icon kpi-icon--accent">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            </svg>
          </div>
        </div>
        <div class="kpi-value font-mono text-warning">R$ {{ formatMoney(metrics.valor_estoque_custo) }}</div>
        <div class="kpi-label">Patrimônio (Custo)</div>
        <div class="kpi-hint">Capital investido em estoque</div>
      </div>
    </div>

    <!-- Contas Bancárias -->
    <div v-if="accounts.length > 0" class="grid-3 mb-6" style="gap: 1.5rem;">
      <div v-for="acc in accounts" :key="acc.id" class="card">
        <div class="card-body">
          <div class="flex items-center justify-between mb-2">
            <div class="text-muted" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">{{ acc.banco }}</div>
            <span v-if="!acc.agencia && !acc.conta" class="badge badge-primary" style="font-size: 0.65rem;">Gateway</span>
          </div>
          <h3 class="font-semibold" style="font-size: 1rem;">{{ acc.nome }}</h3>
          <div class="font-mono font-bold mt-2" :class="acc.saldo >= 0 ? 'text-success' : 'text-danger'" style="font-size: 1.5rem;">
            R$ {{ formatMoney(acc.saldo) }}
          </div>
          <div v-if="acc.agencia || acc.conta" class="text-muted" style="font-size: 0.7rem; margin-top: 0.25rem;">
            Ag: {{ acc.agencia || '—' }} / Cc: {{ acc.conta || '—' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Extrato de Transações -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">
          <span class="card-title-icon" style="background: var(--color-brand-surface); color: var(--color-brand);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
              <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
              <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
            </svg>
          </span>
          Extrato de Transações
        </h3>
      </div>

      <!-- Filtros -->
      <div class="card-body" style="border-bottom: 1px solid var(--color-border);">
        <form @submit.prevent="handleFilter" class="flex flex-wrap gap-2 items-end">
          <div class="form-input-wrap flex-1" style="min-width: 180px;">
            <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" v-model="form.search" placeholder="Buscar por descrição, fornecedor..." class="form-input" />
          </div>
          <select v-model="form.tipo" class="form-select" style="width: 140px;">
            <option value="">Todos Tipos</option>
            <option value="entrada">Receitas</option>
            <option value="saida">Despesas</option>
          </select>
          <select v-model="form.status" class="form-select" style="width: 140px;">
            <option value="">Todos Status</option>
            <option value="conciliado">Conciliados</option>
            <option value="pendente">Pendentes</option>
          </select>
          <select v-model="form.categoria" class="form-select" style="width: 150px;">
            <option value="">Todas Cat.</option>
            <option value="venda">Vendas</option>
            <option value="compra_fornecedor">Compra Fornecedor</option>
            <option value="frete">Frete</option>
            <option value="marketing">Marketing</option>
            <option value="aluguel">Aluguel</option>
            <option value="impostos">Impostos</option>
            <option value="salarios">Salários</option>
            <option value="outros">Outros</option>
          </select>
          <select v-model="form.conta_id" class="form-select" style="width: 140px;">
            <option value="">Todas Contas</option>
            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.nome }}</option>
          </select>
          <div class="flex items-center gap-1">
            <input type="date" v-model="form.data_inicio" class="form-input" style="width: 140px;" title="Data Início" />
            <span class="text-muted" style="font-size: 0.75rem;">a</span>
            <input type="date" v-model="form.data_fim" class="form-input" style="width: 140px;" title="Data Fim" />
          </div>
          <button type="submit" class="btn btn-primary">Filtrar</button>
          <button type="button" @click="clearFilters" class="btn btn-secondary">Limpar</button>
        </form>
      </div>

      <!-- Tabela -->
      <div v-if="transactions.data.length === 0" class="empty-state">
        <div class="empty-state-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="1" x2="12" y2="23"/>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
        </div>
        <p class="empty-state-title">Nenhuma transação encontrada</p>
        <p class="empty-state-desc">Tente ajustar os filtros ou adicione um novo lançamento.</p>
      </div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Data</th>
              <th>Descrição / Referência</th>
              <th>Conta</th>
              <th>Categoria</th>
              <th>Valor</th>
              <th>Status</th>
              <th style="width: 160px; text-align: right;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in transactions.data" :key="t.id">
              <td data-label="Data" style="font-size: 0.8125rem; white-space: nowrap;">{{ formatDate(t.data_lancamento) }}</td>
              <td data-label="Descrição">
                <div class="font-semibold" style="font-size: 0.875rem;">{{ t.descricao }}</div>
                <div v-if="t.pedido" class="text-muted font-mono" style="font-size: 0.75rem;">Pedido #{{ t.pedido.id }}</div>
                <div v-if="t.fornecedor" class="text-secondary" style="font-size: 0.75rem;">{{ t.fornecedor.razao_social }}</div>
                <div v-if="t.comprovante" class="mt-1">
                  <a :href="t.comprovante.startsWith('http') ? t.comprovante : ('https://pay.infinitepay.io/receipt/' + t.comprovante)" target="_blank" style="font-size: 0.75rem; font-weight: 600; padding: 2px 8px; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid #10b981; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    </svg>
                    Abrir Comprovante ↗
                  </a>
                </div>
              </td>
              <td data-label="Conta">
                <span class="badge badge-secondary">{{ t.conta ? t.conta.nome : '—' }}</span>
              </td>
              <td data-label="Categoria">
                <span class="badge badge-secondary">{{ t.categoria }}</span>
              </td>
              <td data-label="Valor">
                <span class="font-bold font-mono" :class="t.tipo === 'entrada' ? 'text-success' : 'text-danger'">
                  {{ t.tipo === 'entrada' ? '+' : '-' }} R$ {{ formatMoney(t.valor) }}
                </span>
              </td>
              <td data-label="Status">
                <span :class="t.conciliado ? 'badge badge-success' : 'badge badge-warning'">
                  <span class="badge-dot"></span>{{ t.conciliado ? 'Conciliado' : 'Aguardando' }}
                </span>
              </td>
              <td data-label="Ações" style="text-align: right;">
                <div class="flex gap-1 justify-end">
                  <button v-if="!t.conciliado" @click="reconcile(t.id)" class="btn btn-ghost btn-sm" title="Conciliar">Conciliar</button>
                  <button @click="openEditModal(t)" class="btn-icon" title="Editar">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                  </button>
                  <button @click="deleteTransaction(t.id)" class="btn-icon" title="Excluir" style="color: var(--color-danger-light);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="transactions.links && transactions.links.length > 3" class="pagination">
        <Link v-for="(link, idx) in transactions.links" :key="idx" :href="link.url || '#'" class="page-btn" :class="{ active: link.active, disabled: !link.url }" v-html="link.label" />
      </div>
    </div>

    <!-- Modal Lançamento -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
          <div class="modal modal-md">
            <div class="modal-header">
              <h3 class="modal-title">{{ isEditingModal ? 'Editar Lançamento' : 'Novo Lançamento Manual' }}</h3>
              <button @click="showModal = false" class="btn-icon" aria-label="Fechar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
            <form @submit.prevent="submitModalForm" id="lancamentoForm">
              <div class="modal-body flex flex-col gap-4">
                <div class="grid-2">
                  <div class="form-group">
                    <label class="form-label form-label-required">Tipo</label>
                    <select v-model="modalForm.tipo" class="form-select" required>
                      <option value="entrada">Receita (Entrada)</option>
                      <option value="saida">Despesa (Saída)</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label form-label-required">Categoria</label>
                    <select v-model="modalForm.categoria" class="form-select" required>
                      <option value="venda">Vendas</option>
                      <option value="compra_fornecedor">Compra Fornecedor</option>
                      <option value="frete">Frete</option>
                      <option value="marketing">Marketing</option>
                      <option value="aluguel">Aluguel</option>
                      <option value="impostos">Impostos</option>
                      <option value="salarios">Salários</option>
                      <option value="outros">Outros</option>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label form-label-required">Descrição</label>
                  <input v-model="modalForm.descricao" type="text" class="form-input" placeholder="Ex: Pagamento da conta de energia" required />
                </div>
                <div class="grid-2">
                  <div class="form-group">
                    <label class="form-label form-label-required">Valor (R$)</label>
                    <input v-model="modalForm.valor" type="number" step="0.01" min="0.01" class="form-input" placeholder="0,00" required />
                  </div>
                  <div class="form-group">
                    <label class="form-label form-label-required">Data de Competência</label>
                    <input v-model="modalForm.data_lancamento" type="date" class="form-input" required />
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Conta Bancária</label>
                  <select v-model="modalForm.conta_id" class="form-select">
                    <option :value="null">Nenhuma conta (Deixar pendente)</option>
                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.nome }} ({{ acc.banco }})</option>
                  </select>
                </div>
                <div class="flex items-center gap-2">
                  <input type="checkbox" id="lancamento-conciliado" v-model="modalForm.conciliado" style="width: 1rem; height: 1rem; accent-color: var(--color-brand);" />
                  <label for="lancamento-conciliado" class="form-label" style="margin: 0; cursor: pointer;">Confirmar como já pago/conciliado</label>
                </div>
                <div class="form-group">
                  <label class="form-label">
                    Comprovante de Pagamento
                    <span v-if="modalForm.comprovante" class="text-success" style="font-size: 0.75rem;">(Possui comprovante)</span>
                  </label>
                  <input type="file" @change="handleFileChange" class="form-input" accept=".pdf,.jpg,.jpeg,.png" />
                  <span class="form-hint">Formatos: PDF, JPG, PNG (Máx: 5MB)</span>
                  <div v-if="modalForm.comprovante" class="mt-2">
                    <a :href="modalForm.comprovante" target="_blank" class="text-success" style="font-size: 0.8rem; font-weight: 600;">Ver Comprovante Atual →</a>
                  </div>
                </div>
                <div v-if="!isEditingModal" class="flex items-center gap-2">
                  <input type="checkbox" id="lancamento-recorrente" v-model="modalForm.recorrente" style="width: 1rem; height: 1rem; accent-color: var(--color-brand);" />
                  <label for="lancamento-recorrente" class="form-label" style="margin: 0; cursor: pointer;">Repetir lançamento (Recorrente)</label>
                </div>
                <div v-if="!isEditingModal && modalForm.recorrente" class="grid-2">
                  <div class="form-group">
                    <label class="form-label">Frequência</label>
                    <select v-model="modalForm.frequencia" class="form-select" required>
                      <option value="mensal">Mensal</option>
                      <option value="semanal">Semanal</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Número de Vezes</label>
                    <input v-model="modalForm.recorrencias" type="number" min="2" max="36" class="form-input" placeholder="Ex: 12" required />
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="showModal = false">Cancelar</button>
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                  {{ submitting ? 'Salvando...' : 'Salvar Lançamento' }}
                </button>
              </div>
            </form>
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
  transactions: { type: Object, required: true },
  accounts:     { type: Array,  required: true },
  filters:      { type: Object, default: () => ({}) },
  metrics:      { type: Object, required: true }
})

const form = ref({
  tipo: props.filters.tipo || '', categoria: props.filters.categoria || '',
  status: props.filters.status || '', conta_id: props.filters.conta_id || '',
  data_inicio: props.filters.data_inicio || '', data_fim: props.filters.data_fim || '',
  search: props.filters.search || ''
})

const showModal            = ref(false)
const submitting           = ref(false)
const isEditingModal       = ref(false)
const editingTransactionId = ref(null)

const modalForm = ref({
  tipo: 'saida', categoria: 'outros', descricao: '', valor: '',
  data_lancamento: new Date().toISOString().slice(0, 10), conta_id: null,
  conciliado: false, recorrente: false, recorrencias: 12, frequencia: 'mensal',
  comprovante: null, comprovante_file: null
})

function handleFilter() { router.get(route('admin.financial.index'), form.value, { preserveState: true }) }
function clearFilters() {
  form.value = { tipo: '', categoria: '', status: '', conta_id: '', data_inicio: '', data_fim: '', search: '' }
  handleFilter()
}
function handleFileChange(event) { modalForm.value.comprovante_file = event.target.files[0] }
function reconcile(id) {
  if (confirm('Confirmar conciliação manual deste lançamento?')) router.post(route('admin.financial.reconcile', id))
}
function openCreateModal() {
  isEditingModal.value = false; editingTransactionId.value = null
  modalForm.value = { tipo: 'saida', categoria: 'outros', descricao: '', valor: '', data_lancamento: new Date().toISOString().slice(0, 10), conta_id: props.accounts[0]?.id || null, conciliado: false, recorrente: false, recorrencias: 12, frequencia: 'mensal', comprovante: null, comprovante_file: null }
  showModal.value = true
}
function openEditModal(t) {
  isEditingModal.value = true; editingTransactionId.value = t.id
  modalForm.value = { tipo: t.tipo, categoria: t.categoria, descricao: t.descricao, valor: t.valor, data_lancamento: t.data_lancamento, conta_id: t.conta_id, conciliado: t.conciliado === 1 || t.conciliado === true, recorrente: false, recorrencias: 12, frequencia: 'mensal', comprovante: t.comprovante, comprovante_file: null }
  showModal.value = true
}
function deleteTransaction(id) {
  if (confirm('Tem certeza que deseja excluir este lançamento?')) router.delete(route('admin.financial.destroy', id))
}
function submitModalForm() {
  submitting.value = true
  const done = () => { submitting.value = false }
  if (isEditingModal.value) {
    router.post(route('admin.financial.update', editingTransactionId.value), { ...modalForm.value, _method: 'put' }, {
      onSuccess: () => { showModal.value = false; isEditingModal.value = false; done() },
      onError: done
    })
  } else {
    router.post(route('admin.financial.store'), modalForm.value, {
      onSuccess: () => { showModal.value = false; done() },
      onError: done
    })
  }
}
function formatMoney(v) { return v !== null && v !== undefined ? parseFloat(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0,00' }
function formatDate(d) { return new Date(d).toLocaleDateString('pt-BR') }
</script>

<style scoped>
.page-btn.disabled { pointer-events: none; opacity: 0.35; }
</style>
