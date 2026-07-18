<template>
  <AdminLayout title="CRM — Segmentos">
    <div class="header-container">
      <div>
        <h1 class="page-title">🏷️ Segmentos de Clientes</h1>
        <p class="page-sub">Segmentação automática baseada em comportamento e LTV</p>
      </div>
      <button @click="openModal = true" class="crm-btn btn-primary">+ Novo Segmento</button>
    </div>

    <!-- Modal Novo Segmento -->
    <div v-if="openModal" class="crm-modal-overlay" @click.self="openModal = false">
      <div class="crm-modal-body">
        <div class="modal-header">
          <h2>🏷️ Novo Segmento de Clientes</h2>
          <button @click="openModal = false" class="close-btn">&times;</button>
        </div>
        <form @submit.prevent="submitSegment" class="modal-form">
          <div class="form-group">
            <label>Nome do Segmento *</label>
            <input v-model="form.nome" type="text" class="crm-input" required placeholder="Ex: Clientes Inativos" />
          </div>
          <div class="form-group">
            <label>Descrição</label>
            <textarea v-model="form.descricao" class="crm-textarea" placeholder="Ex: Clientes sem compras nos últimos 30 dias"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Cor Identificadora *</label>
              <input v-model="form.cor" type="color" class="crm-input" style="height: 40px; padding: 2px;" required />
            </div>
            <div class="form-group">
              <label>Tipo *</label>
              <select v-model="form.tipo" class="crm-select" required>
                <option value="automatico">🤖 Automático (Regras de Banco)</option>
                <option value="manual">👤 Manual (Lista Fixa)</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="openModal = false" class="crm-btn btn-outline">Cancelar</button>
            <button type="submit" class="crm-btn btn-primary" :disabled="form.processing">Criar Segmento</button>
          </div>
        </form>
      </div>
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
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ segmentos: Array })
const openModal = ref(false)

const form = useForm({
  nome: '',
  descricao: '',
  cor: '#6366f1',
  tipo: 'automatico',
  regras: [],
  ativo: true
})

function submitSegment() {
  form.post(route('admin.crm.segmentos.store'), {
    onSuccess: () => {
      openModal.value = false
      form.reset()
    }
  })
}

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
