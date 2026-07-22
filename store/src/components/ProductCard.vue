<template>
  <div class="product-card" :class="{ 'is-esgotado': product.esgotado }">
    <div class="card-header">
      <div class="badges">
        <span v-if="product.esgotado" class="badge badge-red" style="background-color: var(--color-red, #ef4444); border: 1px solid var(--color-red, #ef4444); color: #fff;">Em breve</span>
        <span v-else-if="product.is_destaque" class="badge badge-red">Lançamento</span>
        <span v-if="product.tem_desconto && !product.esgotado" class="badge badge-dark">Oferta</span>
      </div>
      <button class="favorite-btn" title="Favoritar" @click.prevent="toggleFavorite" :class="{ 'is-favorited': isFavorite }">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" :fill="isFavorite ? 'var(--color-red)' : 'none'" :stroke="isFavorite ? 'var(--color-red)' : 'currentColor'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
      </button>
    </div>
    
    <RouterLink :to="`/produto/${product.slug}`" class="card-image-link">
      <img 
        :src="product.fotos?.[0]?.url || product.foto_capa?.url || 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500&auto=format&fit=crop'" 
        class="card-image" 
        :alt="product.nome" 
        loading="lazy"
      />
    </RouterLink>

    <div class="card-body">
      <div class="star-rating">
        <span>{{ getStarsString(getRatingAverage(product.id)) }}</span>
        <small class="rating-count">({{ getRatingCount(product.id) }})</small>
      </div>
      
      <div class="brand">{{ product.marca || 'MARCA ORIGINAL' }}</div>
      
      <RouterLink :to="`/produto/${product.slug}`" class="name-link">
        <h3 class="name">{{ product.nome }}</h3>
      </RouterLink>
      
      <div class="price-box">
        <template v-if="product.tem_desconto">
          <span class="price-old">R$ {{ formatMoney(product.preco_venda) }}</span>
          <span class="price-new">R$ {{ formatMoney(product.preco_desconto) }}</span>
        </template>
        <template v-else>
          <span class="price-new">R$ {{ formatMoney(product.preco_venda) }}</span>
        </template>
      </div>
      
      <button class="btn btn-primary add-cart-btn" @click.prevent="$emit('quick-view', product)">
        {{ product.esgotado ? 'Me Avise' : 'Comprar' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useStore } from '../store/main'
import { getRatingCount, getRatingAverage, getStarsString } from '../utils/rating'

const props = defineProps({
  product: { type: Object, required: true }
})

defineEmits(['quick-view'])

const store = useStore()
const isFavorite = computed(() => store.favorites?.some(item => item.id === props.product.id))

function toggleFavorite() {
  store.toggleFavorite(props.product)
}

function formatMoney(val) {
  if (!val) return '0,00'
  return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.product-card {
  background-color: var(--color-black-light);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius);
  overflow: hidden;
  transition: var(--transition);
  position: relative;
  display: flex;
  flex-direction: column;
}

.product-card:hover {
  border-color: var(--color-red);
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(227, 6, 19, 0.1);
}

.card-header {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  padding: var(--spacing-3);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  z-index: 10;
  pointer-events: none; /* Let clicks pass through to image except for buttons */
}

.badges {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-1);
}

.favorite-btn {
  pointer-events: auto;
  color: var(--color-gray);
  background-color: rgba(0, 0, 0, 0.5);
  border-radius: 50%;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.favorite-btn:hover {
  color: var(--color-red);
  background-color: var(--color-white);
}

.card-image-link {
  display: block;
  overflow: hidden;
  aspect-ratio: 4 / 5;
  background-color: var(--color-black-lighter);
}

.card-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  transition: transform 0.5s ease;
}

.product-card:hover .card-image {
  transform: scale(1.05);
}

.card-body {
  padding: var(--spacing-4);
  display: flex;
  flex-direction: column;
  flex: 1;
}

.star-rating {
  color: #fbbf24;
  font-size: 0.875rem;
  margin-bottom: var(--spacing-1);
}

.rating-count {
  color: var(--color-gray);
  margin-left: var(--spacing-1);
}

.brand {
  font-size: 0.75rem;
  text-transform: uppercase;
  color: var(--color-gray);
  letter-spacing: 1px;
  margin-bottom: var(--spacing-1);
}

.name-link {
  margin-bottom: var(--spacing-3);
  flex: 1;
}

.name {
  font-family: var(--font-body);
  font-size: 1rem;
  font-weight: 500;
  color: var(--color-white);
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-transform: none;
}

.name-link:hover .name {
  color: var(--color-red);
}

.price-box {
  display: flex;
  flex-direction: column;
  margin-bottom: var(--spacing-4);
}

.price-old {
  font-size: 0.875rem;
  color: var(--color-gray);
  text-decoration: line-through;
}

.price-new {
  font-family: var(--font-title);
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-white);
}

.installment {
  font-size: 0.75rem;
  color: var(--color-gray);
}

.add-cart-btn {
  width: 100%;
  padding: var(--spacing-2);
  font-size: 0.875rem;
  opacity: 0;
  transform: translateY(10px);
  transition: all 0.3s ease;
}

.product-card:hover .add-cart-btn {
  opacity: 1;
  transform: translateY(0);
}

@media (max-width: 768px) {
  .add-cart-btn {
    opacity: 1;
    transform: translateY(0);
  }
}

.product-card.is-esgotado {
  opacity: 0.6;
  filter: grayscale(1);
}
.product-card.is-esgotado:hover {
  border-color: var(--color-black-lighter) !important;
  box-shadow: none !important;
  transform: none !important;
}
</style>
