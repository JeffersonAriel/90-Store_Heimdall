<template>
  <AdminLayout title="Novo Pedido Manual">
    <template #breadcrumb>
      <span><Link :href="route('admin.orders.index')" class="text-indigo-600 hover:underline">Pedidos</Link> / Novo Pedido</span>
    </template>

    <div class="card max-w-4xl mx-auto mb-10">
      <div class="card-body">
        <h2 class="title-md mb-6">🛒 Criar Pedido Manualmente</h2>

        <form @submit.prevent="submitForm">
          
          <!-- 1. IDENTIFICAÇÃO DO CLIENTE -->
          <div class="card mb-6" style="border: 1px solid var(--color-border); background: var(--color-bg-elevated);">
            <div class="card-header border-b border-dashed" style="border-color: var(--color-border);">
              <h4 class="m-0 font-bold flex items-center gap-2">
                <span class="bg-indigo-600 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm">1</span>
                Dados do Cliente
              </h4>
            </div>
            <div class="card-body">
              <div class="mb-4">
                <label class="flex items-center font-bold cursor-pointer">
                  <input type="checkbox" v-model="isNewClient" class="w-5 h-5 rounded mr-2" style="accent-color: var(--color-brand)" />
                  Cadastrar Novo Cliente
                </label>
              </div>

              <!-- Selecionar Cliente Existente -->
              <div v-if="!isNewClient" class="form-group">
                <label class="form-label">Selecionar Cliente Cadastrado *</label>
                <select v-model="form.cliente_id" class="form-select text-lg" :required="!isNewClient">
                  <option value="" disabled>Selecione um cliente...</option>
                  <option v-for="c in clients" :key="c.id" :value="c.id">
                    {{ c.nome_completo }} — CPF: {{ c.cpf }} ({{ c.email }})
                  </option>
                </select>
              </div>

              <!-- Cadastrar Novo Cliente -->
              <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                  <label class="form-label">Nome Completo *</label>
                  <input v-model="form.novo_cliente.nome_completo" type="text" class="form-input" placeholder="Ex: João da Silva" :required="isNewClient" />
                </div>
                <div class="form-group">
                  <label class="form-label">CPF *</label>
                  <input v-model="form.novo_cliente.cpf" type="text" class="form-input" placeholder="000.000.000-00" :required="isNewClient" />
                </div>
                <div class="form-group">
                  <label class="form-label">E-mail *</label>
                  <input v-model="form.novo_cliente.email" type="email" class="form-input" placeholder="joao@email.com" :required="isNewClient" />
                </div>
                <div class="form-group">
                  <label class="form-label">Telefone / WhatsApp</label>
                  <input v-model="form.novo_cliente.whatsapp" type="text" class="form-input" placeholder="(11) 99999-9999" />
                </div>
              </div>
            </div>
          </div>

          <!-- 2. ENDEREÇO DE ENTREGA -->
          <div class="card mb-6" style="border: 1px solid var(--color-border); background: var(--color-bg-elevated);">
            <div class="card-header border-b border-dashed" style="border-color: var(--color-border);">
              <h4 class="m-0 font-bold flex items-center gap-2">
                <span class="bg-indigo-600 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm">2</span>
                Endereço de Entrega
              </h4>
            </div>
            <div class="card-body">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-group">
                  <label class="form-label">CEP *</label>
                  <div class="flex">
                    <input v-model="form.endereco.cep" type="text" class="form-input" placeholder="00000-000" required @blur="searchCep" />
                    <button type="button" @click="searchCep" class="btn btn-secondary ml-2" :disabled="searchingCep">
                      {{ searchingCep ? '...' : '🔍' }}
                    </button>
                  </div>
                </div>
                <div class="form-group md:col-span-2">
                  <label class="form-label">Logradouro *</label>
                  <input v-model="form.endereco.logradouro" type="text" class="form-input" placeholder="Rua, Avenida..." required />
                </div>
                <div class="form-group">
                  <label class="form-label">Número *</label>
                  <input v-model="form.endereco.numero" type="text" class="form-input" placeholder="123" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Complemento</label>
                  <input v-model="form.endereco.complemento" type="text" class="form-input" placeholder="Apto, Bloco..." />
                </div>
                <div class="form-group">
                  <label class="form-label">Bairro *</label>
                  <input v-model="form.endereco.bairro" type="text" class="form-input" placeholder="Bairro" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Cidade *</label>
                  <input v-model="form.endereco.cidade" type="text" class="form-input" placeholder="Cidade" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Estado (UF) *</label>
                  <input v-model="form.endereco.estado" type="text" class="form-input" placeholder="SP" maxlength="2" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Apelido Endereço</label>
                  <input v-model="form.endereco.apelido" type="text" class="form-input" placeholder="Casa, Trabalho..." />
                </div>
              </div>
            </div>
          </div>

          <!-- 3. SELEÇÃO DE PRODUTOS -->
          <div class="card mb-6" style="border: 1px solid var(--color-border); background: var(--color-bg-elevated);">
            <div class="card-header border-b border-dashed" style="border-color: var(--color-border);">
              <h4 class="m-0 font-bold flex items-center gap-2">
                <span class="bg-indigo-600 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm">3</span>
                Produtos e Itens
              </h4>
            </div>
            <div class="card-body">
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-6 pb-6 border-b" style="border-color: var(--color-border);">
                
                <!-- Produto -->
                <div class="form-group md:col-span-2 mb-0">
                  <label class="form-label">Produto *</label>
                  <select v-model="selectedProductIndex" class="form-select" @change="onProductSelect">
                    <option value="" disabled>Selecione um produto...</option>
                    <option v-for="(p, idx) in products" :key="p.id" :value="idx">
                      {{ p.nome }} — R$ {{ formatMoney(p.preco_venda) }}
                    </option>
                  </select>
                </div>

                <!-- Variação (Tamanho/Cor) -->
                <div class="form-group mb-0">
                  <label class="form-label">Variação (Tam/Cor) *</label>
                  <select v-model="selectedVariationIndex" class="form-select" :disabled="!selectedProduct">
                    <option value="" disabled>Selecione a grade...</option>
                    <option v-for="(v, idx) in selectedProductVariations" :key="v.id" :value="idx">
                      Tam: {{ v.tamanho || '—' }} | Cor: {{ v.cor || '—' }} (Estoque: {{ v.estoque_quantidade }})
                    </option>
                  </select>
                </div>

                <!-- Adicionar -->
                <button type="button" @click="addItemToCart" class="btn btn-secondary w-full" style="height: 38px;">
                  + Adicionar Item
                </button>
              </div>

              <!-- Listagem do Carrinho Manual -->
              <div v-if="form.itens.length === 0" class="text-center py-6 text-gray-500">
                Nenhum produto adicionado ao pedido ainda.
              </div>
              <div v-else class="table-wrapper">
                <table class="w-full text-left">
                  <thead>
                    <tr class="border-b" style="border-color: var(--color-border);">
                      <th>Produto</th>
                      <th>Variação</th>
                      <th class="text-center">Quantidade</th>
                      <th class="text-right">Preço Unitário</th>
                      <th class="text-right">Subtotal</th>
                      <th class="text-center">Ações</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, idx) in form.itens" :key="idx" class="border-b" style="border-color: var(--color-border);">
                      <td><strong>{{ item.productName }}</strong></td>
                      <td>Tam: {{ item.size || '—' }} | Cor: {{ item.color || '—' }}</td>
                      <td class="text-center">
                        <input type="number" v-model.number="item.quantidade" class="form-input text-center w-16 mx-auto" min="1" required />
                      </td>
                      <td class="text-right">
                        R$ <input type="number" step="0.01" v-model.number="item.preco_venda_snapshot" class="form-input text-right w-24 inline-block" required />
                      </td>
                      <td class="text-right font-bold">R$ {{ formatMoney(item.preco_venda_snapshot * item.quantidade) }}</td>
                      <td class="text-center">
                        <button type="button" @click="removeItemFromCart(idx)" class="text-red-500 hover:text-red-700 font-bold">🗑️</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- 4. TOTAL E PAGAMENTO -->
          <div class="card mb-6" style="border: 1px solid var(--color-border); background: var(--color-bg-elevated);">
            <div class="card-header border-b border-dashed" style="border-color: var(--color-border);">
              <h4 class="m-0 font-bold flex items-center gap-2">
                <span class="bg-indigo-600 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm">4</span>
                Totais e Lançamento
              </h4>
            </div>
            <div class="card-body">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="form-group">
                  <label class="form-label">Valor do Frete (R$) *</label>
                  <input v-model.number="form.valor_frete" type="number" step="0.01" class="form-input" min="0" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Método / Canal de Venda *</label>
                  <select v-model="form.gateway_pagamento" class="form-select" required>
                    <option value="pix_manual">Pix Manual (WhatsApp)</option>
                    <option value="dinheiro">Dinheiro</option>
                    <option value="cartao_presencial">Cartão de Crédito/Débito Presencial</option>
                    <option value="outro">Outro Canal/Manual</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Status Inicial do Pedido *</label>
                  <select v-model="form.status" class="form-select" disabled required>
                    <option value="aguardando_pagamento">Aguardando Pagamento</option>
                    <option value="em_separacao">Pago — Em Separação</option>
                    <option value="em_envio">Pago — Em Envio</option>
                    <option value="enviado">Pago — Enviado</option>
                    <option value="entregue">Pago — Entregue</option>
                  </select>
                </div>
              </div>

              <!-- Resumo -->
              <div class="flex justify-between items-center p-4 rounded-lg" style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--color-border);">
                <div>
                  <span class="text-secondary text-sm">Subtotal: R$ {{ formatMoney(cartSubtotal) }}</span>
                  <span class="text-secondary text-sm block">Frete: R$ {{ formatMoney(form.valor_frete) }}</span>
                </div>
                <div class="text-right">
                  <span class="text-secondary text-xs block">TOTAL DO PEDIDO:</span>
                  <strong class="text-2xl text-white">R$ {{ formatMoney(cartTotal) }}</strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Ações -->
          <div class="flex justify-end gap-3">
            <Link :href="route('admin.orders.index')" class="btn btn-secondary px-6">Cancelar</Link>
            <button type="submit" class="btn btn-primary px-8 shadow-md" :disabled="form.processing || form.itens.length === 0">
              Concluir Pedido Manual
            </button>
          </div>

        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'

const props = defineProps({
  clients: { type: Array, required: true },
  products: { type: Array, required: true }
})

const isNewClient = ref(false)
const searchingCep = ref(false)

const selectedProductIndex = ref('')
const selectedVariationIndex = ref('')

const selectedProduct = computed(() => {
  if (selectedProductIndex.value === '') return null
  return props.products[selectedProductIndex.value]
})

const selectedProductVariations = computed(() => {
  return selectedProduct.value ? selectedProduct.value.variacoes : []
})

function onProductSelect() {
  selectedVariationIndex.value = ''
}

const form = useForm({
  cliente_id: '',
  novo_cliente: {
    nome_completo: '',
    cpf: '',
    email: '',
    telefone: '',
    whatsapp: ''
  },
  endereco: {
    cep: '',
    logradouro: '',
    numero: '',
    complemento: '',
    bairro: '',
    cidade: '',
    estado: '',
    apelido: 'Principal'
  },
  itens: [],
  valor_frete: 0,
  gateway_pagamento: 'pix_manual',
  status: 'aguardando_pagamento'
})

const cartSubtotal = computed(() => {
  return form.itens.reduce((sum, item) => sum + (item.preco_venda_snapshot * item.quantidade), 0)
})

const cartTotal = computed(() => {
  return cartSubtotal.value + (form.valor_frete || 0)
})

function addItemToCart() {
  if (selectedProductIndex.value === '' || selectedVariationIndex.value === '') {
    alert('Selecione o produto e a variação.')
    return
  }

  const prod = selectedProduct.value
  const vr = selectedProductVariations.value[selectedVariationIndex.value]

  // Checa duplicados
  const exists = form.itens.find(item => item.variacao_id === vr.id)
  if (exists) {
    exists.quantidade += 1
  } else {
    form.itens.push({
      variacao_id: vr.id,
      productName: prod.nome,
      size: vr.tamanho,
      color: vr.cor,
      quantidade: 1,
      preco_venda_snapshot: parseFloat(prod.preco_desconto || prod.preco_venda)
    })
  }

  // Limpa seletor
  selectedProductIndex.value = ''
  selectedVariationIndex.value = ''
}

function removeItemFromCart(idx) {
  form.itens.splice(idx, 1)
}

async function searchCep() {
  const cep = form.endereco.cep ? form.endereco.cep.replace(/\D/g, '') : ''
  if (cep.length !== 8) return

  searchingCep.value = true
  try {
    const res = await axios.get(`/api/cep/${cep}`)
    if (res.data && res.data.success) {
      const data = res.data
      form.endereco.logradouro = data.logradouro || ''
      form.endereco.bairro = data.bairro || ''
      form.endereco.cidade = data.cidade || ''
      form.endereco.estado = data.estado || ''
    }
  } catch (e) {
    console.error('Erro ao buscar CEP:', e)
  } finally {
    searchingCep.value = false
  }
}

function submitForm() {
  form.post(route('admin.orders.store'))
}

function formatMoney(value) {
  if (value === null || value === undefined) return '0,00'
  return parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Quando mudar o checkbox de novo cliente, limpa o ID do cliente selecionado
watch(isNewClient, (newVal) => {
  if (newVal) {
    form.cliente_id = ''
  } else {
    form.novo_cliente = { nome_completo: '', cpf: '', email: '', telefone: '', whatsapp: '' }
  }
})

// Quando selecionar um cliente, carrega o endereço dele caso exista
watch(() => form.cliente_id, (newVal) => {
  if (newVal) {
    const selectedClient = props.clients.find(c => c.id === newVal)
    if (selectedClient && selectedClient.enderecos && selectedClient.enderecos.length > 0) {
      const addr = selectedClient.enderecos.find(e => e.is_principal) || selectedClient.enderecos[0]
      form.endereco.cep = addr.cep || ''
      form.endereco.logradouro = addr.logradouro || ''
      form.endereco.numero = addr.numero || ''
      form.endereco.complemento = addr.complemento || ''
      form.endereco.bairro = addr.bairro || ''
      form.endereco.cidade = addr.cidade || ''
      form.endereco.estado = addr.estado || ''
      form.endereco.apelido = addr.apelido || 'Principal'
    } else {
      // Reseta se não houver endereço cadastrado
      form.endereco = { cep: '', logradouro: '', numero: '', complemento: '', bairro: '', cidade: '', estado: '', apelido: 'Principal' }
    }
  } else {
    // Reseta se limpar o cliente
    form.endereco = { cep: '', logradouro: '', numero: '', complemento: '', bairro: '', cidade: '', estado: '', apelido: 'Principal' }
  }
})
</script>
