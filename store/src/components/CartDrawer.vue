<template>
  <div v-if="isOpen" class="drawer-overlay" @click="close">
    <div class="drawer-content" @click.stop>
      <div class="drawer-header">
        <h3 class="title-sm">MEU CARRINHO</h3>
        <button class="close-btn" @click="close">✕</button>
      </div>
      
      <div class="drawer-body">
        
        <div v-if="cartItems.length === 0" class="empty-cart">
          <span class="icon">🛒</span>
          <p>Seu carrinho está vazio.</p>
          <button class="btn btn-primary mt-4" @click="close">Continuar Comprando</button>
        </div>

        <template v-else>
          <div class="cart-items">
            <div v-for="(item, index) in cartItems" :key="index" class="cart-item">
              <img :src="item.produto.foto_capa?.url || item.produto.fotos?.[0]?.url || 'https://via.placeholder.com/150'" class="item-img" :alt="item.produto.nome" />
              <div class="item-details">
                <h4 class="item-name">{{ item.produto.nome }}</h4>
                <div class="item-meta">Tam: {{ item.variacao.tamanho }} | Cor: {{ item.variacao.cor }}</div>
                <div class="item-price">R$ {{ formatMoney((item.produto.tem_desconto ? item.produto.preco_desconto : item.produto.preco_venda) + item.variacao.preco_adicional) }}</div>
                
                <div class="item-actions">
                  <div class="qty-control">
                    <button @click="updateQty(item, -1)">-</button>
                    <span>{{ item.quantidade }}</span>
                    <button @click="updateQty(item, 1)">+</button>
                  </div>
                  <button class="remove-btn" @click="removeItem(item.variacao.id)">Remover</button>
                </div>
              </div>
            </div>
          </div>

          <div class="cart-summary">
            <!-- Cupom -->
            <div class="coupon-section">
              <label>Cupom de Desconto</label>
              <div class="coupon-input">
                <input type="text" v-model="couponCode" placeholder="Insira o código" class="input-field" />
                <button class="btn btn-outline" @click="applyCoupon">Aplicar</button>
              </div>
            </div>

            <div class="summary-line mt-4">
              <span>Subtotal</span>
              <span>R$ {{ formatMoney(subtotal) }}</span>
            </div>
            <div class="summary-line">
              <span>Frete Estimado</span>
              <span class="text-green">Grátis</span>
            </div>
            <div v-if="discount > 0" class="summary-line text-red">
              <span>Desconto</span>
              <span>- R$ {{ formatMoney(discount) }}</span>
            </div>
            
            <div class="summary-line total mt-4">
              <span>Total</span>
              <span>R$ {{ formatMoney(total) }}</span>
            </div>

            <RouterLink to="/checkout" class="btn btn-primary w-full mt-6" @click="close">
              FINALIZAR COMPRA
            </RouterLink>
            <button class="btn btn-outline w-full mt-2" @click="close">
              Continuar Comprando
            </button>
          </div>
        </template>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useStore } from '../store/main'

const props = defineProps({
  isOpen: { type: Boolean, default: false }
})

const emit = defineEmits(['close'])
const store = useStore()

const cartItems = computed(() => store.cart)

const couponCode = ref('')
const discount = computed(() => store.cartDiscount)
const subtotal = computed(() => store.cartSubtotal)
const total = computed(() => store.cartTotal)

function close() {
  emit('close')
}

function updateQty(item, delta) {
  if (item.quantidade + delta > 0) {
    store.addToCart(item.produto, item.variacao, delta)
  } else if (item.quantidade + delta === 0) {
    store.removeFromCart(item.variacao.id)
  }
}

function removeItem(variationId) {
  store.removeFromCart(variationId)
}

function applyCoupon() {
  if (couponCode.value.toUpperCase() === 'BEMVINDO10') {
    store.appliedCoupon = { tipo: 'percent', valor: 10, valor_minimo_pedido: 0 }
    alert('Cupom de 10% aplicado!')
  } else {
    alert('Cupom inválido ou expirado.')
    store.appliedCoupon = null
  }
}

function formatMoney(val) {
  return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.drawer-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.7);
  z-index: var(--z-drawer);
  display: flex;
  justify-content: flex-end;
}

.drawer-content {
  width: 450px;
  max-width: 100%;
  background-color: var(--color-black);
  border-left: 1px solid var(--color-black-lighter);
  height: 100%;
  animation: slideInRight 0.3s ease;
  display: flex;
  flex-direction: column;
}

.drawer-header {
  padding: var(--spacing-4);
  border-bottom: 1px solid var(--color-black-lighter);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.close-btn {
  color: var(--color-white);
  font-size: 1.5rem;
}

.close-btn:hover {
  color: var(--color-red);
}

.drawer-body {
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow: hidden;
}

.empty-cart {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  color: var(--color-gray);
  padding: var(--spacing-8);
}

.empty-cart .icon {
  font-size: 4rem;
  margin-bottom: var(--spacing-4);
}

.cart-items {
  flex: 1;
  overflow-y: auto;
  padding: var(--spacing-4);
}

.cart-item {
  display: flex;
  gap: var(--spacing-4);
  padding: var(--spacing-4) 0;
  border-bottom: 1px solid var(--color-black-lighter);
}

.item-img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  background-color: var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
}

.item-details {
  flex: 1;
}

.item-name {
  font-family: var(--font-body);
  font-size: 0.9rem;
  font-weight: 500;
  margin-bottom: var(--spacing-1);
}

.item-meta {
  color: var(--color-gray);
  font-size: 0.75rem;
  margin-bottom: var(--spacing-1);
}

.item-price {
  font-family: var(--font-title);
  color: var(--color-white);
  margin-bottom: var(--spacing-2);
}

.item-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.qty-control {
  display: flex;
  align-items: center;
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
}

.qty-control button {
  width: 25px;
  height: 25px;
  color: var(--color-white);
}

.qty-control span {
  width: 30px;
  text-align: center;
  font-size: 0.875rem;
}

.remove-btn {
  color: var(--color-gray);
  font-size: 0.75rem;
  text-decoration: underline;
}

.remove-btn:hover {
  color: var(--color-red);
}

.cart-summary {
  background-color: var(--color-black-light);
  padding: var(--spacing-6) var(--spacing-4);
  border-top: 1px solid var(--color-black-lighter);
}

.coupon-section label {
  display: block;
  font-size: 0.875rem;
  color: var(--color-gray);
  margin-bottom: var(--spacing-2);
}

.coupon-input {
  display: flex;
  gap: var(--spacing-2);
}

.coupon-input .input-field {
  flex: 1;
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

.text-green { color: #10b981; }
.text-red { color: var(--color-red); }

.w-full { width: 100%; }
.mt-2 { margin-top: var(--spacing-2); }
.mt-4 { margin-top: var(--spacing-4); }
.mt-6 { margin-top: var(--spacing-6); }
</style>
