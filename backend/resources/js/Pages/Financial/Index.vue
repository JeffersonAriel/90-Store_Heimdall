<template>
  <AdminLayout title="Controle Financeiro">
    <template #breadcrumb>
      <span>Operações / Financeiro</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">💰 Controle Financeiro</h1>
        <p class="text-secondary mt-1">Gerencie o fluxo de caixa, contas bancárias e realize conciliação manual de Pix.</p>
      </div>
      <div class="flex gap-2">
        <a :href="route('admin.financial.export-bi')" target="_blank" class="btn btn-secondary">
          📊 Exportar BI (JSON)
        </a>
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
            <option value="receita">Receitas</option>
            <option value="despesa">Despesas</option>
          </select>
          <select v-model="form.categoria" class="form-control form-control-sm" style="max-width: 130px;">
            <option value="">Todas Cat.</option>
            <option value="venda">Vendas</option>
            <option value="custo_produto">Custo de Produto</option>
            <option value="frete">Frete</option>
            <option value="estorno">Estorno</option>
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
                      Pedido ID: #{{ t.pedido.id }} | Canal: {{ t.pedido.canal }}
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
                  <span class="font-bold" :class="t.tipo === 'receita' ? 'text-success' : 'text-danger'">
                    {{ t.tipo === 'receita' ? '+' : '-' }} R$ {{ formatMoney(t.valor) }}
                  </span>
                </td>
                <td>
                  <span :class="t.conciliado ? 'badge badge-success' : 'badge badge-warning'">
                    {{ t.conciliado ? 'Conciliado' : 'Aguardando' }}
                  </span>
                </td>
                <td>
                  <button v-if="!t.conciliado" @click="reconcile(t.id)" class="btn btn-primary btn-sm" style="padding: 4px 8px;">
                    Conciliar Pix
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
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  transactions: { type: Object, required: true },
  accounts: { type: Array, required: true },
  filters: { type: Object, default: () => ({}) }
})

const form = ref({
  tipo: props.filters.tipo || '',
  categoria: props.filters.categoria || ''
})

function handleFilter() {
  router.get(route('admin.financial.index'), form.value, { preserveState: true })
}

function reconcile(id) {
  if (confirm('Deseja confirmar a conciliação manual deste recebimento Pix? O sistema registrará seu usuário como responsável.')) {
    router.post(route('admin.financial.reconcile', id))
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
</style>
