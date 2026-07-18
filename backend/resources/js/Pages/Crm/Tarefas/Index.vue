<template>
  <AdminLayout title="CRM — Tarefas">
    <div class="header-container">
      <div>
        <h1 class="page-title">✅ Agenda & Tarefas CRM</h1>
        <p class="page-sub">Controle de tarefas de acompanhamento de leads e clientes</p>
      </div>
      <button @click="openModal = true" class="crm-btn btn-primary">+ Criar Tarefa</button>
    </div>

    <!-- Tasks Grid -->
    <div class="crm-card">
      <div v-if="!tarefas.data.length" class="empty-msg">Nenhuma tarefa pendente. Excelente!</div>
      <table v-else class="crm-table">
        <thead>
          <tr>
            <th>Título / Descrição</th>
            <th>Tipo</th>
            <th>Vínculo</th>
            <th>Vencimento</th>
            <th>Prioridade</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in tarefas.data" :key="t.id">
            <td>
              <span class="task-title" :class="{ completed: t.status === 'concluida' }">{{ t.titulo }}</span>
              <span class="task-desc">{{ t.descricao || 'Sem descrição' }}</span>
            </td>
            <td>
              <span class="badge-tipo">{{ t.tipo }}</span>
            </td>
            <td>
              <span v-if="t.cliente">👤 {{ t.cliente.nome_completo }}</span>
              <span v-else-if="t.lead">🎯 {{ t.lead.nome }}</span>
              <span v-else>—</span>
            </td>
            <td>
              <span :class="{ overdue: new Date(t.vencimento_em) < new Date() && t.status === 'pendente' }">
                {{ new Date(t.vencimento_em).toLocaleDateString() }}
              </span>
            </td>
            <td>
              <span class="badge-priority" :class="t.prioridade">{{ t.prioridade }}</span>
            </td>
            <td>
              <button v-if="t.status !== 'concluida'" @click="concluir(t.id)" class="crm-btn btn-success small">
                ✓ Concluir
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ tarefas: Object, funcionarios: Array })
const openModal = ref(false)

function concluir(id) {
  router.patch(route('admin.crm.tarefas.concluir', id), {}, { preserveScroll: true })
}
</script>

<style scoped>
.header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.page-title { font-size: 1.8rem; font-weight: 800; color: #f1f5f9; margin: 0; }
.page-sub { color: #64748b; margin-top: 0.25rem; }

.crm-btn { border-radius: 8px; padding: .55rem 1.25rem; font-weight: 600; cursor: pointer; border: none; font-size: .85rem; transition: background .2s; }
.btn-primary { background: #6366f1; color: #fff; }
.btn-primary:hover { background: #4f46e5; }
.btn-success { background: rgba(16,185,129,.1); color: #10b981; border: 1px solid rgba(16,185,129,.2); }
.btn-success:hover { background: #10b981; color: #fff; }
.small { padding: .35rem .75rem; font-size: .75rem; }

.crm-card { background: rgba(17,24,39,.95); border: 1px solid rgba(255,255,255,.05); border-radius: 16px; padding: 1.5rem; }
.crm-table { width: 100%; border-collapse: collapse; text-align: left; }
.crm-table th { color: #64748b; font-size: .75rem; text-transform: uppercase; padding: .75rem 1rem; border-bottom: 1px solid rgba(255,255,255,.05); }
.crm-table td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,.02); color: #cbd5e1; font-size: .85rem; }

.task-title { display: block; font-weight: 700; color: #e2e8f0; }
.task-title.completed { text-decoration: line-through; color: #475569; }
.task-desc { font-size: .75rem; color: #64748b; display: block; margin-top: .15rem; }

.badge-tipo { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); padding: .2rem .4rem; border-radius: 4px; font-size: .75rem; }
.overdue { color: #ef4444; font-weight: 700; }

.badge-priority { font-size: .7rem; font-weight: 700; text-transform: uppercase; padding: .15rem .4rem; border-radius: 4px; }
.badge-priority.urgente { background: rgba(239,68,68,.1); color: #ef4444; }
.badge-priority.alta { background: rgba(245,158,11,.1); color: #f97316; }
.badge-priority.media { background: rgba(245,158,11,.05); color: #f59e0b; }
.badge-priority.baixa { background: rgba(16,185,129,.1); color: #10b981; }

.empty-msg { text-align: center; color: #475569; padding: 2rem; font-size: .9rem; }
</style>
