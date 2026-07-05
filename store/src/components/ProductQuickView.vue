<template>
  <div v-if="isOpen" class="drawer-overlay" @click="close">
    <div class="drawer-content" @click.stop>
      <div class="drawer-header">
        <h3 class="title-sm">Compra Rápida</h3>
        <button class="close-btn" @click="close">✕</button>
      </div>
      
      <div class="drawer-body" v-if="product">
        <!-- Galeria simples -->
        <div class="gallery">
          <img :src="mainImage" class="main-image" :alt="product.nome" />
          <div class="thumbnails">
             <!-- Placeholder para miniaturas se houver mais fotos -->
             <img 
               v-for="(foto, idx) in product.fotos || [product.foto_capa]" 
               :key="idx" 
               :src="foto?.url" 
               class="thumbnail"
               @click="mainImage = foto?.url"
             />
          </div>
        </div>

        <div class="product-info mt-4">
          <div class="brand">{{ product.fornecedor?.nome_fantasia || 'Marca Original' }}</div>
          <h2 class="name">{{ product.nome }}</h2>
          <div class="star-rating">★★★★★ <small>(12 avaliações)</small></div>
          
          <div class="price-box mt-4">
            <template v-if="product.tem_desconto">
              <span class="price-old">R$ {{ formatMoney(product.preco_venda) }}</span>
              <span class="price-new">R$ {{ formatMoney(product.preco_desconto) }}</span>
            </template>
            <template v-else>
              <span class="price-new">R$ {{ formatMoney(product.preco_venda) }}</span>
            </template>
          </div>

          <!-- Variações -->
          <div class="variations mt-6">
            <div class="variation-group">
              <label>Tamanho:</label>
              <div class="variation-options">
                <button class="var-btn" :class="{ active: selectedSize === 'P' }" @click="selectedSize = 'P'">P</button>
                <button class="var-btn" :class="{ active: selectedSize === 'M' }" @click="selectedSize = 'M'">M</button>
                <button class="var-btn" :class="{ active: selectedSize === 'G' }" @click="selectedSize = 'G'">G</button>
              </div>
            </div>
          </div>

          <!-- Ações -->
          <div class="actions mt-6">
            <button class="btn btn-primary w-full" @click="addToCart">ADICIONAR AO CARRINHO</button>
            <RouterLink :to="`/produto/${product.slug}`" class="btn btn-outline w-full mt-2">
              Ver Detalhes Completos
            </RouterLink>
          </div>

          <!-- Infos -->
          <div class="description mt-6">
            <p>{{ product.descricao_curta || 'Produto esportivo de alta performance.' }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  product: { type: Object, default: null }
})

const emit = defineEmits(['close'])

const mainImage = ref('')
const selectedSize = ref('')

watch(() => props.product, (newVal) => {
  if (newVal) {
    mainImage.value = newVal.foto_capa?.url || 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500'
    selectedSize.value = ''
  }
})

function close() {
  emit('close')
}

function addToCart() {
  if (!selectedSize.value) {
    alert('Por favor, selecione um tamanho.')
    return
  }
  alert(`Adicionado ao carrinho: ${props.product.nome} - Tam: ${selectedSize.value}`)
  close()
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
  padding: var(--spacing-4);
  flex: 1;
  overflow-y: auto;
}

.gallery .main-image {
  width: 100%;
  aspect-ratio: 1/1;
  object-fit: cover;
  background-color: var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
}

.thumbnails {
  display: flex;
  gap: var(--spacing-2);
  margin-top: var(--spacing-2);
  overflow-x: auto;
}

.thumbnail {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border: 2px solid transparent;
  cursor: pointer;
  border-radius: var(--border-radius-sm);
}

.thumbnail:hover, .thumbnail.active {
  border-color: var(--color-red);
}

.brand {
  color: var(--color-gray);
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 1px;
}

.name {
  font-size: 1.25rem;
  margin: var(--spacing-1) 0;
  line-height: 1.3;
}

.star-rating {
  color: #fbbf24;
  font-size: 0.875rem;
}

.star-rating small {
  color: var(--color-gray);
}

.price-box {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
}

.price-old {
  color: var(--color-gray);
  text-decoration: line-through;
}

.price-new {
  font-family: var(--font-title);
  font-size: 1.5rem;
  color: var(--color-red);
}

.variation-group label {
  display: block;
  font-weight: 600;
  margin-bottom: var(--spacing-2);
}

.variation-options {
  display: flex;
  gap: var(--spacing-2);
}

.var-btn {
  width: 40px;
  height: 40px;
  border: 1px solid var(--color-gray-dark);
  background-color: transparent;
  color: var(--color-white);
  border-radius: var(--border-radius-sm);
}

.var-btn:hover {
  border-color: var(--color-white);
}

.var-btn.active {
  background-color: var(--color-white);
  color: var(--color-black);
  border-color: var(--color-white);
}

.w-full {
  width: 100%;
}

.mt-2 { margin-top: var(--spacing-2); }
.mt-4 { margin-top: var(--spacing-4); }
.mt-6 { margin-top: var(--spacing-6); }

.description {
  color: var(--color-gray);
  font-size: 0.9rem;
  line-height: 1.6;
}
</style>
