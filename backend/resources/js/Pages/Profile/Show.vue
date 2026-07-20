<template>
  <AdminLayout title="Meu Perfil">
    <template #breadcrumb>
      <span class="text-muted">Configurações</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Perfil</span>
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
          Meu Perfil
        </h1>
        <p class="page-subtitle">Gerencie suas informações de cadastro e senha de acesso ao Heimdall.</p>
      </div>
    </div>

    <div class="grid-3" style="gap: 1.5rem; align-items: start;">
      <!-- Formulário -->
      <div class="card" style="grid-column: span 2;">
        <div class="card-header">
          <h3 class="card-title">
            <span class="card-title-icon" style="background: var(--color-brand-surface); color: var(--color-brand);">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
            </span>
            Editar Informações
          </h3>
        </div>
        <form @submit.prevent="submit">
          <div class="card-body flex flex-col gap-4">
            <div class="grid-2">
              <div class="form-group">
                <label class="form-label form-label-required">Nome Completo</label>
                <input v-model="form.nome" type="text" class="form-input" required placeholder="Seu nome completo" />
                <span v-if="form.errors.nome" class="form-error">{{ form.errors.nome }}</span>
              </div>
              <div class="form-group">
                <label class="form-label form-label-required">E-mail Corporativo</label>
                <input v-model="form.email" type="email" class="form-input" required placeholder="exemplo@empresa.com.br" />
                <span v-if="form.errors.email" class="form-error">{{ form.errors.email }}</span>
              </div>
            </div>
            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">Telefone de Contato</label>
                <input v-model="form.telefone" type="text" class="form-input" placeholder="(11) 99999-9999" />
              </div>
              <div class="form-group">
                <label class="form-label">CPF</label>
                <input v-model="form.cpf" type="text" class="form-input" placeholder="000.000.000-00" />
              </div>
            </div>

            <!-- Divider -->
            <div style="border-top: 1px solid var(--color-border); padding-top: 1rem;">
              <div class="info-box mb-4">
                <div class="flex items-center gap-2" style="font-size: 0.8125rem;">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                  </svg>
                  Deixe os campos de senha em branco caso não queira alterar sua senha atual.
                </div>
              </div>
              <div class="grid-2">
                <div class="form-group">
                  <label class="form-label">Nova Senha</label>
                  <input v-model="form.password" type="password" class="form-input" placeholder="Mínimo 8 caracteres" />
                  <span v-if="form.errors.password" class="form-error">{{ form.errors.password }}</span>
                </div>
                <div class="form-group">
                  <label class="form-label">Confirmar Nova Senha</label>
                  <input v-model="form.password_confirmation" type="password" class="form-input" placeholder="Digite novamente" />
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer" style="border-top: 1px solid var(--color-border);">
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              <svg v-if="!form.processing" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
              </svg>
              {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Sidebar: Avatar + Logout -->
      <div class="flex flex-col gap-4">
        <!-- Avatar Card -->
        <div class="card">
          <div class="card-body" style="text-align: center; display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 2rem 1.5rem;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--color-brand), var(--color-brand-light, #818cf8)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.875rem; font-weight: 800; box-shadow: 0 4px 20px var(--color-brand-glow, rgba(99,102,241,0.3));">
              {{ initials(employee.nome) }}
            </div>
            <div>
              <h3 class="font-bold" style="font-size: 1rem;">{{ employee.nome }}</h3>
              <p class="text-secondary" style="font-size: 0.8125rem; margin-top: 0.25rem;">{{ employee.email }}</p>
            </div>
            <span class="badge badge-primary">{{ employee.perfil?.nome || 'Funcionário' }}</span>
          </div>
        </div>

        <!-- Logout Card -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title" style="color: var(--color-danger);">
              <span class="card-title-icon" style="background: var(--color-danger-bg); color: var(--color-danger);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                  <polyline points="16 17 21 12 16 7"/>
                  <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
              </span>
              Encerrar Sessão
            </h3>
          </div>
          <div class="card-body">
            <p class="text-secondary" style="font-size: 0.875rem; margin-bottom: 1rem;">
              Deseja sair do painel administrativo Heimdall?
            </p>
            <button @click="logout" class="btn btn-danger" style="width: 100%;">
              Sair da Conta
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

const props = defineProps({ employee: { type: Object, required: true } })

const form = useForm({
  nome: props.employee.nome, email: props.employee.email,
  telefone: props.employee.telefone || '', cpf: props.employee.cpf || '',
  password: '', password_confirmation: ''
})

function submit() {
  form.put(route('admin.profile.update'), { preserveScroll: true, onSuccess: () => form.reset('password', 'password_confirmation') })
}

function logout() {
  if (confirm('Tem certeza que deseja sair?')) router.post(route('admin.logout'))
}

function initials(name) {
  if (!name) return '?'
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}
</script>
