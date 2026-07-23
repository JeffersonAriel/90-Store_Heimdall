<template>
  <AdminLayout title="Configuração de APIs & Gateways">
    <template #breadcrumb>
      <span>Configurações / APIs e Gateways</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">🔗 Integrações & APIs</h1>
        <p class="text-secondary mt-1">Configure chaves de gateways de pagamento, CEP, transportadoras e regras gerais de frete.</p>
      </div>
    </div>

    <div class="grid-3 gap-6">
      <!-- APIs e Gateways -->
      <div class="col-span-2 flex flex-col gap-6">
        <!-- Pagamentos -->
        <div class="card">
          <div class="card-header flex justify-between items-center">
            <h3 class="card-title">💳 Gateways de Pagamento (Múltiplos e Plugáveis)</h3>
            <button @click="openCreateModal" class="btn btn-outline btn-sm">+ Novo Gateway</button>
          </div>
          <div class="card-body flex flex-col gap-4">
            <div v-for="api in paymentApis" :key="api.id" class="api-item p-4" style="background: var(--color-bg-elevated); border: 1px solid var(--color-border); border-radius: var(--radius-md);">
              <div class="flex justify-between items-center mb-3">
                <div>
                  <h4 class="font-bold flex items-center gap-2">
                    {{ api.nome }}
                    <span v-if="api.sandbox" class="badge badge-warning" style="font-size: 0.6875rem;">Sandbox</span>
                  </h4>
                  <p class="text-secondary" style="font-size: 0.8125rem;">{{ api.descricao }}</p>
                </div>
                <div class="flex items-center gap-4">
                  <label class="flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" v-model="api.ativo" @change="toggleApi(api)" />
                    <span style="font-size: 0.875rem;">Ativo</span>
                  </label>
                  <button @click="editApi(api)" class="btn btn-secondary btn-sm">Configurar</button>
                  <button @click="deleteApi(api)" class="btn btn-outline btn-sm text-red" title="Remover" v-if="!['mercadopago','pagseguro','stripe'].includes(api.slug)">Excluir</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CEP e Redundância -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">🔍 Redundância de Busca de CEP</h3>
          </div>
          <div class="card-body">
            <p class="text-secondary mb-4" style="font-size: 0.875rem;"> Célula de fallback do autopreenchimento de endereços. A primeira ativa que responder com sucesso será utilizada: </p>
            <div class="flex flex-col gap-2">
              <div v-for="(api, idx) in cepApis" :key="api.id" class="flex items-center justify-between p-3" style="background: var(--color-bg-elevated); border-radius: var(--radius-md);">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-brand" style="font-size: 0.875rem;">{{ idx + 1 }}º</span>
                  <strong>{{ api.nome }}</strong>
                </div>
                <span class="badge badge-success">Ativo (Fallback Auto)</span>
              </div>
            </div>
          </div>
        </div>

        <!-- WhatsApp CRM Enterprise Integration -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">💬 Integração com WhatsApp (CRM Enterprise)</h3>
          </div>
          <div class="card-body flex flex-col gap-4">
            <div v-for="api in whatsappApis" :key="api.id" class="api-item p-4" style="background: var(--color-bg-elevated); border: 1px solid var(--color-border); border-radius: var(--radius-md);">
              <div class="flex justify-between items-center">
                <div>
                  <h4 class="font-bold flex items-center gap-2">
                    {{ api.nome }}
                  </h4>
                  <p class="text-secondary" style="font-size: 0.8125rem;">Habilite e configure os parâmetros de conexão de sua instância do Evolution API para disparos automáticos.</p>
                </div>
                <div class="flex items-center gap-4">
                  <label class="flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" v-model="api.ativo" @change="toggleApi(api)" />
                    <span style="font-size: 0.875rem;">Ativo</span>
                  </label>
                  <button @click="editApi(api)" class="btn btn-secondary btn-sm">Configurar</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Servidor de E-mail SMTP / CRM Integration -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">📧 Servidor de Disparo de E-mails (SMTP & CRM)</h3>
          </div>
          <div class="card-body flex flex-col gap-4">
            <div v-for="api in emailApis" :key="api.id" class="api-item p-4" style="background: var(--color-bg-elevated); border: 1px solid var(--color-border); border-radius: var(--radius-md);">
              <div class="flex justify-between items-center">
                <div>
                  <h4 class="font-bold flex items-center gap-2">
                    {{ api.nome }}
                  </h4>
                  <p class="text-secondary" style="font-size: 0.8125rem;">Configure o servidor SMTP (HostGator Titan Mail, Gmail, Resend, etc.) para envio de e-mails das campanhas do CRM, contatos e notificações de pedidos.</p>
                </div>
                <div class="flex items-center gap-3">
                  <label class="flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" v-model="api.ativo" @change="toggleApi(api)" />
                    <span style="font-size: 0.875rem;">Ativo</span>
                  </label>
                  <button @click="editApi(api)" class="btn btn-secondary btn-sm">Configurar</button>
                  <button @click="openTestEmailModal" class="btn btn-outline btn-sm" style="color: var(--color-brand);">✉️ Testar Envio</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Configurações de Credenciais -->
    <div v-if="showConfigModal" class="modal-backdrop" @click.self="showConfigModal = false">
      <div class="modal-box">
        <h2 class="modal-title">Configurar {{ activeApi.nome }}</h2>
        <form @submit.prevent="saveApiConfig">
          <div class="form-group flex items-center mb-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="apiConfigForm.ativo" type="checkbox" />
              <strong>Gateway Ativo</strong>
            </label>
          </div>

          <div class="form-group flex items-center mb-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="apiConfigForm.sandbox" type="checkbox" />
              <span>Modo Sandbox (Testes)</span>
            </label>
          </div>

          <!-- Campos Dinâmicos (Para gateways nativos com template) -->
          <div v-if="hasTemplate" v-for="t in activeApiTemplate" :key="t.campo" class="form-group mb-4">
            <label class="form-label">{{ t.label }}</label>
            <input v-model="apiConfigForm.credenciais[t.campo]" :type="t.tipo === 'password' ? 'password' : 'text'" class="form-control" placeholder="Inserir..." :required="t.obrigatorio" />
          </div>

          <!-- Campos Dinâmicos (Para gateways manuais sem template) -->
          <div v-if="!hasTemplate" v-for="key in Object.keys(apiConfigForm.credenciais)" :key="key" class="form-group mb-4">
            <label class="form-label text-capitalize">{{ key.replace('_', ' ') }}</label>
            <input v-model="apiConfigForm.credenciais[key]" type="text" class="form-control" placeholder="Inserir valor..." required />
          </div>

          <div class="flex gap-3 mt-6" style="justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" @click="showConfigModal = false">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar Credenciais</button>
          </div>
        </form>
      </div>
    </div>
    <!-- Modal Criar Nova API -->
    <div v-if="showCreateModal" class="modal-backdrop" @click.self="showCreateModal = false">
      <div class="modal-box">
        <h2 class="modal-title">Adicionar Nova Integração</h2>
        <form @submit.prevent="createApi">
          <div class="form-group mb-4">
            <label class="form-label">Nome da Integração</label>
            <input v-model="createForm.nome" type="text" class="form-control" placeholder="Ex: PIX Manual, Cielo, etc" required />
          </div>
          
          <div class="form-group mb-4">
            <label class="form-label">Identificador Único (Slug)</label>
            <input v-model="createForm.slug" type="text" class="form-control" placeholder="Ex: pix_manual" required />
          </div>

          <div class="form-group mb-4">
            <label class="form-label">Tipo de Integração</label>
            <select v-model="createForm.tipo" class="form-control" required>
              <option value="gateway">Pagamento</option>
              <option value="frete">Frete (Transportadora)</option>
              <option value="cep">Busca de CEP</option>
            </select>
          </div>

          <div class="form-group mb-4">
            <label class="form-label">Campos Necessários (separados por vírgula)</label>
            <input v-model="createForm.campos_str" type="text" class="form-control" placeholder="Ex: chave_pix, token, public_key" required />
            <small class="text-secondary mt-1 block">Estes campos serão solicitados ao configurar a integração.</small>
          </div>

          <div class="flex gap-3 mt-6" style="justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" @click="showCreateModal = false">Cancelar</button>
            <button type="submit" class="btn btn-primary">Cadastrar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Enviar E-mail de Teste -->
    <div v-if="showTestEmailModal" class="modal-backdrop" @click.self="showTestEmailModal = false">
      <div class="modal-box">
        <h2 class="modal-title">🚀 Disparar E-mail de Teste</h2>
        <form @submit.prevent="sendTestEmail">
          <div class="form-group mb-4">
            <label class="form-label">E-mail de Destino *</label>
            <input v-model="testEmailForm.email_destino" type="email" class="form-control" placeholder="ex: seuemail@dominio.com" required />
          </div>
          <div class="form-group mb-4">
            <label class="form-label">Assunto</label>
            <input v-model="testEmailForm.assunto" type="text" class="form-control" placeholder="Teste de Envio de E-mail" />
          </div>
          <div class="form-group mb-4">
            <label class="form-label">Mensagem</label>
            <textarea v-model="testEmailForm.mensagem" rows="3" class="form-control" placeholder="Mensagem de teste..."></textarea>
          </div>
          <div class="flex gap-3 mt-6" style="justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" @click="showTestEmailModal = false">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="sendingTest">
              {{ sendingTest ? 'Enviando...' : 'Enviar E-mail de Teste' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  apis: { type: Array, required: true },
  freteRegra: { type: Object, default: null }
})

const showConfigModal = ref(false)
const showCreateModal = ref(false)
const activeApi = ref(null)

const paymentApis = computed(() => props.apis.filter(a => a.tipo === 'gateway'))
const cepApis = computed(() => props.apis.filter(a => a.tipo === 'cep'))
const whatsappApis = computed(() => props.apis.filter(a => a.slug === 'evolution'))
const emailApis = computed(() => props.apis.filter(a => a.tipo === 'email' || a.slug === 'smtp_mail'))

const apiConfigForm = ref({
  ativo: false,
  sandbox: false,
  credenciais: {}
})

const activeApiCreds = computed(() => {
  if (!activeApi.value || !activeApi.value.credenciais_json) return {}
  return JSON.parse(activeApi.value.credenciais_json)
})

const createForm = ref({
  nome: '',
  slug: '',
  tipo: 'gateway',
  campos_str: ''
})



const activeApiTemplate = computed(() => {
  if (!activeApi.value || !activeApi.value.template_campos_json) return []
  const templateStr = activeApi.value.template_campos_json
  return typeof templateStr === 'string' ? JSON.parse(templateStr) : templateStr
})

const hasTemplate = computed(() => activeApiTemplate.value.length > 0)

function editApi(api) {
  activeApi.value = api
  const creds = JSON.parse(api.credenciais_json || '{}')
  const formCreds = { ...creds }
  
  // Se for API nativa e não tem credenciais salvas, inicializa com vazio baseado no template
  let template = []
  if (api.template_campos_json) {
    template = typeof api.template_campos_json === 'string' ? JSON.parse(api.template_campos_json) : api.template_campos_json
    template.forEach(t => {
      if (formCreds[t.campo] === undefined) {
        formCreds[t.campo] = ''
      }
    })
  }

  apiConfigForm.value = {
    ativo: !!api.ativo,
    sandbox: !!api.sandbox,
    credenciais: formCreds
  }
  showConfigModal.value = true
}

function saveApiConfig() {
  router.put(route('admin.api-config.update', activeApi.value.slug), apiConfigForm.value, {
    onSuccess: () => {
      showConfigModal.value = false
    }
  })
}

function toggleApi(api) {
  const creds = JSON.parse(api.credenciais_json || '{}')
  router.put(route('admin.api-config.update', api.slug), {
    ativo: !!api.ativo,
    sandbox: !!api.sandbox,
    credenciais: creds
  })
}



function openCreateModal() {
  createForm.value = { nome: '', slug: '', tipo: 'gateway', campos_str: 'chave_pix' }
  showCreateModal.value = true
}

function createApi() {
  const camposArr = createForm.value.campos_str.split(',').map(s => s.trim()).filter(s => s)
  const credenciais = {}
  camposArr.forEach(c => { credenciais[c] = '' })

  router.post(route('admin.api-config.store'), {
    nome: createForm.value.nome,
    slug: createForm.value.slug,
    tipo: createForm.value.tipo,
    ativo: true,
    credenciais: credenciais
  }, {
    onSuccess: () => {
      showCreateModal.value = false
    }
  })
}

function deleteApi(api) {
  if (confirm(`Tem certeza que deseja excluir o gateway ${api.nome}?`)) {
    router.delete(route('admin.api-config.destroy', api.slug))
  }
}

const showTestEmailModal = ref(false)
const sendingTest = ref(false)
const testEmailForm = ref({
  email_destino: '',
  assunto: 'Teste de Envio — 90 Store',
  mensagem: 'Olá! Este é um e-mail de teste enviado via configurações de SMTP do painel Heimdall.'
})

function openTestEmailModal() {
  showTestEmailModal.value = true
}

function sendTestEmail() {
  sendingTest.value = true
  router.post(route('admin.api-config.test-email'), testEmailForm.value, {
    preserveScroll: true,
    onFinish: () => {
      sendingTest.value = false
      showTestEmailModal.value = false
    }
  })
}
</script>

<style scoped>
.text-capitalize {
  text-transform: capitalize;
}
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
}

.modal-box {
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: 2rem;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 24px 64px rgba(0,0,0,0.4);
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin-bottom: 1.5rem;
}
</style>
