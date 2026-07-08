<template>
  <AdminLayout title="Funcionários">
    <template #breadcrumb>
      <span>Funcionários</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">👥 Funcionários</h1>
        <p class="text-secondary mt-1">Gerencie a equipe e os perfis de acesso ao painel Heimdall.</p>
      </div>
      <button class="btn btn-primary" @click="showForm = true">
        <span>+ Novo Funcionário</span>
      </button>
    </div>

    <!-- Tabela -->
    <div class="card">
      <div class="card-body" style="padding:0;">
        <div v-if="employees.data.length === 0" class="alert alert-success" style="margin:1.5rem;">
          Nenhum funcionário cadastrado.
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
                <th style="width:80px;"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="emp in employees.data" :key="emp.id">
                <td><strong>{{ emp.nome }}</strong></td>
                <td class="text-secondary">{{ emp.email }}</td>
                <td class="text-secondary">{{ emp.telefone || '—' }}</td>
                <td class="text-secondary font-mono">{{ emp.cpf || '—' }}</td>
                <td>
                  <span class="badge badge-secondary">{{ emp.perfil_nome || '—' }}</span>
                </td>
                <td>
                  <span :class="emp.ativo ? 'badge badge-success' : 'badge badge-danger'">
                    {{ emp.ativo ? 'Ativo' : 'Inativo' }}
                  </span>
                </td>
                <td style="text-align: right; white-space: nowrap; width: 100px;">
                  <div style="display: inline-flex; gap: 0.5rem; justify-content: flex-end; width: 100%;">
                    <button class="btn-icon" title="Editar" @click="editEmployee(emp)" style="cursor: pointer; background: transparent; border: none; padding: 4px; font-size: 1.1rem;">✏️</button>
                    <button class="btn-icon" title="Excluir" @click="deleteEmployee(emp.id)" style="cursor: pointer; background: transparent; border: none; padding: 4px; font-size: 1.1rem;">🗑</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Novo/Editar Funcionário -->
    <div v-if="showForm" class="modal-backdrop" @click.self="closeForm">
      <div class="modal-box">
        <h2 class="modal-title">{{ isEdit ? 'Editar Funcionário' : 'Novo Funcionário' }}</h2>
        <form @submit.prevent="saveEmployee">
          <div class="form-group">
            <label class="form-label">Nome completo</label>
            <input v-model="form.nome" type="text" class="form-control" required />
          </div>
          <div class="form-group">
            <label class="form-label">E-mail</label>
            <input v-model="form.email" type="email" class="form-control" required />
          </div>
          <div class="grid-2 gap-4">
            <div class="form-group">
              <label class="form-label">Telefone</label>
              <input v-model="form.telefone" type="text" class="form-control" placeholder="(11) 99999-9999" />
            </div>
            <div class="form-group">
              <label class="form-label">CPF</label>
              <input v-model="form.cpf" type="text" class="form-control" placeholder="000.000.000-00" />
            </div>
          </div>
          <div class="form-group" v-if="!isEdit">
            <label class="form-label">Senha</label>
            <input v-model="form.senha" type="password" class="form-control" minlength="8" required />
          </div>
          <div class="form-group">
            <label class="form-label">Perfil de Acesso</label>
            <select v-model="form.perfil_id" class="form-control" required>
              <option value="" disabled>Selecione...</option>
              <option v-for="p in perfis" :key="p.id" :value="p.id">{{ p.nome }}</option>
            </select>
          </div>
          <div class="form-group" v-if="isEdit">
            <label class="form-label flex items-center gap-2 cursor-pointer">
              <input v-model="form.ativo" type="checkbox" style="width: 1.2rem; height: 1.2rem; accent-color: var(--color-brand);" />
              <span>Funcionário Ativo</span>
            </label>
          </div>
          <div class="flex gap-3 mt-6" style="justify-content:flex-end;">
            <button type="button" class="btn btn-secondary" @click="closeForm">Cancelar</button>
            <button type="submit" class="btn btn-primary">{{ isEdit ? 'Salvar Alterações' : 'Criar' }}</button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  employees: { type: Object, required: true },
  perfis: { type: Array, required: true },
})

const showForm = ref(false)
const isEdit = ref(false)
const editingId = ref(null)

const form = ref({
  nome: '',
  email: '',
  senha: '',
  perfil_id: '',
  telefone: '',
  cpf: '',
  ativo: true
})

function editEmployee(emp) {
  isEdit.value = true
  editingId.value = emp.id
  form.value = {
    nome: emp.nome,
    email: emp.email,
    senha: '',
    perfil_id: emp.perfil_id || '',
    telefone: emp.telefone || '',
    cpf: emp.cpf || '',
    ativo: emp.ativo === 1 || emp.ativo === true
  }
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  isEdit.value = false
  editingId.value = null
  form.value = { nome: '', email: '', senha: '', perfil_id: '', telefone: '', cpf: '', ativo: true }
}

function saveEmployee() {
  if (isEdit.value) {
    router.put(route('admin.employees.update', editingId.value), form.value, {
      onSuccess: () => closeForm()
    })
  } else {
    router.post(route('admin.employees.store'), form.value, {
      onSuccess: () => closeForm()
    })
  }
}

function deleteEmployee(id) {
  if (!confirm('Confirma a exclusão deste funcionário?')) return
  router.delete(route('admin.employees.destroy', id))
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
  padding: 2rem;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 24px 64px rgba(0,0,0,0.4);
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin-bottom: 1.5rem;
}
</style>
