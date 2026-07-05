<template>
  <div class="product-view container">
    
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Carregando produto...</p>
    </div>

    <div v-else-if="!product" class="empty-state">
      <h2>Produto não encontrado</h2>
      <RouterLink to="/catalogo" class="btn btn-primary mt-4">Voltar ao Catálogo</RouterLink>
    </div>

    <template v-else>
      <!-- Breadcrumb -->
      <div class="breadcrumb">
        <RouterLink to="/">Home</RouterLink> / 
        <RouterLink to="/catalogo">Catálogo</RouterLink> / 
        <span>{{ product.nome }}</span>
      </div>

      <!-- Hero Produto -->
      <div class="product-hero grid grid-cols-2 gap-8">
        
        <!-- Galeria -->
        <div class="product-gallery">
          <div class="main-image-wrapper">
            <img :src="mainImage" class="main-image" :alt="product.nome" />
          </div>
          <div class="thumbnails mt-4">
             <img 
               v-for="(foto, idx) in product.fotos || [product.foto_capa]" 
               :key="idx" 
               :src="foto?.url" 
               class="thumbnail"
               :class="{ active: mainImage === foto?.url }"
               @click="mainImage = foto?.url"
             />
          </div>
        </div>

        <!-- Coluna da Direita (Infos) -->
        <div class="product-info-col">
          
          <div class="product-meta">
            <span class="brand">{{ product.marca || 'MARCA ORIGINAL' }}</span>
            <span class="gender-badge" v-if="product.genero">{{ product.genero }}</span>
          </div>
          
          <h1 class="product-title">{{ product.nome }}</h1>
          
          <div class="rating">
            <span class="stars">★★★★★</span>
            <span class="reviews-count">(12 avaliações)</span>
          </div>

          <div class="price-section mt-6">
            <template v-if="product.tem_desconto">
              <span class="price-old">R$ {{ formatMoney(product.preco_venda) }}</span>
              <div class="price-new">
                R$ {{ formatMoney(product.preco_desconto) }}
                <span class="discount-badge">-{{ calculateDiscountPercent() }}%</span>
              </div>
            </template>
            <template v-else>
              <div class="price-new">R$ {{ formatMoney(product.preco_venda) }}</div>
            </template>
            <p class="installments">ou em até 10x de R$ {{ formatMoney((product.preco_desconto || product.preco_venda) / 10) }} sem juros</p>
          </div>

          <!-- Seletor de Variações -->
          <div class="variations-section mt-6" v-if="availableColors.length > 0 || availableSizes.length > 0">
            <div class="variation-group" v-if="availableColors.length > 0">
              <label>Cor: <strong>{{ selectedColor || 'Selecione' }}</strong></label>
              <div class="color-options">
                <button 
                  v-for="color in availableColors" 
                  :key="color"
                  class="color-btn" 
                  :class="{ active: selectedColor === color }" 
                  :style="{ backgroundColor: getColorCode(color) }" 
                  @click="selectedColor = color; selectedSize = ''"
                  :title="color"
                ></button>
              </div>
            </div>
            
            <div class="variation-group mt-4" v-if="availableSizes.length > 0">
              <div class="size-header">
                <label>Tamanho: <strong>{{ selectedSize || 'Selecione' }}</strong></label>
                <button class="size-guide-btn">Tabela de Medidas</button>
              </div>
              <div class="size-options">
                <button 
                  v-for="size in availableSizes" 
                  :key="size"
                  class="size-btn" 
                  :class="{ active: selectedSize === size }" 
                  @click="selectedSize = size"
                >{{ size }}</button>
              </div>
            </div>
          </div>

          <div v-if="stockWarning" class="stock-warning mt-4">
            ⚠️ Últimas unidades em estoque!
          </div>

          <!-- Ações -->
          <div class="action-section mt-6">
            <div class="qty-selector">
              <button @click="quantity > 1 ? quantity-- : 1">-</button>
              <input type="number" v-model="quantity" min="1" readonly />
              <button @click="quantity++">+</button>
            </div>
            <button class="btn btn-primary add-to-cart-btn" @click="addToCart">
              🛒 ADICIONAR AO CARRINHO
            </button>
          </div>

          <!-- Frete -->
          <div class="shipping-section mt-8">
            <label>Calcule o Frete e Prazo</label>
            <div class="shipping-input-group">
              <input type="text" v-model="cep" placeholder="00000-000" class="input-field" maxlength="9" />
              <button class="btn btn-outline" @click="calculateShipping">OK</button>
            </div>
            <div v-if="shippingResult" class="shipping-result mt-4">
              <div class="shipping-option">
                <span>Padrão (Transportadora)</span>
                <strong>Grátis (8 a 10 dias úteis)</strong>
              </div>
              <div class="shipping-option">
                <span>Expresso (Sedex)</span>
                <strong>R$ 29,90 (2 a 3 dias úteis)</strong>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Detalhes e Infos -->
      <div class="product-details mt-12">
        <h3 class="section-title">DESCRIÇÃO DO PRODUTO</h3>
        <div class="desc-content">
          <p>{{ product.descricao || 'Este produto não possui uma descrição detalhada.' }}</p>
        </div>
      </div>

      <!-- Avaliações -->
      <div class="product-reviews mt-12">
        <h3 class="section-title">AVALIAÇÕES (12)</h3>
        <div class="review-item">
          <div class="review-header">
            <span class="stars">★★★★★</span>
            <strong>João S.</strong>
            <span class="review-date">10/10/2025</span>
          </div>
          <p class="review-text">Produto excelente! Qualidade do material me surpreendeu e chegou antes do prazo.</p>
        </div>
        <div class="review-item">
          <div class="review-header">
            <span class="stars">★★★★☆</span>
            <strong>Maria C.</strong>
            <span class="review-date">05/10/2025</span>
          </div>
          <p class="review-text">Muito bom, o tamanho P ficou perfeito. Só achei que a cor era um pouco mais escura.</p>
        </div>
      </div>

      <!-- Relacionados -->
      <div class="related-products mt-12 mb-12">
        <h3 class="section-title">QUEM VIU, COMPROU TAMBÉM</h3>
        <div class="grid grid-cols-4 gap-6 mt-6">
          <ProductCard 
            v-for="rel in relatedProducts" 
            :key="rel.id" 
            :product="rel" 
            @quick-view="$emit('open-drawer')"
          />
        </div>
      </div>

    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '@vueuse/head'
import axios from 'axios'
import ProductCard from '../components/ProductCard.vue'
import { useStore } from '../store/main'

const store = useStore()

const route = useRoute()

const product = ref(null)
const relatedProducts = ref([])
const loading = ref(true)

const mainImage = ref('')
const selectedColor = ref('')
const selectedSize = ref('')
const quantity = ref(1)
const stockWarning = ref(true) // Simulando alerta de estoque crítico

const availableColors = computed(() => {
  if (!product.value || !product.value.variacoes) return [];
  const cores = product.value.variacoes.map(v => v.cor).filter(Boolean);
  return [...new Set(cores)];
});

const availableSizes = computed(() => {
  if (!product.value || !product.value.variacoes) return [];
  let vars = product.value.variacoes;
  if (selectedColor.value) {
    vars = vars.filter(v => v.cor === selectedColor.value);
  }
  const sizes = vars.map(v => v.tamanho).filter(Boolean);
  return [...new Set(sizes)];
});

function getColorCode(colorName) {
  const map = {
    'Preto': '#000',
    'Branco': '#fff',
    'Vermelho': '#e30613',
    'Amarelo': '#ffd700',
    'Azul': '#0000ff',
    'Verde': '#008000',
    'Cinza': '#808080',
    'Laranja': '#ffa500',
    'Rosa': '#ffc0cb',
    'Roxo': '#800080',
    'Marrom': '#8b4513'
  };
  return map[colorName] || '#ccc';
}

const cep = ref('')
const shippingResult = ref(false)

useHead({
  title: () => `${product.value?.nome || 'Produto'} | 90+ Store`,
  meta: [
    { name: 'description', content: () => product.value?.descricao_curta || 'Compre agora na 90+ Store.' }
  ]
})

onMounted(() => {
  fetchProduct()
})

watch(() => route.params.slug, () => {
  fetchProduct()
})

async function fetchProduct() {
  loading.value = true
  try {
    const slug = route.params.slug
    const res = await axios.get(`/api/catalog?produto=${slug}`)
    if (res.data.produtos && res.data.produtos.length > 0) {
      product.value = res.data.produtos[0]
      mainImage.value = product.value.foto_capa?.url || 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500'
      fetchRelated()
    } else {
      product.value = null
    }
  } catch (err) {
    console.error('Erro ao buscar produto', err)
  } finally {
    loading.value = false
  }
}

async function fetchRelated() {
  try {
    const res = await axios.get(`/api/catalog?limit=4`)
    relatedProducts.value = res.data.produtos || []
  } catch (e) { }
}

function calculateDiscountPercent() {
  if (!product.value || !product.value.tem_desconto) return 0
  const off = ((product.value.preco_venda - product.value.preco_desconto) / product.value.preco_venda) * 100
  return Math.round(off)
}

function addToCart() {
  if (!selectedSize.value || !selectedColor.value) {
    alert('Selecione cor e tamanho para continuar.')
    return
  }

  const variation = product.value.variacoes.find(v => v.tamanho === selectedSize.value && v.cor === selectedColor.value)
  
  if (!variation) {
    alert('Esta combinação de cor e tamanho está indisponível.')
    return
  }

  store.addToCart(product.value, variation, quantity.value)
  window.dispatchEvent(new CustomEvent('open-cart-drawer'))
}

function calculateShipping() {
  if (cep.value.length >= 8) {
    shippingResult.value = true
  } else {
    alert('Digite um CEP válido')
  }
}

function formatMoney(val) {
  if (!val) return '0,00'
  return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.product-view {
  padding: var(--spacing-6) var(--spacing-4);
}

.breadcrumb {
  font-size: 0.875rem;
  color: var(--color-gray);
  margin-bottom: var(--spacing-6);
}

.breadcrumb a {
  color: var(--color-gray);
}
.breadcrumb a:hover {
  color: var(--color-white);
}

/* Galeria */
.main-image-wrapper {
  background-color: var(--color-black-lighter);
  border-radius: var(--border-radius);
  overflow: hidden;
  aspect-ratio: 1/1;
}

.main-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: transform 0.3s;
}

.main-image:hover {
  transform: scale(1.1); /* Simple zoom */
}

.thumbnails {
  display: flex;
  gap: var(--spacing-4);
  overflow-x: auto;
}

.thumbnail {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: var(--border-radius-sm);
  cursor: pointer;
  border: 2px solid transparent;
  transition: var(--transition);
}

.thumbnail:hover, .thumbnail.active {
  border-color: var(--color-red);
}

/* Buy Box */
.product-info-col {
  display: flex;
  flex-direction: column;
}

.brand {
  font-size: 0.75rem;
  letter-spacing: 0.1em;
  color: var(--store-text-muted);
  text-transform: uppercase;
}

.gender-badge {
  background-color: var(--store-primary);
  color: white;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.65rem;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.product-title {
  font-family: var(--font-title);
  font-size: 2.5rem;
  line-height: 1.1;
  margin: var(--spacing-2) 0;
  color: var(--color-white);
}

.rating-box {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

.stars {
  color: #fbbf24;
  font-size: 1rem;
}

.rating-text {
  color: var(--color-gray);
  font-size: 0.875rem;
}

.price-old {
  color: var(--color-gray);
  text-decoration: line-through;
  font-size: 1.125rem;
}

.price-new {
  font-family: var(--font-title);
  font-size: 2.5rem;
  color: var(--color-red);
  display: flex;
  align-items: center;
  gap: var(--spacing-4);
}

.discount-badge {
  background-color: var(--color-red);
  color: var(--color-white);
  font-size: 1rem;
  padding: var(--spacing-1) var(--spacing-2);
  border-radius: var(--border-radius-sm);
}

.installments {
  color: var(--color-gray);
  font-size: 1rem;
}

.variation-group label {
  display: block;
  font-size: 0.875rem;
  color: var(--color-gray);
  margin-bottom: var(--spacing-2);
}

.variation-group strong {
  color: var(--color-white);
}

.color-options {
  display: flex;
  gap: var(--spacing-3);
}

.color-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid transparent;
  cursor: pointer;
  box-shadow: 0 0 0 1px var(--color-gray-dark);
}

.color-btn:hover, .color-btn.active {
  box-shadow: 0 0 0 2px var(--color-white);
}

.size-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
}

.size-guide-btn {
  color: var(--color-gray);
  text-decoration: underline;
  font-size: 0.875rem;
}

.size-guide-btn:hover {
  color: var(--color-white);
}

.size-options {
  display: flex;
  gap: var(--spacing-2);
  flex-wrap: wrap;
}

.size-btn {
  min-width: 50px;
  height: 50px;
  border: 1px solid var(--color-gray-dark);
  background: transparent;
  color: var(--color-white);
  border-radius: var(--border-radius-sm);
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
}

.size-btn:hover {
  border-color: var(--color-white);
}

.size-btn.active {
  background-color: var(--color-white);
  color: var(--color-black);
  border-color: var(--color-white);
}

.stock-warning {
  color: #f59e0b; /* yellow */
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

.action-section {
  display: flex;
  gap: var(--spacing-4);
}

.qty-selector {
  display: flex;
  align-items: center;
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  background-color: var(--color-black-light);
}

.qty-selector button {
  width: 40px;
  height: 50px;
  color: var(--color-white);
  font-size: 1.25rem;
}

.qty-selector button:hover {
  color: var(--color-red);
}

.qty-selector input {
  width: 40px;
  height: 50px;
  background: transparent;
  border: none;
  color: var(--color-white);
  text-align: center;
  font-weight: 600;
}
.qty-selector input:focus {
  outline: none;
}

.add-to-cart-btn {
  flex: 1;
  height: 50px;
  font-size: 1.125rem;
}

.shipping-section {
  background-color: var(--color-black-light);
  padding: var(--spacing-4);
  border-radius: var(--border-radius-sm);
}

.shipping-section label {
  display: block;
  font-weight: 600;
  margin-bottom: var(--spacing-2);
}

.shipping-input-group {
  display: flex;
  gap: var(--spacing-2);
}

.shipping-input-group .input-field {
  flex: 1;
}

.shipping-option {
  display: flex;
  justify-content: space-between;
  padding: var(--spacing-2) 0;
  border-bottom: 1px solid var(--color-black-lighter);
  font-size: 0.875rem;
}
.shipping-option:last-child {
  border-bottom: none;
}

/* Sections */
.section-title {
  font-family: var(--font-title);
  font-size: 1.5rem;
  border-bottom: 2px solid var(--color-black-lighter);
  padding-bottom: var(--spacing-2);
  margin-bottom: var(--spacing-4);
}

.desc-content p {
  color: var(--color-gray);
  line-height: 1.8;
}

.review-item {
  background-color: var(--color-black-light);
  padding: var(--spacing-4);
  border-radius: var(--border-radius-sm);
  margin-bottom: var(--spacing-4);
}

.review-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-2);
}

.review-date {
  color: var(--color-gray);
  font-size: 0.875rem;
  margin-left: auto;
}

.review-text {
  color: var(--color-gray);
  line-height: 1.6;
}

/* Utils */
.mt-4 { margin-top: var(--spacing-4); }
.mt-6 { margin-top: var(--spacing-6); }
.mt-8 { margin-top: var(--spacing-8); }
.mt-12 { margin-top: var(--spacing-12); }
.mb-12 { margin-bottom: var(--spacing-12); }
.loading-state, .empty-state {
  text-align: center;
  padding: var(--spacing-16) 0;
}

@media (max-width: 768px) {
  .product-hero { grid-template-columns: 1fr; }
  .action-section { flex-direction: column; }
  .qty-selector { width: 100%; justify-content: space-between; }
  .qty-selector input { flex: 1; }
}
</style>
