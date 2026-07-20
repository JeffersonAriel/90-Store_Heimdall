<template>
  <AdminLayout title="Funcionários">
    <template #breadcrumb>
      <span class="text-muted">Configurações</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Funcionários</span>
    </template>

    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">
          <span class="page-title-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </span>
          Funcionários
        </h1>
        <p class="page-subtitle">Gerencie a equipe e os perfis de acesso ao painel Heimdall.</p>
      </div>
      <div class="page-actions">
        <button class="btn btn-primary" @click="showForm = true">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Novo Funcionário
        </button>
      </div>
    </div>

    <!-- Tabela -->
    <div class="card">
      <div v-if="employees.data.length === 0" class="empty-state">
        <div class="empty-state-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
          </svg>
        </div>
        <p class="empty-state-title">Nenhum funcionário cadastrado</p>
        <p class="empty-state-desc">Adicione o primeiro membro da equipe.</p>
      </div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Nome</th>
              <th>E-mail</th>
              <th>Telefone</th>
              <th>CPF</th>
              <th>Perfil</th>
              <th>Status</th>
              <th style="width: 90px; text-align: right;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="emp in employees.data" :key="emp.id">
              <td data-label="Nome">
                <div class="flex items-center gap-3">
                  <div class="sidebar-user-avatar" style="width: 32px; height: 32px; font-size: 0.625rem; border-radius: var(--radius-sm);">
                    {{ initials(emp.nome) }}
                  </div>
                  <strong>{{ emp.nome }}</strong>
                </div>
              </td>
              <td data-label="E-mail" class="text-secondary">{{ emp.email }}</td>
              <td data-label="Telefone" class="text-secondary">{{ emp.telefone || '—' }}</td>
              <td data-label="CPF" class="text-secondary font-mono" style="font-size: 0.8125rem;">{{ emp.cpf || '—' }}</td>
              <td data-label="Perfil">
                <span class="badge badge-secondary">{{ emp.perfil_nome || '—' }}</span>
              </td>
              <td data-label="Status">
                <span :class="emp.ativo ? 'badge badge-success' : 'badge badge-danger'">
                  <span class="badge-dot"></span>{{ emp.ativo ? 'Ativo' : 'Inativo' }}
                </span>
              </td>
              <td style="text-align: right;">
                <div class="flex gap-1 justify-end">
                  <button class="btn-icon" title="Editar" @click="editEmployee(emp)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                  </button>
                  <button class="btn-icon" title="Excluir" @click="deleteEmployee(emp.id)" style="color: var(--color-danger-light);">
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
    </div>

    <!-- Modal Novo/Editar Funcionário -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showForm" class="modal-overlay" @click.self="closeForm">
          <div class="modal modal-sm">
            <div class="modal-header">
              <h3 class="modal-title">{{ isEdit ? 'Editar Funcionário' : 'Novo Funcionário' }}</h3>
              <button class="btn-icon" @click="closeForm" aria-label="Fechar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="saveEmployee" class="flex flex-col gap-4">
                <div class="form-group">
                  <label class="form-label form-label-required">Nome completo</label>
                  <input v-model="form.nome" type="text" class="form-input" required />
                </div>
                <div class="form-group">
                  <label class="form-label form-label-required">E-mail</label>
                  <input v-model="form.email" type="email" class="form-input" required />
                </div>
                <div class="grid-2">
                  <div class="form-group">
                    <label class="form-label">Telefone</label>
                    <input v-model="form.telefone" type="text" class="form-input" placeholder="(11) 99999-9999" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">CPF</label>
                    <input v-model="form.cpf" type="text" class="form-input" placeholder="000.000.000-00" />
                  </div>
                </div>
                <div class="form-group" v-if="!isEdit">
                  <label class="form-label form-label-required">Senha</label>
                  <input v-model="form.senha" type="password" class="form-input" minlength="8" required />
                </div>
                <div class="form-group">
                  <label class="form-label form-label-required">Perfil de Acesso</label>
                  <select v-model="form.perfil_id" class="form-select" required>
                    <option value="" disabled>Selecione...</option>
                    <option v-for="p in perfis" :key="p.id" :value="p.id">{{ p.nome }}</option>
                  </select>
                </div>
                <div v-if="isEdit" class="flex items-center gap-2">
                  <input v-model="form.ativo" type="checkbox" id="emp-ativo" style="width: 1rem; height: 1rem; accent-color: var(--color-brand);" />
                  <label for="emp-ativo" class="form-label" style="margin: 0; cursor: pointer;">Funcionário Ativo</label>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeForm">Cancelar</button>
              <button type="submit" class="btn btn-primary" @click="saveEmployee">
                {{ isEdit ? 'Salvar Alterações' : 'Criar Funcionário' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  employees: { type: Object, required: true },
  perfis:    { type: Array,  required: true },
})

const showForm   = ref(false)
const isEdit     = ref(false)
const editingId  = ref(null)

const form = ref({ nome: '', email: '', senha: '', perfil_id: '', telefone: '', cpf: '', ativo: true })

function initials(name) {
  if (!name) return '?'
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}

function editEmployee(emp) {
  isEdit.value = true
  editingId.value = emp.id
  form.value = { nome: emp.nome, email: emp.email, senha: '', perfil_id: emp.perfil_id || '', telefone: emp.telefone || '', cpf: emp.cpf || '', ativo: emp.ativo === 1 || emp.ativo === true }
  showForm.value = true
}

function closeForm() {
  showForm.value = false; isEdit.value = false; editingId.value = null
  form.value = { nome: '', email: '', senha: '', perfil_id: '', telefone: '', cpf: '', ativo: true }
}

function saveEmployee() {
  if (isEdit.value) {
    router.put(route('admin.employees.update', editingId.value), form.value, { onSuccess: () => closeForm() })
  } else {
    router.post(route('admin.employees.store'), form.value, { onSuccess: () => closeForm() })
  }
}

function deleteEmployee(id) {
  if (!confirm('Confirma a exclusão deste funcionário?')) return
  router.delete(route('admin.employees.destroy', id))
}
</script>
