<template>
  <div class="login-container">
    <div class="login-card text-center">
      <div class="login-header">
        <div class="logo-badge">🛡️</div>
        <h1>Dupla Autenticação</h1>
        <p class="subtitle text-secondary">Insira o código gerado no seu aplicativo autenticador</p>
      </div>

      <div v-if="errors.code" class="alert alert-danger mb-4">
        {{ errors.code }}
      </div>

      <form @submit.prevent="submit">
        <div class="form-group mb-4">
          <label class="form-label" for="code">Código 2FA</label>
          <input
            id="code"
            v-model="form.code"
            type="text"
            class="form-input text-center"
            placeholder="000000"
            maxlength="6"
            required
            pattern="[0-9]*"
            inputmode="numeric"
            autocomplete="one-time-code"
            autofocus
            style="font-size: 1.5rem; letter-spacing: 0.3em; padding-left: 1.25rem;"
          />
        </div>

        <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
          {{ form.processing ? 'Verificando...' : 'Confirmar e Acessar' }}
        </button>

        <div class="mt-4">
          <a href="/heimdall/login" class="text-muted" style="font-size: 0.8125rem;">Voltar ao Login comum</a>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const form = useForm({
  code: '',
})

const errors = computed(() => form.errors)

function submit() {
  form.post(route('admin.login.2fa.post'))
}
</script>
