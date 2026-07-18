<template>
  <AdminLayout title="CRM — Leads">
    <div class="header-container">
      <div>
        <h1 class="page-title">🎯 Gestão de Leads</h1>
        <p class="page-sub">Controle de contatos comerciais em fase de prospecção</p>
      </div>
      <button @click="openModal = true" class="crm-btn btn-primary">+ Novo Lead</button>
    </div>

    <!-- Modal Novo Lead -->
    <div v-if="openModal" class="crm-modal-overlay" @click.self="openModal = false">
      <div class="crm-modal-body">
        <div class="modal-header">
          <h2>🎯 Novo Lead de Venda</h2>
          <button @click="openModal = false" class="close-btn">&times;</button>
        </div>
        <form @submit.prevent="submitLead" class="modal-form">
          <div class="form-group">
            <label>Nome do Lead *</label>
            <input v-model="form.nome" type="text" class="crm-input" required placeholder="Ex: João da Silva" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email</label>
              <input v-model="form.email" type="email" class="crm-input" placeholder="Ex: joao@email.com" />
            </div>
            <div class="form-group">
              <label>Telefone / WhatsApp</label>
              <input v-model="form.whatsapp" type="text" class="crm-input" placeholder="Ex: 11999999999" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Origem *</label>
              <select v-model="form.origem" class="crm-select" required>
                <option value="site">Site Institucional</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="instagram">Instagram</option>
                <option value="facebook">Facebook</option>
                <option value="google_ads">Google Ads</option>
                <option value="indicacao">Indicação</option>
                <option value="outro">Outro</option>
              </select>
            </div>
            <div class="form-group">
              <label>Temperatura *</label>
              <select v-model="form.temperatura" class="crm-select" required>
                <option value="frio">❄️ Frio</option>
                <option value="morno">☀️ Morno</option>
                <option value="quente">🔥 Quente</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Valor Esperado (R$)</label>
              <input v-model="form.valor_esperado" type="number" step="0.01" class="crm-input" placeholder="Ex: 500.00" />
            </div>
            <div class="form-group">
              <label>Probabilidade (%)</label>
              <input v-model="form.probabilidade" type="number" class="crm-input" placeholder="Ex: 50" />
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="openModal = false" class="crm-btn btn-outline">Cancelar</button>
            <button type="submit" class="crm-btn btn-primary" :disabled="form.processing">Cadastrar Lead</button>
          </div>
        </form>
      </div>
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
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ leads: Object, etapas: Array, funcionarios: Array })
const openModal = ref(false)

const form = useForm({
  nome: '',
  email: '',
  whatsapp: '',
  origem: 'whatsapp',
  temperatura: 'morno',
  valor_esperado: null,
  probabilidade: 50,
})

function submitLead() {
  form.post(route('admin.crm.leads.store'), {
    onSuccess: () => {
      openModal.value = false
      form.reset()
    }
  })
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

/* ── Modal & Form Styles ── */
.crm-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(4px); }
.crm-modal-body { background: linear-gradient(135deg, #111827, #1e1b4b); border: 1px solid rgba(99,102,241,0.25); border-radius: 16px; width: 90%; max-width: 550px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); display: flex; flex-direction: column; overflow: hidden; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.05); }
.modal-header h2 { font-size: 1.15rem; color: #fff; margin: 0; }
.close-btn { background: transparent; border: none; color: #64748b; font-size: 1.5rem; cursor: pointer; }
.modal-form { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: .4rem; }
.form-group label { font-size: .78rem; color: #94a3b8; font-weight: 600; }
.crm-input, .crm-select { width: 100%; padding: .6rem 1rem; background: rgba(255,255,255,.02); border: 1px solid rgba(255,255,255,.08); border-radius: 8px; color: #cbd5e1; font-size: .88rem; }
.crm-input:focus, .crm-select:focus { border-color: rgba(99,102,241,0.4); outline: none; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: .75rem; border-top: 1px solid rgba(255,255,255,.05); padding-top: 1rem; margin-top: .5rem; }
</style>
