<template>
  <AdminLayout title="CRM — Detalhes do Lead">
    <div class="header-container">
      <div>
        <h1 class="page-title">🎯 Lead: {{ lead.nome }}</h1>
        <p class="page-sub">{{ lead.empresa || 'Empresa não informada' }} · Origem: {{ lead.origem }}</p>
      </div>
      <div class="actions">
        <Link :href="route('admin.crm.pipeline')" class="crm-btn btn-outline">Voltar ao Kanban</Link>
      </div>
    </div>

    <!-- Lead Info & Timeline Grid -->
    <div class="crm-360-grid">
      <!-- Left Column: Interactions -->
      <div class="crm-col-left">
        <!-- Interactive Actions -->
        <div class="crm-card">
          <div class="tab-headers">
            <span class="active-tab-title">✍️ Anotação rápida do Lead</span>
          </div>
          <div class="tab-content">
            <textarea placeholder="Adicione uma nota sobre as negociações com este lead..." class="crm-textarea"></textarea>
            <div class="form-actions">
              <button class="crm-btn btn-primary">Salvar Anotação</button>
            </div>
          </div>
        </div>

        <!-- History -->
        <div class="crm-card">
          <h2 class="section-title">⏱️ Histórico de Movimentação</h2>
          <div class="timeline">
            <div v-for="event in timeline" :key="event.id" class="timeline-item">
              <div class="timeline-body">
                <div class="timeline-header">
                  <span class="event-title">{{ event.titulo }}</span>
                  <span class="event-time">{{ relTime(event.created_at) }}</span>
                </div>
                <p v-if="event.descricao" class="event-desc">{{ event.descricao }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Profile Specs -->
      <div class="crm-col-right">
        <div class="crm-card info-card">
          <h2 class="section-title">📋 Especificações do Deal</h2>
          <div class="info-list">
            <div class="info-item">
              <span class="info-label">Temperatura</span>
              <span class="info-value">{{ lead.temperatura }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Probabilidade de Fechamento</span>
              <span class="info-value">{{ lead.probabilidade }}%</span>
            </div>
            <div class="info-item">
              <span class="info-label">Valor Estimado</span>
              <span class="info-value font-green">R$ {{ fmt(lead.valor_esperado) }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Próxima Ação</span>
              <span class="info-value">{{ lead.proxima_acao || 'Agendar contato' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  lead: Object,
  timeline: Array,
  etapas: Array,
  funcionarios: Array
})

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
.header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.page-title { font-size: 1.8rem; font-weight: 800; color: #f1f5f9; margin: 0; }
.page-sub { color: #64748b; margin-top: 0.25rem; }

.crm-btn { border-radius: 8px; padding: .55rem 1.25rem; font-weight: 600; cursor: pointer; border: none; font-size: .85rem; transition: background .2s; }
.btn-primary { background: #6366f1; color: #fff; }
.btn-outline { background: transparent; border: 1px solid rgba(255,255,255,.1); color: #cbd5e1; }

.crm-360-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 1.5rem; }
.crm-card { background: rgba(17,24,39,.95); border: 1px solid rgba(99, 102, 241, 0.15); border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem; }
.section-title { font-size: 1.1rem; font-weight: 700; color: #f1f5f9; margin-bottom: 1.25rem; }

.active-tab-title { font-size: .85rem; font-weight: 700; color: #818cf8; }
.crm-textarea { width: 100%; height: 80px; padding: .65rem 1rem; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 8px; color: #cbd5e1; font-size: .9rem; margin-top: .75rem; margin-bottom: .75rem; resize: none; }
.form-actions { display: flex; justify-content: flex-end; }

.timeline { display: flex; flex-direction: column; gap: 1.25rem; }
.timeline-item { background: rgba(255,255,255,.02); border-radius: 10px; padding: .85rem 1rem; border: 1px solid rgba(255,255,255,.02); }
.timeline-header { display: flex; justify-content: space-between; align-items: center; }
.event-title { font-weight: 700; font-size: .88rem; color: #e2e8f0; }
.event-time { font-size: .75rem; color: #475569; }
.event-desc { font-size: .85rem; color: #94a3b8; margin-top: .4rem; }

.info-list { display: flex; flex-direction: column; gap: 1rem; }
.info-item { display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,.03); padding-bottom: .6rem; }
.info-label { font-size: .85rem; color: #64748b; }
.info-value { font-size: .85rem; color: #cbd5e1; font-weight: 600; }
.font-green { color: #10b981; }
</style>
