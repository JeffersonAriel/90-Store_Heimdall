<template>
  <AdminLayout title="CRM — Templates de Mensagem">
    <div class="header-container">
      <div>
        <h1 class="page-title">💬 Templates de Mensagem</h1>
        <p class="page-sub">Modelos de mensagens pré-configuradas para WhatsApp e Email</p>
      </div>
      <button @click="openModal = true" class="crm-btn btn-primary">+ Novo Template</button>
    </div>

    <!-- Modal Novo Template -->
    <div v-if="openModal" class="crm-modal-overlay" @click.self="openModal = false">
      <div class="crm-modal-body">
        <div class="modal-header">
          <h2>💬 Novo Template de Mensagem</h2>
          <button @click="openModal = false" class="close-btn">&times;</button>
        </div>
        <form @submit.prevent="submitTemplate" class="modal-form">
          <div class="form-group">
            <label>Nome do Template *</label>
            <input v-model="form.nome" type="text" class="crm-input" required placeholder="Ex: Boas-vindas WhatsApp" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Canal / Tipo *</label>
              <select v-model="form.tipo" class="crm-select" required>
                <option value="whatsapp">💬 WhatsApp</option>
                <option value="email">✉️ E-mail</option>
              </select>
            </div>
            <div class="form-group">
              <label>Categoria *</label>
              <select v-model="form.categoria" class="crm-select" required>
                <option value="boas_vendas">Boas-vindas</option>
                <option value="cobranca">Cobrança</option>
                <option value="promocional">Promocional</option>
                <option value="pos_venda">Pós-venda</option>
                <option value="outros">Outros</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Conteúdo da Mensagem *</label>
            <textarea v-model="form.conteudo" class="crm-textarea" required placeholder="Escreva a mensagem. Use tags como {{cliente}}, {{pedido}}..."></textarea>
            <small class="help-text">Variáveis disponíveis: {{ Object.keys(variaveis || {}).join(', ') }}</small>
          </div>
          <div class="modal-footer">
            <button type="button" @click="openModal = false" class="crm-btn btn-outline">Cancelar</button>
            <button type="submit" class="crm-btn btn-primary" :disabled="form.processing">Criar Template</button>
          </div>
        </form>
      </div>
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
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ templates: Array, variaveis: Object })
const openModal = ref(false)

const form = useForm({
  nome: '',
  tipo: 'whatsapp',
  categoria: 'boas_vendas',
  conteudo: '',
})

function submitTemplate() {
  form.post(route('admin.crm.templates.store'), {
    onSuccess: () => {
      openModal.value = false
      form.reset()
    }
  })
}

function deletar(id) {
  if (confirm('Deseja realmente remover este template?')) {
    router.delete(route('admin.crm.templates.destroy', id), { preserveScroll: true })
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
.crm-textarea { height: 100px; resize: none; }
.crm-input:focus, .crm-select:focus, .crm-textarea:focus { border-color: rgba(99,102,241,0.4); outline: none; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: .75rem; border-top: 1px solid rgba(255,255,255,.05); padding-top: 1rem; margin-top: .5rem; }
.btn-outline { background: transparent; border: 1px solid rgba(255,255,255,.1); color: #cbd5e1; }
.btn-outline:hover { background: rgba(255,255,255,.03); }
.help-text { font-size: .7rem; color: #64748b; margin-top: .25rem; }

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
