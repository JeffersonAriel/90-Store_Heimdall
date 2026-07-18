<template>
  <AdminLayout title="CRM — Leads">
    <div class="header-container">
      <div>
        <h1 class="page-title">🎯 Gestão de Leads</h1>
        <p class="page-sub">Controle de contatos comerciais em fase de prospecção</p>
      </div>
      <button @click="openModal = true" class="crm-btn btn-primary">+ Novo Lead</button>
    </div>

    <!-- Leads Table -->
    <div class="crm-card">
      <div v-if="!leads.data.length" class="empty-msg">Nenhum lead cadastrado ainda.</div>
      <table v-else class="crm-table">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Empresa</th>
            <th>Origem</th>
            <th>Temperatura</th>
            <th>Fase</th>
            <th>Valor Esperado</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="lead in leads.data" :key="lead.id">
            <td>
              <span class="lead-name">{{ lead.nome }}</span>
              <span class="lead-email">{{ lead.email || 'Sem e-mail' }}</span>
            </td>
            <td>{{ lead.empresa || '—' }}</td>
            <td>
              <span class="badge-origem">{{ lead.origem }}</span>
            </td>
            <td>
              <span class="temp-indicator" :class="lead.temperatura">
                {{ lead.temperatura === 'quente' ? '🔥 Quente' : lead.temperatura === 'morno' ? '☀️ Morno' : '❄️ Frio' }}
              </span>
            </td>
            <td>{{ lead.etapa?.nome || 'Novo' }}</td>
            <td class="money-val">R$ {{ fmt(lead.valor_esperado) }}</td>
            <td>
              <Link :href="route('admin.crm.leads.show', lead.id)" class="crm-btn btn-outline small">
                Abrir 360°
              </Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ leads: Object, etapas: Array, funcionarios: Array })
const openModal = ref(false)

function fmt(val) {
  return Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
.page-title { font-size: 1.8rem; font-weight: 800; color: #f1f5f9; margin: 0; }
.page-sub { color: #64748b; margin-top: 0.25rem; }

.crm-btn { border-radius: 8px; padding: .55rem 1.25rem; font-weight: 600; cursor: pointer; border: none; font-size: .85rem; transition: background .2s; }
.btn-primary { background: #6366f1; color: #fff; }
.btn-primary:hover { background: #4f46e5; }
.btn-outline { background: transparent; border: 1px solid rgba(255,255,255,.1); color: #cbd5e1; }
.btn-outline:hover { background: rgba(255,255,255,.03); }
.small { padding: .35rem .75rem; font-size: .75rem; }

.crm-card { background: rgba(17,24,39,.95); border: 1px solid rgba(255,255,255,.05); border-radius: 16px; padding: 1.5rem; }
.crm-table { width: 100%; border-collapse: collapse; text-align: left; }
.crm-table th { color: #64748b; font-size: .75rem; text-transform: uppercase; padding: .75rem 1rem; border-bottom: 1px solid rgba(255,255,255,.05); }
.crm-table td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,.02); color: #cbd5e1; font-size: .85rem; }

.lead-name { display: block; font-weight: 700; color: #e2e8f0; }
.lead-email { font-size: .75rem; color: #64748b; display: block; margin-top: .15rem; }

.badge-origem { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); padding: .2rem .5rem; border-radius: 6px; font-size: .75rem; color: #94a3b8; text-transform: uppercase; }
.temp-indicator { font-weight: 600; font-size: .8rem; }
.temp-indicator.quente { color: #f87171; }
.temp-indicator.morno { color: #fb923c; }
.temp-indicator.frio { color: #60a5fa; }
.money-val { font-weight: 700; color: #10b981; }
.empty-msg { text-align: center; color: #475569; padding: 2rem; font-size: .9rem; }
</style>
