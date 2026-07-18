<template>
  <AdminLayout title="CRM — Campanhas">
    <div class="header-container">
      <div>
        <h1 class="page-title">📣 Campanhas de Marketing</h1>
        <p class="page-sub">Envio de disparos segmentados e controle de métricas</p>
      </div>
      <button @click="openModal = true" class="crm-btn btn-primary">+ Nova Campanha</button>
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
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ campanhas: Object, segmentos: Array, templates: Array })
const openModal = ref(false)

function disparar(id) {
  if (confirm('Deseja iniciar o disparo desta campanha agora?')) {
    router.post(route('admin.crm.campanhas.disparar', id), {}, { preserveScroll: true })
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
