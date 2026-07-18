<template>
  <AdminLayout title="CRM — Tarefas">
    <div class="header-container">
      <div>
        <h1 class="page-title">✅ Agenda & Tarefas CRM</h1>
        <p class="page-sub">Controle de tarefas de acompanhamento de leads e clientes</p>
      </div>
      <button @click="openModal = true" class="crm-btn btn-primary">+ Criar Tarefa</button>
    </div>

    <!-- Modal Nova Tarefa -->
    <div v-if="openModal" class="crm-modal-overlay" @click.self="openModal = false">
      <div class="crm-modal-body">
        <div class="modal-header">
          <h2>✅ Nova Tarefa Comercial</h2>
          <button @click="openModal = false" class="close-btn">&times;</button>
        </div>
        <form @submit.prevent="submitTask" class="modal-form">
          <div class="form-group">
            <label>Título da Tarefa *</label>
            <input v-model="form.titulo" type="text" class="crm-input" required placeholder="Ex: Ligar para confirmar proposta" />
          </div>
          <div class="form-group">
            <label>Descrição</label>
            <textarea v-model="form.descricao" class="crm-textarea" placeholder="Descreva os detalhes da tarefa..."></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Tipo *</label>
              <select v-model="form.tipo" class="crm-select" required>
                <option value="ligacao">📞 Ligação</option>
                <option value="visita">🚗 Visita</option>
                <option value="whatsapp">💬 WhatsApp</option>
                <option value="email">✉️ E-mail</option>
                <option value="reuniao">👥 Reunião</option>
                <option value="pos_venda">🔄 Pós-Venda</option>
                <option value="outro">Outro</option>
              </select>
            </div>
            <div class="form-group">
              <label>Prioridade *</label>
              <select v-model="form.prioridade" class="crm-select" required>
                <option value="baixa">🟢 Baixa</option>
                <option value="media">🟡 Média</option>
                <option value="alta">🟠 Alta</option>
                <option value="urgente">🔴 Urgente</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Vencimento em *</label>
            <input v-model="form.vencimento_em" type="datetime-local" class="crm-input" required />
          </div>
          <div class="modal-footer">
            <button type="button" @click="openModal = false" class="crm-btn btn-outline">Cancelar</button>
            <button type="submit" class="crm-btn btn-primary" :disabled="form.processing">Criar Tarefa</button>
          </div>
        </form>
      </div>
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
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ tarefas: Object, funcionarios: Array })
const openModal = ref(false)

const form = useForm({
  titulo: '',
  descricao: '',
  tipo: 'whatsapp',
  prioridade: 'media',
  vencimento_em: '',
  status: 'pendente',
})

function submitTask() {
  form.post(route('admin.crm.tarefas.store'), {
    onSuccess: () => {
      openModal.value = false
      form.reset()
    }
  })
}

function concluir(id) {
  router.patch(route('admin.crm.tarefas.concluir', id), {}, { preserveScroll: true })
}
</script>

<style scoped>
.crm-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(4px); }
.crm-modal-body { background: linear-gradient(135deg, #111827, #1e1b4b); border: 1px solid rgba(99,102,241,0.25); border-radius: 16px; width: 90%; max-width: 550px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); display: flex; flex-direction: column; overflow: hidden; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.05); }
.modal-header h2 { font-size: 1.15rem; color: #fff; margin: 0; }
.close-btn { background: transparent; border: none; color: #64748b; font-size: 1.5rem; cursor: pointer; }
.modal-form { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: .4rem; }
.form-group label { font-size: .78rem; color: #94a3b8; font-weight: 600; }
.crm-input, .crm-select, .crm-textarea { width: 100%; padding: .6rem 1rem; background: rgba(255,255,255,.02); border: 1px solid rgba(255,255,255,.08); border-radius: 8px; color: #cbd5e1; font-size: .88rem; }
.crm-textarea { height: 70px; resize: none; }
.crm-input:focus, .crm-select:focus, .crm-textarea:focus { border-color: rgba(99,102,241,0.4); outline: none; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: .75rem; border-top: 1px solid rgba(255,255,255,.05); padding-top: 1rem; margin-top: .5rem; }
.btn-outline { background: transparent; border: 1px solid rgba(255,255,255,.1); color: #cbd5e1; }
.btn-outline:hover { background: rgba(255,255,255,.03); }

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
