<template>
  <AdminLayout title="Pipeline CRM — Kanban">
    <div class="pipe-header">
      <div>
        <h1 class="pipe-title">🗂️ Pipeline de Vendas</h1>
        <p class="pipe-sub">{{ resumo.total_leads }} leads · R$ {{ fmt(resumo.valor_total_pipeline) }} no funil</p>
      </div>
      <Link :href="route('admin.crm.leads.store')" class="crm-btn crm-btn--primary" @click.prevent="openLead = true">
        + Novo Lead
      </Link>
    </div>

    <!-- Kanban Board -->
    <div class="kanban">
      <div v-for="etapa in etapas" :key="etapa.id" class="kanban-col"
           @dragover.prevent @drop="drop($event, etapa.id)">
        <!-- Column Header -->
        <div class="kanban-col__header" :style="{borderTopColor: etapa.cor}">
          <div class="kanban-col__title">
            <span class="kanban-col__dot" :style="{background: etapa.cor}"></span>
            {{ etapa.nome }}
          </div>
          <div class="kanban-col__stats">
            <span class="kanban-badge" :style="{background: etapa.cor + '33', color: etapa.cor}">{{ etapa.total_leads }}</span>
            <span class="kanban-money">R$ {{ fmtK(etapa.valor_total) }}</span>
          </div>
        </div>

        <!-- Cards -->
        <div class="kanban-cards">
          <div v-for="lead in etapa.leads" :key="lead.id"
               class="kanban-card"
               draggable="true"
               @dragstart="dragStart($event, lead.id)"
               @click="$inertia.visit(route('admin.crm.leads.show', lead.id))">
            <div class="kanban-card__top">
              <span class="kanban-card__temp" :class="`temp--${lead.temperatura}`">
                {{ lead.temperatura === 'quente' ? '🔥' : lead.temperatura === 'morno' ? '☀️' : '❄️' }}
              </span>
              <span class="kanban-card__prob">{{ lead.probabilidade }}%</span>
            </div>
            <div class="kanban-card__nome">{{ lead.nome }}</div>
            <div v-if="lead.empresa" class="kanban-card__empresa">🏢 {{ lead.empresa }}</div>
            <div v-if="lead.valor_esperado" class="kanban-card__valor">
              R$ {{ fmt(lead.valor_esperado) }}
            </div>
            <div class="kanban-card__footer">
              <span v-if="lead.responsavel" class="kanban-card__resp">👤 {{ lead.responsavel.nome }}</span>
              <span class="kanban-card__data">{{ relTime(lead.created_at) }}</span>
            </div>
          </div>

          <div v-if="!etapa.leads.length" class="kanban-empty">
            Nenhum lead aqui
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ etapas: Array, funcionarios: Array, resumo: Object })

let draggedLeadId = null

function dragStart(e, leadId) {
  draggedLeadId = leadId
  e.dataTransfer.effectAllowed = 'move'
}

function drop(e, etapaId) {
  if (!draggedLeadId) return
  router.post(route('admin.crm.leads.mover-etapa', draggedLeadId), { etapa_id: etapaId }, {
    preserveScroll: true,
    onSuccess: () => { draggedLeadId = null },
  })
}

function fmt(v) {
  return Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function fmtK(v) {
  const n = Number(v || 0)
  if (n >= 1000) return (n / 1000).toFixed(1) + 'k'
  return fmt(v)
}
function relTime(dt) {
  if (!dt) return ''
  const d = Math.floor((Date.now() - new Date(dt)) / 86400000)
  if (!d) return 'hoje'
  if (d === 1) return 'ontem'
  return `${d}d atrás`
}
</script>

<style scoped>
.pipe-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
.pipe-title  { font-size: 1.8rem; font-weight: 800; color: #f1f5f9; margin: 0; }
.pipe-sub    { color: #64748b; margin: .3rem 0 0; font-size: .9rem; }

.crm-btn { border-radius: 10px; padding: .55rem 1.25rem; font-weight: 600; cursor: pointer; text-decoration: none; font-size: .9rem; transition: all .2s; display: inline-flex; align-items: center; gap: .4rem; }
.crm-btn--primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; box-shadow: 0 4px 15px rgba(99,102,241,.4); }
.crm-btn--primary:hover { transform: translateY(-1px); }

/* Kanban */
.kanban {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  padding-bottom: 1rem;
  align-items: flex-start;
}
.kanban-col {
  flex-shrink: 0;
  width: 270px;
  background: rgba(15,23,42,.7);
  border-radius: 16px;
  border: 1px solid rgba(99,102,241,.15);
  overflow: hidden;
}
.kanban-col__header {
  padding: 1rem;
  border-top: 3px solid #6366f1;
  background: rgba(99,102,241,.05);
}
.kanban-col__title {
  display: flex; align-items: center; gap: .5rem;
  font-weight: 700; font-size: .9rem; color: #e2e8f0;
}
.kanban-col__dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.kanban-col__stats { display: flex; align-items: center; justify-content: space-between; margin-top: .5rem; }
.kanban-badge { font-size: .75rem; font-weight: 700; padding: .15rem .5rem; border-radius: 20px; }
.kanban-money { font-size: .75rem; color: #64748b; }

.kanban-cards { padding: .75rem; display: flex; flex-direction: column; gap: .6rem; min-height: 80px; }

.kanban-card {
  background: linear-gradient(135deg, rgba(30,41,59,.9), rgba(15,23,42,.95));
  border: 1px solid rgba(99,102,241,.15);
  border-radius: 12px;
  padding: .9rem;
  cursor: pointer;
  transition: all .2s;
}
.kanban-card:hover {
  border-color: rgba(99,102,241,.4);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(0,0,0,.3);
}
.kanban-card__top { display: flex; justify-content: space-between; align-items: center; margin-bottom: .5rem; }
.kanban-card__temp { font-size: 1rem; }
.kanban-card__prob { font-size: .72rem; font-weight: 700; color: #94a3b8; background: rgba(255,255,255,.05); padding: .15rem .4rem; border-radius: 6px; }
.kanban-card__nome { font-weight: 700; font-size: .9rem; color: #e2e8f0; margin-bottom: .25rem; }
.kanban-card__empresa { font-size: .78rem; color: #64748b; margin-bottom: .2rem; }
.kanban-card__valor { font-size: .85rem; font-weight: 700; color: #34d399; margin-bottom: .4rem; }
.kanban-card__footer { display: flex; justify-content: space-between; align-items: center; }
.kanban-card__resp { font-size: .72rem; color: #64748b; }
.kanban-card__data { font-size: .7rem; color: #475569; }

.kanban-empty { text-align: center; color: #1e293b; padding: 1.5rem .5rem; font-size: .8rem; border: 1px dashed rgba(99,102,241,.1); border-radius: 10px; }
</style>
