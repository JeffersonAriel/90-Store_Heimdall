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
          <div class="card-header">
            <h3 class="card-title">💳 Gateways de Pagamento (Múltiplos e Plugáveis)</h3>
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
      </div>

      <!-- Configurações de Frete Geral -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">🚚 Regras de Frete Local & Melhor Envio</h3>
        </div>
        <div class="card-body">
          <form v-if="freteRegra" @submit.prevent="saveShippingRules">
            <div class="form-group">
              <label class="form-label">Frete Grátis a partir de (R$)</label>
              <input v-model.number="shippingForm.valor_minimo_gratis" type="number" step="0.01" class="form-control" required />
            </div>

            <div class="form-group">
              <label class="form-label">Raio Máximo de Entrega Local (Km)</label>
              <input v-model.number="shippingForm.raio_km_local" type="number" class="form-control" required />
              <small class="text-secondary">Raio a partir de São Miguel Paulista (SP) para habilitar Uber/99.</small>
            </div>

            <div class="form-group">
              <label class="form-label">CEP Origem (Faturamento)</label>
              <input v-model="shippingForm.cep_origem" type="text" class="form-control" required />
            </div>

            <div class="grid-2 gap-4">
              <div class="form-group">
                <label class="form-label">Latitude Origem</label>
                <input v-model.number="shippingForm.lat_origem" type="number" step="0.000001" class="form-control" required />
              </div>
              <div class="form-group">
                <label class="form-label">Longitude Origem</label>
                <input v-model.number="shippingForm.lng_origem" type="number" step="0.000001" class="form-control" required />
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-full mt-4">Salvar Regras de Frete</button>
          </form>
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

          <!-- Campos Dinâmicos conforme chaves do json -->
          <div v-for="key in Object.keys(activeApiCreds)" :key="key" class="form-group">
            <label class="form-label text-capitalize">{{ key.replace('_', ' ') }}</label>
            <input v-model="apiConfigForm.credenciais[key]" type="text" class="form-control" placeholder="Inserir chave/token..." required />
          </div>

          <div class="flex gap-3 mt-6" style="justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" @click="showConfigModal = false">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar Credenciais</button>
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
const activeApi = ref(null)

const paymentApis = computed(() => props.apis.filter(a => a.tipo === 'pagamento'))
const cepApis = computed(() => props.apis.filter(a => a.tipo === 'cep'))

const apiConfigForm = ref({
  ativo: false,
  sandbox: false,
  credenciais: {}
})

const activeApiCreds = computed(() => {
  if (!activeApi.value || !activeApi.value.credenciais_json) return {}
  return JSON.parse(activeApi.value.credenciais_json)
})

const shippingForm = ref({
  valor_minimo_gratis: props.freteRegra?.valor_minimo_gratis || 0,
  raio_km_local: props.freteRegra?.raio_km_local || 0,
  cep_origem: props.freteRegra?.cep_origem || '',
  lat_origem: props.freteRegra?.lat_origem || 0,
  lng_origem: props.freteRegra?.lng_origem || 0,
})

function editApi(api) {
  activeApi.value = api
  const creds = JSON.parse(api.credenciais_json || '{}')
  apiConfigForm.value = {
    ativo: !!api.ativo,
    sandbox: !!api.sandbox,
    credenciais: { ...creds }
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

function saveShippingRules() {
  router.put(route('admin.shipping.update', props.freteRegra.id), shippingForm.value)
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
