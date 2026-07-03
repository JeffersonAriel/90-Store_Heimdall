<template>
  <div class="installer-container">
    <!-- Logo -->
    <div class="installer-logo">
      <div class="logo-mark">
        <svg viewBox="0 0 40 40" fill="none" class="w-12 h-12">
          <path d="M20 4L4 12l16 8 16-8-16-8z" fill="#6366f1"/>
          <path d="M4 28l16 8 16-8" stroke="#a5b4fc" stroke-width="2" stroke-linecap="round"/>
          <path d="M4 20l16 8 16-8" stroke="#c7d2fe" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <h1 class="installer-title">HEIMDALL ERP</h1>
      <p class="installer-subtitle">Assistente de Instalação</p>
    </div>

    <!-- Card -->
    <div class="installer-card">
      <!-- Steps indicator -->
      <div class="installer-steps">
        <template v-for="(step, i) in steps" :key="i">
          <div class="installer-step">
            <div class="step-dot" :class="stepClass(i)">
              <svg v-if="i < currentStep" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3">
                <path d="M20 6L9 17l-5-5"/>
              </svg>
              <span v-else>{{ i + 1 }}</span>
            </div>
          </div>
          <div v-if="i < steps.length - 1" class="step-line" :class="{ done: i < currentStep }" />
        </template>
      </div>

      <!-- Step title -->
      <h2 class="step-title">{{ steps[currentStep].title }}</h2>
      <p class="step-desc">{{ steps[currentStep].description }}</p>

      <!-- Step content -->
      <Transition name="slide-fade" mode="out-in">
        <component
          :is="steps[currentStep].component"
          :key="currentStep"
          v-model="formData"
          @next="nextStep"
          @prev="prevStep"
          @done="onDone"
        />
      </Transition>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import StepRequirements from './Steps/StepRequirements.vue'
import StepDatabase from './Steps/StepDatabase.vue'
import StepEnv from './Steps/StepEnv.vue'
import StepMigrate from './Steps/StepMigrate.vue'
import StepAdmin from './Steps/StepAdmin.vue'
import StepCompany from './Steps/StepCompany.vue'
import StepFinish from './Steps/StepFinish.vue'

const currentStep = ref(0)

const formData = reactive({
  db_host: '127.0.0.1',
  db_port: 3306,
  db_database: 'heimdall',
  db_username: 'root',
  db_password: '',
  admin_name: '',
  admin_email: '',
  admin_password: '',
  admin_password_confirmation: '',
  company_name: '90+ Store',
  company_cnpj: '',
})

const steps = [
  {
    title: 'Verificar Requisitos',
    description: 'Verificando se o servidor atende todos os requisitos necessários.',
    component: StepRequirements,
  },
  {
    title: 'Banco de Dados',
    description: 'Configure a conexão com o MySQL. As informações são salvas no arquivo .env.',
    component: StepDatabase,
  },
  {
    title: 'Testar Conexão',
    description: 'Testando a conexão com o banco de dados configurado.',
    component: StepEnv,
  },
  {
    title: 'Migrar Banco',
    description: 'Criando as tabelas e estrutura do banco de dados.',
    component: StepMigrate,
  },
  {
    title: 'Criar Administrador',
    description: 'Configure a conta do Administrador Geral do sistema.',
    component: StepAdmin,
  },
  {
    title: 'Dados da Empresa',
    description: 'Informações básicas da sua empresa para o sistema.',
    component: StepCompany,
  },
  {
    title: 'Instalação Concluída!',
    description: 'Tudo pronto! O sistema está instalado e configurado.',
    component: StepFinish,
  },
]

const stepClass = (i: number) => {
  if (i < currentStep.value) return 'done'
  if (i === currentStep.value) return 'active'
  return 'pending'
}

const nextStep = () => {
  if (currentStep.value < steps.length - 1) currentStep.value++
}

const prevStep = () => {
  if (currentStep.value > 0) currentStep.value--
}

const onDone = () => {
  router.visit('/erp/login')
}
</script>

<style scoped>
.installer-title {
  font-size: 28px;
  font-weight: 800;
  background: linear-gradient(135deg, #c7d2fe, #a5b4fc, #818cf8);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: 0.05em;
  margin-top: 12px;
}

.installer-subtitle {
  font-size: 14px;
  color: #64748b;
  margin-top: 4px;
}

.logo-mark {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(79, 70, 229, 0.2));
  border: 1px solid rgba(99, 102, 241, 0.3);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  backdrop-filter: blur(8px);
}

.step-title {
  font-size: 22px;
  font-weight: 700;
  color: #e2e8f0;
  margin-bottom: 6px;
}

.step-desc {
  font-size: 14px;
  color: #64748b;
  margin-bottom: 28px;
}

.slide-fade-enter-active, .slide-fade-leave-active {
  transition: all 0.3s ease;
}
.slide-fade-enter-from { opacity: 0; transform: translateX(20px); }
.slide-fade-leave-to { opacity: 0; transform: translateX(-20px); }
</style>
