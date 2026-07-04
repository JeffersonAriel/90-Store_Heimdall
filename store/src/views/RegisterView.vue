<template>
  <div class="login-container" style="min-height: auto; padding: 3rem 0;">
    <div class="login-card" style="max-width: 500px;">
      <div class="login-header">
        <h1>Criar Conta</h1>
        <p class="subtitle text-secondary">Cadastre-se na 90-Store para realizar compras</p>
      </div>

      <div v-if="error" class="alert alert-danger mb-4">
        {{ error }}
      </div>

      <form @submit.prevent="submit">
        <div class="form-group mb-4">
          <label class="form-label">Nome Completo</label>
          <input v-model="form.nome_completo" type="text" class="store-input" placeholder="Seu nome" required />
        </div>

        <div class="grid-2">
          <div class="form-group mb-4">
            <label class="form-label">CPF (Apenas números)</label>
            <input v-model="form.cpf" type="text" class="store-input" placeholder="000.000.000-00" required />
          </div>
          <div class="form-group mb-4">
            <label class="form-label">Nascimento</label>
            <input v-model="form.data_nascimento" type="date" class="store-input" required />
          </div>
        </div>

        <div class="form-group mb-4">
          <label class="form-label">E-mail</label>
          <input v-model="form.email" type="email" class="store-input" placeholder="seu.email@exemplo.com" required />
        </div>

        <div class="grid-2">
          <div class="form-group mb-4">
            <label class="form-label">Senha</label>
            <input v-model="form.password" type="password" class="store-input" placeholder="Mínimo 8 dígitos" required />
          </div>
          <div class="form-group mb-4">
            <label class="form-label">Confirmar Senha</label>
            <input v-model="form.password_confirmation" type="password" class="store-input" placeholder="Repita a senha" required />
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group mb-4">
            <label class="form-label">Telefone</label>
            <input v-model="form.telefone" type="text" class="store-input" placeholder="11999998888" />
          </div>
          <div class="form-group mb-4">
            <label class="form-label">WhatsApp</label>
            <input v-model="form.whatsapp" type="text" class="store-input" placeholder="11999998888" />
          </div>
        </div>

        <button type="submit" class="store-btn store-btn-primary w-full" :disabled="loading">
          {{ loading ? 'Registrando...' : 'Finalizar Cadastro' }}
        </button>

        <div class="text-center mt-4">
          <p class="text-secondary" style="font-size: 0.875rem;">
            Já possui uma conta? 
            <RouterLink to="/login" class="text-brand">Faça login</RouterLink>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useStore } from '@/store/main'
import axios from 'axios'

const store = useStore()
const router = useRouter()

const form = ref({
  nome_completo: '',
  cpf: '',
  data_nascimento: '',
  email: '',
  password: '',
  password_confirmation: '',
  telefone: '',
  whatsapp: ''
})

const error = ref('')
const loading = ref(false)

async function submit() {
  loading.value = true
  error.value = ''
  try {
    const res = await axios.post('/api/register', form.value)
    store.setToken(res.data.token)
    await store.fetchUser()
    router.push('/checkout')
  } catch (err) {
    error.value = err.response?.data?.message || 'Erro ao registrar sua conta.'
  } finally {
    loading.value = false
  }
}
</script>
