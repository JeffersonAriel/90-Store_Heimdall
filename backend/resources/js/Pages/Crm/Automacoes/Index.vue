<template>
  <AdminLayout title="CRM — Automações">
    <div class="header-container">
      <div>
        <h1 class="page-title">🤖 Automações Inteligentes</h1>
        <p class="page-sub">Gatilhos automáticos de e-mail (Titan Mail), tarefas e alertas do CRM</p>
      </div>
      <button @click="openCreateModal" class="crm-btn btn-primary">+ Criar Automação</button>
    </div>

    <!-- Modal Criar / Editar Automação -->
    <div v-if="openModal" class="crm-modal-overlay" @click.self="openModal = false">
      <div class="crm-modal-body">
        <div class="modal-header">
          <h2>{{ isEditing ? '✏️ Editar Automação CRM' : '🤖 Nova Automação CRM' }}</h2>
          <button @click="openModal = false" class="close-btn">&times;</button>
        </div>
        <form @submit.prevent="submitAutomation" class="modal-form">
          <div class="form-group">
            <label>Nome da Automação *</label>
            <input v-model="form.nome" type="text" class="crm-input" required placeholder="Ex: Cupom de Aniversário Titan" />
          </div>

          <div class="form-group">
            <label>Descrição</label>
            <textarea v-model="form.descricao" class="crm-textarea" placeholder="Descreva a finalidade desta automação..."></textarea>
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
              <input v-model="form.delay_dias" type="number" class="crm-input" min="0" placeholder="Ex: 5 (0 = imediato)" />
            </div>
          </div>

          <div class="form-group">
            <label>Tipo de Ação a Executar *</label>
            <select v-model="actionForm.tipo" class="crm-select" required>
              <option value="enviar_email">📧 Enviar E-mail (HostGator Titan Mail)</option>
              <option value="criar_alerta">🔔 Criar Alerta no CRM</option>
              <option value="criar_tarefa">📋 Criar Tarefa para o Vendedor</option>
            </select>
          </div>

          <!-- Campos dinâmicos da Ação de E-mail -->
          <div v-if="actionForm.tipo === 'enviar_email'" class="action-box">
            <div class="form-group">
              <label>Assunto do E-mail *</label>
              <input v-model="actionForm.assunto" type="text" class="crm-input" placeholder="Ex: Olá {{cliente}}, temos um presente de aniversário!" required />
            </div>
            <div class="form-group mt-2">
              <label>Mensagem do E-mail (suporta {{cliente}}) *</label>
              <textarea v-model="actionForm.mensagem" class="crm-textarea" style="height: 90px;" placeholder="Olá {{cliente}}, tudo bem? Estamos com condições especiais..." required></textarea>
            </div>
            <span class="hint-text">💡 Dica: Use <code>&#123;&#123;cliente&#125;&#125;</code> para personalizar automaticamente o nome do cliente.</span>
          </div>

          <!-- Campos dinâmicos de Tarefa / Alerta -->
          <div v-else class="action-box">
            <div class="form-group">
              <label>Título *</label>
              <input v-model="actionForm.titulo" type="text" class="crm-input" placeholder="Ex: Entrar em contato com {{cliente}}" required />
            </div>
            <div class="form-group mt-2">
              <label>Descrição detalhada</label>
              <textarea v-model="actionForm.descricao" class="crm-textarea" placeholder="Instruções para o vendedor..."></textarea>
            </div>
          </div>

          <div class="form-group flex items-center gap-2 mt-2">
            <label class="cursor-pointer flex items-center gap-2">
              <input type="checkbox" v-model="form.ativa" />
              <strong>Automação Ativa</strong>
            </label>
          </div>

          <div class="modal-footer">
            <button type="button" @click="openModal = false" class="crm-btn btn-outline">Cancelar</button>
            <button type="submit" class="crm-btn btn-primary" :disabled="form.processing">
              {{ isEditing ? 'Salvar Alterações' : 'Criar Automação' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Lista de Automações -->
    <div class="crm-card">
      <div v-if="!automacoes.length" class="empty-msg">Nenhuma automação configurada.</div>
      <table v-else class="crm-table">
        <thead>
          <tr>
            <th>Nome / Descrição</th>
            <th>Gatilho Alvo</th>
            <th>Ação Configurada</th>
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
              <span class="badge-gatilho">{{ gatilhos[a.gatilho] || a.gatilho }}</span>
            </td>
            <td>
              <span class="badge-action" :class="getActionType(a)">
                {{ getActionLabel(a) }}
              </span>
            </td>
            <td>{{ a.delay_dias ? `${a.delay_dias} dia(s)` : 'Imediato' }}</td>
            <td>
              <div class="metric-group">
                <span>⚡ {{ a.total_execucoes || 0 }} execuções</span>
                <span class="pct-sucesso">({{ getTaxaSucesso(a) }}% OK)</span>
              </div>
            </td>
            <td>
              <button @click="toggleStatus(a)" class="status-toggle cursor-pointer" :class="{ active: a.ativa }">
                {{ a.ativa ? '● Ativa' : '○ Pausada' }}
              </button>
            </td>
            <td>
              <div class="flex gap-2">
                <button @click="executarAgora(a)" class="crm-btn btn-success small" title="Executar manualmente agora">⚡ Executar</button>
                <button @click="editAutomation(a)" class="crm-btn btn-secondary small">✏️ Editar</button>
                <button @click="deletar(a.id)" class="crm-btn btn-danger small">Deletar</button>
              </div>
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
const isEditing = ref(false)
const editingId = ref(null)

const form = useForm({
  nome: '',
  descricao: '',
  gatilho: 'aniversario',
  delay_dias: 0,
  ativa: true,
  acoes: []
})

const actionForm = ref({
  tipo: 'enviar_email',
  assunto: 'Olá {{cliente}}, mensagem especial!',
  mensagem: 'Olá {{cliente}}, temos novidades especiais para você na 90 Store.',
  titulo: 'Entrar em contato com {{cliente}}',
  descricao: ''
})

function openCreateModal() {
  isEditing.value = false
  editingId.value = null
  form.reset()
  form.nome = ''
  form.descricao = ''
  form.gatilho = 'aniversario'
  form.delay_dias = 0
  form.ativa = true
  actionForm.value = {
    tipo: 'enviar_email',
    assunto: 'Olá {{cliente}}, mensagem especial!',
    mensagem: 'Olá {{cliente}}, temos novidades especiais para você na 90 Store.',
    titulo: 'Entrar em contato com {{cliente}}',
    descricao: ''
  }
  openModal.value = true
}

function editAutomation(a) {
  isEditing.value = true
  editingId.value = a.id
  form.nome = a.nome
  form.descricao = a.descricao || ''
  form.gatilho = a.gatilho
  form.delay_dias = a.delay_dias || 0
  form.ativa = Boolean(a.ativa)

  const acao = (a.acoes && a.acoes.length) ? a.acoes[0] : {}
  const tipo = acao.tipo || 'enviar_email'
  const dados = acao.dados || {}

  actionForm.value = {
    tipo: tipo,
    assunto: dados.assunto || 'Olá {{cliente}}, mensagem especial!',
    mensagem: dados.mensagem || dados.descricao || 'Olá {{cliente}}, temos novidades para você.',
    titulo: dados.titulo || 'Entrar em contato com {{cliente}}',
    descricao: dados.descricao || ''
  }

  openModal.value = true
}

function submitAutomation() {
  let acaoObj = { tipo: actionForm.value.tipo, dados: {} }

  if (actionForm.value.tipo === 'enviar_email') {
    acaoObj.dados = {
      assunto: actionForm.value.assunto,
      mensagem: actionForm.value.mensagem
    }
  } else {
    acaoObj.dados = {
      titulo: actionForm.value.titulo,
      descricao: actionForm.value.descricao,
      prioridade: 'media'
    }
  }

  form.acoes = [acaoObj]

  if (isEditing.value && editingId.value) {
    form.put(route('admin.crm.automacoes.update', editingId.value), {
      onSuccess: () => {
        openModal.value = false
        form.reset()
      }
    })
  } else {
    form.post(route('admin.crm.automacoes.store'), {
      onSuccess: () => {
        openModal.value = false
        form.reset()
      }
    })
  }
}

function toggleStatus(a) {
  router.put(route('admin.crm.automacoes.update', a.id), {
    ativa: !a.ativa
  }, { preserveScroll: true })
}

function executarAgora(a) {
  if (confirm(`Deseja disparar a automação "${a.nome}" agora mesmo para os clientes elegíveis?`)) {
    router.post(route('admin.crm.automacoes.executar', a.id), {}, { preserveScroll: true })
  }
}

function deletar(id) {
  if (confirm('Deseja realmente remover esta automação?')) {
    router.delete(route('admin.crm.automacoes.destroy', id), { preserveScroll: true })
  }
}

function getActionType(a) {
  const acao = (a.acoes && a.acoes.length) ? a.acoes[0] : {}
  return acao.tipo || 'default'
}

function getActionLabel(a) {
  const acao = (a.acoes && a.acoes.length) ? a.acoes[0] : {}
  if (acao.tipo === 'enviar_email') return '📧 E-mail (Titan Mail)'
  if (acao.tipo === 'criar_alerta') return '🔔 Alerta no CRM'
  if (acao.tipo === 'criar_tarefa') return '📋 Tarefa para Vendedor'
  return '⚙️ Ação Geral'
}

function getTaxaSucesso(a) {
  if (!a.total_execucoes) return 100
  return Math.round(((a.total_sucesso || 0) / a.total_execucoes) * 100)
}
</script>

<style scoped>
.crm-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(4px); }
.crm-modal-body { background: linear-gradient(135deg, #111827, #1e1b4b); border: 1px solid rgba(99,102,241,0.25); border-radius: 16px; width: 90%; max-width: 600px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); display: flex; flex-direction: column; overflow: hidden; max-height: 90vh; overflow-y: auto; }
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
.action-box { background: rgba(255,255,255,0.03); border: 1px dashed rgba(99,102,241,0.3); border-radius: 10px; padding: 1rem; }
.hint-text { font-size: 0.75rem; color: #818cf8; display: block; margin-top: 0.5rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: .75rem; border-top: 1px solid rgba(255,255,255,.05); padding-top: 1rem; margin-top: .5rem; }
.btn-outline { background: transparent; border: 1px solid rgba(255,255,255,.1); color: #cbd5e1; }
.btn-outline:hover { background: rgba(255,255,255,.03); }

.header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.page-title { font-size: 1.8rem; font-weight: 800; color: #f1f5f9; margin: 0; }
.page-sub { color: #64748b; margin-top: 0.25rem; }

.crm-btn { border-radius: 8px; padding: .55rem 1.25rem; font-weight: 600; cursor: pointer; border: none; font-size: .85rem; transition: background .2s; }
.btn-primary { background: #6366f1; color: #fff; }
.btn-primary:hover { background: #4f46e5; }
.btn-secondary { background: rgba(255,255,255,0.08); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.1); }
.btn-secondary:hover { background: rgba(255,255,255,0.15); }
.btn-success { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
.btn-success:hover { background: #10b981; color: #fff; }
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
.badge-action { padding: .2rem .5rem; border-radius: 6px; font-size: .75rem; font-weight: 600; }
.badge-action.enviar_email { background: rgba(16,185,129,0.15); color: #34d399; }
.badge-action.criar_alerta { background: rgba(245,158,11,0.15); color: #fbbf24; }
.badge-action.criar_tarefa { background: rgba(59,130,246,0.15); color: #60a5fa; }

.metric-group { font-size: .8rem; color: #94a3b8; }
.pct-sucesso { margin-left: .4rem; color: #10b981; font-weight: 700; }

.status-toggle { font-size: .75rem; font-weight: 700; text-transform: uppercase; padding: .25rem .6rem; border-radius: 20px; border: none; background: rgba(255,255,255,0.05); color: #94a3b8; }
.status-toggle.active { background: rgba(16,185,129,.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }

.empty-msg { text-align: center; color: #475569; padding: 2rem; font-size: .9rem; }
</style>
