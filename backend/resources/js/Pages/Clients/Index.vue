<template>
  <AdminLayout title="Clientes">
    <template #breadcrumb>
      <span>Clientes</span>
    </template>

    <div class="page-header mb-6 flex justify-between items-center flex-wrap gap-4">
      <div>
        <h1 class="page-title">👤 Clientes Cadastrados</h1>
        <p class="text-secondary mt-1">Veja a lista de clientes, status cadastral, saldo de fidelidade e histórico de compras.</p>
      </div>
      
      <!-- Barra de Busca -->
      <div class="flex items-center gap-2">
        <input
          v-model="search"
          @keyup.enter="handleSearch"
          type="text"
          placeholder="Buscar por nome, e-mail, telefone..."
          class="form-control"
          style="width: 300px; max-width: 100%;"
        />
        <button @click="handleSearch" class="btn btn-primary">🔍 Buscar</button>
      </div>
    </div>

    <!-- Tabela de Clientes -->
    <div class="card">
      <div class="card-body p-0">
        <div v-if="clients.data.length === 0" class="p-6 text-center text-secondary">
          Nenhum cliente encontrado.
        </div>
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Nome Completo</th>
                <th>E-mail</th>
                <th>Telefone / WhatsApp</th>
                <th class="text-center">Pedidos</th>
                <th class="text-center">Pontos Saldo</th>
                <th class="text-center">Status</th>
                <th style="width: 120px; text-align: center;">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="client in clients.data" :key="client.id">
                <td>
                  <strong>{{ client.nome_completo }}</strong>
                  <span v-if="client.nome_social" class="block text-xs text-muted">Social: {{ client.nome_social }}</span>
                </td>
                <td class="text-secondary">{{ client.email }}</td>
                <td class="text-secondary font-mono">
                  {{ client.whatsapp || client.telefone || '—' }}
                </td>
                <td class="text-center font-bold" style="color: var(--color-brand);">
                  {{ client.pedidos_count }}
                </td>
                <td class="text-center text-success font-semibold">
                  🪙 {{ client.pontos_saldo || 0 }} pts
                </td>
                <td class="text-center">
                  <button 
                    @click="toggleStatus(client)" 
                    class="badge cursor-pointer transition-all hover:scale-105"
                    :class="client.ativo ? 'badge-success' : 'badge-danger'"
                    title="Clique para alternar status"
                  >
                    {{ client.ativo ? 'Ativo' : 'Inativo' }}
                  </button>
                </td>
                <td style="text-align: center;">
                  <div style="display: flex; gap: 0.5rem; justify-content: center;">
                    <button @click="viewDetails(client)" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                      🔍 Ficha
                    </button>
                    <button @click="deleteClient(client.id)" class="btn-icon" title="Remover" style="cursor: pointer; background: transparent; border: none; font-size: 1rem; padding: 4px;">
                      🗑
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Detalhes do Cliente -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-box" style="max-width: 750px; width: 95%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--color-border); padding-bottom: 1rem;">
          <h2 class="modal-title" style="margin: 0;">
            {{ isEditing ? '✏️ Editar Ficha do Cliente' : '📋 Ficha do Cliente: ' + (selectedClient?.nome_completo || '') }}
          </h2>
          <button @click="closeModal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-text-muted);">&times;</button>
        </div>

        <div v-if="loadingDetails" class="p-6 text-center text-brand">
          Carregando informações...
        </div>
        <div v-else-if="selectedClient" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          
          <!-- Dados Básicos / Formulário -->
          <div>
            <h3 style="font-size: 1rem; color: var(--color-brand); margin-bottom: 0.75rem;">Dados Pessoais</h3>
            
            <form v-if="isEditing" @submit.prevent="saveClient" style="display: flex; flex-direction: column; gap: 0.75rem;">
              <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; display: block;">Nome Completo *</label>
                <input type="text" v-model="editForm.nome_completo" class="form-control form-control-sm" style="width: 100%;" required />
              </div>
              <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; display: block;">Nome Social</label>
                <input type="text" v-model="editForm.nome_social" class="form-control form-control-sm" style="width: 100%;" />
              </div>
              <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; display: block;">CPF</label>
                <input type="text" v-model="editForm.cpf" class="form-control form-control-sm" style="width: 100%;" />
              </div>
              <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; display: block;">Data de Nascimento</label>
                <input type="date" v-model="editForm.data_nascimento" class="form-control form-control-sm" style="width: 100%;" />
              </div>
              <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; display: block;">E-mail *</label>
                <input type="email" v-model="editForm.email" class="form-control form-control-sm" style="width: 100%;" required />
              </div>
              <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; display: block;">Telefone</label>
                <input type="text" v-model="editForm.telefone" class="form-control form-control-sm" style="width: 100%;" />
              </div>
              <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; display: block;">WhatsApp</label>
                <input type="text" v-model="editForm.whatsapp" class="form-control form-control-sm" style="width: 100%;" />
              </div>
              <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem; margin-bottom: 0.25rem; display: block;">Saldo de Pontos</label>
                <input type="number" v-model="editForm.pontos_saldo" class="form-control form-control-sm" style="width: 100%;" />
              </div>
              <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                <input type="checkbox" id="edit-ativo" v-model="editForm.ativo" style="width: auto;" />
                <label for="edit-ativo" style="font-size: 0.8125rem; font-weight: bold; cursor: pointer; margin: 0;">Cliente Ativo</label>
              </div>
            </form>

            <ul v-else style="list-style: none; padding: 0; line-height: 1.8; font-size: 0.875rem;">
              <li><strong>Nome Completo:</strong> {{ selectedClient.nome_completo }}</li>
              <li v-if="selectedClient.nome_social"><strong>Nome Social:</strong> {{ selectedClient.nome_social }}</li>
              <li><strong>CPF:</strong> <span class="font-mono">{{ selectedClient.cpf || 'Não informado' }}</span></li>
              <li><strong>Data Nascimento:</strong> {{ formatDate(selectedClient.data_nascimento) }}</li>
              <li><strong>E-mail:</strong> {{ selectedClient.email }}</li>
              <li><strong>Telefone:</strong> {{ selectedClient.telefone || '—' }}</li>
              <li><strong>WhatsApp:</strong> {{ selectedClient.whatsapp || '—' }}</li>
              <li><strong>Saldo de Pontos:</strong> {{ selectedClient.pontos_saldo || 0 }}</li>
              <li>
                <strong>Status:</strong>
                <span class="badge ml-1" :class="selectedClient.ativo ? 'badge-success' : 'badge-danger'">
                  {{ selectedClient.ativo ? 'Ativo' : 'Inativo' }}
                </span>
              </li>
            </ul>
          </div>

          <!-- Endereço de Entrega -->
          <div>
            <h3 style="font-size: 1rem; color: var(--color-brand); margin-bottom: 0.75rem;">Endereços Cadastrados</h3>
            <div v-if="!selectedClient.enderecos || selectedClient.enderecos.length === 0" class="text-secondary text-sm">
              Nenhum endereço cadastrado.
            </div>
            <div v-else style="max-height: 250px; overflow-y: auto;">
              <div 
                v-for="end in selectedClient.enderecos" 
                :key="end.id" 
                class="p-2 mb-2 rounded" 
                style="background: var(--color-bg-elevated); border: 1px solid var(--color-border); font-size: 0.8125rem;"
              >
                <strong>{{ end.identificacao || 'Endereço' }}:</strong><br>
                {{ end.logradouro }}, {{ end.numero }} - {{ end.bairro }}<br>
                {{ end.cidade }}/{{ end.uf }} - CEP {{ end.cep }}
              </div>
            </div>
          </div>

          <!-- Histórico de Pedidos Recentes -->
          <div class="md:col-span-2">
            <h3 style="font-size: 1rem; color: var(--color-brand); margin-bottom: 0.75rem;">Últimos Pedidos (Limite 10)</h3>
            <div v-if="!selectedClient.pedidos || selectedClient.pedidos.length === 0" class="text-secondary text-sm p-4 text-center">
              Nenhum pedido realizado ainda.
            </div>
            <div v-else class="table-wrapper" style="max-height: 200px; overflow-y: auto;">
              <table style="font-size: 0.8125rem;">
                <thead>
                  <tr>
                    <th>Código</th>
                    <th>Data</th>
                    <th>Total</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="ped in selectedClient.pedidos" :key="ped.id">
                    <td>
                      <Link :href="route('admin.orders.show', ped.id)" class="text-brand hover:underline">
                        #{{ ped.codigo || ped.id }}
                      </Link>
                    </td>
                    <td>{{ formatDate(ped.created_at) }}</td>
                    <td class="font-bold">{{ formatPrice(ped.total) }}</td>
                    <td>
                      <span class="badge">{{ ped.status }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>

        <div style="display: flex; justify-content: space-between; margin-top: 1.5rem; border-top: 1px solid var(--color-border); padding-top: 1rem;">
          <div v-if="isEditing" style="display: flex; gap: 0.5rem; justify-content: flex-end; width: 100%;">
            <button @click="cancelEdit" class="btn btn-secondary">Cancelar</button>
            <button @click="saveClient" class="btn btn-success" :disabled="editForm.processing">
              {{ editForm.processing ? 'Salvando...' : 'Salvar Alterações' }}
            </button>
          </div>
          <div v-else style="display: flex; justify-content: space-between; width: 100%;">
            <button @click="startEdit" class="btn btn-primary">✏️ Editar Ficha</button>
            <button @click="closeModal" class="btn btn-secondary">Fechar</button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'

const props = defineProps({
  clients: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search || '')
const showModal = ref(false)
const selectedClient = ref(null)
const loadingDetails = ref(false)
const isEditing = ref(false)

const editForm = useForm({
  id: null,
  nome_completo: '',
  nome_social: '',
  cpf: '',
  data_nascimento: '',
  email: '',
  telefone: '',
  whatsapp: '',
  pontos_saldo: 0,
  ativo: true,
})

function handleSearch() {
  router.get(route('admin.clients.index'), { search: search.value }, { preserveState: true, replace: true })
}

function toggleStatus(client) {
  router.put(route('admin.clients.update', client.id), {
    ativo: !client.ativo
  }, {
    preserveScroll: true
  })
}

async function viewDetails(client) {
  selectedClient.value = null
  showModal.value = true
  loadingDetails.value = true
  isEditing.value = false
  try {
    const res = await axios.get(route('admin.clients.show', client.id))
    if (res.data.success) {
      selectedClient.value = res.data.client
    }
  } catch (e) {
    alert('Erro ao carregar detalhes do cliente.')
    showModal.value = false
  } finally {
    loadingDetails.value = false
  }
}

function startEdit() {
  if (!selectedClient.value) return
  editForm.id = selectedClient.value.id
  editForm.nome_completo = selectedClient.value.nome_completo || ''
  editForm.nome_social = selectedClient.value.nome_social || ''
  editForm.cpf = selectedClient.value.cpf || ''
  editForm.data_nascimento = selectedClient.value.data_nascimento ? selectedClient.value.data_nascimento.substring(0, 10) : ''
  editForm.email = selectedClient.value.email || ''
  editForm.telefone = selectedClient.value.telefone || ''
  editForm.whatsapp = selectedClient.value.whatsapp || ''
  editForm.pontos_saldo = selectedClient.value.pontos_saldo || 0
  editForm.ativo = selectedClient.value.ativo !== false
  isEditing.value = true
}

function cancelEdit() {
  isEditing.value = false
}

function saveClient() {
  editForm.put(route('admin.clients.update', editForm.id), {
    preserveScroll: true,
    onSuccess: () => {
      isEditing.value = false
      // Atualiza os dados locais de selectedClient a partir dos dados do formulário
      if (selectedClient.value) {
        selectedClient.value.nome_completo = editForm.nome_completo
        selectedClient.value.nome_social = editForm.nome_social
        selectedClient.value.cpf = editForm.cpf
        selectedClient.value.data_nascimento = editForm.data_nascimento
        selectedClient.value.email = editForm.email
        selectedClient.value.telefone = editForm.telefone
        selectedClient.value.whatsapp = editForm.whatsapp
        selectedClient.value.pontos_saldo = editForm.pontos_saldo
        selectedClient.value.ativo = editForm.ativo
      }
    }
  })
}

function closeModal() {
  showModal.value = false
  isEditing.value = false
}

function deleteClient(id) {
  if (confirm('Tem certeza que deseja remover este cliente? (Ação Soft Delete)')) {
    router.delete(route('admin.clients.destroy', id))
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('pt-BR')
}

function formatPrice(value) {
  if (!value) return '-'
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
}

.modal-box {
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: 1.5rem;
  box-shadow: 0 24px 64px rgba(0,0,0,0.4);
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-text-primary);
}
</style>
