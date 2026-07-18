<template>
  <AdminLayout title="Controle Financeiro">
    <template #breadcrumb>
      <span>Operações / Financeiro</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">💰 Controle Financeiro</h1>
        <p class="text-secondary mt-1">Gerencie o fluxo de caixa, contas bancárias e controle suas contas a pagar/receber.</p>
      </div>
      <div class="flex gap-2">
        <button class="btn btn-primary" @click="openCreateModal">
          ➕ Novo Lançamento
        </button>
        <a :href="route('admin.financial.export-bi')" target="_blank" class="btn btn-secondary">
          📊 Exportar BI (JSON)
        </a>
      </div>
    </div>

    <!-- Métricas Consolidadas (DRE Simples, Contas & Estoque) -->
    <div class="mb-6" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
      <div class="card" style="border-top: 4px solid var(--color-success);">
        <div class="card-body">
          <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold; letter-spacing: 0.05em;">Saldo Líquido Realizado</div>
          <div class="mt-2 font-mono font-bold" :class="metrics.lucro_liquido >= 0 ? 'text-success' : 'text-danger'" style="font-size: 1.75rem;">
            R$ {{ formatMoney(metrics.lucro_liquido) }}
          </div>
          <small class="text-secondary mt-1 block" title="Legenda: Dinheiro que de fato entrou no caixa físico ou virtual, subtraído do que de fato já foi pago.">
            ℹ️ Receitas confirmadas menos despesas pagas
          </small>
        </div>
      </div>
      <div class="card" style="border-top: 4px solid #3b82f6;">
        <div class="card-body">
          <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold; letter-spacing: 0.05em;">Previsão de Entradas</div>
          <div class="mt-2 font-mono font-bold" style="font-size: 1.75rem; color: #3b82f6;">
            R$ {{ formatMoney(metrics.contas_receber) }}
          </div>
          <small class="text-secondary mt-1 block" title="Legenda: Vendas realizadas ou receitas agendadas que ainda não foram confirmadas (conciliadas) no banco/gateway.">
            ℹ️ Contas a receber (Aguardando Pix/Boleto)
          </small>
        </div>
      </div>
      <div class="card" style="border-top: 4px solid var(--color-danger);">
        <div class="card-body">
          <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold; letter-spacing: 0.05em;">Previsão de Saídas</div>
          <div class="mt-2 font-mono font-bold text-danger" style="font-size: 1.75rem;">
            R$ {{ formatMoney(metrics.contas_pagar) }}
          </div>
          <small class="text-secondary mt-1 block" title="Legenda: Custos, despesas cadastradas ou repasses a fornecedores agendados que ainda não foram marcados como pagos.">
            ℹ️ Contas a pagar (Aguardando liquidação)
          </small>
        </div>
      </div>
      <div class="card" style="border-top: 4px solid #d97706;">
        <div class="card-body">
          <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold; letter-spacing: 0.05em;">Patrimônio (Preço de Custo)</div>
          <div class="mt-2 font-mono font-bold text-warning" style="font-size: 1.75rem; color: #d97706;">
            R$ {{ formatMoney(metrics.valor_estoque_custo) }}
          </div>
          <small class="text-secondary mt-1 block" title="Legenda: Capital financeiro investido na compra dos produtos que estão fisicamente armazenados no estoque.">
            ℹ️ Dinheiro investido nas peças em estoque
          </small>
        </div>
      </div>
      <div class="card" style="border-top: 4px solid #10b981;">
        <div class="card-body">
          <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold; letter-spacing: 0.05em;">Potencial de Venda (Estoque)</div>
          <div class="mt-2 font-mono font-bold" style="font-size: 1.75rem; color: #10b981;">
            R$ {{ formatMoney(metrics.valor_estoque_venda) }}
          </div>
          <small class="text-secondary mt-1 block" title="Legenda: O faturamento bruto estimado que a loja obterá se vender todas as mercadorias atualmente disponíveis no estoque.">
            ℹ️ Retorno bruto potencial do estoque atual
          </small>
        </div>
      </div>
    </div>

    <!-- Contas Bancárias & Gateways -->
    <div class="grid-3 gap-6 mb-6">
      <div v-for="acc in accounts" :key="acc.id" class="card">
        <div class="card-body">
          <div class="text-muted" style="font-size: 0.75rem; font-weight: 500;">
            {{ acc.banco }} 
            <span v-if="acc.agencia || acc.conta" style="opacity: 0.8;">
              (Ag: {{ acc.agencia || '—' }} / Cc: {{ acc.conta || '—' }})
            </span>
            <span v-else style="color: #6366f1; background-color: #e0e7ff; padding: 2px 6px; border-radius: 4px; font-size: 0.65rem; margin-left: 4px;">
              Gateway de Pagamento
            </span>
          </div>
          <h3 class="font-bold mt-1" style="font-size: 1.25rem;">{{ acc.nome }}</h3>
          <div class="mt-2 font-mono font-bold" :class="acc.saldo >= 0 ? 'text-success' : 'text-danger'" style="font-size: 1.5rem;">
            R$ {{ formatMoney(acc.saldo) }}
          </div>
          <small class="text-secondary block mt-1" style="font-size: 0.7rem; line-height: 1.2;">
            {{ acc.agencia || acc.conta ? 'Conta para conciliação e despesas físicas.' : 'Saldo acumulado dos pagamentos Pix/Cartão recebidos no e-commerce.' }}
          </small>
        </div>
      </div>
    </div>

    <!-- Lista de Lançamentos -->
    <div class="card">
      <div class="card-header" style="display: flex; flex-direction: column; gap: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
          <h3 class="card-title" style="margin: 0;">💵 Extrato de Transações</h3>
        </div>
        <form @submit.prevent="handleFilter" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; width: 100%;">
          <input type="text" v-model="form.search" placeholder="Buscar por descrição, fornecedor..." class="form-control form-control-sm" style="flex: 1; min-width: 200px;" />
          <select v-model="form.tipo" class="form-control form-control-sm" style="max-width: 130px;">
            <option value="">Todos Tipos</option>
            <option value="entrada">Receitas</option>
            <option value="saida">Despesas</option>
          </select>
          <select v-model="form.status" class="form-control form-control-sm" style="max-width: 130px;">
            <option value="">Todos Status</option>
            <option value="conciliado">Conciliados</option>
            <option value="pendente">Pendentes</option>
          </select>
          <select v-model="form.categoria" class="form-control form-control-sm" style="max-width: 130px;">
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
          <select v-model="form.conta_id" class="form-control form-control-sm" style="max-width: 130px;">
            <option value="">Todas Contas</option>
            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.nome }}</option>
          </select>
          <div style="display: flex; align-items: center; gap: 0.25rem;">
            <input type="date" v-model="form.data_inicio" class="form-control form-control-sm" style="max-width: 130px;" title="Data Início" />
            <span style="color: var(--color-text-muted); font-size: 0.75rem;">a</span>
            <input type="date" v-model="form.data_fim" class="form-control form-control-sm" style="max-width: 130px;" title="Data Fim" />
          </div>
          <button type="submit" class="btn btn-primary btn-sm">🔍 Filtrar</button>
          <button type="button" @click="clearFilters" class="btn btn-secondary btn-sm">Limpar</button>
        </form>
      </div>
      <div class="card-body" style="padding: 0;">
        <div v-if="transactions.data.length === 0" class="alert alert-secondary" style="margin: 1.5rem;">
          Nenhuma transação financeira encontrada.
        </div>
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Data Lançamento</th>
                <th>Descrição / Referência</th>
                <th>Conta</th>
                <th>Categoria</th>
                <th>Valor</th>
                <th>Status</th>
                <th style="width: 180px; text-align: right;">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in transactions.data" :key="t.id">
                <td style="font-size:0.8125rem;">{{ formatDate(t.data_lancamento) }}</td>
                <td>
                  <div>
                    <strong>{{ t.descricao }}</strong>
                    <div v-if="t.pedido" class="text-secondary font-mono" style="font-size: 0.75rem;">
                      Pedido ID: #{{ t.pedido.id }}
                    </div>
                    <div v-if="t.fornecedor" class="text-secondary" style="font-size: 0.75rem;">
                      Fornecedor: {{ t.fornecedor.razao_social }}
                    </div>
                    <div v-if="t.comprovante" class="mt-1">
                      <a :href="t.comprovante" target="_blank" class="text-success font-bold" style="font-size: 0.75rem; display: inline-flex; align-items: center; gap: 2px;" title="Ver comprovante">
                        📄 Ver Comprovante
                      </a>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge badge-secondary">{{ t.conta ? t.conta.nome : '—' }}</span>
                </td>
                <td>
                  <span class="badge badge-secondary">{{ t.categoria }}</span>
                </td>
                <td>
                  <span class="font-bold" :class="t.tipo === 'entrada' ? 'text-success' : 'text-danger'">
                    {{ t.tipo === 'entrada' ? '+' : '-' }} R$ {{ formatMoney(t.valor) }}
                  </span>
                </td>
                <td>
                  <span :class="t.conciliado ? 'badge badge-success' : 'badge badge-warning'">
                    {{ t.conciliado ? 'Conciliado' : 'Aguardando' }}
                  </span>
                </td>
                <td style="text-align: right;">
                  <div style="display: inline-flex; gap: 0.25rem; justify-content: flex-end; align-items: center; width: 100%;">
                    <button v-if="!t.conciliado" @click="reconcile(t.id)" class="btn btn-primary btn-sm" style="padding: 4px 8px;" title="Conciliar Pix / Confirmar">
                      Conciliar
                    </button>
                    <button @click="openEditModal(t)" class="btn btn-secondary btn-sm" style="padding: 4px 8px;" title="Editar Lançamento">
                      ✏️
                    </button>
                    <button @click="deleteTransaction(t.id)" class="btn btn-danger btn-sm" style="padding: 4px 8px; background-color: var(--color-danger); border-color: var(--color-danger);" title="Excluir Lançamento">
                      🗑️
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginação -->
        <div v-if="transactions.links && transactions.links.length > 3" class="flex gap-2" style="padding: 1.5rem; justify-content: flex-end;">
          <Link v-for="(link, idx) in transactions.links" :key="idx" 
                :href="link.url || '#'" 
                class="btn btn-secondary btn-sm" 
                :class="{ active: link.active, disabled: !link.url }"
                v-html="link.label">
          </Link>
        </div>
      </div>
    </div>

    <!-- Modal Lançamento Manual (Novo/Editar) -->
    <div v-if="showModal" class="modal-backdrop">
      <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
          <h3>{{ isEditingModal ? '✏️ Editar Lançamento' : '💰 Novo Lançamento Manual' }}</h3>
          <button @click="showModal = false" class="modal-close-btn">✕</button>
        </div>
        <form @submit.prevent="submitModalForm">
          <div class="modal-body">
            
            <div class="grid-2 gap-4 mb-4">
              <div class="form-group">
                <label class="form-label">Tipo de Lançamento</label>
                <select v-model="modalForm.tipo" class="form-control" required>
                  <option value="entrada">Receita (Entrada)</option>
                  <option value="saida">Despesa (Saída)</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Categoria</label>
                <select v-model="modalForm.categoria" class="form-control" required>
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

            <div class="form-group mb-4">
              <label class="form-label">Descrição</label>
              <input v-model="modalForm.descricao" type="text" class="form-control" placeholder="Ex: Pagamento da conta de energia" required />
            </div>

            <div class="grid-2 gap-4 mb-4">
              <div class="form-group">
                <label class="form-label">Valor (R$)</label>
                <input v-model="modalForm.valor" type="number" step="0.01" min="0.01" class="form-control" placeholder="0,00" required />
              </div>
              <div class="form-group">
                <label class="form-label">Data de Competência</label>
                <input v-model="modalForm.data_lancamento" type="date" class="form-control" required />
              </div>
            </div>

            <div class="form-group mb-4">
              <label class="form-label">Conta Bancária Associada</label>
              <select v-model="modalForm.conta_id" class="form-control">
                <option :value="null">Nenhuma conta (Deixar pendente)</option>
                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                  {{ acc.nome }} ({{ acc.banco }})
                </option>
              </select>
            </div>

            <div class="form-options mb-4">
              <label class="checkbox-label" style="display:flex; gap:8px; align-items:center;">
                <input type="checkbox" v-model="modalForm.conciliado" />
                <span>Confirmar lançamento como já pago/conciliado</span>
              </label>
            </div>

            <!-- Upload de arquivo do comprovante -->
            <div class="form-group mb-4">
              <label class="form-label" style="display: flex; align-items: center; gap: 4px;">
                <span>Comprovante de Pagamento</span>
                <span v-if="modalForm.comprovante" style="color: var(--color-success); font-size: 0.75rem;">(Possui comprovante salvo)</span>
              </label>
              <input type="file" @change="handleFileChange" class="form-control" accept=".pdf,.jpg,.jpeg,.png" />
              <small class="text-secondary block mt-1">Formatos aceitos: PDF, JPG, JPEG, PNG (Máx: 5MB)</small>
              <div v-if="modalForm.comprovante" class="mt-2">
                <a :href="modalForm.comprovante" target="_blank" class="text-success font-bold" style="font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px;">
                  📄 Ver Comprovante Atual
                </a>
              </div>
            </div>

            <!-- Recorrência só é visível na criação -->
            <div v-if="!isEditingModal" class="form-options mb-4 mt-2">
              <label class="checkbox-label" style="display:flex; gap:8px; align-items:center;">
                <input type="checkbox" v-model="modalForm.recorrente" />
                <span>Repetir lançamento (Recorrente)</span>
              </label>
            </div>

            <div v-if="!isEditingModal && modalForm.recorrente" class="grid-2 gap-4 mb-4">
              <div class="form-group">
                <label class="form-label">Frequência</label>
                <select v-model="modalForm.frequencia" class="form-control" required>
                  <option value="mensal">Mensal</option>
                  <option value="semanal">Semanal</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Número de Vezes (Meses/Semanas)</label>
                <input v-model="modalForm.recorrencias" type="number" min="2" max="36" class="form-control" placeholder="Ex: 12" required />
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

  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  transactions: { type: Object, required: true },
  accounts: { type: Array, required: true },
  filters: { type: Object, default: () => ({}) },
  metrics: { type: Object, required: true }
})

const form = ref({
  tipo: props.filters.tipo || '',
  categoria: props.filters.categoria || '',
  status: props.filters.status || '',
  conta_id: props.filters.conta_id || '',
  data_inicio: props.filters.data_inicio || '',
  data_fim: props.filters.data_fim || '',
  search: props.filters.search || ''
})

const showModal = ref(false)
const submitting = ref(false)
const modalForm = ref({
  tipo: 'saida',
  categoria: 'outros',
  descricao: '',
  valor: '',
  data_lancamento: new Date().toISOString().slice(0, 10),
  conta_id: null,
  conciliado: false,
  recorrente: false,
  recorrencias: 12,
  frequencia: 'mensal',
  comprovante: null,
  comprovante_file: null
})

const isEditingModal = ref(false)
const editingTransactionId = ref(null)

function handleFilter() {
  router.get(route('admin.financial.index'), form.value, { preserveState: true })
}

function clearFilters() {
  form.value = {
    tipo: '',
    categoria: '',
    status: '',
    conta_id: '',
    data_inicio: '',
    data_fim: '',
    search: ''
  }
  handleFilter()
}

function handleFileChange(event) {
  modalForm.value.comprovante_file = event.target.files[0]
}

function reconcile(id) {
  if (confirm('Deseja confirmar a conciliação manual deste lançamento Pix/Pendente? O sistema registrará seu usuário como responsável.')) {
    router.post(route('admin.financial.reconcile', id))
  }
}

function openCreateModal() {
  isEditingModal.value = false
  editingTransactionId.value = null
  modalForm.value = {
    tipo: 'saida',
    categoria: 'outros',
    descricao: '',
    valor: '',
    data_lancamento: new Date().toISOString().slice(0, 10),
    conta_id: props.accounts[0]?.id || null,
    conciliado: false,
    recorrente: false,
    recorrencias: 12,
    frequencia: 'mensal',
    comprovante: null,
    comprovante_file: null
  }
  showModal.value = true
}

function openEditModal(t) {
  isEditingModal.value = true
  editingTransactionId.value = t.id
  modalForm.value = {
    tipo: t.tipo,
    categoria: t.categoria,
    descricao: t.descricao,
    valor: t.valor,
    data_lancamento: t.data_lancamento,
    conta_id: t.conta_id,
    conciliado: t.conciliado === 1 || t.conciliado === true,
    recorrente: false,
    recorrencias: 12,
    frequencia: 'mensal',
    comprovante: t.comprovante,
    comprovante_file: null
  }
  showModal.value = true
}

function deleteTransaction(id) {
  if (confirm('Tem certeza que deseja excluir permanentemente este lançamento financeiro?')) {
    router.delete(route('admin.financial.destroy', id))
  }
}

function submitModalForm() {
  submitting.value = true
  
  // Usamos FormData ou similar via Inertia.
  // Como enviamos arquivos e queremos simular PUT, passamos _method: 'put' via POST comum
  if (isEditingModal.value) {
    const payload = {
      ...modalForm.value,
      _method: 'put'
    }
    router.post(route('admin.financial.update', editingTransactionId.value), payload, {
      onSuccess: () => {
        showModal.value = false
        isEditingModal.value = false
        editingTransactionId.value = null
        submitting.value = false
      },
      onError: () => {
        submitting.value = false
      }
    })
  } else {
    router.post(route('admin.financial.store'), modalForm.value, {
      onSuccess: () => {
        showModal.value = false
        submitting.value = false
      },
      onError: () => {
        submitting.value = false
      }
    })
  }
}

function formatMoney(value) {
  if (value === null || value === undefined) return '0,00'
  return parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('pt-BR')
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
.text-info {
  color: #3b82f6;
}
</style>
