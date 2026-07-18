<template>
  <AdminLayout title="CRM — Automações">
    <div class="header-container">
      <div>
        <h1 class="page-title">🤖 Automações Inteligentes</h1>
        <p class="page-sub">Gatilhos automáticos baseados no comportamento do cliente</p>
      </div>
      <button @click="openModal = true" class="crm-btn btn-primary">+ Criar Automação</button>
    </div>

    <!-- Modal Nova Automação -->
    <div v-if="openModal" class="crm-modal-overlay" @click.self="openModal = false">
      <div class="crm-modal-body">
        <div class="modal-header">
          <h2>🤖 Nova Automação CRM</h2>
          <button @click="openModal = false" class="close-btn">&times;</button>
        </div>
        <form @submit.prevent="submitAutomation" class="modal-form">
          <div class="form-group">
            <label>Nome da Automação *</label>
            <input v-model="form.nome" type="text" class="crm-input" required placeholder="Ex: Cupom de Aniversário" />
          </div>
          <div class="form-group">
            <label>Descrição</label>
            <textarea v-model="form.descricao" class="crm-textarea" placeholder="Descreva o objetivo desta automação..."></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Gatilho Alvo *</label>
              <select v-model="form.gatilho" class="crm-select" required>
                <option v-for="(v, k) in gatilhos" :key="k" :value="k">{{ v }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Delay (Dias)</label>
              <input v-model="form.delay_dias" type="number" class="crm-input" min="0" placeholder="Ex: 5" />
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="openModal = false" class="crm-btn btn-outline">Cancelar</button>
            <button type="submit" class="crm-btn btn-primary" :disabled="form.processing">Criar Automação</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Automations List -->
    <div class="crm-card">
      <div v-if="!automacoes.length" class="empty-msg">Nenhuma automação configurada.</div>
      <table v-else class="crm-table">
        <thead>
          <tr>
            <th>Nome / Descrição</th>
            <th>Gatilho Alvo</th>
            <th>Delay</th>
            <th>Métricas de Sucesso</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in automacoes" :key="a.id">
            <td>
              <span class="auto-name">{{ a.nome }}</span>
              <span class="auto-desc">{{ a.descricao || 'Sem descrição' }}</span>
            </td>
            <td>
              <span class="badge-gatilho">{{ a.gatilho }}</span>
            </td>
            <td>{{ a.delay_dias ? `${a.delay_dias} dias` : 'Imediato' }}</td>
            <td>
              <div class="metric-group">
                <span>⚡ {{ a.total_execucoes }} execuções</span>
                <span class="pct-sucesso">({{ a.taxa_sucesso }}% OK)</span>
              </div>
            </td>
            <td>
              <span class="status-toggle" :class="{ active: a.ativa }">
                {{ a.ativa ? 'Ativa' : 'Pausada' }}
              </span>
            </td>
            <td>
              <button @click="deletar(a.id)" class="crm-btn btn-danger small">Deletar</button>
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

const props = defineProps({ automacoes: Array, gatilhos: Object })
const openModal = ref(false)

const form = useForm({
  nome: '',
  descricao: '',
  gatilho: 'aniversario',
  delay_dias: 0,
  acoes: [{ tipo: 'criar_alerta', dados: { titulo: 'Notificar aniversário', prioridade: 'media' } }],
  ativa: true,
})

function submitAutomation() {
  form.post(route('admin.crm.automacoes.store'), {
    onSuccess: () => {
      openModal.value = false
      form.reset()
    }
  })
}

function deletar(id) {
  if (confirm('Deseja realmente remover esta automação?')) {
    router.delete(route('admin.crm.automacoes.destroy', id), { preserveScroll: true })
  }
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
.btn-danger { background: rgba(239,68,68,.1); color: #f87171; border: 1px solid rgba(239,68,68,.2); }
.btn-danger:hover { background: #ef4444; color: #fff; }
.small { padding: .35rem .75rem; font-size: .75rem; }

.crm-card { background: rgba(17,24,39,.95); border: 1px solid rgba(255,255,255,.05); border-radius: 16px; padding: 1.5rem; }
.crm-table { width: 100%; border-collapse: collapse; text-align: left; }
.crm-table th { color: #64748b; font-size: .75rem; text-transform: uppercase; padding: .75rem 1rem; border-bottom: 1px solid rgba(255,255,255,.05); }
.crm-table td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,.02); color: #cbd5e1; font-size: .85rem; }

.auto-name { display: block; font-weight: 700; color: #e2e8f0; }
.auto-desc { font-size: .75rem; color: #64748b; display: block; margin-top: .15rem; }

.badge-gatilho { background: rgba(99,102,241,.1); color: #818cf8; padding: .2rem .5rem; border-radius: 6px; font-size: .75rem; font-weight: 600; text-transform: uppercase; }

.metric-group { font-size: .8rem; color: #94a3b8; }
.pct-sucesso { margin-left: .4rem; color: #10b981; font-weight: 700; }

.status-toggle { font-size: .7rem; font-weight: 700; text-transform: uppercase; padding: .25rem .5rem; border-radius: 20px; }
.status-toggle.active { background: rgba(16,185,129,.1); color: #10b981; }

.empty-msg { text-align: center; color: #475569; padding: 2rem; font-size: .9rem; }
</style>
