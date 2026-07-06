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

    <!-- Métricas Consolidadas (DRE Simples & Contas) -->
    <div class="grid-3 gap-6 mb-6">
      <div class="card" style="border-top: 4px solid var(--color-success);">
        <div class="card-body">
          <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Lucro Líquido Realizado</div>
          <div class="mt-2 font-mono font-bold" :class="metrics.lucro_liquido >= 0 ? 'text-success' : 'text-danger'" style="font-size: 1.75rem;">
            R$ {{ formatMoney(metrics.lucro_liquido) }}
          </div>
          <small class="text-secondary mt-1 block">Receitas conciliadas - Despesas conciliadas</small>
        </div>
      </div>
      <div class="card" style="border-top: 4px solid #3b82f6;">
        <div class="card-body">
          <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Contas a Receber (Pendente)</div>
          <div class="mt-2 font-mono font-bold" style="font-size: 1.75rem; color: #3b82f6;">
            R$ {{ formatMoney(metrics.contas_receber) }}
          </div>
          <small class="text-secondary mt-1 block">Entradas pendentes de conciliação</small>
        </div>
      </div>
      <div class="card" style="border-top: 4px solid var(--color-danger);">
        <div class="card-body">
          <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Contas a Pagar (Pendente)</div>
          <div class="mt-2 font-mono font-bold text-danger" style="font-size: 1.75rem;">
            R$ {{ formatMoney(metrics.contas_pagar) }}
          </div>
          <small class="text-secondary mt-1 block">Saídas/Repasses pendentes de conciliação</small>
        </div>
      </div>
    </div>

    <!-- Contas Bancárias -->
    <div class="grid-3 gap-6 mb-6">
      <div v-for="acc in accounts" :key="acc.id" class="card">
        <div class="card-body">
          <div class="text-muted" style="font-size: 0.75rem;">{{ acc.banco }} (Ag: {{ acc.agencia }} / Cc: {{ acc.conta }})</div>
          <h3 class="font-bold mt-1" style="font-size: 1.25rem;">{{ acc.nome }}</h3>
          <div class="mt-2 font-mono font-bold" :class="acc.saldo >= 0 ? 'text-success' : 'text-danger'" style="font-size: 1.5rem;">
            R$ {{ formatMoney(acc.saldo) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Lista de Lançamentos -->
    <div class="card">
      <div class="card-header flex justify-between items-center">
        <h3 class="card-title">💵 Extrato de Transações</h3>
        <form @submit.prevent="handleFilter" class="flex gap-2">
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
          <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
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
                <th style="width: 130px;">Ações</th>
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
                <td>
                  <button v-if="!t.conciliado" @click="reconcile(t.id)" class="btn btn-primary btn-sm" style="padding: 4px 8px;">
                    Conciliar Pix / Confirmar
                  </button>
                  <span v-else class="text-muted" style="font-size: 0.75rem;">
                    Conciliado por: <br/><strong>{{ t.funcionario_criador ? t.funcionario_criador.nome : 'Sistema' }}</strong>
                  </span>
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

    <!-- Modal Novo Lançamento Manual -->
    <div v-if="showModal" class="modal-backdrop">
      <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
          <h3>💰 Novo Lançamento Manual</h3>
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

            <div class="form-options mb-2">
              <label class="checkbox-label" style="display:flex; gap:8px; align-items:center;">
                <input type="checkbox" v-model="modalForm.conciliado" />
                <span>Confirmar lançamento como já pago/conciliado</span>
              </label>
            </div>

            <div class="form-options mb-4 mt-2">
              <label class="checkbox-label" style="display:flex; gap:8px; align-items:center;">
                <input type="checkbox" v-model="modalForm.recorrente" />
                <span>Repetir lançamento (Recorrente)</span>
              </label>
            </div>

            <div v-if="modalForm.recorrente" class="grid-2 gap-4 mb-4">
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
  status: props.filters.status || ''
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
  frequencia: 'mensal'
})

function handleFilter() {
  router.get(route('admin.financial.index'), form.value, { preserveState: true })
}

function reconcile(id) {
  if (confirm('Deseja confirmar a conciliação manual deste lançamento Pix/Pendente? O sistema registrará seu usuário como responsável.')) {
    router.post(route('admin.financial.reconcile', id))
  }
}

function openCreateModal() {
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
    frequencia: 'mensal'
  }
  showModal.value = true
}

function submitModalForm() {
  submitting.value = true
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
