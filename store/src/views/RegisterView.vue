<template>
  <div class="login-page">
    <div class="login-split-container">
      
      <!-- Lado Esquerdo: Imagem de Fundo (Esporte/Marca) -->
      <div class="login-image-section">
        <div class="image-overlay">
          <div class="brand-showcase">
            <h2 class="brand-title">90+<span class="text-red">STORE</span></h2>
            <p class="brand-tagline">Sua jornada começa aqui. Faça parte do time.</p>
          </div>
        </div>
      </div>

      <!-- Lado Direito: Formulário de Cadastro -->
      <div class="login-form-section">
        <div class="form-wrapper form-wrapper-large">
          
          <div class="login-header">
            <h1 class="login-title">Criar Conta</h1>
            <p class="login-subtitle">Preencha seus dados para começar a comprar.</p>
          </div>

          <div v-if="error" class="alert alert-danger mb-4">
            {{ error }}
          </div>

          <form @submit.prevent="submit" class="login-form">
            <!-- Dados Pessoais -->
            <h3 class="section-title">Dados Pessoais</h3>
            
            <div class="form-group">
              <label class="form-label">Nome Completo</label>
              <input v-model="form.nome_completo" type="text" class="modern-input" placeholder="Seu nome completo" required />
            </div>

            <div class="form-group">
              <label class="form-label">Nome Social <span class="text-muted">(Opcional)</span></label>
              <input v-model="form.nome_social" type="text" class="modern-input" placeholder="Como prefere ser chamado" />
            </div>

            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">CPF</label>
                <input v-model="form.cpf" type="text" class="modern-input" placeholder="000.000.000-00" @input="maskCPF" maxlength="14" required />
              </div>
              <div class="form-group">
                <label class="form-label">Data de Nascimento</label>
                <input v-model="form.data_nascimento" type="date" class="modern-input" required />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Telefone</label>
              <div class="input-icon-wrapper">
                <span class="input-icon" style="font-weight:bold; color:var(--color-white); left: 1rem; top: 50%; transform: translateY(-50%); font-size: 0.95rem;">+55</span>
                <input v-model="form.telefone" type="text" class="modern-input" style="padding-left: 3.2rem;" placeholder="(00) 00000-0000" @input="maskPhone" maxlength="15" required />
              </div>
            </div>

            <div class="form-options mb-4" style="margin-top: -10px;">
              <label class="remember-me">
                <input type="checkbox" v-model="form.is_whatsapp" />
                <span>Este número é WhatsApp</span>
              </label>
            </div>

            <!-- Dados de Acesso -->
            <h3 class="section-title mt-4">Dados de Acesso</h3>

            <div class="form-group">
              <label class="form-label">E-mail</label>
              <input v-model="form.email" type="email" class="modern-input" placeholder="seu.email@exemplo.com" required />
            </div>

            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">Senha</label>
                <input v-model="form.password" type="password" class="modern-input" placeholder="Mínimo 8 dígitos" required />
              </div>
              <div class="form-group">
                <label class="form-label">Confirmar Senha</label>
                <input v-model="form.password_confirmation" type="password" class="modern-input" placeholder="Repita a senha" required />
              </div>
            </div>

            <!-- Endereço -->
            <h3 class="section-title mt-4">Endereço de Entrega</h3>

            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">CEP</label>
                <div style="display: flex; gap: 10px;">
                  <input v-model="form.cep" type="text" class="modern-input" placeholder="00000-000" @input="maskCEP" @blur="fetchAddress" maxlength="9" required />
                  <button type="button" class="btn-cep" @click="fetchAddress" :disabled="loadingCep">
                    <span v-if="!loadingCep">🔍</span>
                    <span v-else class="loading-spinner-small"></span>
                  </button>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Estado</label>
                <input v-model="form.estado" type="text" class="modern-input" placeholder="UF" maxlength="2" required />
              </div>
            </div>

            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">Cidade</label>
                <input v-model="form.cidade" type="text" class="modern-input" required />
              </div>
              <div class="form-group">
                <label class="form-label">Bairro</label>
                <input v-model="form.bairro" type="text" class="modern-input" required />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Rua / Logradouro</label>
              <input v-model="form.logradouro" type="text" class="modern-input" required />
            </div>

            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">Número</label>
                <input v-model="form.numero" type="text" class="modern-input" required />
              </div>
              <div class="form-group">
                <label class="form-label">Complemento</label>
                <input v-model="form.complemento" type="text" class="modern-input" placeholder="Apto, Bloco, etc." />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Ponto de Referência</label>
              <input v-model="form.ponto_referencia" type="text" class="modern-input" placeholder="Opcional" />
            </div>

            <button type="submit" class="btn-submit mt-6" :disabled="loading">
              <span v-if="!loading">Criar Conta e Continuar</span>
              <span v-else class="loading-spinner"></span>
            </button>

            <div class="form-footer">
              <p>Já possui uma conta? <RouterLink to="/login" class="register-link">Faça login</RouterLink></p>
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
import axios from 'axios'

const store = useStore()
const router = useRouter()
const route = useRoute()

const form = ref({
  nome_completo: '',
  nome_social: '',
  cpf: '',
  data_nascimento: '',
  telefone: '',
  is_whatsapp: true,
  email: '',
  password: '',
  password_confirmation: '',
  cep: '',
  logradouro: '',
  numero: '',
  complemento: '',
  bairro: '',
  cidade: '',
  estado: '',
  ponto_referencia: ''
})

const error = ref('')
const loading = ref(false)
const loadingCep = ref(false)

// Máscaras Simplificadas
function maskCPF(e) {
  let v = e.target.value.replace(/\D/g, '')
  if (v.length > 11) v = v.slice(0, 11)
  v = v.replace(/(\d{3})(\d)/, '$1.$2')
  v = v.replace(/(\d{3})(\d)/, '$1.$2')
  v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2')
  form.value.cpf = v
}

function maskPhone(e) {
  let v = e.target.value.replace(/\D/g, '')
  if (v.length > 11) v = v.slice(0, 11)
  
  if (v.length > 0) {
    if (v.length <= 2) v = `(${v}`
    else if (v.length <= 7) v = `(${v.slice(0,2)}) ${v.slice(2)}`
    else v = `(${v.slice(0,2)}) ${v.slice(2,7)}-${v.slice(7)}`
  }
  form.value.telefone = v
}

function maskCEP(e) {
  let v = e.target.value.replace(/\D/g, '')
  if (v.length > 8) v = v.slice(0, 8)
  v = v.replace(/(\d{5})(\d)/, '$1-$2')
  form.value.cep = v
}

// Busca CEP com Redundância
async function fetchAddress() {
  const cleanCep = form.value.cep.replace(/\D/g, '')
  if (cleanCep.length !== 8) return

  loadingCep.value = true
  
  try {
    // Tenta BrasilAPI primeiro
    const res = await axios.get(`https://brasilapi.com.br/api/cep/v1/${cleanCep}`, { timeout: 4000 })
    form.value.logradouro = res.data.street || ''
    form.value.bairro = res.data.neighborhood || ''
    form.value.cidade = res.data.city || ''
    form.value.estado = res.data.state || ''
  } catch (err) {
    console.warn("BrasilAPI falhou, tentando ViaCEP...")
    try {
      // Fallback ViaCEP
      const res2 = await axios.get(`https://viacep.com.br/ws/${cleanCep}/json/`, { timeout: 4000 })
      if (!res2.data.erro) {
        form.value.logradouro = res2.data.logradouro || ''
        form.value.bairro = res2.data.bairro || ''
        form.value.cidade = res2.data.localidade || ''
        form.value.estado = res2.data.uf || ''
      }
    } catch (err2) {
      console.error("Falha em ambas APIs de CEP")
    }
  } finally {
    loadingCep.value = false
  }
}

async function submit() {
  loading.value = true
  error.value = ''
  
  try {
    const payload = { ...form.value }
    if (payload.telefone && !payload.telefone.startsWith('+55')) {
      payload.telefone = `+55 ${payload.telefone}`
    }
    
    const res = await axios.post('/api/register', payload)
    store.setToken(res.data.token)
    await store.fetchUser()
    
    const redirectPath = route.query.redirect || '/minha-conta'
    router.push(redirectPath)
  } catch (err) {
    if (err.response?.status === 422) {
      const msgs = []
      for (const key in err.response.data.errors) {
        msgs.push(err.response.data.errors[key][0])
      }
      error.value = msgs.join(' ')
    } else {
      error.value = 'Ocorreu um erro ao registrar. Tente novamente.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: calc(100vh - 80px);
  display: flex;
  background-color: var(--color-black);
  margin-top: -30px;
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
  background-image: url('/sports_register_bg.png');
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

.form-wrapper-large {
  max-width: 550px;
  max-height: 80vh;
  overflow-y: auto;
  padding-right: 15px;
}

/* Custom scrollbar para o form de registro longo */
.form-wrapper-large::-webkit-scrollbar {
  width: 6px;
}
.form-wrapper-large::-webkit-scrollbar-track {
  background: var(--color-black);
}
.form-wrapper-large::-webkit-scrollbar-thumb {
  background: var(--color-gray-dark);
  border-radius: 4px;
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

.section-title {
  font-family: var(--font-title);
  font-size: 1.25rem;
  color: var(--color-white);
  margin: 1.5rem 0 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--color-black-lighter);
}

/* Campos do Formulário */
.form-group {
  margin-bottom: 1.25rem;
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

.text-muted {
  color: var(--color-gray-dark);
  font-size: 0.75rem;
}

.modern-input {
  width: 100%;
  background-color: var(--color-black);
  border: 1px solid var(--color-black-lighter);
  color: var(--color-white);
  padding: 0.875rem 1rem;
  border-radius: var(--border-radius);
  font-size: 1rem;
  transition: var(--transition);
}

.modern-input:focus {
  outline: none;
  border-color: var(--color-red);
  box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.15);
}

.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.btn-cep {
  background-color: var(--color-black-lighter);
  border: none;
  border-radius: var(--border-radius);
  padding: 0 1.25rem;
  color: var(--color-white);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
}

.btn-cep:hover:not(:disabled) {
  background-color: var(--color-red);
}

/* Checkbox */
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
  padding-bottom: 2rem;
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

.loading-spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: var(--color-white);
  animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
