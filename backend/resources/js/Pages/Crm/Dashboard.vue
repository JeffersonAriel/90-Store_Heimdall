<template>
  <AdminLayout title="CRM — Dashboard Executivo">

    <!-- ── Hero Header ──────────────────────────────────────────────── -->
    <div class="crm-hero">
      <div class="crm-hero__inner">
        <div>
          <h1 class="crm-hero__title">
            <span class="crm-hero__icon">📊</span>
            Dashboard CRM
          </h1>
          <p class="crm-hero__sub">Inteligência comercial em tempo real</p>
        </div>
        <div class="crm-hero__actions">
          <select v-model="periodo" @change="recarregar" class="crm-periodo-select">
            <option value="7">Últimos 7 dias</option>
            <option value="15">Últimos 15 dias</option>
            <option value="30">Últimos 30 dias</option>
            <option value="60">Últimos 60 dias</option>
            <option value="90">Últimos 90 dias</option>
          </select>
          <Link :href="route('admin.crm.leads.index')" class="crm-btn crm-btn--primary">
            + Novo Lead
          </Link>
        </div>
      </div>
    </div>

    <!-- ── KPI Grid ─────────────────────────────────────────────────── -->
    <div class="crm-kpi-grid">
      <KpiCard label="Clientes Ativos"     :value="kpis.clientes_ativos"     icon="👥" color="#6366f1" />
      <KpiCard label="Novos no Período"    :value="kpis.clientes_novos"      icon="✨" color="#10b981" />
      <KpiCard label="Inativos +90 dias"   :value="kpis.clientes_inativos_90"icon="😴" color="#f59e0b" />
      <KpiCard label="Recorrentes"         :value="kpis.clientes_recorrentes" icon="🔄" color="#8b5cf6" />
      <KpiCard label="Receita no Período"  :value="'R$ ' + fmt(kpis.receita_realizada)" icon="💰" color="#10b981" />
      <KpiCard label="Ticket Médio"        :value="'R$ ' + fmt(kpis.ticket_medio)"  icon="🎫" color="#3b82f6" />
      <KpiCard label="LTV Médio"           :value="'R$ ' + fmt(kpis.ltv_medio)"     icon="📈" color="#6366f1" />
      <KpiCard label="Taxa de Recompra"    :value="kpis.taxa_recompra + '%'"        icon="🛒" color="#8b5cf6" />
      <KpiCard label="Leads Ativos"        :value="kpis.leads_ativos"               icon="🎯" color="#f97316" />
      <KpiCard label="Taxa de Conversão"   :value="kpis.taxa_conversao + '%'"       icon="🏆" color="#10b981" />
      <KpiCard label="Churn Rate"          :value="kpis.churn_rate + '%'"           icon="📉" color="#ef4444" />
      <KpiCard label="NPS Médio"           :value="kpis.nps_medio ?? '—'"           icon="⭐" color="#f59e0b" />
      <KpiCard label="Pedidos Abertos"     :value="kpis.pedidos_abertos"            icon="📦" color="#3b82f6" />
      <KpiCard label="Alertas Ativos"      :value="kpis.alertas_ativos"             icon="🔔" color="#ef4444" />
      <KpiCard label="Tarefas Hoje"        :value="kpis.tarefas_hoje"               icon="✅" color="#f97316" />
      <KpiCard label="Contatos Realizados" :value="kpis.contatos_realizados"        icon="📞" color="#6366f1" />
    </div>

    <!-- ── Duas Colunas: Gráfico + Alertas ──────────────────────────── -->
    <div class="crm-two-col">

      <!-- Evolução de Receita -->
      <div class="crm-card">
        <div class="crm-card__header">
          <h2 class="crm-card__title">📈 Evolução de Receita</h2>
          <span class="crm-badge crm-badge--gray">{{ periodo }} dias</span>
        </div>
        <div class="crm-chart-area">
          <div v-if="!evolucaoReceita.length" class="crm-empty">Sem dados neste período</div>
          <div v-else class="crm-bars">
            <div v-for="dia in evolucaoReceita" :key="dia.data" class="crm-bar-item"
                 :title="`${dia.data}: R$ ${fmt(dia.receita)}`">
              <div class="crm-bar"
                   :style="{height: barHeight(dia.receita) + '%'}"
                   :class="{'crm-bar--highlight': isToday(dia.data)}">
              </div>
              <span class="crm-bar__label">{{ fmtDate(dia.data) }}</span>
            </div>
          </div>
        </div>
        <div v-if="evolucaoReceita.length" class="crm-chart-total">
          Total: <strong>R$ {{ fmt(evolucaoReceita.reduce((s, d) => s + parseFloat(d.receita || 0), 0)) }}</strong>
        </div>
      </div>

      <!-- Alertas Inteligentes -->
      <div class="crm-card">
        <div class="crm-card__header">
          <h2 class="crm-card__title">🔔 Alertas Inteligentes</h2>
          <span v-if="alertas.length" class="crm-badge crm-badge--red">{{ alertas.length }}</span>
        </div>
        <div v-if="!alertas.length" class="crm-empty crm-empty--success">
          ✅ Nenhum alerta pendente
        </div>
        <div v-else class="crm-alertas-list">
          <AlertaCard v-for="alerta in alertas" :key="alerta.id" :alerta="alerta" />
        </div>
      </div>
    </div>

    <!-- ── Rankings ──────────────────────────────────────────────────── -->
    <div class="crm-two-col">

      <!-- Top Vendedores -->
      <div class="crm-card">
        <div class="crm-card__header">
          <h2 class="crm-card__title">🏆 Top Vendedores</h2>
        </div>
        <div v-if="!rankingVend.length" class="crm-empty">Sem dados</div>
        <table v-else class="crm-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Vendedor</th>
              <th>Clientes</th>
              <th>Pedidos</th>
              <th>Receita</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(v, i) in rankingVend" :key="v.id">
              <td class="crm-rank">{{ i + 1 }}</td>
              <td>{{ v.nome }}</td>
              <td>{{ v.total_clientes }}</td>
              <td>{{ v.total_pedidos }}</td>
              <td class="crm-money">R$ {{ fmt(v.receita_total) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Top Clientes -->
      <div class="crm-card">
        <div class="crm-card__header">
          <h2 class="crm-card__title">👑 Top Clientes</h2>
        </div>
        <div v-if="!rankingClientes.length" class="crm-empty">Sem dados</div>
        <table v-else class="crm-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Cliente</th>
              <th>Pedidos</th>
              <th>Total Gasto</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(c, i) in rankingClientes" :key="c.id">
              <td class="crm-rank">{{ i + 1 }}</td>
              <td>
                <Link :href="route('admin.crm.clientes.show', c.id)" class="crm-link">
                  {{ c.nome_completo }}
                </Link>
                <span v-if="c.segmento_crm" class="crm-badge crm-badge--vip">{{ c.segmento_crm }}</span>
              </td>
              <td>{{ c.total_pedidos }}</td>
              <td class="crm-money">R$ {{ fmt(c.total_gasto) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Links Rápidos ─────────────────────────────────────────────── -->
    <div class="crm-quick-links">
      <Link v-for="link in quickLinks" :key="link.href" :href="link.href" class="crm-quick-link">
        <span class="crm-quick-link__icon">{{ link.icon }}</span>
        <span class="crm-quick-link__label">{{ link.label }}</span>
      </Link>
    </div>

  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import KpiCard from '@/Components/Crm/KpiCard.vue'
import AlertaCard from '@/Components/Crm/AlertaCard.vue'

const props = defineProps({
  kpis:           Object,
  rankingVend:    Array,
  rankingClientes:Array,
  evolucaoReceita:Array,
  alertas:        Array,
  periodo:        [String, Number],
})

const periodo = ref(String(props.periodo ?? '30'))

function recarregar() {
  router.get(route('admin.crm.dashboard'), { periodo: periodo.value }, { preserveScroll: true })
}

function fmt(val) {
  return Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(d) {
  return d ? d.substring(5) : ''
}

function isToday(d) {
  return d === new Date().toISOString().substring(0, 10)
}

const maxReceita = computed(() => {
  return Math.max(...(props.evolucaoReceita || []).map(d => parseFloat(d.receita) || 0), 1)
})

function barHeight(val) {
  return Math.max(4, (parseFloat(val) / maxReceita.value) * 100)
}

const quickLinks = [
  { href: route('admin.crm.pipeline'),        icon: '🗂️',  label: 'Pipeline' },
  { href: route('admin.crm.leads.index'),     icon: '🎯',  label: 'Leads' },
  { href: route('admin.crm.clientes.index'),  icon: '👥',  label: 'Clientes CRM' },
  { href: route('admin.crm.tarefas.index'),   icon: '✅',  label: 'Tarefas' },
  { href: route('admin.crm.campanhas.index'), icon: '📣',  label: 'Campanhas' },
  { href: route('admin.crm.templates.index'), icon: '💬',  label: 'Templates' },
  { href: route('admin.crm.automacoes.index'),icon: '🤖',  label: 'Automações' },
  { href: route('admin.crm.segmentos.index'), icon: '🏷️',  label: 'Segmentos' },
  { href: route('admin.crm.comercial'),       icon: '📈',  label: 'Comercial' },
]
</script>

<style scoped>
/* ── Hero ───────────────────────────────────────────────────────────── */
.crm-hero {
  background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
  border-radius: 20px;
  padding: 2rem 2.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 20px 60px rgba(99,102,241,.3);
}
.crm-hero__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}
.crm-hero__title {
  font-size: 2rem;
  font-weight: 800;
  color: #fff;
  margin: 0;
  display: flex;
  align-items: center;
  gap: .6rem;
}
.crm-hero__icon { font-size: 2.2rem; }
.crm-hero__sub  { color: rgba(255,255,255,.7); margin: .3rem 0 0; font-size: .95rem; }
.crm-hero__actions { display: flex; gap: .75rem; align-items: center; flex-wrap: wrap; }

/* ── Periodo Select ─────────────────────────────────────────────────── */
.crm-periodo-select {
  background: rgba(255,255,255,.15);
  border: 1px solid rgba(255,255,255,.3);
  color: #fff;
  border-radius: 10px;
  padding: .5rem 1rem;
  font-size: .9rem;
  cursor: pointer;
}
.crm-periodo-select option { background: #312e81; color: #fff; }

/* ── Buttons ────────────────────────────────────────────────────────── */
.crm-btn { border-radius: 10px; padding: .5rem 1.25rem; font-weight: 600; cursor: pointer; text-decoration: none; font-size: .9rem; transition: all .2s; display: inline-flex; align-items: center; gap: .4rem; }
.crm-btn--primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; box-shadow: 0 4px 15px rgba(99,102,241,.4); }
.crm-btn--primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,.5); }

/* ── KPI Grid ───────────────────────────────────────────────────────── */
.crm-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

/* ── Two Col Layout ─────────────────────────────────────────────────── */
.crm-two-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}
@media (max-width: 900px) {
  .crm-two-col { grid-template-columns: 1fr; }
}

/* ── Card ───────────────────────────────────────────────────────────── */
.crm-card {
  background: linear-gradient(135deg, rgba(30,27,75,.8) 0%, rgba(17,24,39,.9) 100%);
  border: 1px solid rgba(99,102,241,.2);
  border-radius: 16px;
  padding: 1.5rem;
  backdrop-filter: blur(10px);
}
.crm-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}
.crm-card__title {
  font-size: 1rem;
  font-weight: 700;
  color: #e2e8f0;
  margin: 0;
}

/* ── Chart (barras simples) ─────────────────────────────────────────── */
.crm-chart-area { height: 160px; overflow-x: auto; }
.crm-bars {
  display: flex;
  align-items: flex-end;
  gap: 4px;
  height: 100%;
  padding-bottom: 24px;
}
.crm-bar-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  flex: 1;
  min-width: 14px;
  height: 100%;
  cursor: pointer;
  position: relative;
}
.crm-bar {
  width: 100%;
  min-height: 4px;
  background: linear-gradient(to top, #6366f1, #a78bfa);
  border-radius: 4px 4px 0 0;
  transition: background .2s;
}
.crm-bar--highlight { background: linear-gradient(to top, #10b981, #34d399); }
.crm-bar__label {
  position: absolute;
  bottom: 0;
  font-size: .55rem;
  color: #94a3b8;
  white-space: nowrap;
}
.crm-chart-total {
  margin-top: .75rem;
  font-size: .85rem;
  color: #94a3b8;
  text-align: right;
}
.crm-chart-total strong { color: #a78bfa; }

/* ── Alertas ────────────────────────────────────────────────────────── */
.crm-alertas-list { display: flex; flex-direction: column; gap: .5rem; max-height: 260px; overflow-y: auto; }

/* ── Table ──────────────────────────────────────────────────────────── */
.crm-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
.crm-table th { color: #94a3b8; text-align: left; padding: .5rem .75rem; border-bottom: 1px solid rgba(99,102,241,.15); font-weight: 600; font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; }
.crm-table td { padding: .6rem .75rem; color: #cbd5e1; border-bottom: 1px solid rgba(255,255,255,.04); }
.crm-table tr:hover td { background: rgba(99,102,241,.06); }
.crm-rank { font-weight: 800; color: #a78bfa; }
.crm-money { font-weight: 700; color: #34d399; }

/* ── Badges ─────────────────────────────────────────────────────────── */
.crm-badge { font-size: .7rem; font-weight: 600; padding: .2rem .5rem; border-radius: 20px; }
.crm-badge--gray { background: rgba(148,163,184,.15); color: #94a3b8; }
.crm-badge--red  { background: rgba(239,68,68,.2); color: #f87171; }
.crm-badge--vip  { background: rgba(245,158,11,.2); color: #fbbf24; margin-left: .4rem; }

/* ── Links ──────────────────────────────────────────────────────────── */
.crm-link { color: #818cf8; text-decoration: none; }
.crm-link:hover { color: #a78bfa; }

/* ── Empty ──────────────────────────────────────────────────────────── */
.crm-empty { text-align: center; color: #475569; padding: 2rem; font-size: .9rem; }
.crm-empty--success { color: #34d399; }

/* ── Quick Links ────────────────────────────────────────────────────── */
.crm-quick-links {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}
.crm-quick-link {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .5rem;
  padding: 1.25rem 1rem;
  background: linear-gradient(135deg, rgba(30,27,75,.8), rgba(17,24,39,.9));
  border: 1px solid rgba(99,102,241,.2);
  border-radius: 16px;
  text-decoration: none;
  transition: all .2s;
  cursor: pointer;
}
.crm-quick-link:hover {
  border-color: rgba(99,102,241,.5);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(99,102,241,.2);
}
.crm-quick-link__icon { font-size: 2rem; }
.crm-quick-link__label { font-size: .8rem; font-weight: 600; color: #cbd5e1; text-align: center; }
</style>
