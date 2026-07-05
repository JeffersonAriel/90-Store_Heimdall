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

    <div class="card max-w-2xl">
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
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  freteRegra: { type: Object, default: null }
})

const shippingForm = ref({
  valor_minimo_gratis: props.freteRegra?.valor_minimo_gratis || 0,
  raio_km_local: props.freteRegra?.raio_km_local || 0,
  cep_origem: props.freteRegra?.cep_origem || '',
  lat_origem: props.freteRegra?.lat_origem || 0,
  lng_origem: props.freteRegra?.lng_origem || 0,
})

function saveShippingRules() {
  if (!props.freteRegra) {
    alert("Erro: Regra base não encontrada no banco de dados.")
    return
  }
  
  router.put(route('admin.shipping.update', props.freteRegra.id), shippingForm.value, {
    onSuccess: () => alert("Regras de frete atualizadas com sucesso!")
  })
}
</script>
