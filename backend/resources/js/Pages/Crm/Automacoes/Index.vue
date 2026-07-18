<template>
  <AdminLayout title="CRM — Automações">
    <div class="header-container">
      <div>
        <h1 class="page-title">🤖 Automações Inteligentes</h1>
        <p class="page-sub">Gatilhos automáticos baseados no comportamento do cliente</p>
      </div>
      <button @click="openModal = true" class="crm-btn btn-primary">+ Criar Automação</button>
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
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ automacoes: Array, gatilhos: Object })
const openModal = ref(false)

function deletar(id) {
  if (confirm('Deseja realmente remover esta automação?')) {
    router.delete(route('admin.crm.automacoes.destroy', id), { preserveScroll: true })
  }
}
</script>

<style scoped>
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
