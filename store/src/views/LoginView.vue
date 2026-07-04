<template>
  <div class="login-container" style="min-height: auto; padding: 3rem 0;">
    <div class="login-card">
      <div class="login-header">
        <h1>Acessar Loja</h1>
        <p class="subtitle text-secondary">Identifique-se para finalizar suas compras esportivas</p>
      </div>

      <div v-if="error" class="alert alert-danger mb-4">
        {{ error }}
      </div>

      <form @submit.prevent="submit">
        <div class="form-group mb-4">
          <label class="form-label">E-mail</label>
          <input v-model="form.email" type="email" class="store-input" placeholder="seu.email@exemplo.com" required />
        </div>

        <div class="form-group mb-4">
          <label class="form-label">Senha</label>
          <input v-model="form.password" type="password" class="store-input" placeholder="Sua senha" required />
        </div>

        <button type="submit" class="store-btn store-btn-primary w-full" :disabled="loading">
          {{ loading ? 'Entrando...' : 'Entrar' }}
        </button>

        <div class="text-center mt-4">
          <p class="text-secondary" style="font-size: 0.875rem;">
            Não tem uma conta? 
            <RouterLink to="/cadastro" class="text-brand">Cadastre-se aqui</RouterLink>
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

const form = ref({ email: '', password: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  loading.value = true
  error.value = ''
  try {
    const res = await axios.post('/api/login', form.value)
    store.setToken(res.data.token)
    await store.fetchUser()
    router.push('/checkout')
  } catch (err) {
    error.value = err.response?.data?.message || 'E-mail ou senha incorretos.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.text-brand {
  color: var(--brand-light);
  text-decoration: none;
  font-weight: 600;
}
.text-brand:hover {
  text-shadow: 0 0 8px var(--brand-glow);
}
</style>
