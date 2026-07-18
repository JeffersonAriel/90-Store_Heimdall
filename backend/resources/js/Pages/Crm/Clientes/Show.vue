<template>
  <AdminLayout title="CRM — CRM 360°">
    <!-- Hero Header -->
    <div class="client-hero">
      <div class="client-hero__inner">
        <div class="client-info">
          <div class="client-avatar">
            {{ (cliente.nome_social || cliente.nome_completo).charAt(0).toUpperCase() }}
          </div>
          <div>
            <h1 class="client-name">
              {{ cliente.nome_social || cliente.nome_completo }}
              <span v-if="cliente.nome_social" class="client-social-name">({{ cliente.nome_completo }})</span>
            </h1>
            <p class="client-sub">
              {{ cliente.email }} · {{ cliente.telefone || cliente.whatsapp || 'Sem telefone' }}
              <span v-if="cliente.is_vip" class="badge-vip">👑 VIP</span>
            </p>
          </div>
        </div>

        <div class="client-stats">
          <div class="stat-item">
            <span class="stat-label">LTV</span>
            <span class="stat-val font-green">R$ {{ fmt(cliente.ltv) }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Pedidos</span>
            <span class="stat-val">{{ cliente.total_pedidos_count }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Risco Churn</span>
            <span class="stat-val" :style="{ color: cliente.cor_risco_churn }">
              {{ (cliente.risco_churn || 'baixo').toUpperCase() }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Layout -->
    <div class="crm-360-grid">
      <!-- Left Column: Tabs & Interactions -->
      <div class="crm-col-left">
        <!-- Interactive CRM Forms -->
        <div class="crm-card forms-card">
          <div class="tab-headers">
            <button @click="activeTab = 'nota'" :class="{ active: activeTab === 'nota' }">✍️ Nota</button>
            <button @click="activeTab = 'contato'" :class="{ active: activeTab === 'contato' }">📞 Contato</button>
            <button @click="activeTab = 'tarefa'" :class="{ active: activeTab === 'tarefa' }">✅ Tarefa</button>
            <button @click="activeTab = 'documento'" :class="{ active: activeTab === 'documento' }">📁 Doc</button>
          </div>

          <div class="tab-content">
            <!-- Nota Form -->
            <form v-if="activeTab === 'nota'" @submit.prevent="submitNota">
              <input v-model="formNota.titulo" placeholder="Título da nota (opcional)" class="crm-input" />
              <textarea v-model="formNota.conteudo" placeholder="Escreva uma anotação interna..." class="crm-textarea" required></textarea>
              <div class="form-actions">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="formNota.privada" /> Privada (só para mim)
                </label>
                <button type="submit" class="crm-btn btn-primary" :disabled="formNota.processing">Salvar Nota</button>
              </div>
            </form>

            <!-- Contato Form -->
            <form v-if="activeTab === 'contato'" @submit.prevent="submitContato">
              <div class="form-row">
                <select v-model="formContato.tipo" class="crm-select" required>
                  <option value="whatsapp">WhatsApp</option>
                  <option value="email">E-mail</option>
                  <option value="ligacao">Ligação</option>
                  <option value="visita">Visita</option>
                  <option value="reuniao">Reunião</option>
                  <option value="outro">Outro</option>
                </select>
                <input v-model="formContato.assunto" placeholder="Assunto" class="crm-input" required />
              </div>
              <textarea v-model="formContato.descricao" placeholder="Resumo do contato..." class="crm-textarea"></textarea>
              <div class="form-actions">
                <input type="number" v-model="formContato.duracao_minutos" placeholder="Duração (min)" class="crm-input min-input" />
                <button type="submit" class="crm-btn btn-primary" :disabled="formContato.processing">Registrar</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Timeline Events -->
        <div class="crm-card timeline-card">
          <h2 class="section-title">⏱️ Linha do Tempo de Interações</h2>
          <div class="timeline">
            <div v-for="event in timeline" :key="event.id" class="timeline-item">
              <div class="timeline-icon" :style="{ backgroundColor: event.cor_resolvida }">
                <span>{{ event.icone_resolvido === 'phone' ? '📞' : event.icone_resolvido === 'mail' ? '✉️' : event.icone_resolvido === 'shopping-cart' ? '🛒' : '💬' }}</span>
              </div>
              <div class="timeline-body">
                <div class="timeline-header">
                  <span class="event-title">{{ event.titulo }}</span>
                  <span class="event-time">{{ relTime(event.created_at) }}</span>
                </div>
                <p v-if="event.descricao" class="event-desc">{{ event.descricao }}</p>
                <span class="event-user">por {{ event.usuario_nome }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Info, Tasks, Segments -->
      <div class="crm-col-right">
        <!-- Client Details Card -->
        <div class="crm-card info-card">
          <h2 class="section-title">👤 Perfil Comercial</h2>
          <div class="info-list">
            <div class="info-item">
              <span class="info-label">Vendedor Responsável</span>
              <span class="info-value">{{ cliente.vendedor?.nome || 'Não definido' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Segmentação CRM</span>
              <span class="info-value">{{ cliente.segmento_crm || 'Padrão' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Canal de Aquisição</span>
              <span class="info-value">{{ cliente.canal_aquisicao || 'Direto' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Último Pedido</span>
              <span class="info-value">{{ cliente.ultimo_pedido_em ? new Date(cliente.ultimo_pedido_em).toLocaleDateString() : 'Nunca' }}</span>
            </div>
          </div>
        </div>

        <!-- Tasks Card -->
        <div class="crm-card tasks-card">
          <h2 class="section-title">✅ Próximas Tarefas</h2>
          <div v-if="!tarefas.length" class="empty-msg">Nenhuma tarefa agendada.</div>
          <div v-else class="tasks-list">
            <div v-for="tarefa in tarefas" :key="tarefa.id" class="task-item">
              <div class="task-details">
                <span class="task-title">{{ tarefa.titulo }}</span>
                <span class="task-due" :class="{ overdue: new Date(tarefa.vencimento_em) < new Date() }">
                  📅 {{ new Date(tarefa.vencimento_em).toLocaleDateString() }}
                </span>
              </div>
              <button @click="concluirTarefa(tarefa.id)" class="btn-check">✓</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  cliente: Object,
  timeline: Array,
  notas: Array,
  contatos: Array,
  tarefas: Array,
  ocorrencias: Array,
  documentos: Array,
  funcionarios: Array
})

const activeTab = ref('nota')

const formNota = useForm({
  titulo: '',
  conteudo: '',
  privada: false
})

const formContato = useForm({
  tipo: 'whatsapp',
  assunto: '',
  descricao: '',
  duracao_minutos: null,
  realizado_em: new Date().toISOString().substring(0, 16)
})

function submitNota() {
  formNota.post(route('admin.crm.clientes.nota', props.cliente.id), {
    preserveScroll: true,
    onSuccess: () => formNota.reset()
  })
}

function submitContato() {
  formContato.post(route('admin.crm.clientes.contato', props.cliente.id), {
    preserveScroll: true,
    onSuccess: () => formContato.reset()
  })
}

function concluirTarefa(id) {
  router.patch(route('admin.crm.tarefas.concluir', id), {}, { preserveScroll: true })
}

function fmt(val) {
  return Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function relTime(dt) {
  const diff = Math.floor((Date.now() - new Date(dt)) / 60000)
  if (diff < 1) return 'agora'
  if (diff < 60) return `${diff}m`
  if (diff < 1440) return `${Math.floor(diff/60)}h`
  return `${Math.floor(diff/1440)}d`
}
</script>

<style scoped>
.client-hero {
  background: linear-gradient(135deg, #1e1b4b, #111827);
  border: 1px solid rgba(99, 102, 241, 0.25);
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 10px 30px rgba(0,0,0,.25);
}
.client-hero__inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
}
.client-info {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}
.client-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  font-weight: 800;
  box-shadow: 0 4px 15px rgba(99,102,241,.4);
}
.client-name { font-size: 1.8rem; font-weight: 800; color: #f1f5f9; margin: 0; }
.client-social-name { font-size: 1rem; color: #64748b; font-weight: 400; }
.client-sub { color: #94a3b8; margin: 0.25rem 0 0; }
.badge-vip { background: rgba(245,158,11,.15); color: #fbbf24; padding: .15rem .5rem; border-radius: 20px; font-size: .8rem; font-weight: 600; }

.client-stats { display: flex; gap: 2rem; }
.stat-item { display: flex; flex-direction: column; align-items: flex-end; }
.stat-label { font-size: .72rem; color: #64748b; text-transform: uppercase; font-weight: 700; }
.stat-val { font-size: 1.4rem; font-weight: 800; color: #e2e8f0; }
.font-green { color: #10b981; }

.crm-360-grid {
  display: grid;
  grid-template-columns: 1.6fr 1fr;
  gap: 1.5rem;
}
@media(max-width: 991px) {
  .crm-360-grid { grid-template-columns: 1fr; }
}

.crm-card {
  background: rgba(17,24,39,.95);
  border: 1px solid rgba(99, 102, 241, 0.15);
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}
.section-title { font-size: 1.1rem; font-weight: 700; color: #f1f5f9; margin-bottom: 1.25rem; }

/* Tabs */
.tab-headers { display: flex; gap: .5rem; border-bottom: 1px solid rgba(255,255,255,.05); padding-bottom: .75rem; margin-bottom: 1.25rem; }
.tab-headers button { background: none; border: none; color: #64748b; font-weight: 600; padding: .5rem 1rem; border-radius: 8px; cursor: pointer; transition: all .2s; }
.tab-headers button:hover { background: rgba(255,255,255,.03); color: #cbd5e1; }
.tab-headers button.active { background: rgba(99,102,241,.1); color: #818cf8; }

/* Forms input controls */
.crm-input, .crm-textarea, .crm-select { width: 100%; padding: .65rem 1rem; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 8px; color: #cbd5e1; font-size: .9rem; margin-bottom: .75rem; transition: border .2s; }
.crm-input:focus, .crm-textarea:focus { border-color: rgba(99,102,241,.4); outline: none; }
.crm-textarea { height: 100px; resize: none; }
.form-row { display: grid; grid-template-columns: 1fr 2fr; gap: .75rem; }
.form-actions { display: flex; justify-content: space-between; align-items: center; }
.checkbox-label { color: #64748b; font-size: .85rem; display: flex; align-items: center; gap: .4rem; }
.crm-btn { border-radius: 8px; padding: .55rem 1.25rem; font-weight: 600; cursor: pointer; border: none; font-size: .85rem; transition: background .2s; }
.btn-primary { background: #6366f1; color: #fff; }
.btn-primary:hover { background: #4f46e5; }
.min-input { width: 120px; margin-bottom: 0; }

/* Timeline */
.timeline { display: flex; flex-direction: column; gap: 1.25rem; position: relative; padding-left: 1.5rem; border-left: 2px solid rgba(255,255,255,.05); margin-left: .5rem; }
.timeline-item { display: flex; gap: 1rem; position: relative; }
.timeline-icon { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .85rem; position: absolute; left: -25px; transform: translateX(-50%); border: 3px solid #111827; }
.timeline-body { flex: 1; background: rgba(255,255,255,.02); border-radius: 10px; padding: .85rem 1rem; border: 1px solid rgba(255,255,255,.02); }
.timeline-header { display: flex; justify-content: space-between; align-items: center; }
.event-title { font-weight: 700; font-size: .88rem; color: #e2e8f0; }
.event-time { font-size: .75rem; color: #475569; }
.event-desc { font-size: .85rem; color: #94a3b8; margin: .4rem 0 0; line-height: 1.4; }
.event-user { font-size: .7rem; color: #64748b; display: block; margin-top: .4rem; }

/* Profile/Info Card */
.info-list { display: flex; flex-direction: column; gap: 1rem; }
.info-item { display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,.03); padding-bottom: .6rem; }
.info-label { font-size: .85rem; color: #64748b; }
.info-value { font-size: .85rem; color: #cbd5e1; font-weight: 600; }

/* Tasks */
.tasks-list { display: flex; flex-direction: column; gap: .75rem; }
.task-item { display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,.02); padding: .75rem 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,.03); }
.task-details { display: flex; flex-direction: column; }
.task-title { font-size: .88rem; color: #e2e8f0; font-weight: 600; }
.task-due { font-size: .75rem; color: #64748b; margin-top: .15rem; }
.task-due.overdue { color: #f87171; }
.btn-check { width: 24px; height: 24px; border-radius: 50%; border: 1px solid rgba(16,185,129,.4); background: rgba(16,185,129,.1); color: #10b981; font-size: .75rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .2s; }
.btn-check:hover { background: #10b981; color: #fff; }
.empty-msg { text-align: center; color: #475569; padding: 1rem; font-size: .85rem; }
</style>
