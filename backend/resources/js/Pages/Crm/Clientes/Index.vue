<template>
  <AdminLayout title="CRM — Clientes">
    <div class="header-container">
      <div>
        <h1 class="page-title">👥 Gestão de Clientes CRM</h1>
        <p class="page-sub">Base unificada de contatos com inteligência de LTV e Churn</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="crm-card filter-card">
      <div class="filter-row">
        <input v-model="search" placeholder="Buscar cliente por nome ou email..." class="crm-input" @input="filtrar" />
        <select v-model="segmento" class="crm-select" @change="filtrar">
          <option value="">Todos os Segmentos</option>
          <option value="VIP">👑 VIP</option>
          <option value="Premium">⭐ Premium</option>
          <option value="Padrao">Padrão</option>
        </select>
        <select v-model="risco" class="crm-select" @change="filtrar">
          <option value="">Risco de Churn</option>
          <option value="baixo">🟢 Baixo</option>
          <option value="medio">🟡 Médio</option>
          <option value="alto">🔴 Alto</option>
        </select>
      </div>
    </div>

    <!-- Client Cards Grid -->
    <div class="clients-grid">
      <div v-for="c in clientes.data" :key="c.id" class="client-card">
        <div class="card-header">
          <h3 class="client-name">{{ c.nome_social || c.nome_completo }}</h3>
          <span v-if="c.segmento_crm" class="badge-segment">{{ c.segmento_crm }}</span>
        </div>
        <p class="client-email">📧 {{ c.email }}</p>
        <p class="client-phone">📞 {{ c.telefone || c.whatsapp || 'Sem contato' }}</p>

        <div class="card-metrics">
          <div class="metric-item">
            <span class="m-label">LTV</span>
            <span class="m-val font-green">R$ {{ fmt(c.ltv) }}</span>
          </div>
          <div class="metric-item">
            <span class="m-label">Pedidos</span>
            <span class="m-val">{{ c.total_pedidos_count }}</span>
          </div>
          <div class="metric-item">
            <span class="m-label">Churn</span>
            <span class="m-val" :style="{ color: c.cor_risco_churn }">{{ c.risco_churn || 'baixo' }}</span>
          </div>
        </div>

        <div class="card-actions">
          <Link :href="route('admin.crm.clientes.show', c.id)" class="crm-btn btn-outline full-width">
            Visualizar CRM 360°
          </Link>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ clientes: Object, vendors: Array, filters: Object })

const search = ref(props.filters?.search || '')
const segmento = ref(props.filters?.segmento || '')
const risco = ref(props.filters?.risco || '')

function filtrar() {
  router.get(route('admin.crm.clientes.index'), {
    search: search.value,
    segmento: segmento.value,
    risco: risco.value
  }, { preserveState: true, replace: true })
}

function fmt(val) {
  return Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.page-title { font-size: 1.8rem; font-weight: 800; color: #f1f5f9; margin: 0; }
.page-sub { color: #64748b; margin-top: 0.25rem; }

.crm-btn { border-radius: 8px; padding: .55rem 1.25rem; font-weight: 600; cursor: pointer; border: none; font-size: .85rem; transition: background .2s; }
.btn-outline { background: transparent; border: 1px solid rgba(255,255,255,.1); color: #cbd5e1; }
.btn-outline:hover { background: rgba(255,255,255,.03); }
.full-width { width: 100%; text-align: center; }

.crm-card { background: rgba(17,24,39,.95); border: 1px solid rgba(255,255,255,.05); border-radius: 16px; padding: 1.25rem; margin-bottom: 1.5rem; }
.filter-row { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; }
.crm-input, .crm-select { width: 100%; padding: .65rem 1rem; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 8px; color: #cbd5e1; font-size: .9rem; }

.clients-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
.client-card { background: rgba(17,24,39,.95); border: 1px solid rgba(255,255,255,.05); border-radius: 12px; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; }
.card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: .5rem; }
.client-name { font-size: 1rem; font-weight: 700; color: #e2e8f0; margin: 0; }
.badge-segment { font-size: .65rem; font-weight: 700; color: #fbbf24; background: rgba(245,158,11,.1); padding: .15rem .4rem; border-radius: 4px; }
.client-email, .client-phone { font-size: .8rem; color: #64748b; margin: .25rem 0; }

.card-metrics { display: flex; justify-content: space-between; border-top: 1px solid rgba(255,255,255,.05); border-bottom: 1px solid rgba(255,255,255,.05); padding: .75rem 0; margin: .75rem 0; }
.metric-item { display: flex; flex-direction: column; align-items: center; }
.m-label { font-size: .65rem; color: #64748b; text-transform: uppercase; }
.m-val { font-size: .88rem; font-weight: 700; color: #cbd5e1; }
.font-green { color: #10b981; }

.card-actions { margin-top: .5rem; }
</style>
