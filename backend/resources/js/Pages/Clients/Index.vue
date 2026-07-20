<template>
  <AdminLayout title="Clientes">
    <template #breadcrumb>
      <span class="text-muted">Operações</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Clientes</span>
    </template>

    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">
          <span class="page-title-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </span>
          Clientes
        </h1>
        <p class="page-subtitle">Lista de clientes com histórico de compras e saldo de fidelidade.</p>
      </div>
      <div class="page-actions">
        <div class="form-input-wrap" style="width: 280px;">
          <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <input
            v-model="search"
            @keyup.enter="handleSearch"
            type="text"
            placeholder="Nome, e-mail, telefone..."
            class="form-input"
          />
        </div>
        <button @click="handleSearch" class="btn btn-primary">Buscar</button>
      </div>
    </div>

    <!-- Tabela -->
    <div class="card">
      <div v-if="clients.data.length === 0" class="empty-state">
        <div class="empty-state-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <p class="empty-state-title">Nenhum cliente encontrado</p>
        <p class="empty-state-desc">Ajuste a busca ou aguarde novos cadastros.</p>
      </div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Nome</th>
              <th>E-mail</th>
              <th>Telefone / WhatsApp</th>
              <th style="text-align: center;">Pedidos</th>
              <th style="text-align: center;">Pontos</th>
              <th>Status</th>
              <th style="width: 110px; text-align: right;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="client in clients.data" :key="client.id">
              <td data-label="Nome">
                <div class="font-semibold">{{ client.nome_completo }}</div>
                <div v-if="client.nome_social" class="text-muted" style="font-size: 0.75rem;">{{ client.nome_social }}</div>
              </td>
              <td data-label="E-mail" class="text-secondary" style="font-size: 0.8125rem;">{{ client.email }}</td>
              <td data-label="Telefone" class="text-secondary font-mono" style="font-size: 0.8125rem;">
                {{ client.whatsapp || client.telefone || '—' }}
              </td>
              <td data-label="Pedidos" style="text-align: center;">
                <span class="badge badge-primary">{{ client.pedidos_count }}</span>
              </td>
              <td data-label="Pontos" style="text-align: center;">
                <span class="badge badge-success">{{ client.pontos_saldo || 0 }} pts</span>
              </td>
              <td data-label="Status">
                <button
                  @click="toggleStatus(client)"
                  class="badge cursor-pointer"
                  :class="client.ativo ? 'badge-success' : 'badge-danger'"
                  title="Clique para alternar status"
                  style="border: none; font-family: inherit;"
                >
                  <span class="badge-dot"></span>{{ client.ativo ? 'Ativo' : 'Inativo' }}
                </button>
              </td>
              <td data-label="Ações" style="text-align: right;">
                <div class="flex gap-2 justify-end">
                  <button @click="viewDetails(client)" class="btn btn-secondary btn-sm">Ficha</button>
                  <button @click="deleteClient(client.id)" class="btn-icon" title="Remover" style="color: var(--color-danger-light);">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="clients.links && clients.links.length > 3" class="pagination">
        <Link v-for="(link, idx) in clients.links" :key="idx" :href="link.url || '#'" class="page-btn" :class="{ active: link.active, disabled: !link.url }" v-html="link.label" />
      </div>
    </div>

    <!-- Modal Detalhes / Edição do Cliente -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h3 class="modal-title">
                {{ isEditing ? 'Editar Ficha do Cliente' : 'Ficha do Cliente' }}
                <span v-if="!isEditing && selectedClient" class="text-muted font-medium" style="font-weight: 400; font-size: 0.9375rem;"> — {{ selectedClient.nome_completo }}</span>
              </h3>
              <button @click="closeModal" class="btn-icon" aria-label="Fechar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>

            <div class="modal-body">
              <!-- Loading State -->
              <div v-if="loadingDetails" class="empty-state" style="padding: 2rem;">
                <div class="spinner"></div>
                <p class="text-muted" style="margin-top: 0.75rem; font-size: 0.875rem;">Carregando...</p>
              </div>

              <!-- Content -->
              <div v-else-if="selectedClient" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">

                <!-- Dados Pessoais -->
                <div>
                  <h4 style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-text-muted); margin-bottom: 1rem;">Dados Pessoais</h4>

                  <!-- Edit Form -->
                  <form v-if="isEditing" @submit.prevent="saveClient" class="flex flex-col gap-3">
                    <div class="form-group">
                      <label class="form-label form-label-required">Nome Completo</label>
                      <input type="text" v-model="editForm.nome_completo" class="form-input" required />
                    </div>
                    <div class="form-group">
                      <label class="form-label">Nome Social</label>
                      <input type="text" v-model="editForm.nome_social" class="form-input" />
                    </div>
                    <div class="grid-2">
                      <div class="form-group">
                        <label class="form-label">CPF</label>
                        <input type="text" v-model="editForm.cpf" class="form-input" />
                      </div>
                      <div class="form-group">
                        <label class="form-label">Nascimento</label>
                        <input type="date" v-model="editForm.data_nascimento" class="form-input" />
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="form-label form-label-required">E-mail</label>
                      <input type="email" v-model="editForm.email" class="form-input" required />
                    </div>
                    <div class="grid-2">
                      <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" v-model="editForm.telefone" class="form-input" />
                      </div>
                      <div class="form-group">
                        <label class="form-label">WhatsApp</label>
                        <input type="text" v-model="editForm.whatsapp" class="form-input" />
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Saldo de Pontos</label>
                      <input type="number" v-model="editForm.pontos_saldo" class="form-input" />
                    </div>
                    <div class="flex items-center gap-2">
                      <input type="checkbox" id="edit-ativo" v-model="editForm.ativo" style="width: 1rem; height: 1rem; accent-color: var(--color-brand);" />
                      <label for="edit-ativo" class="form-label" style="margin: 0; cursor: pointer;">Cliente Ativo</label>
                    </div>
                  </form>

                  <!-- View Mode -->
                  <div v-else class="divide-y">
                    <div v-for="row in clientDetails" :key="row.label" class="py-2 flex justify-between gap-4" style="font-size: 0.875rem;">
                      <span class="text-muted" style="flex-shrink: 0;">{{ row.label }}</span>
                      <span class="font-medium text-right">{{ row.value }}</span>
                    </div>
                    <div class="py-2 flex justify-between gap-4" style="font-size: 0.875rem;">
                      <span class="text-muted">Status</span>
                      <span :class="selectedClient.ativo ? 'badge badge-success' : 'badge badge-danger'">
                        {{ selectedClient.ativo ? 'Ativo' : 'Inativo' }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Endereços -->
                <div>
                  <h4 style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-text-muted); margin-bottom: 1rem;">Endereços Cadastrados</h4>
                  <div v-if="!selectedClient.enderecos?.length" class="info-box" style="font-size: 0.875rem; color: var(--color-text-muted);">
                    Nenhum endereço cadastrado.
                  </div>
                  <div v-else class="flex flex-col gap-2" style="max-height: 200px; overflow-y: auto;">
                    <div v-for="end in selectedClient.enderecos" :key="end.id" class="info-box">
                      <div class="font-semibold" style="font-size: 0.8125rem; margin-bottom: 0.25rem;">{{ end.identificacao || 'Endereço' }}</div>
                      <div class="text-secondary" style="font-size: 0.8125rem; line-height: 1.5;">
                        {{ end.logradouro }}, {{ end.numero }} - {{ end.bairro }}<br>
                        {{ end.cidade }}/{{ end.uf }} — CEP {{ end.cep }}
                      </div>
                    </div>
                  </div>

                  <!-- Últimos pedidos -->
                  <h4 style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-text-muted); margin: 1.25rem 0 1rem;">Últimos Pedidos</h4>
                  <div v-if="!selectedClient.pedidos?.length" class="info-box" style="font-size: 0.875rem; color: var(--color-text-muted);">
                    Nenhum pedido realizado ainda.
                  </div>
                  <div v-else class="table-wrapper" style="max-height: 180px; overflow-y: auto;">
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
                            <Link :href="route('admin.orders.show', ped.id)" class="text-brand" style="font-weight: 600;">#{{ ped.codigo || ped.id }}</Link>
                          </td>
                          <td class="text-secondary">{{ formatDate(ped.created_at) }}</td>
                          <td class="font-bold">{{ formatPrice(ped.total) }}</td>
                          <td><span class="badge badge-secondary">{{ ped.status }}</span></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
              <div v-if="isEditing" class="flex gap-2" style="width: 100%; justify-content: flex-end;">
                <button @click="cancelEdit" class="btn btn-secondary">Cancelar</button>
                <button @click="saveClient" class="btn btn-success" :disabled="editForm.processing">
                  {{ editForm.processing ? 'Salvando...' : 'Salvar Alterações' }}
                </button>
              </div>
              <div v-else class="flex gap-2" style="width: 100%; justify-content: space-between;">
                <button @click="startEdit" class="btn btn-primary">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                  Editar Ficha
                </button>
                <button @click="closeModal" class="btn btn-secondary">Fechar</button>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'

const props = defineProps({
  clients: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
})

const search         = ref(props.filters.search || '')
const showModal      = ref(false)
const selectedClient = ref(null)
const loadingDetails = ref(false)
const isEditing      = ref(false)

const editForm = useForm({
  id: null, nome_completo: '', nome_social: '', cpf: '',
  data_nascimento: '', email: '', telefone: '', whatsapp: '',
  pontos_saldo: 0, ativo: true,
})

const clientDetails = computed(() => {
  if (!selectedClient.value) return []
  const c = selectedClient.value
  return [
    { label: 'Nome Completo',  value: c.nome_completo },
    { label: 'Nome Social',    value: c.nome_social || '—' },
    { label: 'CPF',            value: c.cpf || 'Não informado' },
    { label: 'Nascimento',     value: formatDate(c.data_nascimento) },
    { label: 'E-mail',         value: c.email },
    { label: 'Telefone',       value: c.telefone || '—' },
    { label: 'WhatsApp',       value: c.whatsapp || '—' },
    { label: 'Saldo de Pontos', value: `${c.pontos_saldo || 0} pts` },
  ]
})

function handleSearch() {
  router.get(route('admin.clients.index'), { search: search.value }, { preserveState: true, replace: true })
}

function toggleStatus(client) {
  router.put(route('admin.clients.update', client.id), { ativo: !client.ativo }, { preserveScroll: true })
}

async function viewDetails(client) {
  selectedClient.value = null
  showModal.value = true
  loadingDetails.value = true
  isEditing.value = false
  try {
    const res = await axios.get(route('admin.clients.show', client.id))
    if (res.data.success) selectedClient.value = res.data.client
  } catch { alert('Erro ao carregar detalhes.'); showModal.value = false }
  finally { loadingDetails.value = false }
}

function startEdit() {
  if (!selectedClient.value) return
  const c = selectedClient.value
  editForm.id = c.id; editForm.nome_completo = c.nome_completo || ''; editForm.nome_social = c.nome_social || ''
  editForm.cpf = c.cpf || ''; editForm.data_nascimento = c.data_nascimento ? c.data_nascimento.substring(0, 10) : ''
  editForm.email = c.email || ''; editForm.telefone = c.telefone || ''; editForm.whatsapp = c.whatsapp || ''
  editForm.pontos_saldo = c.pontos_saldo || 0; editForm.ativo = c.ativo !== false
  isEditing.value = true
}

function cancelEdit() { isEditing.value = false }

function saveClient() {
  editForm.put(route('admin.clients.update', editForm.id), {
    preserveScroll: true,
    onSuccess: () => {
      isEditing.value = false
      if (selectedClient.value) {
        Object.assign(selectedClient.value, {
          nome_completo: editForm.nome_completo, nome_social: editForm.nome_social,
          cpf: editForm.cpf, data_nascimento: editForm.data_nascimento,
          email: editForm.email, telefone: editForm.telefone,
          whatsapp: editForm.whatsapp, pontos_saldo: editForm.pontos_saldo, ativo: editForm.ativo
        })
      }
    }
  })
}

function closeModal() { showModal.value = false; isEditing.value = false }

function deleteClient(id) {
  if (confirm('Tem certeza que deseja remover este cliente? (Soft Delete)')) router.delete(route('admin.clients.destroy', id))
}

function formatDate(d) { return d ? new Date(d).toLocaleDateString('pt-BR') : '—' }
function formatPrice(v) { return v ? new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v) : '—' }
</script>

<style scoped>
.page-btn.disabled { pointer-events: none; opacity: 0.35; }
</style>
