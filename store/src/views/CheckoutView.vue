<template>
  <div class="checkout-layout">
    <div class="checkout-main">
      <!-- 1. Endereço de Envio -->
      <div class="card p-4 mb-4">
        <h2>📍 Endereço de Entrega</h2>
        
        <div v-if="loadingAddresses" class="text-center py-4">Carregando seus endereços...</div>
        
        <div v-else-if="!addresses.length" class="no-address-box">
          <p class="text-secondary mb-4">Você ainda não tem nenhum endereço cadastrado.</p>
          <!-- Formulário Rápido de Cadastro de Endereço -->
          <div class="form-address-quick">
            <div class="flex gap-2 mb-4">
              <input v-model="newAddr.cep" type="text" class="store-input" placeholder="CEP" maxlength="9" />
              <button class="store-btn store-btn-secondary" @click="lookupNewCep">Autopreencher</button>
            </div>
            <input v-model="newAddr.logradouro" type="text" class="store-input mb-2" placeholder="Rua / Logradouro" />
            <div class="grid-2 mb-2">
              <input v-model="newAddr.numero" type="text" class="store-input" placeholder="Número" />
              <input v-model="newAddr.complemento" type="text" class="store-input" placeholder="Compl (Ap/Bloco)" />
            </div>
            <input v-model="newAddr.bairro" type="text" class="store-input mb-2" placeholder="Bairro" />
            <div class="grid-2 mb-4">
              <input v-model="newAddr.cidade" type="text" class="store-input" placeholder="Cidade" />
              <input v-model="newAddr.estado" type="text" class="store-input" placeholder="Estado (UF)" maxlength="2" />
            </div>
            <button class="store-btn store-btn-primary w-full" @click="saveNewAddress">Salvar Endereço</button>
          </div>
        </div>

        <div v-else class="addresses-list">
          <div 
            v-for="addr in addresses" 
            :key="addr.id" 
            class="address-option"
            :class="{ active: selectedAddressId === addr.id }"
            @click="selectedAddressId = addr.id"
          >
            <div class="addr-details">
              <strong>{{ addr.apelido || 'Endereço' }}</strong>
              <p class="text-secondary">{{ addr.logradouro }}, {{ addr.numero }} - {{ addr.bairro }}</p>
              <p class="text-muted">{{ addr.cidade }}/{{ addr.estado }} - {{ addr.cep }}</p>
            </div>
            <span class="radio-indicator"></span>
          </div>
        </div>
      </div>

      <!-- 2. Escolha de Gateway de Pagamento -->
      <div class="card p-4 mb-4">
        <h2>💳 Forma de Pagamento (PIX)</h2>
        <p class="text-secondary mb-4">O pagamento de Pix será processado sob o gateway de sua preferência:</p>
        
        <div class="gateways-grid">
          <div 
            class="gateway-option" 
            :class="{ active: selectedGateway === 'mercadopago' }"
            @click="selectedGateway = 'mercadopago'"
          >
            <span>Mercado Pago</span>
          </div>
          <div 
            class="gateway-option" 
            :class="{ active: selectedGateway === 'pagseguro' }"
            @click="selectedGateway = 'pagseguro'"
          >
            <span>PagSeguro / PagBank</span>
          </div>
          <div 
            class="gateway-option" 
            :class="{ active: selectedGateway === 'stripe' }"
            @click="selectedGateway = 'stripe'"
          >
            <span>Stripe</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Sidebar Resumo & Ações -->
    <div class="checkout-sidebar">
      <div class="card p-4">
        <h3>Resumo Final</h3>
        
        <div class="summary-row mt-4">
          <span class="text-secondary">Itens ({{ store.cart.length }})</span>
          <span>R$ {{ formatMoney(store.cartSubtotal) }}</span>
        </div>

        <div class="summary-row">
          <span class="text-secondary">Frete ({{ store.shippingQuote?.servico }})</span>
          <span>R$ {{ formatMoney(store.shippingQuote?.valor) }}</span>
        </div>

        <!-- Cupom de Desconto -->
        <div class="coupon-section mt-4 pt-4" style="border-top: 1px solid var(--border-color);">
          <div class="flex gap-2">
            <input v-model="couponCode" type="text" class="store-input" placeholder="CUPOM" />
            <button class="store-btn store-btn-secondary" @click="applyCoupon">Aplicar</button>
          </div>
          <p v-if="store.appliedCoupon" class="text-success mt-2">Cupom aplicado com sucesso!</p>
        </div>

        <div v-if="store.cartDiscount > 0" class="summary-row mt-2">
          <span class="text-success">Desconto</span>
          <span class="text-success">- R$ {{ formatMoney(store.cartDiscount) }}</span>
        </div>

        <div class="summary-row total-row mt-4 pt-4" style="border-top: 1px solid var(--border-color);">
          <span>Total Geral</span>
          <span class="total-price">R$ {{ formatMoney(store.cartTotal) }}</span>
        </div>

        <div v-if="errorCheckout" class="alert alert-danger mt-4">
          {{ errorCheckout }}
        </div>

        <button class="store-btn store-btn-primary w-full mt-6" :disabled="processing" @click="runCheckout">
          {{ processing ? 'Processando Pix...' : 'Finalizar e Ver QR Code' }}
        </button>
      </div>
    </div>

    <!-- Modal de Sucesso & QR Code Pix -->
    <div v-if="pixData" class="modal-overlay">
      <div class="modal modal-sm text-center" style="max-width: 460px;">
        <div class="modal-header">
          <h3>Pix Gerado com Sucesso!</h3>
        </div>
        <div class="modal-body">
          <p class="text-secondary mb-4">Escaneie o código abaixo com o aplicativo do seu banco para efetuar o pagamento.</p>
          
          <img :src="pixData.qr_code_base64" class="qr-code-img mb-4" alt="QR Code Pix" style="max-width: 200px; margin: 0 auto; display: block;" />
          
          <div class="form-group mb-4">
            <label class="form-label">Código Pix (Copia e Cola)</label>
            <input type="text" readonly :value="pixData.qr_code" class="store-input text-center" @click="$event.target.select()" />
          </div>

          <div class="alert alert-warning text-left">
            <strong>Estoque Reservado!</strong> Seu produto está garantido e separado no estoque por 15 minutos até a confirmação do pagamento.
          </div>
        </div>
        <div class="modal-footer">
          <button class="store-btn store-btn-primary w-full" @click="finishCheckout">
            Concluir e Ir para Home
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStore } from '@/store/main'
import axios from 'axios'

const store = useStore()
const router = useRouter()

const loadingAddresses = ref(true)
const addresses = ref([])
const selectedAddressId = ref(null)

const selectedGateway = ref('mercadopago')
const couponCode = ref('')

const processing = ref(false)
const errorCheckout = ref('')
const pixData = ref(null)

// Novo endereço form
const newAddr = ref({
  apelido: 'Casa',
  cep: '',
  logradouro: '',
  numero: '',
  complemento: '',
  bairro: '',
  cidade: '',
  estado: ''
})

onMounted(() => {
  fetchAddresses()
})

async function fetchAddresses() {
  loadingAddresses.value = true
  try {
    const res = await axios.get('/api/addresses', {
      headers: { Authorization: `Bearer ${store.token}` }
    })
    addresses.value = res.data.enderecos
    if (addresses.value.length) {
      selectedAddressId.value = addresses.value.find(a => a.is_principal)?.id || addresses.value[0].id
    }
  } catch (err) {
    console.error(err)
  } finally {
    loadingAddresses.value = false
  }
}

async function lookupNewCep() {
  if (newAddr.value.cep.length < 8) return
  try {
    const res = await axios.get(`/api/cep/${newAddr.value.cep}`)
    newAddr.value.logradouro = res.data.logradouro
    newAddr.value.bairro = res.data.bairro
    newAddr.value.cidade = res.data.cidade
    newAddr.value.estado = res.data.estado
  } catch (err) {
    alert('CEP não encontrado. Preencha manualmente.')
  }
}

async function saveNewAddress() {
  try {
    const res = await axios.post('/api/addresses', newAddr.value, {
      headers: { Authorization: `Bearer ${store.token}` }
    })
    addresses.value.push(res.data.endereco)
    selectedAddressId.value = res.data.endereco.id
  } catch (err) {
    alert('Erro ao salvar endereço.')
  }
}

function applyCoupon() {
  if (!couponCode.value) return
  // Simulador rápido de cupom aceito
  store.appliedCoupon = {
    codigo: couponCode.value,
    tipo: 'percent',
    valor: 10,
    valor_minimo_pedido: 50
  }
}

async function runCheckout() {
  if (!selectedAddressId.value) {
    errorCheckout.value = 'Por favor, selecione ou cadastre um endereço de entrega.'
    return
  }

  processing.value = true
  errorCheckout.value = ''
  
  const payload = {
    endereco_id: selectedAddressId.value,
    gateway: selectedGateway.value,
    cupom_codigo: store.appliedCoupon?.codigo || null,
    itens: store.cart.map(item => ({
      variacao_id: item.variacao.id,
      quantidade: item.quantidade
    })),
    frete_valor: store.shippingQuote?.valor || 0,
    frete_servico: store.shippingQuote?.servico || 'PAC'
  }

  try {
    const res = await axios.post('/api/checkout', payload, {
      headers: { Authorization: `Bearer ${store.token}` }
    })
    pixData.value = res.data
  } catch (err) {
    errorCheckout.value = err.response?.data?.message || 'Falha ao processar pagamento.'
  } finally {
    processing.value = false
  }
}

function finishCheckout() {
  store.clearCart()
  router.push('/')
}

function formatMoney(val) {
  return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.checkout-layout {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
  margin-top: 2rem;
}

@media (max-width: 1024px) {
  .checkout-layout {
    grid-template-columns: 1fr;
  }
}

.address-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border: 1px solid var(--border-color);
  background: var(--bg-input);
  border-radius: var(--radius-md);
  margin-bottom: 0.75rem;
  cursor: pointer;
}

.address-option.active {
  border-color: var(--brand-primary);
}

.gateways-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.gateway-option {
  background: var(--bg-input);
  border: 1px solid var(--border-color);
  padding: 1rem;
  text-align: center;
  border-radius: var(--radius-md);
  cursor: pointer;
  font-weight: 600;
  transition: var(--transition-smooth);
}

.gateway-option.active {
  border-color: var(--brand-primary);
  background: var(--brand-glow);
  color: var(--brand-light);
}

.qr-code-img {
  padding: 0.5rem;
  background: white;
  border-radius: var(--radius-md);
}

.text-center { text-align: center; }
.block { display: block; }
</style>
