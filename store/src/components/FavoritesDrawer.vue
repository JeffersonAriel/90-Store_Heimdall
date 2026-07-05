<template>
  <div v-if="isOpen" class="drawer-overlay" @click="close">
    <div class="drawer-content" @click.stop>
      <div class="drawer-header">
        <h3 class="title-sm">MEUS FAVORITOS</h3>
        <button class="close-btn" @click="close">✕</button>
      </div>
      
      <div class="drawer-body">
        <div v-if="favoriteItems.length === 0" class="empty-favorites">
          <span class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="heart-icon">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
          </span>
          <p>Você ainda não tem produtos favoritos.</p>
          <button class="btn btn-primary mt-4" @click="close">Explorar Produtos</button>
        </div>

        <template v-else>
          <div class="favorite-items">
            <div v-for="product in favoriteItems" :key="product.id" class="favorite-item">
              <img :src="product.foto_capa?.url || product.fotos?.[0]?.url || 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=150&auto=format&fit=crop'" class="item-img" :alt="product.nome" />
              <div class="item-details">
                <h4 class="item-name">{{ product.nome }}</h4>
                <div class="item-price">
                  <template v-if="product.tem_desconto">
                    <span class="price-old">R$ {{ formatMoney(product.preco_venda) }}</span>
                    <span class="price-new">R$ {{ formatMoney(product.preco_desconto) }}</span>
                  </template>
                  <template v-else>
                    <span class="price-new">R$ {{ formatMoney(product.preco_venda) }}</span>
                  </template>
                </div>
                
                <div class="item-actions">
                  <RouterLink :to="`/produto/${product.slug}`" class="btn btn-primary btn-sm" @click="close">
                    Ver Detalhes
                  </RouterLink>
                  <button class="remove-btn" @click="toggleFavorite(product)">
                    Remover
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useStore } from '../store/main'

defineProps({
  isOpen: { type: Boolean, default: false }
})

const emit = defineEmits(['close'])
const store = useStore()

const favoriteItems = computed(() => store.favorites || [])

function close() {
  emit('close')
}

function toggleFavorite(product) {
  store.toggleFavorite(product)
}

function formatMoney(val) {
  if (!val) return '0,00'
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

.empty-favorites {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  color: var(--color-gray);
  padding: var(--spacing-8);
  text-align: center;
}

.empty-favorites .icon {
  margin-bottom: var(--spacing-4);
  color: var(--color-gray);
}

.heart-icon {
  stroke: var(--color-gray);
}

.favorite-items {
  flex: 1;
  overflow-y: auto;
  padding: var(--spacing-4);
}

.favorite-item {
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
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.item-name {
  font-family: var(--font-body);
  font-size: 0.9rem;
  font-weight: 500;
  margin-bottom: var(--spacing-1);
  color: var(--color-white);
}

.item-price {
  margin-bottom: var(--spacing-2);
}

.price-old {
  font-size: 0.75rem;
  color: var(--color-gray);
  text-decoration: line-through;
  margin-right: var(--spacing-2);
}

.price-new {
  font-family: var(--font-title);
  color: var(--color-white);
  font-weight: 600;
}

.item-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.btn-sm {
  padding: var(--spacing-1) var(--spacing-3);
  font-size: 0.75rem;
}

.remove-btn {
  color: var(--color-gray);
  font-size: 0.75rem;
  text-decoration: underline;
}

.remove-btn:hover {
  color: var(--color-red);
}

.mt-4 { margin-top: var(--spacing-4); }
</style>
