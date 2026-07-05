<template>
  <div class="catalog-view container">
    <!-- Breadcrumb & Header -->
    <div class="catalog-header">
      <div class="breadcrumb">
        <RouterLink to="/">Home</RouterLink> / <span>Catálogo</span>
      </div>
      <h1 class="title-lg mt-2">Catálogo de Produtos</h1>
      <p class="subtitle">{{ totalProducts }} produtos encontrados</p>
    </div>

    <div class="catalog-layout">
      <!-- Sidebar de Filtros -->
      <aside class="catalog-sidebar">
        <div class="filter-section">
          <h3 class="filter-title">Categorias</h3>
          <ul class="filter-list">
            <li>
              <label class="checkbox-label">
                <input type="checkbox" v-model="filters.categories" value="lancamentos" @change="applyFilters"> Lançamentos
              </label>
            </li>
            <li>
              <label class="checkbox-label">
                <input type="checkbox" v-model="filters.categories" value="masculino" @change="applyFilters"> Masculino
              </label>
            </li>
            <li>
              <label class="checkbox-label">
                <input type="checkbox" v-model="filters.categories" value="feminino" @change="applyFilters"> Feminino
              </label>
            </li>
            <li>
              <label class="checkbox-label">
                <input type="checkbox" v-model="filters.categories" value="calcados" @change="applyFilters"> Calçados
              </label>
            </li>
          </ul>
        </div>

        <div class="filter-section">
          <h3 class="filter-title">Marcas</h3>
          <ul class="filter-list">
            <li v-for="brand in ['Nike', 'Adidas', 'Puma', 'Lacoste', 'New Balance', 'Under Armour']" :key="brand">
              <label class="checkbox-label">
                <input type="checkbox" v-model="filters.brands" :value="brand.toLowerCase()" @change="applyFilters"> {{ brand }}
              </label>
            </li>
          </ul>
        </div>

        <div class="filter-section">
          <h3 class="filter-title">Faixa de Preço</h3>
          <div class="price-inputs">
            <input type="number" v-model="filters.minPrice" placeholder="Mín (R$)" class="input-field" @blur="applyFilters" />
            <span>-</span>
            <input type="number" v-model="filters.maxPrice" placeholder="Máx (R$)" class="input-field" @blur="applyFilters" />
          </div>
        </div>

        <button class="btn btn-outline w-full" @click="clearFilters">Limpar Filtros</button>
      </aside>

      <!-- Main Content (Grid) -->
      <div class="catalog-main">
        <!-- Toolbar (Sort) -->
        <div class="catalog-toolbar">
          <div class="active-filters">
             <span v-if="filters.categories.length" class="badge badge-dark">Categorias: {{ filters.categories.length }}</span>
             <span v-if="filters.brands.length" class="badge badge-dark">Marcas: {{ filters.brands.length }}</span>
          </div>
          <div class="sort-box">
            <label>Ordenar por:</label>
            <select class="input-field select-field" v-model="filters.sort" @change="applyFilters">
              <option value="relevance">Relevância</option>
              <option value="lowest_price">Menor Preço</option>
              <option value="highest_price">Maior Preço</option>
              <option value="newest">Lançamentos</option>
            </select>
          </div>
        </div>

        <!-- Produtos Grid -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Buscando produtos...</p>
        </div>

        <div v-else-if="products.length === 0" class="empty-state">
          <p>Nenhum produto encontrado com os filtros selecionados.</p>
          <button class="btn btn-primary mt-4" @click="clearFilters">Limpar Filtros</button>
        </div>

        <div v-else class="grid grid-cols-3 gap-6">
          <ProductCard 
            v-for="product in products" 
            :key="product.id" 
            :product="product" 
            @quick-view="openQuickView"
          />
        </div>

        <!-- Paginação -->
        <div v-if="products.length > 0" class="pagination mt-8">
          <button class="page-btn">Anterior</button>
          <button class="page-btn active">1</button>
          <button class="page-btn">2</button>
          <button class="page-btn">3</button>
          <button class="page-btn">Próxima</button>
        </div>
      </div>
    </div>

    <!-- Quick View Drawer -->
    <ProductQuickView 
      :is-open="!!quickViewProduct" 
      :product="quickViewProduct" 
      @close="closeQuickView"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useHead } from '@vueuse/head'
import ProductCard from '../components/ProductCard.vue'
import ProductQuickView from '../components/ProductQuickView.vue'

const route = useRoute()
const router = useRouter()

const products = ref([])
const totalProducts = ref(0)
const loading = ref(true)
const quickViewProduct = ref(null)

const filters = reactive({
  categories: [],
  brands: [],
  minPrice: '',
  maxPrice: '',
  sort: 'relevance'
})

useHead({
  title: 'Catálogo de Produtos | 90+ Store',
  meta: [
    { name: 'description', content: 'Explore nossa seleção de produtos esportivos.' }
  ]
})

onMounted(() => {
  // Sync query params to state
  if (route.query.categoria) {
    filters.categories.push(route.query.categoria)
  }
  if (route.query.genero) {
    filters.categories.push(route.query.genero)
  }
  if (route.query.marca) {
    filters.brands.push(route.query.marca.toLowerCase())
  }
  fetchProducts()
})

// Re-fetch se a rota (query string) mudar via header
watch(() => route.query, () => {
  filters.categories = []
  filters.brands = []
  if (route.query.categoria) filters.categories.push(route.query.categoria)
  if (route.query.genero) filters.categories.push(route.query.genero)
  if (route.query.marca) filters.brands.push(route.query.marca.toLowerCase())
  fetchProducts()
})

async function fetchProducts() {
  loading.value = true
  try {
    // Montando a querystring baseada nos filtros
    let qs = '?'
    if (filters.categories.length) qs += `categoria=${filters.categories[0]}&`
    if (filters.brands.length) qs += `marca=${filters.brands[0]}&`
    if (filters.sort) qs += `sort=${filters.sort}`
    
    // Na vida real passaríamos os arrays de marcas/categorias pro endpoint
    // Para simplificar, consumimos o /api/catalog e simulamos na view caso seja necessário
    
    const res = await axios.get(`/api/catalog${qs}`)
    products.value = res.data.produtos || []
    totalProducts.value = products.value.length
  } catch (err) {
    console.error('Erro ao buscar produtos', err)
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  fetchProducts()
}

function clearFilters() {
  filters.categories = []
  filters.brands = []
  filters.minPrice = ''
  filters.maxPrice = ''
  filters.sort = 'relevance'
  router.push('/catalogo')
  fetchProducts()
}

function openQuickView(product) {
  quickViewProduct.value = product
  document.body.style.overflow = 'hidden'
}

function closeQuickView() {
  quickViewProduct.value = null
  document.body.style.overflow = ''
}
</script>

<style scoped>
.catalog-view {
  padding: var(--spacing-8) var(--spacing-4);
}

.catalog-header {
  margin-bottom: var(--spacing-8);
  border-bottom: 1px solid var(--color-black-lighter);
  padding-bottom: var(--spacing-6);
}

.breadcrumb {
  font-size: 0.875rem;
  color: var(--color-gray);
  margin-bottom: var(--spacing-2);
}

.breadcrumb a {
  color: var(--color-gray);
}

.breadcrumb a:hover {
  color: var(--color-white);
}

.subtitle {
  color: var(--color-gray);
  margin-top: var(--spacing-2);
}

.catalog-layout {
  display: flex;
  gap: var(--spacing-8);
}

.catalog-sidebar {
  width: 250px;
  flex-shrink: 0;
}

.filter-section {
  margin-bottom: var(--spacing-6);
  padding-bottom: var(--spacing-6);
  border-bottom: 1px solid var(--color-black-lighter);
}

.filter-title {
  font-size: 1.125rem;
  margin-bottom: var(--spacing-4);
  text-transform: uppercase;
}

.filter-list li {
  margin-bottom: var(--spacing-2);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  cursor: pointer;
  color: var(--color-gray);
}

.checkbox-label:hover {
  color: var(--color-white);
}

.checkbox-label input[type="checkbox"] {
  accent-color: var(--color-red);
  width: 16px;
  height: 16px;
}

.price-inputs {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

.price-inputs .input-field {
  width: 100%;
  padding: var(--spacing-2);
  font-size: 0.875rem;
}

.catalog-main {
  flex: 1;
}

.catalog-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-6);
  padding: var(--spacing-2) 0;
}

.active-filters {
  display: flex;
  gap: var(--spacing-2);
}

.sort-box {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

.sort-box label {
  color: var(--color-gray);
  font-size: 0.875rem;
}

.select-field {
  padding: var(--spacing-2) var(--spacing-4);
  background-color: var(--color-black-light);
  color: var(--color-white);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
}

.loading-state, .empty-state {
  text-align: center;
  padding: var(--spacing-16) 0;
  color: var(--color-gray);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--color-black-lighter);
  border-top-color: var(--color-red);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto var(--spacing-4);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.pagination {
  display: flex;
  justify-content: center;
  gap: var(--spacing-2);
}

.page-btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--color-black-light);
  border: 1px solid var(--color-black-lighter);
  color: var(--color-white);
  border-radius: var(--border-radius-sm);
}

.page-btn:hover {
  background-color: var(--color-red);
  border-color: var(--color-red);
}

.page-btn.active {
  background-color: var(--color-red);
  border-color: var(--color-red);
}

.w-full { width: 100%; }
.mt-2 { margin-top: var(--spacing-2); }
.mt-4 { margin-top: var(--spacing-4); }
.mt-8 { margin-top: var(--spacing-8); }

@media (max-width: 1024px) {
  .catalog-layout {
    flex-direction: column;
  }
  .catalog-sidebar {
    width: 100%;
  }
  .grid-cols-3 {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .grid-cols-3 {
    grid-template-columns: 1fr;
  }
  .catalog-toolbar {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-4);
  }
}
</style>
