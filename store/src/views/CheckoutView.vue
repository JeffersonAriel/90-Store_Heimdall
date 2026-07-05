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

      <div class="payment-instructions mt-8" v-if="checkoutData.paymentMethod === 'pix'">
        <h3>Instruções para PIX</h3>
        <p class="mt-2">Escaneie o QR Code ou copie a chave Pix abaixo.</p>
        <div class="pix-box mt-4">
          <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" width="150" alt="QR Code Pix" />
          <input type="text" value="00020126580014br.gov.bcb.pix0136..." class="input-field mt-4 w-full text-center" readonly />
        </div>
      </div>

      <RouterLink to="/" class="btn btn-primary mt-8">VOLTAR PARA A LOJA</RouterLink>
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
              <button type="submit" class="btn btn-primary mt-6 w-full">Ir para Opções de Frete</button>
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
            <div class="shipping-options">
              <label class="shipping-label" :class="{'selected': checkoutData.shipping === 'pac'}">
                <input type="radio" v-model="checkoutData.shipping" value="pac" />
                <div class="shipping-info">
                  <strong>Padrão (Melhor Envio)</strong>
                  <span>Grátis - Entrega em 8 a 10 dias</span>
                </div>
              </label>
              
              <label class="shipping-label mt-2" :class="{'selected': checkoutData.shipping === 'sedex'}">
                <input type="radio" v-model="checkoutData.shipping" value="sedex" />
                <div class="shipping-info">
                  <strong>Expresso (Sedex)</strong>
                  <span>R$ 29,90 - Entrega em 2 a 3 dias</span>
                </div>
              </label>
            </div>
            <button class="btn btn-primary mt-6 w-full" @click="currentStep = 4">Ir para Pagamento</button>
          </div>
          <div class="step-summary" v-show="currentStep > 3">
            {{ checkoutData.shipping === 'pac' ? 'Padrão (Grátis)' : 'Expresso (R$ 29,90)' }}
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
              <label class="payment-label" :class="{'selected': checkoutData.paymentMethod === 'pix'}">
                <input type="radio" v-model="checkoutData.paymentMethod" value="pix" />
                <span>PIX (5% OFF)</span>
              </label>
              <label class="payment-label mt-2" :class="{'selected': checkoutData.paymentMethod === 'credit'}">
                <input type="radio" v-model="checkoutData.paymentMethod" value="credit" />
                <span>Cartão de Crédito</span>
              </label>
            </div>
            
            <div v-if="checkoutData.paymentMethod === 'credit'" class="credit-card-form mt-4">
              <div class="input-group">
                <label class="input-label">Número do Cartão</label>
                <input type="text" class="input-field" placeholder="0000 0000 0000 0000" />
              </div>
              <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="input-group">
                  <label class="input-label">Validade</label>
                  <input type="text" class="input-field" placeholder="MM/AA" />
                </div>
                <div class="input-group">
                  <label class="input-label">CVV</label>
                  <input type="text" class="input-field" placeholder="123" />
                </div>
              </div>
            </div>

            <button class="btn btn-primary mt-6 w-full" @click="currentStep = 5">Revisar Pedido</button>
          </div>
          <div class="step-summary" v-show="currentStep > 4">
            {{ checkoutData.paymentMethod === 'pix' ? 'PIX' : 'Cartão de Crédito' }}
          </div>
        </div>
      </div>

      <!-- Lado Direito: Resumo (Step 5 e Fixo) -->
      <aside class="checkout-sidebar">
        <div class="summary-card">
          <h3 class="filter-title">RESUMO DO PEDIDO</h3>
          
          <div class="cart-items-mini">
            <div class="cart-item-mini">
              <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=100" />
              <div class="mini-details">
                <p>Chuteira Nike Mercurial</p>
                <span>1x R$ 899,90</span>
              </div>
            </div>
          </div>

          <div class="summary-lines mt-4">
            <div class="summary-line">
              <span>Subtotal</span>
              <span>R$ 899,90</span>
            </div>
            <div class="summary-line">
              <span>Frete</span>
              <span>{{ checkoutData.shipping === 'pac' ? 'Grátis' : 'R$ 29,90' }}</span>
            </div>
            <div class="summary-line text-red" v-if="checkoutData.paymentMethod === 'pix'">
              <span>Desconto PIX</span>
              <span>- R$ 44,99</span>
            </div>
            <div class="summary-line total mt-4">
              <span>TOTAL</span>
              <span>R$ {{ calculateTotal() }}</span>
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
import { ref, reactive } from 'vue'
import { useHead } from '@vueuse/head'

useHead({ title: 'Checkout | 90+ Store' })

const currentStep = ref(1)
const orderSuccess = ref(false)
const orderNumber = ref('')

const checkoutData = reactive({
  email: 'teste@email.com',
  name: 'João Teste',
  cpf: '123.456.789-00',
  cep: '',
  rua: '',
  numero: '',
  bairro: '',
  cidade: '',
  shipping: 'pac',
  paymentMethod: 'pix'
})

// Simulação de busca de CEP (via API Heimdall)
async function fetchAddress() {
  if (checkoutData.cep.length >= 8) {
    // mock response
    checkoutData.rua = 'Av. Paulista'
    checkoutData.bairro = 'Bela Vista'
    checkoutData.cidade = 'São Paulo'
  }
}

function calculateTotal() {
  let subtotal = 899.90
  if (checkoutData.shipping === 'sedex') subtotal += 29.90
  if (checkoutData.paymentMethod === 'pix') subtotal -= 44.99 // 5% discount
  return subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function finalizeOrder() {
  orderNumber.value = Math.floor(Math.random() * 100000)
  orderSuccess.value = true
  window.scrollTo(0, 0)
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
