<template>
  <AdminLayout title="CRM — Comercial">
    <div class="header-container">
      <div>
        <h1 class="page-title">📈 Painel Comercial CRM</h1>
        <p class="page-sub">Previsibilidade de receita, conversão de propostas e funil de vendas</p>
      </div>
      <div class="crm-hero-actions">
        <select v-model="periodo" @change="recarregar" class="crm-periodo-select">
          <option value="7">Últimos 7 dias</option>
          <option value="15">Últimos 15 dias</option>
          <option value="30">Últimos 30 dias</option>
          <option value="60">Últimos 60 dias</option>
          <option value="90">Últimos 90 dias</option>
        </select>
      </div>
    </div>

    <!-- Metrics Row -->
    <div class="metrics-row">
      <div class="metric-box">
        <span class="box-label">Receita Estimada (Pipeline)</span>
        <span class="box-val font-blue">R$ {{ fmt(receitaPrevista?.total) }}</span>
      </div>
      <div class="metric-box">
        <span class="box-label">Receita Ponderada (Probabilidade)</span>
        <span class="box-val font-green">R$ {{ fmt(receitaPrevista?.prevista) }}</span>
      </div>
    </div>

    <div class="pipe-details-grid">
      <!-- Funil list -->
      <div class="crm-card">
        <h2 class="section-title">📊 Leads por Fase do Funil</h2>
        <div class="funnel-list">
          <div v-for="f in pipeline" :key="f.id" class="funnel-step">
            <div class="step-meta">
              <span class="step-name">{{ f.nome }}</span>
              <span class="step-count">{{ f.total_leads }} leads</span>
            </div>
            <div class="step-bar-bg">
              <div class="step-bar" :style="{ width: getStepPct(f.total_leads) + '%', backgroundColor: f.cor }"></div>
            </div>
            <div class="step-footer">
              <span class="step-money">Total: R$ {{ fmt(f.valor_total) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Ranking de Vendas -->
      <div class="crm-card">
        <h2 class="section-title">🏆 Desempenho do Time</h2>
        <table class="crm-table">
          <thead>
            <tr>
              <th>Vendedor</th>
              <th>Clientes Ativos</th>
              <th>Pedidos</th>
              <th>Total Gerado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="v in rankingVend" :key="v.id">
              <td>{{ v.nome }}</td>
              <td>{{ v.total_clientes }}</td>
              <td>{{ v.total_pedidos }}</td>
              <td class="money-cell font-green">R$ {{ fmt(v.receita_total) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  pipeline: Array,
  receitaPrevista: Object,
  rankingVend: Array,
  periodo: [String, Number]
})

const periodo = ref(String(props.periodo ?? '30'))

function recarregar() {
  router.get(route('admin.crm.comercial'), { periodo: periodo.value }, { preserveScroll: true })
}

const maxLeads = computed(() => {
  return Math.max(...(props.pipeline || []).map(f => f.total_leads), 1)
})

function getStepPct(leads) {
  return Math.max(5, (leads / maxLeads.value) * 100)
}

function fmt(val) {
  return Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.page-title { font-size: 1.8rem; font-weight: 800; color: #f1f5f9; margin: 0; }
.page-sub { color: #64748b; margin-top: 0.25rem; }

.crm-periodo-select { background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); color: #fff; border-radius: 8px; padding: .5rem 1rem; font-size: .9rem; cursor: pointer; }
.crm-periodo-select option { background: #111827; }

.metrics-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
.metric-box { background: rgba(17,24,39,.95); border: 1px solid rgba(99, 102, 241, 0.15); border-radius: 12px; padding: 1.25rem; display: flex; flex-direction: column; }
.box-label { font-size: .75rem; color: #64748b; text-transform: uppercase; font-weight: 700; }
.box-val { font-size: 1.6rem; font-weight: 800; margin-top: .4rem; }
.font-blue { color: #60a5fa; }
.font-green { color: #10b981; }

.pipe-details-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 1.5rem; }
@media (max-width: 900px) { .pipe-details-grid { grid-template-columns: 1fr; } }

.crm-card { background: rgba(17,24,39,.95); border: 1px solid rgba(99, 102, 241, 0.15); border-radius: 16px; padding: 1.5rem; }
.section-title { font-size: 1.1rem; font-weight: 700; color: #f1f5f9; margin-bottom: 1.25rem; }

.funnel-list { display: flex; flex-direction: column; gap: 1rem; }
.funnel-step { display: flex; flex-direction: column; }
.step-meta { display: flex; justify-content: space-between; font-size: .85rem; font-weight: 600; color: #e2e8f0; }
.step-bar-bg { background: rgba(255,255,255,.03); height: 8px; border-radius: 4px; margin: .4rem 0; overflow: hidden; }
.step-bar { height: 100%; border-radius: 4px; transition: width .5s ease-out; }
.step-footer { text-align: right; }
.step-money { font-size: .78rem; color: #64748b; font-weight: 600; }

.crm-table { width: 100%; border-collapse: collapse; text-align: left; }
.crm-table th { color: #64748b; font-size: .75rem; text-transform: uppercase; padding: .75rem .5rem; border-bottom: 1px solid rgba(255,255,255,.05); }
.crm-table td { padding: .75rem .5rem; border-bottom: 1px solid rgba(255,255,255,.02); color: #cbd5e1; font-size: .85rem; }
.money-cell { font-weight: 700; }
</style>
