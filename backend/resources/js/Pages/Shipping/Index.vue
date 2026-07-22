<template>
  <AdminLayout title="Frete e Entregas">
    <template #breadcrumb>
      <span class="text-muted">Configurações</span>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Frete e Entregas</span>
    </template>

    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">
          <span class="page-title-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
              <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
          </span>
          Frete e Entregas
        </h1>
        <p class="page-subtitle">Configure regras de frete grátis e entrega local para a sua loja.</p>
      </div>
    </div>

    <div class="grid-2" style="gap: 1.5rem; align-items: start;">
      <!-- Regras de Frete Local -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <span class="card-title-icon" style="background: var(--color-brand-surface); color: var(--color-brand);">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
              </svg>
            </span>
            Regras de Frete Local & Melhor Envio
          </h3>
        </div>
        <div class="card-body">
          <form @submit.prevent="saveShippingRules" class="flex flex-col gap-4">
            <div class="form-group">
              <label class="form-label">Frete Grátis a partir de (R$)</label>
              <input v-model.number="shippingForm.valor_minimo_gratis" type="number" step="0.01" class="form-input" />
              <span class="form-hint">Pedidos acima deste valor terão frete grátis automaticamente.</span>
            </div>
            <div class="form-group">
              <label class="form-label">Raio Máximo de Entrega Local (km)</label>
              <input v-model.number="shippingForm.raio_km_local" type="number" step="0.1" class="form-input" />
              <span class="form-hint">Para habilitar a opção "Frete Local (Motoboy)" para clientes próximos.</span>
            </div>
            <div class="form-group">
              <label class="form-label">CEP de Origem (Remetente)</label>
              <input v-model="shippingForm.cep_origem" type="text" class="form-input" maxlength="9" placeholder="08010-000" />
            </div>
            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">Logradouro (Rua/Av.)</label>
                <input v-model="shippingForm.logradouro_origem" type="text" class="form-input" placeholder="Ex: Av. Marechal Tito" />
              </div>
              <div class="form-group">
                <label class="form-label">Número</label>
                <input v-model="shippingForm.numero_origem" type="text" class="form-input" placeholder="Ex: 1000" />
              </div>
            </div>
            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">Bairro</label>
                <input v-model="shippingForm.bairro_origem" type="text" class="form-input" placeholder="Ex: São Miguel Paulista" />
              </div>
              <div class="form-group">
                <label class="form-label">Cidade / UF</label>
                <div class="flex gap-2">
                  <input v-model="shippingForm.cidade_origem" type="text" class="form-input" placeholder="São Paulo" />
                  <input v-model="shippingForm.estado_origem" type="text" class="form-input" style="width: 70px;" maxlength="2" placeholder="SP" />
                </div>
              </div>
            </div>
            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">CNPJ / CPF do Remetente</label>
                <input v-model="shippingForm.documento_origem" type="text" class="form-input" placeholder="00.000.000/0001-00" />
              </div>
              <div class="form-group">
                <label class="form-label">Telefone de Contato</label>
                <input v-model="shippingForm.telefone_origem" type="text" class="form-input" placeholder="(11) 99999-9999" />
              </div>
            </div>
            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">Latitude (Origem)</label>
                <input v-model.number="shippingForm.lat_origem" type="text" class="form-input" placeholder="-23.5505" />
              </div>
              <div class="form-group">
                <label class="form-label">Longitude (Origem)</label>
                <input v-model.number="shippingForm.lng_origem" type="text" class="form-input" placeholder="-46.6333" />
              </div>
            </div>
            <div class="flex">
              <button type="submit" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                  <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                </svg>
                Salvar Regras de Frete
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Coluna Direita -->
      <div class="flex flex-col gap-6">
        <!-- Modelos de Frete -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              <span class="card-title-icon" style="background: var(--color-warning-bg); color: var(--color-warning);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                </svg>
              </span>
              Modelos de Frete (Moto, Metrô, etc)
            </h3>
          </div>
          <div class="card-body flex flex-col gap-3">
            <div v-for="(servico, index) in servicosLocais" :key="index" class="flex gap-2">
              <input v-model="servico.nome" type="text" class="form-input" placeholder="Nome (Ex: Uber Moto)" />
              <input v-model="servico.api" type="text" class="form-input" placeholder="ID (Ex: uber_moto)" />
              <button class="btn btn-danger btn-sm" @click="removeServico(index)" title="Remover">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
            <button class="btn btn-secondary" @click="addServico" style="width: 100%; margin-top: 0.25rem;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
              </svg>
              Adicionar Modelo de Frete
            </button>
          </div>
        </div>

        <!-- APIs Ativas -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              <span class="card-title-icon" style="background: var(--color-success-bg); color: var(--color-success);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/>
                </svg>
              </span>
              APIs de Frete Ativas
            </h3>
          </div>
          <div class="card-body">
            <div v-if="apisFrete?.length > 0" class="flex flex-col gap-3">
              <div v-for="api in apisFrete" :key="api.id" class="flex justify-between items-center p-3 rounded-lg" style="background: var(--color-bg-elevated); border: 1px solid var(--color-border);">
                <div>
                  <div class="font-semibold" style="font-size: 0.875rem;">{{ api.nome }}</div>
                  <div class="text-muted font-mono" style="font-size: 0.75rem;">{{ api.slug }}</div>
                </div>
                <span class="badge" :class="api.ativo ? 'badge-success' : 'badge-danger'">
                  <span class="badge-dot"></span>{{ api.ativo ? 'Ativa' : 'Inativa' }}
                </span>
              </div>
            </div>
            <div v-else class="info-box" style="font-size: 0.875rem; color: var(--color-text-muted);">
              Nenhuma API de frete cadastrada. Acesse <strong>APIs</strong> para configurar.
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
  apisFrete:  { type: Array,  default: () => [] }
})

let initialServicos = []
if (props.freteRegra?.servicos_locais_json) {
  try { initialServicos = JSON.parse(props.freteRegra.servicos_locais_json) } catch {}
}

const servicosLocais = ref(initialServicos)
const shippingForm   = ref({
  valor_minimo_gratis: props.freteRegra?.valor_minimo_gratis || 0,
  raio_km_local:       props.freteRegra?.raio_km_local       || 0,
  cep_origem:          props.freteRegra?.cep_origem           || '',
  logradouro_origem:   props.freteRegra?.logradouro_origem   || 'Rua Marechal Tito',
  numero_origem:       props.freteRegra?.numero_origem       || '1000',
  complemento_origem:  props.freteRegra?.complemento_origem  || '',
  bairro_origem:       props.freteRegra?.bairro_origem       || 'São Miguel Paulista',
  cidade_origem:       props.freteRegra?.cidade_origem       || 'São Paulo',
  estado_origem:       props.freteRegra?.estado_origem       || 'SP',
  documento_origem:    props.freteRegra?.documento_origem    || '',
  telefone_origem:     props.freteRegra?.telefone_origem     || '',
  email_origem:        props.freteRegra?.email_origem        || '',
  lat_origem:          props.freteRegra?.lat_origem           || 0,
  lng_origem:          props.freteRegra?.lng_origem           || 0,
})

function addServico() { servicosLocais.value.push({ nome: '', api: '' }) }
function removeServico(index) { servicosLocais.value.splice(index, 1) }

function saveShippingRules() {
  if (!props.freteRegra) { alert('Erro: Regra base não encontrada.'); return }
  const payload = {
    ...shippingForm.value,
    servicos_locais_json: JSON.stringify(servicosLocais.value.filter(s => s.nome.trim() !== ''))
  }
  router.put(route('admin.shipping.update', props.freteRegra.id), payload)
}
</script>
