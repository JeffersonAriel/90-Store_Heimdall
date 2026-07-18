<template>
  <AdminLayout title="CRM — Templates de Mensagem">
    <div class="header-container">
      <div>
        <h1 class="page-title">💬 Templates de Mensagem</h1>
        <p class="page-sub">Modelos de mensagens pré-configuradas para WhatsApp e Email</p>
      </div>
      <button @click="openModal = true" class="crm-btn btn-primary">+ Novo Template</button>
    </div>

    <!-- Templates Grid -->
    <div class="templates-grid">
      <div v-for="t in templates" :key="t.id" class="template-card">
        <div class="card-header">
          <span class="badge-channel" :class="t.tipo">{{ t.tipo }}</span>
          <span class="badge-category">{{ t.categoria }}</span>
        </div>
        <h3 class="template-name">{{ t.nome }}</h3>
        
        <div class="message-preview-container">
          <pre class="message-preview">{{ t.conteudo }}</pre>
        </div>

        <div class="card-actions">
          <button @click="deletar(t.id)" class="crm-btn btn-danger small">Deletar</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ templates: Array, variaveis: Object })
const openModal = ref(false)

function deletar(id) {
  if (confirm('Deseja realmente remover este template?')) {
    router.delete(route('admin.crm.templates.destroy', id), { preserveScroll: true })
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

.templates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
.template-card { background: rgba(17,24,39,.95); border: 1px solid rgba(255,255,255,.05); border-radius: 12px; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; }

.card-header { display: flex; justify-content: space-between; margin-bottom: .75rem; }
.badge-channel { font-size: .65rem; font-weight: 700; text-transform: uppercase; padding: .15rem .4rem; border-radius: 4px; }
.badge-channel.whatsapp { background: rgba(16,185,129,.1); color: #10b981; }
.badge-channel.email { background: rgba(99,102,241,.1); color: #818cf8; }
.badge-category { font-size: .65rem; color: #64748b; font-weight: 600; }

.template-name { font-size: 1rem; font-weight: 700; color: #e2e8f0; margin: 0 0 .75rem; }

.message-preview-container { background: rgba(255,255,255,.02); border: 1px solid rgba(255,255,255,.04); border-radius: 8px; padding: .75rem; margin-bottom: 1rem; height: 120px; overflow-y: auto; }
.message-preview { font-family: monospace; font-size: .8rem; color: #94a3b8; white-space: pre-wrap; margin: 0; line-height: 1.4; }

.card-actions { display: flex; justify-content: flex-end; }
</style>
