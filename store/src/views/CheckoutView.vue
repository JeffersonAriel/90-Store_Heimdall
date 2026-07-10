<template>
  <div class="checkout-view container">
    <div class="checkout-header">
      <h1 class="title-lg">CHECKOUT SEGURO</h1>
      <p class="subtitle">Finalize sua compra com segurança na 90+ Store</p>
    </div>

    <!-- Tela Pós-Compra (Sucesso) -->
    <div v-if="orderSuccess" class="order-success-card">
      <div class="success-icon">✅</div>
      <h2 class="title-md mt-4">Pedido #{{ orderNumber }} Gerado!</h2>
      <p class="mt-2 text-gray">Seu pedido foi recebido. Aguardando confirmação de pagamento.</p>
      
      <!-- Linha do Tempo do Pedido (Simulação) -->
      <div class="order-timeline mt-8">
        <div class="timeline-step active">
          <div class="step-circle">1</div>
          <span>Pedido Gerado</span>
        </div>
        <div class="timeline-line"></div>
        <div class="timeline-step">
          <div class="step-circle">2</div>
          <span>Pagamento Aprovado</span>
        </div>
        <div class="timeline-line"></div>
        <div class="timeline-step">
          <div class="step-circle">3</div>
          <span>Em Separação</span>
        </div>
        <div class="timeline-line"></div>
        <div class="timeline-step">
          <div class="step-circle">4</div>
          <span>Enviado</span>
        </div>
      </div>

      <div class="payment-instructions mt-8" v-if="pixManualData">
        <h3>Instruções para PIX</h3>
        <p class="mt-2">Escaneie o QR Code ou copie a Chave Pix abaixo.</p>
        <div class="pix-box mt-4 flex flex-col items-center">
          <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(pixManualData)}`" width="200" alt="QR Code Pix" />
          <input type="text" :value="pixManualData" class="input-field mt-4 w-full text-center" readonly @click="$event.target.select()" />
          <small class="text-secondary mt-2">Dica: Selecione a opção "Pagar com Chave Pix" no seu banco e cole a chave acima.</small>
        </div>
      </div>

      <RouterLink to="/minha-conta?tab=pedidos" class="btn btn-primary mt-8 w-full text-center block">VER MEUS PEDIDOS</RouterLink>
    </div>

    <!-- Fluxo de Checkout (5 Etapas) -->
    <div v-else class="checkout-layout">
      <!-- Lado Esquerdo: Etapas -->
      <div class="checkout-steps">
        
        <!-- Step 1: Identificação -->
        <div class="step-card" :class="{ 'active': currentStep === 1, 'completed': currentStep > 1 }">
          <div class="step-header">
            <span class="step-number">1</span>
            <h2>Identificação</h2>
            <button v-if="currentStep > 1" class="edit-btn" @click="currentStep = 1">Editar</button>
          </div>
          <div class="step-body" v-show="currentStep === 1">
            <form @submit.prevent="currentStep = 2">
              <div class="input-group">
                <label class="input-label">E-mail</label>
                <input type="email" v-model="checkoutData.email" class="input-field" required />
              </div>
              <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="input-group">
                  <label class="input-label">Nome Completo</label>
                  <input type="text" v-model="checkoutData.name" class="input-field" required />
                </div>
                <div class="input-group">
                  <label class="input-label">CPF</label>
                  <input type="text" v-model="checkoutData.cpf" class="input-field" required />
                </div>
              </div>
              <button type="submit" class="btn btn-primary mt-6 w-full">Continuar para Entrega</button>
            </form>
          </div>
          <div class="step-summary" v-show="currentStep > 1">
            {{ checkoutData.name }} ({{ checkoutData.email }})
          </div>
        </div>

        <!-- Step 2: Endereço -->
        <div class="step-card mt-4" :class="{ 'active': currentStep === 2, 'completed': currentStep > 2 }">
          <div class="step-header">
            <span class="step-number">2</span>
            <h2>Endereço de Entrega</h2>
            <button v-if="currentStep > 2" class="edit-btn" @click="currentStep = 2">Editar</button>
          </div>
          <div class="step-body" v-show="currentStep === 2">
            <form @submit.prevent="currentStep = 3">
              <div class="grid grid-cols-2 gap-4">
                <div class="input-group">
                  <label class="input-label">CEP</label>
                  <input type="text" v-model="checkoutData.cep" @blur="fetchAddress" class="input-field" maxlength="9" required />
                </div>
                <div class="input-group">
                  <label class="input-label">Rua / Logradouro</label>
                  <input type="text" v-model="checkoutData.rua" class="input-field" required />
                </div>
              </div>
              <div class="grid grid-cols-3 gap-4 mt-4">
                <div class="input-group">
                  <label class="input-label">Número</label>
                  <input type="text" v-model="checkoutData.numero" class="input-field" required />
                </div>
                <div class="input-group">
                  <label class="input-label">Bairro</label>
                  <input type="text" v-model="checkoutData.bairro" class="input-field" required />
                </div>
                <div class="input-group">
                  <label class="input-label">Cidade</label>
                  <input type="text" v-model="checkoutData.cidade" class="input-field" required />
                </div>
              </div>
              <button type="submit" class="btn btn-primary mt-6 w-full" @click.prevent="advanceToShipping">Ir para Opções de Frete</button>
            </form>
          </div>
          <div class="step-summary" v-show="currentStep > 2">
            {{ checkoutData.rua }}, {{ checkoutData.numero }} - {{ checkoutData.cidade }}
          </div>
        </div>

        <!-- Step 3: Frete -->
        <div class="step-card mt-4" :class="{ 'active': currentStep === 3, 'completed': currentStep > 3 }">
          <div class="step-header">
            <span class="step-number">3</span>
            <h2>Opções de Frete</h2>
            <button v-if="currentStep > 3" class="edit-btn" @click="currentStep = 3">Editar</button>
          </div>
          <div class="step-body" v-show="currentStep === 3">
            <div class="shipping-options" v-if="!loadingShipping">
              <label v-for="(opt, index) in shippingOptions" :key="index" class="shipping-label mt-2" :class="{'selected': checkoutData.shipping === opt.servico}">
                <input type="radio" v-model="checkoutData.shipping" :value="opt.servico" />
                <div class="shipping-info">
                  <strong>{{ opt.servico }}</strong>
                  <span>
                    <template v-if="opt.a_combinar">A combinar após o pedido</template>
                    <template v-else-if="opt.valor === 0">Grátis</template>
                    <template v-else>{{ formatCurrency(opt.valor) }}</template>
                    - Entrega em {{ opt.prazo_dias }} dia(s)
                  </span>
                </div>
              </label>
              
              <div v-if="selectedShippingOpt?.a_combinar" class="alert alert-info mt-3 mb-2" style="background: var(--color-bg-elevated); padding: 12px; border-radius: 6px; font-size: 0.875rem;">
                <i class="fas fa-info-circle text-brand mr-2"></i>
                Entraremos em contato com você via WhatsApp após a conclusão do pedido para confirmar o valor exato da entrega local.
              </div>

              <div v-if="shippingOptions.length === 0" class="text-gray text-center my-4">
                Nenhuma opção de frete encontrada para este CEP.
              </div>
            </div>
            <div v-else class="text-center my-4">
              <span class="loading-spinner"></span>
              <p class="text-gray mt-2">Cotando opções de frete...</p>
            </div>
            
            <button class="btn btn-primary mt-6 w-full" @click="currentStep = 4" :disabled="shippingOptions.length === 0">Ir para Pagamento</button>
          </div>
          <div class="step-summary" v-show="currentStep > 3">
            {{ checkoutData.shipping }}
            <span v-if="selectedShippingOpt?.a_combinar">(A combinar)</span>
            <span v-else>({{ formatCurrency(shippingCost) }})</span>
          </div>
        </div>

        <!-- Step 4: Pagamento -->
        <div class="step-card mt-4" :class="{ 'active': currentStep === 4, 'completed': currentStep > 4 }">
          <div class="step-header">
            <span class="step-number">4</span>
            <h2>Pagamento</h2>
            <button v-if="currentStep > 4" class="edit-btn" @click="currentStep = 4">Editar</button>
          </div>
          <div class="step-body" v-show="currentStep === 4">
            <div class="payment-methods">
              <label v-for="(gateway, index) in paymentOptions" :key="index" class="payment-label mt-2" :class="{'selected': checkoutData.paymentMethod === gateway.slug}">
                <input type="radio" v-model="checkoutData.paymentMethod" :value="gateway.slug" />
                <span>{{ gateway.nome }}</span>
              </label>
              <div v-if="paymentOptions.length === 0" class="text-gray text-center my-4">
                Nenhum método de pagamento disponível no momento.
              </div>
            </div>

            <button class="btn btn-primary mt-6 w-full" @click="currentStep = 5" :disabled="!checkoutData.paymentMethod">Revisar Pedido</button>
          </div>
          <div class="step-summary" v-show="currentStep > 4">
            {{ paymentOptions.find(p => p.slug === checkoutData.paymentMethod)?.nome || 'Pagamento' }}
          </div>
        </div>
      </div>

      <!-- Lado Direito: Resumo (Step 5 e Fixo) -->
      <aside class="checkout-sidebar">
        <div class="summary-card">
          <h3 class="filter-title">RESUMO DO PEDIDO</h3>
          
          <div class="cart-items-mini">
            <div class="cart-item-mini" v-for="item in store.cart" :key="item.variacao.id">
              <img :src="item.produto.foto_capa || 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=100'" />
              <div class="mini-details">
                <p>{{ item.produto.nome }} ({{ item.variacao.tamanho }} / {{ item.variacao.cor }})</p>
                <span>{{ item.quantidade }}x {{ formatCurrency(item.produto.tem_desconto ? item.produto.preco_desconto : item.produto.preco_venda) }}</span>
              </div>
            </div>
            <div v-if="store.cart.length === 0" class="text-center text-gray my-4">Seu carrinho está vazio.</div>
          </div>

          <div class="summary-lines mt-4">
            <div class="summary-line">
              <span>Subtotal</span>
              <span>{{ formatCurrency(cartSubtotal) }}</span>
            </div>
            <div class="summary-line">
              <span>Frete</span>
              <span>
                <template v-if="selectedShippingOpt?.a_combinar">A combinar</template>
                <template v-else-if="shippingCost === 0">Grátis</template>
                <template v-else>{{ formatCurrency(shippingCost) }}</template>
              </span>
            </div>
            <div class="summary-line text-red" v-if="checkoutData.paymentMethod === 'pix'">
              <span>Desconto PIX (5%)</span>
              <span>- {{ formatCurrency(pixDiscount) }}</span>
            </div>
            <div class="summary-line total mt-4">
              <span>TOTAL</span>
              <span>{{ formatCurrency(cartTotal) }}</span>
            </div>
          </div>

          <!-- Step 5: Confirmação -->
          <button v-if="currentStep === 5" class="btn btn-primary w-full mt-6" @click="finalizeOrder">
            FINALIZAR COMPRA
          </button>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useHead } from '@vueuse/head'
import { useStore } from '@/store/main'
import axios from 'axios'

useHead({ title: 'Checkout | 90+ Store' })

const store = useStore()

const currentStep = ref(1)
const orderSuccess = ref(false)
const pixManualData = ref(null)
const orderNumber = ref('')

const checkoutData = reactive({
  email: '',
  name: '',
  cpf: '',
  cep: '',
  rua: '',
  numero: '',
  complemento: '',
  bairro: '',
  cidade: '',
  estado: '',
  shipping: '',
  paymentMethod: 'pix'
})

const shippingOptions = ref([])
const paymentOptions = ref([])
const loadingShipping = ref(false)

onMounted(async () => {
  try {
    const res = await axios.get('/api/store-settings')
    paymentOptions.value = res.data.paymentMethods || []
    
    // Auto-select infinitepay if available
    const hasInfinitePay = paymentOptions.value.find(p => p.slug === 'infinitepay')
    if (hasInfinitePay) {
      checkoutData.paymentMethod = 'infinitepay'
    } else if (paymentOptions.value.length > 0) {
      checkoutData.paymentMethod = paymentOptions.value[0].slug
    }
  } catch (err) {
    console.error("Erro ao carregar configurações de pagamento")
  }
})

async function advanceToShipping() {
  currentStep.value = 3
  const cleanCep = checkoutData.cep.replace(/\D/g, '')
  
  if (cleanCep.length >= 8) {
     loadingShipping.value = true
     try {
       // Peso mockado fixo ou pegando do carrinho. (Aqui enviando 1kg mock)
       const res = await axios.post('/api/shipping/quote', { cep: cleanCep, peso_total: 1 })
       shippingOptions.value = res.data.opcoes || []
       
       if (shippingOptions.value.length > 0) {
          // Seleciona a primeira opção por padrão se estiver vazio
          if (!checkoutData.shipping) {
             checkoutData.shipping = shippingOptions.value[0].servico
          }
       }
     } catch (err) {
       console.error("Erro ao cotar frete", err)
     } finally {
       loadingShipping.value = false
     }
  }
}

// Atualizar dados se o usuário for carregado depois (ou já estiver no store)
import { watch } from 'vue'
watch(() => store.user, (user) => {
  if (user) {
    checkoutData.email = user.email || ''
    checkoutData.name = user.nome_completo || ''
    // Formatar CPF 000.000.000-00 se vier do banco só números
    let c = user.cpf || ''
    if (c.length === 11) {
      c = c.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4")
    }
    checkoutData.cpf = c
    
    // Puxar o primeiro endereço salvo (Principal)
    if (user.enderecos && user.enderecos.length > 0) {
      const end = user.enderecos[0]
      let zip = end.cep || ''
      if (zip.length === 8) zip = zip.replace(/(\d{5})(\d{3})/, "$1-$2")
      
      checkoutData.cep = zip
      checkoutData.rua = end.logradouro || ''
      checkoutData.numero = end.numero || ''
      checkoutData.complemento = end.complemento || ''
      checkoutData.bairro = end.bairro || ''
      checkoutData.cidade = end.cidade || ''
      checkoutData.estado = end.estado || ''
    }
  }
}, { immediate: true })

// (Removido import repetido)// Busca real de CEP com axios
async function fetchAddress() {
  const cleanCep = checkoutData.cep.replace(/\D/g, '')
  if (cleanCep.length !== 8) return

  try {
    const res = await axios.get(`https://brasilapi.com.br/api/cep/v1/${cleanCep}`, { timeout: 4000 })
    checkoutData.rua = res.data.street || ''
    checkoutData.bairro = res.data.neighborhood || ''
    checkoutData.cidade = res.data.city || ''
    checkoutData.estado = res.data.state || ''
  } catch (err) {
    console.warn("BrasilAPI falhou, tentando ViaCEP no Checkout...")
    try {
      const res2 = await axios.get(`https://viacep.com.br/ws/${cleanCep}/json/`, { timeout: 4000 })
      if (!res2.data.erro) {
        checkoutData.rua = res2.data.logradouro || ''
        checkoutData.bairro = res2.data.bairro || ''
        checkoutData.cidade = res2.data.localidade || ''
        checkoutData.estado = res2.data.uf || ''
      }
    } catch (err2) {
      console.error("Falha em ambas APIs de CEP")
    }
  }
}

const cartSubtotal = computed(() => {
  return store.cartSubtotal
})

const pixDiscount = computed(() => {
  if (checkoutData.paymentMethod === 'pix' || checkoutData.paymentMethod === 'mercadopago') {
    return cartSubtotal.value * 0.05
  }
  return 0
})

const selectedShippingOpt = computed(() => {
  return shippingOptions.value.find(o => o.servico === checkoutData.shipping)
})

const shippingCost = computed(() => {
  return selectedShippingOpt.value ? selectedShippingOpt.value.valor : 0
})

const cartTotal = computed(() => {
  return cartSubtotal.value + shippingCost.value - pixDiscount.value
})

function formatCurrency(value) {
  return 'R$ ' + Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function finalizeOrder() {
  const itens = store.cart.map(item => ({
    variacao_id: item.variacao.id,
    quantidade: item.quantidade
  }))

  const payload = {
    cep: checkoutData.cep,
    logradouro: checkoutData.rua,
    numero: checkoutData.numero,
    complemento: checkoutData.complemento || '',
    bairro: checkoutData.bairro,
    cidade: checkoutData.cidade,
    estado: checkoutData.estado,
    gateway: checkoutData.paymentMethod,
    frete_valor: shippingCost.value,
    frete_servico: checkoutData.shipping,
    frete_a_combinar: selectedShippingOpt.value?.a_combinar || false,
    itens: itens
  }

  try {
    const res = await axios.post('/api/checkout', payload, {
      headers: { Authorization: `Bearer ${store.token}` }
    })
    
    // Se for InfinitePay, redireciona o cliente para o link de pagamento
    if (res.data.infinitepay && res.data.redirect_url) {
      window.location.href = res.data.redirect_url
      return
    }

    orderNumber.value = res.data.pedido_id
    if (res.data.pix_manual) {
      pixManualData.value = res.data.chave_pix
    }
    orderSuccess.value = true
    store.clearCart()
    window.scrollTo(0, 0)
  } catch (err) {
    console.error("Erro no checkout", err)
    const msg = err.response?.data?.message || 'Erro ao processar pedido. Verifique os dados e tente novamente.'
    alert("Falha no pagamento: " + msg)
  }
}
</script>

<style scoped>
.checkout-view {
  padding: var(--spacing-8) var(--spacing-4);
  max-width: 1200px;
}

.checkout-header {
  text-align: center;
  margin-bottom: var(--spacing-8);
}

.subtitle {
  color: var(--color-gray);
  margin-top: var(--spacing-2);
}

.checkout-layout {
  display: flex;
  gap: var(--spacing-8);
  align-items: flex-start;
}

.checkout-steps {
  flex: 2;
}

.step-card {
  background-color: var(--color-black-light);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  padding: var(--spacing-6);
  transition: var(--transition);
}

.step-card.active {
  border-color: var(--color-red);
}

.step-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-4);
}

.step-number {
  width: 32px;
  height: 32px;
  background-color: var(--color-black-lighter);
  color: var(--color-white);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-title);
  font-size: 1.25rem;
}

.step-card.active .step-number {
  background-color: var(--color-red);
}

.step-card.completed .step-number {
  background-color: #10b981; /* green */
}

.step-header h2 {
  font-family: var(--font-body);
  font-size: 1.25rem;
  font-weight: 600;
  text-transform: none;
  flex: 1;
}

.edit-btn {
  color: var(--color-gray);
  text-decoration: underline;
  font-size: 0.875rem;
}

.edit-btn:hover {
  color: var(--color-white);
}

.step-summary {
  color: var(--color-gray);
  font-size: 0.875rem;
  padding-left: 48px; /* alinhar com titulo */
}

.shipping-label, .payment-label {
  display: flex;
  align-items: center;
  gap: var(--spacing-4);
  padding: var(--spacing-4);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  cursor: pointer;
  background-color: var(--color-black);
}

.shipping-label.selected, .payment-label.selected {
  border-color: var(--color-red);
}

.shipping-info strong {
  display: block;
}

.shipping-info span {
  color: var(--color-gray);
  font-size: 0.875rem;
}

/* Sidebar Resumo */
.checkout-sidebar {
  flex: 1;
  position: sticky;
  top: 100px; /* Header height offset */
}

.summary-card {
  background-color: var(--color-black-light);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  padding: var(--spacing-6);
}

.cart-item-mini {
  display: flex;
  gap: var(--spacing-4);
  border-bottom: 1px solid var(--color-black-lighter);
  padding-bottom: var(--spacing-4);
  margin-bottom: var(--spacing-4);
}

.cart-item-mini img {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: var(--border-radius-sm);
}

.mini-details p {
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: var(--spacing-1);
}

.mini-details span {
  color: var(--color-gray);
  font-size: 0.875rem;
}

.summary-line {
  display: flex;
  justify-content: space-between;
  margin-bottom: var(--spacing-2);
  color: var(--color-gray);
}

.summary-line.total {
  border-top: 1px solid var(--color-black-lighter);
  padding-top: var(--spacing-4);
  color: var(--color-white);
  font-family: var(--font-title);
  font-size: 1.5rem;
}

.text-red { color: var(--color-red); }

/* Pós-Compra / Timeline */
.order-success-card {
  background-color: var(--color-black-light);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  padding: var(--spacing-8);
  text-align: center;
  max-width: 600px;
  margin: 0 auto;
}

.success-icon {
  font-size: 4rem;
}

.order-timeline {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 500px;
  margin: 0 auto;
}

.timeline-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: var(--color-gray);
}

.timeline-step.active {
  color: var(--color-white);
}

.timeline-step.active .step-circle {
  background-color: var(--color-red);
  border-color: var(--color-red);
}

.step-circle {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: 2px solid var(--color-gray);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: var(--spacing-2);
  font-weight: 600;
}

.timeline-line {
  flex: 1;
  height: 2px;
  background-color: var(--color-gray-dark);
  margin: 0 var(--spacing-2);
  margin-bottom: 25px; /* align with circle */
}

.pix-box {
  background-color: var(--color-black);
  padding: var(--spacing-4);
  border-radius: var(--border-radius-sm);
  display: flex;
  flex-direction: column;
  align-items: center;
}

@media (max-width: 1024px) {
  .checkout-layout {
    flex-direction: column;
  }
  .checkout-sidebar {
    width: 100%;
    position: static;
  }
}

@media (max-width: 600px) {
  .order-timeline {
    flex-direction: column;
    gap: var(--spacing-4);
  }
  .timeline-line {
    display: none;
  }
}
</style>
