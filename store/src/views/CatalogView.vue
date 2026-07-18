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

    <!-- Filtros mobile: botão -->
    <div class="mobile-filter-bar">
      <button class="btn-filter-mobile" @click="mobileFiltersOpen = true">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"></line><line x1="8" y1="12" x2="16" y2="12"></line><line x1="11" y1="18" x2="13" y2="18"></line></svg>
        Filtros
        <span v-if="activeFilterCount > 0" class="filter-count-badge">{{ activeFilterCount }}</span>
      </button>
      <select class="input-field select-field mobile-sort" v-model="filters.sort" @change="applyFilters">
        <option value="relevance">Relevância</option>
        <option value="lowest_price">Menor Preço</option>
        <option value="highest_price">Maior Preço</option>
        <option value="newest">Lançamentos</option>
      </select>
    </div>

    <!-- Overlay filtros mobile -->
    <div v-if="mobileFiltersOpen" class="filter-overlay" @click="mobileFiltersOpen = false"></div>

    <div class="catalog-layout">
      <!-- Sidebar de Filtros -->
      <aside class="catalog-sidebar" :class="{ 'mobile-open': mobileFiltersOpen }">
        <!-- Cabeçalho mobile do drawer -->
        <div class="sidebar-mobile-header">
          <h3 style="font-size: 1rem; color: var(--color-white);">Filtros</h3>
          <button @click="mobileFiltersOpen = false" style="color: var(--color-gray); background: none; border: none; cursor: pointer; font-size: 1.5rem; line-height: 1;">&#x2715;</button>
        </div>
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
                <input type="checkbox" v-model="filters.categories" value="infantil" @change="applyFilters"> Infantil
              </label>
            </li>
            <li>
              <label class="checkbox-label">
                <input type="checkbox" v-model="filters.categories" value="calcados" @change="applyFilters"> Calçados
              </label>
            </li>
          </ul>
        </div>

        <div class="filter-section" v-if="availableBrands.length > 0">
          <h3 class="filter-title">Marcas</h3>
          <ul class="filter-list">
            <li v-for="brand in availableBrands" :key="brand">
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

        <button class="btn btn-outline w-full" @click="clearFilters; mobileFiltersOpen = false">Limpar Filtros</button>
      </aside>

      <!-- Main Content (Grid) -->
      <div class="catalog-main">
        <!-- Toolbar (Sort) - desktop -->
        <div class="catalog-toolbar desktop-toolbar">
          <div class="active-filters">
             <span v-if="filters.search" class="badge badge-dark">Busca: "{{ filters.search }}"</span>
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
            v-for="product in products.slice(0, visibleCount)" 
            :key="product.id" 
            :product="product" 
            @quick-view="openQuickView"
          />
        </div>

        <!-- Botão Ver Mais -->
        <div v-if="products.length > visibleCount" class="load-more-container mt-12 text-center" style="display: flex; justify-content: center; width: 100%;">
          <button class="btn btn-primary" style="padding: var(--spacing-3) var(--spacing-8); font-family: var(--font-title); font-size: 1.1rem;" @click="visibleCount += 12">
            Ver Mais
          </button>
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
import { ref, onMounted, reactive, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useHead } from '@vueuse/head'
import ProductCard from '../components/ProductCard.vue'
import ProductQuickView from '../components/ProductQuickView.vue'

const route = useRoute()
const router = useRouter()

const products = ref([])
const availableBrands = ref([])
const totalProducts = ref(0)
const loading = ref(true)
const quickViewProduct = ref(null)
const visibleCount = ref(12)
const mobileFiltersOpen = ref(false)

const activeFilterCount = computed(() => {
  let count = 0
  if (filters.categories.length) count++
  if (filters.brands.length) count++
  if (filters.minPrice || filters.maxPrice) count++
  return count
})

const filters = reactive({
  categories: [],
  brands: [],
  minPrice: '',
  maxPrice: '',
  sort: 'relevance',
  search: ''
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
  if (route.query.search) {
    filters.search = route.query.search
  }
  fetchProducts()
})

// Re-fetch se a rota (query string) mudar via header
watch(() => route.query, () => {
  filters.categories = []
  filters.brands = []
  filters.search = route.query.search || ''
  if (route.query.categoria) filters.categories.push(route.query.categoria)
  if (route.query.genero) filters.categories.push(route.query.genero)
  if (route.query.marca) filters.brands.push(route.query.marca.toLowerCase())
  fetchProducts()
})

async function fetchProducts() {
  loading.value = true
  visibleCount.value = 12
  try {
    // Montando a querystring baseada nos filtros
    let qs = '?'
    if (filters.categories.length) qs += `categoria=${filters.categories[0]}&`
    if (filters.brands.length) qs += `marca=${filters.brands[0]}&`
    if (filters.search) qs += `search=${encodeURIComponent(filters.search)}&`
    if (filters.sort) qs += `sort=${filters.sort}`
    
    // Na vida real passaríamos os arrays de marcas/categorias pro endpoint
    // Para simplificar, consumimos o /api/catalog e simulamos na view caso seja necessário
    
    const res = await axios.get(`/api/catalog${qs}`)
    products.value = res.data.produtos || []
    totalProducts.value = products.value.length
    if (res.data.marcas) {
      availableBrands.value = res.data.marcas
    }
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
  filters.search = ''
  visibleCount.value = 12
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

/* Mobile filter bar */
.mobile-filter-bar {
  display: none;
  gap: var(--spacing-3);
  margin-bottom: var(--spacing-4);
  align-items: center;
}

.btn-filter-mobile {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-2) var(--spacing-4);
  background-color: var(--color-black-light);
  border: 1px solid var(--color-black-lighter);
  border-radius: var(--border-radius-sm);
  color: var(--color-white);
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
  position: relative;
}

.btn-filter-mobile:hover { border-color: var(--color-red); }

.filter-count-badge {
  background-color: var(--color-red);
  color: white;
  font-size: 0.7rem;
  font-weight: 700;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-left: 2px;
}

.mobile-sort { flex: 1; font-size: 0.8rem; padding: var(--spacing-2); }

/* Filter Overlay */
.filter-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  z-index: 1090;
  backdrop-filter: blur(2px);
}

/* Sidebar mobile header */
.sidebar-mobile-header {
  display: none;
  align-items: center;
  justify-content: space-between;
  padding-bottom: var(--spacing-4);
  margin-bottom: var(--spacing-4);
  border-bottom: 1px solid var(--color-black-lighter);
}

.desktop-toolbar { display: flex; }

@media (max-width: 1024px) {
  .catalog-layout { flex-direction: column; }
  .catalog-sidebar { width: 100%; }
  .desktop-toolbar { display: flex; }
}

@media (max-width: 768px) {
  /* Mobile filter bar visible */
  .mobile-filter-bar { display: flex; }
  .desktop-toolbar { display: none; }

  /* Sidebar becomes a drawer */
  .catalog-sidebar {
    position: fixed;
    top: 0;
    left: -100%;
    height: 100vh;
    width: min(300px, 85vw);
    z-index: 1100;
    background-color: var(--color-black-light);
    border-right: 1px solid var(--color-black-lighter);
    overflow-y: auto;
    padding: var(--spacing-6);
    transition: left 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .catalog-sidebar.mobile-open { left: 0; }
  .sidebar-mobile-header { display: flex; }

  .catalog-layout { flex-direction: column; }

  .grid-cols-3 { grid-template-columns: repeat(2, 1fr); }

  .catalog-view { padding: var(--spacing-4) var(--spacing-3); }
}

@media (max-width: 480px) {
  .grid-cols-3 { grid-template-columns: repeat(2, 1fr); }
}
</style>
