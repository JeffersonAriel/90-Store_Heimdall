<template>
  <div class="login-page">
    <div class="login-split-container">
      
      <!-- Lado Esquerdo: Imagem de Fundo (Esporte/Marca) -->
      <div class="login-image-section">
        <div class="image-overlay">
          <div class="brand-showcase">
            <h2 class="brand-title">90+<span class="text-red">STORE</span></h2>
            <p class="brand-tagline">Ultrapasse seus limites. Vista a performance.</p>
          </div>
        </div>
      </div>

      <!-- Lado Direito: Formulário de Login -->
      <div class="login-form-section">
        <div class="form-wrapper">
          
          <div class="login-header">
            <h1 class="login-title">Acesse sua Conta</h1>
            <p class="login-subtitle">Bem-vindo de volta! Faça login para continuar suas compras esportivas.</p>
          </div>

          <div v-if="error" class="alert alert-danger mb-4">
            {{ error }}
          </div>

          <form @submit.prevent="submit" class="login-form">
            <div class="form-group">
              <label class="form-label">E-mail</label>
              <div class="input-icon-wrapper">
                <span class="input-icon">✉️</span>
                <input v-model="form.email" type="email" class="modern-input" placeholder="seu.email@exemplo.com" required />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Senha</label>
              <div class="input-icon-wrapper">
                <span class="input-icon">🔒</span>
                <input v-model="form.password" type="password" class="modern-input" placeholder="Sua senha" required />
              </div>
            </div>

            <div class="form-options">
              <label class="remember-me">
                <input type="checkbox" />
                <span>Lembrar-me</span>
              </label>
              <a href="#" class="forgot-password">Esqueceu a senha?</a>
            </div>

            <button type="submit" class="btn-submit" :disabled="loading">
              <span v-if="!loading">Entrar na Loja</span>
              <span v-else class="loading-spinner"></span>
            </button>

            <div class="form-footer">
              <p>Não tem uma conta? <RouterLink to="/cadastro" class="register-link">Cadastre-se grátis</RouterLink></p>
            </div>
          </form>

        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import { useStore } from '@/store/main'
import { useHead } from '@vueuse/head'
import axios from 'axios'

useHead({ title: 'Login | 90+ Store' })

const store = useStore()
const router = useRouter()
const route = useRoute()

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
    const redirectPath = route.query.redirect || '/minha-conta'
    router.push(redirectPath)
  } catch (err) {
    error.value = err.response?.data?.message || 'E-mail ou senha incorretos.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: calc(100vh - 80px); /* Subtraindo header se houver */
  display: flex;
  background-color: var(--color-black);
  margin-top: -30px; /* Para grudar no header no layout atual */
}

.login-split-container {
  display: flex;
  width: 100%;
  max-width: 1600px;
  margin: 0 auto;
  background-color: var(--color-black-light);
  border-radius: 0;
  overflow: hidden;
}

/* Lado da Imagem */
.login-image-section {
  flex: 1.2;
  background-image: url('/sports_login_bg.png');
  background-size: cover;
  background-position: center;
  position: relative;
  display: none;
}

@media (min-width: 992px) {
  .login-image-section {
    display: block;
  }
}

.image-overlay {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: linear-gradient(to right, rgba(10, 10, 10, 0.2) 0%, rgba(10, 10, 10, 0.9) 100%);
  display: flex;
  align-items: flex-end;
  padding: 4rem;
}

.brand-showcase {
  max-width: 500px;
  animation: fadeUp 1s ease-out forwards;
}

.brand-title {
  font-family: var(--font-title);
  font-size: 4rem;
  color: var(--color-white);
  line-height: 1;
  margin-bottom: 0.5rem;
  letter-spacing: 2px;
}

.text-red {
  color: var(--color-red);
}

.brand-tagline {
  font-size: 1.25rem;
  color: var(--color-gray);
  font-weight: 300;
  letter-spacing: 1px;
}

/* Lado do Formulário */
.login-form-section {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background-color: var(--color-black-light);
  position: relative;
}

.form-wrapper {
  width: 100%;
  max-width: 420px;
  animation: fadeIn 0.8s ease-out forwards;
}

.login-header {
  margin-bottom: 2.5rem;
  text-align: center;
}

.login-title {
  font-family: var(--font-title);
  font-size: 2.25rem;
  color: var(--color-white);
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.login-subtitle {
  color: var(--color-gray);
  font-size: 0.95rem;
  line-height: 1.5;
}

/* Campos do Formulário */
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  color: var(--color-gray);
  margin-bottom: 0.5rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.input-icon-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 1rem;
  color: var(--color-gray-dark);
  font-size: 1.1rem;
}

.modern-input {
  width: 100%;
  background-color: var(--color-black);
  border: 1px solid var(--color-black-lighter);
  color: var(--color-white);
  padding: 0.875rem 1rem 0.875rem 3rem;
  border-radius: var(--border-radius);
  font-size: 1rem;
  transition: var(--transition);
}

.modern-input:focus {
  outline: none;
  border-color: var(--color-red);
  box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.15);
}

.modern-input::placeholder {
  color: var(--color-gray-dark);
}

/* Opções (Lembrar / Esqueci) */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  font-size: 0.875rem;
}

.remember-me {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--color-gray);
  cursor: pointer;
}

.remember-me input {
  accent-color: var(--color-red);
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.forgot-password {
  color: var(--color-gray);
  text-decoration: none;
  transition: var(--transition);
}

.forgot-password:hover {
  color: var(--color-red);
}

/* Botão Submit */
.btn-submit {
  width: 100%;
  background-color: var(--color-red);
  color: var(--color-white);
  border: none;
  padding: 1rem;
  border-radius: var(--border-radius);
  font-size: 1.1rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 54px;
}

.btn-submit:hover:not(:disabled) {
  background-color: var(--color-red-light);
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(227, 6, 19, 0.3);
}

.btn-submit:disabled {
  background-color: var(--color-gray-dark);
  cursor: not-allowed;
  opacity: 0.7;
}

/* Footer / Cadastro */
.form-footer {
  margin-top: 2rem;
  text-align: center;
  font-size: 0.95rem;
  color: var(--color-gray);
}

.register-link {
  color: var(--color-red);
  font-weight: 600;
  text-decoration: none;
  margin-left: 0.25rem;
  transition: var(--transition);
}

.register-link:hover {
  color: var(--color-red-light);
  text-decoration: underline;
}

/* Alertas */
.alert {
  padding: 1rem;
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  font-weight: 500;
  text-align: center;
}

.alert-danger {
  background-color: rgba(227, 6, 19, 0.1);
  color: var(--color-red-light);
  border: 1px solid rgba(227, 6, 19, 0.2);
}

/* Animações */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Spinner */
.loading-spinner {
  width: 24px;
  height: 24px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: var(--color-white);
  animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
