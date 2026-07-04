<template>
  <div>
    <section class="hero-section mb-6">
      <h1>Artigos Esportivos Premium</h1>
      <p class="text-secondary mt-1">Camisetas, agasalhos e chuteiras oficiais em até 10x sem juros</p>
    </section>

    <!-- Categorias Bar -->
    <div class="categories-bar mb-6">
      <button 
        class="category-btn" 
        :class="{ active: !selectedCategory }" 
        @click="filterCategory(null)"
      >
        Todos
      </button>
      <button 
        v-for="cat in categories" 
        :key="cat.id" 
        class="category-btn" 
        :class="{ active: selectedCategory === cat.slug }" 
        @click="filterCategory(cat.slug)"
      >
        {{ cat.nome }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-6">
      <div class="spinner"></div>
      <p class="text-secondary mt-2">Carregando vitrine...</p>
    </div>

    <!-- Vitrine Grid -->
    <div v-else class="grid-cols-4">
      <div v-for="product in products" :key="product.id" class="store-card">
        <RouterLink :to="`/produtos/${product.slug}`">
          <img 
            :src="product.foto_capa?.url || 'https://via.placeholder.com/400?text=Sem+Foto'" 
            class="store-card-image" 
            :alt="product.nome" 
          />
        </RouterLink>
        <div class="store-card-body">
          <span class="category-badge">{{ product.categoria?.nome }}</span>
          <h3 class="product-title mt-1">{{ product.nome }}</h3>
          
          <div class="price-container mt-2">
            <template v-if="product.tem_desconto">
              <span class="price-old">R$ {{ formatMoney(product.preco_venda) }}</span>
              <span class="price-new">R$ {{ formatMoney(product.preco_desconto) }}</span>
            </template>
            <template v-else>
              <span class="price-new">R$ {{ formatMoney(product.preco_venda) }}</span>
            </template>
          </div>

          <div class="actions mt-4">
            <RouterLink :to="`/produtos/${product.slug}`" class="store-btn store-btn-primary w-full">
              Ver Detalhes
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import axios from 'axios'
import { useHead } from '@vueuse/head'

const products = ref([])
const categories = ref([])
const loading = ref(true)
const selectedCategory = ref(null)

useHead({
  title: 'Vitrine Esportiva | Camisetas, Chuteiras e Jaquetas',
  meta: [
    { name: 'description', content: 'Explore a melhor seleção de artigos esportivos nacionais, importados e retrô na 90-Store.' }
  ]
})

onMounted(() => {
  fetchCatalog()
})

async function fetchCatalog(categorySlug = null) {
  loading.value = true
  try {
    const url = categorySlug ? `/api/catalog?categoria=${categorySlug}` : '/api/catalog'
    const res = await axios.get(url)
    products.value = res.data.produtos
    categories.value = res.data.categorias
  } catch (err) {
    console.error('Erro ao buscar vitrine', err)
  } finally {
    loading.value = false
  }
}

function filterCategory(slug) {
  selectedCategory.value = slug
  fetchCatalog(slug)
}

function formatMoney(val) {
  return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.hero-section {
  text-align: center;
  padding: 3rem 1rem;
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(6, 182, 212, 0.05));
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
}

.hero-section h1 {
  font-family: 'Outfit', sans-serif;
  font-size: 2.5rem;
  font-weight: 950;
  background: linear-gradient(90deg, #fff, var(--text-secondary));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.categories-bar {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
  padding-bottom: 0.5rem;
}

.category-btn {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  color: var(--text-secondary);
  padding: 0.5rem 1.25rem;
  border-radius: var(--radius-full);
  cursor: pointer;
  font-family: inherit;
  font-weight: 500;
  white-space: nowrap;
  transition: var(--transition-smooth);
}

.category-btn:hover, .category-btn.active {
  background: var(--brand-primary);
  color: #fff;
  border-color: var(--brand-primary);
}

.category-badge {
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--accent-cyan);
  letter-spacing: 0.05em;
}

.product-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  height: 3rem;
}

.price-container {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
}

.price-old {
  font-size: 0.8125rem;
  color: var(--text-muted);
  text-decoration: line-through;
}

.price-new {
  font-size: 1.125rem;
  font-weight: 800;
  color: var(--text-primary);
}

.spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid var(--border-color);
  border-top-color: var(--brand-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto;
}

@keyframes spin { to { transform: rotate(360deg); } }

.py-6 { padding: 3rem 0; }
.w-full { width: 100%; }
.mt-2 { margin-top: 0.5rem; }
.mt-4 { margin-top: 1rem; }
.mb-6 { margin-bottom: 1.5rem; }
.font-bold { font-weight: 700; }
</style>
