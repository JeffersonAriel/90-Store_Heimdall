<template>
  <div>
    <h1 class="mb-6">Seu Carrinho</h1>

    <div v-if="!store.cart.length" class="text-center py-6">
      <p class="text-secondary">Seu carrinho está vazio.</p>
      <RouterLink to="/" class="store-btn store-btn-primary mt-4">Continuar Comprando</RouterLink>
    </div>

    <div v-else class="cart-container">
      <!-- Lista de Itens -->
      <div class="cart-items-panel">
        <div v-for="item in store.cart" :key="item.variacao.id" class="cart-item">
          <img :src="item.produto.fotos[0]?.url" class="item-img" :alt="item.produto.nome" />
          
          <div class="item-details">
            <h4>{{ item.produto.nome }}</h4>
            <p class="text-secondary">Tam: {{ item.variacao.tamanho || 'N/A' }} | Cor: {{ item.variacao.cor || 'N/A' }}</p>
            <p class="text-muted">Modelo: {{ item.variacao.tipo_estoque === 'proprio' ? 'Estoque Próprio' : 'Dropshipping' }}</p>
          </div>

          <div class="item-qty">
            <span>x{{ item.quantidade }}</span>
          </div>

          <div class="item-price">
            <strong>R$ {{ formatMoney(getItemPrice(item)) }}</strong>
          </div>

          <button class="remove-btn" @click="store.removeFromCart(item.variacao.id)">✕</button>
        </div>
      </div>

      <!-- Resumo Financeiro & Frete -->
      <div class="cart-summary-panel">
        <div class="card p-4">
          <h3>Resumo da Compra</h3>
          
          <div class="summary-row mt-4">
            <span class="text-secondary">Subtotal</span>
            <span>R$ {{ formatMoney(store.cartSubtotal) }}</span>
          </div>

          <!-- CEP & Frete -->
          <div class="shipping-section mt-4 pt-4" style="border-top: 1px solid var(--border-color);">
            <label class="form-label">Cálculo de Frete (CEP)</label>
            <div class="flex gap-2">
              <input v-model="cep" type="text" class="store-input" placeholder="00000-000" maxlength="9" />
              <button class="store-btn store-btn-secondary" :disabled="loadingShipping" @click="calculateShipping">
                {{ loadingShipping ? '...' : 'Calcular' }}
              </button>
            </div>

            <!-- Opções de Frete -->
            <div v-if="shippingOptions.length" class="shipping-options-list mt-2">
              <div 
                v-for="opt in shippingOptions" 
                :key="opt.servico" 
                class="shipping-option" 
                :class="{ active: store.shippingQuote?.servico === opt.servico }"
                @click="store.shippingQuote = opt"
              >
                <div class="opt-info">
                  <strong>{{ opt.servico }}</strong>
                  <span class="text-secondary block">Prazo: {{ opt.prazo_dias }} dia(s)</span>
                </div>
                <strong>R$ {{ formatMoney(opt.valor) }}</strong>
              </div>
            </div>
          </div>

          <div v-if="store.shippingQuote" class="summary-row mt-4">
            <span class="text-secondary">Frete ({{ store.shippingQuote.servico }})</span>
            <span>R$ {{ formatMoney(store.shippingQuote.valor) }}</span>
          </div>

          <div class="summary-row total-row mt-4 pt-4" style="border-top: 1px solid var(--border-color);">
            <span>Total</span>
            <span class="total-price">R$ {{ formatMoney(store.cartTotal) }}</span>
          </div>

          <button class="store-btn store-btn-primary w-full mt-6" @click="proceedToCheckout">
            Ir para o Pagamento
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useStore } from '@/store/main'
import axios from 'axios'

const store = useStore()
const router = useRouter()

const cep = ref('')
const loadingShipping = ref(false)
const shippingOptions = ref([])

function getItemPrice(item) {
  const base = item.produto.tem_desconto ? item.produto.preco_desconto : item.produto.preco_venda
  return (parseFloat(base) + parseFloat(item.variacao.preco_adicional)) * item.quantidade
}

async function calculateShipping() {
  if (cep.value.length < 8) return
  loadingShipping.value = true
  try {
    const res = await axios.post('/api/shipping/quote', {
      cep: cep.value,
      peso_total: store.cartWeight
    })
    shippingOptions.value = res.data.opcoes
  } catch (err) {
    console.error('Falha ao calcular fretes', err)
  } finally {
    loadingShipping.value = false
  }
}

function proceedToCheckout() {
  if (!store.shippingQuote) {
    alert('Por favor, calcule e selecione a opção de frete antes de prosseguir.')
    return
  }
  router.push('/checkout')
}

function formatMoney(val) {
  return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.cart-container {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
  margin-top: 2rem;
}

@media (max-width: 1024px) {
  .cart-container {
    grid-template-columns: 1fr;
  }
}

.cart-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  padding: 1rem;
  border-radius: var(--radius-lg);
  margin-bottom: 1rem;
  position: relative;
}

.item-img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: var(--radius-md);
  background: var(--bg-secondary);
}

.item-details {
  flex: 1;
}

.remove-btn {
  background: transparent;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  font-size: 1rem;
  position: absolute;
  top: 1rem;
  right: 1rem;
}

.remove-btn:hover { color: var(--color-danger); }

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.total-row {
  font-weight: 700;
  font-size: 1.125rem;
}

.total-price {
  color: var(--accent-cyan);
  font-size: 1.25rem;
}

.shipping-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: var(--bg-input);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  margin-top: 0.5rem;
  cursor: pointer;
  transition: var(--transition-smooth);
}

.shipping-option.active {
  border-color: var(--brand-primary);
  background: var(--brand-glow);
}

.block { display: block; }
.p-4 { padding: 1.5rem; }
.pt-4 { padding-top: 1rem; }
.mt-6 { margin-top: 1.5rem; }
</style>
