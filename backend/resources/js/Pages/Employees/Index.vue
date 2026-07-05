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
                <th>Perfil</th>
                <th>Status</th>
                <th style="width:80px;"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="emp in employees.data" :key="emp.id">
                <td><strong>{{ emp.nome }}</strong></td>
                <td class="text-secondary">{{ emp.email }}</td>
                <td>
                  <span class="badge badge-secondary">{{ emp.perfil_nome || '—' }}</span>
                </td>
                <td>
                  <span :class="emp.ativo ? 'badge badge-success' : 'badge badge-danger'">
                    {{ emp.ativo ? 'Ativo' : 'Inativo' }}
                  </span>
                </td>
                <td>
                  <button class="btn-icon" title="Excluir" @click="deleteEmployee(emp.id)">🗑</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Novo Funcionário -->
    <div v-if="showForm" class="modal-backdrop" @click.self="showForm = false">
      <div class="modal-box">
        <h2 class="modal-title">Novo Funcionário</h2>
        <form @submit.prevent="saveEmployee">
          <div class="form-group">
            <label class="form-label">Nome completo</label>
            <input v-model="form.nome" type="text" class="form-control" required />
          </div>
          <div class="form-group">
            <label class="form-label">E-mail</label>
            <input v-model="form.email" type="email" class="form-control" required />
          </div>
          <div class="form-group">
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
          <div class="flex gap-3 mt-4" style="justify-content:flex-end;">
            <button type="button" class="btn btn-secondary" @click="showForm = false">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar</button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  employees: { type: Object, required: true },
  perfis: { type: Array, required: true },
})

const showForm = ref(false)
const form = ref({ nome: '', email: '', senha: '', perfil_id: '' })

function saveEmployee() {
  router.post(route('admin.employees.store'), form.value, {
    onSuccess: () => { showForm.value = false; form.value = { nome: '', email: '', senha: '', perfil_id: '' } },
  })
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
