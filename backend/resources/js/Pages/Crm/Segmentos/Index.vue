<template>
  <AdminLayout title="CRM — Segmentos">
    <div class="header-container">
      <div>
        <h1 class="page-title">🏷️ Segmentos de Clientes</h1>
        <p class="page-sub">Segmentação automática baseada em comportamento e LTV</p>
      </div>
      <button @click="openModal = true" class="crm-btn btn-primary">+ Novo Segmento</button>
    </div>

    <div class="segments-grid">
      <div v-for="segmento in segmentos" :key="segmento.id" class="segment-card" :style="{ borderLeftColor: segmento.cor }">
        <div class="segment-header">
          <div class="segment-title-area">
            <span class="segment-icon" :style="{ backgroundColor: segmento.cor + '22', color: segmento.cor }">
              🏷️
            </span>
            <h3 class="segment-name">{{ segmento.nome }}</h3>
          </div>
          <span class="badge-type" :class="segmento.tipo">{{ segmento.tipo }}</span>
        </div>
        <p class="segment-desc">{{ segmento.descricao || 'Sem descrição.' }}</p>
        
        <div class="segment-meta">
          <div class="meta-item">
            <span class="meta-label">Membros</span>
            <span class="meta-value">{{ segmento.clientes_count }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Último Cálculo</span>
            <span class="meta-value">{{ segmento.atualizado_em ? new Date(segmento.atualizado_em).toLocaleDateString() : 'Nunca' }}</span>
          </div>
        </div>

        <div class="segment-actions">
          <button v-if="segmento.tipo === 'automatico'" @click="recalcular(segmento.id)" class="crm-btn btn-outline small">
            🔄 Recalcular
          </button>
          <button @click="deletar(segmento.id)" class="crm-btn btn-danger small">Deletar</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ segmentos: Array })
const openModal = ref(false)

function recalcular(id) {
  router.post(route('admin.crm.segmentos.recalcular', id), {}, { preserveScroll: true })
}

function deletar(id) {
  if (confirm('Deseja realmente excluir este segmento?')) {
    router.delete(route('admin.crm.segmentos.destroy', id), { preserveScroll: true })
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
.btn-outline { background: transparent; border: 1px solid rgba(255,255,255,.1); color: #cbd5e1; }
.btn-outline:hover { background: rgba(255,255,255,.03); }
.btn-danger { background: rgba(239,68,68,.1); color: #f87171; border: 1px solid rgba(239,68,68,.2); }
.btn-danger:hover { background: #ef4444; color: #fff; }
.small { padding: .35rem .75rem; font-size: .75rem; }

.segments-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
.segment-card { background: rgba(17,24,39,.95); border: 1px solid rgba(255,255,255,.05); border-left: 4px solid #6366f1; border-radius: 12px; padding: 1.25rem; }
.segment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: .75rem; }
.segment-title-area { display: flex; align-items: center; gap: .75rem; }
.segment-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.segment-name { font-size: 1rem; font-weight: 700; color: #e2e8f0; margin: 0; }
.badge-type { font-size: .65rem; font-weight: 700; text-transform: uppercase; padding: .15rem .4rem; border-radius: 4px; }
.badge-type.automatico { background: rgba(16,185,129,.1); color: #10b981; }
.badge-type.manual { background: rgba(99,102,241,.1); color: #818cf8; }

.segment-desc { font-size: .85rem; color: #94a3b8; margin: 0 0 1rem; line-height: 1.4; height: 40px; overflow: hidden; text-overflow: ellipsis; }

.segment-meta { display: flex; gap: 1.5rem; border-top: 1px solid rgba(255,255,255,.05); padding-top: .75rem; margin-bottom: 1rem; }
.meta-item { display: flex; flex-direction: column; }
.meta-label { font-size: .7rem; color: #64748b; text-transform: uppercase; }
.meta-value { font-size: .9rem; font-weight: 700; color: #cbd5e1; }

.segment-actions { display: flex; gap: .5rem; justify-content: flex-end; }
</style>
