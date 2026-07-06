<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <img src="/logo-heimdall.png" class="logo-badge" style="object-fit: contain; background: none; box-shadow: none;" alt="Heimdall Logo" />
        <h1>Heimdall Back-Office</h1>
        <p class="subtitle text-secondary">Identifique-se para acessar a gestão do e-commerce</p>
      </div>

      <div v-if="errorMsg" class="alert alert-danger mb-4">
        {{ errorMsg }}
      </div>

      <form @submit.prevent="submit">
        <div class="form-group mb-4">
          <label class="form-label" for="email">E-mail Corporativo</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            class="form-input"
            :class="{ error: errors.email }"
            placeholder="seu.nome@90store.com.br"
            required
            autocomplete="username"
          />
          <span v-if="errors.email" class="form-error mt-1">{{ errors.email }}</span>
        </div>

        <div class="form-group mb-4">
          <label class="form-label" for="password">Senha de Acesso</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            class="form-input"
            :class="{ error: errors.password }"
            placeholder="Sua senha"
            required
            autocomplete="current-password"
          />
          <span v-if="errors.password" class="form-error mt-1">{{ errors.password }}</span>
        </div>

        <div class="form-group-checkbox mb-4 flex items-center gap-2">
          <input
            id="remember"
            v-model="form.remember"
            type="checkbox"
            class="form-checkbox"
          />
          <label for="remember" class="form-label" style="margin-bottom: 0; cursor: pointer;">Lembrar de mim</label>
        </div>

        <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
          {{ form.processing ? 'Verificando...' : 'Acessar Heimdall' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const page = usePage()
const errorMsg = computed(() => page.props.flash?.error || '')

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const errors = computed(() => form.errors)

function submit() {
  form.post(route('admin.login.post'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<style>
.login-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background-color: var(--color-bg-primary, #0d0d1a);
  padding: 1.5rem;
}

.login-card {
  background: var(--color-bg-card, #1a1a35);
  border: 1px solid var(--color-border, rgba(255, 255, 255, 0.08));
  border-radius: var(--radius-xl, 16px);
  width: 100%;
  max-width: 440px;
  padding: 2.5rem;
  box-shadow: var(--shadow-lg);
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.logo-badge {
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, var(--color-brand, #6366f1), var(--color-brand-dark, #4f46e5));
  border-radius: var(--radius-md, 8px);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-family: 'Outfit', sans-serif;
  font-weight: 800;
  font-size: 1.25rem;
  margin-bottom: 1rem;
  box-shadow: var(--shadow-glow);
}

.form-checkbox {
  width: 16px;
  height: 16px;
  background-color: var(--color-bg-input, #16162e);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm, 4px);
  cursor: pointer;
  accent-color: var(--color-brand, #6366f1);
}
</style>
