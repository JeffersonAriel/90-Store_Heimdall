<template>
  <div class="login-wrapper">
    <!-- Left panel - branding -->
    <div class="login-left">
      <div class="login-brand">
        <div class="brand-icon">
          <svg viewBox="0 0 40 40" fill="none">
            <path d="M20 4L4 12l16 8 16-8-16-8z" fill="#6366f1"/>
            <path d="M4 28l16 8 16-8" stroke="#a5b4fc" stroke-width="2" stroke-linecap="round"/>
            <path d="M4 20l16 8 16-8" stroke="#c7d2fe" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <h1 class="brand-name">HEIMDALL</h1>
        <p class="brand-tagline">Enterprise Resource Planning</p>
      </div>

      <div class="login-features">
        <div v-for="feature in features" :key="feature" class="feature-item">
          <svg class="feature-check" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
          </svg>
          <span>{{ feature }}</span>
        </div>
      </div>

      <div class="login-deco" />
    </div>

    <!-- Right panel - form -->
    <div class="login-right">
      <div class="login-form-wrapper">
        <div class="text-center mb-8">
          <h2 class="text-2xl font-bold text-slate-100">Bem-vindo de volta</h2>
          <p class="text-slate-500 text-sm mt-2">Entre na sua conta para continuar</p>
        </div>

        <!-- Alert error -->
        <div v-if="form.errors.email" class="error-alert mb-6">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          {{ form.errors.email }}
        </div>

        <form @submit.prevent="submit">
          <div class="mb-4">
            <label class="form-label">E-mail</label>
            <input
              v-model="form.email"
              type="email"
              class="form-input"
              placeholder="admin@empresa.com"
              autocomplete="email"
              required
            />
          </div>

          <div class="mb-6">
            <div class="flex justify-between items-center">
              <label class="form-label">Senha</label>
              <a href="#" class="text-xs text-indigo-400 hover:text-indigo-300">Esqueci a senha</a>
            </div>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                class="form-input pr-10"
                placeholder="••••••••"
                autocomplete="current-password"
                required
              />
              <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-400"
                @click="showPassword = !showPassword"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path v-if="!showPassword" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="flex items-center justify-between mb-6">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.remember" type="checkbox" class="form-checkbox" />
              <span class="text-sm text-slate-400">Lembrar de mim</span>
            </label>
          </div>

          <button
            type="submit"
            class="btn btn-primary w-full justify-center py-3 text-base"
            :disabled="form.processing"
          >
            <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            {{ form.processing ? 'Entrando...' : 'Entrar no Sistema' }}
          </button>
        </form>

        <p class="text-center text-xs text-slate-600 mt-8">
          HEIMDALL ERP + 90+ Store — v1.0.0<br>
          <span class="text-slate-700">Acesso restrito a usuários autorizados</span>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const showPassword = ref(false)

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post('/erp/login')
}

const features = [
  'Dashboard com KPIs em tempo real',
  'Gestão completa de Produtos e Estoque',
  'E-commerce 90+ Store integrado',
  'IA para cadastro inteligente de produtos',
  'Relatórios e BI avançados',
  'RBAC e Auditoria completa',
]
</script>

<style scoped>
.login-wrapper {
  display: flex;
  min-height: 100vh;
  background: #0f172a;
}

.login-left {
  width: 45%;
  background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%);
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 60px;
  position: relative;
  overflow: hidden;
}

.login-brand {
  margin-bottom: 60px;
}

.brand-icon {
  width: 64px;
  height: 64px;
  background: rgba(99, 102, 241, 0.2);
  border: 1px solid rgba(99, 102, 241, 0.4);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}

.brand-name {
  font-size: 36px;
  font-weight: 900;
  background: linear-gradient(135deg, #e0e7ff, #c7d2fe, #a5b4fc);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: 0.1em;
}

.brand-tagline {
  font-size: 14px;
  color: rgba(165, 180, 252, 0.7);
  letter-spacing: 0.05em;
  text-transform: uppercase;
  margin-top: 4px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  font-size: 15px;
  color: rgba(199, 210, 254, 0.85);
}

.feature-check {
  width: 18px;
  height: 18px;
  color: #818cf8;
  flex-shrink: 0;
}

.login-deco {
  position: absolute;
  bottom: -100px;
  right: -100px;
  width: 400px;
  height: 400px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.2), transparent);
  pointer-events: none;
}

.login-right {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

.login-form-wrapper {
  width: 100%;
  max-width: 400px;
}

.error-alert {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 8px;
  color: #fca5a5;
  font-size: 13px;
}

.form-checkbox {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  border: 1px solid rgba(99, 102, 241, 0.4);
  background: transparent;
  cursor: pointer;
  accent-color: #6366f1;
}

@media (max-width: 768px) {
  .login-wrapper { flex-direction: column; }
  .login-left { width: 100%; padding: 40px 24px; }
  .login-right { padding: 40px 24px; }
}
</style>
