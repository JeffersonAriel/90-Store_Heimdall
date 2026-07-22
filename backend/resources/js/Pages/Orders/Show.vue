<template>
  <AdminLayout title="Detalhes do Pedido">
    <template #breadcrumb>
      <span><Link :href="route('admin.orders.index')" class="text-brand">Pedidos</Link> / Detalhes #{{ order.id }}</span>
    </template>

    <div class="page-header mb-6">
      <div>
        <h1 class="page-title">📋 Pedido #{{ order.id }}</h1>
        <p class="text-secondary mt-1">Status Atual: 
          <span class="badge" :class="getStatusBadgeClass(order.status)">{{ getStatusLabel(order.status) }}</span>
        </p>
      </div>
      <div class="flex gap-2">
        <a v-if="whatsapp_link !== '#'" :href="whatsapp_link" target="_blank" class="btn btn-primary">
          💬 Enviar WhatsApp Manual
        </a>
      </div>
    </div>

    <!-- Alerta: Custo dos Produtos Pendente (Dropshipping) -->
    <div v-if="hasUnsetCosts" class="alert alert-warning" style="background-color: rgba(245, 158, 11, 0.12); border: 1px solid #f59e0b; color: #f59e0b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
      <div>
        <strong>⚠️ Custo dos Produtos (Dropshipping) Pendente</strong>
        <div style="font-size: 0.875rem; margin-top: 0.25rem;">
          Este pedido possui itens de estoque dropshipping sem valor de custo cadastrado. Defina o custo para gerar os repasses financeiros ao fornecedor e calcular o lucro líquido real.
        </div>
      </div>
      <button @click="openCostsModal" class="btn btn-warning" style="background-color: #f59e0b; border-color: #f59e0b; color: white; white-space: nowrap;">
        ✏️ Informar Custo dos Produtos
      </button>
    </div>

    <!-- Layout Dividido em Colunas -->
    <div class="grid-3 gap-6">
      <!-- Coluna Principal (Itens, Valores e Historico) -->
      <div class="col-span-2 flex flex-col gap-6">
        <!-- Itens do Pedido -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">📦 Itens Solicitados</h3>
          </div>
          <div class="card-body" style="padding:0;">
            <div class="table-wrapper">
              <table>
                <thead>
                  <tr>
                    <th>Produto / Variação</th>
                    <th>SKU</th>
                    <th>Tipo</th>
                    <th>Qtd</th>
                    <th>Unitário</th>
                    <th>Custo Unit.</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in order.itens" :key="item.id">
                    <td>
                      <strong>{{ item.produto ? item.produto.nome : 'Produto Removido' }}</strong>
                      <div class="text-secondary" style="font-size:0.75rem;">Snapshot: {{ item.nome_produto_snapshot }}</div>
                    </td>
                    <td class="font-mono text-secondary">{{ item.sku_snapshot }}</td>
                    <td>
                      <span class="badge badge-secondary">{{ item.tipo_estoque_snapshot }}</span>
                    </td>
                    <td>{{ item.quantidade }}</td>
                    <td>R$ {{ formatMoney(item.preco_venda_snapshot) }}</td>
                    <td class="text-secondary">R$ {{ formatMoney(item.preco_custo_snapshot) }}</td>
                    <td class="font-bold">R$ {{ formatMoney(item.preco_venda_snapshot * item.quantidade) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Histórico de Status e Sequência Obrigatória -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">🔄 Linha do Tempo e Ações do Pedido</h3>
          </div>
          <div class="card-body">
            <!-- Botões de Ação Dinâmicos por Sequência -->
            <div class="flex gap-3 mb-6 flex-wrap items-center" style="background: var(--color-bg-elevated); padding: 1rem; border-radius: var(--radius-md);">
              <span class="font-bold mr-2">Ações de Status:</span>

              <!-- Confirmação manual de pagamento -->
              <button v-if="order.status === 'aguardando_pagamento'" @click="openConfirmPaymentModal" class="btn btn-primary">
                💰 Confirmar Pagamento Pix Manual
              </button>

              <!-- Outros status (avanço obrigatório) -->
              <button v-if="order.status === 'em_separacao'" @click="advanceStatus('em_envio')" class="btn btn-secondary">
                🚚 Colocar em Rota de Envio
              </button>
              <button v-if="order.status === 'em_envio'" @click="openTrackingModal" class="btn btn-secondary">
                📬 Informar Rastreio e Postar
              </button>
              <button v-if="order.status === 'enviado'" @click="advanceStatus('entregue')" class="btn btn-success">
                ✅ Marcar como Entregue
              </button>

              <!-- Integração SuperFrete: Emitir e Imprimir Etiqueta PDF Oficial -->
              <button v-if="order.status === 'em_separacao' || order.status === 'em_envio'" @click="generateSuperFreteLabel" class="btn btn-primary" style="background-color: #10b981; border-color: #10b981;">
                🏷️ Gerar Etiqueta SuperFrete
              </button>
              <a v-if="order.codigo_rastreio" :href="route('admin.orders.print-label', order.id)" target="_blank" class="btn btn-secondary" style="background-color: #4b5563; border-color: #4b5563;" title="Baixar e imprimir etiqueta de envio">
                🖨️ Imprimir Etiqueta Oficial SuperFrete (PDF)
              </a>

              <!-- Exceções aplicáveis a qualquer momento -->
              <button v-if="order.status !== 'cancelado' && order.status !== 'entregue'" @click="advanceStatus('cancelado')" class="btn btn-danger">
                🚫 Cancelar Pedido
              </button>
            </div>

            <!-- Lista de Histórico -->
            <div class="flex flex-col gap-4">
              <div v-for="log in order.historico_status" :key="log.id" class="p-3" style="background: var(--color-bg-elevated); border-radius: var(--radius-md); border-left: 4px solid var(--color-brand);">
                <div class="flex justify-between items-center">
                  <strong class="text-brand">{{ getStatusLabel(log.status_novo) }}</strong>
                  <span class="text-secondary" style="font-size:0.75rem;">{{ formatDate(log.created_at) }}</span>
                </div>
                <div class="text-secondary mt-1" style="font-size: 0.875rem;">{{ log.observacao || 'Nenhuma observação informada.' }}</div>
                <div v-if="log.funcionario" class="text-muted mt-1" style="font-size: 0.75rem;">Registrado por: <strong>{{ log.funcionario.nome }}</strong></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Coluna Lateral (Cliente e Financeiro) -->
      <div class="flex flex-col gap-6">
        <!-- Cliente -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">👤 Informações do Cliente</h3>
          </div>
          <div class="card-body">
            <div v-if="order.cliente">
              <h4 class="font-bold" style="font-size: 1.125rem;">{{ order.cliente.nome_completo }}</h4>
              <div class="text-secondary mt-2" style="font-size: 0.875rem;">
                <strong>CPF:</strong> {{ order.cliente.cpf }} <br/>
                <strong>E-mail:</strong> {{ order.cliente.email }} <br/>
                <strong>Telefone:</strong> {{ order.cliente.telefone }} <br/>
                <strong>WhatsApp:</strong> {{ order.cliente.whatsapp || 'Não informado' }}
              </div>
            </div>
            <div v-else class="text-danger">Dados do cliente indisponíveis.</div>
          </div>
        </div>

        <!-- Endereço de Entrega -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">📍 Endereço de Entrega</h3>
          </div>
          <div class="card-body">
            <div v-if="order.endereco" style="font-size: 0.875rem; line-height: 1.6;" class="text-secondary">
              <strong>CEP:</strong> {{ order.endereco.cep }} <br/>
              {{ order.endereco.logradouro }}, {{ order.endereco.numero }} {{ order.endereco.complemento || '' }} <br/>
              {{ order.endereco.bairro }}, {{ order.endereco.cidade }} - {{ order.endereco.estado }} <br/>
              <strong>Referência:</strong> {{ order.endereco.ponto_referencia || '—' }}
            </div>
            <div v-else class="text-secondary" style="font-size: 0.875rem;">Endereço direto do cadastro principal.</div>
          </div>
        </div>

        <!-- Detalhes do Frete Escolhido -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">🚚 Detalhes do Frete Escolhido</h3>
          </div>
          <div class="card-body" style="font-size: 0.875rem; line-height: 1.6;">
            <div class="flex flex-col gap-2 text-secondary">
              <div>
                <strong>Serviço Selecionado:</strong>
                <span class="badge badge-primary ml-2" style="background-color: var(--color-brand); color: white;">{{ order.servico_frete_nome || 'Envio Padrão (SuperFrete)' }}</span>
              </div>
              <div>
                <strong>Prazo Estimado:</strong> {{ order.prazo_frete_dias ? order.prazo_frete_dias + ' dias úteis' : 'Conforme transportadora' }}
              </div>
              <div>
                <strong>Valor do Frete Pago:</strong> R$ {{ formatMoney(order.valor_frete) }}
              </div>
              <div style="border-top: 1px solid var(--color-border); padding-top: 0.5rem; margin-top: 0.25rem;">
                <strong>Origem (Remetente):</strong> 08230-600 — Rua Nicolau Campanella, 25 (São Paulo / SP)
              </div>
              <div>
                <strong>Destino (Cliente):</strong> {{ order.endereco?.cep }} — {{ order.endereco?.logradouro }}, {{ order.endereco?.numero }} ({{ order.endereco?.cidade }} / {{ order.endereco?.estado }})
              </div>
              <div v-if="order.codigo_rastreio" style="border-top: 1px solid var(--color-border); padding-top: 0.5rem; margin-top: 0.25rem;">
                <strong>Código de Rastreio Oficial:</strong> <code class="font-mono text-brand">{{ order.codigo_rastreio }}</code>
              </div>
            </div>
          </div>
        </div>

        <!-- Comprovante de Pagamento (InfinitePay / Gateway) -->
        <div v-if="order.url_comprovante_pagamento || (order.observacoes && order.observacoes.includes('InfinitePay'))" class="card" style="border-color: #10b981; background: rgba(16, 185, 129, 0.05);">
          <div class="card-header flex justify-between items-center">
            <h3 class="card-title" style="color: #10b981;">💳 Comprovante de Pagamento</h3>
            <a v-if="order.url_comprovante_pagamento" :href="order.url_comprovante_pagamento" target="_blank" class="btn btn-sm btn-success" style="background-color: #10b981; border-color: #10b981; color: white;">
              📄 Abrir Comprovante InfinitePay ↗
            </a>
          </div>
          <div class="card-body" style="font-size: 0.875rem; line-height: 1.6;">
            <div v-if="order.url_comprovante_pagamento" class="text-secondary mb-2">
              <strong>URL do Comprovante:</strong> <br/>
              <a :href="order.url_comprovante_pagamento" target="_blank" class="text-brand underline font-mono" style="word-break: break-all;">{{ order.url_comprovante_pagamento }}</a>
            </div>
            <div v-if="order.observacoes" class="text-muted" style="font-size: 0.75rem; border-top: 1px dashed rgba(16, 185, 129, 0.3); padding-top: 0.5rem;">
              {{ order.observacoes }}
            </div>
          </div>
        </div>

        <!-- Alerta: Frete a Combinar -->
        <div v-if="freteACombinar" class="card" style="border-color: #f59e0b; background: rgba(245,158,11,0.08);">
          <div class="card-header">
            <h3 class="card-title" style="color: #f59e0b;">⚠️ Frete Local - A Combinar</h3>
          </div>
          <div class="card-body">
            <p class="text-secondary" style="font-size: 0.875rem; margin-bottom: 1rem;">Este pedido optou por entrega local (Uber Moto / Metrô). O valor do frete está como <strong>R$ 0,00</strong>. Após combinar com o cliente, preencha o valor abaixo para atualizar o faturamento.</p>
            <form @submit.prevent="submitFreteACombinar" class="flex gap-3 items-end">
              <div class="form-group" style="flex: 1;">
                <label class="form-label">Valor do Frete (R$)</label>
                <input v-model.number="freteForm.valor" type="number" step="0.01" min="0" class="form-control" placeholder="Ex: 18.50" required />
              </div>
              <button type="submit" class="btn btn-primary">Confirmar Frete</button>
            </form>
          </div>
        </div>

        <!-- DRE/Financeiro do Pedido -->
        <div class="card" style="background: var(--color-bg-secondary); border-color: var(--color-brand);">
          <div class="card-header">
            <h3 class="card-title">📊 Resumo Financeiro (Snapshot)</h3>
          </div>
          <div class="card-body" style="font-size:0.875rem;">
            <div class="flex justify-between p-2">
              <span>Valor dos Itens:</span>
              <span>R$ {{ formatMoney(order.total - order.valor_frete) }}</span>
            </div>
            <div class="flex justify-between p-2">
              <span>Valor do Frete:</span>
              <span>R$ {{ formatMoney(order.valor_frete) }}</span>
            </div>
            <hr style="border-color: var(--color-border);" />
            <div class="flex justify-between p-2 font-bold" style="font-size: 1rem;">
              <span>Total Pago:</span>
              <span>R$ {{ formatMoney(order.total) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Rastreio e Postagem -->
    <div v-if="showTrackingModal" class="modal-backdrop" @click.self="showTrackingModal = false">
      <div class="modal-box">
        <h2 class="modal-title">Código de Rastreamento (Postagem)</h2>
        <form @submit.prevent="submitTracking">
          <div class="form-group">
            <label class="form-label">Código de Rastreio (Correios/Jadlog/Melhor Envio)</label>
            <input v-model="trackingForm.codigo_rastreio" type="text" class="form-control" placeholder="Ex: QB123456789BR" required />
          </div>
          <div class="form-group">
            <label class="form-label">URL de Rastreamento</label>
            <input v-model="trackingForm.url_rastreio" type="text" class="form-control" placeholder="Ex: https://melhorenvio.com.br/rastreio/..." required />
          </div>
          <div class="flex gap-3 mt-6" style="justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" @click="showTrackingModal = false">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar e Postar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Confirmação de Pagamento manual -->
    <div v-if="showPaymentModal" class="modal-backdrop" @click.self="showPaymentModal = false">
      <div class="modal-box" style="max-width: 500px; width: 90%;">
        <h2 class="modal-title">Confirmar Recebimento de Pagamento Pix</h2>
        <form @submit.prevent="submitPaymentConfirmation">
          
          <!-- Custos de Dropshipping se houver -->
          <div v-if="dropshippingItens.length > 0" class="mb-4 p-4" style="background: rgba(var(--color-brand-rgb), 0.05); border-radius: var(--radius-md); border: 1px solid var(--color-border);">
            <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--color-text-primary);">
              ⚠️ Preço de Custo (Dropshipping)
            </h4>
            <p class="text-secondary mb-3" style="font-size: 0.75rem;">
              Este pedido possui itens de dropshipping. Informe o preço de custo pago a cada fornecedor.
            </p>
            <div v-for="item in dropshippingItens" :key="item.id" class="form-group mb-3">
              <label class="form-label" style="font-size: 0.8125rem; display: block; margin-bottom: 0.25rem;">
                {{ item.nome_snapshot }} ({{ item.sku_snapshot }}) - Qtd: {{ item.quantidade }}
              </label>
              <div class="flex gap-2 items-center">
                <span style="font-size: 0.875rem; color: var(--color-text-secondary);">R$</span>
                <input type="number" v-model="paymentForm.custos[item.id]" step="0.01" min="0.01" class="form-control" placeholder="0,00" required />
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Informações / Comprovante (Obrigatório)</label>
            <textarea v-model="paymentForm.observacao" class="form-control" rows="3" placeholder="Ex: Pix confirmado na conta PJ Banco do Brasil às 14:32..." required></textarea>
          </div>
          <div class="flex gap-3 mt-6" style="justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" @click="showPaymentModal = false">Cancelar</button>
            <button type="submit" class="btn btn-primary">Confirmar e Liberar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Atualizar Custos Dropshipping -->
    <div v-if="showCostsModal" class="modal-backdrop" @click.self="showCostsModal = false">
      <div class="modal-box" style="max-width: 520px; width: 90%;">
        <h2 class="modal-title">✏️ Informar Custo dos Produtos (Dropshipping)</h2>
        <p class="text-secondary mb-4" style="font-size: 0.875rem;">
          Preencha o valor de custo unitário pago ao fornecedor para cada item. O sistema atualizará os snapshots e lançará os repasses no caixa financeiro.
        </p>
        <form @submit.prevent="submitCosts">
          <div v-for="item in order.itens" :key="item.id" class="form-group mb-4 p-3" style="background: var(--color-bg-elevated); border-radius: var(--radius-md); border: 1px solid var(--color-border);">
            <label class="form-label font-bold" style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem;">
              {{ item.nome_snapshot || (item.produto ? item.produto.nome : 'Produto') }}
            </label>
            <div class="text-secondary mb-2" style="font-size: 0.75rem;">
              SKU: {{ item.sku_snapshot }} | Qtd: {{ item.quantidade }} | Venda Unit.: R$ {{ formatMoney(item.preco_venda_snapshot) }}
            </div>
            <div class="flex items-center gap-2">
              <span class="text-secondary">Custo Unit.: R$</span>
              <input v-model.number="costsFormMap[item.id]" type="number" step="0.01" min="0" class="form-control" placeholder="0.00" required />
            </div>
          </div>
          <div class="flex gap-3 mt-6 justify-end">
            <button type="button" @click="showCostsModal = false" class="btn btn-secondary">Cancelar</button>
            <button type="submit" class="btn btn-primary" style="background-color: var(--color-brand); border-color: var(--color-brand);">
              💾 Salvar Custos e Lançar Financeiro
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  order: { type: Object, required: true },
  whatsapp_link: { type: String, default: '#' }
})

const showTrackingModal = ref(false)
const showPaymentModal = ref(false)
const showFreteModal = ref(false)
const showCostsModal = ref(false)
const costsFormMap = ref({})

// Detecta se existem itens dropshipping com custo não cadastrado (ou custo == 0)
const hasUnsetCosts = computed(() => {
  return (props.order.itens || []).some(item => {
    return item.tipo_estoque_snapshot === 'dropshipping' && (!item.preco_custo_snapshot || parseFloat(item.preco_custo_snapshot) === 0)
  })
})

function openCostsModal() {
  costsFormMap.value = {}
  ;(props.order.itens || []).forEach(item => {
    costsFormMap.value[item.id] = item.preco_custo_snapshot ? parseFloat(item.preco_custo_snapshot) : ''
  })
  showCostsModal.value = true
}

function submitCosts() {
  const custosArray = Object.keys(costsFormMap.value).map(itemId => ({
    item_id: parseInt(itemId),
    preco_custo: parseFloat(costsFormMap.value[itemId]) || 0
  }))

  router.post(route('admin.orders.update-item-costs', props.order.id), { custos: custosArray }, {
    onSuccess: () => {
      showCostsModal.value = false
    }
  })
}

// Filtra itens de dropshipping do pedido
const dropshippingItens = computed(() => {
  return (props.order.itens || []).filter(item => item.tipo_estoque_snapshot === 'dropshipping')
})

// Detecta se este pedido tem frete a combinar (valor 0 + observação marcada + status ainda não confirmado)
const freteACombinar = computed(() => {
  return props.order.valor_frete == 0
    && props.order.observacoes
    && props.order.observacoes.includes('FRETE A COMBINAR')
})

const freteForm = ref({ valor: 0 })

const trackingForm = ref({
  codigo_rastreio: '',
  url_rastreio: ''
})

const paymentForm = ref({
  observacao: '',
  custos: {}
})

function submitFreteACombinar() {
  if (!confirm(`Confirmar valor do frete em R$ ${Number(freteForm.value.valor).toFixed(2).replace('.', ',')}?`)) return
  router.patch(route('admin.orders.update-frete', props.order.id), {
    valor_frete: freteForm.value.valor
  })
}

function advanceStatus(statusNovo) {
  if (confirm(`Deseja alterar o status do pedido para ${getStatusLabel(statusNovo)}?`)) {
    router.post(route('admin.orders.advance', props.order.id), {
      status_novo: statusNovo,
      observacao: `Avanço manual pelo atendente/funcionário para: ${getStatusLabel(statusNovo)}`
    })
  }
}

function openConfirmPaymentModal() {
  paymentForm.value.observacao = ''
  paymentForm.value.custos = {}
  dropshippingItens.value.forEach(item => {
    paymentForm.value.custos[item.id] = item.preco_custo_snapshot ? parseFloat(item.preco_custo_snapshot) : ''
  })
  showPaymentModal.value = true
}

function submitPaymentConfirmation() {
  router.post(route('admin.orders.confirm-payment', props.order.id), paymentForm.value, {
    onSuccess: () => {
      showPaymentModal.value = false
    }
  })
}

function openTrackingModal() {
  trackingForm.value = { codigo_rastreio: '', url_rastreio: '' }
  showTrackingModal.value = true
}

function submitTracking() {
  router.post(route('admin.orders.advance', props.order.id), {
    status_novo: 'enviado',
    observacao: `Código de rastreio ${trackingForm.value.codigo_rastreio} cadastrado pelo atendente.`,
    codigo_rastreio: trackingForm.value.codigo_rastreio,
    url_rastreio: trackingForm.value.url_rastreio
  }, {
    onSuccess: () => {
      showTrackingModal.value = false
    }
  })
}

function generateSuperFreteLabel() {
  // Se a etiqueta já foi emitida para este pedido
  if (props.order.codigo_rastreio) {
    const confirmReprint = confirm(
      `Já foi gerada uma etiqueta de entrega para este pedido!\n\n` +
      `Código de Rastreio: ${props.order.codigo_rastreio}\n\n` +
      `Deseja imprimir novamente a etiqueta oficial da SuperFrete?`
    )
    if (confirmReprint) {
      window.open(route('admin.orders.print-label', props.order.id), '_blank')
    }
    return
  }

  // Se ainda não possui etiqueta emitida
  if (confirm('Deseja emitir e comprar a etiqueta deste pedido na SuperFrete?')) {
    router.post(route('admin.orders.generate-label', props.order.id), {}, {
      onSuccess: (page) => {
        window.open(route('admin.orders.print-label', props.order.id), '_blank')
      }
    })
  }
}

function formatMoney(value) {
  if (value === null || value === undefined) return '0,00'
  return parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleString('pt-BR')
}

function getStatusLabel(status) {
  const map = {
    'aguardando_pagamento': 'Aguardando Pagamento',
    'em_separacao': 'Em Separação',
    'em_envio': 'Em Envio',
    'enviado': 'Enviado',
    'entregue': 'Entregue',
    'cancelado': 'Cancelado',
    'devolvido': 'Devolvido'
  }
  return map[status] || status
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'aguardando_pagamento': return 'badge-warning'
    case 'em_separacao': return 'badge-secondary'
    case 'em_envio': return 'badge-secondary'
    case 'enviado': return 'badge-primary'
    case 'entregue': return 'badge-success'
    case 'cancelado': return 'badge-danger'
    default: return 'badge-secondary'
  }
}
</script>

<style scoped>
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
