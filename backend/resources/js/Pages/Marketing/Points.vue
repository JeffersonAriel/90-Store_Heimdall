<template>
  <AdminLayout title="Pontos & Fidelidade">
    <template #breadcrumb>
      <span>Marketing / Pontos & Fidelidade</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">⭐ Pontos & Fidelidade</h1>
        <p class="text-secondary mt-1">Acompanhe as transações de pontos dos seus clientes.</p>
      </div>
    </div>

    <!-- Regras Ativas -->
    <div class="grid grid-cols-3 gap-6 mb-8">
      <div v-for="rule in rules" :key="rule.id" class="card">
        <div class="card-body">
          <h3 class="font-bold text-brand">{{ rule.nome }}</h3>
          <p class="text-secondary text-sm mt-1 mb-3">{{ rule.descricao }}</p>
          <div class="flex justify-between items-center mt-2 border-t border-dark pt-2">
            <span class="text-xs text-gray">Mecânica:</span>
            <span class="badge badge-dark">
              {{ rule.tipo === 'ganho' ? `Ganha ${rule.pontos} pts` : `Desconto de ${rule.pontos} pts` }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabela de Logs -->
    <div class="card">
      <div class="card-header border-b border-dark mb-4 pb-4">
        <h2 class="title-md">Histórico de Transações</h2>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Cliente</th>
              <th>Tipo</th>
              <th>Pontos</th>
              <th>Descrição</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs.data" :key="log.id">
              <td>#{{ log.id }}</td>
              <td><strong>{{ log.cliente_nome }}</strong></td>
              <td>
                <span class="badge" :class="log.tipo === 'ganho' ? 'badge-success' : 'badge-danger'">
                  {{ log.tipo === 'ganho' ? 'Ganho' : 'Resgate' }}
                </span>
              </td>
              <td :class="log.tipo === 'ganho' ? 'text-green font-bold' : 'text-red font-bold'">
                {{ log.tipo === 'ganho' ? '+' : '-' }}{{ log.pontos }}
              </td>
              <td>{{ log.descricao }}</td>
              <td>{{ formatDate(log.created_at) }}</td>
            </tr>
            <tr v-if="logs.data.length === 0">
              <td colspan="6" class="text-center py-6 text-secondary">Nenhuma transação de pontos encontrada.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação Simples -->
      <div class="mt-4 flex justify-end gap-2" v-if="logs.last_page > 1">
        <button class="btn btn-sm btn-secondary" :disabled="logs.current_page === 1" @click="changePage(logs.current_page - 1)">Anterior</button>
        <span class="text-sm self-center">Página {{ logs.current_page }} de {{ logs.last_page }}</span>
        <button class="btn btn-sm btn-secondary" :disabled="logs.current_page === logs.last_page" @click="changePage(logs.current_page + 1)">Próxima</button>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  logs: Object,
  rules: Array
})

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('pt-BR')
}

function changePage(page) {
  router.get(route('admin.marketing.points'), { page }, { preserveState: true })
}
</script>

<style scoped>
.badge-danger {
  background-color: var(--color-red);
  color: #fff;
}
</style>
