<template>
  <AdminLayout title="Frete e Entregas">
    <template #breadcrumb>
      <span>Configurações / Frete e Entregas</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">🚚 Frete e Entregas</h1>
        <p class="text-secondary mt-1">Configure regras de frete grátis e frete local para a sua loja.</p>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
      <!-- Coluna Esquerda: Regras Locais -->
      <div class="card">
        <div class="card-header border-b border-dark mb-4 pb-4">
          <h2 class="title-md">Regras de Frete Local & Melhor Envio</h2>
        </div>
        <div class="card-body">
        <form @submit.prevent="saveShippingRules">
          <div class="form-group mb-4">
            <label class="form-label">Frete Grátis a partir de (R$)</label>
            <input v-model.number="shippingForm.valor_minimo_gratis" type="number" step="0.01" class="form-control" />
            <small class="text-secondary mt-1 block">Pedidos acima deste valor terão frete grátis.</small>
          </div>

          <div class="form-group mb-4">
            <label class="form-label">Raio Máximo de Entrega Local (km)</label>
            <input v-model.number="shippingForm.raio_km_local" type="number" step="0.1" class="form-control" />
            <small class="text-secondary mt-1 block">Para permitir a opção "Frete Local (Motoboy)" se o cliente estiver perto.</small>
          </div>

          <div class="form-group mb-4">
            <label class="form-label">CEP de Origem (Remetente)</label>
            <input v-model="shippingForm.cep_origem" type="text" class="form-control" maxlength="9" />
          </div>

          <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="form-group">
              <label class="form-label">Lat (Origem)</label>
              <input v-model.number="shippingForm.lat_origem" type="text" class="form-control" />
            </div>
            <div class="form-group">
              <label class="form-label">Lng (Origem)</label>
              <input v-model.number="shippingForm.lng_origem" type="text" class="form-control" />
            </div>
          </div>

          <div class="flex gap-3 mt-6">
            <button type="submit" class="btn btn-primary" :disabled="shippingForm.processing">
              Salvar Regras de Frete
            </button>
          </div>
        </form>
      </div>
    </div>

      <!-- Coluna Direita: Modelos e APIs -->
      <div class="flex flex-col gap-6">
        
        <!-- Serviços Locais / Modelos de Frete -->
        <div class="card">
          <div class="card-header border-b border-dark mb-4 pb-4">
            <h2 class="title-md">Modelos de Frete (Moto, Metrô, etc)</h2>
          </div>
          <div class="card-body">
            <div v-for="(servico, index) in servicosLocais" :key="index" class="flex gap-2 mb-3">
              <input v-model="servico.nome" type="text" class="form-control" placeholder="Nome (Ex: Uber Moto)" />
              <input v-model="servico.api" type="text" class="form-control" placeholder="ID (Ex: uber_moto)" />
              <button class="btn btn-danger" @click="removeServico(index)">X</button>
            </div>
            <button class="btn btn-outline w-full mt-2" @click="addServico">+ Adicionar Modelo de Frete</button>
          </div>
        </div>

        <!-- APIs Ativas -->
        <div class="card">
          <div class="card-header border-b border-dark mb-4 pb-4">
            <h2 class="title-md">APIs de Frete Ativas</h2>
          </div>
          <div class="card-body">
            <div v-if="apisFrete && apisFrete.length > 0" class="flex flex-col gap-3">
              <div v-for="api in apisFrete" :key="api.id" class="flex justify-between items-center p-3 border border-dark rounded">
                <div>
                  <strong class="block">{{ api.nome }}</strong>
                  <span class="text-xs text-secondary">Slug: {{ api.slug }}</span>
                </div>
                <span class="badge" :class="api.ativo ? 'badge-success' : 'badge-danger'">
                  {{ api.ativo ? 'Ativa' : 'Inativa' }}
                </span>
              </div>
            </div>
            <div v-else class="text-secondary text-sm">
              Nenhuma API de frete encontrada. Vá em Integrações & APIs para cadastrar novas.
            </div>
          </div>
        </div>

      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  freteRegra: { type: Object, default: null },
  apisFrete: { type: Array, default: () => [] }
})

let initialServicos = []
if (props.freteRegra && props.freteRegra.servicos_locais_json) {
  try {
    initialServicos = JSON.parse(props.freteRegra.servicos_locais_json)
  } catch (e) {}
}

const servicosLocais = ref(initialServicos)

const shippingForm = ref({
  valor_minimo_gratis: props.freteRegra?.valor_minimo_gratis || 0,
  raio_km_local: props.freteRegra?.raio_km_local || 0,
  cep_origem: props.freteRegra?.cep_origem || '',
  lat_origem: props.freteRegra?.lat_origem || 0,
  lng_origem: props.freteRegra?.lng_origem || 0,
})

function addServico() {
  servicosLocais.value.push({ nome: '', api: '' })
}

function removeServico(index) {
  servicosLocais.value.splice(index, 1)
}

function saveShippingRules() {
  if (!props.freteRegra) {
    alert("Erro: Regra base não encontrada no banco de dados.")
    return
  }
  
  const payload = {
    ...shippingForm.value,
    servicos_locais_json: JSON.stringify(servicosLocais.value.filter(s => s.nome.trim() !== ''))
  }
  
  router.put(route('admin.shipping.update', props.freteRegra.id), payload, {
    onSuccess: () => alert("Regras de frete atualizadas com sucesso!")
  })
}
</script>
