<template>
  <AdminLayout title="CRM — Campanhas">
    <div class="header-container">
      <div>
        <h1 class="page-title">📣 Campanhas de Marketing</h1>
        <p class="page-sub">Envio de disparos segmentados e controle de métricas</p>
      </div>
      <button @click="openModal = true" class="crm-btn btn-primary">+ Nova Campanha</button>
    </div>

    <!-- Modal Nova Campanha -->
    <div v-if="openModal" class="crm-modal-overlay" @click.self="openModal = false">
      <div class="crm-modal-body">
        <div class="modal-header">
          <h2>📣 Nova Campanha de Marketing</h2>
          <button @click="openModal = false" class="close-btn">&times;</button>
        </div>
        <form @submit.prevent="submitCampaign" class="modal-form">
          <div class="form-group">
            <label>Nome da Campanha *</label>
            <input v-model="form.nome" type="text" class="crm-input" required placeholder="Ex: Campanha Dia dos Pais" />
          </div>
          <div class="form-group">
            <label>Descrição</label>
            <textarea v-model="form.descricao" class="crm-textarea" placeholder="Descreva os detalhes da campanha..."></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Canal / Tipo *</label>
              <select v-model="form.tipo" class="crm-select" required>
                <option value="whatsapp">💬 WhatsApp</option>
                <option value="email">✉️ E-mail</option>
              </select>
            </div>
            <div class="form-group">
              <label>Segmento de Clientes *</label>
              <select v-model="form.segmento_id" class="crm-select" required>
                <option v-for="seg in segmentos" :key="seg.id" :value="seg.id">{{ seg.nome }}</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Template de Mensagem *</label>
            <select v-model="form.template_id" class="crm-select" required>
              <option v-for="temp in templates" :key="temp.id" :value="temp.id">{{ temp.nome }}</option>
            </select>
          </div>
          <div class="modal-footer">
            <button type="button" @click="openModal = false" class="crm-btn btn-outline">Cancelar</button>
            <button type="submit" class="crm-btn btn-primary" :disabled="form.processing">Criar Campanha</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Campaigns List -->
    <div class="crm-card">
      <table class="crm-table">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Tipo</th>
            <th>Segmento</th>
            <th>Status</th>
            <th>Destinatários</th>
            <th>Métricas</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="campanha in campanhas.data" :key="campanha.id">
            <td>
              <span class="camp-name">{{ campanha.nome }}</span>
              <span class="camp-desc">{{ campanha.descricao || 'Sem descrição' }}</span>
            </td>
            <td>
              <span class="type-icon">{{ campanha.tipo === 'whatsapp' ? '💬 WhatsApp' : '✉️ Email' }}</span>
            </td>
            <td>{{ campanha.segmento?.nome || 'Filtro manual' }}</td>
            <td>
              <span class="status-badge" :class="campanha.status">{{ campanha.status }}</span>
            </td>
            <td>{{ campanha.total_destinatarios }}</td>
            <td class="metrics-cell">
              <div v-if="campanha.status === 'enviada' || campanha.status === 'enviando'">
                <span>✅ {{ campanha.total_enviados }}</span>
                <span class="err-count">❌ {{ campanha.total_erros }}</span>
              </div>
              <span v-else>—</span>
            </td>
            <td>
              <button v-if="campanha.status === 'rascunho'" @click="disparar(campanha.id)" class="crm-btn btn-success small">
                🚀 Disparar
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

const props = defineProps({ campanhas: Object, segmentos: Array, templates: Array })
const openModal = ref(false)

const form = useForm({
  nome: '',
  descricao: '',
  tipo: 'whatsapp',
  segmento_id: props.segmentos?.[0]?.id || '',
  template_id: props.templates?.[0]?.id || '',
  status: 'rascunho',
})

function submitCampaign() {
  form.post(route('admin.crm.campanhas.store'), {
    onSuccess: () => {
      openModal.value = false
      form.reset()
    }
  })
}

function disparar(id) {
  if (confirm('Deseja iniciar o disparo desta campanha agora?')) {
    router.post(route('admin.crm.campanhas.disparar', id), {}, { preserveScroll: true })
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
.btn-success { background: #10b981; color: #fff; }
.btn-success:hover { background: #059669; }
.small { padding: .35rem .75rem; font-size: .75rem; }

.crm-card { background: rgba(17,24,39,.95); border: 1px solid rgba(255,255,255,.05); border-radius: 16px; padding: 1.5rem; }

.crm-table { width: 100%; border-collapse: collapse; text-align: left; }
.crm-table th { color: #64748b; font-size: .75rem; text-transform: uppercase; padding: .75rem 1rem; border-bottom: 1px solid rgba(255,255,255,.05); }
.crm-table td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,.02); color: #cbd5e1; font-size: .85rem; }

.camp-name { display: block; font-weight: 700; color: #e2e8f0; }
.camp-desc { font-size: .75rem; color: #64748b; display: block; margin-top: .15rem; }

.status-badge { font-size: .7rem; font-weight: 700; text-transform: uppercase; padding: .2rem .5rem; border-radius: 20px; }
.status-badge.rascunho { background: rgba(255,255,255,.05); color: #94a3b8; }
.status-badge.enviando { background: rgba(245,158,11,.15); color: #fbbf24; }
.status-badge.enviada { background: rgba(16,185,129,.15); color: #10b981; }

.metrics-cell { font-weight: 600; }
.err-count { margin-left: .5rem; color: #f87171; }
</style>
