<template>
  <AdminLayout title="Meus Dados & Perfil">
    <template #breadcrumb>
      <span>Configurações / Perfil</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">👤 Meu Perfil</h1>
        <p class="text-secondary mt-1">Gerencie suas informações de cadastro, telefone, CPF e senha de acesso ao Heimdall.</p>
      </div>
    </div>

    <div class="grid-3 gap-6">
      <!-- Edit profile form -->
      <div class="col-span-2">
        <form @submit.prevent="submit" class="card">
          <div class="card-header border-b pb-4">
            <h3 class="card-title">📝 Editar Informações</h3>
          </div>
          
          <div class="card-body flex flex-col gap-6 pt-6">
            <!-- Grid for Nome and Email -->
            <div class="grid-2 gap-4">
              <!-- Name -->
              <div class="form-group">
                <label class="form-label">Nome Completo</label>
                <input v-model="form.nome" type="text" class="form-input" required placeholder="Digite seu nome completo" />
                <span v-if="form.errors.nome" class="text-red text-xs mt-1 block">{{ form.errors.nome }}</span>
              </div>

              <!-- Email -->
              <div class="form-group">
                <label class="form-label">E-mail Corporativo</label>
                <input v-model="form.email" type="email" class="form-input" required placeholder="exemplo@90store.com.br" />
                <span v-if="form.errors.email" class="text-red text-xs mt-1 block">{{ form.errors.email }}</span>
              </div>
            </div>

            <!-- Grid for Telefone and CPF -->
            <div class="grid-2 gap-4">
              <!-- Telefone -->
              <div class="form-group">
                <label class="form-label">Telefone de Contato</label>
                <input v-model="form.telefone" type="text" class="form-input" placeholder="(11) 99999-9999" />
                <span v-if="form.errors.telefone" class="text-red text-xs mt-1 block">{{ form.errors.telefone }}</span>
              </div>

              <!-- CPF -->
              <div class="form-group">
                <label class="form-label">CPF</label>
                <input v-model="form.cpf" type="text" class="form-input" placeholder="000.000.000-00" />
                <span v-if="form.errors.cpf" class="text-red text-xs mt-1 block">{{ form.errors.cpf }}</span>
              </div>
            </div>

            <!-- Password Info Alert -->
            <div class="alert alert-info">
              💡 Deixe os campos de senha em branco caso não queira alterar sua senha atual.
            </div>

            <!-- Grid for passwords -->
            <div class="grid-2 gap-4">
              <!-- New Password -->
              <div class="form-group">
                <label class="form-label">Nova Senha</label>
                <input v-model="form.password" type="password" class="form-input" placeholder="Mínimo 8 caracteres" />
                <span v-if="form.errors.password" class="text-red text-xs mt-1 block">{{ form.errors.password }}</span>
              </div>

              <!-- Confirm Password -->
              <div class="form-group">
                <label class="form-label">Confirmar Nova Senha</label>
                <input v-model="form.password_confirmation" type="password" class="form-input" placeholder="Digite novamente a senha" />
              </div>
            </div>
          </div>

          <div class="card-footer border-t mt-6 pt-4 flex justify-between items-center" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem;">
            <!-- Save Button -->
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
            </button>
          </div>
        </form>
      </div>

      <!-- User card & Logoff -->
      <div class="col-span-1 flex flex-col gap-6">
        <div class="card">
          <div class="card-body text-center flex flex-col items-center gap-4 py-8" style="text-align: center; display: flex; flex-direction: column; align-items: center;">
            <div class="avatar-large" style="width: 80px; height: 80px; border-radius: 50%; background: var(--color-brand); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800;">
              {{ initials(employee.nome) }}
            </div>
            <div>
              <h2 class="font-bold text-lg mt-2">{{ employee.nome }}</h2>
              <p class="text-secondary text-sm">{{ employee.email }}</p>
            </div>
            <span class="badge badge-primary mt-1">{{ employee.perfil?.nome || 'Funcionário' }}</span>
          </div>
        </div>

        <!-- Logoff Card -->
        <div class="card">
          <div class="card-header border-b pb-4">
            <h3 class="card-title">🔌 Encerrar Sessão</h3>
          </div>
          <div class="card-body pt-4">
            <p class="text-secondary text-sm mb-4">Deseja sair do painel administrativo Heimdall e deslogar desta conta?</p>
            <button @click="logout" class="btn btn-outline text-red w-full" style="width: 100%; border-color: rgba(239, 68, 68, 0.4);">
              🚪 Sair da Conta (Logoff)
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  employee: { type: Object, required: true }
})

const form = useForm({
  nome: props.employee.nome,
  email: props.employee.email,
  telefone: props.employee.telefone || '',
  cpf: props.employee.cpf || '',
  password: '',
  password_confirmation: ''
})

function submit() {
  form.put(route('admin.profile.update'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('password', 'password_confirmation')
    }
  })
}

function logout() {
  if (confirm('Tem certeza que deseja sair da conta?')) {
    router.post(route('admin.logout'))
  }
}

function initials(name) {
  if (!name) return ''
  const parts = name.split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
}
</script>

<style scoped>
.avatar-large {
  box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}
</style>
