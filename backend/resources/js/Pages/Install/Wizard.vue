<template>
  <div class="install-wizard-container">
    <div class="install-card">
      <div class="install-header">
        <div class="logo-circle">H</div>
        <h1>Instalação do Heimdall</h1>
        <p class="subtitle text-secondary">Configuração inicial do back-office e do e-commerce</p>
      </div>

      <!-- Steps indicator -->
      <div class="steps-indicator">
        <div class="step-dot" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
          <span class="dot-num">1</span>
          <span class="dot-label">Bem-vindo</span>
        </div>
        <div class="step-line" :class="{ completed: currentStep > 1 }" />
        <div class="step-dot" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
          <span class="dot-num">2</span>
          <span class="dot-label">Banco</span>
        </div>
        <div class="step-line" :class="{ completed: currentStep > 2 }" />
        <div class="step-dot" :class="{ active: currentStep >= 3, completed: currentStep > 3 }">
          <span class="dot-num">3</span>
          <span class="dot-label">Administrador</span>
        </div>
        <div class="step-line" :class="{ completed: currentStep > 3 }" />
        <div class="step-dot" :class="{ active: currentStep >= 4 }">
          <span class="dot-num">4</span>
          <span class="dot-label">Pronto</span>
        </div>
      </div>

      <!-- Step 1: Welcome -->
      <div v-if="currentStep === 1" class="step-content">
        <h2>Bem-vindo ao Sistema Heimdall</h2>
        <p>Este assistente irá ajudá-lo a configurar o banco de dados local ou de produção (HostGator) e criar o primeiro funcionário Administrador para gerenciar seu e-commerce esportivo.</p>
        <div class="info-box mt-4">
          <strong>Antes de começar:</strong>
          <ul>
            <li>Certifique-se de que a conexão com a internet esteja ativa.</li>
            <li>Se for usar MySQL, crie previamente um banco de dados vazio pelo cPanel da HostGator ou localmente.</li>
            <li>Para testes locais rápidos, você pode selecionar a opção <strong>SQLite</strong>.</li>
          </ul>
        </div>
        <div class="actions mt-6">
          <button class="btn btn-primary w-full" @click="currentStep = 2">Iniciar Configuração</button>
        </div>
      </div>

      <!-- Step 2: Database config -->
      <div v-if="currentStep === 2" class="step-content">
        <h2>Configurar Banco de Dados</h2>
        <p class="text-secondary mb-4">Escolha a conexão e insira os dados de acesso.</p>

        <div class="form-group mb-4">
          <label class="form-label">Tipo de Banco</label>
          <select v-model="formDB.driver" class="form-select">
            <option value="sqlite">SQLite (Recomendado para Testes Locais)</option>
            <option value="mysql">MySQL 8 (Recomendado para Produção / HostGator)</option>
          </select>
        </div>

        <div v-if="formDB.driver === 'mysql'">
          <div class="grid-2">
            <div class="form-group mb-4">
              <label class="form-label">Host</label>
              <input v-model="formDB.host" type="text" class="form-input" placeholder="127.0.0.1 ou localhost" />
            </div>
            <div class="form-group mb-4">
              <label class="form-label">Porta</label>
              <input v-model="formDB.port" type="text" class="form-input" placeholder="3306" />
            </div>
          </div>

          <div class="form-group mb-4">
            <label class="form-label">Nome do Banco de Dados</label>
            <input v-model="formDB.database" type="text" class="form-input" placeholder="ex: heimdall_90store" />
          </div>

          <div class="grid-2">
            <div class="form-group mb-4">
              <label class="form-label">Usuário</label>
              <input v-model="formDB.username" type="text" class="form-input" placeholder="root" />
            </div>
            <div class="form-group mb-4">
              <label class="form-label">Senha</label>
              <input v-model="formDB.password" type="password" class="form-input" placeholder="Sua senha" />
            </div>
          </div>
        </div>

        <div v-else class="info-box mb-4">
          <p>O SQLite criará automaticamente um arquivo de banco de dados chamado <strong>database.sqlite</strong> em <code>database/database.sqlite</code> no seu ambiente local.</p>
        </div>

        <!-- Connection Test & Messages -->
        <div v-if="connectionMsg" class="alert" :class="connectionSuccess ? 'alert-success' : 'alert-danger'">
          {{ connectionMsg }}
        </div>

        <div class="actions mt-6 flex justify-between gap-4">
          <button class="btn btn-secondary" @click="currentStep = 1">Voltar</button>
          <button 
            v-if="!connectionSuccess" 
            class="btn btn-warning" 
            :disabled="testingConnection" 
            @click="testConnection"
          >
            {{ testingConnection ? 'Testando...' : 'Testar Conexão' }}
          </button>
          <button 
            v-else 
            class="btn btn-success" 
            :disabled="runningSetup" 
            @click="runSetup"
          >
            {{ runningSetup ? 'Configurando...' : 'Aplicar e Migrar Banco' }}
          </button>
        </div>
      </div>

      <!-- Step 3: Admin creation -->
      <div v-if="currentStep === 3" class="step-content">
        <h2>Criar Administrador</h2>
        <p class="text-secondary mb-4">Cadastre a conta do primeiro funcionário com perfil de Administrador.</p>

        <div class="form-group mb-4">
          <label class="form-label">Nome Completo</label>
          <input v-model="formAdmin.name" type="text" class="form-input" placeholder="Ex: Jefferson Ariel" />
        </div>

        <div class="form-group mb-4">
          <label class="form-label">E-mail Corporativo</label>
          <input v-model="formAdmin.email" type="email" class="form-input" placeholder="exemplo@90store.com.br" />
        </div>

        <div class="form-group mb-4">
          <label class="form-label">Senha de Acesso</label>
          <input v-model="formAdmin.password" type="password" class="form-input" placeholder="Mínimo de 8 caracteres" />
        </div>

        <div v-if="adminMsg" class="alert alert-danger">
          {{ adminMsg }}
        </div>

        <div class="actions mt-6 flex justify-between gap-4">
          <button class="btn btn-secondary" disabled>Voltar</button>
          <button class="btn btn-primary" :disabled="creatingAdmin" @click="createAdmin">
            {{ creatingAdmin ? 'Criando...' : 'Finalizar Instalação' }}
          </button>
        </div>
      </div>

      <!-- Step 4: Finished -->
      <div v-if="currentStep === 4" class="step-content text-center">
        <div class="success-icon-container">
          <div class="success-badge">✓</div>
        </div>
        <h2>Instalação Concluída!</h2>
        <p class="text-secondary mt-2">O sistema Heimdall e a API da 90-Store foram configurados com sucesso.</p>

        <div class="info-box text-left mt-4">
          <strong>Anotações Importantes para Produção:</strong>
          <ul>
            <li>Por segurança, a rota <code>/install</code> agora foi desativada e redirecionará para a tela de login.</li>
            <li>O driver de filas configurado é o <code>database</code>, ideal para a HostGator.</li>
            <li>O banco de dados foi completamente populado com os seeders de categorias esportivas e APIs.</li>
          </ul>
        </div>

        <div class="actions mt-6">
          <a :href="`${basePath}/heimdall/login`" class="btn btn-primary w-full">Acessar Painel Heimdall</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const currentStep = ref(1)

// Form DB
const formDB = ref({
  driver: 'sqlite',
  host: '127.0.0.1',
  port: '3306',
  database: 'database.sqlite',
  username: 'root',
  password: ''
})

const testingConnection = ref(false)
const connectionSuccess = ref(false)
const connectionMsg = ref('')
const runningSetup = ref(false)

const basePath = window.location.pathname.replace(/\/install$/, '')

async function testConnection() {
  testingConnection.value = true
  connectionMsg.value = ''
  try {
    const res = await axios.post(`${basePath}/install/test`, formDB.value)
    connectionSuccess.value = true
    connectionMsg.value = res.data.message
  } catch (err) {
    connectionSuccess.value = false
    connectionMsg.value = err.response?.data?.message || 'Falha ao conectar com o banco.'
  } finally {
    testingConnection.value = false
  }
}

async function runSetup() {
  runningSetup.value = true
  connectionMsg.value = ''
  try {
    const res = await axios.post(`${basePath}/install/setup`, formDB.value)
    connectionMsg.value = res.data.message
    // Espera 1.5s para transição visual suave
    setTimeout(() => {
      currentStep.value = 3
    }, 1500)
  } catch (err) {
    connectionMsg.value = err.response?.data?.message || 'Erro ao rodar setup e migrations.'
  } finally {
    runningSetup.value = false
  }
}

// Form Admin
const formAdmin = ref({
  name: '',
  email: '',
  password: ''
})

const creatingAdmin = ref(false)
const adminMsg = ref('')

async function createAdmin() {
  if (!formAdmin.value.name || !formAdmin.value.email || !formAdmin.value.password) {
    adminMsg.value = 'Por favor, preencha todos os campos obrigatórios.'
    return
  }

  creatingAdmin.value = true
  adminMsg.value = ''
  try {
    await axios.post(`${basePath}/install/admin`, formAdmin.value)
    currentStep.value = 4
  } catch (err) {
    adminMsg.value = err.response?.data?.message || 'Erro ao criar o administrador.'
  } finally {
    creatingAdmin.value = false
  }
}
</script>

<style>
.install-wizard-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background-color: var(--color-bg-primary, #0d0d1a);
  padding: 1.5rem;
}

.install-card {
  background: var(--color-bg-card, #1a1a35);
  border: 1px solid var(--color-border, rgba(255, 255, 255, 0.08));
  border-radius: var(--radius-xl, 16px);
  width: 100%;
  max-width: 580px;
  padding: 2.5rem;
  box-shadow: var(--shadow-lg);
}

.install-header {
  text-align: center;
  margin-bottom: 2rem;
}

.logo-circle {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, var(--color-brand, #6366f1), var(--color-brand-dark, #4f46e5));
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-family: 'Outfit', sans-serif;
  font-weight: 800;
  font-size: 1.5rem;
  margin-bottom: 1rem;
  box-shadow: var(--shadow-glow);
}

.subtitle {
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.steps-indicator {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2.5rem;
  position: relative;
}

.step-dot {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 2;
}

.dot-num {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--color-bg-elevated, #1f1f40);
  border: 2px solid var(--color-border);
  color: var(--color-text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8125rem;
  font-weight: 600;
  transition: all var(--transition-fast);
}

.step-dot.active .dot-num {
  border-color: var(--color-brand);
  background: var(--color-brand);
  color: white;
  box-shadow: 0 0 10px var(--color-brand-glow);
}

.step-dot.completed .dot-num {
  border-color: var(--color-success);
  background: var(--color-success);
  color: white;
}

.dot-label {
  font-size: 0.6875rem;
  color: var(--color-text-muted);
  margin-top: 0.375rem;
  font-weight: 500;
  position: absolute;
  top: 28px;
  white-space: nowrap;
}

.step-dot.active .dot-label {
  color: var(--color-text-primary);
  font-weight: 600;
}

.step-line {
  flex: 1;
  height: 2px;
  background: var(--color-border);
  margin: 0 0.5rem;
  margin-bottom: 14px; /* Align with dots center */
  position: relative;
  z-index: 1;
  transition: background var(--transition-normal);
}

.step-line.completed {
  background: var(--color-success);
}

.info-box {
  background: var(--color-bg-elevated);
  border-left: 4px solid var(--color-brand);
  border-radius: var(--radius-md);
  padding: 1rem;
  font-size: 0.875rem;
  line-height: 1.6;
}

.info-box ul {
  padding-left: 1.25rem;
  margin-top: 0.5rem;
}

.success-icon-container {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
}

.success-badge {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--color-success-bg);
  border: 2px solid var(--color-success);
  color: var(--color-success-light);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: bold;
}

.text-center { text-align: center; }
</style>
